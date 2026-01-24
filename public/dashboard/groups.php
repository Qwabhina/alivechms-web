<?php
$pageTitle = 'Groups Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">
            <i class="bi bi-people-fill me-2"></i>Groups
         </h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Groups</li>
            </ol>
         </nav>
      </div>
      <div>
         <button class="btn btn-outline-primary me-2" id="manageTypesBtn">
            <i class="bi bi-tags me-1"></i>Manage Types
         </button>
         <button class="btn btn-primary" id="addGroupBtn" data-permission="manage_groups">
            <i class="bi bi-plus-circle me-2"></i>Add Group
         </button>
      </div>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4" id="statsCards">
      <div class="col-12 text-center py-4">
         <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
         </div>
      </div>
   </div>

   <!-- Groups Table Card -->
   <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h5 class="mb-0">
                  <i class="bi bi-table me-2"></i>All Groups
                  <span class="badge bg-primary ms-2" id="totalGroupsCount">0</span>
               </h5>
            </div>
            <div class="col-md-6 text-end">
               <button class="btn btn-sm btn-outline-secondary" id="refreshGroupGrid" title="Refresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
               </button>
            </div>
         </div>
      </div>
      <div class="card-body">
         <div id="groupsTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Group Modal -->
<div class="modal fade" id="groupModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header bg-primary bg-opacity-10 border-0">
            <h5 class="modal-title" id="groupModalTitle">
               <i class="bi bi-people me-2"></i>Add New Group
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body px-4">
            <form id="groupForm">
               <input type="hidden" id="groupId">

               <div class="section-header mb-3">
                  <i class="bi bi-info-circle text-primary me-2"></i>
                  <span class="fw-semibold">Group Information</span>
               </div>

               <div class="row g-3 mb-4">
                  <div class="col-md-6">
                     <label class="form-label">Group Name <span class="text-danger">*</span></label>
                     <input type="text" class="form-control" id="groupName" placeholder="e.g., Youth Ministry" required>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Group Type <span class="text-danger">*</span></label>
                     <select class="form-select" id="groupType" required>
                        <option value="">Select Type</option>
                     </select>
                  </div>
                  <div class="col-md-12">
                     <label class="form-label">Group Leader <span class="text-danger">*</span></label>
                     <select class="form-select" id="groupLeader" required>
                        <option value="">Select Leader</option>
                     </select>
                     <div class="form-text">The leader will be automatically added as a member</div>
                  </div>
                  <div class="col-12">
                     <label class="form-label">Description</label>
                     <textarea class="form-control" id="groupDescription" rows="3" placeholder="Brief description of the group's purpose..."></textarea>
                  </div>
               </div>
            </form>
         </div>
         <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveGroupBtn">
               <i class="bi bi-check-circle me-1"></i>Save Group
            </button>
         </div>
      </div>
   </div>
</div>

<!-- View Group Modal -->
<div class="modal fade" id="viewGroupModal" tabindex="-1">
   <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
         <div class="modal-header border-0 p-0">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewGroupContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading group details...</p>
            </div>
         </div>
         <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-warning" id="manageMembersBtn">
               <i class="bi bi-people me-1"></i>Manage Members
            </button>
            <button type="button" class="btn btn-primary" id="editGroupFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit Group
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Manage Group Members Modal -->
<div class="modal fade" id="manageMembersModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="bi bi-people me-2"></i>Manage Group Members
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="mb-4">
               <label class="form-label fw-semibold">Add Member to Group</label>
               <div class="input-group">
                  <select class="form-select" id="addMemberSelect">
                     <option value="">Select a member to add...</option>
                  </select>
                  <button class="btn btn-primary" type="button" id="addMemberToGroupBtn">
                     <i class="bi bi-plus-circle me-1"></i>Add
                  </button>
               </div>
            </div>
            <h6 class="mb-3">Current Members</h6>
            <div id="groupMembersList">
               <p class="text-muted">Loading members...</p>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<!-- Manage Group Types Modal -->
<div class="modal fade" id="groupTypesModal" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="bi bi-tags me-2"></i>Manage Group Types
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="mb-4">
               <label class="form-label fw-semibold">Add New Type</label>
               <div class="input-group">
                  <input type="text" class="form-control" id="newTypeName" placeholder="e.g., Ministry, Fellowship">
                  <button class="btn btn-primary" type="button" id="addGroupTypeBtn">
                     <i class="bi bi-plus-circle me-1"></i>Add
                  </button>
               </div>
            </div>
            <h6 class="mb-3">Existing Types</h6>
            <div id="groupTypesList">
               <p class="text-muted">Loading types...</p>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<script src="../assets/js/core/qmgrid-helper.js"></script>
<script type="module" src="../assets/js/modules/groups/index.js"></script>

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

   .section-header {
      display: flex;
      align-items: center;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #e9ecef;
      color: #495057;
   }

   .group-profile .profile-header {
      border-radius: 0;
   }
</style>

<?php require_once '../includes/footer.php'; ?>