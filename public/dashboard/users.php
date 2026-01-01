<?php
$pageTitle = 'Users Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Users Management</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Users</li>
            </ol>
         </nav>
      </div>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Total Users</p>
                     <h3 class="mb-0" id="totalUsers">0</h3>
                     <small class="text-muted">With login access</small>
                  </div>
                  <div class="stat-icon bg-primary text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-people-fill"></i>
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
                     <p class="text-muted mb-1">Active Users</p>
                     <h3 class="mb-0" id="activeUsers">0</h3>
                     <small class="text-muted">Active status</small>
                  </div>
                  <div class="stat-icon bg-success text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-check-circle"></i>
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
                     <p class="text-muted mb-1">Administrators</p>
                     <h3 class="mb-0" id="adminUsers">0</h3>
                     <small class="text-muted">Admin role</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-shield-check"></i>
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
                     <p class="text-muted mb-1">Roles</p>
                     <h3 class="mb-0" id="totalRoles">0</h3>
                     <small class="text-muted">Defined roles</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-diagram-3"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Users Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>All Users</h5>
      </div>
      <div class="card-body">
         <table id="usersTable" class="table table-striped table-hover" style="width:100%">
            <thead>
               <tr>
                  <th>Name</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th class="no-export">Actions</th>
               </tr>
            </thead>
            <tbody></tbody>
         </table>
      </div>
   </div>
</div>
</main>

<!-- Assign Role Modal -->
<div class="modal fade" id="assignRoleModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Assign Role</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="assignRoleForm">
               <input type="hidden" id="userId">
               <div class="mb-3">
                  <label class="form-label">User</label>
                  <input type="text" class="form-control" id="userName" readonly>
               </div>
               <div class="mb-3">
                  <label class="form-label">Role <span class="text-danger">*</span></label>
                  <select class="form-select" id="roleId" required>
                     <option value="">Select Role</option>
                  </select>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveRoleBtn">
               <i class="bi bi-check-circle me-1"></i>Assign Role
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let usersTable = null;
   let currentUserId = null;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;

      // Wait for settings to load
      await Config.waitForSettings();

      await initPage();
   });

   async function initPage() {
      initTable();
      initEventListeners();
      await loadRoles();
      loadStats();
   }

   function initTable() {
      usersTable = QMGridHelper.initWithButtons('#usersTable', {
         ajax: {
            url: `${Config.API_BASE_URL}/member/all`,
            type: 'GET',
            data: function(d) {
               return {
                  page: Math.floor(d.start / d.length) + 1,
                  limit: d.length,
                  search: d.search.value || ''
               };
            },
            dataFilter: function(data) {
               return QMGridHelper.processServerResponse(data, function(m) {
                  // Filter only members with Username (users with login access)
                  if (!m.Username) return null;

                  return {
                     name: `${m.MbrFirstName} ${m.MbrFamilyName}`,
                     username: m.Username,
                     email: m.MbrEmailAddress || 'N/A',
                     role: m.RoleName || 'No Role',
                     status: m.MbrMembershipStatus,
                     id: m.MbrID
                  };
               });
            }
         },
         columns: [{
               data: 'name',
               title: 'Name'
            },
            {
               data: 'username',
               title: 'Username'
            },
            {
               data: 'email',
               title: 'Email'
            },
            {
               data: 'role',
               title: 'Role'
            },
            {
               data: 'status',
               title: 'Status',
               render: function(data) {
                  const badge = data === 'Active' ? 'success' : 'secondary';
                  return `<span class="badge bg-${badge}">${data}</span>`;
               }
            },
            {
               data: 'id',
               title: 'Actions',
               orderable: false,
               searchable: false,
               className: 'no-export',
               render: function(data, type, row) {
                  return `
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick='assignRole(${data}, "${row.name}")' title="Assign Role">
                           <i class="bi bi-shield-check"></i>
                        </button>
                        <button class="btn btn-outline-warning" onclick="viewUser(${data})" title="View">
                           <i class="bi bi-eye"></i>
                        </button>
                     </div>
                  `;
               }
            }
         ],
         order: [
            [0, 'asc']
         ]
      });
   }

   async function loadStats() {
      try {
         const [membersRes, rolesRes] = await Promise.all([
            api.get('member/all?limit=1000'),
            api.get('role/all')
         ]);

         const members = membersRes?.data?.data || membersRes?.data || [];
         const roles = rolesRes?.data || [];

         const users = members.filter(m => m.Username);
         const activeUsers = users.filter(u => u.MbrMembershipStatus === 'Active');
         const adminUsers = users.filter(u => u.RoleName && u.RoleName.toLowerCase().includes('admin'));

         document.getElementById('totalUsers').textContent = users.length;
         document.getElementById('activeUsers').textContent = activeUsers.length;
         document.getElementById('adminUsers').textContent = adminUsers.length;
         document.getElementById('totalRoles').textContent = roles.length;
      } catch (error) {
         console.error('Load stats error:', error);
      }
   }

   async function loadRoles() {
      try {
         const response = await api.get('role/all');
         const roles = response?.data || [];

         const roleSelect = document.getElementById('roleId');
         roleSelect.innerHTML = '<option value="">Select Role</option>';
         roles.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.RoleID;
            opt.textContent = r.RoleName;
            roleSelect.appendChild(opt);
         });
      } catch (error) {
         console.error('Load roles error:', error);
      }
   }

   function initEventListeners() {
      document.getElementById('saveRoleBtn').addEventListener('click', saveRoleAssignment);
   }

   function assignRole(userId, userName) {
      if (!Auth.hasPermission('manage_roles')) {
         Alerts.error('You do not have permission to assign roles');
         return;
      }

      currentUserId = userId;
      document.getElementById('userId').value = userId;
      document.getElementById('userName').value = userName;
      document.getElementById('roleId').value = '';

      const modal = new bootstrap.Modal(document.getElementById('assignRoleModal'));
      modal.show();
   }

   async function saveRoleAssignment() {
      const roleId = document.getElementById('roleId').value;

      if (!roleId) {
         Alerts.warning('Please select a role');
         return;
      }

      try {
         Alerts.loading('Assigning role...');
         await api.post(`role/assign-member/${currentUserId}`, {
            role_id: parseInt(roleId)
         });
         Alerts.closeLoading();
         Alerts.success('Role assigned successfully');
         bootstrap.Modal.getInstance(document.getElementById('assignRoleModal')).hide();
         QMGridHelper.reload(usersTable);
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Assign role error:', error);
         Alerts.handleApiError(error);
      }
   }

   function viewUser(userId) {
      window.location.href = `members.php?id=${userId}`;
   }
</script>

<?php require_once '../includes/footer.php'; ?>