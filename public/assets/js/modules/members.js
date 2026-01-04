// State
   let membersTable = null;
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
      
      // Wait for settings to load before initializing
      await Config.waitForSettings();
      
      await initPage();
   });

   async function initPage() {
      try {
         // Load dropdown data first (public endpoints)
         await Promise.all([loadFamilies(), loadRoles()]);

         // Initialize components
         initTable();
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

   function initTable() {
      membersTable = QMGridHelper.initWithExport('#membersTable', {
         url: `${Config.API_BASE_URL}/member/all`,
         pageSize: 25,
         filename: 'church_members',
         selectable: true,
         multiSelect: true,
         exportOptions: {
            filename: 'church-members',
            dateFormat: 'DD/MM/YYYY',
            includeHeaders: true
         },
         onDataLoaded: (data) => {
            console.log(`Loaded ${data.data.length} of ${data.total} members`);
            updateMemberCount(data.total);
         },
         onError: (error) => {
            console.error('Failed to load members:', error);
            Alerts.error('Failed to load members: ' + (error.message || 'Unknown error'));
         },
         columns: [
            {
               key: 'ProfilePicture',
               title: '',
               width: '50px',
               sortable: false,
               exportable: false,
               render: function(data, row) {
                  const fullName = `${row.FirstName || ''} ${row.FamilyName || ''}`.trim();
                  return QMGridHelper.formatProfilePicture(data, fullName, 40);
               }
            },
            {
               key: 'FirstName',
               title: 'Member',
               render: function(data, row) {
                  return QMGridHelper.formatMemberName({
                     FirstName: row.FirstName || row.MbrFirstName,
                     OtherNames: row.OtherNames || row.MbrOtherNames,
                     FamilyName: row.FamilyName || row.MbrFamilyName,
                     EmailAddress: row.EmailAddress || row.MbrEmailAddress,
                     ProfilePicture: row.ProfilePicture || row.MbrProfilePicture
                  });
               }
            },
            {
               key: 'PhoneNumbers',
               title: 'Phone',
               sortable: false,
               render: function(data, row) {
                  // Handle different phone number formats from API
                  const phones = data || row.PrimaryPhone || row.phone_numbers;
                  return QMGridHelper.formatPhoneNumbers(phones);
               }
            },
            {
               key: 'Gender',
               title: 'Gender',
               width: '100px',
               render: function(data) {
                  if (!data) return '-';
                  const colors = { 'Male': 'primary', 'Female': 'success', 'Other': 'info' };
                  const color = colors[data] || 'secondary';
                  return `<span class="badge bg-${color}">${data}</span>`;
               }
            },
            {
               key: 'DateOfBirth',
               title: 'Age',
               width: '80px',
               render: function(data) {
                  if (!data) return '-';
                  try {
                     const birthDate = new Date(data);
                     const age = Math.floor((new Date() - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
                     return age > 0 ? `${age} years` : '-';
                  } catch (e) {
                     return '-';
                  }
               }
            },
            {
               key: 'FamilyID',
               title: 'Family',
               width: '120px',
               render: function(data, row) {
                  if (!data) return '<span class="badge bg-secondary">No Family</span>';
                  const familyName = row.FamilyName || `Family ${data}`;
                  return `<span class="badge bg-info">${familyName}</span>`;
               }
            },
            {
               key: 'Occupation',
               title: 'Occupation',
               render: function(data) {
                  return data || '-';
               }
            },
            {
               key: 'CreatedAt',
               title: 'Joined',
               width: '100px',
               render: function(data) {
                  return QMGridHelper.formatDate(data, 'short');
               }
            },
            {
               key: 'MbrRecID',
               title: 'Actions',
               width: '120px',
               sortable: false,
               exportable: false,
               render: function(data, row) {
                  return QMGridHelper.memberActionButtons({
                     MbrRecID: data || row.MbrID
                  }, {
                     view_members: Auth.hasPermission(Config.PERMISSIONS.VIEW_MEMBERS),
                     edit_members: Auth.hasPermission(Config.PERMISSIONS.EDIT_MEMBERS),
                     delete_members: Auth.hasPermission(Config.PERMISSIONS.DELETE_MEMBERS)
                  });
               }
            }
         ]
      });
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

      // Search functionality
      const memberSearch = document.getElementById('memberSearch');
      if (memberSearch) {
         memberSearch.addEventListener('input', function(e) {
            searchMembers(e.target.value);
         });
      }

      // Filter functionality
      const applyFiltersBtn = document.getElementById('applyMemberFilters');
      if (applyFiltersBtn) {
         applyFiltersBtn.addEventListener('click', applyMemberFilters);
      }

      const clearFiltersBtn = document.getElementById('clearMemberFilters');
      if (clearFiltersBtn) {
         clearFiltersBtn.addEventListener('click', clearMemberFilters);
      }

      // Export functionality
      const exportSelectedBtn = document.getElementById('exportSelectedMembers');
      if (exportSelectedBtn) {
         exportSelectedBtn.addEventListener('click', exportSelectedMembers);
      }

      const exportAllBtn = document.getElementById('exportAllMembers');
      if (exportAllBtn) {
         exportAllBtn.addEventListener('click', exportAllMembers);
      }

      const printListBtn = document.getElementById('printMemberList');
      if (printListBtn) {
         printListBtn.addEventListener('click', printMemberList);
      }

      // Refresh functionality
      const refreshBtn = document.getElementById('refreshMemberGrid');
      if (refreshBtn) {
         refreshBtn.addEventListener('click', refreshMemberGrid);
      }

      // Clear selection
      const clearSelectionBtn = document.getElementById('clearMemberSelection');
      if (clearSelectionBtn) {
         clearSelectionBtn.addEventListener('click', clearMemberSelection);
      }

      // Profile picture upload
      const profileZone = document.getElementById('profileDropzone');
      const profileInput = document.getElementById('profilePictureInput');

      if (profileZone && profileInput) {
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
      }

      const removePhotoBtn = document.getElementById('removePhotoBtn');
      if (removePhotoBtn) {
         removePhotoBtn.addEventListener('click', () => {
            profilePictureFile = null;
            document.getElementById('profilePreview').classList.add('d-none');
            document.getElementById('uploadPlaceholder').classList.remove('d-none');
            document.getElementById('removePhotoBtn').classList.add('d-none');
            
            // Mark for removal in edit mode
            if (isEditMode) {
               document.getElementById('profilePreview').setAttribute('data-removed', 'true');
            }
         });
      }

      // Phone number management
      const addPhoneBtn = document.getElementById('addPhoneBtn');
      if (addPhoneBtn) {
         addPhoneBtn.addEventListener('click', addPhoneField);
      }

      const phoneContainer = document.getElementById('phoneContainer');
      if (phoneContainer) {
         phoneContainer.addEventListener('click', (e) => {
            if (e.target.closest('.remove-phone')) {
               e.target.closest('.phone-row').remove();
            }
         });
      }

      // Login toggle
      const enableLogin = document.getElementById('enableLogin');
      if (enableLogin) {
         enableLogin.addEventListener('change', (e) => {
            document.getElementById('loginFields').classList.toggle('d-none', !e.target.checked);
         });
      }

      // Edit from view
      const editFromViewBtn = document.getElementById('editFromViewBtn');
      if (editFromViewBtn) {
         editFromViewBtn.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('viewMemberModal')).hide();
            editMember(currentMemberId);
         });
      }
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
      document.getElementById('profilePreview').removeAttribute('data-removed');
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

   // FIXED saveMember() function - combines data and file in single request

async function saveMember() {
   if (!validateCurrentStep()) return;

   try {
      // Collect phone numbers
      const phones = [];
      document.querySelectorAll('.phone-input').forEach(input => {
         const val = input.value.trim();
         if (val) phones.push(val);
      });

      // Create FormData for multipart/form-data request (supports file upload)
      const formData = new FormData();
      
      // Add all member data to FormData
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
      
      // Add phone numbers as JSON array
      formData.append('phone_numbers', JSON.stringify(phones));
      
      // Add login credentials if enabled
      if (!isEditMode && document.getElementById('enableLogin').checked) {
         formData.append('username', document.getElementById('username').value.trim());
         formData.append('password', document.getElementById('password').value);
      }
      
      // Add profile picture if selected
      if (profilePictureFile) {
         formData.append('profile_picture', profilePictureFile);
         console.log('Profile picture added to FormData:', profilePictureFile.name);
      } else if (isEditMode && document.getElementById('profilePreview').getAttribute('data-removed') === 'true') {
         // User explicitly removed the photo in edit mode
         formData.append('remove_profile_picture', 'true');
         console.log('Profile picture marked for removal');
      }
      
      // Add member ID for updates
      if (isEditMode) {
         formData.append('member_id', currentMemberId);
      }

      console.log('FormData prepared with', Array.from(formData.keys()).length, 'fields');

      Alerts.loading('Saving member...');

      // Single API call with all data
      let result;
      if (isEditMode) {
         result = await api.upload(`member/update/${currentMemberId}`, formData);
      } else {
         result = await api.upload('member/create', formData);
      }

      console.log('Member save result:', result);

      Alerts.closeLoading();
      Alerts.success(isEditMode ? 'Member updated successfully' : 'Member created successfully');

      bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();
      QMGridHelper.reload(membersTable);
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
            `<img src="/public/${member.MbrProfilePicture}" class="rounded-circle border border-4 border-white shadow" style="width:120px;height:120px;object-fit:cover;">` :
            `<div class="rounded-circle bg-gradient d-flex align-items-center justify-content-center text-white border border-4 border-white shadow" style="width:120px;height:120px;font-size:2.5rem;font-weight:700;background:linear-gradient(135deg, var(--primary-color) 0%, #8b5cf6 100%);">
               ${(member.MbrFirstName?.[0] || '') + (member.MbrFamilyName?.[0] || '')}
            </div>`;

         const statusClass = member.MbrMembershipStatus === 'Active' ? 'success' : 'secondary';
         const phones = member.PhoneNumbers?.join(', ') || member.PrimaryPhone || '-';

         // Calculate age if DOB exists
         let ageDisplay = '-';
         if (member.MbrDateOfBirth) {
            const dob = new Date(member.MbrDateOfBirth);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
               age--;
            }
            ageDisplay = `${age} years old`;
         }

         document.getElementById('viewMemberContent').innerHTML = `
         <!-- Profile Header with Gradient Background -->
         <div class="position-relative" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 3rem 2rem 5rem;">
            <div class="text-center position-relative">
               <div class="d-inline-block position-relative">
                  ${photoHtml}
                  <span class="position-absolute bottom-0 end-0 badge bg-${statusClass} rounded-pill px-2 py-1" style="font-size:0.7rem;">
                     <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>${member.MbrMembershipStatus}
                  </span>
               </div>
               <h3 class="text-white mt-3 mb-1 fw-bold">${member.MbrFirstName} ${member.MbrFamilyName}</h3>
               ${member.MbrOtherNames ? `<p class="text-white-50 mb-2">${member.MbrOtherNames}</p>` : ''}
               <div class="d-flex justify-content-center gap-3 mt-3">
                  <span class="badge bg-white bg-opacity-25 text-white px-3 py-2">
                     <i class="bi bi-hash me-1"></i>ID: ${member.MbrID}
                  </span>
                  <span class="badge bg-white bg-opacity-25 text-white px-3 py-2">
                     <i class="bi bi-calendar-check me-1"></i>Joined ${member.MbrRegistrationDate || '-'}
                  </span>
               </div>
            </div>
         </div>
         
         <!-- Quick Contact Actions -->
         <div class="px-4 py-3 bg-light border-bottom">
            <div class="row g-2">
               <div class="col-6">
                  <a href="mailto:${member.MbrEmailAddress || ''}" class="btn btn-outline-primary btn-sm w-100 ${!member.MbrEmailAddress ? 'disabled' : ''}">
                     <i class="bi bi-envelope me-1"></i>Email
                  </a>
               </div>
               <div class="col-6">
                  <a href="tel:${member.PrimaryPhone || ''}" class="btn btn-outline-success btn-sm w-100 ${!member.PrimaryPhone ? 'disabled' : ''}">
                     <i class="bi bi-telephone me-1"></i>Call
                  </a>
               </div>
            </div>
         </div>
         
         <!-- Tabs -->
         <ul class="nav nav-tabs px-3 pt-3 border-0" role="tablist" style="background:#f8f9fa;">
            <li class="nav-item">
               <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-bio">
                  <i class="bi bi-person-badge me-1"></i>Bio Data
               </button>
            </li>
            <li class="nav-item">
               <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-contact">
                  <i class="bi bi-telephone me-1"></i>Contact Info
               </button>
            </li>
            <li class="nav-item">
               <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-family">
                  <i class="bi bi-house-heart me-1"></i>Family Data
               </button>
            </li>
            <li class="nav-item">
               <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-milestones">
                  <i class="bi bi-trophy me-1"></i>Milestones
               </button>
            </li>
         </ul>
         
         <div class="tab-content p-4">
            <!-- Bio Data Tab -->
            <div class="tab-pane fade show active" id="tab-bio">
               <div class="row g-4">
                  <div class="col-md-6">
                     <div class="d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-person text-primary fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Full Name</div>
                           <div class="fw-semibold">${member.MbrFirstName} ${member.MbrFamilyName}</div>
                           ${member.MbrOtherNames ? `<small class="text-muted">${member.MbrOtherNames}</small>` : ''}
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="d-flex align-items-start">
                        <div class="bg-info bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-gender-ambiguous text-info fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Gender</div>
                           <div class="fw-semibold">${member.MbrGender || '-'}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="d-flex align-items-start">
                        <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-cake2 text-success fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Date of Birth</div>
                           <div class="fw-semibold">${member.MbrDateOfBirth || '-'}</div>
                           ${member.MbrDateOfBirth ? `<small class="text-muted">${ageDisplay}</small>` : ''}
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="d-flex align-items-start">
                        <div class="bg-warning bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-heart text-warning fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Marital Status</div>
                           <div class="fw-semibold">${member.MbrMaritalStatus || '-'}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="d-flex align-items-start">
                        <div class="bg-danger bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-briefcase text-danger fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Occupation</div>
                           <div class="fw-semibold">${member.MbrOccupation || '-'}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="d-flex align-items-start">
                        <div class="bg-secondary bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-mortarboard text-secondary fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Education Level</div>
                           <div class="fw-semibold">${member.MbrHighestEducationLevel || '-'}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-12">
                     <div class="d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-calendar-check text-primary fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Registration Date</div>
                           <div class="fw-semibold">${member.MbrRegistrationDate || '-'}</div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            
            <!-- Contact Info Tab -->
            <div class="tab-pane fade" id="tab-contact">
               <div class="row g-4">
                  <div class="col-12">
                     <div class="card border-0 bg-light">
                        <div class="card-body">
                           <div class="d-flex align-items-start">
                              <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                 <i class="bi bi-envelope text-primary fs-5"></i>
                              </div>
                              <div class="flex-grow-1">
                                 <div class="text-muted small mb-1">Email Address</div>
                                 <div class="fw-semibold">
                                    ${member.MbrEmailAddress ? `<a href="mailto:${member.MbrEmailAddress}" class="text-decoration-none">${member.MbrEmailAddress}</a>` : '-'}
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-12">
                     <div class="card border-0 bg-light">
                        <div class="card-body">
                           <div class="d-flex align-items-start">
                              <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                                 <i class="bi bi-telephone text-success fs-5"></i>
                              </div>
                              <div class="flex-grow-1">
                                 <div class="text-muted small mb-1">Phone Number(s)</div>
                                 <div class="fw-semibold">${phones}</div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-12">
                     <div class="card border-0 bg-light">
                        <div class="card-body">
                           <div class="d-flex align-items-start">
                              <div class="bg-danger bg-opacity-10 rounded p-2 me-3">
                                 <i class="bi bi-geo-alt text-danger fs-5"></i>
                              </div>
                              <div class="flex-grow-1">
                                 <div class="text-muted small mb-1">Residential Address</div>
                                 <div class="fw-semibold">${member.MbrResidentialAddress || '-'}</div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            
            <!-- Family Data Tab -->
            <div class="tab-pane fade" id="tab-family">
               <div class="row g-4">
                  <div class="col-12">
                     <div class="card border-0 bg-light">
                        <div class="card-body">
                           <div class="d-flex align-items-start">
                              <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                 <i class="bi bi-house-heart text-primary fs-5"></i>
                              </div>
                              <div class="flex-grow-1">
                                 <div class="text-muted small mb-1">Family Name</div>
                                 <div class="fw-semibold fs-5">${member.FamilyName || 'No family assigned'}</div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  ${member.FamilyID ? `
                  <div class="col-12">
                     <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        This member belongs to the <strong>${member.FamilyName}</strong> family.
                        <a href="families.php?id=${member.FamilyID}" class="alert-link ms-2">View Family Details</a>
                     </div>
                  </div>
                  ` : `
                  <div class="col-12">
                     <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This member is not assigned to any family yet.
                     </div>
                  </div>
                  `}
               </div>
            </div>
            
            <!-- Milestones Tab -->
            <div class="tab-pane fade" id="tab-milestones">
               <div class="row g-4">
                  <div class="col-12">
                     <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Member milestones and achievements will be displayed here.
                     </div>
                  </div>
                  <div class="col-12 text-center text-muted py-4">
                     <i class="bi bi-trophy fs-1 opacity-25"></i>
                     <p class="mt-2">No milestones recorded yet</p>
                     <small>Milestones such as baptism, confirmation, and other significant events will appear here.</small>
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
            <button class="btn btn-outline-danger btn-sm" onclick="viewMember(${memberId})">
               <i class="bi bi-arrow-clockwise me-1"></i>Retry
            </button>
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

   function viewMemberProfile(memberId) {
      // Same as viewMember but could be extended for different view
      viewMember(memberId);
   }

   // ===================================================================
   // ENHANCED MEMBER MANAGEMENT FUNCTIONS
   // ===================================================================

   /**
    * Update member count display
    */
   function updateMemberCount(total) {
      const countElement = document.getElementById('total-members-count');
      if (countElement) {
         countElement.textContent = total.toLocaleString();
      }
      
      // Update stats cards if they exist
      const totalElement = document.querySelector('#statsCards .stat-card:first-child h3');
      if (totalElement) {
         totalElement.textContent = total.toLocaleString();
      }
   }

   /**
    * Search members functionality
    */
   function searchMembers(searchTerm) {
      if (membersTable) {
         QMGridHelper.search(membersTable, searchTerm);
      }
   }

   /**
    * Apply filters to member table
    */
   function applyMemberFilters() {
      const filters = {};
      
      // Get filter values from UI elements
      const statusFilter = document.getElementById('statusFilter');
      const familyFilter = document.getElementById('familyFilter');
      const genderFilter = document.getElementById('genderFilter');
      const dateFromFilter = document.getElementById('dateFromFilter');
      const dateToFilter = document.getElementById('dateToFilter');
      
      if (statusFilter && statusFilter.value) {
         filters.status = statusFilter.value;
      }
      if (familyFilter && familyFilter.value) {
         filters.family_id = familyFilter.value;
      }
      if (genderFilter && genderFilter.value) {
         filters.gender = genderFilter.value;
      }
      if (dateFromFilter && dateFromFilter.value) {
         filters.date_from = dateFromFilter.value;
      }
      if (dateToFilter && dateToFilter.value) {
         filters.date_to = dateToFilter.value;
      }
      
      // Remove empty filters
      Object.keys(filters).forEach(key => {
         if (!filters[key]) delete filters[key];
      });
      
      // Update grid with new filters
      if (membersTable && Object.keys(filters).length > 0) {
         QMGridHelper.updateFilters(membersTable, filters);
      } else if (membersTable) {
         // Clear filters and reload
         QMGridHelper.reload(membersTable);
      }
   }

   /**
    * Clear all filters
    */
   function clearMemberFilters() {
      // Clear filter UI elements
      const filterElements = [
         'statusFilter', 'familyFilter', 'genderFilter', 
         'dateFromFilter', 'dateToFilter'
      ];
      
      filterElements.forEach(id => {
         const element = document.getElementById(id);
         if (element) {
            element.value = '';
         }
      });
      
      // Reload table without filters
      if (membersTable) {
         QMGridHelper.reload(membersTable);
      }
   }

   /**
    * Export selected members
    */
   function exportSelectedMembers() {
      if (!membersTable) return;
      
      const selectedRows = QMGridHelper.getSelectedRows(membersTable);
      if (selectedRows.length === 0) {
         Alerts.warning('Please select members to export');
         return;
      }
      
      QMGridHelper.export(membersTable, 'excel', {
         selectedOnly: true,
         filename: `selected-members-${new Date().toISOString().split('T')[0]}`,
         includeHeaders: true
      });
      
      Alerts.success(`Exporting ${selectedRows.length} selected members`);
   }

   /**
    * Export all members
    */
   function exportAllMembers() {
      if (!membersTable) return;
      
      QMGridHelper.export(membersTable, 'excel', {
         filename: `all-members-${new Date().toISOString().split('T')[0]}`,
         includeHeaders: true
      });
      
      Alerts.success('Exporting all members');
   }

   /**
    * Print member list
    */
   function printMemberList() {
      if (!membersTable) return;
      
      QMGridHelper.export(membersTable, 'print', {
         filename: 'Church Members List'
      });
   }

   /**
    * Refresh member grid
    */
   function refreshMemberGrid() {
      if (membersTable) {
         QMGridHelper.reload(membersTable);
         Alerts.info('Refreshing member list...');
      }
   }

   /**
    * Clear member selection
    */
   function clearMemberSelection() {
      if (membersTable) {
         QMGridHelper.clearSelection(membersTable);
      }
   }

   /**
    * Go to specific page
    */
   function goToMemberPage(page) {
      if (membersTable) {
         QMGridHelper.goToPage(membersTable, page);
      }
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
         QMGridHelper.reload(membersTable);
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete member error:', error);
         Alerts.handleApiError(error);
      }
   }

// ===================================================================
// AUTO-REFRESH AND CLEANUP
// ===================================================================

// Auto-refresh every 5 minutes (optional - can be disabled)
let autoRefreshInterval = null;

function startAutoRefresh() {
   // Clear existing interval
   if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
   }
   
   // Set up auto-refresh every 5 minutes
   autoRefreshInterval = setInterval(() => {
      if (membersTable && document.visibilityState === 'visible') {
         console.log('Auto-refreshing member data...');
         QMGridHelper.reload(membersTable);
      }
   }, 5 * 60 * 1000); // 5 minutes
}

function stopAutoRefresh() {
   if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
      autoRefreshInterval = null;
   }
}

// Start auto-refresh when page loads (after initial load)
setTimeout(() => {
   if (membersTable) {
      startAutoRefresh();
   }
}, 10000); // Start after 10 seconds

// Stop auto-refresh when page becomes hidden
document.addEventListener('visibilitychange', () => {
   if (document.visibilityState === 'hidden') {
      stopAutoRefresh();
   } else if (membersTable) {
      startAutoRefresh();
   }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
   // Stop auto-refresh
   stopAutoRefresh();
   
   // Clean up grid instance
   if (membersTable) {
      QMGridHelper.destroy(membersTable);
   }
});

// ===================================================================
// GLOBAL FUNCTIONS (for HTML onclick handlers)
// ===================================================================

// Make functions globally available for HTML onclick handlers
window.viewMember = viewMember;
window.editMember = editMember;
window.deleteMember = deleteMember;
window.viewMemberProfile = viewMemberProfile;
window.searchMembers = searchMembers;
window.applyMemberFilters = applyMemberFilters;
window.clearMemberFilters = clearMemberFilters;
window.exportSelectedMembers = exportSelectedMembers;
window.exportAllMembers = exportAllMembers;
window.printMemberList = printMemberList;
window.refreshMemberGrid = refreshMemberGrid;