
/**
 * Budget Table Management
 */

import { Config } from '../../core/config.js';

export class BudgetTable {
   constructor(state, api) {
      this.state = state;
      this.api = api;
   }

   init() {
      this.state.table = QMGridHelper.initWithExport('#budgetsTable', {
         url: `${Config.API_BASE_URL}/budget/all`,
         pageSize: 25,
         filename: 'budgets_export',
         onDataLoaded: (data) => {
            console.log(`Loaded ${data.data.length} budgets`);
         },
         onError: (error) => {
            console.error('Failed to load budgets:', error);
            Alerts.error('Failed to load budgets data');
         },
         columns: [{
               key: 'BudgetTitle',
               title: 'Title',
               render: function(data) {
                  return `<div class="fw-medium">${data}</div>`;
               }
            },
            {
               key: 'YearName',
               title: 'Fiscal Year',
               render: function(data) {
                  return data || '-';
               }
            },
            {
               key: 'BranchName',
               title: 'Branch',
               render: function(data) {
                  return data || '-';
               }
            },
            {
               key: 'TotalAmount',
               title: 'Total Amount',
               render: function(data) {
                  return QMGridHelper.formatCurrency(data);
               }
            },
            {
               key: 'BudgetStatus',
               title: 'Status',
               render: function(data) {
                  return QMGridHelper.statusBadge(data, {
                     'draft': 'warning',
                     'submitted': 'info',
                     'approved': 'success',
                     'rejected': 'danger',
                     'active': 'success',
                     'closed': 'secondary'
                  });
               }
            },
            {
               key: 'CreatedAt',
               title: 'Created',
               render: function(data) {
                  return QMGridHelper.formatDate(data, 'short');
               }
            },
            {
               key: 'progress',
               title: 'Progress',
               sortable: false,
               render: function(data, row) {
                  const spent = parseFloat(row.SpentAmount || 0);
                  const total = parseFloat(row.TotalAmount || 1);
                  const percentage = Math.min((spent / total) * 100, 100);

                  let progressClass = 'bg-success';
                  if (percentage > 90) progressClass = 'bg-danger';
                  else if (percentage > 75) progressClass = 'bg-warning';

                  return `
                     <div class="progress" style="height: 20px;">
                        <div class="progress-bar ${progressClass}" role="progressbar" 
                             style="width: ${percentage}%" aria-valuenow="${percentage}" 
                             aria-valuemin="0" aria-valuemax="100">
                           ${percentage.toFixed(1)}%
                        </div>
                     </div>
                  `;
               }
            },
            {
               key: 'BudgetID',
               title: 'Actions',
               sortable: false,
               exportable: false,
               render: function(data, row) {
                  return QMGridHelper.actionButtons(data, {
                     view: true,
                     edit: row.BudgetStatus === 'Draft',
                     delete: row.BudgetStatus === 'Draft',
                     viewFn: 'viewBudget',
                     editFn: 'editBudget',
                     deleteFn: 'deleteBudget',
                     viewPermission: Auth.hasPermission('view_budget'),
                     editPermission: Auth.hasPermission('create_budget'),
                     deletePermission: Auth.hasPermission('delete_budget'),
                     custom: [{
                        icon: 'check-circle',
                        color: 'success',
                        title: 'Approve Budget',
                        fn: 'approveBudget',
                        permission: Auth.hasPermission('approve_budget') && row.BudgetStatus === 'Submitted'
                     }]
                  });
               }
            }
         ]
      });
   }

   reload() {
      if (this.state.table) {
         QMGridHelper.reload(this.state.table);
      }
   }
}
