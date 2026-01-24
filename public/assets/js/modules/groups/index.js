/**
 * Groups Module - Main Entry Point
 */

import { GroupState } from './state.js';
import { GroupAPI } from './api.js';
import { GroupTable } from './table.js';
import { GroupForm } from './form.js';
import { GroupStats } from './stats.js';

class GroupsModule {
   constructor() {
      this.state = new GroupState();
      this.api = new GroupAPI();
      this.stats = null;
      this.table = null;
      this.form = null;
   }

   async init() {
      try {
         // Initialize stats first
         this.stats = new GroupStats(this.state, this.api);
         this.stats.init();

         // Initialize table
         this.table = new GroupTable(this.state, this.api, this.stats);
         this.table.init();

         // Initialize form with table and stats references
         this.form = new GroupForm(this.state, this.api, this.table, this.stats);
         await this.form.init();

         // Load initial data
         await this.stats.load();

         this.initGlobalFunctions();

         console.log('✓ Groups module initialized');
      } catch (error) {
         console.error('Failed to initialize groups module:', error);
         Alerts.error('Failed to initialize groups module');
      }
   }

   initGlobalFunctions() {
      // Expose functions to global scope for onclick handlers
      window.viewGroup = (groupId) => this.form.showView(groupId);
      window.editGroup = (groupId) => {
         if (!Auth.hasPermission('manage_groups')) {
            Alerts.error('You do not have permission to edit groups');
            return;
         }
         this.form.open(groupId);
      };
      window.deleteGroup = async (groupId) => {
         if (!Auth.hasPermission('manage_groups')) {
            Alerts.error('You do not have permission to delete groups');
            return;
         }

         const confirmed = await Alerts.confirm({
            title: 'Delete Group',
            text: 'Are you sure? This action cannot be undone.',
            icon: 'warning',
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc3545'
         });

         if (!confirmed) return;

         try {
            Alerts.loading('Deleting group...');
            await this.api.delete(groupId);
            Alerts.closeLoading();
            Alerts.success('Group deleted');
            this.table.refresh();
            this.stats.load();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      };

      window.removeMemberFromGroup = (groupId, memberId) => 
         this.form.removeMemberFromGroup(groupId, memberId);
      
      window.deleteGroupType = (typeId) => 
         this.form.deleteGroupType(typeId);
   }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', async () => {
   if (!Auth.requireAuth()) return;
   await Config.waitForSettings();
   
   const module = new GroupsModule();
   await module.init();
});
