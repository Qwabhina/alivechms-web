import { api } from '../../utils/api.js';

export class PledgeAPI {
    async getStats(fiscalYearId) {
        let url = 'pledge/stats';
        if (fiscalYearId) url += `?fiscal_year_id=${fiscalYearId}`;
        return await api.get(url);
    }

    async getMembers(limit = 1000) {
        return await api.get(`member/all?limit=${limit}`);
    }

    async getPledgeTypes() {
        return await api.get('pledge/types');
    }

    async createPledgeType(data) {
        return await api.post('pledge/type/create', data);
    }

    async updatePledgeType(id, data) {
        return await api.put(`pledge/type/update/${id}`, data);
    }

    async deletePledgeType(id) {
        return await api.delete(`pledge/type/delete/${id}`);
    }

    async createPledge(data) {
        return await api.post('pledge/create', data);
    }

    async updatePledge(id, data) {
        return await api.put(`pledge/update/${id}`, data);
    }

    async getPledge(id) {
        return await api.get(`pledge/view/${id}`);
    }

    async recordPayment(pledgeId, data) {
        return await api.post(`pledge/payment/${pledgeId}`, data);
    }

    async getFiscalYears() {
        return await api.get('fiscalyear/all?limit=50');
    }
}
