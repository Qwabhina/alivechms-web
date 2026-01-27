import { FiscalYearState } from './state.js';
import { FiscalYearAPI } from './api.js';
import { FiscalYearTable } from './table.js';
import { FiscalYearForm } from './form.js';
import { Auth } from '../../utils/auth.js';
import { Config } from '../../utils/config.js';

class FiscalYearModule {
    constructor() {
        this.state = new FiscalYearState();
        this.api = new FiscalYearAPI();
        this.table = null;
        this.form = null;
    }

    async init() {
        try {
            if (!Auth.requireAuth()) return;
            await Config.waitForSettings();

            this.table = new FiscalYearTable(this.state, this.api);
            this.form = new FiscalYearForm(this.state, this.api, this.table);
            
            await this.table.init();
            await this.form.loadDropdowns();
            this.form.init();
            
            this.initGlobalFunctions();
            
            console.log('✓ Fiscal Year module initialized');
        } catch (error) {
            console.error('Failed to initialize fiscal year module:', error);
        }
    }

    initGlobalFunctions() {
        window.editFiscalYear = (id) => this.form.editFiscalYear(id);
        window.closeFiscalYear = (id) => this.form.closeFiscalYear(id);
        window.deleteFiscalYear = (id) => this.form.deleteFiscalYear(id);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const module = new FiscalYearModule();
    module.init();
});
