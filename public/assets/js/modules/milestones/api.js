/**
 * Milestones API Layer
 */

export class MilestoneAPI {
   // Milestones
   async getAll(params = {}) {
      const queryString = new URLSearchParams(params).toString();
      const url = queryString ? `milestone/all?${queryString}` : 'milestone/all';
      return await api.get(url);
   }

   async get(milestoneId) {
      return await api.get(`milestone/view/${milestoneId}`);
   }

   async create(data) {
      return await api.post('milestone/create', data);
   }

   async update(milestoneId, data) {
      return await api.put(`milestone/update/${milestoneId}`, data);
   }

   async delete(milestoneId) {
      return await api.delete(`milestone/delete/${milestoneId}`);
   }

   async getStats(year = null) {
      const url = year ? `milestone/stats?year=${year}` : 'milestone/stats';
      return await api.get(url);
   }

   async getByMember(memberId) {
      return await api.get(`milestone/member/${memberId}`);
   }

   // Milestone Types
   async getAllTypes(activeOnly = false) {
      const url = activeOnly ? 'milestone/types?active=1' : 'milestone/types';
      return await api.get(url);
   }

   async getType(typeId) {
      return await api.get(`milestone/type/view/${typeId}`);
   }

   async createType(data) {
      return await api.post('milestone/type/create', data);
   }

   async updateType(typeId, data) {
      return await api.put(`milestone/type/update/${typeId}`, data);
   }

   async deleteType(typeId) {
      return await api.delete(`milestone/type/delete/${typeId}`);
   }
}
