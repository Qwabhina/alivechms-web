
/**
 * Contribution Module Entry Point
 */

import { ContributionState } from './state.js';
import { ContributionAPI } from './api.js';
import { ContributionStats } from './stats.js';
import { ContributionTable } from './table.js';
import { ContributionForm } from './form.js';
import { Config } from '../../core/config.js';

class ContributionModule {
   constructor() {
      this.state = new ContributionState();
      this.api = new ContributionAPI();
      this.stats = null;
      this.table = null;
      this.form = null;
   }

   async init() {
      try {
         if (!Auth.requireAuth()) return;
         await Config.waitForSettings();
         this.state.currencySymbol = Config.getSetting('currency_symbol', 'GH₵');
         const symbolEl = document.getElementById('currencySymbol');
         if (symbolEl) symbolEl.textContent = this.state.currencySymbol;

         // Initialize stats
         this.stats = new ContributionStats(this.state, this.api);
         await this.stats.init();

         // Initialize table
         this.table = new ContributionTable(this.state, this.api, this.stats);
         this.table.init();

         // Initialize form
         this.form = new ContributionForm(this.state, this.api, this.table, this.stats);
         this.form.init();

         // Load initial data
         this.stats.load();

         this.initGlobalFunctions();
         console.log('✓ Contributions module initialized');
      } catch (error) {
         console.error('Failed to initialize contributions module:', error);
      }
   }

   initGlobalFunctions() {
      // Expose functions to global scope for onclick handlers in QMGrid or HTML
      window.editContribution = (id) => this.form.edit(id);
      window.deleteContribution = (id) => this.form.delete(id);
      window.restoreContribution = (id) => this.form.restore(id);
      window.viewContribution = (id) => this.viewContribution(id);
      
      window.editContributionType = (id, name, desc) => this.form.editType(id, name, desc);
      window.deleteContributionType = (id, name) => this.form.deleteType(id, name);
      
      window.showReceipt = (id) => this.showReceipt(id);
      window.showMemberStatement = (id) => this.showMemberStatement(id);
      
      // Print handlers
      window.printReceipt = () => {
         const content = document.getElementById('receiptContent').innerHTML;
         this.printContent(content);
      };
      
      window.printStatement = () => {
         const content = document.getElementById('statementContent').innerHTML;
         this.printContent(content);
      };
   }

   async viewContribution(id) {
      const modal = new bootstrap.Modal(document.getElementById('viewContributionModal'));
      modal.show();
      this.state.currentContributionId = id;

      const content = document.getElementById('viewContributionContent');
      content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="text-muted mt-2">Loading...</p></div>';

      try {
         const c = await this.api.get(id);
         const isDeleted = c.Deleted == 1;
         
         content.innerHTML = `
            <div class="p-4">
               <div class="text-center mb-4">
                  <div class="display-6 fw-bold text-primary mb-1">${formatCurrency(c.ContributionAmount)}</div>
                  <div class="text-muted">${QMGridHelper.formatDate(c.ContributionDate, 'long')}</div>
                  ${isDeleted ? '<span class="badge bg-danger mt-2">Deleted</span>' : ''}
               </div>
               
               <div class="row g-3">
                  <div class="col-md-6">
                     <div class="text-muted small text-uppercase">Member</div>
                     <div class="fw-medium">${c.MbrFirstName} ${c.MbrFamilyName}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small text-uppercase">Type</div>
                     <div class="fw-medium">${c.ContributionTypeName}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small text-uppercase">Payment Method</div>
                     <div class="fw-medium">${c.PaymentOptionName || '-'}</div>
                  </div>
                  <div class="col-md-6">
                     <div class="text-muted small text-uppercase">Fiscal Year</div>
                     <div class="fw-medium">${c.FiscalYearName || '-'}</div>
                  </div>
                  ${c.Notes ? `
                  <div class="col-12">
                     <div class="text-muted small text-uppercase">Description</div>
                     <div>${c.Notes}</div>
                  </div>` : ''}
               </div>
            </div>
         `;
      } catch (error) {
         content.innerHTML = `<div class="text-center py-5 text-danger"><i class="bi bi-exclamation-circle fs-1"></i><p>Failed to load contribution</p></div>`;
      }
   }

   async showReceipt(id) {
      const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
      modal.show();
      
      const content = document.getElementById('receiptContent');
      content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
      
      try {
         const c = await this.api.get(id);
         
         // Build Receipt HTML
         const churchName = Config.getSetting('church_name', 'Church Name');
         const receiptHtml = `
            <div class="receipt-container p-4 border rounded">
               <div class="text-center mb-4 border-bottom pb-3">
                  <h3 class="mb-1">${churchName}</h3>
                  <div class="text-uppercase tracking-wide text-muted small">Contribution Receipt</div>
               </div>
               
               <div class="d-flex justify-content-between mb-4">
                  <div>
                     <small class="text-muted d-block">Receipt No.</small>
                     <strong>#${String(c.ContributionID).padStart(6, '0')}</strong>
                  </div>
                  <div class="text-end">
                     <small class="text-muted d-block">Date</small>
                     <strong>${QMGridHelper.formatDate(c.ContributionDate, 'short')}</strong>
                  </div>
               </div>
               
               <div class="mb-4 text-center py-3 bg-light rounded">
                  <small class="text-muted d-block mb-1">Amount Received</small>
                  <h2 class="mb-0 text-primary">${formatCurrency(c.ContributionAmount)}</h2>
               </div>
               
               <div class="row mb-4">
                  <div class="col-6 mb-2">
                     <small class="text-muted d-block">Received From</small>
                     <span class="fw-medium">${c.MbrFirstName} ${c.MbrFamilyName}</span>
                  </div>
                  <div class="col-6 mb-2">
                     <small class="text-muted d-block">Contribution Type</small>
                     <span class="fw-medium">${c.ContributionTypeName}</span>
                  </div>
                  <div class="col-6">
                     <small class="text-muted d-block">Payment Method</small>
                     <span>${c.PaymentOptionName || '-'}</span>
                  </div>
               </div>
               
               <div class="text-center mt-5 pt-3 border-top">
                  <p class="mb-1 fst-italic">"God loves a cheerful giver." - 2 Corinthians 9:7</p>
                  <small class="text-muted">Thank you for your contribution!</small>
               </div>
            </div>
         `;
         
         content.innerHTML = receiptHtml;
      } catch (error) {
         content.innerHTML = `<div class="text-center py-5 text-danger"><p>Failed to generate receipt</p></div>`;
      }
   }

   async showMemberStatement(memberId) {
      const modal = new bootstrap.Modal(document.getElementById('statementModal'));
      modal.show();
      
      const content = document.getElementById('statementContent');
      content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
      
      // We would need an API endpoint for member statement
      // For now, let's just show a placeholder or basic list if we have an endpoint
      // Assuming we can filter by memberId
      
      try {
         // Fetch last 50 contributions for this member
         const res = await this.api.getAll({
            member_id: memberId, // Assuming API supports this
            limit: 50
         });
         
         const contributions = res.data || [];
         const memberName = contributions[0] ? `${contributions[0].MbrFirstName} ${contributions[0].MbrFamilyName}` : 'Member';
         
         let total = 0;
         const rows = contributions.map(c => {
            total += parseFloat(c.ContributionAmount);
            return `
               <tr>
                  <td>${QMGridHelper.formatDate(c.ContributionDate, 'short')}</td>
                  <td>${c.ContributionTypeName}</td>
                  <td class="text-end">${formatCurrency(c.ContributionAmount)}</td>
               </tr>
            `;
         }).join('');
         
         content.innerHTML = `
            <div class="p-3">
               <h4 class="mb-3">Statement for ${memberName}</h4>
               <div class="table-responsive">
                  <table class="table table-sm">
                     <thead>
                        <tr>
                           <th>Date</th>
                           <th>Type</th>
                           <th class="text-end">Amount</th>
                        </tr>
                     </thead>
                     <tbody>
                        ${rows || '<tr><td colspan="3" class="text-center">No contributions found</td></tr>'}
                     </tbody>
                     <tfoot>
                        <tr class="fw-bold">
                           <td colspan="2" class="text-end">Total</td>
                           <td class="text-end">${formatCurrency(total)}</td>
                        </tr>
                     </tfoot>
                  </table>
               </div>
            </div>
         `;
      } catch (error) {
         content.innerHTML = `<div class="text-center py-5 text-danger"><p>Failed to load statement</p></div>`;
      }
   }

   printContent(content) {
      const printWindow = window.open('', '', 'height=600,width=800');
      printWindow.document.write('<html><head><title>Print</title>');
      printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
      printWindow.document.write('</head><body>');
      printWindow.document.write(content);
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
         printWindow.print();
         printWindow.close();
      }, 500);
   }
}

document.addEventListener('DOMContentLoaded', () => {
   const module = new ContributionModule();
   module.init();
});
