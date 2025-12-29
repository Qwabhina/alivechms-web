<?php
$pageTitle = 'Branches Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Branches</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Branches</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addBranchBtn" data-permission="manage_branches">
         <i class="bi bi-plus-circle me-2"></i>Add Branch
      </button>
   </div>

   <!-- Branches Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-building me-2"></i>All Branches</h5>
      </div>
      <div class="card-body">
         <div class="d-flex justify-content-end mb-3 gap-2">
            <button class="btn btn-success btn-sm" onclick="branchesGrid.download('xlsx', 'branches.xlsx')">
               <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="branchesGrid.download('pdf', 'branches.pdf', {orientation:'landscape', title:'Branches Report'})">
               <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
            <button class="btn btn-primary btn-sm" onclick="branchesGrid.print()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-secondary btn-sm" onclick="branchesGrid.setData()">
               <i class="bi bi-arrow-clockwise"></i>
            </button>
         </div>
         <div class="table-responsive">
            <div id="branchesGrid"></div>
         </div>
      </div>
   </div>
</div>
</main>

<!-- Branch Modal -->
<div class="modal fade" id="branchModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="branchModalTitle">Add Branch</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="branchForm">
               <input type="hidden" id="branchId">
               <div class="mb-3">
                  <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="branchName" required maxlength="100">
               </div>
               <div class="mb-3">
                  <label class="form-label">Location</label>
                  <input type="text" class="form-control" id="location" maxlength="200">
               </div>
               <div class="mb-3">
                  <label class="form-label">Phone Number</label>
                  <input type="text" class="form-control" id="phone" maxlength="20">
               </div>
               <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" id="email" maxlength="100">
               </div>
               <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" id="description" rows="2" maxlength="500"></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveBranchBtn">
               <i class="bi bi-check-circle me-1"></i>Save Branch
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let branchesGrid = null;
   let currentBranchId = null;
   let isEditMode = false;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await initPage();
   });

   async function initPage() {
      initGrid();
      initEventListeners();
   }

   function initGrid() {
      branchesGrid = new Tabulator("#branchesGrid", {
         layout: "fitColumns",
         responsiveLayout: "collapse",
         resizableColumns: false,
         ajaxURL: `${Config.API_BASE_URL}/branch/all?limit=1000`,
         ajaxConfig: {
            headers: {
               'Authorization': `Bearer ${Auth.getToken()}`
            }
         },
         ajaxResponse: function(url, params, response) {
            const data = response?.data?.data || response?.data || [];
            return data.map(b => ({
               name: b.BranchName,
               location: b.Location || 'N/A',
               phone: b.Phone || 'N/A',
               email: b.Email || 'N/A',
               id: b.BranchID
            }));
         },
         columns: [{
               title: "Branch Name",
               field: "name",
               widthGrow: 2,
               responsive: 0,
               download: true
            },
            {
               title: "Location",
               field: "location",
               widthGrow: 2,
               responsive: 1,
               download: true
            },
            {
               title: "Phone",
               field: "phone",
               widthGrow: 1.5,
               responsive: 2,
               download: true
            },
            {
               title: "Email",
               field: "email",
               widthGrow: 2,
               responsive: 2,
               download: true
            },
            {
               title: "Actions",
               field: "id",
               width: 100,
               headerSort: false,
               responsive: 0,
               download: false,
               formatter: cell => {
                  const id = cell.getValue();
                  return `
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning" onclick="editBranch(${id})" title="Edit">
                           <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteBranch(${id})" title="Delete">
                           <i class="bi bi-trash"></i>
                        </button>
                     </div>
                  `;
               }
            }
         ]
      });
   }

   function initEventListeners() {
      document.getElementById('addBranchBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('manage_branches')) {
            Alerts.error('You do not have permission to add branches');
            return;
         }
         openBranchModal();
      });

      document.getElementById('saveBranchBtn').addEventListener('click', saveBranch);
   }

   function openBranchModal(branchId = null) {
      isEditMode = !!branchId;
      currentBranchId = branchId;

      document.getElementById('branchForm').reset();
      document.getElementById('branchId').value = '';
      document.getElementById('branchModalTitle').textContent = isEditMode ? 'Edit Branch' : 'Add Branch';

      const modal = new bootstrap.Modal(document.getElementById('branchModal'));
      modal.show();

      if (isEditMode) loadBranchForEdit(branchId);
   }

   async function loadBranchForEdit(branchId) {
      try {
         const branch = await api.get(`branch/view/${branchId}`);
         document.getElementById('branchId').value = branch.BranchID;
         document.getElementById('branchName').value = branch.BranchName;
         document.getElementById('location').value = branch.Location || '';
         document.getElementById('phone').value = branch.Phone || '';
         document.getElementById('email').value = branch.Email || '';
         document.getElementById('description').value = branch.Description || '';
      } catch (error) {
         console.error('Load branch error:', error);
         Alerts.error('Failed to load branch data');
      }
   }

   async function saveBranch() {
      const branchName = document.getElementById('branchName').value.trim();

      if (!branchName) {
         Alerts.warning('Please enter a branch name');
         return;
      }

      const payload = {
         name: branchName,
         location: document.getElementById('location').value.trim() || null,
         phone: document.getElementById('phone').value.trim() || null,
         email: document.getElementById('email').value.trim() || null,
         description: document.getElementById('description').value.trim() || null
      };

      try {
         Alerts.loading('Saving branch...');
         if (isEditMode) {
            await api.put(`branch/update/${currentBranchId}`, payload);
         } else {
            await api.post('branch/create', payload);
         }
         Alerts.closeLoading();
         Alerts.success(isEditMode ? 'Branch updated successfully' : 'Branch created successfully');
         bootstrap.Modal.getInstance(document.getElementById('branchModal')).hide();
         branchesGrid.setData();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save branch error:', error);
         Alerts.handleApiError(error);
      }
   }

   function editBranch(branchId) {
      if (!Auth.hasPermission('manage_branches')) {
         Alerts.error('You do not have permission to edit branches');
         return;
      }
      openBranchModal(branchId);
   }

   async function deleteBranch(branchId) {
      if (!Auth.hasPermission('manage_branches')) {
         Alerts.error('You do not have permission to delete branches');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Branch',
         text: 'Are you sure you want to delete this branch? This action cannot be undone.',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting branch...');
         await api.delete(`branch/delete/${branchId}`);
         Alerts.closeLoading();
         Alerts.success('Branch deleted successfully');
         branchesGrid.setData();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete branch error:', error);
         Alerts.handleApiError(error);
      }
   }
</script>

<?php require_once '../includes/footer.php'; ?>