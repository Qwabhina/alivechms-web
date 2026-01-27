
import { api } from '../../core/api.js';

export class BudgetAPI {
   async getAll(params = {}) {
      const queryString = new URLSearchParams(params).toString();
      return queryString ? `budget/all?${queryString}` : 'budget/all';
   }

   async getStats() {
      return await api.get('budget/all?limit=1000'); // Currently stats are calculated from all budgets
   }

   async create(data) {
      return await api.post('budget/create', data);
   }

   async getBranches() {
      return await api.get('branch/all?limit=100');
   }

   async getFiscalYears() {
      return await api.get('fiscalyear/all?limit=10');
   }
}
