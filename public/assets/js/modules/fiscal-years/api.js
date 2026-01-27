import { api } from '../../utils/api.js';
import { Config } from '../../utils/config.js';

export class FiscalYearAPI {
    async getAll() {
        return await api.get('fiscalyear/all');
    }

    async getBranches() {
        return await api.get('branch/all?limit=100');
    }

    async getFiscalYear(id) {
        return await api.get(`fiscalyear/view/${id}`);
    }

    async createFiscalYear(data) {
        return await api.post('fiscalyear/create', data);
    }

    async updateFiscalYear(id, data) {
        return await api.put(`fiscalyear/update/${id}`, data);
    }

    async closeFiscalYear(id) {
        return await api.post(`fiscalyear/close/${id}`);
    }

    async deleteFiscalYear(id) {
        return await api.delete(`fiscalyear/delete/${id}`);
    }
}
