
export class ContributionState {
   constructor() {
      this.table = null;
      this.currentId = null;
      this.isEditMode = false;
      
      // Data Stores
      this.members = [];
      this.types = [];
      this.paymentOptions = [];
      this.fiscalYears = [];
      this.statsData = null;
      
      // Choices Instances
      this.memberChoices = null;
      this.typeChoices = null;
      this.paymentChoices = null;
      this.fiscalYearChoices = null;
      this.statsFiscalYearChoices = null;
      
      // Selection State
      this.selectedFiscalYearId = null;
      this.editingTypeId = null;
      
      // Config
      this.currencySymbol = 'GH₵';
      
      // Charts
      this.byTypeChart = null;
      this.monthlyTrendChart = null;
   }
}
