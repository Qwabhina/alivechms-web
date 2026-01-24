/**
 * Volunteers Table Component
 */

export class VolunteerTable {
   constructor(state, api, stats = null) {
      this.state = state;
      this.api = api;
      this.stats = stats;
   }

   init() {
      this.initEventListeners();
      this.loadRoles();
      console.log('✓ Volunteer table initialized');
   }

   initEventListeners() {
      // Refresh button is handled via global function
   }

   async loadRoles() {
      try {
         const response = await this.api.getAllRoles();
         const roles = response?.data || [];
         
         this.state.setRoles(roles);
         this.renderTable(roles);
         
         // Update stats
         if (this.stats) {
            this.stats.updateRoleCount(roles.length);
         }
         
         console.log('✓ Loaded roles with member counts:', roles);
      } catch (error) {
         console.error('Load roles error:', error);
         this.renderError();
      }
   }

   renderTable(roles) {
      const tbody = document.getElementById('rolesTableBody');
      
      if (roles.length === 0) {
         tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No volunteer roles yet</td></tr>';
         return;
      }

      tbody.innerHTML = roles.map(role => {
         const memberCount = parseInt(role.MemberCount) || 0;
         return `
            <tr>
               <td class="fw-semibold">${role.RoleName}</td>
               <td>${role.Description || '-'}</td>
               <td>
                  <span class="badge bg-primary">${memberCount} ${memberCount === 1 ? 'member' : 'members'}</span>
               </td>
               <td>
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-outline-primary" onclick="viewRoleDetails(${role.VolunteerRoleID})" title="View">
                        <i class="bi bi-eye"></i>
                     </button>
                     <button class="btn btn-outline-success" onclick="manageRoleMembers(${role.VolunteerRoleID})" title="Manage Members">
                        <i class="bi bi-people"></i>
                     </button>
                  </div>
               </td>
            </tr>
         `;
      }).join('');
   }

   renderError() {
      const tbody = document.getElementById('rolesTableBody');
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Failed to load roles</td></tr>';
   }

   refresh() {
      this.loadRoles();
   }
}
