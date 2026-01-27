import { FinancialReportState } from './state.js';
import { FinancialReportAPI } from './api.js';
import { FinancialReports } from './reports.js';
import { Auth } from '../../utils/auth.js';

class FinancialReportModule {
    constructor() {
        this.state = new FinancialReportState();
        this.api = new FinancialReportAPI();
        this.reports = new FinancialReports(this.state, this.api);
    }

    async init() {
        try {
            if (!Auth.requireAuth()) return;
            await this.loadFiscalYears();
            this.initEventListeners();
            this.initGlobalFunctions();
            console.log('✓ Financial Report module initialized');
        } catch (error) {
            console.error('Failed to initialize financial report module:', error);
        }
    }

    async loadFiscalYears() {
        try {
            const response = await this.api.getFiscalYears();
            this.state.fiscalYearsData = response?.data?.data || response?.data || [];

            const fiscalSelect = document.getElementById('fiscalYear');
            fiscalSelect.innerHTML = '<option value="">All Years</option>';
            this.state.fiscalYearsData.forEach((fy, index) => {
                const opt = document.createElement('option');
                opt.value = fy.FiscalYearID;
                opt.textContent = fy.FiscalYearName || fy.FiscalYearID;
                if (index === 0 && fy.Status === 'Active') opt.selected = true;
                fiscalSelect.appendChild(opt);
            });
        } catch (error) {
            console.error('Load fiscal years error:', error);
        }
    }

    initEventListeners() {
        // No direct event listeners needed as onclick handlers are in HTML
        // But we can bind them if we wanted to remove onclick from HTML
    }

    initGlobalFunctions() {
        window.generateReport = (type) => {
            document.getElementById('reportType').value = type;
            this.reports.generate(type);
        };
        window.generateSelectedReport = () => {
            const type = document.getElementById('reportType').value;
            this.reports.generate(type);
        };
        window.exportReport = (format) => this.reports.export(format);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const module = new FinancialReportModule();
    module.init();
});
