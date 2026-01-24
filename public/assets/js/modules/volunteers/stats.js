/**
 * Volunteers Statistics Component
 */

export class VolunteerStats {
   constructor(state, api) {
      this.state = state;
      this.api = api;
   }

   init() {
      // Stats initialization if needed
   }

   async load() {
      try {
         // Load roles count
         const response = await this.api.getAllRoles();
         const roles = response?.data || [];
         this.updateRoleCount(roles.length);

         // These would come from actual API endpoints in production
         // For now, set to 0 as placeholders
         document.getElementById('activeVolunteers').textContent = '0';
         document.getElementById('upcomingEvents').textContent = '0';
         document.getElementById('monthlyVolunteers').textContent = '0';
      } catch (error) {
         console.error('Failed to load stats:', error);
         this.updateRoleCount(0);
      }
   }

   updateRoleCount(count) {
      document.getElementById('totalRoles').textContent = count;
   }
}
