/**
 * AliveChMS Frontend Configuration
 * 
 * Core configuration and constants for the frontend application
 * @version 1.0.0
 */

const Config = {
    // API Configuration
   //  API_BASE_URL: window.location.origin + '/alivechms-backend',
      API_BASE_URL: 'http://www.onechurch.com',
    API_TIMEOUT: 30000, // 30 seconds
    
    // Authentication
    TOKEN_KEY: 'alive_access_token',
    REFRESH_TOKEN_KEY: 'alive_refresh_token',
    USER_KEY: 'alive_user',
    TOKEN_EXPIRY_BUFFER: 5 * 60 * 1000, // Refresh 5 minutes before expiry
    
    // Dynamic Settings (loaded from server)
    SETTINGS: {
        church_name: 'AliveChMS Church',
        church_motto: 'Faith, Hope, and Love',
        currency_symbol: 'GH₵',
        currency_code: 'GHS',
        date_format: 'Y-m-d',
        time_format: 'H:i',
        timezone: 'Africa/Accra',
        language: 'en',
        items_per_page: 10
    },
    
    // Pagination
    DEFAULT_PAGE_SIZE: 10,
    PAGE_SIZE_OPTIONS: [10, 25, 50, 100],
    
    // UI
    TOAST_DURATION: 3000,
    MODAL_FADE_DURATION: 150,
    DEBOUNCE_DELAY: 300,
    
    // Date Formats
    DATE_FORMAT: 'Y-m-d',
    DATETIME_FORMAT: 'Y-m-d H:i',
    DISPLAY_DATE_FORMAT: 'M d, Y',
    DISPLAY_DATETIME_FORMAT: 'M d, Y h:i K',
    
    // File Upload
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    ALLOWED_IMAGE_TYPES: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    ALLOWED_DOCUMENT_TYPES: [
        'application/pdf', 
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ],
    
    // Church Specific
    GHANA_PHONE_REGEX: /^(\+?233|0)[2-5][0-9]{8}$/,
    CURRENCY: 'GHS',
    CURRENCY_SYMBOL: '₵',
    
    // Permissions (matches backend database)
    // These are dynamically loaded from the backend, but we keep constants for code completion
    PERMISSIONS: {
        // Members
        VIEW_MEMBERS: 'members.view',
        EDIT_MEMBERS: 'members.edit',
        DELETE_MEMBERS: 'members.delete',
        CREATE_MEMBERS: 'members.create',
        
        // Visitors
        VIEW_VISITORS: 'visitors.view',
        CREATE_VISITORS: 'visitors.create',
        MANAGE_VISITORS: 'visitors.manage',
        
        // Contributions
        VIEW_CONTRIBUTION: 'contributions.view',
        CREATE_CONTRIBUTION: 'contributions.create',
        EDIT_CONTRIBUTION: 'contributions.edit',
        DELETE_CONTRIBUTION: 'contributions.delete',
        
        // Expenses
        VIEW_EXPENSES: 'expenses.view',
        CREATE_EXPENSE: 'expenses.create',
        APPROVE_EXPENSES: 'expenses.approve',
        DELETE_EXPENSES: 'expenses.delete',
        
        // Events
        VIEW_EVENTS: 'events.view',
        CREATE_EVENTS: 'events.create',
        EDIT_EVENTS: 'events.edit',
        DELETE_EVENTS: 'events.delete',
        MANAGE_EVENTS: 'events.edit', // Alias for backward compatibility
        
        // Groups
        VIEW_GROUPS: 'groups.view',
        MANAGE_GROUPS: 'groups.manage',
        
        // Finances
        VIEW_FINANCES: 'finances.view',
        VIEW_FINANCIAL_REPORTS: 'reports.view',
        
        // Communications
        VIEW_COMMUNICATIONS: 'communications.view',
        SEND_COMMUNICATIONS: 'communications.send',
        
        // Reports
        VIEW_REPORTS: 'reports.view',
        EXPORT_REPORTS: 'reports.export',
        VIEW_DASHBOARD: 'reports.view', // Dashboard is a type of report
        
        // Settings
        VIEW_SETTINGS: 'settings.view',
        EDIT_SETTINGS: 'settings.edit',
        
        // Roles & Permissions
        MANAGE_ROLES: 'roles.manage',
        MANAGE_PERMISSIONS: 'roles.manage',
        
        // Assets
        VIEW_ASSETS: 'assets.view',
        MANAGE_ASSETS: 'assets.manage',
        
        // Budgets (using finances permissions)
        APPROVE_BUDGETS: 'expenses.approve', // Same approval permission
        VIEW_BUDGETS: 'finances.view',
        CREATE_BUDGETS: 'finances.view',
        EDIT_BUDGETS: 'finances.view',
        
        // Attendance (using events permissions)
        RECORD_ATTENDANCE: 'events.edit',
        VIEW_ATTENDANCE: 'events.view'
    },
    
    // Available permissions cache (loaded from backend)
    _availablePermissions: null,
    
    // Status Options
    STATUS: {
        ACTIVE: 'Active',
        INACTIVE: 'Inactive',
        PENDING: 'Pending',
        APPROVED: 'Approved',
        REJECTED: 'Rejected',
        CANCELLED: 'Cancelled'
    },
    
    // Member Status
    MEMBER_STATUS: {
        ACTIVE: 'Active',
        INACTIVE: 'Inactive',
        SUSPENDED: 'Suspended',
        DECEASED: 'Deceased'
    },
    
    // Gender Options
    GENDER: {
        MALE: 'Male',
        FEMALE: 'Female',
        OTHER: 'Other'
    },
    
    // Attendance Status
    ATTENDANCE: {
        PRESENT: 'Present',
        ABSENT: 'Absent',
        LATE: 'Late',
        EXCUSED: 'Excused'
    },
    
    // Chart Colors
    CHART_COLORS: {
        primary: '#0d6efd',
        secondary: '#6c757d',
        success: '#198754',
        danger: '#dc3545',
        warning: '#ffc107',
        info: '#0dcaf0',
        light: '#f8f9fa',
        dark: '#212529',
        
        // Custom church colors (customize as needed)
        church1: '#2c5282',
        church2: '#4a5568',
        church3: '#ed8936',
        church4: '#38b2ac',
        church5: '#9f7aea'
    },
    
    // SweetAlert2 Configuration
    SWAL_CONFIG: {
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        showCancelButton: true,
        reverseButtons: true
    },
    
    // Development Mode - temporarily enabled for debugging
    DEBUG: true, // window.location.hostname === 'localhost',
    
    // Helper Methods
    log: function(...args) {
        if (this.DEBUG) {
            console.log('[AliveChMS]', ...args);
        }
    },
    
    error: function(...args) {
        console.error('[AliveChMS Error]', ...args);
    },
    
    warn: function(...args) {
        if (this.DEBUG) {
            console.warn('[AliveChMS Warning]', ...args);
        }
    },
    
    // Load settings from server
    loadSettings: async function() {
        try {
            const response = await fetch(`${this.API_BASE_URL}/public/settings`);
            if (response.ok) {
                const data = await response.json();
                
                // Transform backend response to settings object
                // Backend returns array of {SettingKey, SettingValue, SettingType}
                const settingsMap = {};
                
                if (Array.isArray(data)) {
                    // Direct array response
                    data.forEach(setting => {
                        if (setting.SettingKey && setting.SettingValue !== undefined) {
                            settingsMap[setting.SettingKey] = setting.SettingValue;
                        }
                    });
                } else if (data.data && Array.isArray(data.data)) {
                    // Wrapped in data property
                    data.data.forEach(setting => {
                        if (setting.SettingKey && setting.SettingValue !== undefined) {
                            settingsMap[setting.SettingKey] = setting.SettingValue;
                        }
                    });
                } else if (typeof data === 'object') {
                    // Already an object (fallback for old format)
                    Object.assign(settingsMap, data);
                }
                
                // Update settings with loaded values
                Object.assign(this.SETTINGS, settingsMap);
                
                this.log('Settings loaded:', this.SETTINGS);
                
                // Update page title if on dashboard
                if (document.title.includes('AliveChMS') && this.SETTINGS.church_name) {
                    document.title = document.title.replace('AliveChMS', this.SETTINGS.church_name);
                }
                
                // Mark settings as loaded
                this._settingsLoaded = true;
                
                // Dispatch event for other components
                window.dispatchEvent(new CustomEvent('settingsLoaded', { detail: this.SETTINGS }));
            } else {
                this.warn('Settings endpoint returned non-OK status:', response.status);
                this._settingsLoaded = true; // Mark as loaded even if failed (use defaults)
            }
        } catch (error) {
            this.warn('Failed to load settings, using defaults:', error);
            this._settingsLoaded = true; // Mark as loaded even if failed (use defaults)
        }
    },
    
    // Wait for settings to be loaded
    waitForSettings: function() {
        return new Promise((resolve) => {
            if (this._settingsLoaded) {
                resolve();
            } else {
                window.addEventListener('settingsLoaded', () => resolve(), { once: true });
            }
        });
    },
    
    // Get setting value
    getSetting: function(key, defaultValue = null) {
        return this.SETTINGS[key] !== undefined ? this.SETTINGS[key] : defaultValue;
    },
    
    // Get pagination size (helper for tables)
    getPaginationSize: function() {
        return this.getSetting('items_per_page', 10);
    },
    
    // Format currency
    formatCurrency: function(amount) {
        const formatted = parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        return `${this.SETTINGS.currency_symbol} ${formatted}`;
    },
    
    /**
     * Load available permissions from backend
     * This fetches all permissions defined in the system
     * User's actual permissions come from Auth (login response)
     */
    loadAvailablePermissions: async function() {
        try {
            const response = await fetch(`${this.API_BASE_URL}/public/permissions`);
            if (response.ok) {
                const data = await response.json();
                
                // Handle both wrapped and unwrapped responses
                const permissions = Array.isArray(data) ? data : (data.data || []);
                
                // Cache the permissions
                this._availablePermissions = permissions;
                
                this.log('Available permissions loaded:', permissions.length);
                
                // Dispatch event for other components
                window.dispatchEvent(new CustomEvent('permissionsLoaded', { 
                    detail: permissions 
                }));
                
                return permissions;
            } else {
                this.warn('Permissions endpoint returned non-OK status:', response.status);
                return [];
            }
        } catch (error) {
            this.warn('Failed to load available permissions:', error);
            return [];
        }
    },
    
    /**
     * Get available permissions (cached)
     * @returns {Array} Array of permission objects
     */
    getAvailablePermissions: function() {
        return this._availablePermissions || [];
    },
    
    /**
     * Get permissions grouped by category
     * @returns {Object} Permissions grouped by category
     */
    getPermissionsByCategory: function() {
        const permissions = this.getAvailablePermissions();
        const grouped = {};
        
        permissions.forEach(perm => {
            const category = perm.CategoryName || 'Other';
            if (!grouped[category]) {
                grouped[category] = [];
            }
            grouped[category].push(perm);
        });
        
        return grouped;
    },
    
    /**
     * Check if a permission exists in the system
     * @param {string} permissionName - Permission name to check
     * @returns {boolean} True if permission exists
     */
    permissionExists: function(permissionName) {
        const permissions = this.getAvailablePermissions();
        return permissions.some(p => p.PermissionName === permissionName);
    }
};

// Freeze config to prevent modifications (except SETTINGS which is dynamic)
Object.freeze(Config.PERMISSIONS);
Object.freeze(Config.STATUS);
Object.freeze(Config.CHART_COLORS);

// Load settings on page load
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            Config.loadSettings();
            Config.loadAvailablePermissions(); // Load available permissions
        });
    } else {
        Config.loadSettings();
        Config.loadAvailablePermissions(); // Load available permissions
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Config;
}