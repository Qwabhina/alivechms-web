/**
 * Volunteers Module - Main Entry Point
 */

import { VolunteerState } from './state.js';
import { VolunteerAPI } from './api.js';
import { VolunteerTable } from './table.js';
import { VolunteerForm } from './form.js';
import { VolunteerStats } from './stats.js';

class VolunteersModule {
   constructor() {
      this.state = new VolunteerState();
      this.api = new VolunteerAPI();
      this.stats = null;
      this.table = null;
      this.form = null;
   }

   async init() {
      try {
         // Initialize stats first
         this.stats = new VolunteerStats(this.state, this.api);
         this.stats.init();

         // Initialize table
         this.table = new VolunteerTable(this.state, this.api, this.stats);
         this.table.init();

         // Initialize form with table and stats references
         this.form = new VolunteerForm(this.state, this.api, this.table, this.stats);
         this.form.init();

         // Load initial data
         await this.stats.load();

         this.initGlobalFunctions();

         console.log('✓ Volunteers module initialized');
      } catch (error) {
         console.error('Failed to initialize volunteers module:', error);
         Alerts.error('Failed to initialize volunteers module');
      }
   }

   initGlobalFunctions() {
      // Expose functions to global scope for onclick handlers
      window.viewRoleDetails = (roleId) => this.form.viewRoleDetails(roleId);
      window.manageRoleMembers = (roleId) => this.form.manageRoleMembers(roleId);
      window.removeRoleMember = (assignmentId) => this.form.removeRoleMember(assignmentId);
      window.loadRoles = () => this.table.refresh();
   }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', async () => {
   if (!Auth.requireAuth()) return;
   await Config.waitForSettings();
   
   const module = new VolunteersModule();
   await module.init();
});
