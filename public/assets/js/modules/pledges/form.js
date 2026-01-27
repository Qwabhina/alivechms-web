import { Alerts } from '../../utils/alerts.js';
import { Auth } from '../../utils/auth.js';

export class PledgeForm {
    constructor(state, api, table, stats) {
        this.state = state;
        this.api = api;
        this.table = table;
        this.stats = stats;
    }

    init() {
        document.getElementById('addPledgeBtn')?.addEventListener('click', () => {
            if (!Auth.hasPermission('manage_pledges')) {
                Alerts.error('You do not have permission to create pledges');
                return;
            }
            this.openPledgeModal();
        });
        document.getElementById('savePledgeBtn')?.addEventListener('click', () => this.savePledge());
        document.getElementById('refreshGrid')?.addEventListener('click', () => {
            this.table.reload();
            this.stats.load();
        });
        document.getElementById('applyFiltersBtn')?.addEventListener('click', () => this.table.applyFilters());
        document.getElementById('clearFiltersBtn')?.addEventListener('click', () => this.table.clearFilters());
        document.getElementById('submitPaymentBtn')?.addEventListener('click', () => this.submitPayment());

        // Pledge Types Management
        document.getElementById('managePledgeTypesBtn')?.addEventListener('click', () => this.openPledgeTypesModal());
        document.getElementById('addPledgeTypeBtn')?.addEventListener('click', () => this.addPledgeType());
        document.getElementById('savePledgeTypeBtn')?.addEventListener('click', () => this.saveEditPledgeType());
    }

    openPledgeModal(pledgeId = null) {
        this.state.currentPledgeId = pledgeId;
        document.getElementById('pledgeForm').reset();
        document.getElementById('pledgeId').value = pledgeId || '';
        document.getElementById('pledgeModalTitle').innerHTML = pledgeId ? '<i class="bi bi-pencil me-2"></i>Edit Pledge' : '<i class="bi bi-bookmark-heart me-2"></i>Create Pledge';
        document.getElementById('pledgeDate').valueAsDate = new Date();

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

        // Initialize Choices.js for pledge type select
        const typeSelect = document.getElementById('pledgeTypeId');
        typeSelect.innerHTML = '<option value="">Select Type</option>';
        this.state.pledgeTypesData.forEach(t => {
            typeSelect.innerHTML += `<option value="${t.PledgeTypeID}">${t.PledgeTypeName}</option>`;
        });
        if (this.state.pledgeTypeChoices) this.state.pledgeTypeChoices.destroy();
        this.state.pledgeTypeChoices = new Choices(typeSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search types...',
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

        new bootstrap.Modal(document.getElementById('pledgeModal')).show();
    }

    async savePledge() {
        const memberId = document.getElementById('memberId').value;
        const typeId = document.getElementById('pledgeTypeId').value;
        const amount = document.getElementById('amount').value;
        const pledgeDate = document.getElementById('pledgeDate').value;
        const fiscalYearId = document.getElementById('fiscalYear').value;

        if (!memberId || !typeId || !amount || !pledgeDate || !fiscalYearId) {
            Alerts.warning('Please fill all required fields');
            return;
        }

        const payload = {
            member_id: parseInt(memberId),
            pledge_type_id: parseInt(typeId),
            amount: parseFloat(amount),
            pledge_date: pledgeDate,
            due_date: document.getElementById('dueDate').value || null,
            fiscal_year_id: parseInt(fiscalYearId),
            description: document.getElementById('description').value.trim() || null
        };

        try {
            Alerts.loading('Saving pledge...');
            if (this.state.currentPledgeId) {
                await this.api.updatePledge(this.state.currentPledgeId, payload);
            } else {
                await this.api.createPledge(payload);
            }
            Alerts.closeLoading();
            Alerts.success(`Pledge ${this.state.currentPledgeId ? 'updated' : 'created'} successfully`);
            bootstrap.Modal.getInstance(document.getElementById('pledgeModal')).hide();
            this.table.reload();
            this.stats.load();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }

    async editPledge(pledgeId) {
        try {
            const p = await this.api.getPledge(pledgeId);
            this.state.currentPledgeId = pledgeId;
            this.state.currentPledgeData = p;

            document.getElementById('pledgeId').value = pledgeId;
            document.getElementById('pledgeModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Pledge';

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
            this.state.memberChoices.setChoiceByValue(String(p.MbrID));

            // Initialize Choices.js for pledge type select
            const typeSelect = document.getElementById('pledgeTypeId');
            typeSelect.innerHTML = '<option value="">Select Type</option>';
            this.state.pledgeTypesData.forEach(t => {
                typeSelect.innerHTML += `<option value="${t.PledgeTypeID}">${t.PledgeTypeName}</option>`;
            });
            if (this.state.pledgeTypeChoices) this.state.pledgeTypeChoices.destroy();
            this.state.pledgeTypeChoices = new Choices(typeSelect, {
                searchEnabled: true,
                searchPlaceholderValue: 'Search types...',
                itemSelectText: '',
                allowHTML: true
            });
            this.state.pledgeTypeChoices.setChoiceByValue(String(p.PledgeTypeID));

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
            this.state.fiscalYearChoices.setChoiceByValue(String(p.FiscalYearID));

            document.getElementById('amount').value = p.PledgeAmount;
            document.getElementById('pledgeDate').value = p.PledgeDate;
            document.getElementById('dueDate').value = p.DueDate || '';
            document.getElementById('description').value = p.Description || '';

            new bootstrap.Modal(document.getElementById('pledgeModal')).show();
        } catch (error) {
            Alerts.error('Failed to load pledge for editing');
        }
    }

    async viewPledge(pledgeId) {
        this.state.currentPledgeId = pledgeId;
        const modal = new bootstrap.Modal(document.getElementById('viewPledgeModal'));
        modal.show();

        try {
            const p = await this.api.getPledge(pledgeId);
            this.state.currentPledgeData = p;
            const statusColors = {
                'Active': '#ffc107',
                'Fulfilled': '#198754',
                'Cancelled': '#6c757d'
            };
            const statusColor = statusColors[p.PledgeStatus] || '#6c757d';
            const progressColor = p.progress >= 100 ? '#198754' : p.progress >= 50 ? '#ffc107' : '#dc3545';

            let paymentsHtml = '';
            if (p.payments && p.payments.length > 0) {
                paymentsHtml = `<div class="mt-3 pt-3 border-top">
               <div class="text-muted small text-uppercase mb-2">Payment History</div>
               <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                     <thead class="table-light"><tr><th>Date</th><th>Amount</th><th>Recorded By</th></tr></thead>
                     <tbody>${p.payments.map(pay => `<tr><td>${new Date(pay.PaymentDate).toLocaleDateString()}</td><td class="text-success fw-semibold">${this.stats.formatCurrency(pay.PaymentAmount)}</td><td>${pay.RecorderFirstName ? pay.RecorderFirstName + ' ' + pay.RecorderFamilyName : '-'}</td></tr>`).join('')}</tbody>
                  </table>
               </div>
            </div>`;
            }

            document.getElementById('viewPledgeContent').innerHTML = `
         <div class="pledge-view">
            <div class="text-center py-4" style="background: linear-gradient(135deg, ${statusColor} 0%, ${statusColor}99 100%);">
               <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                  <i class="bi bi-bookmark-heart" style="font-size:2rem;color:${statusColor};"></i>
               </div>
               <h2 class="text-white mb-1">${this.stats.formatCurrency(p.PledgeAmount)}</h2>
               <p class="text-white-50 mb-0">${p.PledgeTypeName}</p>
               <span class="badge bg-white text-dark mt-2">${p.PledgeStatus}</span>
            </div>
            <div class="p-4">
               <div class="mb-3">
                  <div class="d-flex justify-content-between mb-1">
                     <span class="text-muted small">Progress</span>
                     <span class="fw-semibold">${p.progress}%</span>
                  </div>
                  <div class="progress" style="height: 25px;">
                     <div class="progress-bar" style="width: ${Math.min(p.progress, 100)}%; background-color: ${progressColor};">${this.stats.formatCurrency(p.total_paid)} paid</div>
                  </div>
                  <div class="d-flex justify-content-between mt-1">
                     <small class="text-muted">Paid: ${this.stats.formatCurrency(p.total_paid)}</small>
                     <small class="text-muted">Balance: ${this.stats.formatCurrency(p.balance)}</small>
                  </div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-6"><div class="text-muted small text-uppercase">Member</div><div class="fw-semibold">${p.MemberName}</div></div>
                  <div class="col-6"><div class="text-muted small text-uppercase">Fiscal Year</div><div>${p.FiscalYearName || '-'}</div></div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-6"><div class="text-muted small text-uppercase">Pledge Date</div><div>${new Date(p.PledgeDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>
                  <div class="col-6"><div class="text-muted small text-uppercase">Due Date</div><div>${p.DueDate ? new Date(p.DueDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'Not set'}</div></div>
               </div>
               ${p.Description ? `<div class="mb-3 pt-3 border-top"><div class="text-muted small text-uppercase">Description</div><div>${p.Description}</div></div>` : ''}
               <div class="row g-3 pt-3 border-top">
                  <div class="col-6"><div class="text-muted small text-uppercase">Created By</div><div>${p.CreatorName || '-'}</div>${p.CreatedAt ? `<small class="text-muted">${new Date(p.CreatedAt).toLocaleString()}</small>` : ''}</div>
               </div>
               ${paymentsHtml}
            </div>
         </div>`;

            let footerHtml = `<button type="button" class="btn btn-outline-secondary" onclick="printPledge()"><i class="bi bi-printer me-1"></i>Print</button>`;
            if (p.PledgeStatus === 'Active') {
                footerHtml += `<button type="button" class="btn btn-success" onclick="recordPayment(${pledgeId})"><i class="bi bi-cash me-1"></i>Record Payment</button>`;
            }
            footerHtml += `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
            document.getElementById('viewPledgeFooter').innerHTML = footerHtml;
        } catch (error) {
            console.error('View pledge error:', error);
            document.getElementById('viewPledgeContent').innerHTML = `<div class="text-center text-danger py-5"><i class="bi bi-exclamation-circle fs-1"></i><p class="mt-2">Failed to load pledge details</p></div>`;
        }
    }

    printPledge() {
        const p = this.state.currentPledgeData;
        if (!p) return;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`<!DOCTYPE html><html><head><title>Pledge Details - ${p.MemberName}</title>
         <style>body{font-family:Arial,sans-serif;padding:20px;max-width:800px;margin:0 auto}.header{text-align:center;border-bottom:2px solid #333;padding-bottom:20px;margin-bottom:20px}.header h1{margin:0;font-size:24px}.amount{font-size:32px;font-weight:bold;color:#0d6efd;text-align:center;margin:20px 0}.status{display:inline-block;padding:5px 15px;border-radius:20px;font-weight:bold}.status-active{background:#fff3cd;color:#856404}.status-fulfilled{background:#d4edda;color:#155724}.status-cancelled{background:#e2e3e5;color:#383d41}.details{margin:20px 0}.row{display:flex;border-bottom:1px solid #eee;padding:10px 0}.label{width:150px;font-weight:bold;color:#666}.value{flex:1}.progress-bar{background:#e9ecef;border-radius:10px;height:20px;overflow:hidden;margin:10px 0}.progress-fill{height:100%;background:#198754}.footer{margin-top:40px;text-align:center;font-size:12px;color:#999}@media print{body{padding:0}}</style>
      </head><body>
         <div class="header"><h1>PLEDGE COMMITMENT</h1><p>Reference: PLG-${String(p.PledgeID).padStart(6, '0')}</p></div>
         <div class="amount">${this.stats.formatCurrency(p.PledgeAmount)}</div>
         <div style="text-align:center;margin-bottom:20px"><span class="status status-${p.PledgeStatus.toLowerCase()}">${p.PledgeStatus}</span></div>
         <div class="progress-bar"><div class="progress-fill" style="width:${Math.min(p.progress, 100)}%"></div></div>
         <div style="text-align:center;margin-bottom:20px"><small>Progress: ${p.progress}% | Paid: ${this.stats.formatCurrency(p.total_paid)} | Balance: ${this.stats.formatCurrency(p.balance)}</small></div>
         <div class="details">
            <div class="row"><div class="label">Member:</div><div class="value">${p.MemberName}</div></div>
            <div class="row"><div class="label">Pledge Type:</div><div class="value">${p.PledgeTypeName}</div></div>
            <div class="row"><div class="label">Pledge Date:</div><div class="value">${new Date(p.PledgeDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>
            ${p.DueDate ? `<div class="row"><div class="label">Due Date:</div><div class="value">${new Date(p.DueDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>` : ''}
            <div class="row"><div class="label">Fiscal Year:</div><div class="value">${p.FiscalYearName || '-'}</div></div>
            ${p.Description ? `<div class="row"><div class="label">Description:</div><div class="value">${p.Description}</div></div>` : ''}
         </div>
         <div class="footer"><p>Printed on ${new Date().toLocaleString()}</p></div>
      </body></html>`);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 250);
    }

    async recordPayment(pledgeId) {
        this.state.currentPledgeId = pledgeId;
        const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewPledgeModal'));
        if (viewModal) viewModal.hide();

        try {
            const p = this.state.currentPledgeData || await this.api.getPledge(pledgeId);
            this.state.currentPledgeData = p;
            document.getElementById('paymentPledgeDetails').innerHTML = `
         <div class="card bg-light">
            <div class="card-body">
               <h6 class="mb-2">${p.MemberName} - ${p.PledgeTypeName}</h6>
               <div class="row">
                  <div class="col-6"><small class="text-muted">Pledged</small><div class="fw-bold text-primary">${this.stats.formatCurrency(p.PledgeAmount)}</div></div>
                  <div class="col-6"><small class="text-muted">Balance</small><div class="fw-bold text-danger">${this.stats.formatCurrency(p.balance)}</div></div>
               </div>
               <div class="progress mt-2" style="height: 10px;"><div class="progress-bar bg-success" style="width: ${Math.min(p.progress, 100)}%"></div></div>
               <small class="text-muted">${p.progress}% fulfilled</small>
            </div>
         </div>`;
            document.getElementById('paymentAmount').value = '';
            document.getElementById('paymentAmount').max = p.balance;
            document.getElementById('paymentDate').valueAsDate = new Date();
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        } catch (error) {
            Alerts.error('Failed to load pledge details');
        }
    }

    async submitPayment() {
        const amount = document.getElementById('paymentAmount').value;
        const paymentDate = document.getElementById('paymentDate').value;
        if (!amount || !paymentDate) {
            Alerts.warning('Please fill all required fields');
            return;
        }

        try {
            Alerts.loading('Recording payment...');
            await this.api.recordPayment(this.state.currentPledgeId, {
                amount: parseFloat(amount),
                payment_date: paymentDate
            });
            Alerts.closeLoading();
            Alerts.success('Payment recorded successfully');
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            this.table.reload();
            this.stats.load();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }

    // ========== PLEDGE TYPES MANAGEMENT ==========
    async openPledgeTypesModal() {
        new bootstrap.Modal(document.getElementById('pledgeTypesModal')).show();
        await this.loadPledgeTypesTable();
    }

    async loadPledgeTypesTable() {
        const tbody = document.getElementById('pledgeTypesBody');
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>';

        try {
            const types = await this.api.getPledgeTypes();
            this.state.pledgeTypesData = Array.isArray(types) ? types : (types?.data || []);

            if (this.state.pledgeTypesData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No pledge types found</td></tr>';
                return;
            }

            tbody.innerHTML = this.state.pledgeTypesData.map(t => `
               <tr>
                  <td class="fw-medium">${t.PledgeTypeName}</td>
                  <td class="text-muted">${t.Description || '-'}</td>
                  <td>
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning btn-sm" onclick="editPledgeType(${t.PledgeTypeID}, '${t.PledgeTypeName.replace(/'/g, "\\'")}', '${(t.Description || '').replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deletePledgeType(${t.PledgeTypeID}, '${t.PledgeTypeName.replace(/'/g, "\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
                     </div>
                  </td>
               </tr>
            `).join('');

            // Update filter dropdown
            const filterTypeSelect = document.getElementById('filterType');
            filterTypeSelect.innerHTML = '<option value="">All Types</option>';
            this.state.pledgeTypesData.forEach(t => {
                filterTypeSelect.innerHTML += `<option value="${t.PledgeTypeID}">${t.PledgeTypeName}</option>`;
            });
        } catch (error) {
            console.error('Load pledge types error:', error);
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Failed to load pledge types</td></tr>';
        }
    }

    async addPledgeType() {
        const name = document.getElementById('newPledgeTypeName').value.trim();
        const desc = document.getElementById('newPledgeTypeDesc').value.trim();

        if (!name) {
            Alerts.warning('Please enter a pledge type name');
            return;
        }

        try {
            Alerts.loading('Adding pledge type...');
            await this.api.createPledgeType({
                name,
                description: desc || null
            });
            Alerts.closeLoading();
            Alerts.success('Pledge type added');
            document.getElementById('newPledgeTypeName').value = '';
            document.getElementById('newPledgeTypeDesc').value = '';
            await this.loadPledgeTypesTable();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }

    editPledgeType(id, name, desc) {
        this.state.editingPledgeTypeId = id;
        document.getElementById('editPledgeTypeId').value = id;
        document.getElementById('editPledgeTypeName').value = name;
        document.getElementById('editPledgeTypeDesc').value = desc || '';
        new bootstrap.Modal(document.getElementById('editPledgeTypeModal')).show();
    }

    async saveEditPledgeType() {
        const id = this.state.editingPledgeTypeId;
        const name = document.getElementById('editPledgeTypeName').value.trim();
        const desc = document.getElementById('editPledgeTypeDesc').value.trim();

        if (!name) {
            Alerts.warning('Please enter a pledge type name');
            return;
        }

        try {
            Alerts.loading('Saving...');
            await this.api.updatePledgeType(id, {
                name,
                description: desc || null
            });
            Alerts.closeLoading();
            Alerts.success('Pledge type updated');
            bootstrap.Modal.getInstance(document.getElementById('editPledgeTypeModal')).hide();
            await this.loadPledgeTypesTable();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }

    async deletePledgeType(id, name) {
        const confirmed = await Alerts.confirm(`Delete pledge type "${name}"?`, 'This cannot be undone. Types in use cannot be deleted.');
        if (!confirmed) return;

        try {
            Alerts.loading('Deleting...');
            await this.api.deletePledgeType(id);
            Alerts.closeLoading();
            Alerts.success('Pledge type deleted');
            await this.loadPledgeTypesTable();
        } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
        }
    }
}
