import { PledgeState } from './state.js';
import { PledgeAPI } from './api.js';
import { PledgeStats } from './stats.js';
import { PledgeTable } from './table.js';
import { PledgeForm } from './form.js';
import { Auth } from '../../utils/auth.js';
import { Config } from '../../utils/config.js';

class PledgeModule {
    constructor() {
        this.state = new PledgeState();
        this.api = new PledgeAPI();
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
            document.getElementById('paymentCurrencySymbol').textContent = this.state.currencySymbol;

            this.stats = new PledgeStats(this.state, this.api, this.form);
            this.table = new PledgeTable(this.state, this.api, this.stats);
            this.form = new PledgeForm(this.state, this.api, this.table, this.stats);
            
            // Circular dependency injection
            this.stats.form = this.form;
            
            await this.loadFiscalYearsForStats();
            await this.loadDropdowns();
            
            this.table.init();
            this.form.init();
            this.stats.load();
            this.initGlobalFunctions();
            
            document.getElementById('pledgeDate').valueAsDate = new Date();
            document.getElementById('paymentDate').valueAsDate = new Date();
            
            console.log('✓ Pledge module initialized');
        } catch (error) {
            console.error('Failed to initialize pledge module:', error);
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
            const [membersRes, typesRes] = await Promise.all([
                this.api.getMembers(),
                this.api.getPledgeTypes()
            ]);

            this.state.membersData = Array.isArray(membersRes) ? membersRes : (membersRes?.data || []);
            this.state.pledgeTypesData = Array.isArray(typesRes) ? typesRes : (typesRes?.data || []);

            // Populate filter type dropdown (not using Choices.js)
            const filterTypeSelect = document.getElementById('filterType');
            filterTypeSelect.innerHTML = '<option value="">All Types</option>';
            this.state.pledgeTypesData.forEach(t => {
                filterTypeSelect.innerHTML += `<option value="${t.PledgeTypeID}">${t.PledgeTypeName}</option>`;
            });
        } catch (error) {
            console.error('Load dropdowns error:', error);
        }
    }

    initGlobalFunctions() {
        window.viewPledge = (id) => this.form.viewPledge(id);
        window.editPledge = (id) => this.form.editPledge(id);
        window.recordPayment = (id) => this.form.recordPayment(id);
        window.editPledgeType = (id, name, desc) => this.form.editPledgeType(id, name, desc);
        window.deletePledgeType = (id, name) => this.form.deletePledgeType(id, name);
        window.printPledge = () => this.form.printPledge();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const module = new PledgeModule();
    module.init();
});
