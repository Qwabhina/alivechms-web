/**
 * Family API Service
 */

export class FamilyAPI {
   constructor() {
      this.baseUrl = '/family';
   }

   async getAll(params = {}) {
      return await api.get(`${this.baseUrl}/all`, params);
   }

   async get(id) {
      return await api.get(`${this.baseUrl}/view/${id}`);
   }

   async create(data) {
      return await api.post(`${this.baseUrl}/create`, data);
   }

   async update(id, data) {
      return await api.put(`${this.baseUrl}/update/${id}`, data);
   }

   async delete(id) {
      return await api.delete(`${this.baseUrl}/delete/${id}`);
   }

   async addMember(familyId, memberId, role = 'Member') {
      return await api.post(`${this.baseUrl}/addMember/${familyId}`, {
         member_id: parseInt(memberId),
         role: role
      });
   }

   async removeMember(familyId, memberId) {
      return await api.delete(`${this.baseUrl}/removeMember/${familyId}/${memberId}`);
   }

   async getMembers() {
      return await api.get('/member/all?limit=1000');
   }
}
