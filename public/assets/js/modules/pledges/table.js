import { Config } from '../../utils/config.js';

export class PledgeTable {
    constructor(state, api, stats) {
        this.state = state;
        this.api = api;
        this.stats = stats;
    }

    init() {
        const url = this.buildUrl();
        this.state.pledgesTable = QMGridHelper.init('#pledgesTable', {
            url: url,
            pageSize: 25,
            selectable: false,
            exportable: true,
            columns: [{
                    key: 'MemberName',
                    title: 'Member',
                    render: (v) => `<div class="fw-medium">${v || '-'}</div>`
                },
                {
                    key: 'PledgeTypeName',
                    title: 'Type',
                    render: (v) => v ? `<span class="badge bg-info">${v}</span>` : '-'
                },
                {
                    key: 'PledgeAmount',
                    title: 'Amount',
                    render: (v) => `<span class="fw-semibold text-primary">${this.stats.formatCurrency(v)}</span>`
                },
                {
                    key: 'TotalPaid',
                    title: 'Paid',
                    render: (v) => `<span class="text-success">${this.stats.formatCurrency(v)}</span>`
                },
                {
                    key: 'Progress',
                    title: 'Progress',
                    width: '120px',
                    render: (v, row) => {
                        const color = v >= 100 ? 'success' : v >= 50 ? 'warning' : 'danger';
                        return `<div class="progress" style="height: 20px;"><div class="progress-bar bg-${color}" style="width: ${Math.min(v, 100)}%">${v}%</div></div>`;
                    }
                },
                {
                    key: 'PledgeDate',
                    title: 'Date',
                    render: (v) => QMGridHelper.formatDate(v, 'short')
                },
                {
                    key: 'DueDate',
                    title: 'Due',
                    render: (v, row) => {
                        if (!v) return '-';
                        const isOverdue = row.PledgeStatus === 'Active' && new Date(v) < new Date();
                        return `<span class="${isOverdue ? 'text-danger fw-bold' : ''}">${QMGridHelper.formatDate(v, 'short')}${isOverdue ? ' <i class="bi bi-exclamation-triangle"></i>' : ''}</span>`;
                    }
                },
                {
                    key: 'PledgeStatus',
                    title: 'Status',
                    render: (v) => {
                        const badges = {
                            'Active': 'warning',
                            'Fulfilled': 'success',
                            'Cancelled': 'secondary'
                        };
                        return `<span class="badge bg-${badges[v] || 'secondary'}">${v || '-'}</span>`;
                    }
                },
                {
                    key: 'PledgeID',
                    title: 'Actions',
                    width: '140px',
                    sortable: false,
                    exportable: false,
                    render: (v, row) => {
                        const isActive = row.PledgeStatus === 'Active';
                        let btns = `<button class="btn btn-primary btn-sm" onclick="viewPledge(${v})" title="View"><i class="bi bi-eye"></i></button>`;
                        if (isActive) {
                            btns += `<button class="btn btn-success btn-sm" onclick="recordPayment(${v})" title="Record Payment"><i class="bi bi-cash"></i></button>`;
                            btns += `<button class="btn btn-warning btn-sm" onclick="editPledge(${v})" title="Edit"><i class="bi bi-pencil"></i></button>`;
                        }
                        return `<div class="btn-group btn-group-sm">${btns}</div>`;
                    }
                }
            ],
            onDataLoaded: (data) => {
                document.getElementById('totalPledgesCount').textContent = data.pagination?.total || data.total || 0;
            }
        });
    }

    buildUrl() {
        let url = `${Config.API_BASE_URL}/pledge/all`;
        const params = new URLSearchParams();
        if (this.state.selectedFiscalYearId) params.append('fiscal_year_id', this.state.selectedFiscalYearId);
        if (this.state.currentFilters.status) params.append('status', this.state.currentFilters.status);
        if (this.state.currentFilters.pledge_type_id) params.append('pledge_type_id', this.state.currentFilters.pledge_type_id);
        if (this.state.currentFilters.start_date) params.append('start_date', this.state.currentFilters.start_date);
        if (this.state.currentFilters.end_date) params.append('end_date', this.state.currentFilters.end_date);
        if (params.toString()) url += '?' + params.toString();
        return url;
    }

    reload() {
        if (this.state.pledgesTable) {
            this.state.pledgesTable.destroy();
        }
        this.init();
    }

    applyFilters() {
        this.state.currentFilters = {
            status: document.getElementById('filterStatus').value,
            pledge_type_id: document.getElementById('filterType').value,
            start_date: document.getElementById('filterStartDate').value,
            end_date: document.getElementById('filterEndDate').value
        };
        Object.keys(this.state.currentFilters).forEach(k => !this.state.currentFilters[k] && delete this.state.currentFilters[k]);
        this.reload();
    }

    clearFilters() {
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('filterStartDate').value = '';
        document.getElementById('filterEndDate').value = '';
        this.state.currentFilters = {};
        this.reload();
    }
}
