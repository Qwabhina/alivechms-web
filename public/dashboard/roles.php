<?php
$pageTitle = 'Roles & Permissions';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Roles & Permissions</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Roles & Permissions</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addRoleBtn" data-permission="manage_roles">
         <i class="bi bi-plus-circle me-2"></i>Create Role
      </button>
   </div>

   <!-- Roles Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>All Roles</h5>
      </div>
      <div class="card-body">
         <div class="table-responsive">
            <div id="rolesGrid"></div>
         </div>
      </div>
   </div>
</div>
</main>

<!-- Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="roleModalTitle">Create Role</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="roleForm">
               <input type="hidden" id="roleId">
               <div class="mb-3">
                  <label class="form-label">Role Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="roleName" required maxlength="100">
               </div>
               <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" id="description" rows="2" maxlength="500"></textarea>
               </div>

               <hr class="my-4">
               <h6 class="mb-3">Permissions</h6>
               <div id="permissionsContainer" class="row g-3">
                  <!-- Permissions will be loaded here -->
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

<!-- View Role Modal -->
<div class="modal fade" id="viewRoleModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Role Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="viewRoleContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="editRoleFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let rolesGrid = null;
   let currentRoleId = null;
   let isEditMode = false;
   let allPermissions = [];

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await initPage();
   });

   async function initPage() {
      initGrid();
      initEventListeners();
      await loadPermissions();
   }

   function initGrid() {
      rolesGrid = new Tabulator("#rolesGrid", {
         layout: "fitColumns",
         responsiveLayout: "collapse",
         resizableColumns: false,
         ajaxURL: `${Config.API_BASE_URL}/role/all`,
         ajaxConfig: {
            headers: {
               'Authorization': `Bearer ${Auth.getToken()}`
            }
         },
         ajaxResponse: function(url, params, response) {
            const data = response?.data || [];
            return data.map(r => ({
               name: r.RoleName,
               permissions_count: r.permissions?.length || 0,
               permissions: r.permissions || [],
               id: r.RoleID
            }));
         },
         columns: [{
               title: "Role Name",
               field: "name",
               widthGrow: 2,
               responsive: 0
            },
            {
               title: "Permissions",
               field: "permissions_count",
               widthGrow: 1.5,
               responsive: 1,
               formatter: cell => {
                  const count = cell.getValue();
                  return `<span class="badge bg-primary">${count} permission${count !== 1 ? 's' : ''}</span>`;
               }
            },
            {
               title: "Actions",
               field: "id",
               width: 120,
               headerSort: false,
               responsive: 0,
               formatter: cell => {
                  const id = cell.getValue();
                  return `
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewRole(${id})" title="View">
                           <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning" onclick="editRole(${id})" title="Edit">
                           <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteRole(${id})" title="Delete">
                           <i class="bi bi-trash"></i>
                        </button>
                     </div>
                  `;
               }
            }
         ]
      });
   }

   async function loadPermissions() {
      try {
         const response = await api.get('permission/all?limit=1000');
         allPermissions = response?.data?.data || response?.data || [];
      } catch (error) {
         console.error('Load permissions error:', error);
      }
   }

   function renderPermissions(selectedPermissions = []) {
      const container = document.getElementById('permissionsContainer');
      container.innerHTML = '';

      if (allPermissions.length === 0) {
         container.innerHTML = '<div class="col-12 text-muted">No permissions available</div>';
         return;
      }

      allPermissions.forEach(perm => {
         const isChecked = selectedPermissions.some(sp => sp.permission_id === perm.PermissionID);
         const permHtml = `
            <div class="col-md-6">
               <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="${perm.PermissionID}" 
                     id="perm_${perm.PermissionID}" ${isChecked ? 'checked' : ''}>
                  <label class="form-check-label" for="perm_${perm.PermissionID}">
                     ${perm.PermissionName}
                  </label>
               </div>
            </div>
         `;
         container.insertAdjacentHTML('beforeend', permHtml);
      });
   }

   function initEventListeners() {
      document.getElementById('addRoleBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('manage_roles')) {
            Alerts.error('You do not have permission to create roles');
            return;
         }
         openRoleModal();
      });

      document.getElementById('saveRoleBtn').addEventListener('click', saveRole);

      document.getElementById('editRoleFromViewBtn').addEventListener('click', () => {
         bootstrap.Modal.getInstance(document.getElementById('viewRoleModal')).hide();
         editRole(currentRoleId);
      });
   }

   function openRoleModal(roleId = null) {
      isEditMode = !!roleId;
      currentRoleId = roleId;

      document.getElementById('roleForm').reset();
      document.getElementById('roleId').value = '';
      document.getElementById('roleModalTitle').textContent = isEditMode ? 'Edit Role' : 'Create Role';

      renderPermissions();

      const modal = new bootstrap.Modal(document.getElementById('roleModal'));
      modal.show();

      if (isEditMode) loadRoleForEdit(roleId);
   }

   async function loadRoleForEdit(roleId) {
      try {
         const role = await api.get(`role/view/${roleId}`);
         document.getElementById('roleId').value = role.RoleID;
         document.getElementById('roleName').value = role.RoleName;
         document.getElementById('description').value = role.Description || '';
         renderPermissions(role.permissions || []);
      } catch (error) {
         console.error('Load role error:', error);
         Alerts.error('Failed to load role data');
      }
   }

   async function saveRole() {
      const roleName = document.getElementById('roleName').value.trim();

      if (!roleName) {
         Alerts.warning('Please enter a role name');
         return;
      }

      const selectedPermissions = Array.from(
         document.querySelectorAll('#permissionsContainer input[type="checkbox"]:checked')
      ).map(cb => parseInt(cb.value));

      const payload = {
         name: roleName,
         description: document.getElementById('description').value.trim() || null
      };

      try {
         Alerts.loading('Saving role...');

         let roleId;
         if (isEditMode) {
            await api.put(`role/update/${currentRoleId}`, payload);
            roleId = currentRoleId;
         } else {
            const result = await api.post('role/create', payload);
            roleId = result.role_id;
         }

         // Assign permissions
         if (selectedPermissions.length > 0) {
            await api.post(`role/assign-permissions/${roleId}`, {
               permission_ids: selectedPermissions
            });
         }

         Alerts.closeLoading();
         Alerts.success(isEditMode ? 'Role updated successfully' : 'Role created successfully');
         bootstrap.Modal.getInstance(document.getElementById('roleModal')).hide();
         rolesGrid.setData();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save role error:', error);
         Alerts.handleApiError(error);
      }
   }

   async function viewRole(roleId) {
      currentRoleId = roleId;
      const modal = new bootstrap.Modal(document.getElementById('viewRoleModal'));
      modal.show();

      try {
         const role = await api.get(`role/view/${roleId}`);

         document.getElementById('viewRoleContent').innerHTML = `
            <div class="mb-4">
               <h4>${role.RoleName}</h4>
               ${role.Description ? `<p class="text-muted">${role.Description}</p>` : ''}
            </div>
            <div>
               <h6 class="mb-3">Permissions (${role.permissions?.length || 0})</h6>
               ${role.permissions && role.permissions.length > 0 ? `
                  <div class="row g-2">
                     ${role.permissions.map(p => `
                        <div class="col-md-6">
                           <div class="d-flex align-items-center">
                              <i class="bi bi-check-circle text-success me-2"></i>
                              <span>${p.permission_name}</span>
                           </div>
                        </div>
                     `).join('')}
                  </div>
               ` : '<p class="text-muted">No permissions assigned</p>'}
            </div>
         `;
      } catch (error) {
         console.error('View role error:', error);
         document.getElementById('viewRoleContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load role details</p>
            </div>
         `;
      }
   }

   function editRole(roleId) {
      if (!Auth.hasPermission('manage_roles')) {
         Alerts.error('You do not have permission to edit roles');
         return;
      }
      openRoleModal(roleId);
   }

   async function deleteRole(roleId) {
      if (!Auth.hasPermission('manage_roles')) {
         Alerts.error('You do not have permission to delete roles');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Role',
         text: 'Are you sure you want to delete this role? This action cannot be undone.',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting role...');
         await api.delete(`role/delete/${roleId}`);
         Alerts.closeLoading();
         Alerts.success('Role deleted successfully');
         rolesGrid.setData();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete role error:', error);
         Alerts.handleApiError(error);
      }
   }
</script>

<?php require_once '../includes/footer.php'; ?>