/**
 * Members Module - Main Entry Point
 * 
 * @package  AliveChMS
 * @version  6.0.0 - Modular Refactor
 */

import { MemberState } from './state.js';
import { MemberTable } from './table.js';
import { MemberForm } from './form.js';
import { MemberStats } from './stats.js';
import { MemberAPI } from './api.js';

// ===================================================================
// ERROR HANDLING
// ===================================================================

/**
 * Handle API errors with user-friendly messages
 * @param {Error} error - The error object
 * @param {string} context - Context of the operation
 */
function handleAPIError(error, context = 'Operation') {
   Config.error(`${context} failed:`, error);
   
   // Handle specific error types
   if (error.status === 401) {
      Alerts.error('Your session has expired. Redirecting to login...');
      setTimeout(() => Auth.logout(), 2000);
   } else if (error.status === 403) {
      Alerts.error('You do not have permission to perform this action.');
   } else if (error.status === 404) {
      Alerts.error('The requested resource was not found.');
   } else if (error.status === 422) {
      // Validation error - show specific field errors if available
      if (error.data && error.data.errors) {
         const errorMessages = Object.values(error.data.errors).flat().join('<br>');
         Alerts.error(`Validation failed:<br>${errorMessages}`);
      } else {
         Alerts.error(error.message || 'Validation failed. Please check your input.');
      }
   } else if (error.status === 500) {
      Alerts.error('Server error. Please try again later.');
   } else if (error.isNetworkError && error.isNetworkError()) {
      Alerts.error('Network error. Please check your internet connection.');
   } else {
      const message = error.message || `${context} failed. Please try again.`;
      Alerts.error(message);
   }
}

class MembersModule {
   constructor() {
      this.state = new MemberState();
      this.api = new MemberAPI();
      this.table = null;
      this.form = null;
      this.stats = null;
      this.autoRefreshInterval = null;
   }

   async init() {
      if (!Auth.requireAuth()) return;
      
      await Config.waitForSettings();
      
      try {
         // Initialize components
         this.table = new MemberTable(this.state, this.api);
         this.stats = new MemberStats(this.state, this.api);
         this.form = new MemberForm(this.state, this.api, this.table, this.stats);

         // Load data
         await Promise.all([
            this.stats.load(),
            this.table.init()
         ]);

         // Initialize form
         this.form.init();

         // Setup event listeners
         this.setupEventListeners();

         // Start auto-refresh after 10 seconds
         setTimeout(() => this.startAutoRefresh(), 10000);

         console.log('✓ Members module initialized');
      } catch (error) {
         handleAPIError(error, 'Page initialization');
      }
   }

   setupEventListeners() {
      // Add member button
      document.getElementById('addMemberBtn')?.addEventListener('click', () => {
         if (!Auth.hasPermission(Config.PERMISSIONS.CREATE_MEMBERS)) {
            Alerts.error('You do not have permission to create members');
            return;
         }
         this.form.open();
      });

      // Refresh button
      document.getElementById('refreshMemberGrid')?.addEventListener('click', () => {
         this.table.refresh();
         Alerts.info('Refreshing member list...');
      });

      // Filters
      document.getElementById('applyMemberFilters')?.addEventListener('click', () => this.applyFilters());
      document.getElementById('clearMemberFilters')?.addEventListener('click', () => this.clearFilters());

      // Export buttons
      document.getElementById('exportSelectedMembers')?.addEventListener('click', () => this.exportSelected());
      document.getElementById('exportAllMembers')?.addEventListener('click', () => this.exportAll());
      document.getElementById('printMemberList')?.addEventListener('click', () => this.printList());

      // Clear selection
      document.getElementById('clearMemberSelection')?.addEventListener('click', () => this.clearSelection());

      // Make functions globally available for inline onclick handlers
      window.viewMember = (id) => this.viewMember(id);
      window.editMember = (id) => this.editMember(id);
      window.deleteMember = (id) => this.deleteMember(id);
      window.printMemberProfile = () => this.form.printProfile();
   }

   async viewMember(id) {
      this.state.currentMemberId = id;
      try {
         const member = await this.api.get(id);
         this.form.showView(member);
      } catch (error) {
         handleAPIError(error, 'Viewing member');
      }
   }

   async editMember(id) {
      if (!Auth.hasPermission(Config.PERMISSIONS.EDIT_MEMBERS)) {
         Alerts.error('You do not have permission to edit members');
         return;
      }

      try {
         const member = await this.api.get(id);
         this.form.open(member);
      } catch (error) {
         handleAPIError(error, 'Loading member for edit');
      }
   }

   async deleteMember(id) {
      if (!Auth.hasPermission(Config.PERMISSIONS.DELETE_MEMBERS)) {
         Alerts.error('You do not have permission to delete members');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Member',
         text: 'Are you sure you want to delete this member? This action cannot be undone.',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting member...');
         await this.api.delete(id);
         Alerts.closeLoading();
         Alerts.success('Member deleted successfully');
         this.table.refresh();
         this.stats.load();
      } catch (error) {
         Alerts.closeLoading();
         handleAPIError(error, 'Deleting member');
      }
   }

   applyFilters() {
      const filters = {};
      
      const statusFilter = document.getElementById('statusFilter');
      const familyFilter = document.getElementById('familyFilter');
      const genderFilter = document.getElementById('genderFilter');
      const dateFromFilter = document.getElementById('dateFromFilter');
      const dateToFilter = document.getElementById('dateToFilter');
      
      if (statusFilter?.value) filters.status = statusFilter.value;
      if (familyFilter?.value) filters.family_id = familyFilter.value;
      if (genderFilter?.value) filters.gender = genderFilter.value;
      if (dateFromFilter?.value) filters.date_from = dateFromFilter.value;
      if (dateToFilter?.value) filters.date_to = dateToFilter.value;
      
      if (this.table && Object.keys(filters).length > 0) {
         QMGridHelper.updateFilters(this.table.grid, filters);
      } else if (this.table) {
         this.table.refresh();
      }
   }

   clearFilters() {
      ['statusFilter', 'familyFilter', 'genderFilter', 'dateFromFilter', 'dateToFilter'].forEach(id => {
         const element = document.getElementById(id);
         if (element) element.value = '';
      });
      
      if (this.table) {
         this.table.refresh();
      }
   }

   exportSelected() {
      if (!this.table || !this.table.grid) return;
      
      const selectedRows = QMGridHelper.getSelectedRows(this.table.grid);
      if (selectedRows.length === 0) {
         Alerts.warning('Please select members to export');
         return;
      }
      
      QMGridHelper.export(this.table.grid, 'excel', {
         selectedOnly: true,
         filename: `selected-members-${new Date().toISOString().split('T')[0]}`
      });
      
      Alerts.success(`Exporting ${selectedRows.length} selected members`);
   }

   exportAll() {
      if (!this.table || !this.table.grid) return;
      
      QMGridHelper.export(this.table.grid, 'excel', {
         filename: `all-members-${new Date().toISOString().split('T')[0]}`
      });
      
      Alerts.success('Exporting all members');
   }

   printList() {
      if (!this.table || !this.table.grid) return;
      
      QMGridHelper.export(this.table.grid, 'print', {
         filename: 'Church Members List'
      });
   }

   clearSelection() {
      if (this.table && this.table.grid) {
         QMGridHelper.clearSelection(this.table.grid);
      }
   }

   startAutoRefresh() {
      if (this.autoRefreshInterval) {
         clearInterval(this.autoRefreshInterval);
      }
      
      this.autoRefreshInterval = setInterval(() => {
         if (this.table && this.table.grid && document.visibilityState === 'visible') {
            console.log('Auto-refreshing member data...');
            this.table.refresh();
         }
      }, 5 * 60 * 1000); // 5 minutes
   }

   stopAutoRefresh() {
      if (this.autoRefreshInterval) {
         clearInterval(this.autoRefreshInterval);
         this.autoRefreshInterval = null;
      }
   }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
   const membersModule = new MembersModule();
   membersModule.init();

   // Handle visibility changes
   document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
         membersModule.stopAutoRefresh();
      } else if (membersModule.table) {
         membersModule.startAutoRefresh();
      }
   });

   // Cleanup on page unload
   window.addEventListener('beforeunload', () => {
      membersModule.stopAutoRefresh();
      if (membersModule.table && membersModule.table.grid) {
         QMGridHelper.destroy(membersModule.table.grid);
      }
   });
});
