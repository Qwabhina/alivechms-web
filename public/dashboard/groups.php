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
<script>
   (function() {
      'use strict';

      const State = {
         groupsTable: null,
         currentGroupId: null,
         isEditMode: false,
         membersData: [],
         groupTypesData: [],
         leaderChoices: null,
         typeChoices: null,
         addMemberChoices: null
      };

      document.addEventListener('DOMContentLoaded', async () => {
         if (!Auth.requireAuth()) return;
         await Config.waitForSettings();
         await initPage();
      });

      async function initPage() {
         await Promise.all([loadMembers(), loadGroupTypes()]);
         initTable();
         initEventListeners();
         loadStats();
      }

      function initTable() {
         State.groupsTable = QMGridHelper.init('#groupsTable', {
            url: `${Config.API_BASE_URL}/group/all`,
            pageSize: 25,
            selectable: false,
            exportable: true,
            columns: [{
                  key: 'GroupName',
                  title: 'Group Name',
                  render: (value, row) => `
                  <div class="d-flex align-items-center">
                     <div class="rounded-circle bg-success bg-opacity-25 text-success d-flex align-items-center justify-content-center me-2" style="width:38px;height:38px;">
                        <i class="bi bi-people"></i>
                     </div>
                     <div>
                        <div class="fw-medium">${value}</div>
                        <small class="text-muted">${row.MemberCount || 0} members</small>
                     </div>
                  </div>
               `
               },
               {
                  key: 'GroupTypeName',
                  title: 'Type',
                  render: (value) => value ? `<span class="badge bg-secondary">${value}</span>` : '-'
               },
               {
                  key: 'LeaderFirstName',
                  title: 'Leader',
                  render: (value, row) => {
                     const name = `${row.LeaderFirstName || ''} ${row.LeaderFamilyName || ''}`.trim();
                     return name || '<span class="text-muted">Not assigned</span>';
                  }
               },
               {
                  key: 'GroupID',
                  title: 'Actions',
                  width: '130px',
                  sortable: false,
                  exportable: false,
                  render: (value) => `
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-primary btn-sm" onclick="viewGroup(${value})" title="View">
                        <i class="bi bi-eye"></i>
                     </button>
                     <button class="btn btn-warning btn-sm" onclick="editGroup(${value})" title="Edit">
                        <i class="bi bi-pencil"></i>
                     </button>
                     <button class="btn btn-danger btn-sm" onclick="deleteGroup(${value})" title="Delete">
                        <i class="bi bi-trash"></i>
                     </button>
                  </div>
               `
               }
            ],
            onDataLoaded: (data) => {
               document.getElementById('totalGroupsCount').textContent = data.pagination?.total || data.total || 0;
            }
         });
      }

      async function loadStats() {
         try {
            const response = await api.get('group/all?limit=1000');
            // api.get() returns the data array directly (unwrapped from response.data)
            const groups = Array.isArray(response) ? response : (response?.data || []);
            const totalMembers = groups.reduce((sum, g) => sum + (parseInt(g.MemberCount) || 0), 0);
            const avgSize = groups.length > 0 ? (totalMembers / groups.length).toFixed(1) : 0;

            renderStatsCards({
               total: groups.length,
               totalMembers: totalMembers,
               types: State.groupTypesData.length,
               avgSize: avgSize
            });
         } catch (error) {
            console.error('Failed to load stats:', error);
            renderStatsCards({
               total: 0,
               totalMembers: 0,
               types: 0,
               avgSize: 0
            });
         }
      }

      function renderStatsCards(stats) {
         const cards = [{
               title: 'Total Groups',
               value: stats.total,
               subtitle: 'All active groups',
               icon: 'people-fill',
               color: 'primary'
            },
            {
               title: 'Total Members',
               value: stats.totalMembers,
               subtitle: 'In all groups',
               icon: 'person-check',
               color: 'success'
            },
            {
               title: 'Group Types',
               value: stats.types,
               subtitle: 'Categories',
               icon: 'tags',
               color: 'info'
            },
            {
               title: 'Average Size',
               value: stats.avgSize,
               subtitle: 'Members per group',
               icon: 'bar-chart',
               color: 'warning'
            }
         ];

         document.getElementById('statsCards').innerHTML = cards.map(card => `
         <div class="col-lg-3 col-md-6">
            <div class="card stat-card bg-${card.color} bg-opacity-25 mb-3">
               <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                     <div>
                        <p class="text-muted mb-1">${card.title}</p>
                        <h3 class="mb-0">${card.value}</h3>
                        <small class="text-muted">${card.subtitle}</small>
                     </div>
                     <div class="stat-icon bg-${card.color} text-white rounded-circle p-3">
                        <i class="bi bi-${card.icon}"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      `).join('');
      }

      async function loadMembers() {
         try {
            const response = await api.get('member/all?limit=1000');
            // api.get() returns the data array directly (unwrapped from response.data)
            State.membersData = Array.isArray(response) ? response : (response?.data || []);
         } catch (error) {
            console.error('Failed to load members:', error);
            State.membersData = [];
         }
      }

      async function loadGroupTypes() {
         try {
            const response = await api.get('grouptype/all?limit=100');
            // api.get() returns the data array directly (unwrapped from response.data)
            State.groupTypesData = Array.isArray(response) ? response : (response?.data || []);
         } catch (error) {
            console.error('Failed to load group types:', error);
            State.groupTypesData = [];
         }
      }

      function populateMemberSelect(selectId, excludeIds = []) {
         const select = document.getElementById(selectId);
         if (!select) return;

         select.innerHTML = '<option value="">Select Member</option>';
         State.membersData
            .filter(m => !excludeIds.includes(m.MbrID))
            .forEach(m => {
               const opt = document.createElement('option');
               opt.value = m.MbrID;
               opt.textContent = `${m.MbrFirstName} ${m.MbrFamilyName}`;
               select.appendChild(opt);
            });
      }

      function populateTypeSelect() {
         const select = document.getElementById('groupType');
         if (!select) return;

         select.innerHTML = '<option value="">Select Type</option>';
         State.groupTypesData.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.GroupTypeID;
            opt.textContent = t.GroupTypeName;
            select.appendChild(opt);
         });
      }

      function initEventListeners() {
         document.getElementById('addGroupBtn')?.addEventListener('click', () => {
            if (!Auth.hasPermission('manage_groups')) {
               Alerts.error('You do not have permission to create groups');
               return;
            }
            openGroupModal();
         });

         document.getElementById('saveGroupBtn')?.addEventListener('click', saveGroup);
         document.getElementById('refreshGroupGrid')?.addEventListener('click', () => {
            QMGridHelper.reload(State.groupsTable);
            loadStats();
         });

         document.getElementById('editGroupFromViewBtn')?.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('viewGroupModal')).hide();
            editGroup(State.currentGroupId);
         });

         document.getElementById('manageMembersBtn')?.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('viewGroupModal')).hide();
            openManageMembersModal(State.currentGroupId);
         });

         document.getElementById('addMemberToGroupBtn')?.addEventListener('click', addMemberToGroup);
         document.getElementById('manageTypesBtn')?.addEventListener('click', openGroupTypesModal);
         document.getElementById('addGroupTypeBtn')?.addEventListener('click', addGroupType);
      }

      function openGroupModal(groupId = null) {
         State.isEditMode = !!groupId;
         State.currentGroupId = groupId;

         document.getElementById('groupForm').reset();
         document.getElementById('groupId').value = '';

         // Destroy existing Choices instances
         if (State.leaderChoices) {
            State.leaderChoices.destroy();
            State.leaderChoices = null;
         }
         if (State.typeChoices) {
            State.typeChoices.destroy();
            State.typeChoices = null;
         }

         populateMemberSelect('groupLeader');
         populateTypeSelect();

         State.leaderChoices = new Choices(document.getElementById('groupLeader'), {
            searchEnabled: true,
            searchPlaceholderValue: 'Search members...',
            itemSelectText: ''
         });

         State.typeChoices = new Choices(document.getElementById('groupType'), {
            searchEnabled: true,
            itemSelectText: ''
         });

         document.getElementById('groupModalTitle').innerHTML = State.isEditMode ?
            '<i class="bi bi-pencil-square me-2"></i>Edit Group' :
            '<i class="bi bi-people me-2"></i>Add New Group';

         const modal = new bootstrap.Modal(document.getElementById('groupModal'));
         modal.show();

         if (State.isEditMode) loadGroupForEdit(groupId);
      }

      async function loadGroupForEdit(groupId) {
         try {
            Alerts.loading('Loading group...');
            const group = await api.get(`group/view/${groupId}`);
            Alerts.closeLoading();

            document.getElementById('groupId').value = group.GroupID;
            document.getElementById('groupName').value = group.GroupName || '';
            document.getElementById('groupDescription').value = group.GroupDescription || '';

            if (State.typeChoices && group.GroupTypeID) {
               State.typeChoices.setChoiceByValue(group.GroupTypeID.toString());
            }
            if (State.leaderChoices && group.GroupLeaderID) {
               State.leaderChoices.setChoiceByValue(group.GroupLeaderID.toString());
            }
         } catch (error) {
            Alerts.closeLoading();
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
            if (State.isEditMode) {
               await api.put(`group/update/${State.currentGroupId}`, payload);
            } else {
               await api.post('group/create', payload);
            }
            Alerts.closeLoading();
            Alerts.success(State.isEditMode ? 'Group updated' : 'Group created');
            bootstrap.Modal.getInstance(document.getElementById('groupModal')).hide();
            QMGridHelper.reload(State.groupsTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function viewGroup(groupId) {
         State.currentGroupId = groupId;
         const modal = new bootstrap.Modal(document.getElementById('viewGroupModal'));
         modal.show();

         try {
            const group = await api.get(`group/view/${groupId}`);
            const membersRes = await api.get(`group/members/${groupId}?limit=100`);
            // api.get() returns the data array directly (unwrapped from response.data)
            const membersList = Array.isArray(membersRes) ? membersRes : (membersRes?.data || []);

            const leaderName = `${group.LeaderFirstName || ''} ${group.LeaderFamilyName || ''}`.trim();

            document.getElementById('viewGroupContent').innerHTML = `
            <div class="group-profile">
               <div class="profile-header text-center py-4" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                  <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
                     <i class="bi bi-people-fill text-success" style="font-size:2.5rem;"></i>
                  </div>
                  <h3 class="text-white mb-1">${group.GroupName}</h3>
                  <span class="badge bg-white bg-opacity-25 text-white">${group.GroupTypeName || 'No Type'}</span>
               </div>
               
               <div class="p-4">
                  <div class="row g-3 mb-4">
                     <div class="col-md-6">
                        <div class="text-muted small text-uppercase">Leader</div>
                        <div class="fw-semibold">${leaderName || 'Not assigned'}</div>
                     </div>
                     <div class="col-md-6">
                        <div class="text-muted small text-uppercase">Total Members</div>
                        <div class="fw-semibold">${membersList.length}</div>
                     </div>
                     ${group.GroupDescription ? `
                     <div class="col-12">
                        <div class="text-muted small text-uppercase">Description</div>
                        <div>${group.GroupDescription}</div>
                     </div>
                     ` : ''}
                  </div>

                  <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">
                     <i class="bi bi-people me-2"></i>Group Members
                  </h6>
                  ${membersList.length > 0 ? `
                     <div class="list-group list-group-flush">
                        ${membersList.map(m => `
                           <div class="list-group-item px-0">
                              <div class="d-flex align-items-center">
                                 <div class="me-3">
                                    ${m.MbrProfilePicture ? 
                                       `<img src="/public/${m.MbrProfilePicture}" class="rounded-circle" style="width:45px;height:45px;object-fit:cover;">` :
                                       `<div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                                          ${(m.MbrFirstName?.[0] || '') + (m.MbrFamilyName?.[0] || '')}
                                       </div>`
                                    }
                                 </div>
                                 <div class="flex-grow-1">
                                    <div class="fw-semibold">${m.MbrFirstName} ${m.MbrFamilyName}</div>
                                    <small class="text-muted">${m.MbrEmailAddress || 'No email'}</small>
                                 </div>
                                 ${group.GroupLeaderID == m.MbrID ? '<span class="badge bg-success">Leader</span>' : ''}
                              </div>
                           </div>
                        `).join('')}
                     </div>
                  ` : '<p class="text-muted text-center py-3">No members in this group yet.</p>'}
               </div>
            </div>
         `;
         } catch (error) {
            document.getElementById('viewGroupContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load group details</p>
            </div>
         `;
         }
      }

      window.viewGroup = viewGroup;

      async function openManageMembersModal(groupId) {
         State.currentGroupId = groupId;
         const modal = new bootstrap.Modal(document.getElementById('manageMembersModal'));
         modal.show();
         await loadGroupMembersForManagement(groupId);
      }

      async function loadGroupMembersForManagement(groupId) {
         try {
            const group = await api.get(`group/view/${groupId}`);
            const membersRes = await api.get(`group/members/${groupId}?limit=100`);
            // api.get() returns the data array directly (unwrapped from response.data)
            const membersList = Array.isArray(membersRes) ? membersRes : (membersRes?.data || []);
            const memberIds = membersList.map(m => m.MbrID);

            if (State.addMemberChoices) {
               State.addMemberChoices.destroy();
               State.addMemberChoices = null;
            }
            populateMemberSelect('addMemberSelect', memberIds);
            State.addMemberChoices = new Choices(document.getElementById('addMemberSelect'), {
               searchEnabled: true,
               searchPlaceholderValue: 'Search members...',
               itemSelectText: ''
            });

            document.getElementById('groupMembersList').innerHTML = membersList.length > 0 ? `
            <div class="list-group">
               ${membersList.map(m => `
                  <div class="list-group-item d-flex align-items-center justify-content-between">
                     <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">
                           ${(m.MbrFirstName?.[0] || '') + (m.MbrFamilyName?.[0] || '')}
                        </div>
                        <div>
                           <div class="fw-semibold">${m.MbrFirstName} ${m.MbrFamilyName}</div>
                           <small class="text-muted">${m.MbrEmailAddress || 'No email'}</small>
                        </div>
                     </div>
                     ${group.GroupLeaderID != m.MbrID ? `
                        <button class="btn btn-outline-danger btn-sm" onclick="removeMemberFromGroup(${groupId}, ${m.MbrID})">
                           <i class="bi bi-x-circle"></i>
                        </button>
                     ` : '<span class="badge bg-success">Leader</span>'}
                  </div>
               `).join('')}
            </div>
         ` : '<p class="text-muted text-center">No members in this group.</p>';
         } catch (error) {
            document.getElementById('groupMembersList').innerHTML = '<p class="text-danger">Failed to load members</p>';
         }
      }

      async function addMemberToGroup() {
         const memberId = document.getElementById('addMemberSelect').value;
         if (!memberId) {
            Alerts.warning('Please select a member to add');
            return;
         }

         try {
            Alerts.loading('Adding member...');
            await api.post(`group/addMember/${State.currentGroupId}`, {
               member_id: parseInt(memberId)
            });
            Alerts.closeLoading();
            Alerts.success('Member added to group');
            await loadGroupMembersForManagement(State.currentGroupId);
            QMGridHelper.reload(State.groupsTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function removeMemberFromGroup(groupId, memberId) {
         const confirmed = await Alerts.confirm({
            title: 'Remove Member',
            text: 'Remove this member from the group?',
            confirmButtonText: 'Yes, remove'
         });

         if (!confirmed) return;

         try {
            Alerts.loading('Removing member...');
            await api.delete(`group/removeMember/${groupId}/${memberId}`);
            Alerts.closeLoading();
            Alerts.success('Member removed from group');
            await loadGroupMembersForManagement(groupId);
            QMGridHelper.reload(State.groupsTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      window.removeMemberFromGroup = removeMemberFromGroup;

      async function openGroupTypesModal() {
         const modal = new bootstrap.Modal(document.getElementById('groupTypesModal'));
         modal.show();
         await loadGroupTypesList();
      }

      async function loadGroupTypesList() {
         try {
            await loadGroupTypes();
            document.getElementById('groupTypesList').innerHTML = State.groupTypesData.length > 0 ? `
            <div class="list-group">
               ${State.groupTypesData.map(t => `
                  <div class="list-group-item d-flex align-items-center justify-content-between">
                     <div>
                        <i class="bi bi-tag me-2 text-primary"></i>
                        <span class="fw-medium">${t.GroupTypeName}</span>
                     </div>
                     <button class="btn btn-outline-danger btn-sm" onclick="deleteGroupType(${t.GroupTypeID})">
                        <i class="bi bi-trash"></i>
                     </button>
                  </div>
               `).join('')}
            </div>
         ` : '<p class="text-muted text-center">No group types defined.</p>';
         } catch (error) {
            document.getElementById('groupTypesList').innerHTML = '<p class="text-danger">Failed to load types</p>';
         }
      }

      async function addGroupType() {
         const name = document.getElementById('newTypeName').value.trim();
         if (!name) {
            Alerts.warning('Please enter a type name');
            return;
         }

         try {
            Alerts.loading('Adding type...');
            await api.post('grouptype/create', {
               name: name
            });
            Alerts.closeLoading();
            Alerts.success('Group type added');
            document.getElementById('newTypeName').value = '';
            await loadGroupTypesList();
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function deleteGroupType(typeId) {
         const confirmed = await Alerts.confirm({
            title: 'Delete Group Type',
            text: 'Are you sure? This cannot be undone if no groups use this type.',
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc3545'
         });

         if (!confirmed) return;

         try {
            Alerts.loading('Deleting type...');
            await api.delete(`grouptype/delete/${typeId}`);
            Alerts.closeLoading();
            Alerts.success('Group type deleted');
            await loadGroupTypesList();
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      window.deleteGroupType = deleteGroupType;

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
            Alerts.success('Group deleted');
            QMGridHelper.reload(State.groupsTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      window.editGroup = editGroup;
      window.deleteGroup = deleteGroup;
   })();
</script>

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