
/**
 * Budget Form Management
 */

export class BudgetForm {
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
         const [branchesRes, fiscalRes] = await Promise.all([
            this.api.getBranches(),
            this.api.getFiscalYears()
         ]);

         this.state.branches = branchesRes?.data?.data || branchesRes?.data || [];
         this.state.fiscalYears = fiscalRes?.data?.data || fiscalRes?.data || [];

         const branchSelect = document.getElementById('branchId');
         if (branchSelect) {
            branchSelect.innerHTML = '<option value="">Select Branch</option>';
            this.state.branches.forEach(b => {
               const opt = document.createElement('option');
               opt.value = b.BranchID;
               opt.textContent = b.BranchName;
               branchSelect.appendChild(opt);
            });
         }

         const fiscalSelect = document.getElementById('fiscalYear');
         if (fiscalSelect) {
            fiscalSelect.innerHTML = '<option value="">Select Fiscal Year</option>';
            this.state.fiscalYears.forEach((fy, index) => {
               const opt = document.createElement('option');
               opt.value = fy.FiscalYearID;
               opt.textContent = fy.FiscalYearName || fy.FiscalYearID;
               if (index === 0 && fy.Status === 'Active') opt.selected = true;
               fiscalSelect.appendChild(opt);
            });
         }
      } catch (error) {
         console.error('Load dropdowns error:', error);
      }
   }

   initEventListeners() {
      document.getElementById('addBudgetBtn')?.addEventListener('click', () => {
         if (!Auth.hasPermission('create_budget')) {
            Alerts.error('You do not have permission to create budgets');
            return;
         }
         this.openModal();
      });

      document.getElementById('saveBudgetBtn')?.addEventListener('click', () => this.save());
      
      // Expose for onclick in HTML
      window.addBudgetItem = () => this.addBudgetItem();
      window.removeBudgetItem = (id) => this.removeBudgetItem(id);
      window.calculateTotal = () => this.calculateTotal();
   }

   openModal() {
      document.getElementById('budgetForm').reset();
      document.getElementById('budgetItemsContainer').innerHTML = '';
      this.state.budgetItemCounter = 0;
      this.addBudgetItem();
      const modal = new bootstrap.Modal(document.getElementById('budgetModal'));
      modal.show();
   }

   addBudgetItem() {
      this.state.budgetItemCounter++;
      const container = document.getElementById('budgetItemsContainer');
      const itemHtml = `
         <div class="card mb-2 budget-item" data-item-id="${this.state.budgetItemCounter}">
            <div class="card-body p-3">
               <div class="row g-2">
                  <div class="col-md-4">
                     <input type="text" class="form-control form-control-sm" placeholder="Category *" data-field="category" required>
                  </div>
                  <div class="col-md-4">
                     <input type="text" class="form-control form-control-sm" placeholder="Description" data-field="description">
                  </div>
                  <div class="col-md-3">
                     <input type="number" class="form-control form-control-sm" placeholder="Amount *" data-field="amount" step="0.01" min="0.01" required onchange="calculateTotal()">
                  </div>
                  <div class="col-md-1">
                     <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBudgetItem(${this.state.budgetItemCounter})">
                        <i class="bi bi-trash"></i>
                     </button>
                  </div>
               </div>
            </div>
         </div>
      `;
      container.insertAdjacentHTML('beforeend', itemHtml);
   }

   removeBudgetItem(itemId) {
      const item = document.querySelector(`[data-item-id="${itemId}"]`);
      if (item) {
         item.remove();
         this.calculateTotal();
      }
   }

   calculateTotal() {
      let total = 0;
      document.querySelectorAll('.budget-item [data-field="amount"]').forEach(input => {
         const value = parseFloat(input.value) || 0;
         total += value;
      });
      document.getElementById('totalBudgetAmount').textContent = formatCurrencyLocale(total);
   }

   async save() {
      const title = document.getElementById('title').value.trim();
      const fiscalYearId = document.getElementById('fiscalYear').value;
      const branchId = document.getElementById('branchId').value;
      const description = document.getElementById('description').value.trim();

      if (!title || !fiscalYearId || !branchId) {
         Alerts.warning('Please fill all required fields');
         return;
      }

      const items = [];
      document.querySelectorAll('.budget-item').forEach(item => {
         const category = item.querySelector('[data-field="category"]').value.trim();
         const desc = item.querySelector('[data-field="description"]').value.trim();
         const amount = parseFloat(item.querySelector('[data-field="amount"]').value);

         if (category && amount > 0) {
            items.push({
               category,
               description: desc || null,
               amount
            });
         }
      });

      if (items.length === 0) {
         Alerts.warning('Please add at least one budget item');
         return;
      }

      const payload = {
         title,
         fiscal_year_id: parseInt(fiscalYearId),
         branch_id: parseInt(branchId),
         description: description || null,
         items
      };

      try {
         Alerts.loading('Creating budget...');
         await this.api.create(payload);
         Alerts.closeLoading();
         Alerts.success('Budget created successfully');
         bootstrap.Modal.getInstance(document.getElementById('budgetModal')).hide();
         this.table.reload();
         this.stats.load();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save budget error:', error);
         Alerts.handleApiError(error);
      }
   }
}
