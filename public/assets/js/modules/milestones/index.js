/**
 * Milestones Module - Main Entry Point
 */

import { MilestoneState } from './state.js';
import { MilestoneAPI } from './api.js';
import { MilestoneStats } from './stats.js';
import { MilestoneTable } from './table.js';
import { MilestoneForm } from './form.js';

class MilestonesModule {
   constructor() {
      this.state = new MilestoneState();
      this.api = new MilestoneAPI();
      this.stats = null;
      this.table = null;
      this.form = null;
   }

   async init() {
      try {
         // Initialize stats first
         this.stats = new MilestoneStats(this.state, this.api);
         this.stats.init();

         // Initialize table
         this.table = new MilestoneTable(this.state, this.api, this.stats);
         this.table.init();

         // Initialize form with table and stats references
         this.form = new MilestoneForm(this.state, this.api, this.table, this.stats);
         this.form.init();

         // Load initial data
         await Promise.all([
            this.stats.load(this.state.getCurrentYear()),
            this.table.loadFilterOptions()
         ]);

         this.initGlobalFunctions();

         console.log('✓ Milestones module initialized');
      } catch (error) {
         console.error('Failed to initialize milestones module:', error);
         Alerts.error('Failed to initialize milestones module');
      }
   }

   initGlobalFunctions() {
      // Expose functions to global scope for onclick handlers
      window.viewMilestone = (milestoneId) => this.form.viewMilestone(milestoneId);
      window.editMilestone = (milestoneId) => this.form.openMilestoneModal(milestoneId);
      window.deleteMilestone = (milestoneId) => this.form.deleteMilestone(milestoneId);
      window.editMilestoneType = (typeId) => this.form.openTypeModal(typeId);
      window.deleteMilestoneType = (typeId) => this.form.deleteMilestoneType(typeId);
   }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', async () => {
   if (!Auth.requireAuth()) return;
   await Config.waitForSettings();
   
   const module = new MilestonesModule();
   await module.init();
});
