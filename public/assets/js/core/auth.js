/**
 * AliveChMS Authentication Manager
 * 
 * Secure authentication with:
 * - Access token in memory (not localStorage) - XSS safe
 * - Refresh token in HttpOnly cookie (server-managed) - XSS proof
 * - CSRF token for state-changing requests
 * 
 * @version 2.0.1
 */

const Auth = {
    // Private in-memory storage (not accessible via XSS)
    _accessToken: null,
    _csrfToken: null,
    _user: null,
    _refreshInterval: null,
    _initialized: false,
    
    /**
     * Login user
     * @param {string} username - Username
     * @param {string} password - Password
     * @param {boolean} remember - Remember me
     * @returns {Promise<Object>} User data
     */
    async login(username, password, remember = false) {
        try {
            const response = await api.post('auth/login', {
                userid: username,
                passkey: password,
                remember: remember
            });
            
            // Debug: log the actual response structure
            Config.log('Login response:', response);
            
            // Handle both wrapped and unwrapped response formats
            // api.js extracts data.data, but Auth::login includes status in its return
            const data = response.access_token ? response : (response.data || response);
            
            if (data.access_token) {
                // Clear any legacy localStorage tokens first
                localStorage.removeItem(Config.TOKEN_KEY);
                localStorage.removeItem(Config.REFRESH_TOKEN_KEY);
                localStorage.removeItem(Config.USER_KEY);
                
                // Store access token in memory (secure)
                this._accessToken = data.access_token;
                
                // Store CSRF token
                this._csrfToken = data.csrf_token;
                
                // Store user data in memory
                this._user = {
                    ...data.user,
                    permissions: this.extractPermissions(data.user)
                };
                
                // Persist user info and access token for page navigation
                this._persistSession();
                
                // Mark as initialized - don't try to restore session
                this._initialized = true;
                
                // Set up auto-refresh (but don't refresh immediately!)
                this.setupTokenRefresh();
                
                Config.log('Login successful, token stored:', this._accessToken ? 'yes' : 'no');
                return this._user;
            } else {
                throw new Error(data.message || response.message || 'Login failed');
            }
        } catch (error) {
            Config.error('Login error', error);
            throw error;
        }
    },
    
    /**
     * Logout user
     * @returns {Promise<void>}
     */
    async logout() {
        try {
            // Call backend logout (clears HttpOnly cookie)
            try {
                await api.post('/auth/logout', {});
            } catch (e) {
                Config.warn('Logout API call failed', e);
            }
        } catch (error) {
            Config.warn('Logout error', error);
        } finally {
            // Clear all stored data
            this._clearAllData();
            
            Config.log('Logged out');
            
            // Redirect to login
            window.location.href = '/public/login/';
        }
    },
    
    /**
     * Clear all authentication data
     */
    _clearAllData() {
        // Clear memory
        this._accessToken = null;
        this._csrfToken = null;
        this._user = null;
        this._initialized = false;
        
        // Stop auto-refresh
        if (this._refreshInterval) {
            clearInterval(this._refreshInterval);
            this._refreshInterval = null;
        }
        
        // Clear all storage
        localStorage.removeItem(Config.TOKEN_KEY);
        localStorage.removeItem(Config.REFRESH_TOKEN_KEY);
        localStorage.removeItem(Config.USER_KEY);
        sessionStorage.removeItem('alive_session');
    },
    
    /**
     * Persist session data for page navigation
     * Access token is stored temporarily - will be refreshed on page load
     */
    _persistSession() {
        if (this._user && this._accessToken) {
            const sessionData = {
                accessToken: this._accessToken,
                csrfToken: this._csrfToken,
                user: {
                    MbrID: this._user.MbrID,
                    MbrFirstName: this._user.MbrFirstName,
                    MbrFamilyName: this._user.MbrFamilyName,
                    Username: this._user.Username,
                    Role: this._user.Role || this._user.RoleName,
                    permissions: this._user.permissions
                },
                timestamp: Date.now()
            };
            sessionStorage.setItem('alive_session', JSON.stringify(sessionData));
            Config.log('Session persisted, token length:', this._accessToken.length);
        }
    },
    
    /**
     * Restore session from sessionStorage (for page navigation)
     * This does NOT call the refresh endpoint - just restores in-memory state
     */
    _restoreFromStorage() {
        const sessionData = sessionStorage.getItem('alive_session');
        if (!sessionData) return false;
        
        try {
            const data = JSON.parse(sessionData);
            
            // Check if session is recent (less than 30 minutes old)
            const age = Date.now() - (data.timestamp || 0);
            if (age > 30 * 60 * 1000) {
                Config.log('Session too old, need to refresh');
                return false;
            }
            
            this._accessToken = data.accessToken;
            this._csrfToken = data.csrfToken;
            this._user = data.user;
            this._initialized = true;
            
            Config.log('Session restored from storage, token length:', this._accessToken ? this._accessToken.length : 0);
            return true;
        } catch (e) {
            Config.warn('Failed to restore session', e);
            return false;
        }
    },
    
    /**
     * Refresh access token using HttpOnly cookie
     */
    async refreshToken() {
        try {
            // Call refresh endpoint - server will use HttpOnly cookie
            const response = await fetch(`${Config.API_BASE_URL}/auth/refresh`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include', // Important: send cookies
                body: JSON.stringify({})
            });
            
            if (!response.ok) {
                throw new Error('Token refresh failed');
            }
            
            const data = await response.json();
            
            if (data.status === 'success' && data.data) {
                // Update access token in memory
                this._accessToken = data.data.access_token;
                
                // Update CSRF token
                if (data.data.csrf_token) {
                    this._csrfToken = data.data.csrf_token;
                }
                
                // Update persisted session
                this._persistSession();
                
                Config.log('Token refreshed');
                return true;
            }
            
            throw new Error('Invalid refresh response');
        } catch (error) {
            Config.error('Token refresh failed', error);
            throw error;
        }
    },
    
    /**
     * Check if user is authenticated
     * @returns {boolean} Is authenticated
     */
    isAuthenticated() {
        // Check memory first
        if (this._accessToken && this._user) {
            return true;
        }
        
        // Try to restore from sessionStorage (preferred - new secure method)
        if (this._restoreFromStorage()) {
            // Clear legacy localStorage if we have a valid session
            localStorage.removeItem(Config.TOKEN_KEY);
            localStorage.removeItem(Config.REFRESH_TOKEN_KEY);
            localStorage.removeItem(Config.USER_KEY);
            return true;
        }
        
        // Check legacy localStorage (for backward compatibility during migration)
        // This will be removed in a future version
        const legacyToken = localStorage.getItem(Config.TOKEN_KEY);
        const legacyUser = localStorage.getItem(Config.USER_KEY);
        
        if (legacyToken && legacyUser) {
            Config.warn('Using legacy localStorage tokens - please re-login for better security');
            this._accessToken = legacyToken;
            try {
                this._user = JSON.parse(legacyUser);
            } catch (e) {}
            return true;
        }
        
        return false;
    },
    
    /**
     * Get current user
     * @returns {Object|null} User data
     */
    getUser() {
        if (this._user) return this._user;
        
        // Try to restore
        this._restoreFromStorage();
        return this._user;
    },
    
    /**
     * Get access token (for API requests)
     * @returns {string|null} Access token
     */
    getToken() {
        if (this._accessToken) {
            Config.log('getToken: returning in-memory token, length:', this._accessToken.length);
            return this._accessToken;
        }
        
        // Try to restore
        this._restoreFromStorage();
        Config.log('getToken: after restore, token length:', this._accessToken ? this._accessToken.length : 0);
        return this._accessToken;
    },
    
    /**
     * Get CSRF token
     * @returns {string|null} CSRF token
     */
    getCsrfToken() {
        if (this._csrfToken) {
            return this._csrfToken;
        }
        
        // Try to get from cookie (set by server)
        const cookies = document.cookie.split(';');
        for (const cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'alive_csrf_token') {
                this._csrfToken = value;
                return value;
            }
        }
        
        return null;
    },
    
    /**
     * Get user's full name
     * @returns {string} Full name
     */
    getUserName() {
        const user = this.getUser();
        if (!user) return 'Guest';
        return `${user.MbrFirstName || ''} ${user.MbrFamilyName || ''}`.trim() || 'User';
    },
    
    /**
     * Get user's initials
     * @returns {string} Initials
     */
    getUserInitials() {
        const name = this.getUserName();
        return Utils.getInitials(name);
    },
    
    /**
     * Get user's role
     * @returns {string|Array} Role(s)
     */
    getUserRole() {
        const user = this.getUser();
        return user?.Role || user?.RoleName || 'Member';
    },
    
    /**
     * Extract permissions from user data
     * @param {Object} user - User object
     * @returns {Array} Permissions array
     */
    extractPermissions(user) {
        if (user.permissions && Array.isArray(user.permissions)) {
            return user.permissions;
        }
        
        if (user.Role) {
            return this.getRolePermissions(user.Role);
        }
        
        return [];
    },
    
    /**
     * Get permissions for a role (fallback only - server provides actual permissions)
     * This is only used if server doesn't return permissions in login response
     * @param {string} role - Role name
     * @returns {Array} Permissions
     */
    getRolePermissions(role) {
        // NOTE: This is a fallback. The server now returns actual permissions
        // from the database with inheritance support via the RBAC system.
        // This fallback ensures backward compatibility if permissions are missing.
        const rolePermissions = {
            'Admin': Object.values(Config.PERMISSIONS),
            'Pastor': [
                Config.PERMISSIONS.VIEW_MEMBERS,
                Config.PERMISSIONS.VIEW_CONTRIBUTION,
                Config.PERMISSIONS.VIEW_EXPENSES,
                Config.PERMISSIONS.VIEW_EVENTS,
                Config.PERMISSIONS.VIEW_GROUPS,
                Config.PERMISSIONS.VIEW_FINANCIAL_REPORTS,
                Config.PERMISSIONS.VIEW_DASHBOARD
            ],
            'Treasurer': [
                Config.PERMISSIONS.VIEW_CONTRIBUTION,
                Config.PERMISSIONS.CREATE_CONTRIBUTION,
                Config.PERMISSIONS.VIEW_EXPENSES,
                Config.PERMISSIONS.CREATE_EXPENSE,
                Config.PERMISSIONS.VIEW_FINANCIAL_REPORTS,
                Config.PERMISSIONS.VIEW_DASHBOARD
            ],
            'Secretary': [
                Config.PERMISSIONS.VIEW_MEMBERS,
                Config.PERMISSIONS.EDIT_MEMBERS,
                Config.PERMISSIONS.VIEW_EVENTS,
                Config.PERMISSIONS.MANAGE_EVENTS,
                Config.PERMISSIONS.VIEW_GROUPS,
                Config.PERMISSIONS.VIEW_DASHBOARD
            ],
            'Member': [
                Config.PERMISSIONS.VIEW_EVENTS,
                Config.PERMISSIONS.VIEW_GROUPS
            ]
        };
        
        return rolePermissions[role] || rolePermissions['Member'];
    },
    
    /**
     * Check if user has permission
     * User permissions come from the login response and are cached in memory
     * Available permissions (all system permissions) are loaded by Config.js
     * 
     * @param {string} permission - Permission name (e.g., 'members.view')
     * @returns {boolean} Has permission
     */
    hasPermission(permission) {
        const user = this.getUser();
        if (!user) return false;
        
        const role = this.getUserRole();
        if (role === 'Admin' || role === 'Administrator' || role === 'Super Admin') return true;
        
        const permissions = user.permissions || this.extractPermissions(user);
        return permissions.includes(permission);
    },
    
    /**
     * Check if user has any of the permissions
     * @param {Array<string>} permissions - Permission names
     * @returns {boolean} Has any permission
     */
    hasAnyPermission(permissions) {
        return permissions.some(permission => this.hasPermission(permission));
    },
    
    /**
     * Check if user has all permissions
     * @param {Array<string>} permissions - Permission names
     * @returns {boolean} Has all permissions
     */
    hasAllPermissions(permissions) {
        return permissions.every(permission => this.hasPermission(permission));
    },
    
    /**
     * Require authentication (call on page load)
     * @param {string} redirectUrl - URL to redirect after login
     */
    requireAuth(redirectUrl = null) {
        if (!this.isAuthenticated()) {
            Config.log('Not authenticated, redirecting to login');
            if (redirectUrl) {
                sessionStorage.setItem('redirect_after_login', redirectUrl);
            }
            window.location.href = '/public/login/';
            return false;
        }
        
        // Set up auto-refresh if not already done
        if (!this._refreshInterval) {
            this.setupTokenRefresh();
        }
        
        return true;
    },
    
    /**
     * Require permission
     * @param {string|Array<string>} permission - Required permission(s)
     * @param {string} message - Error message
     * @returns {boolean} Has permission
     */
    requirePermission(permission, message = null) {
        const hasPermission = Array.isArray(permission)
            ? this.hasAnyPermission(permission)
            : this.hasPermission(permission);
        
        if (!hasPermission) {
            const msg = message || 'You do not have permission to perform this action.';
            if (typeof Alerts !== 'undefined') {
                Alerts.error(msg);
            }
            Config.warn('Permission denied:', permission);
            return false;
        }
        
        return true;
    },
    
    /**
     * Set up automatic token refresh
     */
    setupTokenRefresh() {
        if (this._refreshInterval) {
            clearInterval(this._refreshInterval);
        }
        
        // Refresh token every 25 minutes (before 30 min expiry)
        this._refreshInterval = setInterval(async () => {
            try {
                await this.refreshToken();
            } catch (error) {
                Config.error('Auto token refresh failed', error);
                this.logout();
            }
        }, 25 * 60 * 1000);
        
        Config.log('Token auto-refresh enabled');
    },
    
    /**
     * Update user data
     * @param {Object} updates - User data updates
     */
    updateUser(updates) {
        if (!this._user) return;
        
        this._user = { ...this._user, ...updates };
        this._persistSession();
        
        Config.log('User data updated');
    },
    
    /**
     * Handle redirect after login
     */
    handleRedirectAfterLogin() {
        const redirectUrl = sessionStorage.getItem('redirect_after_login');
        if (redirectUrl) {
            sessionStorage.removeItem('redirect_after_login');
            window.location.href = redirectUrl;
        } else {
            window.location.href = '/public/dashboard/';
        }
    }
};

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (Auth._refreshInterval) {
        clearInterval(Auth._refreshInterval);
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Auth;
}
