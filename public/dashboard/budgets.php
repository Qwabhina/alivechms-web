<?php
$pageTitle = 'Budgets Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Budgets</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Budgets</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addBudgetBtn" data-permission="create_budget">
         <i class="bi bi-plus-circle me-2"></i>Create Budget
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Total Budgets</p>
                     <h3 class="mb-0" id="totalBudgets">-</h3>
                     <small class="text-muted"><span id="budgetCount">0</span> budgets</small>
                  </div>
                  <div class="stat-icon bg-primary text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-wallet2"></i>
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
                     <p class="text-muted mb-1">Draft</p>
                     <h3 class="mb-0" id="draftAmount">-</h3>
                     <small class="text-muted"><span id="draftCount">0</span> budgets</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-pencil-square"></i>
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
                     <small class="text-muted"><span id="approvedCount">0</span> budgets</small>
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
                     <p class="text-muted mb-1">Submitted</p>
                     <h3 class="mb-0" id="submittedAmount">-</h3>
                     <small class="text-muted"><span id="submittedCount">0</span> budgets</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-send"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Budgets Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>All Budgets</h5>
      </div>
      <div class="card-body">
         <table id="budgetsTable" class="table table-striped table-hover" style="width:100%">
            <thead>
               <tr>
                  <th>Title</th>
                  <th>Fiscal Year</th>
                  <th>Branch</th>
                  <th>Total Amount</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th class="no-export">Actions</th>
               </tr>
            </thead>
            <tbody></tbody>
         </table>
      </div>
   </div>
</div>
</main>

<!-- Budget Modal -->
<div class="modal fade" id="budgetModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Create Budget</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="budgetForm">
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Title <span class="text-danger">*</span></label>
                     <input type="text" class="form-control" id="title" required maxlength="150">
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                     <select class="form-select" id="fiscalYear" required>
                        <option value="">Select Fiscal Year</option>
                     </select>
                  </div>
               </div>
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Branch <span class="text-danger">*</span></label>
                     <select class="form-select" id="branchId" required>
                        <option value="">Select Branch</option>
                     </select>
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Description</label>
                     <input type="text" class="form-control" id="description" maxlength="500">
                  </div>
               </div>

               <hr class="my-4">
               <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="mb-0">Budget Items</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="addBudgetItem()">
                     <i class="bi bi-plus-circle me-1"></i>Add Item
                  </button>
               </div>

               <div id="budgetItemsContainer">
                  <!-- Budget items will be added here -->
               </div>

               <div class="mt-3 p-3 bg-light rounded">
                  <div class="d-flex justify-content-between align-items-center">
                     <strong>Total Budget:</strong>
                     <h4 class="mb-0 text-primary" id="totalBudgetAmount">-</h4>
                  </div>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveBudgetBtn">
               <i class="bi bi-check-circle me-1"></i>Create Budget
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let budgetsTable = null;
   let budgetItemCounter = 0;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;

      // Wait for settings to load
      await Config.waitForSettings();

      await initPage();
   });

   async function initPage() {
      initTable();
      initEventListeners();
      await loadDropdowns();
      loadStats();
   }

   function initTable() {
      budgetsTable = QMGridHelper.initWithButtons('#budgetsTable', {
         ajax: {
            url: `${Config.API_BASE_URL}/budget/all`,
            type: 'GET',
            data: function(d) {
               return {
                  page: Math.floor(d.start / d.length) + 1,
                  limit: d.length,
                  search: d.search.value || ''
               };
            },
            dataFilter: function(data) {
               return QMGridHelper.processServerResponse(data, function(b) {
                  return {
                     title: b.BudgetTitle,
                     fiscal_year: b.YearName,
                     branch: b.BranchName,
                     total: parseFloat(b.TotalAmount),
                     status: b.BudgetStatus,
                     created: b.CreatedAt,
                     id: b.BudgetID
                  };
               });
            }
         },
         columns: [{
               data: 'title',
               title: 'Title'
            },
            {
               data: 'fiscal_year',
               title: 'Fiscal Year'
            },
            {
               data: 'branch',
               title: 'Branch'
            },
            {
               data: 'total',
               title: 'Total Amount',
               render: function(data) {
                  return formatCurrency(parseFloat(data));
               }
            },
            {
               data: 'status',
               title: 'Status',
               render: function(data) {
                  const badges = {
                     'Draft': 'warning',
                     'Submitted': 'info',
                     'Approved': 'success',
                     'Rejected': 'danger'
                  };
                  return `<span class="badge bg-${badges[data] || 'secondary'}">${data}</span>`;
               }
            },
            {
               data: 'created',
               title: 'Created',
               render: function(data) {
                  return QMGridHelper.formatDate(data);
               }
            },
            {
               data: 'id',
               title: 'Actions',
               orderable: false,
               searchable: false,
               className: 'no-export',
               render: function(data) {
                  return `<button class="btn btn-sm btn-outline-primary" onclick="viewBudget(${data})" title="View">
                     <i class="bi bi-eye"></i>
                  </button>`;
               }
            }
         ],
         order: [
            [5, 'desc']
         ]
      });
   }

   async function loadStats() {
      try {
         const response = await api.get('budget/all?limit=1000');
         const budgets = response?.data?.data || response?.data || [];

         let totalAmount = 0,
            totalCount = 0;
         let draftAmount = 0,
            draftCount = 0;
         let approvedAmount = 0,
            approvedCount = 0;
         let submittedAmount = 0,
            submittedCount = 0;

         budgets.forEach(b => {
            const amount = parseFloat(b.TotalAmount);
            totalAmount += amount;
            totalCount++;

            if (b.BudgetStatus === 'Draft') {
               draftAmount += amount;
               draftCount++;
            } else if (b.BudgetStatus === 'Approved') {
               approvedAmount += amount;
               approvedCount++;
            } else if (b.BudgetStatus === 'Submitted') {
               submittedAmount += amount;
               submittedCount++;
            }
         });

         document.getElementById('totalBudgets').textContent = formatCurrencyLocale(totalAmount);
         document.getElementById('budgetCount').textContent = totalCount;
         document.getElementById('draftAmount').textContent = formatCurrencyLocale(draftAmount);
         document.getElementById('draftCount').textContent = draftCount;
         document.getElementById('approvedAmount').textContent = formatCurrencyLocale(approvedAmount);
         document.getElementById('approvedCount').textContent = approvedCount;
         document.getElementById('submittedAmount').textContent = formatCurrencyLocale(submittedAmount);
         document.getElementById('submittedCount').textContent = submittedCount;
      } catch (error) {
         console.error('Load stats error:', error);
      }
   }

   async function loadDropdowns() {
      try {
         const [branchesRes, fiscalRes] = await Promise.all([
            api.get('branch/all?limit=100'),
            api.get('fiscalyear/all?limit=10')
         ]);

         const branches = branchesRes?.data?.data || branchesRes?.data || [];
         const fiscalYears = fiscalRes?.data?.data || fiscalRes?.data || [];

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
      document.getElementById('addBudgetBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('create_budget')) {
            Alerts.error('You do not have permission to create budgets');
            return;
         }
         openBudgetModal();
      });

      document.getElementById('saveBudgetBtn').addEventListener('click', saveBudget);
   }

   function openBudgetModal() {
      document.getElementById('budgetForm').reset();
      document.getElementById('budgetItemsContainer').innerHTML = '';
      budgetItemCounter = 0;
      addBudgetItem();
      const modal = new bootstrap.Modal(document.getElementById('budgetModal'));
      modal.show();
   }

   function addBudgetItem() {
      budgetItemCounter++;
      const container = document.getElementById('budgetItemsContainer');
      const itemHtml = `
         <div class="card mb-2 budget-item" data-item-id="${budgetItemCounter}">
            <div class="card-body p-3">
               <div class="row g-2">
                  <div class="col-md-4">
                     <input type="text" class="form-control form-control-sm" placeholder="Category *" data-field="category" required>
                  </div>
                  <div class="col-md-4">
                     <input type="text" class="form-control form-control-sm" placeholder="Description" data-field="description">
                  </div>
                  <div class="col-md-3">
                     <input type="number" class="form-control form-control-sm" placeholder="Amount *" data-field="amount" step="0.01" min="0.01" required onchange="calculateTotal()">
                  </div>
                  <div class="col-md-1">
                     <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBudgetItem(${budgetItemCounter})">
                        <i class="bi bi-trash"></i>
                     </button>
                  </div>
               </div>
            </div>
         </div>
      `;
      container.insertAdjacentHTML('beforeend', itemHtml);
   }

   function removeBudgetItem(itemId) {
      const item = document.querySelector(`[data-item-id="${itemId}"]`);
      if (item) {
         item.remove();
         calculateTotal();
      }
   }

   function calculateTotal() {
      let total = 0;
      document.querySelectorAll('.budget-item [data-field="amount"]').forEach(input => {
         const value = parseFloat(input.value) || 0;
         total += value;
      });
      document.getElementById('totalBudgetAmount').textContent = formatCurrencyLocale(total);
   }

   async function saveBudget() {
      const title = document.getElementById('title').value.trim();
      const fiscalYearId = document.getElementById('fiscalYear').value;
      const branchId = document.getElementById('branchId').value;
      const description = document.getElementById('description').value.trim();

      if (!title || !fiscalYearId || !branchId) {
         Alerts.warning('Please fill all required fields');
         return;
      }

      const items = [];
      document.querySelectorAll('.budget-item').forEach(item => {
         const category = item.querySelector('[data-field="category"]').value.trim();
         const desc = item.querySelector('[data-field="description"]').value.trim();
         const amount = parseFloat(item.querySelector('[data-field="amount"]').value);

         if (category && amount > 0) {
            items.push({
               category,
               description: desc || null,
               amount
            });
         }
      });

      if (items.length === 0) {
         Alerts.warning('Please add at least one budget item');
         return;
      }

      const payload = {
         title,
         fiscal_year_id: parseInt(fiscalYearId),
         branch_id: parseInt(branchId),
         description: description || null,
         items
      };

      try {
         Alerts.loading('Creating budget...');
         await api.post('budget/create', payload);
         Alerts.closeLoading();
         Alerts.success('Budget created successfully');
         bootstrap.Modal.getInstance(document.getElementById('budgetModal')).hide();
         QMGridHelper.reload(budgetsTable);
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save budget error:', error);
         Alerts.handleApiError(error);
      }
   }

   function viewBudget(budgetId) {
      Alerts.info('View functionality coming soon');
   }
</script>

<?php require_once '../includes/footer.php'; ?>