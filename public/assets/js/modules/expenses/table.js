import { Config } from '../../utils/config.js';
import { Auth } from '../../utils/auth.js';

export class ExpenseTable {
    constructor(state, api, stats) {
        this.state = state;
        this.api = api;
        this.stats = stats;
    }

    init() {
        const url = this.buildUrl();
        this.state.expensesTable = QMGridHelper.init('#expensesTable', {
            url: url,
            pageSize: 25,
            selectable: false,
            exportable: true,
            columns: [{
                    key: 'ExpTitle',
                    title: 'Title',
                    render: (v) => `<div class="fw-medium">${v || '-'}</div>`
                },
                {
                    key: 'ExpAmount',
                    title: 'Amount',
                    render: (v) => `<span class="fw-semibold text-danger">${this.stats.formatCurrency(v)}</span>`
                },
                {
                    key: 'ExpDate',
                    title: 'Date',
                    render: (v) => QMGridHelper.formatDate(v, 'short')
                },
                {
                    key: 'CategoryName',
                    title: 'Category',
                    render: (v) => v ? `<span class="badge bg-secondary">${v}</span>` : '-'
                },
                {
                    key: 'ExpensePurpose',
                    title: 'Purpose',
                    render: (v) => v ? `<span class="text-muted small" title="${v}">${v.substring(0, 40)}${v.length > 40 ? '...' : ''}</span>` : '-'
                },
                {
                    key: 'ExpenseStatus',
                    title: 'Status',
                    render: (v) => {
                        const badges = {
                            'Pending Approval': 'warning',
                            'Approved': 'success',
                            'Declined': 'danger'
                        };
                        return `<span class="badge bg-${badges[v] || 'secondary'}">${v || '-'}</span>`;
                    }
                },
                {
                    key: 'ProofFile',
                    title: 'Proof',
                    width: '60px',
                    sortable: false,
                    render: (v) => v ? `<a href="${Config.API_BASE_URL}/../${v}" target="_blank" class="btn btn-sm btn-outline-success" title="View Proof"><i class="bi bi-file-earmark-check"></i></a>` : '<span class="text-muted">-</span>'
                },
                {
                    key: 'ExpID',
                    title: 'Actions',
                    width: '140px',
                    sortable: false,
                    exportable: false,
                    render: (v, row) => {
                        const isPending = row.ExpenseStatus === 'Pending Approval';
                        const isApproved = row.ExpenseStatus === 'Approved';
                        const hasProof = !!row.ProofFile;
                        let btns = `<button class="btn btn-primary btn-sm" onclick="viewExpense(${v})" title="View"><i class="bi bi-eye"></i></button>`;
                        if (isPending) btns += `<button class="btn btn-success btn-sm" onclick="reviewExpense(${v})" title="Review"><i class="bi bi-clipboard-check"></i></button>`;
                        if (isApproved && !hasProof) btns += `<button class="btn btn-warning btn-sm" onclick="uploadProof(${v})" title="Upload Proof"><i class="bi bi-upload"></i></button>`;
                        return `<div class="btn-group btn-group-sm">${btns}</div>`;
                    }
                }
            ],
            onDataLoaded: (data) => {
                document.getElementById('totalExpensesCount').textContent = data.pagination?.total || data.total || 0;
            }
        });
    }

    buildUrl() {
        let url = `${Config.API_BASE_URL}/expense/all`;
        const params = new URLSearchParams();
        if (this.state.selectedFiscalYearId) params.append('fiscal_year_id', this.state.selectedFiscalYearId);
        if (this.state.currentFilters.status) params.append('status', this.state.currentFilters.status);
        if (this.state.currentFilters.category_id) params.append('category_id', this.state.currentFilters.category_id);
        if (this.state.currentFilters.start_date) params.append('start_date', this.state.currentFilters.start_date);
        if (this.state.currentFilters.end_date) params.append('end_date', this.state.currentFilters.end_date);
        if (params.toString()) url += '?' + params.toString();
        return url;
    }

    reload() {
        if (this.state.expensesTable) {
            this.state.expensesTable.destroy();
        }
        this.init();
    }

    applyFilters() {
        this.state.currentFilters = {
            status: document.getElementById('filterStatus').value,
            category_id: document.getElementById('filterCategory').value,
            start_date: document.getElementById('filterStartDate').value,
            end_date: document.getElementById('filterEndDate').value
        };
        Object.keys(this.state.currentFilters).forEach(k => !this.state.currentFilters[k] && delete this.state.currentFilters[k]);
        this.reload();
    }

    clearFilters() {
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterCategory').value = '';
        document.getElementById('filterStartDate').value = '';
        document.getElementById('filterEndDate').value = '';
        this.state.currentFilters = {};
        this.reload();
    }
}
