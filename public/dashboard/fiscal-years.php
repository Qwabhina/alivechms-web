<?php
$pageTitle = 'Fiscal Years';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Fiscal Years</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Fiscal Years</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addFiscalYearBtn" data-permission="manage_fiscal_years">
         <i class="bi bi-plus-circle me-2"></i>Create Fiscal Year
      </button>
   </div>

   <!-- Fiscal Years Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-calendar-range me-2"></i>All Fiscal Years</h5>
      </div>
      <div class="card-body">
         <div id="fiscalYearsTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Fiscal Year Modal -->
<div class="modal fade" id="fiscalYearModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="fiscalYearModalTitle">Create Fiscal Year</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="fiscalYearForm">
               <input type="hidden" id="fiscalYearId">
               <div class="mb-3">
                  <label class="form-label">Start Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="startDate" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">End Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="endDate" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Branch <span class="text-danger">*</span></label>
                  <select class="form-select" id="branchId" required>
                     <option value="">Select Branch</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Status <span class="text-danger">*</span></label>
                  <select class="form-select" id="status" required>
                     <option value="Active">Active</option>
                     <option value="Closed">Closed</option>
                  </select>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveFiscalYearBtn">
               <i class="bi bi-check-circle me-1"></i>Save Fiscal Year
            </button>
         </div>
      </div>
   </div>
</div>

<script type="module" src="../assets/js/modules/fiscal-years/index.js"></script>

<?php require_once '../includes/footer.php'; ?>