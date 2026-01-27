import { Alerts } from '../../utils/alerts.js';
import { Auth } from '../../utils/auth.js';
import { Config } from '../../utils/config.js';

export class ExpenseForm {
    constructor(state, api, table, stats) {
        this.state = state;
        this.api = api;
        this.table = table;
        this.stats = stats;
    }

    init() {
        document.getElementById('addExpenseBtn')?.addEventListener('click', () => {
            if (!Auth.hasPermission('expenses.create')) {
                Alerts.error('You do not have permission to request expenses');
                return;
            }
            this.openExpenseModal();
        });
        document.getElementById('saveExpenseBtn')?.addEventListener('click', () => this.saveExpense());
        document.getElementById('refreshGrid')?.addEventListener('click', () => {
            this.table.reload();
            this.stats.load();
        });
        document.getElementById('applyFiltersBtn')?.addEventListener('click', () => this.table.applyFilters());
        document.getElementById('clearFiltersBtn')?.addEventListener('click', () => this.table.clearFilters());
        document.getElementById('approveExpenseBtn')?.addEventListener('click', () => this.submitReview('approve'));
        document.getElementById('declineExpenseBtn')?.addEventListener('click', () => this.submitReview('reject'));
        document.getElementById('proofFileInput')?.addEventListener('change', (e) => this.handleProofFileSelect(e));
        document.getElementById('removeProofUploadBtn')?.addEventListener('click', () => this.removeProofFile());
        document.getElementById('submitProofBtn')?.addEventListener('click', () => this.submitProofUpload());

        // Category Management
        document.getElementById('manageCategoriesBtn')?.addEventListener('click', () => this.openCategoriesModal());
        document.getElementById('addCategoryBtn')?.addEventListener('click', () => this.addCategory());
        document.getElementById('saveCategoryBtn')?.addEventListener('click', () => this.saveEditCategory());
    }

    openExpenseModal() {
        document.getElementById('expenseForm').reset();
        document.getElementById('expenseId').value = '';
        document.getElementById('expenseModalTitle').innerHTML = '<i class="bi bi-receipt me-2"></i>Request Expense';
        document.getElementById('expenseDate').valueAsDate = new Date();

        // Initialize Choices.js for category select
        const categorySelect = document.getElementById('categoryId');
        categorySelect.innerHTML = '<option value="">Select Category</option>';
        this.state.categoriesData.forEach(c => {
            categorySelect.innerHTML += `<option value="${c.ExpCategoryID}">${c.CategoryName}</option>`;
        });
        if (this.state.categoryChoices) this.state.categoryChoices.destroy();
        this.state.categoryChoices = new Choices(categorySelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search categories...',
            itemSelectText: '',
            allowHTML: true
        });

        // Initialize Choices.js for fiscal year select
        const fiscalSelect = document.getElementById('fiscalYear');
        fiscalSelect.innerHTML = '<option value="">Select Fiscal Year</option>';
        this.state.fiscalYearsData.forEach(fy => {
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

        // Set default fiscal year
        const activeYear = this.state.fiscalYearsData.find(fy => fy.Status === 'Active');
        if (activeYear && this.state.fiscalYearChoices) {
            this.state.fiscalYearChoices.setChoiceByValue(String(activeYear.FiscalYearID));
        }

        new bootstrap.Modal(document.getElementById('expenseModal')).show();
    }

    async saveExpense() {
        const title = document.getElementById('title').value.trim();
        const amount = document.getElementById('amount').value;
        const expenseDate = document.getElementById('expenseDate').value;
        const categoryId = document.getElementById('categoryId').value;
        const fiscalYearId = document.getElementById('fiscalYear').value;
        const purpose = document.getElementById('purpose').value.trim();

        if (!title || !amount || !expenseDate || !categoryId || !fiscalYearId) {
            Alerts.warning('Please fill all required fields');
            return;
        }

        try {
            Alerts.loading('Submitting expense request...');
            await this.api.createExpense({
                title,
                amount: parseFloat(amount),
                expense_date: expenseDate,
                category_id: parseInt(categoryId),
                fiscal_year_id: parseInt(fiscalYearId),
                purpose: purpose || null
            });
            Alerts.closeLoading();
            Alerts.success('Expense request submitted successfully');
            bootstrap.Modal.getInstance(document.getElementById('expenseModal')).hide();
            this.table.reload();
            this.stats.load();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }

    async viewExpense(expenseId) {
        this.state.currentExpenseId = expenseId;
        const modal = new bootstrap.Modal(document.getElementById('viewExpenseModal'));
        modal.show();

        try {
            const e = await this.api.getExpense(expenseId);
            this.state.currentExpenseData = e;
            const statusColors = {
                'Pending Approval': '#ffc107',
                'Approved': '#198754',
                'Declined': '#dc3545'
            };
            const statusColor = statusColors[e.ExpenseStatus] || '#6c757d';
            const isApproved = e.ExpenseStatus === 'Approved';
            const hasProof = !!e.ProofFile;

            document.getElementById('viewExpenseContent').innerHTML = `
         <div class="expense-view" id="printableExpense">
            <div class="text-center py-4" style="background: linear-gradient(135deg, ${statusColor} 0%, ${statusColor}99 100%);">
               <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                  <i class="bi bi-receipt" style="font-size:2rem;color:${statusColor};"></i>
               </div>
               <h2 class="text-white mb-1">${this.stats.formatCurrency(e.ExpenseAmount)}</h2>
               <p class="text-white-50 mb-0">${e.ExpenseTitle}</p>
               <span class="badge bg-white text-dark mt-2">${e.ExpenseStatus}</span>
            </div>
            <div class="p-4">
               <div class="row g-3 mb-3">
                  <div class="col-6"><div class="text-muted small text-uppercase">Category</div><div class="fw-semibold">${e.CategoryName || '-'}</div></div>
                  <div class="col-6"><div class="text-muted small text-uppercase">Date</div><div class="fw-semibold">${new Date(e.ExpenseDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-6"><div class="text-muted small text-uppercase">Fiscal Year</div><div>${e.FiscalYearName || '-'}</div></div>
                  <div class="col-6"><div class="text-muted small text-uppercase">Branch</div><div>${e.BranchName || '-'}</div></div>
               </div>
               ${e.ExpensePurpose ? `<div class="mb-3 pt-3 border-top"><div class="text-muted small text-uppercase">Purpose</div><div>${e.ExpensePurpose}</div></div>` : ''}
               ${e.ProofFile ? `<div class="mb-3 pt-3 border-top"><div class="text-muted small text-uppercase">Proof of Payment</div><div class="mt-2"><a href="${Config.API_BASE_URL}/../${e.ProofFile}" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-check me-1"></i>View Document</a></div></div>` : ''}
               <div class="row g-3 pt-3 border-top">
                  <div class="col-6">
                     <div class="text-muted small text-uppercase">Requested By</div>
                     <div>${e.RequesterFirstName ? `${e.RequesterFirstName} ${e.RequesterFamilyName}` : '-'}</div>
                     ${e.RequestedAt ? `<small class="text-muted">${new Date(e.RequestedAt).toLocaleString()}</small>` : ''}
                  </div>
                  ${e.ApproverFirstName ? `<div class="col-6"><div class="text-muted small text-uppercase">${e.ExpenseStatus === 'Approved' ? 'Approved' : 'Reviewed'} By</div><div>${e.ApproverFirstName} ${e.ApproverFamilyName}</div>${e.ApprovedAt ? `<small class="text-muted">${new Date(e.ApprovedAt).toLocaleString()}</small>` : ''}</div>` : ''}
               </div>
               ${e.ApprovalRemarks ? `<div class="mt-3 pt-3 border-top"><div class="text-muted small text-uppercase">Remarks</div><div>${e.ApprovalRemarks}</div></div>` : ''}
            </div>
         </div>`;

            let footerHtml = `<button type="button" class="btn btn-outline-secondary" onclick="printExpense()"><i class="bi bi-printer me-1"></i>Print</button>`;
            if (e.ExpenseStatus === 'Pending Approval') {
                footerHtml += `<button type="button" class="btn btn-success" onclick="reviewExpense(${expenseId})"><i class="bi bi-clipboard-check me-1"></i>Review</button>`;
            }
            if (isApproved && !hasProof) {
                footerHtml += `<button type="button" class="btn btn-warning" onclick="uploadProof(${expenseId})"><i class="bi bi-upload me-1"></i>Upload Proof</button>`;
            }
            footerHtml += `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
            document.getElementById('viewExpenseFooter').innerHTML = footerHtml;
        } catch (error) {
            console.error('View expense error:', error);
            document.getElementById('viewExpenseContent').innerHTML = `<div class="text-center text-danger py-5"><i class="bi bi-exclamation-circle fs-1"></i><p class="mt-2">Failed to load expense details</p></div>`;
        }
    }

    async reviewExpense(expenseId) {
        if (!Auth.hasPermission('expenses.approve')) {
            Alerts.error('You do not have permission to review expenses');
            return;
        }
        this.state.currentExpenseId = expenseId;
        const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewExpenseModal'));
        if (viewModal) viewModal.hide();

        try {
            const e = await this.api.getExpense(expenseId);
            document.getElementById('reviewExpenseDetails').innerHTML = `
         <div class="card bg-light">
            <div class="card-body">
               <h6 class="mb-2">${e.ExpenseTitle}</h6>
               <div class="row">
                  <div class="col-6"><small class="text-muted">Amount</small><div class="fw-bold text-danger">${this.stats.formatCurrency(e.ExpenseAmount)}</div></div>
                  <div class="col-6"><small class="text-muted">Category</small><div>${e.CategoryName || '-'}</div></div>
               </div>
               <div class="row mt-2">
                  <div class="col-6"><small class="text-muted">Date</small><div>${new Date(e.ExpenseDate).toLocaleDateString()}</div></div>
                  <div class="col-6"><small class="text-muted">Fiscal Year</small><div>${e.FiscalYearName || '-'}</div></div>
               </div>
               ${e.ExpensePurpose ? `<div class="mt-2"><small class="text-muted">Purpose</small><div>${e.ExpensePurpose}</div></div>` : ''}
            </div>
         </div>`;
            document.getElementById('reviewRemarks').value = '';
            new bootstrap.Modal(document.getElementById('reviewExpenseModal')).show();
        } catch (error) {
            console.error('Load expense for review error:', error);
            Alerts.error('Failed to load expense details');
        }
    }

    async submitReview(action) {
        const remarks = document.getElementById('reviewRemarks').value.trim();
        try {
            Alerts.loading(`${action === 'approve' ? 'Approving' : 'Declining'} expense...`);
            await this.api.reviewExpense(this.state.currentExpenseId, action, remarks);
            Alerts.closeLoading();
            Alerts.success(`Expense ${action === 'approve' ? 'approved' : 'declined'} successfully`);
            bootstrap.Modal.getInstance(document.getElementById('reviewExpenseModal')).hide();
            this.table.reload();
            this.stats.load();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }

    uploadProof(expenseId) {
        this.state.currentExpenseId = expenseId;
        const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewExpenseModal'));
        if (viewModal) viewModal.hide();

        document.getElementById('proofFileInput').value = '';
        document.getElementById('proofUploadPreview').classList.add('d-none');

        const e = this.state.currentExpenseData;
        document.getElementById('uploadProofDetails').innerHTML = e ? `
      <div class="card bg-light">
         <div class="card-body">
            <h6 class="mb-2">${e.ExpenseTitle}</h6>
            <div class="row">
               <div class="col-6"><small class="text-muted">Amount</small><div class="fw-bold text-danger">${this.stats.formatCurrency(e.ExpenseAmount)}</div></div>
               <div class="col-6"><small class="text-muted">Status</small><div><span class="badge bg-success">${e.ExpenseStatus}</span></div></div>
            </div>
         </div>
      </div>` : '';

        new bootstrap.Modal(document.getElementById('uploadProofModal')).show();
    }

    handleProofFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;
        const maxSize = 5 * 1024 * 1024;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (file.size > maxSize) {
            Alerts.warning('File size exceeds 5MB limit');
            e.target.value = '';
            return;
        }
        if (!allowedTypes.includes(file.type)) {
            Alerts.warning('Invalid file type. Allowed: JPG, PNG, GIF, PDF');
            e.target.value = '';
            return;
        }
        document.getElementById('proofUploadFileName').textContent = file.name;
        document.getElementById('proofUploadPreview').classList.remove('d-none');
    }

    removeProofFile() {
        document.getElementById('proofFileInput').value = '';
        document.getElementById('proofUploadPreview').classList.add('d-none');
    }

    async submitProofUpload() {
        const fileInput = document.getElementById('proofFileInput');
        if (!fileInput.files[0]) {
            Alerts.warning('Please select a file to upload');
            return;
        }

        const formData = new FormData();
        formData.append('proof', fileInput.files[0]);

        try {
            Alerts.loading('Uploading proof...');
            await this.api.uploadProof(this.state.currentExpenseId, formData);
            Alerts.closeLoading();
            Alerts.success('Proof uploaded successfully');
            bootstrap.Modal.getInstance(document.getElementById('uploadProofModal')).hide();
            this.table.reload();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.error('Failed to upload proof');
        }
    }

    printExpense() {
        const e = this.state.currentExpenseData;
        if (!e) return;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
         <title>Expense Details - ${e.ExpenseTitle}</title>
         <style>
            body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { margin: 0; font-size: 24px; }
            .header p { margin: 5px 0; color: #666; }
            .amount { font-size: 32px; font-weight: bold; color: #dc3545; text-align: center; margin: 20px 0; }
            .status { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
            .status-approved { background: #d4edda; color: #155724; }
            .status-pending { background: #fff3cd; color: #856404; }
            .status-declined { background: #f8d7da; color: #721c24; }
            .details { margin: 20px 0; }
            .row { display: flex; border-bottom: 1px solid #eee; padding: 10px 0; }
            .label { width: 150px; font-weight: bold; color: #666; }
            .value { flex: 1; }
            .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #999; }
            @media print { body { padding: 0; } }
         </style>
      </head>
      <body>
         <div class="header">
            <h1>EXPENSE VOUCHER</h1>
            <p>Reference: EXP-${String(e.ExpenseID).padStart(6, '0')}</p>
         </div>
         <div class="amount">${this.stats.formatCurrency(e.ExpenseAmount)}</div>
         <div style="text-align: center; margin-bottom: 20px;">
            <span class="status status-${e.ExpenseStatus === 'Approved' ? 'approved' : e.ExpenseStatus === 'Pending Approval' ? 'pending' : 'declined'}">${e.ExpenseStatus}</span>
         </div>
         <div class="details">
            <div class="row"><div class="label">Title:</div><div class="value">${e.ExpenseTitle}</div></div>
            <div class="row"><div class="label">Category:</div><div class="value">${e.CategoryName || '-'}</div></div>
            <div class="row"><div class="label">Date:</div><div class="value">${new Date(e.ExpenseDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>
            <div class="row"><div class="label">Fiscal Year:</div><div class="value">${e.FiscalYearName || '-'}</div></div>
            ${e.BranchName ? `<div class="row"><div class="label">Branch:</div><div class="value">${e.BranchName}</div></div>` : ''}
            ${e.ExpensePurpose ? `<div class="row"><div class="label">Purpose:</div><div class="value">${e.ExpensePurpose}</div></div>` : ''}
            <div class="row"><div class="label">Requested By:</div><div class="value">${e.RequesterFirstName ? `${e.RequesterFirstName} ${e.RequesterFamilyName}` : '-'}</div></div>
            ${e.RequestedAt ? `<div class="row"><div class="label">Requested At:</div><div class="value">${new Date(e.RequestedAt).toLocaleString()}</div></div>` : ''}
            ${e.ApproverFirstName ? `<div class="row"><div class="label">${e.ExpenseStatus === 'Approved' ? 'Approved' : 'Reviewed'} By:</div><div class="value">${e.ApproverFirstName} ${e.ApproverFamilyName}</div></div>` : ''}
            ${e.ApprovedAt ? `<div class="row"><div class="label">${e.ExpenseStatus === 'Approved' ? 'Approved' : 'Reviewed'} At:</div><div class="value">${new Date(e.ApprovedAt).toLocaleString()}</div></div>` : ''}
            ${e.ApprovalRemarks ? `<div class="row"><div class="label">Remarks:</div><div class="value">${e.ApprovalRemarks}</div></div>` : ''}
            ${e.ProofFile ? `<div class="row"><div class="label">Proof:</div><div class="value">Document attached</div></div>` : ''}
         </div>
         <div class="footer">
            <p>Printed on ${new Date().toLocaleString()}</p>
         </div>
      </body>
      </html>`);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 250);
    }

    async openCategoriesModal() {
        new bootstrap.Modal(document.getElementById('categoriesModal')).show();
        await this.loadCategoriesTable();
    }

    async loadCategoriesTable() {
        const tbody = document.getElementById('categoriesBody');
        tbody.innerHTML = '<tr><td colspan="2" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>';

        try {
            const categories = await this.api.getCategories();
            this.state.categoriesData = Array.isArray(categories) ? categories : (categories?.data || []);

            if (this.state.categoriesData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">No categories found</td></tr>';
                return;
            }

            tbody.innerHTML = this.state.categoriesData.map(c => `
               <tr>
                  <td class="fw-medium">${c.CategoryName}</td>
                  <td>
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning btn-sm" onclick="editCategory(${c.ExpCategoryID}, '${c.CategoryName.replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteCategory(${c.ExpCategoryID}, '${c.CategoryName.replace(/'/g, "\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
                     </div>
                  </td>
               </tr>
            `).join('');

            // Update filter dropdown
            const filterCategorySelect = document.getElementById('filterCategory');
            filterCategorySelect.innerHTML = '<option value="">All Categories</option>';
            this.state.categoriesData.forEach(c => {
                filterCategorySelect.innerHTML += `<option value="${c.ExpCategoryID}">${c.CategoryName}</option>`;
            });
        } catch (error) {
            console.error('Load categories error:', error);
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger py-3">Failed to load categories</td></tr>';
        }
    }

    async addCategory() {
        const name = document.getElementById('newCategoryName').value.trim();

        if (!name) {
            Alerts.warning('Please enter a category name');
            return;
        }

        try {
            Alerts.loading('Adding category...');
            await this.api.createCategory(name);
            Alerts.closeLoading();
            Alerts.success('Category added');
            document.getElementById('newCategoryName').value = '';
            await this.loadCategoriesTable();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }

    editCategory(id, name) {
        this.state.editingCategoryId = id;
        document.getElementById('editCategoryId').value = id;
        document.getElementById('editCategoryName').value = name;
        new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
    }

    async saveEditCategory() {
        const id = this.state.editingCategoryId;
        const name = document.getElementById('editCategoryName').value.trim();

        if (!name) {
            Alerts.warning('Please enter a category name');
            return;
        }

        try {
            Alerts.loading('Saving...');
            await this.api.updateCategory(id, name);
            Alerts.closeLoading();
            Alerts.success('Category updated');
            bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide();
            await this.loadCategoriesTable();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }

    async deleteCategory(id, name) {
        const confirmed = await Alerts.confirm(`Delete category "${name}"?`, 'This cannot be undone. Categories in use cannot be deleted.');
        if (!confirmed) return;

        try {
            Alerts.loading('Deleting...');
            await this.api.deleteCategory(id);
            Alerts.closeLoading();
            Alerts.success('Category deleted');
            await this.loadCategoriesTable();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }
}
