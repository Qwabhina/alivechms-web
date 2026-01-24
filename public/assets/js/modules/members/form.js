/**
 * Member Form Component
 */

import { FormValidator } from './form-validator.js';
import { FormStepper } from './form-stepper.js';

export class MemberForm {
   constructor(state, api, table = null, stats = null) {
      this.state = state;
      this.api = api;
      this.table = table;
      this.stats = stats;
      this.validator = new FormValidator();
      this.stepper = new FormStepper(state);
      this.modal = null;
      this.viewModal = null;
      this.familyChoices = null;
      this.roleChoices = null;
   }

   init() {
      this.modal = new bootstrap.Modal(document.getElementById('memberModal'));
      this.viewModal = new bootstrap.Modal(document.getElementById('viewMemberModal'));
      
      this.initDatePickers();
      this.initEventListeners();
      this.stepper.init();
      
      console.log('✓ Member form initialized');
   }

   initDatePickers() {
      if (typeof flatpickr !== 'undefined') {
         flatpickr('#dateOfBirth', {
            maxDate: 'today',
            dateFormat: 'Y-m-d',
            allowInput: true
         });
      }
   }

   initEventListeners() {
      // Stepper navigation
      document.getElementById('nextStepBtn')?.addEventListener('click', () => this.nextStep());
      document.getElementById('prevStepBtn')?.addEventListener('click', () => this.prevStep());
      document.getElementById('submitBtn')?.addEventListener('click', () => this.save());

      // Profile picture
      this.setupProfilePictureUpload();

      // Phone numbers
      document.getElementById('addPhoneBtn')?.addEventListener('click', () => this.addPhoneField());
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

      // Password visibility
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

      // Modal close - reset form
      document.getElementById('memberModal')?.addEventListener('hidden.bs.modal', () => {
         this.reset();
      });

      // Edit from view modal
      document.getElementById('editFromViewBtn')?.addEventListener('click', () => {
         // Close view modal
         this.viewModal.hide();
         // Open edit form with current member ID
         if (this.state.currentMemberId) {
            window.editMember(this.state.currentMemberId);
         }
      });
   }

   setupProfilePictureUpload() {
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
            this.handleProfileUpload(e.dataTransfer.files[0]);
         }
      });

      profileInput.addEventListener('change', (e) => {
         if (e.target.files[0]) {
            this.handleProfileUpload(e.target.files[0]);
         }
      });

      removeBtn?.addEventListener('click', () => {
         this.state.profilePictureFile = null;
         document.getElementById('profilePreview').classList.add('d-none');
         document.getElementById('uploadPlaceholder').classList.remove('d-none');
         document.getElementById('removePhotoBtn').classList.add('d-none');
         
         if (this.state.isEditMode) {
            document.getElementById('profilePreview').setAttribute('data-removed', 'true');
         }
      });
   }

   handleProfileUpload(file) {
      if (!file.type.startsWith('image/')) {
         Alerts.error('Please select an image file');
         return;
      }

      if (file.size > 5 * 1024 * 1024) {
         Alerts.error('Image must be less than 5MB');
         return;
      }

      this.state.profilePictureFile = file;

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

   addPhoneField() {
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

   async open(member = null) {
      this.reset();

      if (member) {
         this.state.setEditMode(member.MbrID);
         await this.populateForm(member);
         document.getElementById('memberModalTitle').innerHTML = 
            '<i class="bi bi-pencil me-2"></i>Edit Member';
      } else {
         document.getElementById('memberModalTitle').innerHTML = 
            '<i class="bi bi-person-plus me-2"></i>Add New Member';
      }

      // Load lookup data
      await this.loadLookupData();

      this.modal.show();
   }

   async loadLookupData() {
      try {
         const [families, lookups] = await Promise.all([
            this.api.getFamilies(),
            this.api.getAllLookups()
         ]);

         this.state.familiesData = families?.data || families || [];
         
         // Extract data from combined lookups response
         const lookupsData = lookups?.data || lookups || {};
         this.state.maritalStatuses = lookupsData.marital_statuses || [];
         this.state.educationLevels = lookupsData.education_levels || [];
         this.state.membershipStatuses = lookupsData.membership_statuses || [];
         this.state.phoneTypes = lookupsData.phone_types || [];

         // Populate the select dropdowns
         this.populateMaritalStatusSelect();
         this.populateEducationLevelSelect();

         console.log(`✓ Loaded ${this.state.familiesData.length} families and lookup data`);
      } catch (error) {
         console.error('Failed to load lookup data:', error);
      }
   }

   populateMaritalStatusSelect() {
      const select = document.getElementById('maritalStatus');
      if (!select) return;

      // Store current value if editing
      const currentValue = select.value;

      // Clear existing options except the first one
      select.innerHTML = '<option value="">Select status</option>';

      // Add options from lookup data
      this.state.maritalStatuses.forEach(status => {
         const option = document.createElement('option');
         option.value = status.id;
         option.textContent = status.name;
         if (currentValue && status.id == currentValue) {
            option.selected = true;
         }
         select.appendChild(option);
      });
   }

   populateEducationLevelSelect() {
      const select = document.getElementById('educationLevel');
      if (!select) return;

      // Store current value if editing
      const currentValue = select.value;

      // Clear existing options except the first one
      select.innerHTML = '<option value="">Select education level</option>';

      // Add options from lookup data
      this.state.educationLevels.forEach(level => {
         const option = document.createElement('option');
         option.value = level.id;
         option.textContent = level.name;
         if (currentValue && level.id == currentValue) {
            option.selected = true;
         }
         select.appendChild(option);
      });
   }

   async populateForm(member) {
      // Basic info
      document.getElementById('memberId').value = member.MbrID || '';
      document.getElementById('firstName').value = member.MbrFirstName || '';
      document.getElementById('familyName').value = member.MbrFamilyName || '';
      document.getElementById('otherNames').value = member.MbrOtherNames || '';
      document.getElementById('gender').value = member.MbrGender || '';
      document.getElementById('dateOfBirth').value = member.MbrDateOfBirth || '';
      document.getElementById('occupation').value = member.MbrOccupation || '';
      document.getElementById('email').value = member.MbrEmailAddress || '';
      document.getElementById('address').value = member.MbrResidentialAddress || '';

      // Set marital status and education level IDs
      if (member.MbrMaritalStatusID) {
         document.getElementById('maritalStatus').value = member.MbrMaritalStatusID;
      }
      if (member.MbrEducationLevelID) {
         document.getElementById('educationLevel').value = member.MbrEducationLevelID;
      }

      // Profile picture
      if (member.MbrProfilePicture) {
         const preview = document.getElementById('profilePreview');
         preview.src = `${Config.API_BASE_URL}/public/${member.MbrProfilePicture}`;
         preview.classList.remove('d-none');
         document.getElementById('uploadPlaceholder').classList.add('d-none');
         document.getElementById('removePhotoBtn').classList.remove('d-none');
      }

      // Phone numbers
      const phoneContainer = document.getElementById('phoneContainer');
      phoneContainer.innerHTML = '';
      
      const phones = member.phones || [];
      if (phones.length === 0) {
         this.addPhoneField();
      } else {
         phones.forEach((phone, index) => {
            const row = document.createElement('div');
            row.className = 'phone-row mb-2';
            const isPrimary = index === 0;
            row.innerHTML = `
               <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                  <input type="text" class="form-control phone-input" value="${phone.PhoneNumber || ''}" placeholder="e.g., 0241234567">
                  ${isPrimary ? '<span class="input-group-text bg-success text-white" title="Primary"><i class="bi bi-star-fill" style="font-size: 0.7rem;"></i></span>' : ''}
                  ${!isPrimary ? '<button type="button" class="btn btn-outline-danger remove-phone"><i class="bi bi-trash"></i></button>' : ''}
               </div>
            `;
            phoneContainer.appendChild(row);
         });
      }

      // Family - set initial value for later initialization
      if (member.FamilyID) {
         document.getElementById('familySelect').setAttribute('data-initial-value', member.FamilyID);
      }
   }

   nextStep() {
      if (!this.validator.validateStep(this.state.currentStep)) {
         return;
      }

      if (this.state.nextStep()) {
         this.stepper.update();
         
         // Initialize selects when reaching their steps
         if (this.state.currentStep === 1) this.initFamilySelect();
         if (this.state.currentStep === 2) this.initRoleSelect();
      }
   }

   prevStep() {
      if (this.state.prevStep()) {
         this.stepper.update();
      }
   }

   initFamilySelect() {
      const select = document.getElementById('familySelect');
      
      if (this.familyChoices) {
         this.familyChoices.destroy();
      }

      const initialValue = select.getAttribute('data-initial-value') || '';

      select.innerHTML = '<option value="">No Family</option>';
      this.state.familiesData.forEach(f => {
         const opt = document.createElement('option');
         opt.value = f.FamilyID;
         opt.textContent = f.FamilyName;
         if (initialValue && f.FamilyID == initialValue) {
            opt.selected = true;
         }
         select.appendChild(opt);
      });

      this.familyChoices = new Choices(select, {
         searchEnabled: true,
         searchPlaceholderValue: 'Search families...',
         itemSelectText: '',
         shouldSort: false
      });
   }

   initRoleSelect() {
      const select = document.getElementById('roleSelect');
      
      if (this.roleChoices) {
         this.roleChoices.destroy();
      }

      select.innerHTML = '<option value="">Select Role</option>';
      this.state.rolesData.forEach(r => {
         const opt = document.createElement('option');
         opt.value = r.RoleID;
         opt.textContent = r.RoleName;
         select.appendChild(opt);
      });

      this.roleChoices = new Choices(select, {
         searchEnabled: true,
         searchPlaceholderValue: 'Search roles...',
         itemSelectText: '',
         shouldSort: false
      });
   }

   async save() {
      if (!this.validator.validateStep(this.state.currentStep)) {
         return;
      }

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
         formData.append('marital_status_id', document.getElementById('maritalStatus')?.value || '');
         formData.append('education_level_id', document.getElementById('educationLevel')?.value || '');
         formData.append('family_id', document.getElementById('familySelect').value || '');
         formData.append('branch_id', '1');
         formData.append('phone_numbers', JSON.stringify(phones));
         
         if (!this.state.isEditMode && document.getElementById('enableLogin')?.checked) {
            formData.append('username', document.getElementById('username').value.trim());
            formData.append('password', document.getElementById('password').value);
         }
         
         if (this.state.profilePictureFile) {
            formData.append('profile_picture', this.state.profilePictureFile);
            console.log('Profile picture added:', this.state.profilePictureFile.name);
         } else if (this.state.isEditMode && document.getElementById('profilePreview').getAttribute('data-removed') === 'true') {
            formData.append('remove_profile_picture', 'true');
            console.log('Profile picture marked for removal');
         }
         
         if (this.state.isEditMode) {
            formData.append('member_id', this.state.currentMemberId);
         }

         console.log('Saving member with', Array.from(formData.keys()).length, 'fields');

         Alerts.loading(this.state.isEditMode ? 'Updating member...' : 'Creating member...');

         let result;
         if (this.state.isEditMode) {
            result = await api.upload(`/member/update/${this.state.currentMemberId}`, formData);
         } else {
            result = await api.upload('/member/create', formData);
         }

         Alerts.closeLoading();
         Alerts.success(this.state.isEditMode ? 'Member updated successfully' : 'Member created successfully');

         this.modal.hide();
         
         // Refresh table and stats asynchronously instead of page reload
         if (this.table) {
            this.table.refresh();
         }
         if (this.stats) {
            this.stats.load();
         }

      } catch (error) {
         Alerts.closeLoading();
         console.error('Failed to save member:', error);
         Alerts.error(error.message || 'Failed to save member');
      }
   }

   reset() {
      this.state.reset();
      this.stepper.reset();
      document.getElementById('memberForm').reset();
      
      // Reset profile picture
      document.getElementById('profilePreview').classList.add('d-none');
      document.getElementById('uploadPlaceholder').classList.remove('d-none');
      document.getElementById('removePhotoBtn').classList.add('d-none');
      
      // Reset phone container
      const phoneContainer = document.getElementById('phoneContainer');
      phoneContainer.innerHTML = `
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

      // Reset login toggle
      document.getElementById('enableLogin').checked = false;
      document.getElementById('loginFields').classList.add('d-none');
      document.getElementById('noLoginMessage')?.classList.remove('d-none');
   }

   showView(member) {
      // Defensive null checks
      if (!member) {
         document.getElementById('viewMemberContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Member data is not available</p>
            </div>
         `;
         return;
      }

      const photoHtml = member.MbrProfilePicture
         ? `<img src="${Config.API_BASE_URL}/public/${member.MbrProfilePicture}" class="rounded-circle border border-4 border-white shadow" style="width:120px;height:120px;object-fit:cover;" alt="Profile">`
         : `<div class="rounded-circle bg-gradient d-flex align-items-center justify-content-center text-white border border-4 border-white shadow" style="width:120px;height:120px;font-size:2.5rem;font-weight:700;background:linear-gradient(135deg, var(--bs-primary) 0%, #8b5cf6 100%);">
               ${(member.MbrFirstName?.[0] || '') + (member.MbrFamilyName?.[0] || '')}
            </div>`;

      const statusClass = (member.MembershipStatusName === 'Active' || member.MbrMembershipStatus === 'Active') ? 'success' : 'secondary';
      const statusValue = member.MembershipStatusName || member.MbrMembershipStatus || 'Unknown';
      
      // Handle phone numbers with defensive checks
      let phones = [];
      if (Array.isArray(member.PhoneNumbers)) {
         phones = member.PhoneNumbers;
      } else if (typeof member.PhoneNumbers === 'string' && member.PhoneNumbers) {
         phones = member.PhoneNumbers.split(',');
      } else if (member.PrimaryPhone) {
         phones = [member.PrimaryPhone];
      }
      const phoneDisplay = phones.length > 0 ? phones.join(', ') : 'Not provided';

      // Calculate age with defensive checks
      let ageDisplay = 'Not provided';
      let dobDisplay = 'Not provided';
      if (member.MbrDateOfBirth) {
         try {
            const dob = new Date(member.MbrDateOfBirth);
            if (!isNaN(dob.getTime())) {
               dobDisplay = dob.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
               const today = new Date();
               let age = today.getFullYear() - dob.getFullYear();
               const monthDiff = today.getMonth() - dob.getMonth();
               if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                  age--;
               }
               if (age >= 0 && age < 150) {
                  ageDisplay = `${age} years old`;
               }
            }
         } catch (e) {
            Config.warn('Error calculating age:', e);
         }
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
                     ${statusValue}
                  </span>
               </div>
               <h3 class="text-white mb-1 fw-bold">${fullName}</h3>
               <p class="text-white-50 mb-2">Member ID: ${member.MbrUniqueID || member.MbrID}</p>
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
                           <span class="info-value fw-medium">${member.MaritalStatusName || member.MbrMaritalStatus || 'Not provided'}</span>
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
                           <span class="info-value fw-medium">${member.EducationLevelName || member.MbrHighestEducationLevel || 'Not provided'}</span>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="info-item">
                           <span class="info-label text-muted small">Membership Status</span>
                           <span class="info-value">
                              <span class="badge bg-${statusClass}">${statusValue}</span>
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
                           <span class="info-value fw-medium">${member.MbrUniqueID || member.MbrID}</span>
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

      this.state.currentMemberId = member.MbrID;
      this.viewModal.show();
   }

   printProfile() {
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
   }
}
