<?php
$pageTitle = 'Expenses Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Expenses</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Expenses</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addExpenseBtn" data-permission="create_expense">
         <i class="bi bi-plus-circle me-2"></i>Request Expense
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Total Expenses</p>
                     <h3 class="mb-0" id="totalExpenses">-</h3>
                     <small class="text-muted"><span id="expenseCount">0</span> expenses</small>
                  </div>
                  <div class="stat-icon bg-primary text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-receipt"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-warning bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Pending</p>
                     <h3 class="mb-0" id="pendingAmount">-</h3>
                     <small class="text-muted"><span id="pendingCount">0</span> requests</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-clock-history"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-success bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Approved</p>
                     <h3 class="mb-0" id="approvedAmount">-</h3>
                     <small class="text-muted"><span id="approvedCount">0</span> expenses</small>
                  </div>
                  <div class="stat-icon bg-success text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-check-circle"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-info bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">This Month</p>
                     <h3 class="mb-0" id="monthAmount">-</h3>
                     <small class="text-muted"><span id="monthCount">0</span> expenses</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-calendar-month"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Filters -->
   <div class="card mb-4">
      <div class="card-header">
         <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
      </div>
      <div class="card-body">
         <div class="row g-3">
            <div class="col-md-3">
               <label class="form-label small">Status</label>
               <select class="form-select form-select-sm" id="filterStatus">
                  <option value="">All Status</option>
                  <option value="Pending Approval">Pending</option>
                  <option value="Approved">Approved</option>
                  <option value="Rejected">Rejected</option>
                  <option value="Cancelled">Cancelled</option>
               </select>
            </div>
            <div class="col-md-3">
               <label class="form-label small">Category</label>
               <select class="form-select form-select-sm" id="filterCategory">
                  <option value="">All Categories</option>
               </select>
            </div>
            <div class="col-md-2">
               <label class="form-label small">Start Date</label>
               <input type="date" class="form-control form-control-sm" id="filterStartDate">
            </div>
            <div class="col-md-2">
               <label class="form-label small">End Date</label>
               <input type="date" class="form-control form-control-sm" id="filterEndDate">
            </div>
            <div class="col-md-2 d-flex align-items-end">
               <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()">
                  <i class="bi bi-search me-1"></i>Apply
               </button>
            </div>
         </div>
      </div>
   </div>

   <!-- Expenses Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-receipt-cutoff me-2"></i>All Expenses</h5>
      </div>
      <div class="card-body">
         <div class="d-flex justify-content-end mb-3 gap-2">
            <button class="btn btn-success btn-sm" onclick="expensesGrid.download('xlsx', 'expenses.xlsx')">
               <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="expensesGrid.download('pdf', 'expenses.pdf', {orientation:'landscape', title:'Expenses Report'})">
               <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
            <button class="btn btn-primary btn-sm" onclick="expensesGrid.print()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-secondary btn-sm" onclick="expensesGrid.setData()">
               <i class="bi bi-arrow-clockwise"></i>
            </button>
         </div>
         <div class="table-responsive">
            <div id="expensesGrid"></div>
         </div>
      </div>
   </div>
</div>
</main>

<!-- Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Request Expense</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="expenseForm">
               <div class="mb-3">
                  <label class="form-label">Title <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="title" required maxlength="150">
               </div>
               <div class="mb-3">
                  <label class="form-label">Amount (<span id="currencySymbol"></span>) <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="amount" step="0.01" min="0.01" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="expenseDate" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Category <span class="text-danger">*</span></label>
                  <select class="form-select" id="categoryId" required>
                     <option value="">Select Category</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Branch <span class="text-danger">*</span></label>
                  <select class="form-select" id="branchId" required>
                     <option value="">Select Branch</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                  <select class="form-select" id="fiscalYear" required>
                     <option value="">Select Fiscal Year</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" id="description" rows="3" maxlength="1000"></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveExpenseBtn">
               <i class="bi bi-check-circle me-1"></i>Submit Request
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let expensesGrid = null;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;

      // Set currency symbol in form label
      const currencySymbol = Config.getSetting('currency_symbol', 'GH₵');
      document.getElementById('currencySymbol').textContent = currencySymbol;

      await initPage();
   });

   async function initPage() {
      initGrid();
      initEventListeners();
      await loadDropdowns();
      loadStats();
      document.getElementById('expenseDate').valueAsDate = new Date();
   }

   function initGrid() {
      expensesGrid = new Tabulator("#expensesGrid", {
         layout: "fitColumns",
         responsiveLayout: "collapse",
         resizableColumns: false,
         pagination: true,
         paginationMode: "remote",
         paginationSize: 25,
         paginationSizeSelector: [10, 25, 50, 100],
         ajaxURL: `${Config.API_BASE_URL}/expense/all`,
         ajaxConfig: {
            headers: {
               'Authorization': `Bearer ${Auth.getToken()}`
            }
         },
         ajaxResponse: function(url, params, response) {
            const data = response?.data?.data || response?.data || [];
            const pagination = response?.data?.pagination || {};
            return {
               last_page: pagination.pages || 1,
               data: data.map(e => ({
                  title: e.ExpenseTitle,
                  amount: parseFloat(e.ExpenseAmount),
                  date: e.ExpenseDate,
                  category: e.CategoryName,
                  branch: e.BranchName,
                  status: e.ExpenseStatus,
                  id: e.ExpenseID
               }))
            };
         },
         ajaxURLGenerator: function(url, config, params) {
            let queryParams = [];
            if (params.page) queryParams.push(`page=${params.page}`);
            if (params.size) queryParams.push(`limit=${params.size}`);
            return queryParams.length ? `${url}?${queryParams.join('&')}` : url;
         },
         columns: [{
               title: "Title",
               field: "title",
               widthGrow: 2,
               responsive: 0,
               download: true
            },
            {
               title: "Amount",
               field: "amount",
               widthGrow: 1.5,
               responsive: 0,
               download: true,
               formatter: cell => formatCurrency(parseFloat(cell.getValue()))
            },
            {
               title: "Date",
               field: "date",
               widthGrow: 1.5,
               responsive: 1,
               download: true,
               formatter: cell => new Date(cell.getValue()).toLocaleDateString()
            },
            {
               title: "Category",
               field: "category",
               widthGrow: 1.5,
               responsive: 2,
               download: true
            },
            {
               title: "Branch",
               field: "branch",
               widthGrow: 1.5,
               responsive: 2,
               download: true
            },
            {
               title: "Status",
               field: "status",
               widthGrow: 1.5,
               responsive: 1,
               download: false,
               formatter: cell => {
                  const status = cell.getValue();
                  const badges = {
                     'Pending Approval': 'warning',
                     'Approved': 'success',
                     'Rejected': 'danger',
                     'Cancelled': 'secondary'
                  };
                  return `<span class="badge bg-${badges[status] || 'secondary'}">${status}</span>`;
               }
            },
            {
               title: "Actions",
               field: "id",
               width: 100,
               headerSort: false,
               responsive: 0,
               download: false,
               formatter: cell => {
                  const id = cell.getValue();
                  return `
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewExpense(${id})" title="View">
                           <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="approveExpense(${id})" title="Approve">
                           <i class="bi bi-check"></i>
                        </button>
                     </div>
                  `;
               }
            }
         ]
      });
   }

   async function loadStats() {
      try {
         const response = await api.get('expense/all?limit=1000');
         const expenses = response?.data?.data || response?.data || [];

         let totalAmount = 0,
            totalCount = 0;
         let pendingAmount = 0,
            pendingCount = 0;
         let approvedAmount = 0,
            approvedCount = 0;
         let monthAmount = 0,
            monthCount = 0;

         const now = new Date();
         const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);

         expenses.forEach(e => {
            const amount = parseFloat(e.ExpenseAmount);
            totalAmount += amount;
            totalCount++;

            if (e.ExpenseStatus === 'Pending Approval') {
               pendingAmount += amount;
               pendingCount++;
            } else if (e.ExpenseStatus === 'Approved') {
               approvedAmount += amount;
               approvedCount++;
            }

            const expenseDate = new Date(e.ExpenseDate);
            if (expenseDate >= monthStart) {
               monthAmount += amount;
               monthCount++;
            }
         });

         document.getElementById('totalExpenses').textContent = formatCurrencyLocale(totalAmount);
         document.getElementById('expenseCount').textContent = totalCount;
         document.getElementById('pendingAmount').textContent = formatCurrencyLocale(pendingAmount);
         document.getElementById('pendingCount').textContent = pendingCount;
         document.getElementById('approvedAmount').textContent = formatCurrencyLocale(approvedAmount);
         document.getElementById('approvedCount').textContent = approvedCount;
         document.getElementById('monthAmount').textContent = formatCurrencyLocale(monthAmount);
         document.getElementById('monthCount').textContent = monthCount;
      } catch (error) {
         console.error('Load stats error:', error);
      }
   }

   async function loadDropdowns() {
      try {
         const [categoriesRes, branchesRes, fiscalRes] = await Promise.all([
            api.get('expense-category/all?limit=100'),
            api.get('branch/all?limit=100'),
            api.get('fiscalyear/all?limit=10')
         ]);

         const categories = categoriesRes?.data?.data || categoriesRes?.data || [];
         const branches = branchesRes?.data?.data || branchesRes?.data || [];
         const fiscalYears = fiscalRes?.data?.data || fiscalRes?.data || [];

         const categorySelect = document.getElementById('categoryId');
         const filterCategorySelect = document.getElementById('filterCategory');
         categorySelect.innerHTML = '<option value="">Select Category</option>';
         filterCategorySelect.innerHTML = '<option value="">All Categories</option>';
         categories.forEach(c => {
            const opt1 = document.createElement('option');
            opt1.value = c.ExpenseCategoryID;
            opt1.textContent = c.CategoryName;
            categorySelect.appendChild(opt1);

            const opt2 = document.createElement('option');
            opt2.value = c.ExpenseCategoryID;
            opt2.textContent = c.CategoryName;
            filterCategorySelect.appendChild(opt2);
         });

         const branchSelect = document.getElementById('branchId');
         branchSelect.innerHTML = '<option value="">Select Branch</option>';
         branches.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.BranchID;
            opt.textContent = b.BranchName;
            branchSelect.appendChild(opt);
         });

         const fiscalSelect = document.getElementById('fiscalYear');
         fiscalSelect.innerHTML = '<option value="">Select Fiscal Year</option>';
         fiscalYears.forEach((fy, index) => {
            const opt = document.createElement('option');
            opt.value = fy.FiscalYearID;
            opt.textContent = fy.FiscalYearName || fy.FiscalYearID;
            if (index === 0 && fy.Status === 'Active') opt.selected = true;
            fiscalSelect.appendChild(opt);
         });
      } catch (error) {
         console.error('Load dropdowns error:', error);
      }
   }

   function initEventListeners() {
      document.getElementById('addExpenseBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('create_expense')) {
            Alerts.error('You do not have permission to request expenses');
            return;
         }
         const modal = new bootstrap.Modal(document.getElementById('expenseModal'));
         modal.show();
      });

      document.getElementById('saveExpenseBtn').addEventListener('click', saveExpense);
   }

   async function saveExpense() {
      const title = document.getElementById('title').value.trim();
      const amount = document.getElementById('amount').value;
      const expenseDate = document.getElementById('expenseDate').value;
      const categoryId = document.getElementById('categoryId').value;
      const branchId = document.getElementById('branchId').value;
      const fiscalYearId = document.getElementById('fiscalYear').value;

      if (!title || !amount || !expenseDate || !categoryId || !branchId || !fiscalYearId) {
         Alerts.warning('Please fill all required fields');
         return;
      }

      const payload = {
         title,
         amount: parseFloat(amount),
         expense_date: expenseDate,
         category_id: parseInt(categoryId),
         branch_id: parseInt(branchId),
         fiscal_year_id: parseInt(fiscalYearId),
         description: document.getElementById('description').value.trim() || null
      };

      try {
         Alerts.loading('Submitting expense request...');
         await api.post('expense/create', payload);
         Alerts.closeLoading();
         Alerts.success('Expense request submitted successfully');
         bootstrap.Modal.getInstance(document.getElementById('expenseModal')).hide();
         expensesGrid.setData();
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save expense error:', error);
         Alerts.handleApiError(error);
      }
   }

   function applyFilters() {
      const status = document.getElementById('filterStatus').value;
      const categoryId = document.getElementById('filterCategory').value;
      const startDate = document.getElementById('filterStartDate').value;
      const endDate = document.getElementById('filterEndDate').value;

      let url = `${Config.API_BASE_URL}/expense/all`;
      let params = [];

      if (status) params.push(`status=${encodeURIComponent(status)}`);
      if (categoryId) params.push(`category_id=${categoryId}`);
      if (startDate) params.push(`start_date=${startDate}`);
      if (endDate) params.push(`end_date=${endDate}`);

      if (params.length > 0) url += '?' + params.join('&');
      expensesGrid.setData(url);
   }

   function viewExpense(expenseId) {
      Alerts.info('View functionality coming soon');
   }

   function approveExpense(expenseId) {
      Alerts.info('Approval functionality coming soon');
   }
</script>

<?php require_once '../includes/footer.php'; ?>