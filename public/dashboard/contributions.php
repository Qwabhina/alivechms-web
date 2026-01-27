<?php
$pageTitle = 'Contributions Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">
            <i class="bi bi-cash-coin me-2"></i>Contributions
         </h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Contributions</li>
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
         <button class="btn btn-outline-secondary" id="manageTypesBtn" data-permission="manage_contribution_types">
            <i class="bi bi-tags me-1"></i>Types
         </button>
         <button class="btn btn-primary" id="addContributionBtn" data-permission="create_contribution">
            <i class="bi bi-plus-circle me-2"></i>Record Contribution
         </button>
      </div>
   </div>

   <!-- Stats Cards Row 1 -->
   <div class="row mb-3" id="statsCardsRow1">
      <div class="col-12 text-center py-4">
         <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
         </div>
      </div>
   </div>

   <!-- Stats Cards Row 2 -->
   <div class="row mb-4" id="statsCardsRow2"></div>

   <!-- Charts and Top Contributors Row -->
   <div class="row mb-4">
      <!-- Contribution by Type Chart -->
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>By Type</h6>
            </div>
            <div class="card-body">
               <canvas id="byTypeChart" height="200"></canvas>
            </div>
         </div>
      </div>

      <!-- Monthly Trend Chart -->
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Monthly Trend</h6>
            </div>
            <div class="card-body">
               <canvas id="monthlyTrendChart" height="200"></canvas>
            </div>
         </div>
      </div>

      <!-- Top Contributors -->
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-trophy me-2"></i>Top Contributors</h6>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                     <thead class="table-light">
                        <tr>
                           <th style="width:40px"></th>
                           <th>Member</th>
                           <th class="text-end">Total</th>
                        </tr>
                     </thead>
                     <tbody id="topContributorsBody">
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
                     <label class="form-label small mb-1">Contribution Type</label>
                     <select class="form-select form-select-sm" id="filterType">
                        <option value="">All Types</option>
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
                     <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="showDeletedCheckbox">
                        <label class="form-check-label small" for="showDeletedCheckbox">
                           Show Deleted
                        </label>
                     </div>
                  </div>
                  <div class="col-md-2">
                     <button class="btn btn-primary btn-sm w-100" id="applyFiltersBtn">
                        <i class="bi bi-search me-1"></i>Filter
                     </button>
                  </div>
                  <div class="col-md-2">
                     <button class="btn btn-outline-secondary btn-sm w-100" id="clearFiltersBtn">
                        <i class="bi bi-x-circle me-1"></i>Clear
                     </button>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Contributions Table -->
   <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h5 class="mb-0">
                  <i class="bi bi-table me-2"></i>All Contributions
                  <span class="badge bg-primary ms-2" id="totalContributionsCount">0</span>
               </h5>
            </div>
            <div class="col-md-6 text-end">
               <button class="btn btn-sm btn-outline-secondary" id="refreshGrid" title="Refresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
               </button>
            </div>
         </div>
      </div>
      <div class="card-body">
         <div id="contributionsTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Contribution Modal -->
<div class="modal fade" id="contributionModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header bg-primary bg-opacity-10 border-0">
            <h5 class="modal-title" id="contributionModalTitle">
               <i class="bi bi-cash-coin me-2"></i>Record Contribution
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body px-4">
            <form id="contributionForm">
               <input type="hidden" id="contributionId">
               <div class="mb-3">
                  <label class="form-label">Member <span class="text-danger">*</span></label>
                  <select class="form-select" id="memberId" required>
                     <option value="">Select Member</option>
                  </select>
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
                     <input type="date" class="form-control" id="contributionDate" required>
                  </div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Contribution Type <span class="text-danger">*</span></label>
                     <select class="form-select" id="contributionType" required>
                        <option value="">Select Type</option>
                     </select>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                     <select class="form-select" id="paymentOption" required>
                        <option value="">Select Method</option>
                     </select>
                  </div>
               </div>
               <div class="mb-3">
                  <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                  <select class="form-select" id="fiscalYear" required>
                     <option value="">Select Fiscal Year</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" id="description" rows="2" placeholder="Optional notes..."></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveContributionBtn">
               <i class="bi bi-check-circle me-1"></i>Save Contribution
            </button>
         </div>
      </div>
   </div>
</div>

<!-- View Contribution Modal -->
<div class="modal fade" id="viewContributionModal" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header border-0 p-0">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewContributionContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading...</p>
            </div>
         </div>
         <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-success" id="printReceiptBtn">
               <i class="bi bi-printer me-1"></i>Print Receipt
            </button>
            <button type="button" class="btn btn-primary" id="editContributionFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Receipt Print Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Contribution Receipt</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="receiptContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="printReceipt()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Member Statement Modal -->
<div class="modal fade" id="statementModal" tabindex="-1">
   <div class="modal-dialog modal-xl">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-file-text me-2"></i>Contribution Statement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="statementContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="printStatement()">
               <i class="bi bi-printer me-1"></i>Print Statement
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Manage Contribution Types Modal -->
<div class="modal fade" id="typesModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Manage Contribution Types</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="row mb-3">
               <div class="col-md-5">
                  <input type="text" class="form-control" id="newTypeName" placeholder="Type name (e.g., Tithe, Offering)">
               </div>
               <div class="col-md-5">
                  <input type="text" class="form-control" id="newTypeDesc" placeholder="Description (optional)">
               </div>
               <div class="col-md-2">
                  <button class="btn btn-primary w-100" id="addTypeBtn"><i class="bi bi-plus"></i> Add</button>
               </div>
            </div>
            <div class="table-responsive">
               <table class="table table-hover" id="typesTable">
                  <thead class="table-light">
                     <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th width="100">Actions</th>
                     </tr>
                  </thead>
                  <tbody id="typesBody">
                     <tr>
                        <td colspan="3" class="text-center py-3">Loading...</td>
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

<!-- Edit Contribution Type Modal -->
<div class="modal fade" id="editTypeModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-sm">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Type</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <input type="hidden" id="editTypeId">
            <div class="mb-3">
               <label class="form-label">Name <span class="text-danger">*</span></label>
               <input type="text" class="form-control" id="editTypeName" required>
            </div>
            <div class="mb-3">
               <label class="form-label">Description</label>
               <input type="text" class="form-control" id="editTypeDesc">
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveTypeBtn">Save</button>
         </div>
      </div>
   </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/core/qmgrid-helper.js"></script>
<script type="module" src="../assets/js/modules/contributions/index.js"></script>

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

   .contribution-view {
      border-radius: 0;
   }

   .cursor-pointer {
      cursor: pointer;
   }

   .cursor-pointer:hover {
      background-color: rgba(0, 0, 0, 0.02);
   }

   .receipt-container,
   .statement-container {
      background: white;
   }

   @media print {

      .modal-footer,
      .modal-header .btn-close {
         display: none;
      }
   }
</style>

<?php require_once '../includes/footer.php'; ?>
