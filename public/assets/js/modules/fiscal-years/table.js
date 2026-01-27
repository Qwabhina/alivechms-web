import { Config } from '../../utils/config.js';

export class FiscalYearTable {
    constructor(state, api) {
        this.state = state;
        this.api = api;
    }

    async init() {
        this.state.fiscalYearsTable = await QMGridHelper.initWithButtons('#fiscalYearsTable', {
            url: `${Config.API_BASE_URL}/fiscalyear/all`,
            pageSize: 10,
            columns: [{
                    key: 'FiscalYearStartDate',
                    title: 'Start Date',
                    render: (data) => QMGridHelper.formatDate(data)
                },
                {
                    key: 'FiscalYearEndDate',
                    title: 'End Date',
                    render: (data) => QMGridHelper.formatDate(data)
                },
                {
                    key: 'BranchName',
                    title: 'Branch'
                },
                {
                    key: 'Status',
                    title: 'Status',
                    render: (data) => QMGridHelper.statusBadge(data)
                },
                {
                    key: 'FiscalYearID',
                    title: 'Actions',
                    sortable: false,
                    className: 'no-export',
                    render: (data, row) => {
                        const customButtons = row.Status === 'Active' ? [{
                            icon: 'lock',
                            color: 'info',
                            fn: 'closeFiscalYear',
                            title: 'Close'
                        }] : [];

                        return QMGridHelper.actionButtons(data, {
                            view: false,
                            editFn: 'editFiscalYear',
                            deleteFn: 'deleteFiscalYear',
                            custom: customButtons
                        });
                    }
                }
            ]
        });
    }

    reload() {
        QMGridHelper.reload(this.state.fiscalYearsTable);
    }
}
