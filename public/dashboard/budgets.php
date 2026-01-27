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

<script src="../assets/js/core/qmgrid-helper.js"></script>
<script type="module" src="../assets/js/modules/budgets/index.js"></script>
<?php require_once '../includes/footer.php'; ?>
