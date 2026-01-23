/**
 * Member API Service
 */

export class MemberAPI {
   constructor() {
      // Use absolute paths from root
      this.baseUrl = '/member';
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

   async getStats() {
      return await api.get(`${this.baseUrl}/stats`);
   }

   async uploadProfilePicture(id, file) {
      const formData = new FormData();
      formData.append('profile_picture', file);
      return await api.post(`${this.baseUrl}/${id}/upload-picture`, formData);
   }

   // Lookup data - use the combined lookups/all endpoint
   async getFamilies() {
      return await api.get('/family/all?limit=1000');
   }

   async getAllLookups() {
      return await api.get('/lookups/all');
   }
}
