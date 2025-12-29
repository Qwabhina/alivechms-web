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
   <div class="card">
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
                     <th style="width: 90px;">Actions</th>
                  </tr>
               </thead>
               <tbody id="rolesTableBody">
                  <tr>
                     <td colspan="3" class="text-center py-4">
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

<script>
   let roles = [];

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await initPage();
   });

   async function initPage() {
      initEventListeners();
      await loadRoles();
      loadStats();
   }

   function initEventListeners() {
      document.getElementById('addRoleBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('manage_volunteer_roles')) {
            Alerts.error('You do not have permission to create volunteer roles');
            return;
         }
         openRoleModal();
      });

      document.getElementById('saveRoleBtn').addEventListener('click', saveRole);
   }

   async function loadRoles() {
      try {
         const response = await api.get('volunteer/role/all');
         roles = response?.data || [];
         
         document.getElementById('totalRoles').textContent = roles.length;
         
         const tbody = document.getElementById('rolesTableBody');
         if (roles.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No volunteer roles yet</td></tr>';
            return;
         }

         tbody.innerHTML = roles.map(role => `
            <tr>
               <td class="fw-semibold">${role.RoleName}</td>
               <td>${role.Description || '-'}</td>
               <td>
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-outline-primary" onclick="viewRoleDetails(${role.VolunteerRoleID})" title="View">
                        <i class="bi bi-eye"></i>
                     </button>
                  </div>
               </td>
            </tr>
         `).join('');
      } catch (error) {
         console.error('Load roles error:', error);
         document.getElementById('rolesTableBody').innerHTML = 
            '<tr><td colspan="3" class="text-center text-danger py-4">Failed to load roles</td></tr>';
      }
   }

   async function loadStats() {
      try {
         // These would come from actual API endpoints in production
         document.getElementById('activeVolunteers').textContent = '0';
         document.getElementById('upcomingEvents').textContent = '0';
         document.getElementById('monthlyVolunteers').textContent = '0';
      } catch (error) {
         console.error('Load stats error:', error);
      }
   }

   function openRoleModal() {
      document.getElementById('roleForm').reset();
      const modal = new bootstrap.Modal(document.getElementById('roleModal'));
      modal.show();
   }

   async function saveRole() {
      const roleName = document.getElementById('roleName').value.trim();
      
      if (!roleName) {
         Alerts.warning('Role name is required');
         return;
      }

      const payload = {
         name: roleName,
         description: document.getElementById('roleDescription').value.trim() || null
      };

      try {
         Alerts.loading('Saving role...');
         await api.post('volunteer/role/create', payload);
         Alerts.closeLoading();
         Alerts.success('Volunteer role created successfully');
         bootstrap.Modal.getInstance(document.getElementById('roleModal')).hide();
         await loadRoles();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save role error:', error);
         Alerts.handleApiError(error);
      }
   }

   async function viewRoleDetails(roleId) {
      const role = roles.find(r => r.VolunteerRoleID === roleId);
      if (!role) return;

      await Alerts.info({
         title: role.RoleName,
         html: `
            <div class="text-start">
               <p class="mb-2"><strong>Description:</strong></p>
               <p>${role.Description || 'No description provided'}</p>
            </div>
         `,
         confirmButtonText: 'Close'
      });
   }
</script>

<?php require_once '../includes/footer.php'; ?>
