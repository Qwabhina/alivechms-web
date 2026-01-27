import { ExpenseState } from './state.js';
import { ExpenseAPI } from './api.js';
import { ExpenseStats } from './stats.js';
import { ExpenseTable } from './table.js';
import { ExpenseForm } from './form.js';
import { Auth } from '../../utils/auth.js';
import { Config } from '../../utils/config.js';

class ExpenseModule {
    constructor() {
        this.state = new ExpenseState();
        this.api = new ExpenseAPI();
        this.stats = null;
        this.table = null;
        this.form = null;
    }

    async init() {
        try {
            if (!Auth.requireAuth()) return;
            await Config.waitForSettings();
            this.state.currencySymbol = Config.getSetting('currency_symbol', 'GH₵');
            document.getElementById('currencySymbol').textContent = this.state.currencySymbol;

            this.stats = new ExpenseStats(this.state, this.api, this.form);
            this.table = new ExpenseTable(this.state, this.api, this.stats);
            this.form = new ExpenseForm(this.state, this.api, this.table, this.stats);
            
            // Circular dependency injection
            this.stats.form = this.form;
            
            await this.loadFiscalYearsForStats();
            await this.loadDropdowns();
            
            this.table.init();
            this.form.init();
            this.stats.load();
            this.initGlobalFunctions();
            
            document.getElementById('expenseDate').valueAsDate = new Date();
            console.log('✓ Expense module initialized');
        } catch (error) {
            console.error('Failed to initialize expense module:', error);
        }
    }

    async loadFiscalYearsForStats() {
        try {
            const fiscalRes = await this.api.getFiscalYears();
            this.state.fiscalYearsData = Array.isArray(fiscalRes) ? fiscalRes : (fiscalRes?.data || []);

            const select = document.getElementById('statsFiscalYear');
            select.innerHTML = '';

            const activeFY = this.state.fiscalYearsData.find(fy => fy.Status === 'Active');
            this.state.selectedFiscalYearId = activeFY?.FiscalYearID || null;

            this.state.fiscalYearsData.forEach(fy => {
                const opt = document.createElement('option');
                opt.value = fy.FiscalYearID;
                opt.textContent = fy.FiscalYearName + (fy.Status === 'Active' ? ' (Active)' : fy.Status === 'Closed' ? ' (Closed)' : '');
                if (fy.FiscalYearID === this.state.selectedFiscalYearId) opt.selected = true;
                select.appendChild(opt);
            });

            if (this.state.statsFiscalYearChoices) this.state.statsFiscalYearChoices.destroy();
            this.state.statsFiscalYearChoices = new Choices(select, {
                searchEnabled: true,
                searchPlaceholderValue: 'Search fiscal years...',
                itemSelectText: '',
                allowHTML: true,
                shouldSort: false
            });

            select.addEventListener('change', (e) => {
                this.state.selectedFiscalYearId = e.target.value ? parseInt(e.target.value) : null;
                this.stats.load();
                this.table.reload();
            });
        } catch (error) {
            console.error('Load fiscal years error:', error);
        }
    }

    async loadDropdowns() {
        try {
            const categories = await this.api.getCategories();
            this.state.categoriesData = Array.isArray(categories) ? categories : (categories?.data || []);

            // Populate filter category dropdown (not using Choices.js)
            const filterCategorySelect = document.getElementById('filterCategory');
            filterCategorySelect.innerHTML = '<option value="">All Categories</option>';
            this.state.categoriesData.forEach(c => {
                filterCategorySelect.innerHTML += `<option value="${c.ExpCategoryID}">${c.CategoryName}</option>`;
            });
        } catch (error) {
            console.error('Load dropdowns error:', error);
        }
    }

    initGlobalFunctions() {
        window.viewExpense = (id) => this.form.viewExpense(id);
        window.reviewExpense = (id) => this.form.reviewExpense(id);
        window.uploadProof = (id) => this.form.uploadProof(id);
        window.editCategory = (id, name) => this.form.editCategory(id, name);
        window.deleteCategory = (id, name) => this.form.deleteCategory(id, name);
        window.printExpense = () => this.form.printExpense();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const module = new ExpenseModule();
    module.init();
});
