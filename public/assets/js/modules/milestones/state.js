/**
 * Milestones State Management
 */

export class MilestoneState {
   constructor() {
      this.milestones = [];
      this.milestoneTypes = [];
      this.stats = null;
      this.currentYear = new Date().getFullYear();
      this.filters = {
         type: '',
         member: '',
         startDate: '',
         endDate: ''
      };
   }

   setMilestones(milestones) {
      this.milestones = milestones;
   }

   getMilestones() {
      return this.milestones;
   }

   setMilestoneTypes(types) {
      this.milestoneTypes = types;
   }

   getMilestoneTypes() {
      return this.milestoneTypes;
   }

   getMilestoneTypeById(id) {
      return this.milestoneTypes.find(t => t.MilestoneTypeID === parseInt(id));
   }

   setStats(stats) {
      this.stats = stats;
   }

   getStats() {
      return this.stats;
   }

   setCurrentYear(year) {
      this.currentYear = year;
   }

   getCurrentYear() {
      return this.currentYear;
   }

   setFilters(filters) {
      this.filters = { ...this.filters, ...filters };
   }

   getFilters() {
      return this.filters;
   }

   clearFilters() {
      this.filters = {
         type: '',
         member: '',
         startDate: '',
         endDate: ''
      };
   }
}
