export class ExpenseState {
    constructor() {
        this.expensesTable = null;
        this.currentExpenseId = null;
        this.currentExpenseData = null;
        this.categoriesData = [];
        this.fiscalYearsData = [];
        this.categoryChoices = null;
        this.fiscalYearChoices = null;
        this.statsFiscalYearChoices = null;
        this.selectedFiscalYearId = null;
        this.currencySymbol = 'GH₵';
        this.byCategoryChart = null;
        this.monthlyTrendChart = null;
        this.currentFilters = {};
        this.editingCategoryId = null;
    }
}
