/**
 * Volunteers Form Component
 */

export class VolunteerForm {
   constructor(state, api, table = null, stats = null) {
      this.state = state;
      this.api = api;
      this.table = table;
      this.stats = stats;
      this.modal = null;
   }

   init() {
      this.modal = new bootstrap.Modal(document.getElementById('roleModal'));
      this.manageMembersModal = new bootstrap.Modal(document.getElementById('manageMembersModal'));
      this.initEventListeners();
      console.log('✓ Volunteer form initialized');
   }

   initEventListeners() {
      // Add role button
      document.getElementById('addRoleBtn')?.addEventListener('click', () => {
         if (!Auth.hasPermission('manage_volunteer_roles')) {
            Alerts.error('You do not have permission to create volunteer roles');
            return;
         }
         this.open();
      });

      // Save button
      document.getElementById('saveRoleBtn')?.addEventListener('click', () => this.save());

      // Assign member button
      document.getElementById('assignMemberBtn')?.addEventListener('click', () => this.assignMember());
   }

   async open() {
      document.getElementById('roleForm').reset();
      this.modal.show();
   }

   async save() {
      const roleName = document.getElementById('roleName').value.trim();
      
      if (!roleName) {
         Alerts.warning('Role name is required');
         return;
      }

      const payload = {
         name: roleName,
         description: document.getElementById('roleDescription').value.trim() || null
      };

      try {
         Alerts.loading('Saving role...');
         await this.api.createRole(payload);
         Alerts.closeLoading();
         Alerts.success('Volunteer role created successfully');
         
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
         console.error('Save role error:', error);
         Alerts.handleApiError(error);
      }
   }

   async viewRoleDetails(roleId) {
      const role = this.state.getRoleById(roleId);
      if (!role) return;

      const memberCount = role.MemberCount || 0;
      const description = role.Description || 'No description provided';

      await Swal.fire({
         title: role.RoleName,
         html: `
            <div class="text-start">
               <p class="mb-2"><strong>Description:</strong></p>
               <p>${description}</p>
               <p class="mb-2 mt-3"><strong>Members:</strong></p>
               <p>${memberCount} ${memberCount === 1 ? 'member' : 'members'} assigned to this role</p>
            </div>
         `,
         icon: 'info',
         confirmButtonText: 'Close',
         confirmButtonColor: '#0d6efd'
      });
   }

   async manageRoleMembers(roleId) {
      const role = this.state.getRoleById(roleId);
      if (!role) return;

      this.state.currentRoleId = roleId;
      document.getElementById('roleNameTitle').textContent = role.RoleName;

      // Load all members for assignment dropdown
      await this.loadMembersForAssignment();

      // Load current role members
      await this.loadRoleMembers(roleId);

      this.manageMembersModal.show();
   }

   async loadMembersForAssignment() {
      try {
         const response = await api.get('member/all?limit=1000');
         const members = Array.isArray(response) ? response : (response?.data || []);
         
         const select = document.getElementById('assignMemberSelect');
         select.innerHTML = '<option value="">Select a member...</option>';
         
         members.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.MbrID;
            opt.textContent = `${m.MbrFirstName || ''} ${m.MbrFamilyName || ''}`.trim();
            select.appendChild(opt);
         });

         // Initialize Choices.js if available
         if (window.Choices && !this.memberChoices) {
            this.memberChoices = new Choices(select, {
               searchEnabled: true,
               searchPlaceholderValue: 'Search members...',
               itemSelectText: ''
            });
         }
      } catch (error) {
         console.error('Failed to load members:', error);
      }
   }

   async loadRoleMembers(roleId) {
      try {
         const response = await this.api.getMembersByRole(roleId, { limit: 100 });
         const members = Array.isArray(response) ? response : (response?.data || []);

         const container = document.getElementById('roleMembersList');
         
         if (members.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No members assigned to this role yet.</p>';
            return;
         }

         container.innerHTML = `
            <div class="list-group">
               ${members.map(m => `
                  <div class="list-group-item d-flex align-items-center justify-content-between">
                     <div class="d-flex align-items-center">
                        <div class="me-3">
                           ${m.MbrProfilePicture 
                              ? `<img src="${Config.API_BASE_URL}/public/${m.MbrProfilePicture}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">` 
                              : `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                    ${(m.MbrFirstName?.[0] || '') + (m.MbrFamilyName?.[0] || '')}
                                 </div>`
                           }
                        </div>
                        <div>
                           <div class="fw-semibold">${m.MbrFirstName || ''} ${m.MbrFamilyName || ''}</div>
                           <small class="text-muted">${m.MbrEmailAddress || 'No email'}</small>
                        </div>
                     </div>
                     <button class="btn btn-outline-danger btn-sm" onclick="removeRoleMember(${m.MemberVolunteerRoleID})">
                        <i class="bi bi-x-circle"></i> Remove
                     </button>
                  </div>
               `).join('')}
            </div>
         `;
      } catch (error) {
         console.error('Failed to load role members:', error);
         document.getElementById('roleMembersList').innerHTML = '<p class="text-danger">Failed to load members</p>';
      }
   }

   async assignMember() {
      const memberId = document.getElementById('assignMemberSelect').value;
      
      if (!memberId) {
         Alerts.warning('Please select a member to assign');
         return;
      }

      const payload = {
         role_id: this.state.currentRoleId
      };

      try {
         Alerts.loading('Assigning member...');
         await this.api.assignRoleToMember(parseInt(memberId), payload);
         Alerts.closeLoading();
         Alerts.success('Member assigned to volunteer role');

         // Reset selection
         if (this.memberChoices) {
            this.memberChoices.setChoiceByValue('');
         } else {
            document.getElementById('assignMemberSelect').value = '';
         }

         // Reload role members
         await this.loadRoleMembers(this.state.currentRoleId);

         // Refresh table to update counts
         if (this.table) {
            this.table.refresh();
         }
      } catch (error) {
         Alerts.closeLoading();
         console.error('Failed to assign member:', error);
         Alerts.handleApiError(error);
      }
   }

   async removeRoleMember(assignmentId) {
      const confirmed = await Alerts.confirm({
         title: 'Remove Volunteer',
         text: 'Remove this member from the volunteer role?',
         confirmButtonText: 'Yes, remove'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Removing member...');
         await this.api.removeRoleFromMember(assignmentId);
         Alerts.closeLoading();
         Alerts.success('Member removed from volunteer role');

         // Reload role members
         await this.loadRoleMembers(this.state.currentRoleId);

         // Refresh table to update counts
         if (this.table) {
            this.table.refresh();
         }
      } catch (error) {
         Alerts.closeLoading();
         console.error('Failed to remove member:', error);
         Alerts.handleApiError(error);
      }
   }
}
