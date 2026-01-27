
/**
 * Budget Module Entry Point
 */

import { BudgetState } from './state.js';
import { BudgetAPI } from './api.js';
import { BudgetStats } from './stats.js';
import { BudgetTable } from './table.js';
import { BudgetForm } from './form.js';
import { Config } from '../../core/config.js';

class BudgetModule {
   constructor() {
      this.state = new BudgetState();
      this.api = new BudgetAPI();
      this.stats = null;
      this.table = null;
      this.form = null;
   }

   async init() {
      try {
         if (!Auth.requireAuth()) return;
         await Config.waitForSettings();

         // Initialize stats
         this.stats = new BudgetStats(this.state, this.api);
         
         // Initialize table
         this.table = new BudgetTable(this.state, this.api);
         this.table.init();

         // Initialize form
         this.form = new BudgetForm(this.state, this.api, this.table, this.stats);
         this.form.init();

         // Load initial stats
         this.stats.load();

         this.initGlobalFunctions();
         console.log('✓ Budget module initialized');
      } catch (error) {
         console.error('Failed to initialize budget module:', error);
      }
   }

   initGlobalFunctions() {
      window.viewBudget = (id) => Alerts.info('View functionality coming soon');
      
      window.editBudget = (id) => {
         if (!Auth.hasPermission('create_budget')) {
            Alerts.error('You do not have permission to edit budgets');
            return;
         }
         Alerts.info('Edit functionality coming soon');
      };

      window.deleteBudget = (id) => {
         if (!Auth.hasPermission('delete_budget')) {
            Alerts.error('You do not have permission to delete budgets');
            return;
         }
         Alerts.info('Delete functionality coming soon');
      };

      window.approveBudget = (id) => {
         if (!Auth.hasPermission('approve_budget')) {
            Alerts.error('You do not have permission to approve budgets');
            return;
         }
         Alerts.info('Approve functionality coming soon');
      };
   }
}

document.addEventListener('DOMContentLoaded', () => {
   const module = new BudgetModule();
   module.init();
});
