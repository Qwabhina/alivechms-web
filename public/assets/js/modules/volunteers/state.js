/**
 * Volunteers State Management
 */

export class VolunteerState {
   constructor() {
      this.roles = [];
      this.currentRoleId = null;
   }

   reset() {
      this.currentRoleId = null;
   }

   setRoles(roles) {
      this.roles = roles;
   }

   getRoleById(roleId) {
      return this.roles.find(r => r.VolunteerRoleID === roleId);
   }
}
