import { Alerts } from '../../utils/alerts.js';
import { Auth } from '../../utils/auth.js';

export class FiscalYearForm {
    constructor(state, api, table) {
        this.state = state;
        this.api = api;
        this.table = table;
    }

    init() {
        document.getElementById('addFiscalYearBtn').addEventListener('click', () => {
            if (!Auth.hasPermission('manage_fiscal_years')) {
                Alerts.error('You do not have permission to create fiscal years');
                return;
            }
            this.openFiscalYearModal();
        });

        document.getElementById('saveFiscalYearBtn').addEventListener('click', () => this.saveFiscalYear());
    }

    async loadDropdowns() {
        try {
            const response = await this.api.getBranches();
            this.state.branchesData = response?.data?.data || response?.data || [];

            const branchSelect = document.getElementById('branchId');
            branchSelect.innerHTML = '<option value="">Select Branch</option>';
            this.state.branchesData.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.BranchID;
                opt.textContent = b.BranchName;
                branchSelect.appendChild(opt);
            });
        } catch (error) {
            console.error('Load dropdowns error:', error);
        }
    }

    openFiscalYearModal(fiscalYearId = null) {
        this.state.isEditMode = !!fiscalYearId;
        this.state.currentFiscalYearId = fiscalYearId;

        document.getElementById('fiscalYearForm').reset();
        document.getElementById('fiscalYearId').value = '';
        document.getElementById('fiscalYearModalTitle').textContent = this.state.isEditMode ? 'Edit Fiscal Year' : 'Create Fiscal Year';

        const modal = new bootstrap.Modal(document.getElementById('fiscalYearModal'));
        modal.show();

        if (this.state.isEditMode) this.loadFiscalYearForEdit(fiscalYearId);
    }

    async loadFiscalYearForEdit(fiscalYearId) {
        try {
            const fy = await this.api.getFiscalYear(fiscalYearId);
            document.getElementById('fiscalYearId').value = fy.FiscalYearID;
            document.getElementById('startDate').value = fy.FiscalYearStartDate;
            document.getElementById('endDate').value = fy.FiscalYearEndDate;
            document.getElementById('branchId').value = fy.BranchID;
            document.getElementById('status').value = fy.Status;
        } catch (error) {
            console.error('Load fiscal year error:', error);
            Alerts.error('Failed to load fiscal year data');
        }
    }

    async saveFiscalYear() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const branchId = document.getElementById('branchId').value;
        const status = document.getElementById('status').value;

        if (!startDate || !endDate || !branchId || !status) {
            Alerts.warning('Please fill all required fields');
            return;
        }

        const payload = {
            start_date: startDate,
            end_date: endDate,
            branch_id: parseInt(branchId),
            status: status
        };

        try {
            Alerts.loading('Saving fiscal year...');
            if (this.state.isEditMode) {
                await this.api.updateFiscalYear(this.state.currentFiscalYearId, payload);
            } else {
                await this.api.createFiscalYear(payload);
            }
            Alerts.closeLoading();
            Alerts.success(this.state.isEditMode ? 'Fiscal year updated successfully' : 'Fiscal year created successfully');
            bootstrap.Modal.getInstance(document.getElementById('fiscalYearModal')).hide();
            this.table.reload();
        } catch (error) {
            Alerts.closeLoading();
            console.error('Save fiscal year error:', error);
            Alerts.handleApiError(error);
        }
    }

    editFiscalYear(fiscalYearId) {
        if (!Auth.hasPermission('manage_fiscal_years')) {
            Alerts.error('You do not have permission to edit fiscal years');
            return;
        }
        this.openFiscalYearModal(fiscalYearId);
    }

    async closeFiscalYear(fiscalYearId) {
        if (!Auth.hasPermission('manage_fiscal_years')) {
            Alerts.error('You do not have permission to close fiscal years');
            return;
        }

        const confirmed = await Alerts.confirm({
            title: 'Close Fiscal Year',
            text: 'Are you sure you want to close this fiscal year? This action cannot be undone.',
            icon: 'warning',
            confirmButtonText: 'Yes, close',
            confirmButtonColor: '#0d6efd'
        });

        if (!confirmed) return;

        try {
            Alerts.loading('Closing fiscal year...');
            await this.api.closeFiscalYear(fiscalYearId);
            Alerts.closeLoading();
            Alerts.success('Fiscal year closed successfully');
            this.table.reload();
        } catch (error) {
            Alerts.closeLoading();
            console.error('Close fiscal year error:', error);
            Alerts.handleApiError(error);
        }
    }

    async deleteFiscalYear(fiscalYearId) {
        if (!Auth.hasPermission('manage_fiscal_years')) {
            Alerts.error('You do not have permission to delete fiscal years');
            return;
        }

        const confirmed = await Alerts.confirm({
            title: 'Delete Fiscal Year',
            text: 'Are you sure you want to delete this fiscal year? This action cannot be undone.',
            icon: 'warning',
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc3545'
        });

        if (!confirmed) return;

        try {
            Alerts.loading('Deleting fiscal year...');
            await this.api.deleteFiscalYear(fiscalYearId);
            Alerts.closeLoading();
            Alerts.success('Fiscal year deleted successfully');
            this.table.reload();
        } catch (error) {
            Alerts.closeLoading();
            console.error('Delete fiscal year error:', error);
            Alerts.handleApiError(error);
        }
    }
}
