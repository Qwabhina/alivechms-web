/**
 * QMGridHelper
 * 
 * Optimized wrapper for QMGrid with proper server-side processing
 * and integration with AliveChMS API response structure
 * 
 * @package  AliveChMS
 * @version  1.0.0
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
               // Add authentication headers
               const token = localStorage.getItem(Config.TOKEN_KEY);
               if (token) {
                  options.headers = {
                     ...options.headers,
                     'Authorization': `Bearer ${token}`,
                     'Content-Type': 'application/json',
                     'Accept': 'application/json'
                  };
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
               data: 'data',              // Path to data array in response
               totalRecords: 'total',     // Path to total records count
               error: 'error',            // Path to error message (if present)
               draw: 'draw'               // Request identifier (optional)
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
               : `${Config.API_BASE_URL}/${profilePicture}`;
            
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
       * Format action buttons with proper icons
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
            deletePermission = true
         } = options;

         let html = '<div class="btn-group btn-group-sm" role="group">';

         if (view && viewPermission) {
            html += `<button class="btn btn-outline-primary" onclick="${viewFn}(${id})" title="View">
               <i class="bi bi-eye"></i>
            </button>`;
         }

         if (edit && editPermission) {
            html += `<button class="btn btn-outline-warning" onclick="${editFn}(${id})" title="Edit">
               <i class="bi bi-pencil"></i>
            </button>`;
         }

         // Add custom buttons
         if (custom && custom.length > 0) {
            custom.forEach(btn => {
               if (btn.permission !== false) {
                  html += `<button class="btn btn-outline-${btn.color || 'secondary'}" 
                           onclick="${btn.fn}(${id})" title="${btn.title || ''}">
                     <i class="bi bi-${btn.icon}"></i>
                  </button>`;
               }
            });
         }

         if (del && deletePermission) {
            html += `<button class="btn btn-outline-danger" onclick="${deleteFn}(${id})" title="Delete">
               <i class="bi bi-trash"></i>
            </button>`;
         }

         html += '</div>';
         return html;
      }

      /**
       * Create member-specific action buttons
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
            custom: [
               {
                  icon: 'person-badge',
                  color: 'info',
                  title: 'View Profile',
                  fn: 'viewMemberProfile',
                  permission: permissions.view_members !== false
               }
            ]
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