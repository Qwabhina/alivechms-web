/**
 * AliveChMS API Wrapper
 * 
 * Handles all API requests with automatic token management,
 * error handling, CSRF protection, and request/response interceptors
 * 
 * Security features:
 * - Automatic CSRF token inclusion for state-changing requests
 * - Credentials included for HttpOnly cookie support
 * - Token refresh on 401 responses
 * 
 * @version 2.0.0
 */

class API {
    constructor() {
        this.baseURL = Config.API_BASE_URL;
        this.timeout = Config.API_TIMEOUT;
        this.refreshing = false;
        this.refreshSubscribers = [];
    }
    
    /**
     * Get authorization headers including CSRF
     * @param {string} method - HTTP method
     * @returns {Object} Headers object
     */
    getHeaders(method = 'GET') {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        
        // Add access token
        const token = Auth.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
            Config.log('API: Adding Authorization header, token length:', token.length);
        } else {
            Config.log('API: No token available for Authorization header');
        }
        
        // Add CSRF token for state-changing requests
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method.toUpperCase())) {
            const csrfToken = Auth.getCsrfToken();
            if (csrfToken) {
                headers['X-CSRF-Token'] = csrfToken;
            }
        }
        
        return headers;
    }
    
    /**
     * Make HTTP request
     * @param {string} endpoint - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise} Response data
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}/${endpoint}`;
        const method = options.method || 'GET';
        
        const headers = this.getHeaders(method);
        
        // Debug: Log the actual Authorization header being sent
        if (headers['Authorization']) {
            Config.log('API: Sending Authorization header, length:', headers['Authorization'].length);
        }
        
        const config = {
            ...options,
            method,
            headers: {
                ...headers,
                ...options.headers
            },
            credentials: 'include' // Important: send/receive cookies
        };
        
        // Add timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);
        config.signal = controller.signal;
        
        try {
            Config.log(`API Request: ${method} ${url}`);
            
            const response = await fetch(url, config);
            clearTimeout(timeoutId);
            
            // Handle 401 Unauthorized - Token expired
            if (response.status === 401 && !endpoint.includes('auth/')) {
                Config.log('Token expired, attempting refresh...');
                return await this.handleTokenExpiration(endpoint, options);
            }
            
            // Handle 403 with CSRF error - refresh CSRF token
            if (response.status === 403) {
                const data = await response.clone().json().catch(() => ({}));
                if (data.message && data.message.includes('CSRF')) {
                    Config.log('CSRF token invalid, refreshing...');
                    await this.refreshCsrfToken();
                    // Retry request with new CSRF token
                    return await this.request(endpoint, options);
                }
            }
            
            // Parse response
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = await response.text();
            }
            
            // Handle error responses
            if (!response.ok) {
                throw new APIError(
                    data.message || `HTTP ${response.status}: ${response.statusText}`,
                    response.status,
                    data
                );
            }
            
            Config.log(`API Response: ${method} ${url}`, data);
            
            // Extract data from standard response format
            if (data && typeof data === 'object' && 'data' in data && data.status === 'success') {
                return data.data;
            }
            
            return data;
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error.name === 'AbortError') {
                throw new APIError('Request timeout', 408);
            }
            
            if (error instanceof APIError) {
                throw error;
            }
            
            throw new APIError(
                'Network error. Please check your internet connection.',
                0,
                error
            );
        }
    }
    
    /**
     * Refresh CSRF token
     */
    async refreshCsrfToken() {
        try {
            const response = await fetch(`${this.baseURL}/auth/csrf`, {
                method: 'GET',
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.data && data.data.csrf_token) {
                    Auth._csrfToken = data.data.csrf_token;
                    Config.log('CSRF token refreshed');
                }
            }
        } catch (e) {
            Config.warn('Failed to refresh CSRF token', e);
        }
    }
    
    /**
     * Handle token expiration and refresh
     * @param {string} endpoint - Original endpoint
     * @param {Object} options - Original options
     * @returns {Promise} Retry result
     */
    async handleTokenExpiration(endpoint, options) {
        if (!this.refreshing) {
            this.refreshing = true;
            
            try {
                await Auth.refreshToken();
                
                Config.log('Token refreshed successfully');
                
                // Retry all waiting requests
                this.refreshSubscribers.forEach(callback => callback());
                this.refreshSubscribers = [];
                
                // Retry original request
                return await this.request(endpoint, options);
                
            } catch (error) {
                Config.error('Token refresh failed', error);
                Auth.logout();
                return Promise.reject(new APIError('Session expired. Please login again.', 401));
            } finally {
                this.refreshing = false;
            }
        }
        
        // If already refreshing, wait for it to complete
        return new Promise((resolve, reject) => {
            this.refreshSubscribers.push(() => {
                this.request(endpoint, options).then(resolve).catch(reject);
            });
        });
    }
    
    /**
     * GET request
     * @param {string} endpoint - API endpoint
     * @param {Object} params - Query parameters
     * @returns {Promise} Response data
     */
    async get(endpoint, params = null) {
        let url = endpoint;
        if (params) {
            const queryString = Utils.buildQueryString(params);
            url += `?${queryString}`;
        }
        
        return this.request(url, {
            method: 'GET'
        });
    }
    
    /**
     * POST request
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request body
     * @returns {Promise} Response data
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * PUT request
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request body
     * @returns {Promise} Response data
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * DELETE request
     * @param {string} endpoint - API endpoint
     * @returns {Promise} Response data
     */
    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }
    
    /**
     * Upload file
     * @param {string} endpoint - API endpoint
     * @param {FormData} formData - Form data with file
     * @returns {Promise} Response data
     */
    async upload(endpoint, formData) {
        const url = `${this.baseURL}/${endpoint}`;
        
        const token = Auth.getToken();
        const headers = {};
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        // Add CSRF token for uploads
        const csrfToken = Auth.getCsrfToken();
        if (csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }
        
        // Don't set Content-Type for FormData - browser will set it with boundary
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers,
                body: formData,
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new APIError(
                    data.message || 'Upload failed',
                    response.status,
                    data
                );
            }
            
            return data;
            
        } catch (error) {
            if (error instanceof APIError) {
                throw error;
            }
            throw new APIError('Upload failed', 0, error);
        }
    }
}

/**
 * Custom API Error class
 */
class APIError extends Error {
    constructor(message, status = 0, data = null) {
        super(message);
        this.name = 'APIError';
        this.status = status;
        this.data = data;
    }
    
    /**
     * Check if error is specific status
     * @param {number} status - HTTP status code
     * @returns {boolean} Is matching status
     */
    is(status) {
        return this.status === status;
    }
    
    /**
     * Check if error is client error (4xx)
     * @returns {boolean} Is client error
     */
    isClientError() {
        return this.status >= 400 && this.status < 500;
    }
    
    /**
     * Check if error is server error (5xx)
     * @returns {boolean} Is server error
     */
    isServerError() {
        return this.status >= 500 && this.status < 600;
    }
    
    /**
     * Check if error is network error
     * @returns {boolean} Is network error
     */
    isNetworkError() {
        return this.status === 0;
    }
}

// Create singleton instance
const api = new API();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { API, APIError, api };
}