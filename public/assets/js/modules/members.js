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
      membersGrid = new Tabulator("#membersGrid", {
         layout: "fitColumns",
         responsiveLayout: "collapse",
         resizableColumns: false,
         pagination: true,
         paginationMode: "remote",
         paginationSize: 25,
         paginationSizeSelector: [10, 25, 50, 100],
         ajaxURL: `${Config.API_BASE_URL}/member/all`,
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
               data: data.map(m => ({
                  photo: m.MbrProfilePicture,
                  name: `${m.MbrFirstName} ${m.MbrFamilyName}`,
                  gender: m.MbrGender || '-',
                  phone: m.PrimaryPhone || '-',
                  email: m.MbrEmailAddress || '-',
                  status: m.MbrMembershipStatus || 'Active',
                  id: m.MbrID
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
         columns: [
            {
               title: "Photo",
               field: "photo",
               width: 60,
               headerSort: false,
               download: false,
               responsive: 0,
               formatter: function(cell) {
                  const photo = cell.getValue();
                  const name = cell.getRow().getData().name;
                  if (photo) {
                     return `<img src="/public/${photo}" class="member-photo" alt="${name}">`;
                  }
                  const initials = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                  return `<div class="member-photo-placeholder">${initials}</div>`;
               }
            },
            {title: "Name", field: "name", widthGrow: 2, responsive: 0, download: true},
            {title: "Gender", field: "gender", widthGrow: 1, responsive: 2, download: true},
            {title: "Phone", field: "phone", widthGrow: 1.5, responsive: 1, download: true},
            {title: "Email", field: "email", widthGrow: 2, responsive: 1, download: true},
            {
               title: "Status",
               field: "status",
               widthGrow: 1,
               responsive: 2,
               download: false,
               formatter: function(cell) {
                  const status = cell.getValue();
                  const cls = status === 'Active' ? 'success' : 'secondary';
                  return `<span class="badge bg-${cls}">${status}</span>`;
               }
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
                  `;
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
         
         // Mark for removal in edit mode
         if (isEditMode) {
            document.getElementById('profilePreview').setAttribute('data-removed', 'true');
         }
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
      membersGrid.setData(); // Reload table data
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
         console.log('Phone numbers for display:', phones);
         console.log('Address for display:', member.MbrResidentialAddress);

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
               <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-personal">
                  <i class="bi bi-person me-1"></i>Personal
               </button>
            </li>
            <li class="nav-item">
               <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-contact">
                  <i class="bi bi-telephone me-1"></i>Contact
               </button>
            </li>
            <li class="nav-item">
               <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-church">
                  <i class="bi bi-building me-1"></i>Church Info
               </button>
            </li>
         </ul>
         
         <div class="tab-content p-4">
            <!-- Personal Tab -->
            <div class="tab-pane fade show active" id="tab-personal">
               <div class="row g-4">
                  <div class="col-md-6">
                     <div class="d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-gender-ambiguous text-primary fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Gender</div>
                           <div class="fw-semibold">${member.MbrGender || '-'}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="d-flex align-items-start">
                        <div class="bg-info bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-cake2 text-info fs-5"></i>
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
                        <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-heart text-success fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Marital Status</div>
                           <div class="fw-semibold">${member.MbrMaritalStatus || '-'}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="d-flex align-items-start">
                        <div class="bg-warning bg-opacity-10 rounded p-2 me-3">
                           <i class="bi bi-briefcase text-warning fs-5"></i>
                        </div>
                        <div>
                           <div class="text-muted small mb-1">Occupation</div>
                           <div class="fw-semibold">${member.MbrOccupation || '-'}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-12">
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
               </div>
            </div>
            
            <!-- Contact Tab -->
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
            
            <!-- Church Info Tab -->
            <div class="tab-pane fade" id="tab-church">
               <div class="row g-4">
                  <div class="col-md-6">
                     <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                           <div class="text-muted small mb-2">
                              <i class="bi bi-hash me-1"></i>Member ID
                           </div>
                           <div class="fw-bold fs-4 text-primary">#${member.MbrID}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                           <div class="text-muted small mb-2">
                              <i class="bi bi-calendar-check me-1"></i>Registration Date
                           </div>
                           <div class="fw-semibold">${member.MbrRegistrationDate || '-'}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                           <div class="text-muted small mb-2">
                              <i class="bi bi-house-heart me-1"></i>Family
                           </div>
                           <div class="fw-semibold">${member.FamilyName || 'No family assigned'}</div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                           <div class="text-muted small mb-2">
                              <i class="bi bi-circle-fill me-1 text-${statusClass}" style="font-size:0.5rem;"></i>Membership Status
                           </div>
                           <span class="badge bg-${statusClass} px-3 py-2">${member.MbrMembershipStatus}</span>
                        </div>
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
         membersGrid.setData();
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete member error:', error);
         Alerts.handleApiError(error);
      }
   }