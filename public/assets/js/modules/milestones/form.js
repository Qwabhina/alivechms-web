/**
 * Milestones Form Component
 */

export class MilestoneForm {
   constructor(state, api, table = null, stats = null) {
      this.state = state;
      this.api = api;
      this.table = table;
      this.stats = stats;
      this.modal = null;
      this.typeModal = null;
      this.currentMilestoneId = null;
      this.currentTypeId = null;
      this.memberChoices = null;
   }

   init() {
      this.modal = new bootstrap.Modal(document.getElementById('milestoneModal'));
      this.typeModal = new bootstrap.Modal(document.getElementById('milestoneTypesModal'));
      this.initEventListeners();
      console.log('✓ Milestone form initialized');
   }

   initEventListeners() {
      // Add milestone button
      document.getElementById('addMilestoneBtn')?.addEventListener('click', () => {
         if (!Auth.hasPermission('manage_milestones')) {
            Alerts.error('You do not have permission to record milestones');
            return;
         }
         this.openMilestoneModal();
      });

      // Save milestone button
      document.getElementById('saveMilestoneBtn')?.addEventListener('click', () => this.saveMilestone());

      // Manage types button
      document.getElementById('manageMilestoneTypesBtn')?.addEventListener('click', () => {
         if (!Auth.hasPermission('manage_milestone_types')) {
            Alerts.error('You do not have permission to manage milestone types');
            return;
         }
         this.openTypeManagementModal();
      });

      // Add type button
      document.getElementById('addMilestoneTypeBtn')?.addEventListener('click', () => this.addMilestoneType());

      // Save type button
      document.getElementById('saveMilestoneTypeBtn')?.addEventListener('click', () => this.updateMilestoneType());
   }

   // ========== Milestone CRUD ==========

   async openMilestoneModal(milestoneId = null) {
      this.currentMilestoneId = milestoneId;
      
      // Reset form
      document.getElementById('milestoneForm').reset();
      document.getElementById('milestoneId').value = '';
      document.getElementById('milestoneModalTitle').innerHTML = '<i class="bi bi-trophy me-2"></i>' + (milestoneId ? 'Edit' : 'Record') + ' Milestone';

      // Load members and types
      await this.loadMembersForMilestone();
      await this.loadTypesForMilestone();

      // If editing, load milestone data
      if (milestoneId) {
         await this.loadMilestoneData(milestoneId);
      }

      this.modal.show();
   }

   async loadMembersForMilestone() {
      try {
         const response = await api.get('member/all?limit=1000');
         const members = Array.isArray(response) ? response : (response?.data || []);
         
         const select = document.getElementById('milestoneMember');
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

   async loadTypesForMilestone() {
      try {
         const response = await this.api.getAllTypes(true);
         const types = response?.data || response;
         
         const select = document.getElementById('milestoneTypeId');
         select.innerHTML = '<option value="">Select milestone type...</option>';
         
         types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            select.appendChild(opt);
         });
      } catch (error) {
         console.error('Failed to load milestone types:', error);
      }
   }

   async loadMilestoneData(milestoneId) {
      try {
         const response = await this.api.get(milestoneId);
         const milestone = response?.data || response;

         // Populate form
         if (this.memberChoices) {
            this.memberChoices.setChoiceByValue(milestone.member_id.toString());
         } else {
            document.getElementById('milestoneMember').value = milestone.member_id;
         }

         document.getElementById('milestoneTypeId').value = milestone.type_id;
         document.getElementById('milestoneDate').value = milestone.date;
         document.getElementById('location').value = milestone.location || '';
         document.getElementById('officiatingPastor').value = milestone.officiating_pastor || '';
         document.getElementById('certificateNumber').value = milestone.certificate_number || '';
         document.getElementById('notes').value = milestone.notes || '';
      } catch (error) {
         console.error('Failed to load milestone:', error);
         Alerts.error('Failed to load milestone data');
      }
   }

   async saveMilestone() {
      const memberId = document.getElementById('milestoneMember').value;
      const typeId = document.getElementById('milestoneTypeId').value;
      const date = document.getElementById('milestoneDate').value;

      if (!memberId || !typeId || !date) {
         Alerts.warning('Please fill in all required fields');
         return;
      }

      const payload = {
         member_id: parseInt(memberId),
         milestone_type_id: parseInt(typeId),
         milestone_date: date,
         location: document.getElementById('location').value.trim() || null,
         officiating_pastor: document.getElementById('officiatingPastor').value.trim() || null,
         certificate_number: document.getElementById('certificateNumber').value.trim() || null,
         notes: document.getElementById('notes').value.trim() || null
      };

      try {
         Alerts.loading(this.currentMilestoneId ? 'Updating milestone...' : 'Recording milestone...');
         
         if (this.currentMilestoneId) {
            await this.api.update(this.currentMilestoneId, payload);
            Alerts.closeLoading();
            Alerts.success('Milestone updated successfully');
         } else {
            await this.api.create(payload);
            Alerts.closeLoading();
            Alerts.success('Milestone recorded successfully');
         }

         this.modal.hide();
         
         // Refresh table and stats
         if (this.table) {
            this.table.refresh();
         }
         if (this.stats) {
            this.stats.load(this.state.getCurrentYear());
         }
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save milestone error:', error);
         Alerts.handleApiError(error);
      }
   }

   async viewMilestone(milestoneId) {
      try {
         const response = await this.api.get(milestoneId);
         const milestone = response?.data || response;

         await Swal.fire({
           title: milestone.type_name,
           html: `
               <div class="text-start">
                  <table class="table table-sm">
                     <tr>
                        <th class="text-muted" style="width: 40%">Member:</th>
                        <td>${milestone.member_name}</td>
                     </tr>
                     <tr>
                        <th class="text-muted">Date:</th>
                        <td>${new Date(milestone.date).toLocaleDateString(
                          "en-US",
                          {
                            year: "numeric",
                            month: "long",
                            day: "numeric",
                          },
                        )}</td>
                     </tr>
                     ${
                       milestone.location
                         ? `
                     <tr>
                        <th class="text-muted">Location:</th>
                        <td>${milestone.location}</td>
                     </tr>
                     `
                         : ""
                     }
                     ${
                       milestone.officiating_pastor
                         ? `
                     <tr>
                        <th class="text-muted">Officiating Pastor:</th>
                        <td>${milestone.officiating_pastor}</td>
                     </tr>
                     `
                         : ""
                     }
                     ${
                       milestone.certificate_number
                         ? `
                     <tr>
                        <th class="text-muted">Certificate #:</th>
                        <td>${milestone.certificate_number}</td>
                     </tr>
                     `
                         : ""
                     }
                     ${
                       milestone.notes
                         ? `
                     <tr>
                        <th class="text-muted">Notes:</th>
                        <td>${milestone.notes}</td>
                     </tr>
                     `
                         : ""
                     }
                     <tr>
                        <th class="text-muted">Recorded By:</th>
                        <td>${milestone.recorder_name || "System"}</td>
                     </tr>
                     <tr>
                        <th class="text-muted">Recorded At:</th>
                        <td>${new Date(milestone.recorded_at).toLocaleString()}</td>
                     </tr>
                  </table>
               </div>
            `,
           icon: "info",
           confirmButtonText: "Close",
           confirmButtonColor: "#0d6efd",
           width: "600px",
         });
      } catch (error) {
         console.error('View milestone error:', error);
         Alerts.error('Failed to load milestone details');
      }
   }

   async deleteMilestone(milestoneId) {
      const confirmed = await Alerts.confirm({
         title: 'Delete Milestone',
         text: 'Are you sure you want to delete this milestone? This action cannot be undone.',
         confirmButtonText: 'Yes, delete it'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting milestone...');
         await this.api.delete(milestoneId);
         Alerts.closeLoading();
         Alerts.success('Milestone deleted successfully');

         // Refresh table and stats
         if (this.table) {
            this.table.refresh();
         }
         if (this.stats) {
            this.stats.load(this.state.getCurrentYear());
         }
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete milestone error:', error);
         Alerts.handleApiError(error);
      }
   }

   // ========== Milestone Type Management ==========

   async openTypeManagementModal() {
      await this.loadTypesForManagement();
      this.typeModal.show();
   }

   async loadTypesForManagement() {
      try {
         const response = await this.api.getAllTypes(false);
         const types = response?.data || response;

         const tbody = document.getElementById('milestoneTypesBody');
         if (!tbody) return;

         if (types.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No milestone types yet</td></tr>';
            return;
         }

         tbody.innerHTML = types.map(type => `
            <tr>
               <td class="fw-semibold">${type.name}</td>
               <td>${type.description || '-'}</td>
               <td><i class="bi bi-${type.icon || 'tag'}"></i></td>
               <td><span class="badge bg-${type.color || 'secondary'}">${type.color || 'secondary'}</span></td>
               <td>
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-outline-success" onclick="editMilestoneType(${type.id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                     </button>
                     <button class="btn btn-outline-danger" onclick="deleteMilestoneType(${type.id})" title="Delete">
                        <i class="bi bi-trash"></i>
                     </button>
                  </div>
               </td>
            </tr>
         `).join('');
      } catch (error) {
         console.error('Failed to load milestone types:', error);
      }
   }

   editMilestoneType = async (typeId) => {
      try {
         const response = await this.api.getType(typeId);
         const type = response?.data || response;
         this.currentTypeId = typeId;
         document.getElementById('editTypeId').value = type.id;
         document.getElementById('editTypeName').value = type.name || '';
         document.getElementById('editTypeDesc').value = type.description || '';
         document.getElementById('editTypeIcon').value = type.icon || '';
         document.getElementById('editTypeColor').value = type.color || 'secondary';
         const modal = new bootstrap.Modal(document.getElementById('editMilestoneTypeModal'));
         modal.show();
      } catch (error) {
         console.error('Failed to load type for edit:', error);
         Alerts.error('Failed to load type details');
      }
   }

   async addMilestoneType() {
      const name = document.getElementById('newTypeName').value.trim();
      const desc = document.getElementById('newTypeDesc').value.trim();
      const icon = document.getElementById('newTypeIcon').value.trim();
      const color = document.getElementById('newTypeColor').value;
 
      if (!name) {
         Alerts.warning('Please enter a milestone type name');
         return;
      }
 
      try {
         Alerts.loading('Adding milestone type...');
         await this.api.createType({
            name: name,
            description: desc,
            icon: icon,
            color: color,
            is_active: 1
         });
         Alerts.closeLoading();
         Alerts.success('Milestone type added successfully');
 
         document.getElementById('newTypeName').value = '';
         document.getElementById('newTypeDesc').value = '';
         document.getElementById('newTypeIcon').value = '';
 
         await this.loadTypesForManagement();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Add type error:', error);
         Alerts.handleApiError(error);
      }
   }

   async updateMilestoneType() {
      const id = document.getElementById('editTypeId').value;
      const name = document.getElementById('editTypeName').value.trim();
      const desc = document.getElementById('editTypeDesc').value.trim();
      const icon = document.getElementById('editTypeIcon').value.trim();
      const color = document.getElementById('editTypeColor').value;
 
      if (!name) {
         Alerts.warning('Please enter a milestone type name');
         return;
      }
 
      try {
         Alerts.loading('Updating milestone type...');
         await this.api.updateType(parseInt(id), {
            name: name,
            description: desc,
            icon: icon,
            color: color
         });
         Alerts.closeLoading();
         Alerts.success('Milestone type updated successfully');
 
         const modal = bootstrap.Modal.getInstance(document.getElementById('editMilestoneTypeModal'));
         if (modal) modal.hide();
 
         await this.loadTypesForManagement();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Update type error:', error);
         Alerts.handleApiError(error);
      }
   }

   async deleteMilestoneType(typeId) {
      const confirmed = await Alerts.confirm({
         title: 'Delete Milestone Type',
         text: 'Are you sure you want to delete this milestone type? This will fail if any milestones use this type.',
         confirmButtonText: 'Yes, delete it'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting milestone type...');
         await this.api.deleteType(typeId);
         Alerts.closeLoading();
         Alerts.success('Milestone type deleted successfully');

         // Reload types list
         await this.loadTypesForManagement();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete type error:', error);
         Alerts.handleApiError(error);
      }
   }
}
