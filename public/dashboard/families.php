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
<script>
   (function() {
      'use strict';

      const State = {
         familiesTable: null,
         currentFamilyId: null,
         isEditMode: false,
         membersData: [],
         headOfHouseholdChoices: null,
         addMemberChoices: null
      };

      document.addEventListener('DOMContentLoaded', async () => {
         if (!Auth.requireAuth()) return;
         await Config.waitForSettings();
         await initPage();
      });

      async function initPage() {
         await loadMembers();
         initTable();
         initEventListeners();
         loadStats();
      }

      function initTable() {
         State.familiesTable = QMGridHelper.init('#familiesTable', {
            url: `${Config.API_BASE_URL}/family/all`,
            pageSize: 25,
            selectable: false,
            exportable: true,
            columns: [{
                  key: 'FamilyName',
                  title: 'Family Name',
                  render: (value, row) => `
                  <div class="d-flex align-items-center">
                     <div class="rounded-circle bg-primary bg-opacity-25 text-primary d-flex align-items-center justify-content-center me-2" style="width:38px;height:38px;">
                        <i class="bi bi-house-heart"></i>
                     </div>
                     <div>
                        <div class="fw-medium">${value || 'Unnamed'}</div>
                        <small class="text-muted">${row.MemberCount || 0} member${(row.MemberCount || 0) !== 1 ? 's' : ''}</small>
                     </div>
                  </div>`
               },
               {
                  key: 'HeadOfHouseholdName',
                  title: 'Head of Household',
                  render: (value) => value || '<span class="text-muted">Not assigned</span>'
               },
               {
                  key: 'MemberCount',
                  title: 'Members',
                  width: '90px',
                  render: (value) => `<span class="badge bg-secondary">${value || 0}</span>`
               },
               {
                  key: 'CreatedAt',
                  title: 'Created',
                  width: '100px',
                  render: (value) => QMGridHelper.formatDate(value, 'short')
               },
               {
                  key: 'FamilyID',
                  title: 'Actions',
                  width: '130px',
                  sortable: false,
                  exportable: false,
                  render: (value) => `
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-primary btn-sm" onclick="viewFamily(${value})" title="View"><i class="bi bi-eye"></i></button>
                     <button class="btn btn-warning btn-sm" onclick="editFamily(${value})" title="Edit"><i class="bi bi-pencil"></i></button>
                     <button class="btn btn-danger btn-sm" onclick="deleteFamily(${value})" title="Delete"><i class="bi bi-trash"></i></button>
                  </div>`
               }
            ],
            onDataLoaded: (data) => {
               document.getElementById('totalFamiliesCount').textContent = data.pagination?.total || data.total || 0;
            }
         });
      }

      async function loadStats() {
         try {
            const response = await api.get('family/all?limit=1000');
            // api.get() returns the data array directly (unwrapped from response.data)
            const families = Array.isArray(response) ? response : (response?.data || []);
            const totalMembers = families.reduce((sum, f) => sum + (parseInt(f.MemberCount) || 0), 0);
            const avgSize = families.length > 0 ? (totalMembers / families.length).toFixed(1) : 0;

            const thisMonth = new Date();
            thisMonth.setDate(1);
            thisMonth.setHours(0, 0, 0, 0);
            const newThisMonth = families.filter(f => f.CreatedAt && new Date(f.CreatedAt) >= thisMonth).length;

            renderStatsCards({
               total: families.length,
               totalMembers,
               avgSize,
               newThisMonth
            });
         } catch (error) {
            console.error('Failed to load stats:', error);
            renderStatsCards({
               total: 0,
               totalMembers: 0,
               avgSize: 0,
               newThisMonth: 0
            });
         }
      }

      function renderStatsCards(stats) {
         const cards = [{
               title: 'Total Families',
               value: stats.total,
               subtitle: 'All registered',
               icon: 'house-heart',
               color: 'primary'
            },
            {
               title: 'Total Members',
               value: stats.totalMembers,
               subtitle: 'In all families',
               icon: 'people',
               color: 'success'
            },
            {
               title: 'Average Size',
               value: stats.avgSize,
               subtitle: 'Members per family',
               icon: 'bar-chart',
               color: 'info'
            },
            {
               title: 'New This Month',
               value: stats.newThisMonth,
               subtitle: 'Recently added',
               icon: 'calendar-plus',
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

      function populateMemberSelect(selectId, excludeIds = []) {
         const select = document.getElementById(selectId);
         if (!select) return;
         select.innerHTML = '<option value="">Select Member</option>';
         State.membersData.filter(m => !excludeIds.includes(m.MbrID)).forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.MbrID;
            opt.textContent = `${m.MbrFirstName || ''} ${m.MbrFamilyName || ''}`.trim();
            select.appendChild(opt);
         });
      }

      function initEventListeners() {
         document.getElementById('addFamilyBtn')?.addEventListener('click', () => {
            if (!Auth.hasPermission('manage_families')) {
               Alerts.error('You do not have permission to create families');
               return;
            }
            openFamilyModal();
         });
         document.getElementById('saveFamilyBtn')?.addEventListener('click', saveFamily);
         document.getElementById('refreshFamilyGrid')?.addEventListener('click', () => {
            QMGridHelper.reload(State.familiesTable);
            loadStats();
         });
         document.getElementById('editFamilyFromViewBtn')?.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('viewFamilyModal')).hide();
            editFamily(State.currentFamilyId);
         });
         document.getElementById('manageMembersBtn')?.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('viewFamilyModal')).hide();
            openManageMembersModal(State.currentFamilyId);
         });
         document.getElementById('addMemberToFamilyBtn')?.addEventListener('click', addMemberToFamily);
      }

      function openFamilyModal(familyId = null) {
         State.isEditMode = !!familyId;
         State.currentFamilyId = familyId;
         document.getElementById('familyForm').reset();
         document.getElementById('familyId').value = '';

         if (State.headOfHouseholdChoices) {
            State.headOfHouseholdChoices.destroy();
            State.headOfHouseholdChoices = null;
         }
         populateMemberSelect('headOfHousehold');
         State.headOfHouseholdChoices = new Choices(document.getElementById('headOfHousehold'), {
            searchEnabled: true,
            searchPlaceholderValue: 'Search members...',
            itemSelectText: '',
            allowHTML: true
         });

         document.getElementById('familyModalTitle').innerHTML = State.isEditMode ?
            '<i class="bi bi-pencil-square me-2"></i>Edit Family' :
            '<i class="bi bi-house-heart me-2"></i>Add New Family';

         const modal = new bootstrap.Modal(document.getElementById('familyModal'));
         modal.show();
         if (State.isEditMode) loadFamilyForEdit(familyId);
      }

      async function loadFamilyForEdit(familyId) {
         try {
            Alerts.loading('Loading family...');
            const family = await api.get(`family/view/${familyId}`);
            Alerts.closeLoading();
            document.getElementById('familyId').value = family.FamilyID;
            document.getElementById('familyName').value = family.FamilyName || '';
            if (State.headOfHouseholdChoices && family.HeadOfHouseholdID) {
               State.headOfHouseholdChoices.setChoiceByValue(family.HeadOfHouseholdID.toString());
            }
         } catch (error) {
            Alerts.closeLoading();
            Alerts.error('Failed to load family data');
         }
      }

      async function saveFamily() {
         const familyName = document.getElementById('familyName').value.trim();
         if (!familyName) {
            Alerts.warning('Family name is required');
            return;
         }

         const headId = document.getElementById('headOfHousehold').value;
         const payload = {
            family_name: familyName,
            branch_id: 1
         };
         if (headId) payload.head_id = parseInt(headId);

         try {
            Alerts.loading('Saving family...');
            if (State.isEditMode) {
               await api.put(`family/update/${State.currentFamilyId}`, payload);
            } else {
               await api.post('family/create', payload);
            }
            Alerts.closeLoading();
            Alerts.success(State.isEditMode ? 'Family updated' : 'Family created');
            bootstrap.Modal.getInstance(document.getElementById('familyModal')).hide();
            QMGridHelper.reload(State.familiesTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function viewFamily(familyId) {
         State.currentFamilyId = familyId;
         const modal = new bootstrap.Modal(document.getElementById('viewFamilyModal'));
         modal.show();

         try {
            const family = await api.get(`family/view/${familyId}`);
            const membersList = family.members || [];

            document.getElementById('viewFamilyContent').innerHTML = `
            <div class="family-profile">
               <div class="profile-header text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                  <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
                     <i class="bi bi-house-heart-fill text-primary" style="font-size:2.5rem;"></i>
                  </div>
                  <h3 class="text-white mb-1">${family.FamilyName || 'Unnamed Family'}</h3>
                  <p class="text-white-50 mb-0">${membersList.length} member${membersList.length !== 1 ? 's' : ''}</p>
               </div>
               <div class="p-4">
                  <div class="row g-3 mb-4">
                     <div class="col-md-6">
                        <div class="text-muted small text-uppercase">Head of Household</div>
                        <div class="fw-semibold">${family.HeadOfHouseholdName || 'Not assigned'}</div>
                     </div>
                     <div class="col-md-6">
                        <div class="text-muted small text-uppercase">Created</div>
                        <div class="fw-semibold">${family.CreatedAt ? new Date(family.CreatedAt).toLocaleDateString() : 'Unknown'}</div>
                     </div>
                  </div>
                  <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2"><i class="bi bi-people me-2"></i>Family Members</h6>
                  ${membersList.length > 0 ? `
                     <div class="list-group list-group-flush">
                        ${membersList.map(m => `
                           <div class="list-group-item px-0">
                              <div class="d-flex align-items-center">
                                 <div class="me-3">
                                    ${m.MbrProfilePicture 
                                       ? `<img src="/public/${m.MbrProfilePicture}" class="rounded-circle" style="width:45px;height:45px;object-fit:cover;">`
                                       : `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:45px;height:45px;">${(m.MbrFirstName?.[0] || '') + (m.MbrFamilyName?.[0] || '')}</div>`}
                                 </div>
                                 <div class="flex-grow-1">
                                    <div class="fw-semibold">${m.MbrFirstName || ''} ${m.MbrFamilyName || ''}</div>
                                    <small class="text-muted">${m.MbrEmailAddress || 'No email'}</small>
                                 </div>
                                 ${family.HeadOfHouseholdID == m.MbrID ? '<span class="badge bg-primary">Head</span>' : ''}
                              </div>
                           </div>
                        `).join('')}
                     </div>
                  ` : '<p class="text-muted text-center py-3">No members in this family yet.</p>'}
               </div>
            </div>`;
         } catch (error) {
            document.getElementById('viewFamilyContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load family details</p>
            </div>`;
         }
      }
      window.viewFamily = viewFamily;

      async function openManageMembersModal(familyId) {
         State.currentFamilyId = familyId;
         const modal = new bootstrap.Modal(document.getElementById('manageMembersModal'));
         modal.show();
         await loadFamilyMembersForManagement(familyId);
      }

      async function loadFamilyMembersForManagement(familyId) {
         try {
            const family = await api.get(`family/view/${familyId}`);
            const membersList = family.members || [];
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

            document.getElementById('familyMembersList').innerHTML = membersList.length > 0 ? `
            <div class="list-group">
               ${membersList.map(m => `
                  <div class="list-group-item d-flex align-items-center justify-content-between">
                     <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">
                           ${(m.MbrFirstName?.[0] || '') + (m.MbrFamilyName?.[0] || '')}
                        </div>
                        <div>
                           <div class="fw-semibold">${m.MbrFirstName || ''} ${m.MbrFamilyName || ''}</div>
                           <small class="text-muted">${m.MbrEmailAddress || 'No email'}</small>
                        </div>
                     </div>
                     ${family.HeadOfHouseholdID != m.MbrID 
                        ? `<button class="btn btn-outline-danger btn-sm" onclick="removeMemberFromFamily(${familyId}, ${m.MbrID})"><i class="bi bi-x-circle"></i></button>` 
                        : '<span class="badge bg-primary">Head</span>'}
                  </div>
               `).join('')}
            </div>
         ` : '<p class="text-muted text-center">No members in this family.</p>';
         } catch (error) {
            document.getElementById('familyMembersList').innerHTML = '<p class="text-danger">Failed to load members</p>';
         }
      }

      async function addMemberToFamily() {
         const memberId = document.getElementById('addMemberSelect').value;
         if (!memberId) {
            Alerts.warning('Please select a member to add');
            return;
         }

         try {
            Alerts.loading('Adding member...');
            await api.post(`family/addMember/${State.currentFamilyId}`, {
               member_id: parseInt(memberId),
               role: 'Member'
            });
            Alerts.closeLoading();
            Alerts.success('Member added to family');
            await loadFamilyMembersForManagement(State.currentFamilyId);
            QMGridHelper.reload(State.familiesTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function removeMemberFromFamily(familyId, memberId) {
         const confirmed = await Alerts.confirm({
            title: 'Remove Member',
            text: 'Remove this member from the family?',
            confirmButtonText: 'Yes, remove'
         });
         if (!confirmed) return;

         try {
            Alerts.loading('Removing member...');
            await api.delete(`family/removeMember/${familyId}/${memberId}`);
            Alerts.closeLoading();
            Alerts.success('Member removed from family');
            await loadFamilyMembersForManagement(familyId);
            QMGridHelper.reload(State.familiesTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }
      window.removeMemberFromFamily = removeMemberFromFamily;

      function editFamily(familyId) {
         if (!Auth.hasPermission('manage_families')) {
            Alerts.error('You do not have permission to edit families');
            return;
         }
         openFamilyModal(familyId);
      }

      async function deleteFamily(familyId) {
         if (!Auth.hasPermission('manage_families')) {
            Alerts.error('You do not have permission to delete families');
            return;
         }
         const confirmed = await Alerts.confirm({
            title: 'Delete Family',
            text: 'Are you sure? Members will not be deleted, only the family grouping.',
            icon: 'warning',
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc3545'
         });
         if (!confirmed) return;

         try {
            Alerts.loading('Deleting family...');
            await api.delete(`family/delete/${familyId}`);
            Alerts.closeLoading();
            Alerts.success('Family deleted');
            QMGridHelper.reload(State.familiesTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      window.editFamily = editFamily;
      window.deleteFamily = deleteFamily;
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

   .family-profile .profile-header {
      border-radius: 0;
   }
</style>

<?php require_once '../includes/footer.php'; ?>