<?php
$pageTitle = 'Expenses Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1"><i class="bi bi-receipt me-2"></i>Expenses</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Expenses</li>
            </ol>
         </nav>
      </div>
      <div class="d-flex align-items-center gap-2">
         <div class="d-flex align-items-center">
            <label class="form-label mb-0 me-2 text-muted small">Fiscal Year:</label>
            <select class="form-select form-select-sm" id="statsFiscalYear" style="width: 180px;">
               <option value="">Loading...</option>
            </select>
         </div>
         <button class="btn btn-outline-secondary" id="manageCategoriesBtn" data-permission="manage_expense_categories">
            <i class="bi bi-tags me-1"></i>Categories
         </button>
         <button class="btn btn-primary" id="addExpenseBtn" data-permission="create_expense">
            <i class="bi bi-plus-circle me-2"></i>Request Expense
         </button>
      </div>
   </div>

   <!-- Stats Cards Row 1 -->
   <div class="row mb-3" id="statsCardsRow1">
      <div class="col-12 text-center py-4">
         <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
      </div>
   </div>

   <!-- Stats Cards Row 2 -->
   <div class="row mb-4" id="statsCardsRow2"></div>

   <!-- Charts and Top Expenses Row -->
   <div class="row mb-4">
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>By Category</h6>
            </div>
            <div class="card-body"><canvas id="byCategoryChart" height="200"></canvas></div>
         </div>
      </div>
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Monthly Trend</h6>
            </div>
            <div class="card-body"><canvas id="monthlyTrendChart" height="200"></canvas></div>
         </div>
      </div>
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-sort-down me-2"></i>Top Expenses</h6>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                     <thead class="table-light">
                        <tr>
                           <th>Title</th>
                           <th>Category</th>
                           <th class="text-end">Amount</th>
                        </tr>
                     </thead>
                     <tbody id="topExpensesBody">
                        <tr>
                           <td colspan="3" class="text-center text-muted py-3">Loading...</td>
                        </tr>
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Filters Row -->
   <div class="row mb-4">
      <div class="col-12">
         <div class="card">
            <div class="card-body py-2">
               <div class="row g-2 align-items-end">
                  <div class="col-md-2">
                     <label class="form-label small mb-1">Status</label>
                     <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="Pending Approval">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Declined">Declined</option>
                     </select>
                  </div>
                  <div class="col-md-2">
                     <label class="form-label small mb-1">Category</label>
                     <select class="form-select form-select-sm" id="filterCategory">
                        <option value="">All Categories</option>
                     </select>
                  </div>
                  <div class="col-md-2">
                     <label class="form-label small mb-1">Start Date</label>
                     <input type="date" class="form-control form-control-sm" id="filterStartDate">
                  </div>
                  <div class="col-md-2">
                     <label class="form-label small mb-1">End Date</label>
                     <input type="date" class="form-control form-control-sm" id="filterEndDate">
                  </div>
                  <div class="col-md-2">
                     <button class="btn btn-primary btn-sm w-100" id="applyFiltersBtn"><i class="bi bi-search me-1"></i>Filter</button>
                  </div>
                  <div class="col-md-2">
                     <button class="btn btn-outline-secondary btn-sm w-100" id="clearFiltersBtn"><i class="bi bi-x-circle me-1"></i>Clear</button>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Expenses Table -->
   <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h5 class="mb-0"><i class="bi bi-table me-2"></i>All Expenses <span class="badge bg-primary ms-2" id="totalExpensesCount">0</span></h5>
            </div>
            <div class="col-md-6 text-end">
               <button class="btn btn-sm btn-outline-secondary" id="refreshGrid" title="Refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
         </div>
      </div>
      <div class="card-body">
         <div id="expensesTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Expense Request Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header bg-primary bg-opacity-10 border-0">
            <h5 class="modal-title" id="expenseModalTitle"><i class="bi bi-receipt me-2"></i>Request Expense</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body px-4">
            <form id="expenseForm">
               <input type="hidden" id="expenseId">
               <div class="mb-3">
                  <label class="form-label">Title <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="title" required maxlength="100" placeholder="e.g., Office Supplies">
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Amount <span class="text-danger">*</span></label>
                     <div class="input-group">
                        <span class="input-group-text" id="currencySymbol">GH₵</span>
                        <input type="number" class="form-control" id="amount" step="0.01" min="0.01" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Date <span class="text-danger">*</span></label>
                     <input type="date" class="form-control" id="expenseDate" required>
                  </div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Category <span class="text-danger">*</span></label>
                     <select class="form-select" id="categoryId" required>
                        <option value="">Select Category</option>
                     </select>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                     <select class="form-select" id="fiscalYear" required>
                        <option value="">Select Fiscal Year</option>
                     </select>
                  </div>
               </div>
               <div class="mb-3">
                  <label class="form-label">Purpose / Description</label>
                  <textarea class="form-control" id="purpose" rows="2" maxlength="1000" placeholder="Describe the purpose of this expense..."></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveExpenseBtn"><i class="bi bi-check-circle me-1"></i>Submit Request</button>
         </div>
      </div>
   </div>
</div>

<!-- View Expense Modal -->
<div class="modal fade" id="viewExpenseModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header border-0 p-0">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewExpenseContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading...</p>
            </div>
         </div>
         <div class="modal-footer border-0" id="viewExpenseFooter">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<!-- Review Expense Modal -->
<div class="modal fade" id="reviewExpenseModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-clipboard-check me-2"></i>Review Expense</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div id="reviewExpenseDetails" class="mb-3"></div>
            <div class="mb-3">
               <label class="form-label">Remarks (Optional)</label>
               <textarea class="form-control" id="reviewRemarks" rows="3" placeholder="Add any remarks..."></textarea>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="declineExpenseBtn"><i class="bi bi-x-circle me-1"></i>Decline</button>
            <button type="button" class="btn btn-success" id="approveExpenseBtn"><i class="bi bi-check-circle me-1"></i>Approve</button>
         </div>
      </div>
   </div>
</div>

<!-- Upload Proof Modal -->
<div class="modal fade" id="uploadProofModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Proof of Payment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div id="uploadProofDetails" class="mb-3"></div>
            <div class="mb-3">
               <label class="form-label">Proof Document <span class="text-danger">*</span></label>
               <input type="file" class="form-control" id="proofFileInput" accept=".jpg,.jpeg,.png,.gif,.pdf">
               <small class="text-muted">Upload receipt or invoice (JPG, PNG, GIF, PDF - max 5MB)</small>
            </div>
            <div id="proofUploadPreview" class="d-none">
               <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                  <i class="bi bi-file-earmark-check text-success"></i>
                  <span id="proofUploadFileName" class="small"></span>
                  <button type="button" class="btn btn-sm btn-outline-danger ms-auto" id="removeProofUploadBtn"><i class="bi bi-x"></i></button>
               </div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="submitProofBtn"><i class="bi bi-upload me-1"></i>Upload Proof</button>
         </div>
      </div>
   </div>
</div>

<!-- Manage Expense Categories Modal -->
<div class="modal fade" id="categoriesModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Manage Expense Categories</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="row mb-3">
               <div class="col-md-8">
                  <input type="text" class="form-control" id="newCategoryName" placeholder="Category name (e.g., Office Supplies, Utilities)">
               </div>
               <div class="col-md-4">
                  <button class="btn btn-primary w-100" id="addCategoryBtn"><i class="bi bi-plus"></i> Add Category</button>
               </div>
            </div>
            <div class="table-responsive">
               <table class="table table-hover" id="categoriesTable">
                  <thead class="table-light">
                     <tr>
                        <th>Category Name</th>
                        <th width="100">Actions</th>
                     </tr>
                  </thead>
                  <tbody id="categoriesBody">
                     <tr>
                        <td colspan="2" class="text-center py-3">Loading...</td>
                     </tr>
                  </tbody>
               </table>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-sm">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <input type="hidden" id="editCategoryId">
            <div class="mb-3">
               <label class="form-label">Category Name <span class="text-danger">*</span></label>
               <input type="text" class="form-control" id="editCategoryName" required>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save</button>
         </div>
      </div>
   </div>
</div>

<script src="../assets/js/core/qmgrid-helper.js"></script>
<script>
   (function() {
      'use strict';

      const State = {
         expensesTable: null,
         currentExpenseId: null,
         currentExpenseData: null,
         categoriesData: [],
         fiscalYearsData: [],
         categoryChoices: null,
         fiscalYearChoices: null,
         statsFiscalYearChoices: null,
         selectedFiscalYearId: null,
         currencySymbol: 'GH₵',
         byCategoryChart: null,
         monthlyTrendChart: null,
         currentFilters: {},
         editingCategoryId: null
      };

      document.addEventListener('DOMContentLoaded', async () => {
         if (!Auth.requireAuth()) return;
         await Config.waitForSettings();
         State.currencySymbol = Config.getSetting('currency_symbol', 'GH₵');
         document.getElementById('currencySymbol').textContent = State.currencySymbol;
         await initPage();
      });

      async function initPage() {
         await loadFiscalYearsForStats();
         await loadDropdowns();
         initTable();
         initEventListeners();
         loadStats();
         document.getElementById('expenseDate').valueAsDate = new Date();
      }

      async function loadFiscalYearsForStats() {
         try {
            const fiscalRes = await api.get('fiscalyear/all?limit=50');
            State.fiscalYearsData = Array.isArray(fiscalRes) ? fiscalRes : (fiscalRes?.data || []);

            const select = document.getElementById('statsFiscalYear');
            select.innerHTML = '';

            const activeFY = State.fiscalYearsData.find(fy => fy.Status === 'Active');
            State.selectedFiscalYearId = activeFY?.FiscalYearID || null;

            State.fiscalYearsData.forEach(fy => {
               const opt = document.createElement('option');
               opt.value = fy.FiscalYearID;
               opt.textContent = fy.FiscalYearName + (fy.Status === 'Active' ? ' (Active)' : fy.Status === 'Closed' ? ' (Closed)' : '');
               if (fy.FiscalYearID === State.selectedFiscalYearId) opt.selected = true;
               select.appendChild(opt);
            });

            if (State.statsFiscalYearChoices) State.statsFiscalYearChoices.destroy();
            State.statsFiscalYearChoices = new Choices(select, {
               searchEnabled: true,
               searchPlaceholderValue: 'Search fiscal years...',
               itemSelectText: '',
               allowHTML: true,
               shouldSort: false
            });

            select.addEventListener('change', (e) => {
               State.selectedFiscalYearId = e.target.value ? parseInt(e.target.value) : null;
               loadStats();
               reloadTable();
            });
         } catch (error) {
            console.error('Load fiscal years error:', error);
         }
      }

      function initTable() {
         const url = buildTableUrl();
         State.expensesTable = QMGridHelper.init('#expensesTable', {
            url: url,
            pageSize: 25,
            selectable: false,
            exportable: true,
            columns: [{
                  key: 'ExpTitle',
                  title: 'Title',
                  render: (v) => `<div class="fw-medium">${v || '-'}</div>`
               },
               {
                  key: 'ExpAmount',
                  title: 'Amount',
                  render: (v) => `<span class="fw-semibold text-danger">${formatCurrency(v)}</span>`
               },
               {
                  key: 'ExpDate',
                  title: 'Date',
                  render: (v) => QMGridHelper.formatDate(v, 'short')
               },
               {
                  key: 'CategoryName',
                  title: 'Category',
                  render: (v) => v ? `<span class="badge bg-secondary">${v}</span>` : '-'
               },
               {
                  key: 'ExpensePurpose',
                  title: 'Purpose',
                  render: (v) => v ? `<span class="text-muted small" title="${v}">${v.substring(0, 40)}${v.length > 40 ? '...' : ''}</span>` : '-'
               },
               {
                  key: 'ExpenseStatus',
                  title: 'Status',
                  render: (v) => {
                     const badges = {
                        'Pending Approval': 'warning',
                        'Approved': 'success',
                        'Declined': 'danger'
                     };
                     return `<span class="badge bg-${badges[v] || 'secondary'}">${v || '-'}</span>`;
                  }
               },
               {
                  key: 'ProofFile',
                  title: 'Proof',
                  width: '60px',
                  sortable: false,
                  render: (v) => v ? `<a href="${Config.API_BASE_URL}/../${v}" target="_blank" class="btn btn-sm btn-outline-success" title="View Proof"><i class="bi bi-file-earmark-check"></i></a>` : '<span class="text-muted">-</span>'
               },
               {
                  key: 'ExpID',
                  title: 'Actions',
                  width: '140px',
                  sortable: false,
                  exportable: false,
                  render: (v, row) => {
                     const isPending = row.ExpenseStatus === 'Pending Approval';
                     const isApproved = row.ExpenseStatus === 'Approved';
                     const hasProof = !!row.ProofFile;
                     let btns = `<button class="btn btn-primary btn-sm" onclick="viewExpense(${v})" title="View"><i class="bi bi-eye"></i></button>`;
                     if (isPending) btns += `<button class="btn btn-success btn-sm" onclick="reviewExpense(${v})" title="Review"><i class="bi bi-clipboard-check"></i></button>`;
                     if (isApproved && !hasProof) btns += `<button class="btn btn-warning btn-sm" onclick="uploadProof(${v})" title="Upload Proof"><i class="bi bi-upload"></i></button>`;
                     return `<div class="btn-group btn-group-sm">${btns}</div>`;
                  }
               }
            ],
            onDataLoaded: (data) => {
               document.getElementById('totalExpensesCount').textContent = data.pagination?.total || data.total || 0;
            }
         });
      }

      function buildTableUrl() {
         let url = `${Config.API_BASE_URL}/expense/all`;
         const params = new URLSearchParams();
         if (State.selectedFiscalYearId) params.append('fiscal_year_id', State.selectedFiscalYearId);
         if (State.currentFilters.status) params.append('status', State.currentFilters.status);
         if (State.currentFilters.category_id) params.append('category_id', State.currentFilters.category_id);
         if (State.currentFilters.start_date) params.append('start_date', State.currentFilters.start_date);
         if (State.currentFilters.end_date) params.append('end_date', State.currentFilters.end_date);
         if (params.toString()) url += '?' + params.toString();
         return url;
      }

      function reloadTable() {
         if (State.expensesTable) {
            State.expensesTable.destroy();
         }
         initTable();
      }

      async function loadStats() {
         try {
            let url = 'expense/stats';
            if (State.selectedFiscalYearId) url += `?fiscal_year_id=${State.selectedFiscalYearId}`;
            const stats = await api.get(url);
            renderStatsCards(stats);
            renderTopExpenses(stats.top_expenses || []);
            renderByCategoryChart(stats.by_category || []);
            renderMonthlyTrendChart(stats.monthly_trend || []);
         } catch (error) {
            console.error('Load stats error:', error);
            renderStatsCards({});
            renderTopExpenses([]);
         }
      }

      function renderStatsCards(stats) {
         const fyStatus = stats.fiscal_year?.status;
         const statusBadge = fyStatus === 'Closed' ? ' <span class="badge bg-secondary small">Closed</span>' : '';
         const row1Cards = [{
               title: `Total ${statusBadge}`,
               value: formatCurrency(stats.total_amount || 0),
               subtitle: `${(stats.total_count || 0).toLocaleString()} expenses`,
               icon: 'receipt',
               color: 'primary'
            },
            {
               title: 'Approved',
               value: formatCurrency(stats.approved_total || 0),
               subtitle: `${(stats.approved_count || 0).toLocaleString()} expenses`,
               icon: 'check-circle',
               color: 'success'
            },
            {
               title: 'Pending',
               value: formatCurrency(stats.pending_total || 0),
               subtitle: `${(stats.pending_count || 0).toLocaleString()} requests`,
               icon: 'clock-history',
               color: 'warning'
            },
            {
               title: 'Declined',
               value: formatCurrency(stats.rejected_total || 0),
               subtitle: `${(stats.rejected_count || 0).toLocaleString()} expenses`,
               icon: 'x-circle',
               color: 'danger'
            }
         ];
         const row2Cards = [{
               title: 'This Month',
               value: formatCurrency(stats.month_total || 0),
               subtitle: `<span class="badge bg-${(stats.month_growth || 0) >= 0 ? 'danger' : 'success'}">${(stats.month_growth || 0) >= 0 ? '+' : ''}${stats.month_growth || 0}%</span> vs last month`,
               icon: 'calendar-check',
               color: 'info'
            },
            {
               title: 'This Week',
               value: formatCurrency(stats.week_total || 0),
               subtitle: `${(stats.week_count || 0).toLocaleString()} expenses`,
               icon: 'calendar-week',
               color: 'secondary'
            },
            {
               title: 'Today',
               value: formatCurrency(stats.today_total || 0),
               subtitle: `${(stats.today_count || 0).toLocaleString()} expenses`,
               icon: 'calendar-day',
               color: 'dark'
            },
            {
               title: 'Average Expense',
               value: formatCurrency(stats.average_amount || 0),
               subtitle: 'Per transaction',
               icon: 'calculator',
               color: 'primary'
            }
         ];
         document.getElementById('statsCardsRow1').innerHTML = row1Cards.map(renderStatCard).join('');
         document.getElementById('statsCardsRow2').innerHTML = row2Cards.map(renderStatCard).join('');
      }

      function renderStatCard(card) {
         return `<div class="col-lg-3 col-md-6"><div class="card stat-card bg-${card.color} bg-opacity-10 mb-3"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted mb-1 small">${card.title}</p><h4 class="mb-0">${card.value}</h4><small class="text-muted">${card.subtitle}</small></div><div class="stat-icon bg-${card.color} text-white rounded-circle"><i class="bi bi-${card.icon}"></i></div></div></div></div></div>`;
      }

      function renderTopExpenses(expenses) {
         const tbody = document.getElementById('topExpensesBody');
         if (expenses.length > 0) {
            tbody.innerHTML = expenses.slice(0, 5).map(e => `<tr class="cursor-pointer" onclick="viewExpense(${e.ExpID})"><td><div class="fw-medium text-truncate" style="max-width:120px;" title="${e.ExpTitle}">${e.ExpTitle}</div></td><td><small class="text-muted">${e.CategoryName || '-'}</small></td><td class="text-end fw-semibold text-danger">${formatCurrency(parseFloat(e.ExpAmount))}</td></tr>`).join('');
         } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>';
         }
      }

      function renderByCategoryChart(byCategory) {
         const ctx = document.getElementById('byCategoryChart').getContext('2d');
         if (State.byCategoryChart) State.byCategoryChart.destroy();
         if (!byCategory.length) {
            ctx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
         }
         const colors = ['#dc3545', '#fd7e14', '#ffc107', '#198754', '#0d6efd', '#6f42c1', '#20c997', '#6c757d'];
         State.byCategoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
               labels: byCategory.map(c => c.CategoryName),
               datasets: [{
                  data: byCategory.map(c => parseFloat(c.total)),
                  backgroundColor: colors.slice(0, byCategory.length),
                  borderWidth: 0
               }]
            },
            options: {
               responsive: true,
               maintainAspectRatio: false,
               plugins: {
                  legend: {
                     position: 'bottom',
                     labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: {
                           size: 11
                        }
                     }
                  },
                  tooltip: {
                     callbacks: {
                        label: (ctx) => `${ctx.label}: ${formatCurrency(ctx.raw)}`
                     }
                  }
               }
            }
         });
      }

      function renderMonthlyTrendChart(monthlyTrend) {
         const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
         if (State.monthlyTrendChart) State.monthlyTrendChart.destroy();
         if (!monthlyTrend.length) {
            ctx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
         }
         State.monthlyTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
               labels: monthlyTrend.map(m => m.month_label),
               datasets: [{
                  label: 'Expenses',
                  data: monthlyTrend.map(m => parseFloat(m.total)),
                  borderColor: '#dc3545',
                  backgroundColor: 'rgba(220, 53, 69, 0.1)',
                  fill: true,
                  tension: 0.3,
                  pointRadius: 3,
                  pointHoverRadius: 5
               }]
            },
            options: {
               responsive: true,
               maintainAspectRatio: false,
               plugins: {
                  legend: {
                     display: false
                  },
                  tooltip: {
                     callbacks: {
                        label: (ctx) => formatCurrency(ctx.raw)
                     }
                  }
               },
               scales: {
                  y: {
                     beginAtZero: true,
                     ticks: {
                        callback: (v) => formatCurrencyShort(v)
                     }
                  },
                  x: {
                     ticks: {
                        font: {
                           size: 10
                        }
                     }
                  }
               }
            }
         });
      }

      async function loadDropdowns() {
         try {
            const categoriesRes = await api.get('expensecategory/all?limit=100');
            State.categoriesData = Array.isArray(categoriesRes) ? categoriesRes : (categoriesRes?.data || []);

            // Populate filter category dropdown (not using Choices.js)
            const filterCategorySelect = document.getElementById('filterCategory');
            filterCategorySelect.innerHTML = '<option value="">All Categories</option>';
            State.categoriesData.forEach(c => {
               filterCategorySelect.innerHTML += `<option value="${c.ExpCategoryID}">${c.CategoryName}</option>`;
            });
         } catch (error) {
            console.error('Load dropdowns error:', error);
         }
      }

      function initEventListeners() {
         document.getElementById('addExpenseBtn')?.addEventListener('click', () => {
            if (!Auth.hasPermission('expenses.create')) {
               Alerts.error('You do not have permission to request expenses');
               return;
            }
            openExpenseModal();
         });
         document.getElementById('saveExpenseBtn')?.addEventListener('click', saveExpense);
         document.getElementById('refreshGrid')?.addEventListener('click', () => {
            reloadTable();
            loadStats();
         });
         document.getElementById('applyFiltersBtn')?.addEventListener('click', applyFilters);
         document.getElementById('clearFiltersBtn')?.addEventListener('click', clearFilters);
         document.getElementById('approveExpenseBtn')?.addEventListener('click', () => submitReview('approve'));
         document.getElementById('declineExpenseBtn')?.addEventListener('click', () => submitReview('reject'));
         document.getElementById('proofFileInput')?.addEventListener('change', handleProofFileSelect);
         document.getElementById('removeProofUploadBtn')?.addEventListener('click', removeProofFile);
         document.getElementById('submitProofBtn')?.addEventListener('click', submitProofUpload);

         // Category Management
         document.getElementById('manageCategoriesBtn')?.addEventListener('click', openCategoriesModal);
         document.getElementById('addCategoryBtn')?.addEventListener('click', addCategory);
         document.getElementById('saveCategoryBtn')?.addEventListener('click', saveEditCategory);
      }

      // ========== EXPENSE CATEGORIES MANAGEMENT ==========
      async function openCategoriesModal() {
         new bootstrap.Modal(document.getElementById('categoriesModal')).show();
         await loadCategoriesTable();
      }

      async function loadCategoriesTable() {
         const tbody = document.getElementById('categoriesBody');
         tbody.innerHTML = '<tr><td colspan="2" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>';

         try {
            const res = await api.get('expensecategory/all');
            const categories = Array.isArray(res) ? res : (res?.data || []);
            State.categoriesData = categories;

            if (categories.length === 0) {
               tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">No categories found</td></tr>';
               return;
            }

            tbody.innerHTML = categories.map(c => `
               <tr>
                  <td class="fw-medium">${c.CategoryName}</td>
                  <td>
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning btn-sm" onclick="editCategory(${c.ExpCategoryID}, '${c.CategoryName.replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteCategory(${c.ExpCategoryID}, '${c.CategoryName.replace(/'/g, "\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
                     </div>
                  </td>
               </tr>
            `).join('');

            // Update filter dropdown
            const filterCategorySelect = document.getElementById('filterCategory');
            filterCategorySelect.innerHTML = '<option value="">All Categories</option>';
            categories.forEach(c => {
               filterCategorySelect.innerHTML += `<option value="${c.ExpCategoryID}">${c.CategoryName}</option>`;
            });
         } catch (error) {
            console.error('Load categories error:', error);
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger py-3">Failed to load categories</td></tr>';
         }
      }

      async function addCategory() {
         const name = document.getElementById('newCategoryName').value.trim();

         if (!name) {
            Alerts.warning('Please enter a category name');
            return;
         }

         try {
            Alerts.loading('Adding category...');
            await api.post('expensecategory/create', {
               name
            });
            Alerts.closeLoading();
            Alerts.success('Category added');
            document.getElementById('newCategoryName').value = '';
            await loadCategoriesTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      function editCategory(id, name) {
         State.editingCategoryId = id;
         document.getElementById('editCategoryId').value = id;
         document.getElementById('editCategoryName').value = name;
         new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
      }
      window.editCategory = editCategory;

      async function saveEditCategory() {
         const id = State.editingCategoryId;
         const name = document.getElementById('editCategoryName').value.trim();

         if (!name) {
            Alerts.warning('Please enter a category name');
            return;
         }

         try {
            Alerts.loading('Saving...');
            await api.put(`expensecategory/update/${id}`, {
               name
            });
            Alerts.closeLoading();
            Alerts.success('Category updated');
            bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide();
            await loadCategoriesTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function deleteCategory(id, name) {
         const confirmed = await Alerts.confirm(`Delete category "${name}"?`, 'This cannot be undone. Categories in use cannot be deleted.');
         if (!confirmed) return;

         try {
            Alerts.loading('Deleting...');
            await api.delete(`expensecategory/delete/${id}`);
            Alerts.closeLoading();
            Alerts.success('Category deleted');
            await loadCategoriesTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }
      window.deleteCategory = deleteCategory;

      function openExpenseModal() {
         document.getElementById('expenseForm').reset();
         document.getElementById('expenseId').value = '';
         document.getElementById('expenseModalTitle').innerHTML = '<i class="bi bi-receipt me-2"></i>Request Expense';
         document.getElementById('expenseDate').valueAsDate = new Date();

         // Initialize Choices.js for category select
         const categorySelect = document.getElementById('categoryId');
         categorySelect.innerHTML = '<option value="">Select Category</option>';
         State.categoriesData.forEach(c => {
            categorySelect.innerHTML += `<option value="${c.ExpCategoryID}">${c.CategoryName}</option>`;
         });
         if (State.categoryChoices) State.categoryChoices.destroy();
         State.categoryChoices = new Choices(categorySelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search categories...',
            itemSelectText: '',
            allowHTML: true
         });

         // Initialize Choices.js for fiscal year select
         const fiscalSelect = document.getElementById('fiscalYear');
         fiscalSelect.innerHTML = '<option value="">Select Fiscal Year</option>';
         State.fiscalYearsData.forEach(fy => {
            const opt = document.createElement('option');
            opt.value = fy.FiscalYearID;
            opt.textContent = fy.FiscalYearName + (fy.Status === 'Active' ? ' (Active)' : '');
            fiscalSelect.appendChild(opt);
         });
         if (State.fiscalYearChoices) State.fiscalYearChoices.destroy();
         State.fiscalYearChoices = new Choices(fiscalSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search fiscal years...',
            itemSelectText: '',
            allowHTML: true
         });

         // Set default fiscal year
         const activeYear = State.fiscalYearsData.find(fy => fy.Status === 'Active');
         if (activeYear && State.fiscalYearChoices) {
            State.fiscalYearChoices.setChoiceByValue(String(activeYear.FiscalYearID));
         }

         new bootstrap.Modal(document.getElementById('expenseModal')).show();
      }

      async function saveExpense() {
         const title = document.getElementById('title').value.trim();
         const amount = document.getElementById('amount').value;
         const expenseDate = document.getElementById('expenseDate').value;
         const categoryId = document.getElementById('categoryId').value;
         const fiscalYearId = document.getElementById('fiscalYear').value;
         const purpose = document.getElementById('purpose').value.trim();

         if (!title || !amount || !expenseDate || !categoryId || !fiscalYearId) {
            Alerts.warning('Please fill all required fields');
            return;
         }

         try {
            Alerts.loading('Submitting expense request...');
            await api.post('expense/create', {
               title,
               amount: parseFloat(amount),
               expense_date: expenseDate,
               category_id: parseInt(categoryId),
               fiscal_year_id: parseInt(fiscalYearId),
               purpose: purpose || null
            });
            Alerts.closeLoading();
            Alerts.success('Expense request submitted successfully');
            bootstrap.Modal.getInstance(document.getElementById('expenseModal')).hide();
            reloadTable();
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function viewExpense(expenseId) {
         State.currentExpenseId = expenseId;
         const modal = new bootstrap.Modal(document.getElementById('viewExpenseModal'));
         modal.show();

         try {
            const e = await api.get(`expense/view/${expenseId}`);
            State.currentExpenseData = e;
            const statusColors = {
               'Pending Approval': '#ffc107',
               'Approved': '#198754',
               'Declined': '#dc3545'
            };
            const statusColor = statusColors[e.ExpenseStatus] || '#6c757d';
            const isApproved = e.ExpenseStatus === 'Approved';
            const hasProof = !!e.ProofFile;

            document.getElementById('viewExpenseContent').innerHTML = `
         <div class="expense-view" id="printableExpense">
            <div class="text-center py-4" style="background: linear-gradient(135deg, ${statusColor} 0%, ${statusColor}99 100%);">
               <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                  <i class="bi bi-receipt" style="font-size:2rem;color:${statusColor};"></i>
               </div>
               <h2 class="text-white mb-1">${formatCurrency(e.ExpenseAmount)}</h2>
               <p class="text-white-50 mb-0">${e.ExpenseTitle}</p>
               <span class="badge bg-white text-dark mt-2">${e.ExpenseStatus}</span>
            </div>
            <div class="p-4">
               <div class="row g-3 mb-3">
                  <div class="col-6"><div class="text-muted small text-uppercase">Category</div><div class="fw-semibold">${e.CategoryName || '-'}</div></div>
                  <div class="col-6"><div class="text-muted small text-uppercase">Date</div><div class="fw-semibold">${new Date(e.ExpenseDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-6"><div class="text-muted small text-uppercase">Fiscal Year</div><div>${e.FiscalYearName || '-'}</div></div>
                  <div class="col-6"><div class="text-muted small text-uppercase">Branch</div><div>${e.BranchName || '-'}</div></div>
               </div>
               ${e.ExpensePurpose ? `<div class="mb-3 pt-3 border-top"><div class="text-muted small text-uppercase">Purpose</div><div>${e.ExpensePurpose}</div></div>` : ''}
               ${e.ProofFile ? `<div class="mb-3 pt-3 border-top"><div class="text-muted small text-uppercase">Proof of Payment</div><div class="mt-2"><a href="${Config.API_BASE_URL}/../${e.ProofFile}" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-check me-1"></i>View Document</a></div></div>` : ''}
               <div class="row g-3 pt-3 border-top">
                  <div class="col-6">
                     <div class="text-muted small text-uppercase">Requested By</div>
                     <div>${e.RequesterFirstName ? `${e.RequesterFirstName} ${e.RequesterFamilyName}` : '-'}</div>
                     ${e.RequestedAt ? `<small class="text-muted">${new Date(e.RequestedAt).toLocaleString()}</small>` : ''}
                  </div>
                  ${e.ApproverFirstName ? `<div class="col-6"><div class="text-muted small text-uppercase">${e.ExpenseStatus === 'Approved' ? 'Approved' : 'Reviewed'} By</div><div>${e.ApproverFirstName} ${e.ApproverFamilyName}</div>${e.ApprovedAt ? `<small class="text-muted">${new Date(e.ApprovedAt).toLocaleString()}</small>` : ''}</div>` : ''}
               </div>
               ${e.ApprovalRemarks ? `<div class="mt-3 pt-3 border-top"><div class="text-muted small text-uppercase">Remarks</div><div>${e.ApprovalRemarks}</div></div>` : ''}
            </div>
         </div>`;

            let footerHtml = `<button type="button" class="btn btn-outline-secondary" onclick="printExpense()"><i class="bi bi-printer me-1"></i>Print</button>`;
            if (e.ExpenseStatus === 'Pending Approval') {
               footerHtml += `<button type="button" class="btn btn-success" onclick="reviewExpense(${expenseId})"><i class="bi bi-clipboard-check me-1"></i>Review</button>`;
            }
            if (isApproved && !hasProof) {
               footerHtml += `<button type="button" class="btn btn-warning" onclick="uploadProof(${expenseId})"><i class="bi bi-upload me-1"></i>Upload Proof</button>`;
            }
            footerHtml += `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
            document.getElementById('viewExpenseFooter').innerHTML = footerHtml;
         } catch (error) {
            console.error('View expense error:', error);
            document.getElementById('viewExpenseContent').innerHTML = `<div class="text-center text-danger py-5"><i class="bi bi-exclamation-circle fs-1"></i><p class="mt-2">Failed to load expense details</p></div>`;
         }
      }
      window.viewExpense = viewExpense;

      function printExpense() {
         const e = State.currentExpenseData;
         if (!e) return;

         const printWindow = window.open('', '_blank');
         printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
         <title>Expense Details - ${e.ExpenseTitle}</title>
         <style>
            body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { margin: 0; font-size: 24px; }
            .header p { margin: 5px 0; color: #666; }
            .amount { font-size: 32px; font-weight: bold; color: #dc3545; text-align: center; margin: 20px 0; }
            .status { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
            .status-approved { background: #d4edda; color: #155724; }
            .status-pending { background: #fff3cd; color: #856404; }
            .status-declined { background: #f8d7da; color: #721c24; }
            .details { margin: 20px 0; }
            .row { display: flex; border-bottom: 1px solid #eee; padding: 10px 0; }
            .label { width: 150px; font-weight: bold; color: #666; }
            .value { flex: 1; }
            .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #999; }
            @media print { body { padding: 0; } }
         </style>
      </head>
      <body>
         <div class="header">
            <h1>EXPENSE VOUCHER</h1>
            <p>Reference: EXP-${String(e.ExpenseID).padStart(6, '0')}</p>
         </div>
         <div class="amount">${formatCurrency(e.ExpenseAmount)}</div>
         <div style="text-align: center; margin-bottom: 20px;">
            <span class="status status-${e.ExpenseStatus === 'Approved' ? 'approved' : e.ExpenseStatus === 'Pending Approval' ? 'pending' : 'declined'}">${e.ExpenseStatus}</span>
         </div>
         <div class="details">
            <div class="row"><div class="label">Title:</div><div class="value">${e.ExpenseTitle}</div></div>
            <div class="row"><div class="label">Category:</div><div class="value">${e.CategoryName || '-'}</div></div>
            <div class="row"><div class="label">Date:</div><div class="value">${new Date(e.ExpenseDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>
            <div class="row"><div class="label">Fiscal Year:</div><div class="value">${e.FiscalYearName || '-'}</div></div>
            ${e.BranchName ? `<div class="row"><div class="label">Branch:</div><div class="value">${e.BranchName}</div></div>` : ''}
            ${e.ExpensePurpose ? `<div class="row"><div class="label">Purpose:</div><div class="value">${e.ExpensePurpose}</div></div>` : ''}
            <div class="row"><div class="label">Requested By:</div><div class="value">${e.RequesterFirstName ? `${e.RequesterFirstName} ${e.RequesterFamilyName}` : '-'}</div></div>
            ${e.RequestedAt ? `<div class="row"><div class="label">Requested At:</div><div class="value">${new Date(e.RequestedAt).toLocaleString()}</div></div>` : ''}
            ${e.ApproverFirstName ? `<div class="row"><div class="label">${e.ExpenseStatus === 'Approved' ? 'Approved' : 'Reviewed'} By:</div><div class="value">${e.ApproverFirstName} ${e.ApproverFamilyName}</div></div>` : ''}
            ${e.ApprovedAt ? `<div class="row"><div class="label">${e.ExpenseStatus === 'Approved' ? 'Approved' : 'Reviewed'} At:</div><div class="value">${new Date(e.ApprovedAt).toLocaleString()}</div></div>` : ''}
            ${e.ApprovalRemarks ? `<div class="row"><div class="label">Remarks:</div><div class="value">${e.ApprovalRemarks}</div></div>` : ''}
            ${e.ProofFile ? `<div class="row"><div class="label">Proof:</div><div class="value">Document attached</div></div>` : ''}
         </div>
         <div class="footer">
            <p>Printed on ${new Date().toLocaleString()}</p>
         </div>
      </body>
      </html>`);
         printWindow.document.close();
         printWindow.focus();
         setTimeout(() => {
            printWindow.print();
         }, 250);
      }
      window.printExpense = printExpense;

      async function reviewExpense(expenseId) {
         if (!Auth.hasPermission('expenses.approve')) {
            Alerts.error('You do not have permission to review expenses');
            return;
         }
         State.currentExpenseId = expenseId;
         const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewExpenseModal'));
         if (viewModal) viewModal.hide();

         try {
            const e = await api.get(`expense/view/${expenseId}`);
            document.getElementById('reviewExpenseDetails').innerHTML = `
         <div class="card bg-light">
            <div class="card-body">
               <h6 class="mb-2">${e.ExpenseTitle}</h6>
               <div class="row">
                  <div class="col-6"><small class="text-muted">Amount</small><div class="fw-bold text-danger">${formatCurrency(e.ExpenseAmount)}</div></div>
                  <div class="col-6"><small class="text-muted">Category</small><div>${e.CategoryName || '-'}</div></div>
               </div>
               <div class="row mt-2">
                  <div class="col-6"><small class="text-muted">Date</small><div>${new Date(e.ExpenseDate).toLocaleDateString()}</div></div>
                  <div class="col-6"><small class="text-muted">Fiscal Year</small><div>${e.FiscalYearName || '-'}</div></div>
               </div>
               ${e.ExpensePurpose ? `<div class="mt-2"><small class="text-muted">Purpose</small><div>${e.ExpensePurpose}</div></div>` : ''}
            </div>
         </div>`;
            document.getElementById('reviewRemarks').value = '';
            new bootstrap.Modal(document.getElementById('reviewExpenseModal')).show();
         } catch (error) {
            console.error('Load expense for review error:', error);
            Alerts.error('Failed to load expense details');
         }
      }
      window.reviewExpense = reviewExpense;

      async function submitReview(action) {
         const remarks = document.getElementById('reviewRemarks').value.trim();
         try {
            Alerts.loading(`${action === 'approve' ? 'Approving' : 'Declining'} expense...`);
            await api.post(`expense/review/${State.currentExpenseId}`, {
               action,
               remarks: remarks || null
            });
            Alerts.closeLoading();
            Alerts.success(`Expense ${action === 'approve' ? 'approved' : 'declined'} successfully`);
            bootstrap.Modal.getInstance(document.getElementById('reviewExpenseModal')).hide();
            reloadTable();
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      function uploadProof(expenseId) {
         State.currentExpenseId = expenseId;
         const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewExpenseModal'));
         if (viewModal) viewModal.hide();

         document.getElementById('proofFileInput').value = '';
         document.getElementById('proofUploadPreview').classList.add('d-none');

         const e = State.currentExpenseData;
         document.getElementById('uploadProofDetails').innerHTML = e ? `
      <div class="card bg-light">
         <div class="card-body">
            <h6 class="mb-2">${e.ExpenseTitle}</h6>
            <div class="row">
               <div class="col-6"><small class="text-muted">Amount</small><div class="fw-bold text-danger">${formatCurrency(e.ExpenseAmount)}</div></div>
               <div class="col-6"><small class="text-muted">Status</small><div><span class="badge bg-success">${e.ExpenseStatus}</span></div></div>
            </div>
         </div>
      </div>` : '';

         new bootstrap.Modal(document.getElementById('uploadProofModal')).show();
      }
      window.uploadProof = uploadProof;

      function handleProofFileSelect(e) {
         const file = e.target.files[0];
         if (!file) return;
         const maxSize = 5 * 1024 * 1024;
         const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
         if (file.size > maxSize) {
            Alerts.warning('File size exceeds 5MB limit');
            e.target.value = '';
            return;
         }
         if (!allowedTypes.includes(file.type)) {
            Alerts.warning('Invalid file type. Allowed: JPG, PNG, GIF, PDF');
            e.target.value = '';
            return;
         }
         document.getElementById('proofUploadFileName').textContent = file.name;
         document.getElementById('proofUploadPreview').classList.remove('d-none');
      }

      function removeProofFile() {
         document.getElementById('proofFileInput').value = '';
         document.getElementById('proofUploadPreview').classList.add('d-none');
      }

      async function submitProofUpload() {
         const fileInput = document.getElementById('proofFileInput');
         if (!fileInput.files[0]) {
            Alerts.warning('Please select a file to upload');
            return;
         }

         const formData = new FormData();
         formData.append('proof', fileInput.files[0]);

         try {
            Alerts.loading('Uploading proof...');
            await fetch(`${Config.API_BASE_URL}/expense/upload-proof/${State.currentExpenseId}`, {
               method: 'POST',
               headers: {
                  'Authorization': `Bearer ${Auth.getToken()}`
               },
               body: formData
            });
            Alerts.closeLoading();
            Alerts.success('Proof uploaded successfully');
            bootstrap.Modal.getInstance(document.getElementById('uploadProofModal')).hide();
            reloadTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.error('Failed to upload proof');
         }
      }

      function applyFilters() {
         State.currentFilters = {
            status: document.getElementById('filterStatus').value,
            category_id: document.getElementById('filterCategory').value,
            start_date: document.getElementById('filterStartDate').value,
            end_date: document.getElementById('filterEndDate').value
         };
         Object.keys(State.currentFilters).forEach(k => !State.currentFilters[k] && delete State.currentFilters[k]);
         reloadTable();
      }

      function clearFilters() {
         document.getElementById('filterStatus').value = '';
         document.getElementById('filterCategory').value = '';
         document.getElementById('filterStartDate').value = '';
         document.getElementById('filterEndDate').value = '';
         State.currentFilters = {};
         reloadTable();
      }

      function formatCurrency(amount) {
         if (amount === null || amount === undefined) return '-';
         const num = parseFloat(amount);
         if (isNaN(num)) return '-';
         return `${State.currencySymbol} ${num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
      }

      function formatCurrencyShort(amount) {
         if (amount >= 1000000) return `${State.currencySymbol}${(amount/1000000).toFixed(1)}M`;
         if (amount >= 1000) return `${State.currencySymbol}${(amount/1000).toFixed(1)}K`;
         return `${State.currencySymbol}${amount}`;
      }
   })();
</script>

<style>
   .stat-card {
      border: none;
      transition: transform 0.2s;
   }

   .stat-card:hover {
      transform: translateY(-2px);
   }

   .stat-icon {
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
   }

   .expense-view {
      border-radius: 0;
   }

   .cursor-pointer {
      cursor: pointer;
   }

   .cursor-pointer:hover {
      background-color: rgba(0, 0, 0, 0.02);
   }

   @media print {

      .modal-footer,
      .btn-close {
         display: none !important;
      }
   }
</style>

<?php require_once '../includes/footer.php'; ?>