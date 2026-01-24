/**
 * Groups Form Component
 */

export class GroupForm {
   constructor(state, api, table = null, stats = null) {
      this.state = state;
      this.api = api;
      this.table = table;
      this.stats = stats;
      this.modal = null;
      this.viewModal = null;
      this.manageMembersModal = null;
      this.groupTypesModal = null;
   }

   async init() {
      this.modal = new bootstrap.Modal(document.getElementById('groupModal'));
      this.viewModal = new bootstrap.Modal(document.getElementById('viewGroupModal'));
      this.manageMembersModal = new bootstrap.Modal(document.getElementById('manageMembersModal'));
      this.groupTypesModal = new bootstrap.Modal(document.getElementById('groupTypesModal'));
      
      // Load initial data
      await Promise.all([this.loadMembers(), this.loadGroupTypes()]);
      
      this.initEventListeners();
      
      console.log('✓ Group form initialized');
   }

   initEventListeners() {
      // Add group button
      document.getElementById('addGroupBtn')?.addEventListener('click', () => {
         if (!Auth.hasPermission('manage_groups')) {
            Alerts.error('You do not have permission to create groups');
            return;
         }
         this.open();
      });

      // Save button
      document.getElementById('saveGroupBtn')?.addEventListener('click', () => this.save());

      // Edit from view modal
      document.getElementById('editGroupFromViewBtn')?.addEventListener('click', () => {
         this.viewModal.hide();
         if (this.state.currentGroupId) {
            window.editGroup(this.state.currentGroupId);
         }
      });

      // Manage members button
      document.getElementById('manageMembersBtn')?.addEventListener('click', () => {
         this.viewModal.hide();
         this.openManageMembersModal(this.state.currentGroupId);
      });

      // Add member to group
      document.getElementById('addMemberToGroupBtn')?.addEventListener('click', () => this.addMemberToGroup());

      // Manage types button
      document.getElementById('manageTypesBtn')?.addEventListener('click', () => this.openGroupTypesModal());

      // Add group type
      document.getElementById('addGroupTypeBtn')?.addEventListener('click', () => this.addGroupType());
   }

   async loadMembers() {
      try {
         const response = await this.api.getAllMembers({ limit: 1000 });
         this.state.membersData = Array.isArray(response) ? response : (response?.data || []);
      } catch (error) {
         console.error('Failed to load members:', error);
         this.state.membersData = [];
      }
   }

   async loadGroupTypes() {
      try {
         const response = await this.api.getAllGroupTypes({ limit: 100 });
         this.state.groupTypesData = Array.isArray(response) ? response : (response?.data || []);
      } catch (error) {
         console.error('Failed to load group types:', error);
         this.state.groupTypesData = [];
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

   populateTypeSelect() {
      const select = document.getElementById('groupType');
      if (!select) return;

      select.innerHTML = '<option value="">Select Type</option>';
      this.state.groupTypesData.forEach(t => {
         const opt = document.createElement('option');
         opt.value = t.GroupTypeID;
         opt.textContent = t.GroupTypeName;
         select.appendChild(opt);
      });
   }

   async open(groupId = null) {
      this.state.reset();

      if (groupId) {
         this.state.setEditMode(groupId);
         document.getElementById('groupModalTitle').innerHTML = 
            '<i class="bi bi-pencil-square me-2"></i>Edit Group';
      } else {
         document.getElementById('groupModalTitle').innerHTML = 
            '<i class="bi bi-people me-2"></i>Add New Group';
      }

      // Reset form
      document.getElementById('groupForm').reset();
      document.getElementById('groupId').value = '';

      // Destroy existing Choices instances
      if (this.state.leaderChoices) {
         this.state.leaderChoices.destroy();
         this.state.leaderChoices = null;
      }
      if (this.state.typeChoices) {
         this.state.typeChoices.destroy();
         this.state.typeChoices = null;
      }

      // Populate selects
      this.populateMemberSelect('groupLeader');
      this.populateTypeSelect();

      // Initialize Choices.js
      this.state.leaderChoices = new Choices(document.getElementById('groupLeader'), {
         searchEnabled: true,
         searchPlaceholderValue: 'Search members...',
         itemSelectText: '',
         allowHTML: true
      });

      this.state.typeChoices = new Choices(document.getElementById('groupType'), {
         searchEnabled: true,
         itemSelectText: ''
      });

      this.modal.show();

      if (this.state.isEditMode) {
         await this.loadGroupForEdit(groupId);
      }
   }

   async loadGroupForEdit(groupId) {
      try {
         Alerts.loading('Loading group...');
         const group = await this.api.get(groupId);
         Alerts.closeLoading();

         document.getElementById('groupId').value = group.GroupID;
         document.getElementById('groupName').value = group.GroupName || '';
         document.getElementById('groupDescription').value = group.GroupDescription || '';

         if (this.state.typeChoices && group.GroupTypeID) {
            this.state.typeChoices.setChoiceByValue(group.GroupTypeID.toString());
         }
         if (this.state.leaderChoices && group.GroupLeaderID) {
            this.state.leaderChoices.setChoiceByValue(group.GroupLeaderID.toString());
         }
      } catch (error) {
         Alerts.closeLoading();
         Alerts.error('Failed to load group data');
      }
   }

   async save() {
      const groupName = document.getElementById('groupName').value.trim();
      const typeId = document.getElementById('groupType').value;
      const leaderId = document.getElementById('groupLeader').value;

      if (!groupName || !typeId || !leaderId) {
         Alerts.warning('Please fill all required fields');
         return;
      }

      const payload = {
         name: groupName,
         type_id: parseInt(typeId),
         leader_id: parseInt(leaderId),
         description: document.getElementById('groupDescription').value.trim() || null
      };

      try {
         Alerts.loading(this.state.isEditMode ? 'Updating group...' : 'Creating group...');
         
         if (this.state.isEditMode) {
            await this.api.update(this.state.currentGroupId, payload);
         } else {
            await this.api.create(payload);
         }
         
         Alerts.closeLoading();
         Alerts.success(this.state.isEditMode ? 'Group updated successfully' : 'Group created successfully');
         
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
         console.error('Failed to save group:', error);
         Alerts.handleApiError(error);
      }
   }

   async showView(groupId) {
      this.state.currentGroupId = groupId;
      this.viewModal.show();

      try {
         const group = await this.api.get(groupId);
         const membersRes = await this.api.getMembers(groupId, { limit: 100 });
         const membersList = Array.isArray(membersRes) ? membersRes : (membersRes?.data || []);

         const leaderName = `${group.LeaderFirstName || ''} ${group.LeaderFamilyName || ''}`.trim();

         document.getElementById('viewGroupContent').innerHTML = `
            <div class="group-profile">
               <div class="profile-header text-center py-4" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                  <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
                     <i class="bi bi-people-fill text-success" style="font-size:2.5rem;"></i>
                  </div>
                  <h3 class="text-white mb-1">${group.GroupName}</h3>
                  <span class="badge bg-white bg-opacity-25 text-white">${group.GroupTypeName || 'No Type'}</span>
               </div>
               
               <div class="p-4">
                  <div class="row g-3 mb-4">
                     <div class="col-md-6">
                        <div class="text-muted small text-uppercase">Leader</div>
                        <div class="fw-semibold">${leaderName || 'Not assigned'}</div>
                     </div>
                     <div class="col-md-6">
                        <div class="text-muted small text-uppercase">Total Members</div>
                        <div class="fw-semibold">${membersList.length}</div>
                     </div>
                     ${group.GroupDescription ? `
                     <div class="col-12">
                        <div class="text-muted small text-uppercase">Description</div>
                        <div>${group.GroupDescription}</div>
                     </div>
                     ` : ''}
                  </div>

                  <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">
                     <i class="bi bi-people me-2"></i>Group Members
                  </h6>
                  ${membersList.length > 0 ? `
                     <div class="list-group list-group-flush">
                        ${membersList.map(m => `
                           <div class="list-group-item px-0">
                              <div class="d-flex align-items-center">
                                 <div class="me-3">
                                    ${m.MbrProfilePicture ? 
                                       `<img src="${Config.API_BASE_URL}/public/${m.MbrProfilePicture}" class="rounded-circle" style="width:45px;height:45px;object-fit:cover;">` :
                                       `<div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                                          ${(m.MbrFirstName?.[0] || '') + (m.MbrFamilyName?.[0] || '')}
                                       </div>`
                                    }
                                 </div>
                                 <div class="flex-grow-1">
                                    <div class="fw-semibold">${m.MbrFirstName || ''} ${m.MbrFamilyName || ''}</div>
                                    <small class="text-muted">${m.MbrEmailAddress || 'No email'}</small>
                                 </div>
                                 ${group.GroupLeaderID == m.MbrID ? '<span class="badge bg-success">Leader</span>' : ''}
                              </div>
                           </div>
                        `).join('')}
                     </div>
                  ` : '<p class="text-muted text-center py-3">No members in this group yet.</p>'}
               </div>
            </div>
         `;
      } catch (error) {
         document.getElementById('viewGroupContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load group details</p>
            </div>
         `;
      }
   }

   async openManageMembersModal(groupId) {
      this.state.currentGroupId = groupId;
      this.manageMembersModal.show();
      await this.loadGroupMembersForManagement(groupId);
   }

   async loadGroupMembersForManagement(groupId) {
      try {
         const group = await this.api.get(groupId);
         const membersRes = await this.api.getMembers(groupId, { limit: 100 });
         const membersList = Array.isArray(membersRes) ? membersRes : (membersRes?.data || []);
         const memberIds = membersList.map(m => m.MbrID);

         if (this.state.addMemberChoices) {
            this.state.addMemberChoices.destroy();
            this.state.addMemberChoices = null;
         }
         
         this.populateMemberSelect('addMemberSelect', memberIds);
         this.state.addMemberChoices = new Choices(document.getElementById('addMemberSelect'), {
            searchEnabled: true,
            searchPlaceholderValue: 'Search members...',
            itemSelectText: ''
         });

         document.getElementById('groupMembersList').innerHTML = membersList.length > 0 ? `
            <div class="list-group">
               ${membersList.map(m => `
                  <div class="list-group-item d-flex align-items-center justify-content-between">
                     <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">
                           ${(m.MbrFirstName?.[0] || '') + (m.MbrFamilyName?.[0] || '')}
                        </div>
                        <div>
                           <div class="fw-semibold">${m.MbrFirstName || ''} ${m.MbrFamilyName || ''}</div>
                           <small class="text-muted">${m.MbrEmailAddress || 'No email'}</small>
                        </div>
                     </div>
                     ${group.GroupLeaderID != m.MbrID ? `
                        <button class="btn btn-outline-danger btn-sm" onclick="removeMemberFromGroup(${groupId}, ${m.MbrID})">
                           <i class="bi bi-x-circle"></i>
                        </button>
                     ` : '<span class="badge bg-success">Leader</span>'}
                  </div>
               `).join('')}
            </div>
         ` : '<p class="text-muted text-center">No members in this group.</p>';
      } catch (error) {
         document.getElementById('groupMembersList').innerHTML = '<p class="text-danger">Failed to load members</p>';
      }
   }

   async addMemberToGroup() {
      const memberId = document.getElementById('addMemberSelect').value;
      if (!memberId) {
         Alerts.warning('Please select a member to add');
         return;
      }

      try {
         Alerts.loading('Adding member...');
         await this.api.addMember(this.state.currentGroupId, parseInt(memberId));
         Alerts.closeLoading();
         Alerts.success('Member added to group');
         
         await this.loadGroupMembersForManagement(this.state.currentGroupId);
         
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

   async removeMemberFromGroup(groupId, memberId) {
      const confirmed = await Alerts.confirm({
         title: 'Remove Member',
         text: 'Remove this member from the group?',
         confirmButtonText: 'Yes, remove'
      });
      if (!confirmed) return;

      try {
         Alerts.loading('Removing member...');
         await this.api.removeMember(groupId, memberId);
         Alerts.closeLoading();
         Alerts.success('Member removed from group');
         
         await this.loadGroupMembersForManagement(groupId);
         
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

   async openGroupTypesModal() {
      this.groupTypesModal.show();
      await this.loadGroupTypesList();
   }

   async loadGroupTypesList() {
      try {
         await this.loadGroupTypes();
         document.getElementById('groupTypesList').innerHTML = this.state.groupTypesData.length > 0 ? `
            <div class="list-group">
               ${this.state.groupTypesData.map(t => `
                  <div class="list-group-item d-flex align-items-center justify-content-between">
                     <div>
                        <i class="bi bi-tag me-2 text-primary"></i>
                        <span class="fw-medium">${t.GroupTypeName}</span>
                     </div>
                     <button class="btn btn-outline-danger btn-sm" onclick="deleteGroupType(${t.GroupTypeID})">
                        <i class="bi bi-trash"></i>
                     </button>
                  </div>
               `).join('')}
            </div>
         ` : '<p class="text-muted text-center">No group types defined.</p>';
      } catch (error) {
         document.getElementById('groupTypesList').innerHTML = '<p class="text-danger">Failed to load types</p>';
      }
   }

   async addGroupType() {
      const name = document.getElementById('newTypeName').value.trim();
      if (!name) {
         Alerts.warning('Please enter a type name');
         return;
      }

      try {
         Alerts.loading('Adding type...');
         await this.api.createGroupType({ name: name });
         Alerts.closeLoading();
         Alerts.success('Group type added');
         document.getElementById('newTypeName').value = '';
         await this.loadGroupTypesList();
         if (this.stats) {
            this.stats.load();
         }
      } catch (error) {
         Alerts.closeLoading();
         Alerts.handleApiError(error);
      }
   }

   async deleteGroupType(typeId) {
      const confirmed = await Alerts.confirm({
         title: 'Delete Group Type',
         text: 'Are you sure? This cannot be undone if no groups use this type.',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting type...');
         await this.api.deleteGroupType(typeId);
         Alerts.closeLoading();
         Alerts.success('Group type deleted');
         await this.loadGroupTypesList();
         if (this.stats) {
            this.stats.load();
         }
      } catch (error) {
         Alerts.closeLoading();
         Alerts.handleApiError(error);
      }
   }
}
