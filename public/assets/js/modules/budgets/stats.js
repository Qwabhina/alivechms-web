
/**
 * Budget Statistics
 */

export class BudgetStats {
   constructor(state, api) {
      this.state = state;
      this.api = api;
   }

   async load() {
      try {
         const response = await this.api.getStats();
         const budgets = response?.data?.data || response?.data || [];

         let totalAmount = 0,
            totalCount = 0;
         let draftAmount = 0,
            draftCount = 0;
         let approvedAmount = 0,
            approvedCount = 0;
         let submittedAmount = 0,
            submittedCount = 0;

         budgets.forEach(b => {
            const amount = parseFloat(b.TotalAmount);
            totalAmount += amount;
            totalCount++;

            if (b.BudgetStatus === 'Draft') {
               draftAmount += amount;
               draftCount++;
            } else if (b.BudgetStatus === 'Approved') {
               approvedAmount += amount;
               approvedCount++;
            } else if (b.BudgetStatus === 'Submitted') {
               submittedAmount += amount;
               submittedCount++;
            }
         });

         this.updateStat('totalBudgets', totalAmount, 'budgetCount', totalCount);
         this.updateStat('draftAmount', draftAmount, 'draftCount', draftCount);
         this.updateStat('approvedAmount', approvedAmount, 'approvedCount', approvedCount);
         this.updateStat('submittedAmount', submittedAmount, 'submittedCount', submittedCount);
      } catch (error) {
         console.error('Load stats error:', error);
      }
   }

   updateStat(amountId, amount, countId, count) {
      const amountEl = document.getElementById(amountId);
      const countEl = document.getElementById(countId);
      if (amountEl) amountEl.textContent = formatCurrencyLocale(amount);
      if (countEl) countEl.textContent = count;
   }
}
