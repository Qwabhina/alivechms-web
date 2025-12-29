<?php
$pageTitle = 'Members Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Members</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Members</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addMemberBtn" data-permission="create_members">
         <i class="bi bi-plus-circle me-2"></i>Add Member
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row" id="statsCards">
      <div class="col-12 text-center py-5">
         <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
         </div>
         <p class="text-muted mt-2">Loading member statistics...</p>
      </div>
   </div>

   <!-- Members Table -->
   <div class="card mt-4">
      <div class="card-header d-flex justify-content-between align-items-center">
         <h5 class="mb-0"><i class="bi bi-people me-2"></i>All Members</h5>
         <button class="btn btn-sm btn-outline-primary" onclick="membersGrid.forceRender()">
            <i class="bi bi-arrow-clockwise"></i>
         </button>
      </div>
      <div class="card-body">
         <div id="membersGrid"></div>
      </div>
   </div>
</div>
</main>

<!-- Member Modal with Stepper -->
<div class="modal fade" id="memberModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-xl">
      <div class="modal-content">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title" id="memberModalTitle">Add New Member</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div id="memberStepper">
               <!-- Stepper Header -->
               <div class="stepper mb-4">
                  <div class="stepper-step active" data-step="0">
                     <div class="step-number">1</div>
                     <div class="step-label">Basic Info</div>
                  </div>
                  <div class="stepper-step" data-step="1">
                     <div class="step-number">2</div>
                     <div class="step-label">Contact</div>
                  </div>
                  <div class="stepper-step" data-step="2">
                     <div class="step-number">3</div>
                     <div class="step-label">Personal</div>
                  </div>
                  <div class="stepper-step" data-step="3">
                     <div class="step-number">4</div>
                     <div class="step-label">Family</div>
                  </div>
                  <div class="stepper-step" data-step="4">
                     <div class="step-number">5</div>
                     <div class="step-label">Login</div>
                  </div>
               </div>

               <form id="memberForm">
                  <input type="hidden" id="memberId" name="memberId">

                  <!-- Step 1: Basic Info -->
                  <div class="stepper-content" data-step="0">
                     <div class="row">
                        <div class="col-12 text-center mb-4">
                           <div class="profile-upload-zone" id="profileDropzone">
                              <div class="upload-placeholder" id="uploadPlaceholder">
                                 <i class="bi bi-camera"></i>
                                 <div class="small">Upload Photo</div>
                              </div>
                              <img id="profilePreview" class="d-none" alt="Profile">
                           </div>
                           <input type="file" id="profilePictureInput" accept="image/*" class="d-none">
                           <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-none" id="removePhotoBtn">
                              <i class="bi bi-trash me-1"></i>Remove
                           </button>
                        </div>
                        <div class="col-md-4 mb-3">
                           <label class="form-label">First Name <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="firstName" required>
                        </div>
                        <div class="col-md-4 mb-3">
                           <label class="form-label">Family Name <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="familyName" required>
                        </div>
                        <div class="col-md-4 mb-3">
                           <label class="form-label">Other Names</label>
                           <input type="text" class="form-control" id="otherNames">
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Gender <span class="text-danger">*</span></label>
                           <select class="form-select" id="gender" required>
                              <option value="">Select Gender</option>
                              <option value="Male">Male</option>
                              <option value="Female">Female</option>
                              <option value="Other">Other</option>
                           </select>
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Date of Birth</label>
                           <input type="text" class="form-control" id="dateOfBirth" placeholder="YYYY-MM-DD">
                        </div>
                     </div>
                  </div>

                  <!-- Step 2: Contact Info -->
                  <div class="stepper-content d-none" data-step="1">
                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Email Address <span class="text-danger">*</span></label>
                           <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Residential Address</label>
                           <input type="text" class="form-control" id="address">
                        </div>
                        <div class="col-12 mb-3">
                           <label class="form-label">Phone Numbers</label>
                           <div id="phoneContainer">
                              <div class="phone-row mb-2">
                                 <div class="input-group">
                                    <input type="text" class="form-control phone-input" placeholder="e.g., 0241234567">
                                    <button type="button" class="btn btn-outline-danger remove-phone d-none">
                                       <i class="bi bi-trash"></i>
                                    </button>
                                 </div>
                              </div>
                           </div>
                           <button type="button" class="btn btn-sm btn-outline-primary" id="addPhoneBtn">
                              <i class="bi bi-plus-circle me-1"></i>Add Phone
                           </button>
                        </div>
                     </div>
                  </div>

                  <!-- Step 3: Personal Info -->
                  <div class="stepper-content d-none" data-step="2">
                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Occupation</label>
                           <input type="text" class="form-control" id="occupation">
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Marital Status</label>
                           <select class="form-select" id="maritalStatus">
                              <option value="">Select Status</option>
                              <option value="Single">Single</option>
                              <option value="Married">Married</option>
                              <option value="Divorced">Divorced</option>
                              <option value="Widowed">Widowed</option>
                           </select>
                        </div>
                        <div class="col-md-12 mb-3">
                           <label class="form-label">Highest Education Level</label>
                           <input type="text" class="form-control" id="educationLevel">
                        </div>
                     </div>
                  </div>

                  <!-- Step 4: Family Info -->
                  <div class="stepper-content d-none" data-step="3">
                     <div class="row">
                        <div class="col-md-12 mb-3">
                           <label class="form-label">Family</label>
                           <select class="form-select" id="familySelect">
                              <option value="">No Family</option>
                           </select>
                        </div>
                     </div>
                  </div>

                  <!-- Step 5: Login Info -->
                  <div class="stepper-content d-none" data-step="4">
                     <div class="row">
                        <div class="col-12 mb-3">
                           <div class="alert alert-info">
                              <i class="bi bi-info-circle me-2"></i>
                              Login credentials are optional. You can add them later.
                           </div>
                           <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="enableLogin">
                              <label class="form-check-label" for="enableLogin">
                                 Enable login access for this member
                              </label>
                           </div>
                        </div>
                        <div id="loginFields" class="row d-none">
                           <div class="col-md-6 mb-3">
                              <label class="form-label">Username</label>
                              <input type="text" class="form-control" id="username">
                           </div>
                           <div class="col-md-6 mb-3">
                              <label class="form-label">Password</label>
                              <input type="password" class="form-control" id="password">
                              <small class="text-muted">Min 8 characters</small>
                           </div>
                           <div class="col-md-12 mb-3">
                              <label class="form-label">Role</label>
                              <select class="form-select" id="roleSelect">
                                 <option value="">Select Role</option>
                              </select>
                           </div>
                        </div>
                     </div>
                  </div>
               </form>
            </div>
         </div>
         <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" id="prevStepBtn" disabled>
               <i class="bi bi-arrow-left me-1"></i>Previous
            </button>
            <button type="button" class="btn btn-primary" id="nextStepBtn">
               Next<i class="bi bi-arrow-right ms-1"></i>
            </button>
            <button type="button" class="btn btn-success d-none" id="submitBtn">
               <i class="bi bi-check-circle me-1"></i>Save Member
            </button>
         </div>
      </div>
   </div>
</div>

<!-- View Member Modal with Tabs -->
<div class="modal fade" id="viewMemberModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Member Profile</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewMemberContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading member details...</p>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="editFromViewBtn" data-permission="edit_members">
               <i class="bi bi-pencil me-1"></i>Edit Member
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   // State
   let membersGrid = null;
   let currentStep = 0;
   const totalSteps = 5;
   let currentMemberId = null;
   let isEditMode = false;
   let profilePictureFile = null;
   let familiesData = [];
   let rolesData = [];
   let familyChoices = null;
   let roleChoices = null;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await initPage();
   });

   async function initPage() {
      try {
         // Load dropdown data first (public endpoints)
         await Promise.all([loadFamilies(), loadRoles()]);

         // Initialize components
         initGrid();
         initStepper();
         initDatePickers();
         initEventListeners();

         // Load stats
         loadStats();
      } catch (error) {
         console.error('Init error:', error);
         Alerts.error('Failed to initialize page');
      }
   }

   function renderStatsCards(stats) {
      const cards = [{
            title: 'Total Members',
            value: stats.total || 0,
            change: 'All registered members',
            icon: 'people',
            color: 'primary'
         },
         {
            title: 'Active Members',
            value: stats.active || 0,
            change: 'Currently active',
            icon: 'person-check',
            color: 'success'
         },
         {
            title: 'Inactive Members',
            value: stats.inactive || 0,
            change: 'Marked inactive',
            icon: 'person-dash',
            color: 'danger'
         },
         {
            title: 'New This Month',
            value: stats.newThisMonth || 0,
            change: 'Registered this month',
            icon: 'calendar-plus',
            color: 'warning'
         }
      ];

      const html = cards.map(card => `
         <div class="col-lg-3 col-md-6">
            <div class="card stat-card bg-${card.color} bg-opacity-25 mb-4">
               <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                     <div>
                        <p class="text-muted mb-1">${card.title}</p>
                        <h3 class="mb-0">${card.value}</h3>
                        <small class="text-muted">${card.change}</small>
                     </div>
                     <div class="stat-icon bg-${card.color} text-white text-opacity-50 rounded-circle p-3">
                        <i class="bi bi-${card.icon}"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      `).join('');

      document.getElementById('statsCards').innerHTML = html;
   }

   async function loadStats() {
      try {
         const response = await api.get('member/all?limit=1');
         const total = response?.pagination?.total || 0;
         renderStatsCards({
            total: total,
            active: total,
            inactive: 0,
            newThisMonth: 0
         });
      } catch (error) {
         console.error('Load stats error:', error);
         renderStatsCards({
            total: 0,
            active: 0,
            inactive: 0,
            newThisMonth: 0
         });
      }
   }

   function initGrid() {
      membersGrid = new gridjs.Grid({
         columns: [{
               name: 'Photo',
               width: '50px',
               sort: false,
               formatter: (_, row) => {
                  const photo = row.cells[6].data;
                  const name = row.cells[1].data;
                  if (photo) {
                     return gridjs.html(`<img src="/${photo}" class="member-photo" alt="${name}">`);
                  }
                  const initials = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                  return gridjs.html(`<div class="member-photo-placeholder">${initials}</div>`);
               }
            },
            {
               name: 'Name',
               width: '160px'
            },
            {
               name: 'Gender',
               width: '70px'
            },
            {
               name: 'Phone',
               width: '120px'
            },
            {
               name: 'Email',
               width: '180px'
            },
            {
               name: 'Status',
               width: '80px',
               formatter: (cell) => {
                  const cls = cell === 'Active' ? 'success' : 'secondary';
                  return gridjs.html(`<span class="badge bg-${cls}">${cell}</span>`);
               }
            },
            {
               name: 'photo',
               hidden: true
            },
            {
               name: 'id',
               hidden: true
            },
            {
               name: 'Actions',
               width: '90px',
               sort: false,
               formatter: (_, row) => {
                  const id = row.cells[7].data;
                  return gridjs.html(`
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-outline-primary" onclick="viewMember(${id})" title="View">
                        <i class="bi bi-eye"></i>
                     </button>
                     <button class="btn btn-outline-warning" onclick="editMember(${id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                     </button>
                     <button class="btn btn-outline-danger" onclick="deleteMember(${id})" title="Delete">
                        <i class="bi bi-trash"></i>
                     </button>
                  </div>
               `);
               }
            }
         ],
         server: {
            url: `${Config.API_BASE_URL}/member/all`,
            headers: {
               'Authorization': `Bearer ${Auth.getToken()}`
            },
            then: response => {
               const members = response?.data?.data || response?.data || [];
               // Store total for server-side pagination
               const total = response?.data?.pagination?.total || members.length;
               membersGrid.config.pagination.total = total;
               return members.map(m => [
                  null,
                  `${m.MbrFirstName} ${m.MbrFamilyName}`,
                  m.MbrGender || '-',
                  m.PrimaryPhone || '-',
                  m.MbrEmailAddress || '-',
                  m.MbrMembershipStatus || 'Active',
                  m.MbrProfilePicture,
                  m.MbrID
               ]);
            },
            total: response => response?.data?.pagination?.total || 0
         },
         pagination: {
            limit: 25,
            server: {
               url: (prev, page, limit) => {
                  const pageNum = page + 1;
                  const separator = prev.includes('?') ? '&' : '?';
                  return `${prev}${separator}page=${pageNum}&limit=${limit}`;
               }
            }
         },
         search: {
            server: {
               url: (prev, keyword) => {
                  const separator = prev.includes('?') ? '&' : '?';
                  return `${prev}${separator}search=${encodeURIComponent(keyword)}`;
               }
            }
         },
         sort: true,
         className: {
            container: 'gridjs-container',
            table: 'table table-hover table-striped',
            thead: 'table-light',
            th: 'gridjs-th',
            td: 'gridjs-td',
            footer: 'gridjs-footer'
         },
         style: {
            table: {
               'border-collapse': 'collapse',
               'width': '100%'
            }
         },
         language: {
            search: {
               placeholder: 'Search members...'
            },
            pagination: {
               previous: '← Previous',
               next: 'Next →',
               showing: 'Showing',
               of: 'of',
               to: 'to',
               results: () => 'members'
            },
            loading: 'Loading...',
            noRecordsFound: 'No members found',
            error: 'Error loading data'
         }
      }).render(document.getElementById('membersGrid'));
   }

   function initStepper() {
      updateStepperUI();
   }

   function updateStepperUI() {
      // Update step indicators
      document.querySelectorAll('.stepper-step').forEach((step, idx) => {
         step.classList.remove('active', 'completed');
         if (idx < currentStep) step.classList.add('completed');
         if (idx === currentStep) step.classList.add('active');
      });

      // Update content visibility
      document.querySelectorAll('.stepper-content').forEach((content, idx) => {
         content.classList.toggle('d-none', idx !== currentStep);
      });

      // Update buttons
      document.getElementById('prevStepBtn').disabled = currentStep === 0;
      document.getElementById('nextStepBtn').classList.toggle('d-none', currentStep === totalSteps - 1);
      document.getElementById('submitBtn').classList.toggle('d-none', currentStep !== totalSteps - 1);
   }

   function validateCurrentStep() {
      if (currentStep === 0) {
         const firstName = document.getElementById('firstName').value.trim();
         const familyName = document.getElementById('familyName').value.trim();
         const gender = document.getElementById('gender').value;
         if (!firstName) {
            Alerts.warning('First name is required');
            return false;
         }
         if (!familyName) {
            Alerts.warning('Family name is required');
            return false;
         }
         if (!gender) {
            Alerts.warning('Gender is required');
            return false;
         }
      }
      if (currentStep === 1) {
         const email = document.getElementById('email').value.trim();
         if (!email) {
            Alerts.warning('Email address is required');
            return false;
         }
         if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            Alerts.warning('Please enter a valid email address');
            return false;
         }
      }
      return true;
   }

   function nextStep() {
      if (!validateCurrentStep()) return;
      if (currentStep < totalSteps - 1) {
         currentStep++;
         updateStepperUI();
         // Initialize family select on step 3
         if (currentStep === 3) initFamilySelect();
         if (currentStep === 4) initRoleSelect();
      }
   }

   function prevStep() {
      if (currentStep > 0) {
         currentStep--;
         updateStepperUI();
      }
   }

   function initFamilySelect() {
      const select = document.getElementById('familySelect');
      // Destroy existing Choices instance if any
      if (familyChoices) {
         familyChoices.destroy();
      }
      // Get initial value if editing
      const initialValue = select.getAttribute('data-initial-value') || '';

      // Reset options
      select.innerHTML = '<option value="">No Family</option>';
      familiesData.forEach(f => {
         const opt = document.createElement('option');
         opt.value = f.FamilyID;
         opt.textContent = f.FamilyName;
         if (initialValue && f.FamilyID == initialValue) {
            opt.selected = true;
         }
         select.appendChild(opt);
      });
      // Initialize Choices.js
      familyChoices = new Choices(select, {
         searchEnabled: true,
         searchPlaceholderValue: 'Search families...',
         itemSelectText: '',
         noResultsText: 'No families found',
         noChoicesText: 'No families available',
         shouldSort: false
      });
   }

   function initRoleSelect() {
      const select = document.getElementById('roleSelect');
      // Destroy existing Choices instance if any
      if (roleChoices) {
         roleChoices.destroy();
      }
      // Reset options
      select.innerHTML = '<option value="">Select Role</option>';
      rolesData.forEach(r => {
         const opt = document.createElement('option');
         opt.value = r.RoleID;
         opt.textContent = r.RoleName;
         select.appendChild(opt);
      });
      // Initialize Choices.js
      roleChoices = new Choices(select, {
         searchEnabled: true,
         searchPlaceholderValue: 'Search roles...',
         itemSelectText: '',
         noResultsText: 'No roles found',
         noChoicesText: 'No roles available',
         shouldSort: false
      });
   }

   function initDatePickers() {
      if (typeof flatpickr !== 'undefined') {
         flatpickr('#dateOfBirth', {
            maxDate: 'today',
            dateFormat: 'Y-m-d'
         });
      }
   }

   function initEventListeners() {
      // Stepper navigation
      document.getElementById('nextStepBtn').addEventListener('click', nextStep);
      document.getElementById('prevStepBtn').addEventListener('click', prevStep);
      document.getElementById('submitBtn').addEventListener('click', saveMember);

      // Add member button
      document.getElementById('addMemberBtn').addEventListener('click', () => {
         if (!Auth.hasPermission(Config.PERMISSIONS.CREATE_MEMBERS)) {
            Alerts.error('You do not have permission to create members');
            return;
         }
         openMemberModal();
      });

      // Profile picture upload
      const profileZone = document.getElementById('profileDropzone');
      const profileInput = document.getElementById('profilePictureInput');

      profileZone.addEventListener('click', () => profileInput.click());
      profileZone.addEventListener('dragover', (e) => {
         e.preventDefault();
         profileZone.style.borderColor = 'var(--primary-color)';
      });
      profileZone.addEventListener('dragleave', () => {
         profileZone.style.borderColor = '';
      });
      profileZone.addEventListener('drop', (e) => {
         e.preventDefault();
         profileZone.style.borderColor = '';
         if (e.dataTransfer.files[0]) handleProfileUpload(e.dataTransfer.files[0]);
      });
      profileInput.addEventListener('change', (e) => {
         if (e.target.files[0]) handleProfileUpload(e.target.files[0]);
      });

      document.getElementById('removePhotoBtn').addEventListener('click', () => {
         profilePictureFile = null;
         document.getElementById('profilePreview').classList.add('d-none');
         document.getElementById('uploadPlaceholder').classList.remove('d-none');
         document.getElementById('removePhotoBtn').classList.add('d-none');
      });

      // Phone number management
      document.getElementById('addPhoneBtn').addEventListener('click', addPhoneField);
      document.getElementById('phoneContainer').addEventListener('click', (e) => {
         if (e.target.closest('.remove-phone')) {
            e.target.closest('.phone-row').remove();
         }
      });

      // Login toggle
      document.getElementById('enableLogin').addEventListener('change', (e) => {
         document.getElementById('loginFields').classList.toggle('d-none', !e.target.checked);
      });

      // Edit from view
      document.getElementById('editFromViewBtn').addEventListener('click', () => {
         bootstrap.Modal.getInstance(document.getElementById('viewMemberModal')).hide();
         editMember(currentMemberId);
      });
   }

   function handleProfileUpload(file) {
      if (!file.type.startsWith('image/')) {
         Alerts.error('Please select an image file');
         return;
      }
      if (file.size > 5 * 1024 * 1024) {
         Alerts.error('Image must be less than 5MB');
         return;
      }

      profilePictureFile = file;
      console.log('Profile picture file stored:', file);
      const reader = new FileReader();
      reader.onload = (e) => {
         const preview = document.getElementById('profilePreview');
         preview.src = e.target.result;
         preview.classList.remove('d-none');
         document.getElementById('uploadPlaceholder').classList.add('d-none');
         document.getElementById('removePhotoBtn').classList.remove('d-none');
      };
      reader.readAsDataURL(file);
   }

   function addPhoneField() {
      const container = document.getElementById('phoneContainer');
      const row = document.createElement('div');
      row.className = 'phone-row mb-2';
      row.innerHTML = `
      <div class="input-group">
         <input type="text" class="form-control phone-input" placeholder="e.g., 0241234567">
         <button type="button" class="btn btn-outline-danger remove-phone"><i class="bi bi-trash"></i></button>
      </div>
   `;
      container.appendChild(row);
   }

   async function loadFamilies() {
      try {
         const response = await api.get('family/all?limit=1000');
         familiesData = response?.data || response || [];
      } catch (error) {
         console.error('Load families error:', error);
         familiesData = [];
      }
   }

   async function loadRoles() {
      try {
         const response = await api.get('role/all');
         rolesData = Array.isArray(response) ? response : (response?.data || []);
      } catch (error) {
         console.error('Load roles error:', error);
         rolesData = [];
      }
   }

   function openMemberModal(memberId = null) {
      isEditMode = !!memberId;
      currentMemberId = memberId;
      currentStep = 0;
      profilePictureFile = null;

      // Destroy existing Choices instances
      if (familyChoices) {
         familyChoices.destroy();
         familyChoices = null;
      }
      if (roleChoices) {
         roleChoices.destroy();
         roleChoices = null;
      }

      // Reset form
      document.getElementById('memberForm').reset();
      document.getElementById('memberId').value = '';
      document.getElementById('profilePreview').classList.add('d-none');
      document.getElementById('uploadPlaceholder').classList.remove('d-none');
      document.getElementById('removePhotoBtn').classList.add('d-none');
      document.getElementById('loginFields').classList.add('d-none');
      document.getElementById('enableLogin').checked = false;

      // Reset select elements to plain HTML
      document.getElementById('familySelect').innerHTML = '<option value="">No Family</option>';
      document.getElementById('roleSelect').innerHTML = '<option value="">Select Role</option>';

      // Reset phone fields
      document.getElementById('phoneContainer').innerHTML = `
      <div class="phone-row mb-2">
         <div class="input-group">
            <input type="text" class="form-control phone-input" placeholder="e.g., 0241234567">
            <button type="button" class="btn btn-outline-danger remove-phone d-none"><i class="bi bi-trash"></i></button>
         </div>
      </div>
   `;

      // Reset stepper
      updateStepperUI();

      // Update title
      document.getElementById('memberModalTitle').textContent = isEditMode ? 'Edit Member' : 'Add New Member';

      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('memberModal'));
      modal.show();

      if (isEditMode) loadMemberForEdit(memberId);
   }

   async function loadMemberForEdit(memberId) {
      try {
         Alerts.loading('Loading member...');
         const member = await api.get(`member/view/${memberId}`);
         Alerts.closeLoading();

         document.getElementById('memberId').value = member.MbrID;
         document.getElementById('firstName').value = member.MbrFirstName || '';
         document.getElementById('familyName').value = member.MbrFamilyName || '';
         document.getElementById('otherNames').value = member.MbrOtherNames || '';
         document.getElementById('gender').value = member.MbrGender || '';
         document.getElementById('dateOfBirth').value = member.MbrDateOfBirth || '';
         document.getElementById('email').value = member.MbrEmailAddress || '';
         document.getElementById('address').value = member.MbrResidentialAddress || '';
         document.getElementById('occupation').value = member.MbrOccupation || '';
         document.getElementById('maritalStatus').value = member.MbrMaritalStatus || '';
         document.getElementById('educationLevel').value = member.MbrHighestEducationLevel || '';

         // Store family ID for later when Choices.js is initialized
         document.getElementById('familySelect').setAttribute('data-initial-value', member.FamilyID || '');

         if (member.MbrProfilePicture) {
            const preview = document.getElementById('profilePreview');
            preview.src = `/${member.MbrProfilePicture}`;
            preview.classList.remove('d-none');
            document.getElementById('uploadPlaceholder').classList.add('d-none');
            document.getElementById('removePhotoBtn').classList.remove('d-none');
         }

         if (member.phones && member.phones.length > 0) {
            const container = document.getElementById('phoneContainer');
            container.innerHTML = '';
            member.phones.forEach((phone, idx) => {
               const row = document.createElement('div');
               row.className = 'phone-row mb-2';
               row.innerHTML = `
               <div class="input-group">
                  <input type="text" class="form-control phone-input" value="${phone.PhoneNumber}" placeholder="e.g., 0241234567">
                  <button type="button" class="btn btn-outline-danger remove-phone ${idx === 0 ? 'd-none' : ''}"><i class="bi bi-trash"></i></button>
               </div>
            `;
               container.appendChild(row);
            });
         }
      } catch (error) {
         Alerts.closeLoading();
         console.error('Load member error:', error);
         Alerts.error('Failed to load member data');
      }
   }

   async function saveMember() {
      if (!validateCurrentStep()) return;

      try {
         const phones = [];
         document.querySelectorAll('.phone-input').forEach(input => {
            const val = input.value.trim();
            if (val) phones.push(val);
         });

         const payload = {
            first_name: document.getElementById('firstName').value.trim(),
            family_name: document.getElementById('familyName').value.trim(),
            other_names: document.getElementById('otherNames').value.trim() || null,
            gender: document.getElementById('gender').value,
            date_of_birth: document.getElementById('dateOfBirth').value || null,
            email_address: document.getElementById('email').value.trim(),
            address: document.getElementById('address').value.trim() || null,
            phone_numbers: phones,
            occupation: document.getElementById('occupation').value.trim() || null,
            marital_status: document.getElementById('maritalStatus').value || null,
            education_level: document.getElementById('educationLevel').value.trim() || null,
            family_id: document.getElementById('familySelect').value || null,
            branch_id: 1
         };

         if (!isEditMode && document.getElementById('enableLogin').checked) {
            payload.username = document.getElementById('username').value.trim();
            payload.password = document.getElementById('password').value;
         }

         console.log('Payload being sent:', payload);

         Alerts.loading('Saving member...');

         let result;
         if (isEditMode) {
            result = await api.put(`member/update/${currentMemberId}`, payload);
         } else {
            result = await api.post('member/create', payload);
         }

         console.log('Member save result:', result);

         const newMemberId = result?.mbr_id || currentMemberId;
         console.log('Member ID for photo upload:', newMemberId);

         if (profilePictureFile && newMemberId) {
            try {
               console.log('Uploading profile picture:', profilePictureFile);
               const formData = new FormData();
               formData.append('profile_picture', profilePictureFile);
               const uploadResult = await api.upload(`member/upload-photo/${newMemberId}`, formData);
               console.log('Photo upload result:', uploadResult);
            } catch (uploadError) {
               console.error('Photo upload error:', uploadError);
               Alerts.warning('Member saved but photo upload failed: ' + uploadError.message);
            }
         }

         Alerts.closeLoading();
         Alerts.success(isEditMode ? 'Member updated successfully' : 'Member created successfully');

         bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();
         membersGrid.forceRender();
         loadStats();

      } catch (error) {
         Alerts.closeLoading();
         console.error('Save member error:', error);
         Alerts.handleApiError(error);
      }
   }

   async function viewMember(memberId) {
      currentMemberId = memberId;
      const modal = new bootstrap.Modal(document.getElementById('viewMemberModal'));
      modal.show();

      try {
         const member = await api.get(`member/view/${memberId}`);
         console.log('Member data retrieved:', member);

         const photoHtml = member.MbrProfilePicture ?
            `<img src="/${member.MbrProfilePicture}" class="rounded-circle border border-3" style="width:100px;height:100px;object-fit:cover;">` :
            `<div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white border border-3" style="width:100px;height:100px;font-size:2rem;font-weight:600;">
               ${(member.MbrFirstName?.[0] || '') + (member.MbrFamilyName?.[0] || '')}
            </div>`;

         const statusClass = member.MbrMembershipStatus === 'Active' ? 'success' : 'secondary';
         const phones = member.PhoneNumbers?.join(', ') || member.PrimaryPhone || '-';
         console.log('Phone numbers for display:', phones);
         console.log('Address for display:', member.MbrResidentialAddress);

         document.getElementById('viewMemberContent').innerHTML = `
         <!-- Profile Header -->
         <div class="bg-light p-4 text-center">
            ${photoHtml}
            <h4 class="mt-3 mb-1">${member.MbrFirstName} ${member.MbrFamilyName}</h4>
            ${member.MbrOtherNames ? `<p class="text-muted mb-2">${member.MbrOtherNames}</p>` : ''}
            <span class="badge bg-${statusClass}">${member.MbrMembershipStatus}</span>
         </div>
         
         <!-- Tabs -->
         <ul class="nav nav-tabs px-3 pt-3" role="tablist">
            <li class="nav-item">
               <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-personal">
                  <i class="bi bi-person me-1"></i>Personal
               </button>
            </li>
            <li class="nav-item">
               <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contact">
                  <i class="bi bi-telephone me-1"></i>Contact
               </button>
            </li>
            <li class="nav-item">
               <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-church">
                  <i class="bi bi-building me-1"></i>Church Info
               </button>
            </li>
         </ul>
         
         <div class="tab-content p-4">
            <!-- Personal Tab -->
            <div class="tab-pane fade show active" id="tab-personal">
               <div class="row g-3">
                  <div class="col-md-6">
                     <div class="text-muted small">Gender</div>
                     <div class="fw-medium">${member.MbrGender || '-'}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small">Date of Birth</div>
                     <div class="fw-medium">${member.MbrDateOfBirth || '-'}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small">Marital Status</div>
                     <div class="fw-medium">${member.MbrMaritalStatus || '-'}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small">Occupation</div>
                     <div class="fw-medium">${member.MbrOccupation || '-'}</div>
                  </div>
                  <div class="col-12">
                     <div class="text-muted small">Education Level</div>
                     <div class="fw-medium">${member.MbrHighestEducationLevel || '-'}</div>
                  </div>
               </div>
            </div>
            
            <!-- Contact Tab -->
            <div class="tab-pane fade" id="tab-contact">
               <div class="row g-3">
                  <div class="col-12">
                     <div class="text-muted small">Email Address</div>
                     <div class="fw-medium">
                        ${member.MbrEmailAddress ? `<a href="mailto:${member.MbrEmailAddress}">${member.MbrEmailAddress}</a>` : '-'}
                     </div>
                  </div>
                  <div class="col-12">
                     <div class="text-muted small">Phone Number(s)</div>
                     <div class="fw-medium">${phones}</div>
                  </div>
                  <div class="col-12">
                     <div class="text-muted small">Residential Address</div>
                     <div class="fw-medium">${member.MbrResidentialAddress || '-'}</div>
                  </div>
               </div>
            </div>
            
            <!-- Church Info Tab -->
            <div class="tab-pane fade" id="tab-church">
               <div class="row g-3">
                  <div class="col-md-6">
                     <div class="text-muted small">Member ID</div>
                     <div class="fw-medium">#${member.MbrID}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small">Registration Date</div>
                     <div class="fw-medium">${member.MbrRegistrationDate || '-'}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small">Family</div>
                     <div class="fw-medium">${member.FamilyName || '-'}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small">Membership Status</div>
                     <div class="fw-medium">
                        <span class="badge bg-${statusClass}">${member.MbrMembershipStatus}</span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      `;
      } catch (error) {
         console.error('View member error:', error);
         document.getElementById('viewMemberContent').innerHTML = `
         <div class="text-center text-danger py-5">
            <i class="bi bi-exclamation-circle fs-1"></i>
            <p class="mt-2">Failed to load member details</p>
         </div>
      `;
      }
   }

   function editMember(memberId) {
      if (!Auth.hasPermission(Config.PERMISSIONS.EDIT_MEMBERS)) {
         Alerts.error('You do not have permission to edit members');
         return;
      }
      openMemberModal(memberId);
   }

   async function deleteMember(memberId) {
      if (!Auth.hasPermission(Config.PERMISSIONS.DELETE_MEMBERS)) {
         Alerts.error('You do not have permission to delete members');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Member',
         text: 'Are you sure you want to delete this member? This action cannot be undone.',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting member...');
         await api.delete(`member/delete/${memberId}`);
         Alerts.closeLoading();
         Alerts.success('Member deleted successfully');
         membersGrid.forceRender();
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete member error:', error);
         Alerts.handleApiError(error);
      }
   }
</script>

<?php require_once '../includes/footer.php'; ?>