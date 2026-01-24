<?php
$pageTitle = 'Families Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">
            <i class="bi bi-house-heart-fill me-2"></i>Families
         </h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Families</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addFamilyBtn" data-permission="manage_families">
         <i class="bi bi-plus-circle me-2"></i>Add Family
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4" id="statsCards">
      <div class="col-12 text-center py-4">
         <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
         </div>
      </div>
   </div>

   <!-- Families Table Card -->
   <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h5 class="mb-0">
                  <i class="bi bi-table me-2"></i>All Families
                  <span class="badge bg-primary ms-2" id="totalFamiliesCount">0</span>
               </h5>
            </div>
            <div class="col-md-6 text-end">
               <button class="btn btn-sm btn-outline-secondary" id="refreshFamilyGrid" title="Refresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
               </button>
            </div>
         </div>
      </div>
      <div class="card-body">
         <div id="familiesTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Family Modal -->
<div class="modal fade" id="familyModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header bg-primary bg-opacity-10 border-0">
            <h5 class="modal-title" id="familyModalTitle">
               <i class="bi bi-house-heart me-2"></i>Add New Family
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body px-4">
            <form id="familyForm">
               <input type="hidden" id="familyId">
               <div class="mb-3">
                  <label class="form-label">Family Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="familyName" placeholder="e.g., The Johnsons" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Head of Household</label>
                  <select class="form-select" id="headOfHousehold">
                     <option value="">Select Member (Optional)</option>
                  </select>
                  <div class="form-text">The primary contact for this family</div>
               </div>
            </form>
         </div>
         <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveFamilyBtn">
               <i class="bi bi-check-circle me-1"></i>Save Family
            </button>
         </div>
      </div>
   </div>
</div>

<!-- View Family Modal -->
<div class="modal fade" id="viewFamilyModal" tabindex="-1">
   <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
         <div class="modal-header border-0 p-0">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewFamilyContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading family details...</p>
            </div>
         </div>
         <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-warning" id="manageMembersBtn">
               <i class="bi bi-people me-1"></i>Manage Members
            </button>
            <button type="button" class="btn btn-primary" id="editFamilyFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit Family
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Manage Family Members Modal -->
<div class="modal fade" id="manageMembersModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-people me-2"></i>Manage Family Members</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="mb-4">
               <label class="form-label fw-semibold">Add Member to Family</label>
               <div class="input-group">
                  <select class="form-select" id="addMemberSelect">
                     <option value="">Select a member to add...</option>
                  </select>
                  <button class="btn btn-primary" type="button" id="addMemberToFamilyBtn">
                     <i class="bi bi-plus-circle me-1"></i>Add
                  </button>
               </div>
            </div>
            <h6 class="mb-3">Current Members</h6>
            <div id="familyMembersList">
               <p class="text-muted">Loading members...</p>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<script src="../assets/js/core/qmgrid-helper.js"></script>
<script type="module" src="../assets/js/modules/families/index.js"></script>

<style>
   .stat-card {
      border: none;
      transition: transform 0.2s;
   }

   .stat-card:hover {
      transform: translateY(-3px);
   }

   .stat-icon {
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
   }

   .family-profile .profile-header {
      border-radius: 0;
   }
</style>

<?php require_once '../includes/footer.php'; ?>