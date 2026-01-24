<?php
$pageTitle = 'Volunteers Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Volunteers</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Volunteers</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addRoleBtn" data-permission="manage_volunteer_roles">
         <i class="bi bi-plus-circle me-2"></i>Add Role
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Volunteer Roles</p>
                     <h3 class="mb-0" id="totalRoles">0</h3>
                     <small class="text-muted">Service opportunities</small>
                  </div>
                  <div class="stat-icon bg-primary text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-award"></i>
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
                     <p class="text-muted mb-1">Active Volunteers</p>
                     <h3 class="mb-0" id="activeVolunteers">0</h3>
                     <small class="text-muted">Currently serving</small>
                  </div>
                  <div class="stat-icon bg-success text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-person-check"></i>
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
                     <p class="text-muted mb-1">Upcoming Events</p>
                     <h3 class="mb-0" id="upcomingEvents">0</h3>
                     <small class="text-muted">Need volunteers</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-calendar-event"></i>
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
                     <p class="text-muted mb-1">This Month</p>
                     <h3 class="mb-0" id="monthlyVolunteers">0</h3>
                     <small class="text-muted">Volunteer hours</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-clock-history"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Volunteer Roles Table -->
   <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
         <h5 class="mb-0"><i class="bi bi-award me-2"></i>Volunteer Roles</h5>
         <button class="btn btn-sm btn-outline-primary" onclick="loadRoles()">
            <i class="bi bi-arrow-clockwise"></i>
         </button>
      </div>
      <div class="card-body">
         <div class="table-responsive">
            <table class="table table-hover" id="rolesTable">
               <thead class="table-light">
                  <tr>
                     <th>Role Name</th>
                     <th>Description</th>
                     <th>Members</th>
                     <th style="width: 120px;">Actions</th>
                  </tr>
               </thead>
               <tbody id="rolesTableBody">
                  <tr>
                     <td colspan="4" class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                     </td>
                  </tr>
               </tbody>
            </table>
         </div>
      </div>
   </div>
</div>
</main>

<!-- Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Add Volunteer Role</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="roleForm">
               <div class="mb-3">
                  <label class="form-label">Role Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="roleName" required placeholder="e.g., Usher, Sound Tech">
               </div>
               <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" id="roleDescription" rows="3" placeholder="Brief description of this role"></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveRoleBtn">
               <i class="bi bi-check-circle me-1"></i>Save Role
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Manage Role Members Modal -->
<div class="modal fade" id="manageMembersModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">
               <i class="bi bi-people me-2"></i>Manage Volunteers - <span id="roleNameTitle"></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="mb-4">
               <label class="form-label fw-semibold">Assign Member to Role</label>
               <div class="row g-2">
                  <div class="col-md-8">
                     <select class="form-select" id="assignMemberSelect">
                        <option value="">Select a member...</option>
                     </select>
                  </div>
                  <div class="col-md-4">
                     <button class="btn btn-primary w-100" type="button" id="assignMemberBtn">
                        <i class="bi bi-plus-circle me-1"></i>Assign
                     </button>
                  </div>
               </div>
            </div>
            <h6 class="mb-3">Current Volunteers</h6>
            <div id="roleMembersList">
               <p class="text-muted">Loading members...</p>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<script type="module" src="../assets/js/modules/volunteers/index.js"></script>

<?php require_once '../includes/footer.php'; ?>