<?php
$pageTitle = 'Member Milestones';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Member Milestones</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Milestones</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addMilestoneBtn" data-permission="edit_members">
         <i class="bi bi-plus-circle me-2"></i>Add Milestone
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Total Milestones</p>
                     <h3 class="mb-0" id="totalMilestones">0</h3>
                     <small class="text-muted">All recorded</small>
                  </div>
                  <div class="stat-icon bg-primary text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-trophy"></i>
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
                     <p class="text-muted mb-1">Baptisms</p>
                     <h3 class="mb-0" id="totalBaptisms">0</h3>
                     <small class="text-muted">Water baptisms</small>
                  </div>
                  <div class="stat-icon bg-success text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-droplet"></i>
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
                     <p class="text-muted mb-1">Marriages</p>
                     <h3 class="mb-0" id="totalMarriages">0</h3>
                     <small class="text-muted">Weddings</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-heart"></i>
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
                     <p class="text-muted mb-1">This Year</p>
                     <h3 class="mb-0" id="yearMilestones">0</h3>
                     <small class="text-muted">New milestones</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-calendar-check"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Milestones Table -->
   <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
         <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>All Milestones</h5>
         <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" id="filterAll" onclick="filterMilestones('all')">All</button>
            <button class="btn btn-outline-secondary" id="filterBaptism" onclick="filterMilestones('Baptism')">Baptism</button>
            <button class="btn btn-outline-secondary" id="filterConfirmation" onclick="filterMilestones('Confirmation')">Confirmation</button>
            <button class="btn btn-outline-secondary" id="filterMarriage" onclick="filterMilestones('Marriage')">Marriage</button>
            <button class="btn btn-outline-secondary" id="filterDedication" onclick="filterMilestones('Dedication')">Dedication</button>
         </div>
      </div>
      <div class="card-body">
         <div class="table-responsive">
            <table class="table table-hover" id="milestonesTable">
               <thead class="table-light">
                  <tr>
                     <th>Member</th>
                     <th>Milestone Type</th>
                     <th>Date</th>
                     <th>Notes</th>
                     <th style="width: 90px;">Actions</th>
                  </tr>
               </thead>
               <tbody id="milestonesTableBody">
                  <tr>
                     <td colspan="5" class="text-center py-5">
                        <div class="text-muted">
                           <i class="bi bi-trophy fs-1 d-block mb-2"></i>
                           <p>No milestones recorded yet</p>
                           <button class="btn btn-sm btn-primary" onclick="document.getElementById('addMilestoneBtn').click()">
                              <i class="bi bi-plus-circle me-1"></i>Add First Milestone
                           </button>
                        </div>
                     </td>
                  </tr>
               </tbody>
            </table>
         </div>
      </div>
   </div>
</div>
</main>

<!-- Milestone Modal -->
<div class="modal fade" id="milestoneModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="milestoneModalTitle">Add Milestone</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="milestoneForm">
               <input type="hidden" id="milestoneId">
               <div class="mb-3">
                  <label class="form-label">Member <span class="text-danger">*</span></label>
                  <select class="form-select" id="milestoneMember" required>
                     <option value="">Select Member</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Milestone Type <span class="text-danger">*</span></label>
                  <select class="form-select" id="milestoneType" required>
                     <option value="">Select Type</option>
                     <option value="Baptism">Baptism</option>
                     <option value="Confirmation">Confirmation</option>
                     <option value="Marriage">Marriage</option>
                     <option value="Child Dedication">Child Dedication</option>
                     <option value="Ordination">Ordination</option>
                     <option value="First Communion">First Communion</option>
                     <option value="Salvation">Salvation</option>
                     <option value="Other">Other</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="milestoneDate" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" id="milestoneNotes" rows="3" placeholder="Additional details about this milestone"></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveMilestoneBtn">
               <i class="bi bi-check-circle me-1"></i>Save Milestone
            </button>
         </div>
      </div>
   </div>
</div>

<!-- View Milestone Modal -->
<div class="modal fade" id="viewMilestoneModal" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Milestone Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="viewMilestoneContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="editMilestoneFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit Milestone
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let milestones = [];
   let filteredMilestones = [];
   let currentFilter = 'all';
   let currentMilestoneId = null;
   let isEditMode = false;
   let memberChoices = null;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await initPage();
   });

   async function initPage() {
      initEventListeners();
      await loadMembers();
      loadMilestones();
   }

   function initEventListeners() {
      document.getElementById('addMilestoneBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('edit_members')) {
            Alerts.error('You do not have permission to add milestones');
            return;
         }
         openMilestoneModal();
      });

      document.getElementById('saveMilestoneBtn').addEventListener('click', saveMilestone);

      document.getElementById('editMilestoneFromViewBtn').addEventListener('click', () => {
         bootstrap.Modal.getInstance(document.getElementById('viewMilestoneModal')).hide();
         editMilestone(currentMilestoneId);
      });
   }

   async function loadMembers() {
      try {
         const response = await api.get('member/all?limit=1000');
         const members = response?.data?.data || response?.data || [];

         const select = document.getElementById('milestoneMember');
         select.innerHTML = '<option value="">Select Member</option>';
         members.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.MbrID;
            opt.textContent = `${m.MbrFirstName} ${m.MbrFamilyName}`;
            select.appendChild(opt);
         });

         if (memberChoices) memberChoices.destroy();
         memberChoices = new Choices(select, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search members...',
            itemSelectText: ''
         });
      } catch (error) {
         console.error('Load members error:', error);
      }
   }

   function loadMilestones() {
      // Simulated data - in production this would come from an API
      milestones = [];
      filteredMilestones = milestones;
      updateStats();
      renderMilestones();
   }

   function updateStats() {
      document.getElementById('totalMilestones').textContent = milestones.length;
      document.getElementById('totalBaptisms').textContent = milestones.filter(m => m.type === 'Baptism').length;
      document.getElementById('totalMarriages').textContent = milestones.filter(m => m.type === 'Marriage').length;

      const currentYear = new Date().getFullYear();
      const yearCount = milestones.filter(m => new Date(m.date).getFullYear() === currentYear).length;
      document.getElementById('yearMilestones').textContent = yearCount;
   }

   function filterMilestones(type) {
      currentFilter = type;

      // Update button states
      document.querySelectorAll('[id^="filter"]').forEach(btn => btn.classList.remove('active'));
      document.getElementById(`filter${type === 'all' ? 'All' : type}`).classList.add('active');

      if (type === 'all') {
         filteredMilestones = milestones;
      } else {
         filteredMilestones = milestones.filter(m => m.type === type);
      }

      renderMilestones();
   }

   function renderMilestones() {
      const tbody = document.getElementById('milestonesTableBody');

      if (filteredMilestones.length === 0) {
         tbody.innerHTML = `
            <tr>
               <td colspan="5" class="text-center py-5">
                  <div class="text-muted">
                     <i class="bi bi-trophy fs-1 d-block mb-2"></i>
                     <p>${currentFilter === 'all' ? 'No milestones recorded yet' : `No ${currentFilter} milestones found`}</p>
                     ${currentFilter === 'all' ? `
                        <button class="btn btn-sm btn-primary" onclick="document.getElementById('addMilestoneBtn').click()">
                           <i class="bi bi-plus-circle me-1"></i>Add First Milestone
                        </button>
                     ` : ''}
                  </div>
               </td>
            </tr>
         `;
         return;
      }

      tbody.innerHTML = filteredMilestones.map(m => `
         <tr>
            <td class="fw-semibold">${m.memberName}</td>
            <td><span class="badge bg-primary">${m.type}</span></td>
            <td>${new Date(m.date).toLocaleDateString()}</td>
            <td>${m.notes || '-'}</td>
            <td>
               <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-primary" onclick="viewMilestone(${m.id})" title="View">
                     <i class="bi bi-eye"></i>
                  </button>
                  <button class="btn btn-outline-warning" onclick="editMilestone(${m.id})" title="Edit">
                     <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-outline-danger" onclick="deleteMilestone(${m.id})" title="Delete">
                     <i class="bi bi-trash"></i>
                  </button>
               </div>
            </td>
         </tr>
      `).join('');
   }

   function openMilestoneModal(milestoneId = null) {
      isEditMode = !!milestoneId;
      currentMilestoneId = milestoneId;

      document.getElementById('milestoneForm').reset();
      document.getElementById('milestoneId').value = '';
      document.getElementById('milestoneModalTitle').textContent = isEditMode ? 'Edit Milestone' : 'Add Milestone';

      const modal = new bootstrap.Modal(document.getElementById('milestoneModal'));
      modal.show();

      if (isEditMode) loadMilestoneForEdit(milestoneId);
   }

   function loadMilestoneForEdit(milestoneId) {
      const milestone = milestones.find(m => m.id === milestoneId);
      if (!milestone) return;

      document.getElementById('milestoneId').value = milestone.id;
      document.getElementById('milestoneType').value = milestone.type;
      document.getElementById('milestoneDate').value = milestone.date;
      document.getElementById('milestoneNotes').value = milestone.notes || '';

      if (memberChoices) {
         memberChoices.setChoiceByValue(milestone.memberId.toString());
      }
   }

   async function saveMilestone() {
      const memberId = document.getElementById('milestoneMember').value;
      const type = document.getElementById('milestoneType').value;
      const date = document.getElementById('milestoneDate').value;

      if (!memberId || !type || !date) {
         Alerts.warning('Please fill all required fields');
         return;
      }

      // Simulated save - in production this would call an API
      const memberSelect = document.getElementById('milestoneMember');
      const memberName = memberSelect.options[memberSelect.selectedIndex].text;

      const milestone = {
         id: isEditMode ? currentMilestoneId : Date.now(),
         memberId: parseInt(memberId),
         memberName: memberName,
         type: type,
         date: date,
         notes: document.getElementById('milestoneNotes').value.trim() || null
      };

      if (isEditMode) {
         const index = milestones.findIndex(m => m.id === currentMilestoneId);
         if (index !== -1) milestones[index] = milestone;
      } else {
         milestones.push(milestone);
      }

      Alerts.success(isEditMode ? 'Milestone updated successfully' : 'Milestone added successfully');
      bootstrap.Modal.getInstance(document.getElementById('milestoneModal')).hide();

      filterMilestones(currentFilter);
      updateStats();
   }

   function viewMilestone(milestoneId) {
      const milestone = milestones.find(m => m.id === milestoneId);
      if (!milestone) return;

      currentMilestoneId = milestoneId;
      const modal = new bootstrap.Modal(document.getElementById('viewMilestoneModal'));
      modal.show();

      document.getElementById('viewMilestoneContent').innerHTML = `
         <div class="mb-3">
            <div class="text-muted small">Member</div>
            <div class="fw-semibold fs-5">${milestone.memberName}</div>
         </div>
         <div class="mb-3">
            <div class="text-muted small">Milestone Type</div>
            <div><span class="badge bg-primary">${milestone.type}</span></div>
         </div>
         <div class="mb-3">
            <div class="text-muted small">Date</div>
            <div>${new Date(milestone.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
         </div>
         ${milestone.notes ? `
         <div class="mb-3">
            <div class="text-muted small">Notes</div>
            <div>${milestone.notes}</div>
         </div>
         ` : ''}
      `;
   }

   function editMilestone(milestoneId) {
      if (!Auth.hasPermission('edit_members')) {
         Alerts.error('You do not have permission to edit milestones');
         return;
      }
      openMilestoneModal(milestoneId);
   }

   async function deleteMilestone(milestoneId) {
      if (!Auth.hasPermission('edit_members')) {
         Alerts.error('You do not have permission to delete milestones');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Milestone',
         text: 'Are you sure you want to delete this milestone?',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      const index = milestones.findIndex(m => m.id === milestoneId);
      if (index !== -1) {
         milestones.splice(index, 1);
         Alerts.success('Milestone deleted successfully');
         filterMilestones(currentFilter);
         updateStats();
      }
   }

   // Set initial filter state
   document.getElementById('filterAll').classList.add('active');
</script>

<?php require_once '../includes/footer.php'; ?>