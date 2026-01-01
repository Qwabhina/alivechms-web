/**
 * QMGrid Helper - Server-Side Wrapper for QMGrid
 * 
 * Provides server-side pagination, search, and sort functionality for QMGrid
 * 
 * @package  AliveChMS
 * @version  2.0.0
 * @author   Qwabhina McFynn
 */

(function(window) {
   'use strict';

   class QMGridHelper {
      /**
       * Initialize QMGrid with server-side data loading
       * 
       * @param {string} selector - Table container selector
       * @param {Object} config - Configuration object
       * @returns {Object} QMGrid instance with server-side capabilities
       */
      static async init(selector, config = {}) {
         const {
            url,
            columns = [],
            pageSize = 10,
            exportable = false,
            onDataLoad = null
         } = config;

         // State management
         const state = {
            currentPage: 1,
            pageSize: pageSize,
            searchTerm: '',
            sortColumn: '',
            sortDirection: 'asc',
            totalRecords: 0
         };

         // Load initial data
         const initialData = await QMGridHelper.loadServerData(url, state);
         
         // Initialize QMGrid with client-side features
         const grid = new QMGrid(selector, {
            data: initialData.data,
            columns: columns.map(col => ({
               key: col.key,
               title: col.title,
               width: col.width,
               sortable: col.sortable !== false,
               className: col.className || '',
               render: col.render
            })),
            pagination: true,
            pageSize: state.pageSize,
            sortable: true,
            searchable: true,
            exportable: exportable,
            striped: true,
            bordered: true,
            hover: true,
            responsive: true
         });

         // Store state and URL in grid instance
         grid._serverState = state;
         grid._serverUrl = url;
         grid._onDataLoad = onDataLoad;

         // Override search to use server-side
         grid.on('search', async (data) => {
            state.searchTerm = data.term;
            state.currentPage = 1; // Reset to first page on search
            await QMGridHelper.reloadServerData(grid);
         });

         // Override sort to use server-side
         grid.on('sort', async (data) => {
            state.sortColumn = data.column;
            state.sortDirection = data.direction;
            await QMGridHelper.reloadServerData(grid);
         });

         // Override pagination to use server-side
         grid.on('pageChange', async (data) => {
            state.currentPage = data.page;
            await QMGridHelper.reloadServerData(grid);
         });

         // Add custom reload method
         grid.reloadFromServer = async () => {
            await QMGridHelper.reloadServerData(grid);
         };

         return grid;
      }

      /**
       * Initialize QMGrid with export buttons
       */
      static async initWithButtons(selector, config = {}) {
         return await QMGridHelper.init(selector, {
            ...config,
            exportable: true
         });
      }

      /**
       * Load data from server
       */
      static async loadServerData(url, state) {
         try {
            const params = new URLSearchParams({
               page: state.currentPage,
               limit: state.pageSize,
               search: state.searchTerm || '',
               sort: state.sortColumn || '',
               order: state.sortDirection || 'asc'
            });

            const response = await fetch(`${url}?${params}`, {
               method: 'GET',
               headers: {
                  'Authorization': `Bearer ${Auth.getToken()}`,
                  'Content-Type': 'application/json'
               }
            });

            if (response.status === 401) {
               Auth.logout();
               return { data: [], total: 0 };
            }

            if (!response.ok) {
               throw new Error(`HTTP error! status: ${response.status}`);
            }

            const json = await response.json();
            
            // Handle different response formats
            let data = [];
            let total = 0;

            if (json.success && json.data) {
               if (Array.isArray(json.data.data)) {
                  data = json.data.data;
                  total = json.data.pagination?.total || data.length;
               } else if (Array.isArray(json.data)) {
                  data = json.data;
                  total = json.pagination?.total || data.length;
               }
            } else if (Array.isArray(json.data)) {
               data = json.data;
               total = json.recordsTotal || json.recordsFiltered || data.length;
            }

            state.totalRecords = total;

            return { data, total };
         } catch (error) {
            console.error('Server data load error:', error);
            return { data: [], total: 0 };
         }
      }

      /**
       * Reload data from server
       */
      static async reloadServerData(grid) {
         const result = await QMGridHelper.loadServerData(
            grid._serverUrl,
            grid._serverState
         );

         grid.setData(result.data);

         // Call custom callback if provided
         if (grid._onDataLoad && typeof grid._onDataLoad === 'function') {
            grid._onDataLoad(result);
         }

         return result;
      }

      /**
       * Reload table (for backward compatibility)
       */
      static async reload(grid, resetPaging = false) {
         if (!grid) return;

         if (resetPaging && grid._serverState) {
            grid._serverState.currentPage = 1;
         }

         if (grid.reloadFromServer) {
            await grid.reloadFromServer();
         } else {
            // Fallback for non-server grids
            grid.refresh();
         }
      }

      /**
       * Format date for display
       */
      static formatDate(dateString) {
         if (!dateString) return '-';
         try {
            const date = new Date(dateString);
            return date.toLocaleDateString();
         } catch (e) {
            return dateString;
         }
      }

      /**
       * Format currency for display
       */
      static formatCurrency(amount) {
         if (amount === null || amount === undefined) return '-';
         const num = parseFloat(amount);
         if (isNaN(num)) return '-';

         const formatted = num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
         const symbol = Config.getSetting('currency_symbol', 'GH₵');

         return `${symbol} ${formatted}`;
      }

      /**
       * Format status badge
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

         return `<span class="badge bg-${color}">${status || 'Unknown'}</span>`;
      }

      /**
       * Format action buttons
       */
      static actionButtons(id, options = {}) {
         const {
            view = true,
            edit = true,
            delete: del = true,
            custom = [],
            viewFn = 'viewRecord',
            editFn = 'editRecord',
            deleteFn = 'deleteRecord'
         } = options;

         let html = '<div class="btn-group btn-group-sm" role="group">';

         if (view) {
            html += `<button class="btn btn-outline-primary" onclick="${viewFn}(${id})" title="View">
               <i class="bi bi-eye"></i>
            </button>`;
         }

         if (edit) {
            html += `<button class="btn btn-outline-warning" onclick="${editFn}(${id})" title="Edit">
               <i class="bi bi-pencil"></i>
            </button>`;
         }

         // Add custom buttons
         if (custom && custom.length > 0) {
            custom.forEach(btn => {
               html += `<button class="btn btn-outline-${btn.color || 'secondary'}" 
                        onclick="${btn.fn}(${id})" title="${btn.title || ''}">
                  <i class="bi bi-${btn.icon}"></i>
               </button>`;
            });
         }

         if (del) {
            html += `<button class="btn btn-outline-danger" onclick="${deleteFn}(${id})" title="Delete">
               <i class="bi bi-trash"></i>
            </button>`;
         }

         html += '</div>';
         return html;
      }
   }

   // Expose to global scope
   window.QMGridHelper = QMGridHelper;

})(window);
