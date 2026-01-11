/**
 * Members Management - Clean QMGrid Implementation
 * 
 * @package  AliveChMS
 * @version  5.0.0
 * @author   Clean Architecture Rewrite
 */

(function() {
   'use strict';

   // ===================================================================
   // STATE MANAGEMENT
   // ===================================================================
   
   const State = {
      membersTable: null,
      currentStep: 0,
      totalSteps: 3,
      currentMemberId: null,
      isEditMode: false,
      profilePictureFile: null,
      familiesData: [],
      rolesData: [],
      familyChoices: null,
      roleChoices: null,
      autoRefreshInterval: null
   };

   // ===================================================================
   // INITIALIZATION
   // ===================================================================

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      
      await Config.waitForSettings();
      await initPage();
   });

   async function initPage() {
      try {
         // Load reference data in parallel
         await Promise.all([
            loadFamilies(),
            loadRoles()
         ]);

         // Initialize components
         initMembersTable();
         initStepper();
         initDatePickers();
         initEventListeners();

         // Load statistics
         await loadStats();

         // Start auto-refresh after 10 seconds
         setTimeout(() => startAutoRefresh(), 10000);
         
      } catch (error) {
         console.error('Initialization error:', error);
         Alerts.error('Failed to initialize page');
      }
   }

   // ===================================================================
   // TABLE INITIALIZATION - CLEAN QMGRID IMPLEMENTATION
   // ===================================================================

   function initMembersTable() {
      State.membersTable = QMGridHelper.init('#membersTable', {
         url: `${Config.API_BASE_URL}/member/all`,
         pageSize: 25,
         selectable: false, // Disabled - server-side pagination doesn't support cross-page selection
         multiSelect: false,
         exportable: true,
         
         // Configure columns - comprehensive member info
         columns: [
            {
               key: 'MbrProfilePicture',
               title: '',
               width: '55px',
               sortable: false,
               exportable: false,
               render: (value, row) => {
                  const fullName = `${row.MbrFirstName || ''} ${row.MbrFamilyName || ''}`.trim();
                  return QMGridHelper.formatProfilePicture(value, fullName, 42);
               }
            },
            {
               key: 'MbrFullName',
               title: 'Full Name',
               exportable: true,
               render: (value, row) => {
                  const fullName = `${row.MbrFirstName || ''} ${row.MbrOtherNames ? row.MbrOtherNames + ' ' : ''}${row.MbrFamilyName || ''}`.trim();
                  return `<div class="fw-medium">${fullName}</div>`;
               }
            },{
               key: 'PhoneNumbers',
               title: 'Phone',
               sortable: false,
               exportable: true,
               render: (value, row) => {
                  const phones = Array.isArray(value) ? value : 
                                (typeof value === 'string' && value ? value.split(',') : 
                                (row.PrimaryPhone ? [row.PrimaryPhone] : []));
                  if (phones.length === 0) return '<span class="text-muted">-</span>';
                  return `<span>${phones[0]}</span>`;
               }
            },
            {
               key: 'MbrEmailAddress',
               title: 'Email',
               exportable: true,
               render: (value) => {
                  if (!value) return '<span class="text-muted">-</span>';
                  return `<a href="mailto:${value}" class="text-decoration-none">${value}</a>`;
               }
            },
            {
               key: 'MbrGender',
               title: 'Gender',
               width: '80px',
               exportable: true,
               render: (value) => {
                  if (!value) return '<span class="text-muted">-</span>';
                  const icon = value === 'Male' ? 'gender-male' : value === 'Female' ? 'gender-female' : 'gender-ambiguous';
                  return `<i class="bi bi-${icon} me-1"></i>${value}`;
               }
            },
            {
               key: 'MbrDateOfBirth',
               title: 'Age',
               width: '60px',
               exportable: true,
               render: (value) => {
                  if (!value) return '<span class="text-muted">-</span>';
                  try {
                     const birthDate = new Date(value);
                     const age = Math.floor((new Date() - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
                     return age > 0 ? `${age}` : '-';
                  } catch (e) {
                     return '-';
                  }
               }
            },
            {
               key: 'MbrResidentialAddress',
               title: 'Address',
               width: '100px',
               exportable: true,
               render: (value) => {
                  if (!value) return '<span class="text-muted">-</span>';
                  return `<span>${value}</span>`;
               }
            },
            {
               key: 'MbrMembershipStatus',
               title: 'Status',
               width: '85px',
               exportable: true,
               render: (value) => {
                  if (!value) return '-';
                  const color = value === 'Active' ? 'success' : 'secondary';
                  return `<span class="badge bg-${color}">${value}</span>`;
               }
            },
            {
               key: 'MbrRegistrationDate',
               title: 'Joined',
               width: '100px',
               exportable: true,
               render: (value) => QMGridHelper.formatDate(value, 'short')
            },
            {
               key: 'MbrID',
               title: 'Actions',
               width: '130px',
               sortable: false,
               exportable: false,
               render: (value, row) => {
                  const canView = Auth.hasPermission(Config.PERMISSIONS.VIEW_MEMBERS);
                  const canEdit = Auth.hasPermission(Config.PERMISSIONS.EDIT_MEMBERS);
                  const canDelete = Auth.hasPermission(Config.PERMISSIONS.DELETE_MEMBERS);
                  
                  let html = '<div class="btn-group btn-group-sm" role="group">';
                  
                  if (canView) {
                     html += `<button class="btn btn-primary btn-sm" onclick="viewMember(${value})" title="View">
                        <i class="bi bi-eye"></i>
                     </button>`;
                  }
                  
                  if (canEdit) {
                     html += `<button class="btn btn-warning btn-sm" onclick="editMember(${value})" title="Edit">
                        <i class="bi bi-pencil"></i>
                     </button>`;
                  }
                  
                  if (canDelete) {
                     html += `<button class="btn btn-danger btn-sm" onclick="deleteMember(${value})" title="Delete">
                        <i class="bi bi-trash"></i>
                     </button>`;
                  }
                  
                  html += '</div>';
                  return html;
               }
            }
         ],

         // Export configuration
         exportOptions: {
            filename: 'church-members',
            dateFormat: 'DD/MM/YYYY',
            includeHeaders: true
         },

         // Callbacks
         onDataLoaded: (data) => {
            console.log(`✓ Loaded ${data.data.length} of ${data.total} members`);
            updateMemberCount(data.total);
         },

         onError: (error) => {
            console.error('✗ Failed to load members:', error);
            Alerts.error('Failed to load members. Please try again.');
         }
      });

      console.log('✓ Members table initialized successfully');
   }

   // ===================================================================
   // STATISTICS
   // ===================================================================

   async function loadStats() {
      try {
         const response = await api.get('member/stats');
         const stats = response?.data || response || {};
         
         renderStatsCards({
            total: stats.total || 0,
            active: stats.active || 0,
            inactive: stats.inactive || 0,
            newThisMonth: stats.new_this_month || 0
         });

         // Render distribution charts
         renderGenderChart(stats.gender_distribution || {});
         renderAgeChart(stats.age_distribution || {});
      } catch (error) {
         console.error('Load stats error:', error);
         // Fallback to basic count
         try {
            const fallback = await api.get('member/all?limit=1');
            const total = fallback?.pagination?.total || 0;
            renderStatsCards({
               total: total,
               active: total,
               inactive: 0,
               newThisMonth: 0
            });
            renderGenderChart({});
            renderAgeChart({});
         } catch (e) {
            renderStatsCards({
               total: 0,
               active: 0,
               inactive: 0,
               newThisMonth: 0
            });
            renderGenderChart({});
            renderAgeChart({});
         }
      }
   }

   function renderStatsCards(stats) {
      const cards = [
         {
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
                        <h3 class="mb-0">${card.value.toLocaleString()}</h3>
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

   // Chart instances for cleanup
   let genderChartInstance = null;
   let ageChartInstance = null;

   function renderGenderChart(genderData) {
      const canvas = document.getElementById('genderChart');
      if (!canvas) return;

      // Destroy existing chart
      if (genderChartInstance) {
         genderChartInstance.destroy();
      }

      const labels = Object.keys(genderData);
      const data = Object.values(genderData);

      if (labels.length === 0) {
         canvas.parentElement.innerHTML = '<p class="text-muted text-center">No gender data available</p>';
         return;
      }

      const colors = {
         'Male': '#4e73df',
         'Female': '#e74a3b',
         'Other': '#f6c23e',
         'Unknown': '#858796'
      };

      genderChartInstance = new Chart(canvas, {
         type: 'doughnut',
         data: {
            labels: labels,
            datasets: [{
               data: data,
               backgroundColor: labels.map(l => colors[l] || '#858796'),
               borderWidth: 2,
               borderColor: '#fff'
            }]
         },
         options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
               legend: {
                  position: 'bottom',
                  labels: {
                     padding: 15,
                     usePointStyle: true
                  }
               },
               tooltip: {
                  callbacks: {
                     label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.raw / total) * 100).toFixed(1);
                        return `${context.label}: ${context.raw} (${percentage}%)`;
                     }
                  }
               }
            },
            cutout: '60%'
         }
      });
   }

   function renderAgeChart(ageData) {
      const canvas = document.getElementById('ageChart');
      if (!canvas) return;

      // Destroy existing chart
      if (ageChartInstance) {
         ageChartInstance.destroy();
      }

      // Define order for age groups
      const ageOrder = ['Under 18', '18-30', '31-45', '46-60', 'Over 60', 'Unknown'];
      const labels = ageOrder.filter(age => ageData[age] !== undefined);
      const data = labels.map(age => ageData[age] || 0);

      if (labels.length === 0 || data.every(d => d === 0)) {
         canvas.parentElement.innerHTML = '<p class="text-muted text-center">No age data available</p>';
         return;
      }

      const colors = ['#36b9cc', '#1cc88a', '#4e73df', '#f6c23e', '#e74a3b', '#858796'];

      ageChartInstance = new Chart(canvas, {
         type: 'bar',
         data: {
            labels: labels,
            datasets: [{
               label: 'Members',
               data: data,
               backgroundColor: colors.slice(0, labels.length),
               borderRadius: 4,
               borderSkipped: false
            }]
         },
         options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
               legend: {
                  display: false
               },
               tooltip: {
                  callbacks: {
                     label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.raw / total) * 100).toFixed(1);
                        return `${context.raw} members (${percentage}%)`;
                     }
                  }
               }
            },
            scales: {
               y: {
                  beginAtZero: true,
                  ticks: {
                     stepSize: 1
                  },
                  grid: {
                     display: true,
                     drawBorder: false
                  }
               },
               x: {
                  grid: {
                     display: false
                  }
               }
            }
         }
      });
   }

   function updateMemberCount(total) {
      const countElement = document.getElementById('total-members-count');
      if (countElement) {
         countElement.textContent = total.toLocaleString();
      }
   }

   // ===================================================================
   // STEPPER COMPONENT
   // ===================================================================

   function initStepper() {
      updateStepperUI();
   }

   function updateStepperUI() {
      // Update step indicators
      document.querySelectorAll('.stepper-step').forEach((step, idx) => {
         step.classList.remove('active', 'completed');
         if (idx < State.currentStep) step.classList.add('completed');
         if (idx === State.currentStep) step.classList.add('active');
      });

      // Update content visibility
      document.querySelectorAll('.stepper-content').forEach((content, idx) => {
         content.classList.toggle('d-none', idx !== State.currentStep);
      });

      // Update buttons
      const prevBtn = document.getElementById('prevStepBtn');
      const nextBtn = document.getElementById('nextStepBtn');
      const submitBtn = document.getElementById('submitBtn');

      if (prevBtn) prevBtn.disabled = State.currentStep === 0;
      if (nextBtn) nextBtn.classList.toggle('d-none', State.currentStep === State.totalSteps - 1);
      if (submitBtn) submitBtn.classList.toggle('d-none', State.currentStep !== State.totalSteps - 1);
   }

   function validateCurrentStep() {
      // Step 0: Basic Info
      if (State.currentStep === 0) {
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

      // Step 1: Contact Info
      if (State.currentStep === 1) {
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

      if (State.currentStep < State.totalSteps - 1) {
         State.currentStep++;
         updateStepperUI();

         // Initialize selects when reaching their steps
         // Step 1 now has Family select (was step 3)
         if (State.currentStep === 1) initFamilySelect();
         // Step 2 now has Role select (was step 4)
         if (State.currentStep === 2) initRoleSelect();
      }
   }

   function prevStep() {
      if (State.currentStep > 0) {
         State.currentStep--;
         updateStepperUI();
      }
   }

   // ===================================================================
   // FORM COMPONENTS
   // ===================================================================

   function initDatePickers() {
      if (typeof flatpickr !== 'undefined') {
         flatpickr('#dateOfBirth', {
            maxDate: 'today',
            dateFormat: 'Y-m-d',
            allowInput: true
         });
      }
   }

   function initFamilySelect() {
      const select = document.getElementById('familySelect');
      
      if (State.familyChoices) {
         State.familyChoices.destroy();
      }

      const initialValue = select.getAttribute('data-initial-value') || '';

      select.innerHTML = '<option value="">No Family</option>';
      State.familiesData.forEach(f => {
         const opt = document.createElement('option');
         opt.value = f.FamilyID;
         opt.textContent = f.FamilyName;
         if (initialValue && f.FamilyID == initialValue) {
            opt.selected = true;
         }
         select.appendChild(opt);
      });

      State.familyChoices = new Choices(select, {
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
      
      if (State.roleChoices) {
         State.roleChoices.destroy();
      }

      select.innerHTML = '<option value="">Select Role</option>';
      State.rolesData.forEach(r => {
         const opt = document.createElement('option');
         opt.value = r.RoleID;
         opt.textContent = r.RoleName;
         select.appendChild(opt);
      });

      State.roleChoices = new Choices(select, {
         searchEnabled: true,
         searchPlaceholderValue: 'Search roles...',
         itemSelectText: '',
         noResultsText: 'No roles found',
         noChoicesText: 'No roles available',
         shouldSort: false
      });
   }

   function initEventListeners() {
      // Stepper navigation
      document.getElementById('nextStepBtn')?.addEventListener('click', nextStep);
      document.getElementById('prevStepBtn')?.addEventListener('click', prevStep);
      document.getElementById('submitBtn')?.addEventListener('click', saveMember);

      // Add member button
      document.getElementById('addMemberBtn')?.addEventListener('click', () => {
         if (!Auth.hasPermission(Config.PERMISSIONS.CREATE_MEMBERS)) {
            Alerts.error('You do not have permission to create members');
            return;
         }
         openMemberModal();
      });

      // Filters
      document.getElementById('applyMemberFilters')?.addEventListener('click', applyMemberFilters);
      document.getElementById('clearMemberFilters')?.addEventListener('click', clearMemberFilters);

      // Export buttons
      document.getElementById('exportSelectedMembers')?.addEventListener('click', exportSelectedMembers);
      document.getElementById('exportAllMembers')?.addEventListener('click', exportAllMembers);
      document.getElementById('printMemberList')?.addEventListener('click', printMemberList);

      // Refresh button
      document.getElementById('refreshMemberGrid')?.addEventListener('click', refreshMemberGrid);

      // Clear selection
      document.getElementById('clearMemberSelection')?.addEventListener('click', clearMemberSelection);

      // Profile picture upload
      setupProfilePictureUpload();

      // Phone number management
      document.getElementById('addPhoneBtn')?.addEventListener('click', addPhoneField);
      document.getElementById('phoneContainer')?.addEventListener('click', (e) => {
         if (e.target.closest('.remove-phone')) {
            e.target.closest('.phone-row').remove();
         }
      });

      // Login toggle
      document.getElementById('enableLogin')?.addEventListener('change', (e) => {
         document.getElementById('loginFields').classList.toggle('d-none', !e.target.checked);
         document.getElementById('noLoginMessage')?.classList.toggle('d-none', e.target.checked);
      });

      // Password visibility toggle
      document.getElementById('togglePassword')?.addEventListener('click', () => {
         const passwordInput = document.getElementById('password');
         const toggleBtn = document.getElementById('togglePassword');
         if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.innerHTML = '<i class="bi bi-eye-slash"></i>';
         } else {
            passwordInput.type = 'password';
            toggleBtn.innerHTML = '<i class="bi bi-eye"></i>';
         }
      });

      // Edit from view modal
      document.getElementById('editFromViewBtn')?.addEventListener('click', () => {
         bootstrap.Modal.getInstance(document.getElementById('viewMemberModal')).hide();
         editMember(State.currentMemberId);
      });
   }

   function setupProfilePictureUpload() {
      const profileZone = document.getElementById('profileDropzone');
      const profileInput = document.getElementById('profilePictureInput');
      const removeBtn = document.getElementById('removePhotoBtn');

      if (!profileZone || !profileInput) return;

      profileZone.addEventListener('click', () => profileInput.click());

      profileZone.addEventListener('dragover', (e) => {
         e.preventDefault();
         profileZone.style.borderColor = 'var(--bs-primary)';
      });

      profileZone.addEventListener('dragleave', () => {
         profileZone.style.borderColor = '';
      });

      profileZone.addEventListener('drop', (e) => {
         e.preventDefault();
         profileZone.style.borderColor = '';
         if (e.dataTransfer.files[0]) {
            handleProfileUpload(e.dataTransfer.files[0]);
         }
      });

      profileInput.addEventListener('change', (e) => {
         if (e.target.files[0]) {
            handleProfileUpload(e.target.files[0]);
         }
      });

      removeBtn?.addEventListener('click', () => {
         State.profilePictureFile = null;
         document.getElementById('profilePreview').classList.add('d-none');
         document.getElementById('uploadPlaceholder').classList.remove('d-none');
         document.getElementById('removePhotoBtn').classList.add('d-none');
         
         if (State.isEditMode) {
            document.getElementById('profilePreview').setAttribute('data-removed', 'true');
         }
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

      State.profilePictureFile = file;
      console.log('Profile picture file stored:', file.name);

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
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="text" class="form-control phone-input" placeholder="e.g., 0241234567">
            <button type="button" class="btn btn-outline-danger remove-phone">
               <i class="bi bi-trash"></i>
            </button>
         </div>
      `;
      container.appendChild(row);
   }

   // ===================================================================
   // DATA LOADING
   // ===================================================================

   async function loadFamilies() {
      try {
         const response = await api.get('family/all?limit=1000');
         State.familiesData = response?.data || response || [];
         console.log(`✓ Loaded ${State.familiesData.length} families`);
      } catch (error) {
         console.error('Load families error:', error);
         State.familiesData = [];
      }
   }

   async function loadRoles() {
      try {
         const response = await api.get('role/all');
         State.rolesData = Array.isArray(response) ? response : (response?.data || []);
         console.log(`✓ Loaded ${State.rolesData.length} roles`);
      } catch (error) {
         console.error('Load roles error:', error);
         State.rolesData = [];
      }
   }

   // ===================================================================
   // MEMBER MODAL MANAGEMENT
   // ===================================================================

   function openMemberModal(memberId = null) {
      State.isEditMode = !!memberId;
      State.currentMemberId = memberId;
      State.currentStep = 0;
      State.profilePictureFile = null;

      if (State.familyChoices) {
         State.familyChoices.destroy();
         State.familyChoices = null;
      }
      if (State.roleChoices) {
         State.roleChoices.destroy();
         State.roleChoices = null;
      }

      document.getElementById('memberForm').reset();
      document.getElementById('memberId').value = '';
      document.getElementById('profilePreview').classList.add('d-none');
      document.getElementById('profilePreview').removeAttribute('data-removed');
      document.getElementById('uploadPlaceholder').classList.remove('d-none');
      document.getElementById('removePhotoBtn').classList.add('d-none');
      document.getElementById('loginFields').classList.add('d-none');
      document.getElementById('enableLogin').checked = false;
      document.getElementById('noLoginMessage')?.classList.remove('d-none');

      document.getElementById('familySelect').innerHTML = '<option value="">No Family Assignment</option>';
      document.getElementById('roleSelect').innerHTML = '<option value="">Select a role</option>';

      document.getElementById('phoneContainer').innerHTML = `
         <div class="phone-row mb-2">
            <div class="input-group">
               <span class="input-group-text"><i class="bi bi-telephone"></i></span>
               <input type="text" class="form-control phone-input" placeholder="e.g., 0241234567">
               <span class="input-group-text bg-success text-white" title="Primary">
                  <i class="bi bi-star-fill" style="font-size: 0.7rem;"></i>
               </span>
            </div>
         </div>
      `;

      updateStepperUI();

      document.getElementById('memberModalTitle').innerHTML = State.isEditMode 
         ? '<i class="bi bi-pencil-square me-2"></i>Edit Member' 
         : '<i class="bi bi-person-plus me-2"></i>Add New Member';

      const modal = new bootstrap.Modal(document.getElementById('memberModal'));
      modal.show();

      if (State.isEditMode) {
         loadMemberForEdit(memberId);
      }
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

         document.getElementById('familySelect').setAttribute('data-initial-value', member.FamilyID || '');

         if (member.MbrProfilePicture) {
            const preview = document.getElementById('profilePreview');
            preview.src = `/public/${member.MbrProfilePicture}`;
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
               if (idx === 0) {
                  // First phone is primary
                  row.innerHTML = `
                     <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" class="form-control phone-input" value="${phone.PhoneNumber}" placeholder="e.g., 0241234567">
                        <span class="input-group-text bg-success text-white" title="Primary">
                           <i class="bi bi-star-fill" style="font-size: 0.7rem;"></i>
                        </span>
                     </div>
                  `;
               } else {
                  row.innerHTML = `
                     <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" class="form-control phone-input" value="${phone.PhoneNumber}" placeholder="e.g., 0241234567">
                        <button type="button" class="btn btn-outline-danger remove-phone">
                           <i class="bi bi-trash"></i>
                        </button>
                     </div>
                  `;
               }
               container.appendChild(row);
            });
         }
      } catch (error) {
         Alerts.closeLoading();
         console.error('Load member error:', error);
         Alerts.error('Failed to load member data');
      }
   }

   // ===================================================================
   // SAVE MEMBER (CREATE/UPDATE)
   // ===================================================================

   async function saveMember() {
      if (!validateCurrentStep()) return;

      try {
         const phones = [];
         document.querySelectorAll('.phone-input').forEach(input => {
            const val = input.value.trim();
            if (val) phones.push(val);
         });

         const formData = new FormData();
         
         formData.append('first_name', document.getElementById('firstName').value.trim());
         formData.append('family_name', document.getElementById('familyName').value.trim());
         formData.append('other_names', document.getElementById('otherNames').value.trim() || '');
         formData.append('gender', document.getElementById('gender').value);
         formData.append('date_of_birth', document.getElementById('dateOfBirth').value || '');
         formData.append('email_address', document.getElementById('email').value.trim());
         formData.append('address', document.getElementById('address').value.trim() || '');
         formData.append('occupation', document.getElementById('occupation').value.trim() || '');
         formData.append('marital_status', document.getElementById('maritalStatus').value || '');
         formData.append('education_level', document.getElementById('educationLevel').value.trim() || '');
         formData.append('family_id', document.getElementById('familySelect').value || '');
         formData.append('branch_id', '1');
         formData.append('phone_numbers', JSON.stringify(phones));
         
         if (!State.isEditMode && document.getElementById('enableLogin').checked) {
            formData.append('username', document.getElementById('username').value.trim());
            formData.append('password', document.getElementById('password').value);
         }
         
         if (State.profilePictureFile) {
            formData.append('profile_picture', State.profilePictureFile);
            console.log('Profile picture added:', State.profilePictureFile.name);
         } else if (State.isEditMode && document.getElementById('profilePreview').getAttribute('data-removed') === 'true') {
            formData.append('remove_profile_picture', 'true');
            console.log('Profile picture marked for removal');
         }
         
         if (State.isEditMode) {
            formData.append('member_id', State.currentMemberId);
         }

         console.log('Saving member with', Array.from(formData.keys()).length, 'fields');

         Alerts.loading(State.isEditMode ? 'Updating member...' : 'Creating member...');

         let result;
         if (State.isEditMode) {
            result = await api.upload(`member/update/${State.currentMemberId}`, formData);
         } else {
            result = await api.upload('member/create', formData);
         }

         Alerts.closeLoading();
         Alerts.success(State.isEditMode ? 'Member updated successfully' : 'Member created successfully');

         bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();
         QMGridHelper.reload(State.membersTable);
         loadStats();

      } catch (error) {
         Alerts.closeLoading();
         console.error('Save member error:', error);
         Alerts.handleApiError(error);
      }
   }

   // ===================================================================
   // MEMBER ACTIONS
   // ===================================================================

   async function viewMember(memberId) {
      State.currentMemberId = memberId;
      const modal = new bootstrap.Modal(document.getElementById('viewMemberModal'));
      modal.show();

      try {
         const member = await api.get(`member/view/${memberId}`);
         renderMemberView(member);
      } catch (error) {
         console.error('View member error:', error);
         document.getElementById('viewMemberContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load member details</p>
               <button class="btn btn-outline-danger btn-sm" onclick="viewMember(${memberId})">
                  <i class="bi bi-arrow-clockwise me-1"></i>Retry
               </button>
            </div>
         `;
      }
   }

   function renderMemberView(member) {
      const photoHtml = member.MbrProfilePicture
         ? `<img src="/public/${member.MbrProfilePicture}" class="rounded-circle border border-4 border-white shadow" style="width:120px;height:120px;object-fit:cover;" alt="Profile">`
         : `<div class="rounded-circle bg-gradient d-flex align-items-center justify-content-center text-white border border-4 border-white shadow" style="width:120px;height:120px;font-size:2.5rem;font-weight:700;background:linear-gradient(135deg, var(--bs-primary) 0%, #8b5cf6 100%);">
               ${(member.MbrFirstName?.[0] || '') + (member.MbrFamilyName?.[0] || '')}
            </div>`;

      const statusClass = member.MbrMembershipStatus === 'Active' ? 'success' : 'secondary';
      
      // Handle phone numbers
      let phones = [];
      if (Array.isArray(member.PhoneNumbers)) {
         phones = member.PhoneNumbers;
      } else if (typeof member.PhoneNumbers === 'string' && member.PhoneNumbers) {
         phones = member.PhoneNumbers.split(',');
      } else if (member.PrimaryPhone) {
         phones = [member.PrimaryPhone];
      }
      const phoneDisplay = phones.length > 0 ? phones.join(', ') : 'Not provided';

      // Calculate age
      let ageDisplay = 'Not provided';
      let dobDisplay = 'Not provided';
      if (member.MbrDateOfBirth) {
         const dob = new Date(member.MbrDateOfBirth);
         dobDisplay = dob.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
         const today = new Date();
         let age = today.getFullYear() - dob.getFullYear();
         const monthDiff = today.getMonth() - dob.getMonth();
         if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
         }
         ageDisplay = `${age} years old`;
      }

      // Format registration date
      const joinedDate = member.MbrRegistrationDate 
         ? new Date(member.MbrRegistrationDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
         : 'Unknown';

      const fullName = `${member.MbrFirstName || ''} ${member.MbrOtherNames ? member.MbrOtherNames + ' ' : ''}${member.MbrFamilyName || ''}`.trim();

      document.getElementById('viewMemberContent').innerHTML = `
         <div class="member-profile-printable">
            <!-- Profile Header -->
            <div class="profile-header text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
               <div class="d-inline-block position-relative mb-3">
                  ${photoHtml}
                  <span class="position-absolute bottom-0 end-0 badge bg-${statusClass} rounded-pill px-2 py-1" style="font-size:0.7rem;">
                     ${member.MbrMembershipStatus || 'Unknown'}
                  </span>
               </div>
               <h3 class="text-white mb-1 fw-bold">${fullName}</h3>
               <p class="text-white-50 mb-2">Member ID: ${member.MbrID}</p>
               <div class="d-flex justify-content-center gap-2 flex-wrap">
                  <span class="badge bg-white bg-opacity-25 text-white px-3 py-2">
                     <i class="bi bi-calendar-check me-1"></i>Joined ${joinedDate}
                  </span>
                  ${member.MbrGender ? `<span class="badge bg-white bg-opacity-25 text-white px-3 py-2">
                     <i class="bi bi-gender-${member.MbrGender === 'Male' ? 'male' : member.MbrGender === 'Female' ? 'female' : 'ambiguous'} me-1"></i>${member.MbrGender}
                  </span>` : ''}
               </div>
            </div>
            
            <!-- Quick Actions (hidden in print) -->
            <div class="px-4 py-3 bg-light border-bottom d-print-none">
               <div class="row g-2">
                  <div class="col-4">
                     <a href="mailto:${member.MbrEmailAddress || ''}" class="btn btn-outline-primary btn-sm w-100 ${!member.MbrEmailAddress ? 'disabled' : ''}">
                        <i class="bi bi-envelope me-1"></i>Email
                     </a>
                  </div>
                  <div class="col-4">
                     <a href="tel:${phones[0] || ''}" class="btn btn-outline-success btn-sm w-100 ${phones.length === 0 ? 'disabled' : ''}">
                        <i class="bi bi-telephone me-1"></i>Call
                     </a>
                  </div>
                  <div class="col-4">
                     <button class="btn btn-outline-secondary btn-sm w-100" onclick="printMemberProfile()">
                        <i class="bi bi-printer me-1"></i>Print
                     </button>
                  </div>
               </div>
            </div>
            
            <!-- Profile Content -->
            <div class="p-4">
               <!-- Personal Information Section -->
               <div class="mb-4">
                  <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">
                     <i class="bi bi-person-badge me-2"></i>Personal Information
                  </h6>
                  <div class="row g-3">
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Full Name</span>
                           <span class="info-value fw-medium">${fullName}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Gender</span>
                           <span class="info-value fw-medium">${member.MbrGender || 'Not provided'}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Date of Birth</span>
                           <span class="info-value fw-medium">${dobDisplay}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Age</span>
                           <span class="info-value fw-medium">${ageDisplay}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Marital Status</span>
                           <span class="info-value fw-medium">${member.MbrMaritalStatus || 'Not provided'}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Occupation</span>
                           <span class="info-value fw-medium">${member.MbrOccupation || 'Not provided'}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Education Level</span>
                           <span class="info-value fw-medium">${member.MbrHighestEducationLevel || 'Not provided'}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Membership Status</span>
                           <span class="info-value">
                              <span class="badge bg-${statusClass}">${member.MbrMembershipStatus || 'Unknown'}</span>
                           </span>
                        </div>
                     </div>
                  </div>
               </div>

               <!-- Contact Information Section -->
               <div class="mb-4">
                  <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">
                     <i class="bi bi-telephone me-2"></i>Contact Information
                  </h6>
                  <div class="row g-3">
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Email Address</span>
                           <span class="info-value fw-medium">
                              ${member.MbrEmailAddress ? `<a href="mailto:${member.MbrEmailAddress}" class="text-decoration-none">${member.MbrEmailAddress}</a>` : 'Not provided'}
                           </span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Phone Number(s)</span>
                           <span class="info-value fw-medium">${phoneDisplay}</span>
                        </div>
                     </div>
                     <div class="col-12">
                        <div class="info-item">
                           <span class="info-label text-muted small">Residential Address</span>
                           <span class="info-value fw-medium">${member.MbrResidentialAddress || 'Not provided'}</span>
                        </div>
                     </div>
                  </div>
               </div>

               <!-- Church Information Section -->
               <div class="mb-4">
                  <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">
                     <i class="bi bi-building me-2"></i>Church Information
                  </h6>
                  <div class="row g-3">
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Family</span>
                           <span class="info-value fw-medium">${member.FamilyName || 'No family assigned'}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Registration Date</span>
                           <span class="info-value fw-medium">${joinedDate}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Member ID</span>
                           <span class="info-value fw-medium">${member.MbrID}</span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            
            <!-- Print Footer (only visible in print) -->
            <div class="d-none d-print-block text-center py-3 border-top mt-4">
               <small class="text-muted">Printed on ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</small>
            </div>
         </div>
         
         <style>
            .info-item {
               display: flex;
               flex-direction: column;
               padding: 0.5rem 0;
            }
            .info-label {
               font-size: 0.75rem;
               text-transform: uppercase;
               letter-spacing: 0.5px;
               margin-bottom: 0.25rem;
            }
            .info-value {
               font-size: 0.95rem;
            }
            @media print {
               .member-profile-printable {
                  -webkit-print-color-adjust: exact !important;
                  print-color-adjust: exact !important;
               }
               .profile-header {
                  background: #667eea !important;
                  -webkit-print-color-adjust: exact !important;
               }
               .modal-footer, .btn-close, .d-print-none {
                  display: none !important;
               }
               .modal-dialog {
                  max-width: 100% !important;
                  margin: 0 !important;
               }
               .modal-content {
                  border: none !important;
                  box-shadow: none !important;
               }
            }
         </style>
      `;
   }

   // Print member profile
   window.printMemberProfile = function() {
      const printContent = document.querySelector('.member-profile-printable');
      if (printContent) {
         const printWindow = window.open('', '_blank');
         printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
               <title>Member Profile</title>
               <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
               <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
               <style>
                  body { padding: 20px; }
                  .info-item { display: flex; flex-direction: column; padding: 0.5rem 0; }
                  .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem; color: #6c757d; }
                  .info-value { font-size: 0.95rem; }
                  .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                  .d-print-none { display: none !important; }
                  @media print {
                     body { padding: 0; }
                     .profile-header { background: #667eea !important; }
                  }
               </style>
            </head>
            <body>
               ${printContent.outerHTML}
            </body>
            </html>
         `);
         printWindow.document.close();
         printWindow.onload = function() {
            printWindow.print();
            printWindow.close();
         };
      }
   };

   function editMember(memberId) {
      if (!Auth.hasPermission(Config.PERMISSIONS.EDIT_MEMBERS)) {
         Alerts.error('You do not have permission to edit members');
         return;
      }
      openMemberModal(memberId);
   }

   function viewMemberProfile(memberId) {
      viewMember(memberId);
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
         QMGridHelper.reload(State.membersTable);
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete member error:', error);
         Alerts.handleApiError(error);
      }
   }

   // ===================================================================
   // TABLE ACTIONS
   // ===================================================================

   function searchMembers(searchTerm) {
      if (State.membersTable) {
         QMGridHelper.search(State.membersTable, searchTerm);
      }
   }

   function applyMemberFilters() {
      const filters = {};
      
      const statusFilter = document.getElementById('statusFilter');
      const familyFilter = document.getElementById('familyFilter');
      const genderFilter = document.getElementById('genderFilter');
      const dateFromFilter = document.getElementById('dateFromFilter');
      const dateToFilter = document.getElementById('dateToFilter');
      
      if (statusFilter?.value) filters.status = statusFilter.value;
      if (familyFilter?.value) filters.family_id = familyFilter.value;
      if (genderFilter?.value) filters.gender = genderFilter.value;
      if (dateFromFilter?.value) filters.date_from = dateFromFilter.value;
      if (dateToFilter?.value) filters.date_to = dateToFilter.value;
      
      if (State.membersTable && Object.keys(filters).length > 0) {
         QMGridHelper.updateFilters(State.membersTable, filters);
      } else if (State.membersTable) {
         QMGridHelper.reload(State.membersTable);
      }
   }

   function clearMemberFilters() {
      ['statusFilter', 'familyFilter', 'genderFilter', 'dateFromFilter', 'dateToFilter'].forEach(id => {
         const element = document.getElementById(id);
         if (element) element.value = '';
      });
      
      if (State.membersTable) {
         QMGridHelper.reload(State.membersTable);
      }
   }

   function exportSelectedMembers() {
      if (!State.membersTable) return;
      
      const selectedRows = QMGridHelper.getSelectedRows(State.membersTable);
      if (selectedRows.length === 0) {
         Alerts.warning('Please select members to export');
         return;
      }
      
      QMGridHelper.export(State.membersTable, 'excel', {
         selectedOnly: true,
         filename: `selected-members-${new Date().toISOString().split('T')[0]}`
      });
      
      Alerts.success(`Exporting ${selectedRows.length} selected members`);
   }

   function exportAllMembers() {
      if (!State.membersTable) return;
      
      QMGridHelper.export(State.membersTable, 'excel', {
         filename: `all-members-${new Date().toISOString().split('T')[0]}`
      });
      
      Alerts.success('Exporting all members');
   }

   function printMemberList() {
      if (!State.membersTable) return;
      
      QMGridHelper.export(State.membersTable, 'print', {
         filename: 'Church Members List'
      });
   }

   function refreshMemberGrid() {
      if (State.membersTable) {
         QMGridHelper.reload(State.membersTable);
         Alerts.info('Refreshing member list...');
      }
   }

   function clearMemberSelection() {
      if (State.membersTable) {
         QMGridHelper.clearSelection(State.membersTable);
      }
   }

   // ===================================================================
   // AUTO-REFRESH
   // ===================================================================

   function startAutoRefresh() {
      if (State.autoRefreshInterval) {
         clearInterval(State.autoRefreshInterval);
      }
      
      State.autoRefreshInterval = setInterval(() => {
         if (State.membersTable && document.visibilityState === 'visible') {
            console.log('Auto-refreshing member data...');
            QMGridHelper.reload(State.membersTable);
         }
      }, 5 * 60 * 1000); // 5 minutes
   }

   function stopAutoRefresh() {
      if (State.autoRefreshInterval) {
         clearInterval(State.autoRefreshInterval);
         State.autoRefreshInterval = null;
      }
   }

   document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
         stopAutoRefresh();
      } else if (State.membersTable) {
         startAutoRefresh();
      }
   });

   window.addEventListener('beforeunload', () => {
      stopAutoRefresh();
      if (State.membersTable) {
         QMGridHelper.destroy(State.membersTable);
      }
   });

   // ===================================================================
   // GLOBAL FUNCTIONS
   // ===================================================================

   window.viewMember = viewMember;
   window.editMember = editMember;
   window.deleteMember = deleteMember;
   window.viewMemberProfile = viewMemberProfile;

})();