<?php
$pageTitle = 'Groups Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Groups</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Groups</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addGroupBtn" data-permission="manage_groups">
         <i class="bi bi-plus-circle me-2"></i>Add Group
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Total Groups</p>
                     <h3 class="mb-0" id="totalGroups">0</h3>
                     <small class="text-muted">All active groups</small>
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
                     <p class="text-muted mb-1">Total Members</p>
                     <h3 class="mb-0" id="totalMembers">0</h3>
                     <small class="text-muted">In all groups</small>
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
                     <p class="text-muted mb-1">Group Types</p>
                     <h3 class="mb-0" id="totalTypes">0</h3>
                     <small class="text-muted">Categories</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-tags"></i>
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
                     <p class="text-muted mb-1">Average Size</p>
                     <h3 class="mb-0" id="avgSize">0</h3>
                     <small class="text-muted">Members per group</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-bar-chart"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Groups Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>All Groups</h5>
      </div>
      <div class="card-body">
         <div class="d-flex justify-content-end mb-3 gap-2">
            <button class="btn btn-success btn-sm" onclick="groupsGrid.download('xlsx', 'groups.xlsx')">
               <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="groupsGrid.download('pdf', 'groups.pdf', {orientation:'landscape', title:'Groups List'})">
               <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
            <button class="btn btn-primary btn-sm" onclick="groupsGrid.print()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-secondary btn-sm" onclick="groupsGrid.setData()">
               <i class="bi bi-arrow-clockwise"></i>
            </button>
         </div>
         <div class="table-responsive">
            <div id="groupsGrid"></div>
         </div>
      </div>
   </div>
</div>
</main>

<!-- Group Modal -->
<div class="modal fade" id="groupModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="groupModalTitle">Add New Group</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="groupForm">
               <input type="hidden" id="groupId">
               <div class="mb-3">
                  <label class="form-label">Group Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="groupName" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Group Type <span class="text-danger">*</span></label>
                  <select class="form-select" id="groupType" required>
                     <option value="">Select Type</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Group Leader <span class="text-danger">*</span></label>
                  <select class="form-select" id="groupLeader" required>
                     <option value="">Select Leader</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" id="groupDescription" rows="3"></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer">
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
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Group Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="viewGroupContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="editGroupFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit Group
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let groupsGrid = null;
   let currentGroupId = null;
   let isEditMode = false;
   let leaderChoices = null;
   let typeChoices = null;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await initPage();
   });

   async function initPage() {
      initGrid();
      initEventListeners();
      loadStats();
      await loadMembers();
      await loadGroupTypes();
   }

   function initGrid() {
      groupsGrid = new Tabulator("#groupsGrid", {
         layout: "fitColumns",
         responsiveLayout: "collapse",
         resizableColumns: false,
         pagination: true,
         paginationMode: "remote",
         paginationSize: 25,
         paginationSizeSelector: [10, 25, 50, 100],
         ajaxURL: `${Config.API_BASE_URL}/group/all`,
         ajaxConfig: {
            headers: {
               'Authorization': `Bearer ${Auth.getToken()}`
            }
         },
         ajaxResponse: function(url, params, response) {
            const data = response?.data?.data || response?.data || [];
            const pagination = response?.data?.pagination || {};
            return {
               last_page: pagination.pages || 1,
               data: data.map(g => ({
                  name: g.GroupName,
                  type: g.GroupTypeName || '-',
                  leader: `${g.LeaderFirstName || ''} ${g.LeaderFamilyName || ''}`.trim() || '-',
                  members: g.MemberCount || 0,
                  id: g.GroupID
               }))
            };
         },
         ajaxURLGenerator: function(url, config, params) {
            let queryParams = [];
            if (params.page) queryParams.push(`page=${params.page}`);
            if (params.size) queryParams.push(`limit=${params.size}`);
            if (params.search) queryParams.push(`name=${encodeURIComponent(params.search)}`);
            return queryParams.length ? `${url}?${queryParams.join('&')}` : url;
         },
         columns: [{
               title: "Group Name",
               field: "name",
               widthGrow: 2,
               responsive: 0,
               download: true
            },
            {
               title: "Type",
               field: "type",
               widthGrow: 1.5,
               responsive: 2,
               download: true
            },
            {
               title: "Leader",
               field: "leader",
               widthGrow: 2,
               responsive: 1,
               download: true
            },
            {
               title: "Members",
               field: "members",
               widthGrow: 1,
               responsive: 2,
               download: true
            },
            {
               title: "Actions",
               field: "id",
               width: 120,
               headerSort: false,
               responsive: 0,
               download: false,
               formatter: function(cell) {
                  const id = cell.getValue();
                  return `
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewGroup(${id})" title="View">
                           <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning" onclick="editGroup(${id})" title="Edit">
                           <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteGroup(${id})" title="Delete">
                           <i class="bi bi-trash"></i>
                        </button>
                     </div>
                  `;
               }
            }
         ]
      });
   }

   async function loadStats() {
      try {
         const response = await api.get('group/all?limit=1000');
         const groups = response?.data || [];
         const totalMembers = groups.reduce((sum, g) => sum + (g.MemberCount || 0), 0);
         const avgSize = groups.length > 0 ? Math.round(totalMembers / groups.length) : 0;

         document.getElementById('totalGroups').textContent = groups.length;
         document.getElementById('totalMembers').textContent = totalMembers;
         document.getElementById('avgSize').textContent = avgSize;
      } catch (error) {
         console.error('Load stats error:', error);
      }
   }

   async function loadMembers() {
      try {
         const response = await api.get('member/all?limit=1000');
         const members = response?.data?.data || response?.data || [];

         const select = document.getElementById('groupLeader');
         select.innerHTML = '<option value="">Select Leader</option>';
         members.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.MbrID;
            opt.textContent = `${m.MbrFirstName} ${m.MbrFamilyName}`;
            select.appendChild(opt);
         });

         if (leaderChoices) leaderChoices.destroy();
         leaderChoices = new Choices(select, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search members...',
            itemSelectText: ''
         });
      } catch (error) {
         console.error('Load members error:', error);
      }
   }

   async function loadGroupTypes() {
      try {
         const response = await api.get('grouptype/all?limit=100');
         const types = response?.data?.data || response?.data || [];

         document.getElementById('totalTypes').textContent = types.length;

         const select = document.getElementById('groupType');
         select.innerHTML = '<option value="">Select Type</option>';
         types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.GroupTypeID;
            opt.textContent = t.GroupTypeName;
            select.appendChild(opt);
         });

         if (typeChoices) typeChoices.destroy();
         typeChoices = new Choices(select, {
            searchEnabled: true,
            itemSelectText: ''
         });
      } catch (error) {
         console.error('Load group types error:', error);
      }
   }

   function initEventListeners() {
      document.getElementById('addGroupBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('manage_groups')) {
            Alerts.error('You do not have permission to create groups');
            return;
         }
         openGroupModal();
      });

      document.getElementById('saveGroupBtn').addEventListener('click', saveGroup);

      document.getElementById('editGroupFromViewBtn').addEventListener('click', () => {
         bootstrap.Modal.getInstance(document.getElementById('viewGroupModal')).hide();
         editGroup(currentGroupId);
      });
   }

   function openGroupModal(groupId = null) {
      isEditMode = !!groupId;
      currentGroupId = groupId;

      document.getElementById('groupForm').reset();
      document.getElementById('groupId').value = '';
      document.getElementById('groupModalTitle').textContent = isEditMode ? 'Edit Group' : 'Add New Group';

      const modal = new bootstrap.Modal(document.getElementById('groupModal'));
      modal.show();

      if (isEditMode) loadGroupForEdit(groupId);
   }

   async function loadGroupForEdit(groupId) {
      try {
         const group = await api.get(`group/view/${groupId}`);
         document.getElementById('groupId').value = group.GroupID;
         document.getElementById('groupName').value = group.GroupName;
         document.getElementById('groupDescription').value = group.GroupDescription || '';

         if (typeChoices) {
            typeChoices.setChoiceByValue(group.GroupTypeID?.toString() || '');
         }
         if (leaderChoices) {
            leaderChoices.setChoiceByValue(group.GroupLeaderID?.toString() || '');
         }
      } catch (error) {
         console.error('Load group error:', error);
         Alerts.error('Failed to load group data');
      }
   }

   async function saveGroup() {
      const groupName = document.getElementById('groupName').value.trim();
      const typeId = document.getElementById('groupType').value;
      const leaderId = document.getElementById('groupLeader').value;

      if (!groupName || !typeId || !leaderId) {
         Alerts.warning('Please fill all required fields');
         return;
      }

      const payload = {
         name: groupName,
         type_id: parseInt(typeId),
         leader_id: parseInt(leaderId),
         description: document.getElementById('groupDescription').value.trim() || null
      };

      try {
         Alerts.loading('Saving group...');
         if (isEditMode) {
            await api.put(`group/update/${currentGroupId}`, payload);
         } else {
            await api.post('group/create', payload);
         }
         Alerts.closeLoading();
         Alerts.success(isEditMode ? 'Group updated successfully' : 'Group created successfully');
         bootstrap.Modal.getInstance(document.getElementById('groupModal')).hide();
         groupsGrid.setData();
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save group error:', error);
         Alerts.handleApiError(error);
      }
   }

   async function viewGroup(groupId) {
      currentGroupId = groupId;
      const modal = new bootstrap.Modal(document.getElementById('viewGroupModal'));
      modal.show();

      try {
         const group = await api.get(`group/view/${groupId}`);
         const members = await api.get(`group/members/${groupId}?limit=100`);
         const membersList = members?.data?.data || members?.data || [];

         document.getElementById('viewGroupContent').innerHTML = `
            <div class="mb-4">
               <h4 class="mb-3"><i class="bi bi-people-fill me-2 text-primary"></i>${group.GroupName}</h4>
               <div class="row g-3">
                  <div class="col-md-6">
                     <div class="text-muted small">Group Type</div>
                     <div class="fw-semibold">${group.GroupTypeName || '-'}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small">Leader</div>
                     <div class="fw-semibold">${group.LeaderFirstName} ${group.LeaderFamilyName}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small">Total Members</div>
                     <div class="fw-semibold">${membersList.length}</div>
                  </div>
                  ${group.GroupDescription ? `
                  <div class="col-12">
                     <div class="text-muted small">Description</div>
                     <div>${group.GroupDescription}</div>
                  </div>
                  ` : ''}
               </div>
            </div>
            <h5 class="mb-3">Group Members</h5>
            ${membersList.length > 0 ? `
               <div class="list-group">
                  ${membersList.map(m => `
                     <div class="list-group-item">
                        <div class="d-flex align-items-center">
                           <div class="me-3">
                              <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-size:0.8rem;">
                                 ${m.MbrFirstName[0]}${m.MbrFamilyName[0]}
                              </div>
                           </div>
                           <div class="flex-grow-1">
                              <div class="fw-semibold">${m.MbrFirstName} ${m.MbrFamilyName}</div>
                              <small class="text-muted">${m.MbrEmailAddress || 'No email'}</small>
                           </div>
                           ${group.GroupLeaderID == m.MbrID ? '<span class="badge bg-primary">Leader</span>' : ''}
                        </div>
                     </div>
                  `).join('')}
               </div>
            ` : '<p class="text-muted">No members in this group yet.</p>'}
         `;
      } catch (error) {
         console.error('View group error:', error);
         document.getElementById('viewGroupContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load group details</p>
            </div>
         `;
      }
   }

   function editGroup(groupId) {
      if (!Auth.hasPermission('manage_groups')) {
         Alerts.error('You do not have permission to edit groups');
         return;
      }
      openGroupModal(groupId);
   }

   async function deleteGroup(groupId) {
      if (!Auth.hasPermission('manage_groups')) {
         Alerts.error('You do not have permission to delete groups');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Group',
         text: 'Are you sure? This action cannot be undone.',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting group...');
         await api.delete(`group/delete/${groupId}`);
         Alerts.closeLoading();
         Alerts.success('Group deleted successfully');
         groupsGrid.setData();
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete group error:', error);
         Alerts.handleApiError(error);
      }
   }
</script>

<?php require_once '../includes/footer.php'; ?>