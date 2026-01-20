/**
 * QMGridHelper - Enhanced Implementation for AliveChMS
 * 
 * Optimized wrapper for QMGrid with proper server-side processing
 * and integration with AliveChMS API response structure
 * 
 * @package  AliveChMS
 * @version  5.0.0
 * @author   Qwabhina McFynn
 */

(function(window) {
   'use strict';

   // Store original fetch for QMGrid authentication
   const originalFetch = window.fetch;
   let fetchIntercepted = false;

   class QMGridHelper {
      /**
       * Setup fetch interceptor for QMGrid authentication
       */
      static setupFetchInterceptor() {
         if (fetchIntercepted) return;
         
         window.fetch = async function(url, options = {}) {
            // Check if this is a request to our API
            if (typeof url === 'string' && url.includes(Config.API_BASE_URL)) {
               // Add authentication headers - use Auth module to get token
               const token = Auth.getToken();
               if (token) {
                  // Check if this is a FormData upload (don't override Content-Type for multipart)
                  const isFormData = options.body instanceof FormData;
                  
                  options.headers = {
                     ...options.headers,
                     'Authorization': `Bearer ${token}`,
                     'Accept': 'application/json'
                  };
                  
                  // Only set Content-Type for non-FormData requests
                  // FormData needs the browser to set the boundary automatically
                  if (!isFormData) {
                     options.headers['Content-Type'] = 'application/json';
                  }
                  
                  // Include credentials for cookie-based refresh tokens
                  options.credentials = 'include';
               }
            }
            
            return originalFetch.call(this, url, options);
         };
         
         fetchIntercepted = true;
      }

      /**
       * Initialize QMGrid with server-side processing for AliveChMS API
       * 
       * @param {string} selector - Table container selector
       * @param {Object} config - Configuration object
       * @returns {Object} QMGrid instance
       */
      static init(selector, config = {}) {
         // Setup fetch interceptor for authentication
         QMGridHelper.setupFetchInterceptor();
         
         const {
            url,
            columns = [],
            pageSize = 25,
            exportable = true,
            selectable = false,
            multiSelect = false,
            filters = {}, // Additional filters like status, family_id, date_from, date_to
            onDataLoaded = null, // Callback when data is loaded
            onError = null, // Callback when error occurs
            ...additionalConfig
         } = config;

         if (!url) {
            throw new Error('URL is required for server-side processing');
         }

         // QMGrid configuration optimized for AliveChMS API
         const qmConfig = {
            columns: columns,
            pagination: true,
            pageSize: pageSize,
            sortable: true,
            searchable: true,
            exportable: exportable,
            selectable: selectable,
            multiSelect: multiSelect,
            striped: true,
            bordered: true,
            hover: true,
            responsive: true,
            
            // Enable server-side processing
            serverSide: true,
            
            // AJAX configuration for AliveChMS API
            ajax: {
               url: url,
               method: 'GET', // MemberRoutes uses GET for listing
               headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json'
               },
               timeout: 30000,
               retryAttempts: 3,
               retryDelay: 1000,
               
               // Transform QMGrid parameters to AliveChMS API format
               data: function(params) {
                  // Map QMGrid params to API query parameters
                  const apiParams = {
                     page: params.page,
                     limit: params.pageSize
                  };
                  
                  // Add search if present
                  if (params.search) {
                     apiParams.search = params.search;
                  }
                  
                  // Add sorting (if API supports it - currently not in MemberRoutes)
                  // You may need to add this to the API later
                  if (params.sortBy) {
                     apiParams.sort_by = params.sortBy;
                     apiParams.sort_dir = params.sortDir;
                  }
                  
                  // Add additional filters
                  if (filters.status) {
                     apiParams.status = filters.status;
                  }
                  if (filters.family_id) {
                     apiParams.family_id = filters.family_id;
                  }
                  if (filters.date_from) {
                     apiParams.date_from = filters.date_from;
                  }
                  if (filters.date_to) {
                     apiParams.date_to = filters.date_to;
                  }
                  
                  return apiParams;
               },
               
               // Lifecycle callbacks
               beforeSend: function(data, params) {
                  console.log('QMGrid: Sending request', { url, params: data });
                  return true;
               },
               
               complete: function() {
                  console.log('QMGrid: Request completed');
               },
               
               error: function(error, page, search) {
                  console.error('QMGrid: Request failed', { error, page, search });
                  if (onError && typeof onError === 'function') {
                     onError(error, page, search);
                  }
               }
            },
            
            // Map AliveChMS API response structure to QMGrid format
            serverResponse: {
               data: 'data',                      // Path to data array in response
               totalRecords: 'pagination.total',  // Path to total records count (nested in pagination object)
               error: 'error',                    // Path to error message (if present)
               draw: 'draw'                       // Request identifier (optional)
            },
            
            // Apply any additional configuration
            ...additionalConfig
         };

         // Initialize QMGrid
         const grid = new QMGrid(selector, qmConfig);
         
         // Add event listeners
         grid.on('serverDataLoaded', (data) => {
            console.log('QMGrid: Data loaded', {
               records: data.data.length,
               total: data.total,
               page: data.page
            });
            
            if (onDataLoaded && typeof onDataLoaded === 'function') {
               onDataLoaded(data);
            }
         });
         
         grid.on('serverError', (data) => {
            console.error('QMGrid: Server error', data.error);
            
            // Show user-friendly error message
            if (typeof Helpers !== 'undefined' && Helpers.showToast) {
               Helpers.showToast('error', data.error || 'Failed to load data');
            }
         });

         return grid;
      }

      /**
       * Initialize QMGrid with all export buttons enabled
       */
      static initWithExport(selector, config = {}) {
         return QMGridHelper.init(selector, {
            ...config,
            exportable: true,
            exportOptions: {
               filename: config.filename || 'export',
               includeHeaders: true,
               dateFormat: 'YYYY-MM-DD',
               csvSeparator: ',',
               pdfOrientation: 'portrait',
               pdfPageSize: 'A4',
               ...config.exportOptions
            }
         });
      }

      /**
       * Initialize QMGrid with action buttons (alias for init with common button config)
       * Supports both QMGrid native config and DataTables-style ajax config for compatibility
       * 
       * @param {string} selector - Table container selector
       * @param {Object} config - Configuration object
       * @returns {Object} QMGrid instance
       */
      static initWithButtons(selector, config = {}) {
         // Setup fetch interceptor for authentication
         QMGridHelper.setupFetchInterceptor();
         
         // Check if using DataTables-style ajax config (for backward compatibility)
         if (config.ajax && typeof config.ajax === 'object' && config.ajax.url) {
            return QMGridHelper.initDataTablesStyle(selector, config);
         }
         
         // Use standard QMGrid init
         return QMGridHelper.init(selector, {
            ...config,
            exportable: true
         });
      }

      /**
       * Initialize QMGrid with DataTables-style configuration
       * Provides compatibility layer for pages using DataTables ajax format
       * 
       * @param {string} selector - Table container selector
       * @param {Object} config - DataTables-style configuration
       * @returns {Object} QMGrid instance
       */
      static initDataTablesStyle(selector, config = {}) {
         const {
            ajax = {},
            columns = [],
            order = [[0, 'asc']],
            pageSize = 25,
            ...additionalConfig
         } = config;

         // Extract sorting from DataTables order format
         const defaultSortColumn = order[0] ? order[0][0] : 0;
         const defaultSortDir = order[0] ? order[0][1] : 'asc';
         const sortColumnKey = columns[defaultSortColumn]?.data || columns[defaultSortColumn]?.key;

         // Convert DataTables columns to QMGrid format
         const qmColumns = columns.map(col => ({
            key: col.data || col.key,
            title: col.title || col.data || col.key,
            sortable: col.orderable !== false,
            searchable: col.searchable !== false,
            exportable: !col.className?.includes('no-export'),
            render: col.render ? (value, row) => col.render(value, 'display', row) : undefined
         }));

         // QMGrid configuration
         const qmConfig = {
            columns: qmColumns,
            pagination: true,
            pageSize: pageSize,
            sortable: true,
            searchable: true,
            exportable: true,
            striped: true,
            bordered: true,
            hover: true,
            responsive: true,
            serverSide: true,
            
            ajax: {
               url: ajax.url,
               method: ajax.type || 'GET',
               headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json'
               },
               timeout: 30000,
               
               // Transform QMGrid parameters to API format
               data: function(params) {
                  // If custom data function provided, use it with DataTables-style params
                  if (ajax.data && typeof ajax.data === 'function') {
                     const dtParams = {
                        start: (params.page - 1) * params.pageSize,
                        length: params.pageSize,
                        search: { value: params.search || '' },
                        order: [{
                           column: params.sortBy ? qmColumns.findIndex(c => c.key === params.sortBy) : defaultSortColumn,
                           dir: params.sortDir || defaultSortDir
                        }]
                     };
                     return ajax.data(dtParams);
                  }
                  
                  // Default parameter mapping
                  const apiParams = {
                     page: params.page,
                     limit: params.pageSize
                  };
                  
                  if (params.search) {
                     apiParams.search = params.search;
                  }
                  
                  if (params.sortBy) {
                     apiParams.sort_by = params.sortBy;
                     apiParams.sort_dir = params.sortDir || 'asc';
                  } else if (sortColumnKey) {
                     apiParams.sort_by = sortColumnKey;
                     apiParams.sort_dir = defaultSortDir;
                  }
                  
                  return apiParams;
               },
               
               // Process response - handle dataFilter if provided
               processResponse: function(response) {
                  // If dataFilter is provided, use it to transform the response
                  if (ajax.dataFilter && typeof ajax.dataFilter === 'function') {
                     try {
                        const transformed = ajax.dataFilter(JSON.stringify(response));
                        if (typeof transformed === 'string') {
                           return JSON.parse(transformed);
                        }
                        return transformed;
                     } catch (e) {
                        console.error('QMGrid: dataFilter error', e);
                        return response;
                     }
                  }
                  return response;
               }
            },
            
            serverResponse: {
               data: 'data',
               totalRecords: 'pagination.total',
               error: 'error'
            },
            
            ...additionalConfig
         };

         // Initialize QMGrid
         const grid = new QMGrid(selector, qmConfig);
         
         // Add event listeners
         grid.on('serverDataLoaded', (data) => {
            console.log('QMGrid: Data loaded', {
               records: data.data?.length || 0,
               total: data.pagination?.total || data.total || 0
            });
         });
         
         grid.on('serverError', (data) => {
            console.error('QMGrid: Server error', data.error);
            if (typeof Alerts !== 'undefined' && Alerts.error) {
               Alerts.error(data.error || 'Failed to load data');
            }
         });

         return grid;
      }

      /**
       * Process server response for DataTables-style compatibility
       * Transforms AliveChMS API response to DataTables format
       * 
       * @param {string|Object} data - Raw response data (JSON string or object)
       * @param {Function} rowMapper - Function to transform each row
       * @returns {string} JSON string in DataTables format
       */
      static processServerResponse(data, rowMapper = null) {
         try {
            // Parse if string
            const response = typeof data === 'string' ? JSON.parse(data) : data;
            
            // Extract data array and pagination
            let records = response.data || [];
            const pagination = response.pagination || {};
            const total = pagination.total || records.length;
            
            // Apply row mapper if provided
            if (rowMapper && typeof rowMapper === 'function') {
               records = records.map(rowMapper).filter(r => r !== null && r !== undefined);
            }
            
            // Return DataTables-compatible format
            const result = {
               data: records,
               recordsTotal: total,
               recordsFiltered: total,
               pagination: {
                  total: total,
                  current_page: pagination.current_page || pagination.page || 1,
                  per_page: pagination.per_page || pagination.limit || records.length,
                  total_pages: pagination.total_pages || pagination.pages || 1
               }
            };
            
            return JSON.stringify(result);
         } catch (e) {
            console.error('QMGrid: processServerResponse error', e);
            return JSON.stringify({
               data: [],
               recordsTotal: 0,
               recordsFiltered: 0,
               pagination: { total: 0 }
            });
         }
      }

      /**
       * Reload table data
       * @param {Object} grid - QMGrid instance
       */
      static reload(grid) {
         if (grid && typeof grid.refresh === 'function') {
            grid.refresh();
         }
      }

      /**
       * Update filters and reload data
       * @param {Object} grid - QMGrid instance
       * @param {Object} newFilters - New filter values
       */
      static updateFilters(grid, newFilters) {
         if (!grid || !grid.config || !grid.config.ajax) {
            console.warn('Invalid grid instance');
            return;
         }
         
         // Store original data function
         const originalDataFn = grid.config.ajax.data;
         
         // Create new data function with updated filters
         grid.config.ajax.data = function(params) {
            const baseParams = originalDataFn ? originalDataFn(params) : {
               page: params.page,
               limit: params.pageSize,
               search: params.search
            };
            
            // Merge with new filters
            return { ...baseParams, ...newFilters };
         };
         
         // Reload data
         QMGridHelper.reload(grid);
      }

      /**
       * Format date for display
       * @param {string} dateString - Date string
       * @param {string} format - Format (default: 'short')
       * @returns {string} Formatted date
       */
      static formatDate(dateString, format = 'short') {
         if (!dateString) return '-';
         try {
            const date = new Date(dateString);
            
            switch (format) {
               case 'short':
                  return date.toLocaleDateString();
               case 'long':
                  return date.toLocaleDateString('en-US', { 
                     year: 'numeric', 
                     month: 'long', 
                     day: 'numeric' 
                  });
               case 'time':
                  return date.toLocaleString();
               case 'iso':
                  return date.toISOString().split('T')[0];
               default:
                  return date.toLocaleDateString();
            }
         } catch (e) {
            return dateString;
         }
      }

      /**
       * Format currency for display
       * @param {number|string} amount - Amount to format
       * @param {string} currency - Currency code (default: from Config)
       * @returns {string} Formatted currency
       */
      static formatCurrency(amount, currency = null) {
         if (amount === null || amount === undefined) return '-';
         const num = parseFloat(amount);
         if (isNaN(num)) return '-';

         const formatted = num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
         const symbol = currency || Config.getSetting('currency_symbol', 'GH₵');

         return `${symbol} ${formatted}`;
      }

      /**
       * Format phone number for display
       * @param {string|Array} phoneNumbers - Phone number(s)
       * @returns {string} Formatted phone numbers
       */
      static formatPhone(phoneNumbers) {
         if (!phoneNumbers) return '-';
         
         if (typeof phoneNumbers === 'string') {
            return phoneNumbers;
         }
         
         if (Array.isArray(phoneNumbers)) {
            return phoneNumbers.join(', ');
         }
         
         return '-';
      }

      /**
       * Format status badge with colors
       * @param {string} status - Status value
       * @param {Object} colorMap - Custom color mapping
       * @returns {string} HTML badge
       */
      static statusBadge(status, colorMap = {}) {
         const defaultColors = {
            active: 'success',
            inactive: 'secondary',
            pending: 'warning',
            completed: 'primary',
            cancelled: 'danger',
            approved: 'success',
            rejected: 'danger',
            open: 'info',
            closed: 'secondary'
         };

         const colors = { ...defaultColors, ...colorMap };
         const color = colors[status?.toLowerCase()] || 'secondary';
         const displayStatus = status || 'Unknown';

         return `<span class="badge bg-${color}">${displayStatus.toUpperCase()}</span>`;
      }

      /**
       * Format profile picture with fallback
       * @param {string} profilePicture - Profile picture URL
       * @param {string} name - User name for fallback
       * @param {number} size - Image size in pixels
       * @returns {string} HTML img or avatar
       */
      static formatProfilePicture(profilePicture, name = '', size = 40) {
         if (profilePicture) {
            const imageUrl = profilePicture.startsWith('http') 
               ? profilePicture 
               : `${Config.API_BASE_URL}/public/${profilePicture}`;
            
            return `<img src="${imageUrl}" alt="${name}" class="rounded-circle" 
                     style="width: ${size}px; height: ${size}px; object-fit: cover;" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="rounded-circle bg-primary text-white d-none" 
                     style="width: ${size}px; height: ${size}px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                     ${QMGridHelper.getInitials(name)}
                    </div>`;
         }
         
         return `<div class="rounded-circle bg-primary text-white" 
                  style="width: ${size}px; height: ${size}px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                  ${QMGridHelper.getInitials(name)}
                 </div>`;
      }

      /**
       * Get initials from name
       * @param {string} name - Full name
       * @returns {string} Initials
       */
      static getInitials(name) {
         if (!name) return '?';
         const parts = name.trim().split(' ');
         if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
         return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
      }

      /**
       * Format action buttons with proper icons (filled style)
       * @param {number|string} id - Record ID
       * @param {Object} options - Button options
       * @returns {string} HTML buttons
       */
      static actionButtons(id, options = {}) {
         const {
            view = true,
            edit = true,
            delete: del = true,
            custom = [],
            viewFn = 'viewRecord',
            editFn = 'editRecord',
            deleteFn = 'deleteRecord',
            viewPermission = true,
            editPermission = true,
            deletePermission = true,
            filled = true // Use filled buttons by default
         } = options;

         const btnStyle = filled ? '' : 'outline-';
         let html = '<div class="btn-group btn-group-sm" role="group">';

         if (view && viewPermission) {
            html += `<button class="btn btn-${btnStyle}primary btn-sm" onclick="${viewFn}(${id})" title="View">
               <i class="bi bi-eye"></i>
            </button>`;
         }

         if (edit && editPermission) {
            html += `<button class="btn btn-${btnStyle}warning btn-sm" onclick="${editFn}(${id})" title="Edit">
               <i class="bi bi-pencil"></i>
            </button>`;
         }

         // Add custom buttons
         if (custom && custom.length > 0) {
            custom.forEach(btn => {
               if (btn.permission !== false) {
                  html += `<button class="btn btn-${btnStyle}${btn.color || 'secondary'} btn-sm" 
                           onclick="${btn.fn}(${id})" title="${btn.title || ''}">
                     <i class="bi bi-${btn.icon}"></i>
                  </button>`;
               }
            });
         }

         if (del && deletePermission) {
            html += `<button class="btn btn-${btnStyle}danger btn-sm" onclick="${deleteFn}(${id})" title="Delete">
               <i class="bi bi-trash"></i>
            </button>`;
         }

         html += '</div>';
         return html;
      }

      /**
       * Create member-specific action buttons (filled style)
       * @param {Object} member - Member object
       * @param {Object} permissions - User permissions
       * @returns {string} HTML buttons
       */
      static memberActionButtons(member, permissions = {}) {
         return QMGridHelper.actionButtons(member.MbrRecID, {
            view: true,
            edit: true,
            delete: true,
            viewFn: 'viewMember',
            editFn: 'editMember',
            deleteFn: 'deleteMember',
            viewPermission: permissions.view_members !== false,
            editPermission: permissions.edit_members !== false,
            deletePermission: permissions.delete_members !== false,
            filled: true // Use filled buttons
         });
      }

      /**
       * Format member name with profile picture
       * @param {Object} member - Member object
       * @returns {string} HTML content
       */
      static formatMemberName(member) {
         const fullName = `${member.FirstName || ''} ${member.OtherNames || ''} ${member.FamilyName || ''}`.trim();
         const profilePic = member.ProfilePicture;
         
         return `
            <div class="d-flex align-items-center gap-2">
               ${QMGridHelper.formatProfilePicture(profilePic, fullName, 32)}
               <div>
                  <div class="fw-medium">${fullName}</div>
                  ${member.EmailAddress ? `<small class="text-muted">${member.EmailAddress}</small>` : ''}
               </div>
            </div>
         `;
      }

      /**
       * Process and format phone numbers
       * @param {string|Array|Object} phoneNumbers - Phone numbers data
       * @returns {string} Formatted display
       */
      static formatPhoneNumbers(phoneNumbers) {
         if (!phoneNumbers) return '-';
         
         // Handle string (JSON or plain)
         if (typeof phoneNumbers === 'string') {
            try {
               const parsed = JSON.parse(phoneNumbers);
               return QMGridHelper.formatPhoneNumbers(parsed);
            } catch (e) {
               return phoneNumbers;
            }
         }
         
         // Handle array
         if (Array.isArray(phoneNumbers)) {
            if (phoneNumbers.length === 0) return '-';
            const primary = phoneNumbers[0];
            const others = phoneNumbers.slice(1);
            
            let html = `<div>${primary}</div>`;
            if (others.length > 0) {
               html += `<small class="text-muted">+${others.length} more</small>`;
            }
            return html;
         }
         
         // Handle object
         if (typeof phoneNumbers === 'object') {
            const numbers = Object.values(phoneNumbers).filter(Boolean);
            return QMGridHelper.formatPhoneNumbers(numbers);
         }
         
         return '-';
      }

      /**
       * Create loading overlay for grid
       * @param {Object} grid - QMGrid instance
       */
      static showLoading(grid) {
         if (grid && typeof grid.showLoading === 'function') {
            grid.showLoading();
         }
      }

      /**
       * Hide loading overlay for grid
       * @param {Object} grid - QMGrid instance
       */
      static hideLoading(grid) {
         if (grid && typeof grid.hideLoading === 'function') {
            grid.hideLoading();
         }
      }

      /**
       * Export grid data with custom options
       * @param {Object} grid - QMGrid instance
       * @param {string} format - Export format (csv, excel, pdf, print)
       * @param {Object} options - Export options
       */
      static export(grid, format, options = {}) {
         if (grid && typeof grid.exportData === 'function') {
            grid.exportData(format, options);
         }
      }

      /**
       * Get selected rows from grid
       * @param {Object} grid - QMGrid instance
       * @returns {Array} Selected row data
       */
      static getSelectedRows(grid) {
         if (grid && typeof grid.getSelectedRows === 'function') {
            return grid.getSelectedRows();
         }
         return [];
      }

      /**
       * Clear selection in grid
       * @param {Object} grid - QMGrid instance
       */
      static clearSelection(grid) {
         if (grid && typeof grid.clearSelection === 'function') {
            grid.clearSelection();
         }
      }

      /**
       * Search in grid
       * @param {Object} grid - QMGrid instance
       * @param {string} searchTerm - Search term
       */
      static search(grid, searchTerm) {
         if (grid && typeof grid.search === 'function') {
            grid.search(searchTerm);
         }
      }

      /**
       * Go to specific page
       * @param {Object} grid - QMGrid instance
       * @param {number} page - Page number
       */
      static goToPage(grid, page) {
         if (grid && typeof grid.goToPage === 'function') {
            grid.goToPage(page);
         }
      }

      /**
       * Destroy grid instance and cleanup
       * @param {Object} grid - QMGrid instance
       */
      static destroy(grid) {
         if (grid && typeof grid.destroy === 'function') {
            grid.destroy();
         }
      }
   }

   // Expose to global scope
   window.QMGridHelper = QMGridHelper;

})(window);