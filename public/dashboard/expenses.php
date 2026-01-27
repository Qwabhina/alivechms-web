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
<script type="module" src="../assets/js/modules/expenses/index.js"></script>

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