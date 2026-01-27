import { Alerts } from '../../utils/alerts.js';
import { api } from '../../utils/api.js';
import { Config } from '../../utils/config.js';
import { Auth } from '../../utils/auth.js';

export class ExpenseAPI {
    async getStats(fiscalYearId) {
        let url = 'expense/stats';
        if (fiscalYearId) url += `?fiscal_year_id=${fiscalYearId}`;
        return await api.get(url);
    }

    async getCategories(limit = 100) {
        const url = limit ? `expensecategory/all?limit=${limit}` : 'expensecategory/all';
        return await api.get(url);
    }

    async createCategory(name) {
        return await api.post('expensecategory/create', { name });
    }

    async updateCategory(id, name) {
        return await api.put(`expensecategory/update/${id}`, { name });
    }

    async deleteCategory(id) {
        return await api.delete(`expensecategory/delete/${id}`);
    }

    async createExpense(data) {
        return await api.post('expense/create', data);
    }

    async getExpense(id) {
        return await api.get(`expense/view/${id}`);
    }

    async reviewExpense(id, action, remarks) {
        return await api.post(`expense/review/${id}`, {
            action,
            remarks: remarks || null
        });
    }

    async uploadProof(id, formData) {
        return await fetch(`${Config.API_BASE_URL}/expense/upload-proof/${id}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${Auth.getToken()}`
            },
            body: formData
        });
    }

    async getFiscalYears() {
        return await api.get('fiscalyear/all?limit=50');
    }
}
