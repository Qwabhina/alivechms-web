<?php
$pageTitle = 'Families Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Families</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Families</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addFamilyBtn" data-permission="create_members">
         <i class="bi bi-plus-circle me-2"></i>Add Family
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Total Families</p>
                     <h3 class="mb-0" id="totalFamilies">0</h3>
                     <small class="text-muted">All registered families</small>
                  </div>
                  <div class="stat-icon bg-primary text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-house-heart"></i>
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
                     <small class="text-muted">In all families</small>
                  </div>
                  <div class="stat-icon bg-success text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-people"></i>
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
                     <p class="text-muted mb-1">Average Size</p>
                     <h3 class="mb-0" id="avgSize">0</h3>
                     <small class="text-muted">Members per family</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-bar-chart"></i>
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
                     <p class="text-muted mb-1">New This Month</p>
                     <h3 class="mb-0" id="newFamilies">0</h3>
                     <small class="text-muted">Recently added</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-calendar-plus"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Families Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-house-heart me-2"></i>All Families</h5>
      </div>
      <div class="card-body">
         <div class="d-flex justify-content-end mb-3 gap-2">
            <button class="btn btn-success btn-sm" onclick="familiesGrid.download('xlsx', 'families.xlsx')">
               <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="familiesGrid.download('pdf', 'families.pdf', {orientation:'portrait', title:'Families List'})">
               <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
            <button class="btn btn-primary btn-sm" onclick="familiesGrid.print()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-secondary btn-sm" onclick="familiesGrid.setData()">
               <i class="bi bi-arrow-clockwise"></i>
            </button>
         </div>
         <div class="table-responsive">
            <div id="familiesGrid"></div>
         </div>
      </div>
   </div>
</div>
</main>

<!-- Family Modal -->
<div class="modal fade" id="familyModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="familyModalTitle">Add New Family</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="familyForm">
               <input type="hidden" id="familyId">
               <div class="mb-3">
                  <label class="form-label">Family Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="familyName" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Head of Household</label>
                  <select class="form-select" id="headOfHousehold">
                     <option value="">Select Member</option>
                  </select>
               </div>
            </form>
         </div>
         <div class="modal-footer">
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
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Family Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="viewFamilyContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="editFamilyFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit Family
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let familiesGrid = null;
   let currentFamilyId = null;
   let isEditMode = false;
   let headOfHouseholdChoices = null;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await initPage();
   });

   async function initPage() {
      initGrid();
      initEventListeners();
      loadStats();
      await loadMembers();
   }

   function initGrid() {
      familiesGrid = new Tabulator("#familiesGrid", {
         layout: "fitColumns",
         responsiveLayout: "collapse",
         resizableColumns: false,
         pagination: true,
         paginationMode: "remote",
         paginationSize: 25,
         paginationSizeSelector: [10, 25, 50, 100],
         ajaxURL: `${Config.API_BASE_URL}/family/all`,
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
               data: data.map(f => ({
                  name: f.FamilyName,
                  head: f.HeadOfHouseholdName || '-',
                  members: f.MemberCount || 0,
                  created: f.CreatedAt ? new Date(f.CreatedAt).toLocaleDateString() : '-',
                  id: f.FamilyID
               }))
            };
         },
         ajaxURLGenerator: function(url, config, params) {
            let queryParams = [];
            if (params.page) queryParams.push(`page=${params.page}`);
            if (params.size) queryParams.push(`limit=${params.size}`);
            if (params.search) queryParams.push(`search=${encodeURIComponent(params.search)}`);
            return queryParams.length ? `${url}?${queryParams.join('&')}` : url;
         },
         columns: [{
               title: "Family Name",
               field: "name",
               widthGrow: 2,
               responsive: 0,
               download: true
            },
            {
               title: "Head of Household",
               field: "head",
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
               title: "Created",
               field: "created",
               widthGrow: 1.5,
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
                        <button class="btn btn-outline-primary" onclick="viewFamily(${id})" title="View">
                           <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning" onclick="editFamily(${id})" title="Edit">
                           <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteFamily(${id})" title="Delete">
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
         const response = await api.get('family/all?limit=1000');
         const families = response?.data || [];
         const totalMembers = families.reduce((sum, f) => sum + (f.MemberCount || 0), 0);
         const avgSize = families.length > 0 ? Math.round(totalMembers / families.length) : 0;

         document.getElementById('totalFamilies').textContent = families.length;
         document.getElementById('totalMembers').textContent = totalMembers;
         document.getElementById('avgSize').textContent = avgSize;
         document.getElementById('newFamilies').textContent = 0;
      } catch (error) {
         console.error('Load stats error:', error);
      }
   }

   async function loadMembers() {
      try {
         const response = await api.get('member/all?limit=1000');
         const members = response?.data?.data || response?.data || [];

         const select = document.getElementById('headOfHousehold');
         select.innerHTML = '<option value="">Select Member</option>';
         members.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.MbrID;
            opt.textContent = `${m.MbrFirstName} ${m.MbrFamilyName}`;
            select.appendChild(opt);
         });

         if (headOfHouseholdChoices) headOfHouseholdChoices.destroy();
         headOfHouseholdChoices = new Choices(select, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search members...',
            itemSelectText: ''
         });
      } catch (error) {
         console.error('Load members error:', error);
      }
   }

   function initEventListeners() {
      document.getElementById('addFamilyBtn').addEventListener('click', () => {
         if (!Auth.hasPermission(Config.PERMISSIONS.CREATE_MEMBERS)) {
            Alerts.error('You do not have permission to create families');
            return;
         }
         openFamilyModal();
      });

      document.getElementById('saveFamilyBtn').addEventListener('click', saveFamily);

      document.getElementById('editFamilyFromViewBtn').addEventListener('click', () => {
         bootstrap.Modal.getInstance(document.getElementById('viewFamilyModal')).hide();
         editFamily(currentFamilyId);
      });
   }

   function openFamilyModal(familyId = null) {
      isEditMode = !!familyId;
      currentFamilyId = familyId;

      document.getElementById('familyForm').reset();
      document.getElementById('familyId').value = '';
      document.getElementById('familyModalTitle').textContent = isEditMode ? 'Edit Family' : 'Add New Family';

      const modal = new bootstrap.Modal(document.getElementById('familyModal'));
      modal.show();

      if (isEditMode) loadFamilyForEdit(familyId);
   }

   async function loadFamilyForEdit(familyId) {
      try {
         const family = await api.get(`family/view/${familyId}`);
         document.getElementById('familyId').value = family.FamilyID;
         document.getElementById('familyName').value = family.FamilyName;
         if (headOfHouseholdChoices) {
            headOfHouseholdChoices.setChoiceByValue(family.HeadOfHouseholdID?.toString() || '');
         }
      } catch (error) {
         console.error('Load family error:', error);
         Alerts.error('Failed to load family data');
      }
   }

   async function saveFamily() {
      const familyName = document.getElementById('familyName').value.trim();
      if (!familyName) {
         Alerts.warning('Family name is required');
         return;
      }

      const payload = {
         family_name: familyName,
         head_of_household_id: document.getElementById('headOfHousehold').value || null,
         branch_id: 1
      };

      try {
         Alerts.loading('Saving family...');
         if (isEditMode) {
            await api.put(`family/update/${currentFamilyId}`, payload);
         } else {
            await api.post('family/create', payload);
         }
         Alerts.closeLoading();
         Alerts.success(isEditMode ? 'Family updated successfully' : 'Family created successfully');
         bootstrap.Modal.getInstance(document.getElementById('familyModal')).hide();
         familiesGrid.setData();
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save family error:', error);
         Alerts.handleApiError(error);
      }
   }

   async function viewFamily(familyId) {
      currentFamilyId = familyId;
      const modal = new bootstrap.Modal(document.getElementById('viewFamilyModal'));
      modal.show();

      try {
         const family = await api.get(`family/view/${familyId}`);
         const members = await api.get(`member/all?family_id=${familyId}&limit=100`);
         const membersList = members?.data?.data || members?.data || [];

         document.getElementById('viewFamilyContent').innerHTML = `
            <div class="mb-4">
               <h4 class="mb-3"><i class="bi bi-house-heart me-2 text-primary"></i>${family.FamilyName}</h4>
               <div class="row g-3">
                  <div class="col-md-6">
                     <div class="text-muted small">Head of Household</div>
                     <div class="fw-semibold">${family.HeadOfHouseholdName || 'Not assigned'}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small">Total Members</div>
                     <div class="fw-semibold">${membersList.length}</div>
                  </div>
               </div>
            </div>
            <h5 class="mb-3">Family Members</h5>
            ${membersList.length > 0 ? `
               <div class="list-group">
                  ${membersList.map(m => `
                     <div class="list-group-item">
                        <div class="d-flex align-items-center">
                           <div class="me-3">
                              ${m.MbrProfilePicture ? 
                                 `<img src="/${m.MbrProfilePicture}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">` :
                                 `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-size:0.8rem;">
                                    ${m.MbrFirstName[0]}${m.MbrFamilyName[0]}
                                 </div>`
                              }
                           </div>
                           <div class="flex-grow-1">
                              <div class="fw-semibold">${m.MbrFirstName} ${m.MbrFamilyName}</div>
                              <small class="text-muted">${m.MbrEmailAddress || 'No email'}</small>
                           </div>
                           ${family.HeadOfHouseholdID == m.MbrID ? '<span class="badge bg-primary">Head</span>' : ''}
                        </div>
                     </div>
                  `).join('')}
               </div>
            ` : '<p class="text-muted">No members in this family yet.</p>'}
         `;
      } catch (error) {
         console.error('View family error:', error);
         document.getElementById('viewFamilyContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load family details</p>
            </div>
         `;
      }
   }

   function editFamily(familyId) {
      if (!Auth.hasPermission(Config.PERMISSIONS.EDIT_MEMBERS)) {
         Alerts.error('You do not have permission to edit families');
         return;
      }
      openFamilyModal(familyId);
   }

   async function deleteFamily(familyId) {
      if (!Auth.hasPermission(Config.PERMISSIONS.DELETE_MEMBERS)) {
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
         Alerts.success('Family deleted successfully');
         familiesGrid.setData();
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete family error:', error);
         Alerts.handleApiError(error);
      }
   }
</script>

<?php require_once '../includes/footer.php'; ?>