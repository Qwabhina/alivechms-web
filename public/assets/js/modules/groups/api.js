/**
 * Groups API Layer
 */

export class GroupAPI {
   async getAll(params = {}) {
      const queryString = new URLSearchParams(params).toString();
      const url = queryString ? `group/all?${queryString}` : 'group/all';
      return await api.get(url);
   }

   async get(groupId) {
      return await api.get(`group/view/${groupId}`);
   }

   async create(data) {
      return await api.post('group/create', data);
   }

   async update(groupId, data) {
      return await api.put(`group/update/${groupId}`, data);
   }

   async delete(groupId) {
      return await api.delete(`group/delete/${groupId}`);
   }

   async getMembers(groupId, params = {}) {
      const queryString = new URLSearchParams(params).toString();
      const url = queryString ? `group/members/${groupId}?${queryString}` : `group/members/${groupId}`;
      return await api.get(url);
   }

   async addMember(groupId, memberId) {
      return await api.post(`group/addMember/${groupId}`, { member_id: memberId });
   }

   async removeMember(groupId, memberId) {
      return await api.delete(`group/removeMember/${groupId}/${memberId}`);
   }

   async getAllMembers(params = {}) {
      const queryString = new URLSearchParams(params).toString();
      const url = queryString ? `member/all?${queryString}` : 'member/all';
      return await api.get(url);
   }

   async getAllGroupTypes(params = {}) {
      const queryString = new URLSearchParams(params).toString();
      const url = queryString ? `grouptype/all?${queryString}` : 'grouptype/all';
      return await api.get(url);
   }

   async createGroupType(data) {
      return await api.post('grouptype/create', data);
   }

   async deleteGroupType(typeId) {
      return await api.delete(`grouptype/delete/${typeId}`);
   }
}
