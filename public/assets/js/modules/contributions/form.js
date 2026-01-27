
/**
 * Contribution Form Management
 */

export class ContributionForm {
   constructor(state, api, table, stats) {
      this.state = state;
      this.api = api;
      this.table = table;
      this.stats = stats;
   }

   init() {
      this.initEventListeners();
      this.loadDropdowns();
   }

   async loadDropdowns() {
      try {
         const [membersRes, typesRes, paymentRes] = await Promise.all([
            this.api.getMembers(),
            this.api.getTypes(),
            this.api.getPaymentOptions()
         ]);

         this.state.membersData = Array.isArray(membersRes) ? membersRes : (membersRes?.data || []);
         this.state.typesData = Array.isArray(typesRes) ? typesRes : (typesRes?.data || []);
         this.state.paymentOptionsData = Array.isArray(paymentRes) ? paymentRes : (paymentRes?.data || []);

         // Populate filter type dropdown
         const filterTypeSelect = document.getElementById('filterType');
         if (filterTypeSelect) {
            filterTypeSelect.innerHTML = '<option value="">All Types</option>';
            this.state.typesData.forEach(t => {
               filterTypeSelect.innerHTML += `<option value="${t.ContributionTypeID}">${t.ContributionTypeName}</option>`;
            });
         }
      } catch (error) {
         console.error('Load dropdowns error:', error);
      }
   }

   initEventListeners() {
      document.getElementById('addContributionBtn')?.addEventListener('click', () => {
         if (!Auth.hasPermission('create_contribution')) {
            Alerts.error('You do not have permission to record contributions');
            return;
         }
         this.openModal();
      });

      document.getElementById('saveContributionBtn')?.addEventListener('click', () => this.save());

      // Edit from view modal
      document.getElementById('editContributionFromViewBtn')?.addEventListener('click', () => {
         const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewContributionModal'));
         if (viewModal) viewModal.hide();
         this.edit(this.state.currentContributionId);
      });

      // Types Management
      document.getElementById('manageTypesBtn')?.addEventListener('click', () => this.openTypesModal());
      document.getElementById('addTypeBtn')?.addEventListener('click', () => this.addContributionType());
      document.getElementById('saveTypeBtn')?.addEventListener('click', () => this.saveEditType());
   }

   openModal(contributionId = null) {
      this.state.isEditMode = !!contributionId;
      this.state.currentContributionId = contributionId;

      const form = document.getElementById('contributionForm');
      form.reset();
      document.getElementById('contributionId').value = '';
      
      const title = document.getElementById('contributionModalTitle');
      title.innerHTML = this.state.isEditMode ?
         '<i class="bi bi-pencil-square me-2"></i>Edit Contribution' :
         '<i class="bi bi-cash-coin me-2"></i>Record Contribution';

      // Initialize Choices.js for member select
      const memberSelect = document.getElementById('memberId');
      memberSelect.innerHTML = '<option value="">Select Member</option>';
      this.state.membersData.forEach(m => {
         memberSelect.innerHTML += `<option value="${m.MbrID}">${m.MbrFirstName} ${m.MbrFamilyName}</option>`;
      });
      if (this.state.memberChoices) this.state.memberChoices.destroy();
      this.state.memberChoices = new Choices(memberSelect, {
         searchEnabled: true,
         searchPlaceholderValue: 'Search members...',
         itemSelectText: '',
         allowHTML: true
      });

      // Initialize Choices.js for contribution type select
      const typeSelect = document.getElementById('contributionType');
      typeSelect.innerHTML = '<option value="">Select Type</option>';
      this.state.typesData.forEach(t => {
         typeSelect.innerHTML += `<option value="${t.ContributionTypeID}">${t.ContributionTypeName}</option>`;
      });
      if (this.state.typeChoices) this.state.typeChoices.destroy();
      this.state.typeChoices = new Choices(typeSelect, {
         searchEnabled: true,
         searchPlaceholderValue: 'Search types...',
         itemSelectText: '',
         allowHTML: true
      });

      // Initialize Choices.js for payment option select
      const paymentSelect = document.getElementById('paymentOption');
      paymentSelect.innerHTML = '<option value="">Select Method</option>';
      this.state.paymentOptionsData.forEach(p => {
         paymentSelect.innerHTML += `<option value="${p.PaymentOptionID}">${p.PaymentOptionName}</option>`;
      });
      if (this.state.paymentChoices) this.state.paymentChoices.destroy();
      this.state.paymentChoices = new Choices(paymentSelect, {
         searchEnabled: true,
         searchPlaceholderValue: 'Search methods...',
         itemSelectText: '',
         allowHTML: true
      });

      // Initialize Choices.js for fiscal year select
      const fiscalSelect = document.getElementById('fiscalYear');
      fiscalSelect.innerHTML = '<option value="">Select Fiscal Year</option>';
      this.state.fiscalYears.forEach(fy => {
         const opt = document.createElement('option');
         opt.value = fy.FiscalYearID;
         opt.textContent = fy.FiscalYearName + (fy.Status === 'Active' ? ' (Active)' : '');
         fiscalSelect.appendChild(opt);
      });
      if (this.state.fiscalYearChoices) this.state.fiscalYearChoices.destroy();
      this.state.fiscalYearChoices = new Choices(fiscalSelect, {
         searchEnabled: true,
         searchPlaceholderValue: 'Search fiscal years...',
         itemSelectText: '',
         allowHTML: true
      });

      if (!this.state.isEditMode) {
         document.getElementById('contributionDate').valueAsDate = new Date();
         const activeYear = this.state.fiscalYears.find(fy => fy.Status === 'Active');
         if (activeYear && this.state.fiscalYearChoices) {
            this.state.fiscalYearChoices.setChoiceByValue(String(activeYear.FiscalYearID));
         }
      }

      const modal = new bootstrap.Modal(document.getElementById('contributionModal'));
      modal.show();

      if (this.state.isEditMode) this.loadForEdit(contributionId);
   }

   async loadForEdit(contributionId) {
      try {
         Alerts.loading('Loading contribution...');
         const c = await this.api.get(contributionId);
         Alerts.closeLoading();

         document.getElementById('contributionId').value = c.ContributionID;
         document.getElementById('amount').value = c.ContributionAmount;
         document.getElementById('contributionDate').value = c.ContributionDate;
         document.getElementById('description').value = c.Notes || '';

         // Set Choices.js values
         if (this.state.memberChoices && c.MbrID) {
            this.state.memberChoices.setChoiceByValue(String(c.MbrID));
         }
         if (this.state.typeChoices && c.ContributionTypeID) {
            this.state.typeChoices.setChoiceByValue(String(c.ContributionTypeID));
         }
         if (this.state.paymentChoices && c.PaymentOptionID) {
            this.state.paymentChoices.setChoiceByValue(String(c.PaymentOptionID));
         }
         if (this.state.fiscalYearChoices && c.FiscalYearID) {
            this.state.fiscalYearChoices.setChoiceByValue(String(c.FiscalYearID));
         }
      } catch (error) {
         Alerts.closeLoading();
         console.error('Load contribution error:', error);
         Alerts.error('Failed to load contribution data');
      }
   }

   async save() {
      const memberId = document.getElementById('memberId').value;
      const amount = document.getElementById('amount').value;
      const date = document.getElementById('contributionDate').value;
      const typeId = document.getElementById('contributionType').value;
      const paymentId = document.getElementById('paymentOption').value;
      const fiscalYearId = document.getElementById('fiscalYear').value;

      if (!memberId || !amount || !date || !typeId || !paymentId || !fiscalYearId) {
         Alerts.warning('Please fill all required fields');
         return;
      }

      const payload = {
         member_id: parseInt(memberId),
         amount: parseFloat(amount),
         date: date,
         contribution_type_id: parseInt(typeId),
         payment_option_id: parseInt(paymentId),
         fiscal_year_id: parseInt(fiscalYearId),
         description: document.getElementById('description').value.trim() || null
      };

      try {
         Alerts.loading('Saving contribution...');
         if (this.state.isEditMode) {
            await this.api.update(this.state.currentContributionId, payload);
            Alerts.success('Contribution updated successfully');
         } else {
            await this.api.create(payload);
            Alerts.success('Contribution recorded successfully');
         }
         
         Alerts.closeLoading();
         bootstrap.Modal.getInstance(document.getElementById('contributionModal')).hide();
         
         this.table.reload();
         this.stats.load();
      } catch (error) {
         Alerts.closeLoading();
         Alerts.handleApiError(error);
      }
   }

   edit(id) {
      if (!Auth.hasPermission('edit_contribution')) {
         Alerts.error('You do not have permission to edit contributions');
         return;
      }
      this.openModal(id);
   }

   async delete(id) {
      if (!Auth.hasPermission('delete_contribution')) {
         Alerts.error('You do not have permission to delete contributions');
         return;
      }

      const confirmed = await Alerts.confirm('Are you sure you want to delete this contribution?');
      if (!confirmed) return;

      try {
         Alerts.loading('Deleting...');
         await this.api.delete(id);
         Alerts.closeLoading();
         Alerts.success('Contribution deleted successfully');
         this.table.reload();
         this.stats.load();
      } catch (error) {
         Alerts.closeLoading();
         Alerts.handleApiError(error);
      }
   }

   async restore(id) {
       // Check permission if needed
       const confirmed = await Alerts.confirm('Restore this contribution?');
       if (!confirmed) return;

       try {
           Alerts.loading('Restoring...');
           await this.api.restore(id);
           Alerts.closeLoading();
           Alerts.success('Contribution restored');
           this.table.reload();
           this.stats.load();
       } catch (error) {
           Alerts.closeLoading();
           Alerts.handleApiError(error);
       }
   }

   // ========== TYPES MANAGEMENT ==========

   async openTypesModal() {
      new bootstrap.Modal(document.getElementById('typesModal')).show();
      await this.loadTypesTable();
   }

   async loadTypesTable() {
      const tbody = document.getElementById('typesBody');
      tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>';

      try {
         const res = await this.api.getTypes();
         const types = Array.isArray(res) ? res : (res?.data || []);
         this.state.typesData = types;

         if (types.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No contribution types found</td></tr>';
            return;
         }

         tbody.innerHTML = types.map(t => `
            <tr>
               <td class="fw-medium">${t.ContributionTypeName}</td>
               <td class="text-muted">${t.ContributionTypeDescription || '-'}</td>
               <td>
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-warning btn-sm" onclick="editContributionType(${t.ContributionTypeID}, '${t.ContributionTypeName.replace(/'/g, "\\'")}', '${(t.ContributionTypeDescription || '').replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil"></i></button>
                     <button class="btn btn-danger btn-sm" onclick="deleteContributionType(${t.ContributionTypeID}, '${t.ContributionTypeName.replace(/'/g, "\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
                  </div>
               </td>
            </tr>
         `).join('');

         // Update filter dropdown
         const filterTypeSelect = document.getElementById('filterType');
         filterTypeSelect.innerHTML = '<option value="">All Types</option>';
         types.forEach(t => {
            filterTypeSelect.innerHTML += `<option value="${t.ContributionTypeID}">${t.ContributionTypeName}</option>`;
         });
      } catch (error) {
         console.error('Load types error:', error);
         tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Failed to load types</td></tr>';
      }
   }

   async addContributionType() {
      const name = document.getElementById('newTypeName').value.trim();
      const desc = document.getElementById('newTypeDesc').value.trim();

      if (!name) {
         Alerts.warning('Please enter a type name');
         return;
      }

      try {
         Alerts.loading('Adding type...');
         await this.api.createType({
            name,
            description: desc || null
         });
         Alerts.closeLoading();
         Alerts.success('Contribution type added');
         document.getElementById('newTypeName').value = '';
         document.getElementById('newTypeDesc').value = '';
         await this.loadTypesTable();
         await this.loadDropdowns(); // Reload main dropdowns
      } catch (error) {
         Alerts.closeLoading();
         Alerts.handleApiError(error);
      }
   }

   editType(id, name, desc) {
      this.state.editingTypeId = id;
      document.getElementById('editTypeId').value = id;
      document.getElementById('editTypeName').value = name;
      document.getElementById('editTypeDesc').value = desc || '';
      new bootstrap.Modal(document.getElementById('editTypeModal')).show();
   }

   async saveEditType() {
      const id = this.state.editingTypeId;
      const name = document.getElementById('editTypeName').value.trim();
      const desc = document.getElementById('editTypeDesc').value.trim();

      if (!name) {
         Alerts.warning('Please enter a type name');
         return;
      }

      try {
         Alerts.loading('Saving...');
         await this.api.updateType(id, {
            name,
            description: desc || null
         });
         Alerts.closeLoading();
         Alerts.success('Contribution type updated');
         bootstrap.Modal.getInstance(document.getElementById('editTypeModal')).hide();
         await this.loadTypesTable();
         await this.loadDropdowns(); // Reload main dropdowns
      } catch (error) {
         Alerts.closeLoading();
         Alerts.handleApiError(error);
      }
   }

   async deleteType(id, name) {
      const confirmed = await Alerts.confirm(`Delete contribution type "${name}"?`, 'This cannot be undone. Types in use cannot be deleted.');
      if (!confirmed) return;

      try {
         Alerts.loading('Deleting...');
         await this.api.deleteType(id);
         Alerts.closeLoading();
         Alerts.success('Contribution type deleted');
         await this.loadTypesTable();
         await this.loadDropdowns(); // Reload main dropdowns
      } catch (error) {
         Alerts.closeLoading();
         Alerts.handleApiError(error);
      }
   }
}
