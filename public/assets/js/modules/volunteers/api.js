/**
 * Volunteers API Layer
 */

export class VolunteerAPI {
   // Volunteer Roles
   async getAllRoles() {
      return await api.get('volunteer/role/all');
   }

   async createRole(data) {
      return await api.post('volunteer/role/create', data);
   }

   // Event Volunteer Assignments
   async assignVolunteers(eventId, volunteers) {
      return await api.post(`volunteer/assign/${eventId}`, { volunteers });
   }

   async confirmAssignment(assignmentId, action) {
      return await api.post(`volunteer/confirm/${assignmentId}`, { action });
   }

   async completeAssignment(assignmentId) {
      return await api.post(`volunteer/complete/${assignmentId}`);
   }

   async getEventVolunteers(eventId, params = {}) {
      const queryString = new URLSearchParams(params).toString();
      const url = queryString ? `volunteer/event/${eventId}?${queryString}` : `volunteer/event/${eventId}`;
      return await api.get(url);
   }

   async removeVolunteer(assignmentId) {
      return await api.delete(`volunteer/remove/${assignmentId}`);
   }

   // Member Volunteer Role Assignments
   async assignRoleToMember(memberId, data) {
      return await api.post(`volunteer/member/${memberId}/assign`, data);
   }

   async removeRoleFromMember(assignmentId) {
      return await api.delete(`volunteer/member/remove/${assignmentId}`);
   }

   async getMemberVolunteerRoles(memberId) {
      return await api.get(`volunteer/member/${memberId}/roles`);
   }

   async getMembersByRole(roleId, params = {}) {
      const queryString = new URLSearchParams(params).toString();
      const url = queryString ? `volunteer/role/${roleId}/members?${queryString}` : `volunteer/role/${roleId}/members`;
      return await api.get(url);
   }
}
