
/**
 * Contribution API Layer
 */

export class ContributionAPI {
   // Contributions
   async getAll(params = {}) {
      const queryString = new URLSearchParams(params).toString();
      const url = queryString ? `contribution/all?${queryString}` : 'contribution/all';
      // QMGrid handles the actual call, but this is for reference or custom fetching
      return url; 
   }

   async getStats(fiscalYearId = null) {
      let url = 'contribution/stats';
      if (fiscalYearId) url += `?fiscal_year_id=${fiscalYearId}`;
      return await api.get(url);
   }

   async get(id) {
      return await api.get(`contribution/view/${id}`);
   }

   async create(data) {
      return await api.post('contribution/create', data);
   }

   async update(id, data) {
      return await api.put(`contribution/update/${id}`, data);
   }

   async delete(id) {
      return await api.delete(`contribution/delete/${id}`);
   }

   async restore(id) {
      return await api.put(`contribution/restore/${id}`);
   }

   // Dropdown Data
   async getMembers() {
      return await api.get('member/all?limit=1000');
   }

   async getTypes() {
      return await api.get('contribution/types');
   }

   async getPaymentOptions() {
      return await api.get('contribution/payment-options');
   }

   async getFiscalYears() {
      return await api.get('fiscalyear/all?limit=50');
   }

   // Contribution Types Management
   async createType(data) {
      return await api.post('contribution/type/create', data);
   }

   async updateType(id, data) {
      return await api.put(`contribution/type/update/${id}`, data);
   }

   async deleteType(id) {
      return await api.delete(`contribution/type/delete/${id}`);
   }
}
