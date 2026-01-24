/**
 * Family Form Component
 */

export class FamilyForm {
   constructor(state, api, table = null, stats = null) {
      this.state = state;
      this.api = api;
      this.table = table;
      this.stats = stats;
      this.modal = null;
      this.viewModal = null;
      this.manageMembersModal = null;
   }

   init() {
      this.modal = new bootstrap.Modal(document.getElementById('familyModal'));
      this.viewModal = new bootstrap.Modal(document.getElementById('viewFamilyModal'));
      this.manageMembersModal = new bootstrap.Modal(document.getElementById('manageMembersModal'));
      
      this.initEventListeners();
      
      console.log('✓ Family form initialized');
   }

   initEventListeners() {
      // Save button
      document.getElementById('saveFamilyBtn')?.addEventListener('click', () => this.save());

      // Edit from view modal
      document.getElementById('editFamilyFromViewBtn')?.addEventListener('click', () => {
         this.viewModal.hide();
         if (this.state.currentFamilyId) {
            window.editFamily(this.state.currentFamilyId);
         }
      });

      // Manage members button
      document.getElementById('manageMembersBtn')?.addEventListener('click', () => {
         this.viewModal.hide();
         this.openManageMembersModal(this.state.currentFamilyId);
      });

      // Add member to family
      document.getElementById('addMemberToFamilyBtn')?.addEventListener('click', () => this.addMemberToFamily());
   }

   async open(familyId = null) {
      this.state.reset();

      if (familyId) {
         this.state.setEditMode(familyId);
         document.getElementById('familyModalTitle').innerHTML = 
            '<i class="bi bi-pencil-square me-2"></i>Edit Family';
      } else {
         document.getElementById('familyModalTitle').innerHTML = 
            '<i class="bi bi-house-heart me-2"></i>Add New Family';
      }

      // Reset form
      document.getElementById('familyForm').reset();
      document.getElementById('familyId').value = '';

      // Load members and populate dropdown
      await this.loadMembers();
      this.populateMemberSelect('headOfHousehold');
      
      // Initialize Choices.js
      if (this.state.headOfHouseholdChoices) {
         this.state.headOfHouseholdChoices.destroy();
      }
      this.state.headOfHouseholdChoices = new Choices(document.getElementById('headOfHousehold'), {
         searchEnabled: true,
         searchPlaceholderValue: 'Search members...',
         itemSelectText: '',
         allowHTML: true
      });

      this.modal.show();

      if (this.state.isEditMode) {
         await this.loadFamilyForEdit(familyId);
      }
   }

   async loadMembers() {
      try {
         const response = await this.api.getMembers();
         this.state.membersData = Array.isArray(response) ? response : (response?.data || []);
      } catch (error) {
         console.error('Failed to load members:', error);
         this.state.membersData = [];
      }
   }

   populateMemberSelect(selectId, excludeIds = []) {
      const select = document.getElementById(selectId);
      if (!select) return;
      
      select.innerHTML = '<option value="">Select Member</option>';
      this.state.membersData
         .filter(m => !excludeIds.includes(m.MbrID))
         .forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.MbrID;
            opt.textContent = `${m.MbrFirstName || ''} ${m.MbrFamilyName || ''}`.trim();
            select.appendChild(opt);
         });
   }

   async loadFamilyForEdit(familyId) {
      try {
         Alerts.loading('Loading family...');
         const family = await this.api.get(familyId);
         Alerts.closeLoading();
         
         document.getElementById('familyId').value = family.FamilyID;
         document.getElementById('familyName').value = family.FamilyName || '';
         
         if (this.state.headOfHouseholdChoices && family.HeadOfHouseholdID) {
            this.state.headOfHouseholdChoices.setChoiceByValue(family.HeadOfHouseholdID.toString());
         }
      } catch (error) {
         Alerts.closeLoading();
         Alerts.error('Failed to load family data');
      }
   }

   async save() {
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
         Alerts.loading(this.state.isEditMode ? 'Updating family...' : 'Creating family...');
         
         if (this.state.isEditMode) {
            await this.api.update(this.state.currentFamilyId, payload);
         } else {
            await this.api.create(payload);
         }
         
         Alerts.closeLoading();
         Alerts.success(this.state.isEditMode ? 'Family updated successfully' : 'Family created successfully');
         
         this.modal.hide();
         
         // Refresh table and stats asynchronously
         if (this.table) {
            this.table.refresh();
         }
         if (this.stats) {
            this.stats.load();
         }
      } catch (error) {
         Alerts.closeLoading();
         console.error('Failed to save family:', error);
         Alerts.handleApiError(error);
      }
   }

   async showView(familyId) {
      this.state.currentFamilyId = familyId;
      this.viewModal.show();

      try {
         const family = await this.api.get(familyId);
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
                  <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">
                     <i class="bi bi-people me-2"></i>Family Members
                  </h6>
                  ${membersList.length > 0 ? `
                     <div class="list-group list-group-flush">
                        ${membersList.map(m => `
                           <div class="list-group-item px-0">
                              <div class="d-flex align-items-center">
                                 <div class="me-3">
                                    ${m.MbrProfilePicture 
                                       ? `<img src="${Config.API_BASE_URL}/public/${m.MbrProfilePicture}" class="rounded-circle" style="width:45px;height:45px;object-fit:cover;">`
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

   async openManageMembersModal(familyId) {
      this.state.currentFamilyId = familyId;
      this.manageMembersModal.show();
      await this.loadFamilyMembersForManagement(familyId);
   }

   async loadFamilyMembersForManagement(familyId) {
      try {
         const family = await this.api.get(familyId);
         const membersList = family.members || [];
         const memberIds = membersList.map(m => m.MbrID);

         if (this.state.addMemberChoices) {
            this.state.addMemberChoices.destroy();
         }
         
         this.populateMemberSelect('addMemberSelect', memberIds);
         this.state.addMemberChoices = new Choices(document.getElementById('addMemberSelect'), {
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
                        ? `<button class="btn btn-outline-danger btn-sm" onclick="removeMemberFromFamily(${familyId}, ${m.MbrID})">
                              <i class="bi bi-x-circle"></i>
                           </button>` 
                        : '<span class="badge bg-primary">Head</span>'}
                  </div>
               `).join('')}
            </div>
         ` : '<p class="text-muted text-center">No members in this family.</p>';
      } catch (error) {
         document.getElementById('familyMembersList').innerHTML = '<p class="text-danger">Failed to load members</p>';
      }
   }

   async addMemberToFamily() {
      const memberId = document.getElementById('addMemberSelect').value;
      if (!memberId) {
         Alerts.warning('Please select a member to add');
         return;
      }

      try {
         Alerts.loading('Adding member...');
         await this.api.addMember(this.state.currentFamilyId, memberId);
         Alerts.closeLoading();
         Alerts.success('Member added to family');
         
         await this.loadFamilyMembersForManagement(this.state.currentFamilyId);
         
         if (this.table) {
            this.table.refresh();
         }
         if (this.stats) {
            this.stats.load();
         }
      } catch (error) {
         Alerts.closeLoading();
         Alerts.handleApiError(error);
      }
   }

   async removeMemberFromFamily(familyId, memberId) {
      const confirmed = await Alerts.confirm({
         title: 'Remove Member',
         text: 'Remove this member from the family?',
         confirmButtonText: 'Yes, remove'
      });
      if (!confirmed) return;

      try {
         Alerts.loading('Removing member...');
         await this.api.removeMember(familyId, memberId);
         Alerts.closeLoading();
         Alerts.success('Member removed from family');
         
         await this.loadFamilyMembersForManagement(familyId);
         
         if (this.table) {
            this.table.refresh();
         }
         if (this.stats) {
            this.stats.load();
         }
      } catch (error) {
         Alerts.closeLoading();
         Alerts.handleApiError(error);
      }
   }
}
