export class PledgeState {
    constructor() {
        this.pledgesTable = null;
        this.currentPledgeId = null;
        this.currentPledgeData = null;
        this.membersData = [];
        this.pledgeTypesData = [];
        this.fiscalYearsData = [];
        this.memberChoices = null;
        this.pledgeTypeChoices = null;
        this.fiscalYearChoices = null;
        this.statsFiscalYearChoices = null;
        this.selectedFiscalYearId = null;
        this.currencySymbol = 'GH₵';
        this.byTypeChart = null;
        this.monthlyTrendChart = null;
        this.currentFilters = {};
        this.editingPledgeTypeId = null;
    }
}
