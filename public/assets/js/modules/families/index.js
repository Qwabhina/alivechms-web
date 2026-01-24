/**
 * Families Module - Main Entry Point
 * 
 * @package  AliveChMS
 * @version  1.0.0
 */

import { FamilyState } from './state.js';
import { FamilyTable } from './table.js';
import { FamilyForm } from './form.js';
import { FamilyStats } from './stats.js';
import { FamilyAPI } from './api.js';

// ===================================================================
// ERROR HANDLING
// ===================================================================

function handleAPIError(error, context = 'Operation') {
   Config.error(`${context} failed:`, error);
   
   if (error.status === 401) {
      Alerts.error('Your session has expired. Redirecting to login...');
      setTimeout(() => Auth.logout(), 2000);
   } else if (error.status === 403) {
      Alerts.error('You do not have permission to perform this action.');
   } else if (error.status === 404) {
      Alerts.error('The requested resource was not found.');
   } else if (error.status === 422) {
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

class FamiliesModule {
   constructor() {
      this.state = new FamilyState();
      this.api = new FamilyAPI();
      this.table = null;
      this.form = null;
      this.stats = null;
   }

   async init() {
      if (!Auth.requireAuth()) return;
      
      await Config.waitForSettings();
      
      try {
         // Initialize components
         this.table = new FamilyTable(this.state, this.api);
         this.stats = new FamilyStats(this.state, this.api);
         this.form = new FamilyForm(this.state, this.api, this.table, this.stats);

         // Load data
         await Promise.all([
            this.stats.load(),
            this.table.init()
         ]);

         // Initialize form
         this.form.init();

         // Setup event listeners
         this.setupEventListeners();

         console.log('✓ Families module initialized');
      } catch (error) {
         handleAPIError(error, 'Page initialization');
      }
   }

   setupEventListeners() {
      // Add family button
      document.getElementById('addFamilyBtn')?.addEventListener('click', () => {
         if (!Auth.hasPermission('manage_families')) {
            Alerts.error('You do not have permission to create families');
            return;
         }
         this.form.open();
      });

      // Refresh button
      document.getElementById('refreshFamilyGrid')?.addEventListener('click', () => {
         this.table.refresh();
         this.stats.load();
         Alerts.info('Refreshing families list...');
      });

      // Make functions globally available for inline onclick handlers
      window.viewFamily = (id) => this.viewFamily(id);
      window.editFamily = (id) => this.editFamily(id);
      window.deleteFamily = (id) => this.deleteFamily(id);
      window.removeMemberFromFamily = (familyId, memberId) => this.form.removeMemberFromFamily(familyId, memberId);
   }

   async viewFamily(id) {
      this.state.currentFamilyId = id;
      try {
         await this.form.showView(id);
      } catch (error) {
         handleAPIError(error, 'Viewing family');
      }
   }

   async editFamily(id) {
      if (!Auth.hasPermission('manage_families')) {
         Alerts.error('You do not have permission to edit families');
         return;
      }

      try {
         await this.form.open(id);
      } catch (error) {
         handleAPIError(error, 'Loading family for edit');
      }
   }

   async deleteFamily(id) {
      if (!Auth.hasPermission('manage_families')) {
         Alerts.error('You do not have permission to delete families');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Family',
         text: 'Are you sure? Members will not be deleted, only the family grouping.',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting family...');
         await this.api.delete(id);
         Alerts.closeLoading();
         Alerts.success('Family deleted successfully');
         this.table.refresh();
         this.stats.load();
      } catch (error) {
         Alerts.closeLoading();
         handleAPIError(error, 'Deleting family');
      }
   }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
   const familiesModule = new FamiliesModule();
   familiesModule.init();
});
