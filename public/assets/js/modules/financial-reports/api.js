import { api } from '../../utils/api.js';

export class FinancialReportAPI {
    async getFiscalYears() {
        return await api.get('fiscalyear/all?limit=10');
    }

    async getContributions(params = {}) {
        let url = 'contribution/all?limit=1000';
        if (params.fiscal_year_id) url += `&fiscal_year_id=${params.fiscal_year_id}`;
        if (params.start_date) url += `&start_date=${params.start_date}`;
        if (params.end_date) url += `&end_date=${params.end_date}`;
        return await api.get(url);
    }

    async getContributionStats() {
        return await api.get('contribution/stats');
    }

    async getPledges(params = {}) {
        let url = 'pledge/all?limit=1000';
        if (params.fiscal_year_id) url += `&fiscal_year_id=${params.fiscal_year_id}`;
        return await api.get(url);
    }

    async getExpenses(params = {}) {
        let url = 'expense/all?limit=1000';
        if (params.fiscal_year_id) url += `&fiscal_year_id=${params.fiscal_year_id}`;
        if (params.start_date) url += `&start_date=${params.start_date}`;
        if (params.end_date) url += `&end_date=${params.end_date}`;
        return await api.get(url);
    }

    async getBudgets(params = {}) {
        let url = 'budget/all?limit=1000';
        if (params.fiscal_year_id) url += `&fiscal_year_id=${params.fiscal_year_id}`;
        return await api.get(url);
    }
}
