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
      this.typeModal = new bootstrap.Modal(document.getElementById('milestoneTypeModal'));
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
      document.getElementById('addTypeBtn')?.addEventListener('click', () => this.openTypeModal());

      // Save type button
      document.getElementById('saveTypeBtn')?.addEventListener('click', () => this.saveType());
   }

   // ========== Milestone CRUD ==========

   async openMilestoneModal(milestoneId = null) {
      this.currentMilestoneId = milestoneId;
      
      // Reset form
      document.getElementById('milestoneForm').reset();
      document.getElementById('milestoneModalTitle').textContent = milestoneId ? 'Edit Milestone' : 'Record Milestone';

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
         
         const select = document.getElementById('milestoneType');
         select.innerHTML = '<option value="">Select milestone type...</option>';
         
         types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.MilestoneTypeID;
            opt.textContent = t.TypeName;
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
            this.memberChoices.setChoiceByValue(milestone.MbrID.toString());
         } else {
            document.getElementById('milestoneMember').value = milestone.MbrID;
         }

         document.getElementById('milestoneType').value = milestone.MilestoneTypeID;
         document.getElementById('milestoneDate').value = milestone.MilestoneDate;
         document.getElementById('milestoneLocation').value = milestone.Location || '';
         document.getElementById('milestoneOfficiant').value = milestone.OfficiatingPastor || '';
         document.getElementById('milestoneCertificate').value = milestone.CertificateNumber || '';
         document.getElementById('milestoneNotes').value = milestone.Notes || '';
      } catch (error) {
         console.error('Failed to load milestone:', error);
         Alerts.error('Failed to load milestone data');
      }
   }

   async saveMilestone() {
      const memberId = document.getElementById('milestoneMember').value;
      const typeId = document.getElementById('milestoneType').value;
      const date = document.getElementById('milestoneDate').value;

      if (!memberId || !typeId || !date) {
         Alerts.warning('Please fill in all required fields');
         return;
      }

      const payload = {
         member_id: parseInt(memberId),
         milestone_type_id: parseInt(typeId),
         milestone_date: date,
         location: document.getElementById('milestoneLocation').value.trim() || null,
         officiating_pastor: document.getElementById('milestoneOfficiant').value.trim() || null,
         certificate_number: document.getElementById('milestoneCertificate').value.trim() || null,
         notes: document.getElementById('milestoneNotes').value.trim() || null
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
            title: milestone.MilestoneTypeName,
            html: `
               <div class="text-start">
                  <table class="table table-sm">
                     <tr>
                        <th class="text-muted" style="width: 40%">Member:</th>
                        <td>${milestone.MemberName}</td>
                     </tr>
                     <tr>
                        <th class="text-muted">Date:</th>
                        <td>${new Date(milestone.MilestoneDate).toLocaleDateString('en-US', {
                           year: 'numeric',
                           month: 'long',
                           day: 'numeric'
                        })}</td>
                     </tr>
                     ${milestone.Location ? `
                     <tr>
                        <th class="text-muted">Location:</th>
                        <td>${milestone.Location}</td>
                     </tr>
                     ` : ''}
                     ${milestone.OfficiatingPastor ? `
                     <tr>
                        <th class="text-muted">Officiating Pastor:</th>
                        <td>${milestone.OfficiatingPastor}</td>
                     </tr>
                     ` : ''}
                     ${milestone.CertificateNumber ? `
                     <tr>
                        <th class="text-muted">Certificate #:</th>
                        <td>${milestone.CertificateNumber}</td>
                     </tr>
                     ` : ''}
                     ${milestone.Notes ? `
                     <tr>
                        <th class="text-muted">Notes:</th>
                        <td>${milestone.Notes}</td>
                     </tr>
                     ` : ''}
                     <tr>
                        <th class="text-muted">Recorded By:</th>
                        <td>${milestone.RecorderName || 'System'}</td>
                     </tr>
                     <tr>
                        <th class="text-muted">Recorded At:</th>
                        <td>${new Date(milestone.RecordedAt).toLocaleString()}</td>
                     </tr>
                  </table>
               </div>
            `,
            icon: 'info',
            confirmButtonText: 'Close',
            confirmButtonColor: '#0d6efd',
            width: '600px'
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

         const tbody = document.getElementById('typesTableBody');
         if (!tbody) return;

         if (types.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No milestone types yet</td></tr>';
            return;
         }

         tbody.innerHTML = types.map(type => `
            <tr>
               <td class="fw-semibold">${type.TypeName}</td>
               <td>${type.Description || '-'}</td>
               <td>
                  <span class="badge bg-${type.IsActive ? 'success' : 'secondary'}">
                     ${type.IsActive ? 'Active' : 'Inactive'}
                  </span>
               </td>
               <td>
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-outline-success" onclick="editMilestoneType(${type.MilestoneTypeID})" title="Edit">
                        <i class="bi bi-pencil"></i>
                     </button>
                     <button class="btn btn-outline-danger" onclick="deleteMilestoneType(${type.MilestoneTypeID})" title="Delete">
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

   openTypeModal(typeId = null) {
      this.currentTypeId = typeId;
      
      // Reset form
      document.getElementById('typeForm').reset();
      document.getElementById('typeModalTitle').textContent = typeId ? 'Edit Milestone Type' : 'Add Milestone Type';

      // If editing, load type data
      if (typeId) {
         this.loadTypeData(typeId);
      }

      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('typeFormModal'));
      modal.show();
   }

   async loadTypeData(typeId) {
      try {
         const types = this.state.getMilestoneTypes();
         const type = types.find(t => t.MilestoneTypeID === typeId);

         if (type) {
            document.getElementById('typeName').value = type.TypeName;
            document.getElementById('typeDescription').value = type.Description || '';
            document.getElementById('typeIsActive').checked = type.IsActive === 1;
         }
      } catch (error) {
         console.error('Failed to load type data:', error);
      }
   }

   async saveType() {
      const name = document.getElementById('typeName').value.trim();

      if (!name) {
         Alerts.warning('Please enter a milestone type name');
         return;
      }

      const payload = {
         name: name,
         description: document.getElementById('typeDescription').value.trim() || null,
         is_active: document.getElementById('typeIsActive').checked ? 1 : 0
      };

      try {
         Alerts.loading(this.currentTypeId ? 'Updating type...' : 'Creating type...');
         
         if (this.currentTypeId) {
            await this.api.updateType(this.currentTypeId, payload);
            Alerts.closeLoading();
            Alerts.success('Milestone type updated successfully');
         } else {
            await this.api.createType(payload);
            Alerts.closeLoading();
            Alerts.success('Milestone type created successfully');
         }

         // Close type form modal
         const modal = bootstrap.Modal.getInstance(document.getElementById('typeFormModal'));
         if (modal) modal.hide();

         // Reload types list
         await this.loadTypesForManagement();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save type error:', error);
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
