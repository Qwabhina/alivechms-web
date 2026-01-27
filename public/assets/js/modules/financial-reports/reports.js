import { Alerts } from '../../utils/alerts.js';
import { Formatter } from '../../utils/formatter.js';

export class FinancialReports {
    constructor(state, api) {
        this.state = state;
        this.api = api;
    }

    async generate(type) {
        const fiscalYearId = document.getElementById('fiscalYear').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        Alerts.loading('Generating report...');

        try {
            let reportData;
            let reportTitle;

            switch (type) {
                case 'contributions':
                    reportData = await this.generateContributionsReport(fiscalYearId, startDate, endDate);
                    reportTitle = 'Contributions Report';
                    break;
                case 'pledges':
                    reportData = await this.generatePledgesReport(fiscalYearId, startDate, endDate);
                    reportTitle = 'Pledges Report';
                    break;
                case 'expenses':
                    reportData = await this.generateExpensesReport(fiscalYearId, startDate, endDate);
                    reportTitle = 'Expenses Report';
                    break;
                case 'budget':
                    reportData = await this.generateBudgetReport(fiscalYearId);
                    reportTitle = 'Budget vs Actual Report';
                    break;
                case 'summary':
                    reportData = await this.generateSummaryReport(fiscalYearId, startDate, endDate);
                    reportTitle = 'Financial Summary Report';
                    break;
            }

            document.getElementById('reportTitle').textContent = reportTitle;
            document.getElementById('reportContent').innerHTML = reportData;
            document.getElementById('reportCard').style.display = 'block';

            Alerts.closeLoading();
        } catch (error) {
            Alerts.closeLoading();
            console.error('Generate report error:', error);
            Alerts.error('Failed to generate report');
        }
    }

    async generateContributionsReport(fiscalYearId, startDate, endDate) {
        const response = await this.api.getContributions({ fiscal_year_id: fiscalYearId, start_date: startDate, end_date: endDate });
        const contributions = response?.data?.data || response?.data || [];

        const total = contributions.reduce((sum, c) => sum + parseFloat(c.ContributionAmount), 0);
        const byType = {};

        contributions.forEach(c => {
            const type = c.ContributionTypeName;
            if (!byType[type]) byType[type] = {
                count: 0,
                total: 0
            };
            byType[type].count++;
            byType[type].total += parseFloat(c.ContributionAmount);
        });

        let html = `
         <div class="mb-4">
            <h4>Summary</h4>
            <p><strong>Total Contributions:</strong> ${this.formatCurrency(total)}</p>
            <p><strong>Number of Contributions:</strong> ${contributions.length}</p>
         </div>
         <div class="mb-4">
            <h5>By Type</h5>
            <table class="table table-bordered">
               <thead class="table-light">
                  <tr>
                     <th>Type</th>
                     <th class="text-end">Count</th>
                     <th class="text-end">Total Amount</th>
                  </tr>
               </thead>
               <tbody>
      `;

        for (const [type, data] of Object.entries(byType)) {
            html += `
            <tr>
               <td>${type}</td>
               <td class="text-end">${data.count}</td>
               <td class="text-end">${this.formatCurrency(data.total)}</td>
            </tr>
         `;
        }

        html += `
               </tbody>
            </table>
         </div>
      `;

        return html;
    }

    async generatePledgesReport(fiscalYearId, startDate, endDate) {
        const response = await this.api.getPledges({ fiscal_year_id: fiscalYearId });
        const pledges = response?.data?.data || response?.data || [];

        const total = pledges.reduce((sum, p) => sum + parseFloat(p.PledgeAmount), 0);
        const fulfilled = pledges.filter(p => p.PledgeStatus === 'Fulfilled').length;
        const active = pledges.filter(p => p.PledgeStatus === 'Active').length;

        return `
         <div class="mb-4">
            <h4>Summary</h4>
            <p><strong>Total Pledges:</strong> ${this.formatCurrency(total)}</p>
            <p><strong>Total Count:</strong> ${pledges.length}</p>
            <p><strong>Fulfilled:</strong> ${fulfilled}</p>
            <p><strong>Active:</strong> ${active}</p>
            <p><strong>Fulfillment Rate:</strong> ${pledges.length > 0 ? ((fulfilled / pledges.length) * 100).toFixed(1) : 0}%</p>
         </div>
      `;
    }

    async generateExpensesReport(fiscalYearId, startDate, endDate) {
        const response = await this.api.getExpenses({ fiscal_year_id: fiscalYearId, start_date: startDate, end_date: endDate });
        const expenses = response?.data?.data || response?.data || [];

        const total = expenses.reduce((sum, e) => sum + parseFloat(e.ExpenseAmount), 0);
        const approved = expenses.filter(e => e.ExpenseStatus === 'Approved');
        const approvedTotal = approved.reduce((sum, e) => sum + parseFloat(e.ExpenseAmount), 0);

        return `
         <div class="mb-4">
            <h4>Summary</h4>
            <p><strong>Total Expenses:</strong> ${this.formatCurrency(total)}</p>
            <p><strong>Total Count:</strong> ${expenses.length}</p>
            <p><strong>Approved:</strong> ${approved.length} (${this.formatCurrency(approvedTotal)})</p>
            <p><strong>Pending:</strong> ${expenses.filter(e => e.ExpenseStatus === 'Pending Approval').length}</p>
         </div>
      `;
    }

    async generateBudgetReport(fiscalYearId) {
        const response = await this.api.getBudgets({ fiscal_year_id: fiscalYearId });
        const budgets = response?.data?.data || response?.data || [];

        const total = budgets.reduce((sum, b) => sum + parseFloat(b.TotalAmount), 0);
        const approved = budgets.filter(b => b.BudgetStatus === 'Approved');

        return `
         <div class="mb-4">
            <h4>Summary</h4>
            <p><strong>Total Budgets:</strong> ${this.formatCurrency(total)}</p>
            <p><strong>Total Count:</strong> ${budgets.length}</p>
            <p><strong>Approved:</strong> ${approved.length}</p>
         </div>
      `;
    }

    async generateSummaryReport(fiscalYearId, startDate, endDate) {
        const [contribRes, pledgesRes, expensesRes] = await Promise.all([
            this.api.getContributionStats(),
            this.api.getPledges({ fiscal_year_id: fiscalYearId }),
            this.api.getExpenses({ fiscal_year_id: fiscalYearId })
        ]);

        const stats = contribRes;
        const pledges = pledgesRes?.data?.data || pledgesRes?.data || [];
        const expenses = expensesRes?.data?.data || expensesRes?.data || [];

        const totalPledges = pledges.reduce((sum, p) => sum + parseFloat(p.PledgeAmount), 0);
        const totalExpenses = expenses.reduce((sum, e) => sum + parseFloat(e.ExpenseAmount), 0);

        return `
         <div class="row">
            <div class="col-md-4 mb-3">
               <div class="card bg-success bg-opacity-25">
                  <div class="card-body">
                     <h6 class="text-muted">Total Contributions</h6>
                     <h3>${this.formatCurrency(stats.total_amount)}</h3>
                  </div>
               </div>
            </div>
            <div class="col-md-4 mb-3">
               <div class="card bg-warning bg-opacity-25">
                  <div class="card-body">
                     <h6 class="text-muted">Total Pledges</h6>
                     <h3>${this.formatCurrency(totalPledges)}</h3>
                  </div>
               </div>
            </div>
            <div class="col-md-4 mb-3">
               <div class="card bg-danger bg-opacity-25">
                  <div class="card-body">
                     <h6 class="text-muted">Total Expenses</h6>
                     <h3>${this.formatCurrency(totalExpenses)}</h3>
                  </div>
               </div>
            </div>
         </div>
         <div class="card bg-primary bg-opacity-25 mt-3">
            <div class="card-body">
               <h6 class="text-muted">Net Position</h6>
               <h3>${this.formatCurrency(stats.total_amount - totalExpenses)}</h3>
            </div>
         </div>
      `;
    }

    export(format) {
        Alerts.info(`Export to ${format.toUpperCase()} coming soon`);
    }

    formatCurrency(amount) {
        return formatCurrencyLocale(amount);
    }
}
