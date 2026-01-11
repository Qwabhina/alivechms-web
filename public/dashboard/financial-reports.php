<?php
$pageTitle = 'Financial Reports';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Financial Reports</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Financial Reports</li>
            </ol>
         </nav>
      </div>
   </div>

   <!-- Report Selection -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card h-100 report-card" onclick="generateReport('contributions')">
            <div class="card-body text-center">
               <div class="mb-3">
                  <i class="bi bi-cash-stack text-primary" style="font-size: 3rem;"></i>
               </div>
               <h5 class="card-title">Contributions Report</h5>
               <p class="card-text text-muted small">Detailed contribution analysis by type, member, and period</p>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card h-100 report-card" onclick="generateReport('pledges')">
            <div class="card-body text-center">
               <div class="mb-3">
                  <i class="bi bi-clipboard-check text-success" style="font-size: 3rem;"></i>
               </div>
               <h5 class="card-title">Pledges Report</h5>
               <p class="card-text text-muted small">Pledge fulfillment status and outstanding balances</p>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card h-100 report-card" onclick="generateReport('expenses')">
            <div class="card-body text-center">
               <div class="mb-3">
                  <i class="bi bi-receipt text-danger" style="font-size: 3rem;"></i>
               </div>
               <h5 class="card-title">Expenses Report</h5>
               <p class="card-text text-muted small">Expense breakdown by category and approval status</p>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card h-100 report-card" onclick="generateReport('budget')">
            <div class="card-body text-center">
               <div class="mb-3">
                  <i class="bi bi-wallet2 text-warning" style="font-size: 3rem;"></i>
               </div>
               <h5 class="card-title">Budget vs Actual</h5>
               <p class="card-text text-muted small">Compare budgeted amounts with actual spending</p>
            </div>
         </div>
      </div>
   </div>

   <!-- Report Filters -->
   <div class="card mb-4">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Report Filters</h5>
      </div>
      <div class="card-body">
         <div class="row g-3">
            <div class="col-md-3">
               <label class="form-label">Report Type</label>
               <select class="form-select" id="reportType">
                  <option value="contributions">Contributions</option>
                  <option value="pledges">Pledges</option>
                  <option value="expenses">Expenses</option>
                  <option value="budget">Budget vs Actual</option>
                  <option value="summary">Financial Summary</option>
               </select>
            </div>
            <div class="col-md-3">
               <label class="form-label">Fiscal Year</label>
               <select class="form-select" id="fiscalYear">
                  <option value="">All Years</option>
               </select>
            </div>
            <div class="col-md-2">
               <label class="form-label">Start Date</label>
               <input type="date" class="form-control" id="startDate">
            </div>
            <div class="col-md-2">
               <label class="form-label">End Date</label>
               <input type="date" class="form-control" id="endDate">
            </div>
            <div class="col-md-2 d-flex align-items-end">
               <button class="btn btn-primary w-100" onclick="generateSelectedReport()">
                  <i class="bi bi-file-earmark-bar-graph me-1"></i>Generate
               </button>
            </div>
         </div>
      </div>
   </div>

   <!-- Report Output -->
   <div class="card" id="reportCard" style="display: none;">
      <div class="card-header d-flex justify-content-between align-items-center">
         <h5 class="mb-0" id="reportTitle">Report</h5>
         <div class="btn-group">
            <button class="btn btn-sm btn-success" onclick="exportReport('excel')">
               <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button class="btn btn-sm btn-danger" onclick="exportReport('pdf')">
               <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
            <button class="btn btn-sm btn-primary" onclick="window.print()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
         </div>
      </div>
      <div class="card-body">
         <div id="reportContent">
            <!-- Report content will be loaded here -->
         </div>
      </div>
   </div>
</div>
</main>

<style>
   .report-card {
      cursor: pointer;
      transition: all 0.3s ease;
      border: 2px solid transparent;
   }

   .report-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      border-color: var(--bs-primary);
   }
</style>

<script>
   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await loadFiscalYears();
   });

   async function loadFiscalYears() {
      try {
         const response = await api.get('fiscalyear/all?limit=10');
         const fiscalYears = response?.data?.data || response?.data || [];

         const fiscalSelect = document.getElementById('fiscalYear');
         fiscalSelect.innerHTML = '<option value="">All Years</option>';
         fiscalYears.forEach((fy, index) => {
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

   function generateReport(type) {
      document.getElementById('reportType').value = type;
      generateSelectedReport();
   }

   async function generateSelectedReport() {
      const reportType = document.getElementById('reportType').value;
      const fiscalYearId = document.getElementById('fiscalYear').value;
      const startDate = document.getElementById('startDate').value;
      const endDate = document.getElementById('endDate').value;

      Alerts.loading('Generating report...');

      try {
         let reportData;
         let reportTitle;

         switch (reportType) {
            case 'contributions':
               reportData = await generateContributionsReport(fiscalYearId, startDate, endDate);
               reportTitle = 'Contributions Report';
               break;
            case 'pledges':
               reportData = await generatePledgesReport(fiscalYearId, startDate, endDate);
               reportTitle = 'Pledges Report';
               break;
            case 'expenses':
               reportData = await generateExpensesReport(fiscalYearId, startDate, endDate);
               reportTitle = 'Expenses Report';
               break;
            case 'budget':
               reportData = await generateBudgetReport(fiscalYearId);
               reportTitle = 'Budget vs Actual Report';
               break;
            case 'summary':
               reportData = await generateSummaryReport(fiscalYearId, startDate, endDate);
               reportTitle = 'Financial Summary Report';
               break;
         }

         document.getElementById('reportTitle').textContent = reportTitle;
         document.getElementById('reportContent').innerHTML = reportData;
         document.getElementById('reportCard').style.display = 'block';

         Alerts.closeLoading();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Generate report error:', error);
         Alerts.error('Failed to generate report');
      }
   }

   async function generateContributionsReport(fiscalYearId, startDate, endDate) {
      let url = 'contribution/all?limit=1000';
      if (fiscalYearId) url += `&fiscal_year_id=${fiscalYearId}`;
      if (startDate) url += `&start_date=${startDate}`;
      if (endDate) url += `&end_date=${endDate}`;

      const response = await api.get(url);
      const contributions = response?.data?.data || response?.data || [];

      const total = contributions.reduce((sum, c) => sum + parseFloat(c.ContributionAmount), 0);
      const byType = {};

      contributions.forEach(c => {
         const type = c.ContributionTypeName;
         if (!byType[type]) byType[type] = {
            count: 0,
            total: 0
         };
         byType[type].count++;
         byType[type].total += parseFloat(c.ContributionAmount);
      });

      let html = `
         <div class="mb-4">
            <h4>Summary</h4>
            <p><strong>Total Contributions:</strong> ${formatCurrencyLocale(total)}</p>
            <p><strong>Number of Contributions:</strong> ${contributions.length}</p>
         </div>
         <div class="mb-4">
            <h5>By Type</h5>
            <table class="table table-bordered">
               <thead class="table-light">
                  <tr>
                     <th>Type</th>
                     <th class="text-end">Count</th>
                     <th class="text-end">Total Amount</th>
                  </tr>
               </thead>
               <tbody>
      `;

      for (const [type, data] of Object.entries(byType)) {
         html += `
            <tr>
               <td>${type}</td>
               <td class="text-end">${data.count}</td>
               <td class="text-end">${formatCurrencyLocale(data.total)}</td>
            </tr>
         `;
      }

      html += `
               </tbody>
            </table>
         </div>
      `;

      return html;
   }

   async function generatePledgesReport(fiscalYearId, startDate, endDate) {
      let url = 'pledge/all?limit=1000';
      if (fiscalYearId) url += `&fiscal_year_id=${fiscalYearId}`;

      const response = await api.get(url);
      const pledges = response?.data?.data || response?.data || [];

      const total = pledges.reduce((sum, p) => sum + parseFloat(p.PledgeAmount), 0);
      const fulfilled = pledges.filter(p => p.PledgeStatus === 'Fulfilled').length;
      const active = pledges.filter(p => p.PledgeStatus === 'Active').length;

      return `
         <div class="mb-4">
            <h4>Summary</h4>
            <p><strong>Total Pledges:</strong> ${formatCurrencyLocale(total)}</p>
            <p><strong>Total Count:</strong> ${pledges.length}</p>
            <p><strong>Fulfilled:</strong> ${fulfilled}</p>
            <p><strong>Active:</strong> ${active}</p>
            <p><strong>Fulfillment Rate:</strong> ${pledges.length > 0 ? ((fulfilled / pledges.length) * 100).toFixed(1) : 0}%</p>
         </div>
      `;
   }

   async function generateExpensesReport(fiscalYearId, startDate, endDate) {
      let url = 'expense/all?limit=1000';
      if (fiscalYearId) url += `&fiscal_year_id=${fiscalYearId}`;
      if (startDate) url += `&start_date=${startDate}`;
      if (endDate) url += `&end_date=${endDate}`;

      const response = await api.get(url);
      const expenses = response?.data?.data || response?.data || [];

      const total = expenses.reduce((sum, e) => sum + parseFloat(e.ExpenseAmount), 0);
      const approved = expenses.filter(e => e.ExpenseStatus === 'Approved');
      const approvedTotal = approved.reduce((sum, e) => sum + parseFloat(e.ExpenseAmount), 0);

      return `
         <div class="mb-4">
            <h4>Summary</h4>
            <p><strong>Total Expenses:</strong> ${formatCurrencyLocale(total)}</p>
            <p><strong>Total Count:</strong> ${expenses.length}</p>
            <p><strong>Approved:</strong> ${approved.length} (${formatCurrencyLocale(approvedTotal)})</p>
            <p><strong>Pending:</strong> ${expenses.filter(e => e.ExpenseStatus === 'Pending Approval').length}</p>
         </div>
      `;
   }

   async function generateBudgetReport(fiscalYearId) {
      let url = 'budget/all?limit=1000';
      if (fiscalYearId) url += `&fiscal_year_id=${fiscalYearId}`;

      const response = await api.get(url);
      const budgets = response?.data?.data || response?.data || [];

      const total = budgets.reduce((sum, b) => sum + parseFloat(b.TotalAmount), 0);
      const approved = budgets.filter(b => b.BudgetStatus === 'Approved');

      return `
         <div class="mb-4">
            <h4>Summary</h4>
            <p><strong>Total Budgets:</strong> ${formatCurrencyLocale(total)}</p>
            <p><strong>Total Count:</strong> ${budgets.length}</p>
            <p><strong>Approved:</strong> ${approved.length}</p>
         </div>
      `;
   }

   async function generateSummaryReport(fiscalYearId, startDate, endDate) {
      const [contribRes, pledgesRes, expensesRes] = await Promise.all([
         api.get('contribution/stats'),
         api.get('pledge/all?limit=1000'),
         api.get('expense/all?limit=1000')
      ]);

      const stats = contribRes;
      const pledges = pledgesRes?.data?.data || pledgesRes?.data || [];
      const expenses = expensesRes?.data?.data || expensesRes?.data || [];

      const totalPledges = pledges.reduce((sum, p) => sum + parseFloat(p.PledgeAmount), 0);
      const totalExpenses = expenses.reduce((sum, e) => sum + parseFloat(e.ExpenseAmount), 0);

      return `
         <div class="row">
            <div class="col-md-4 mb-3">
               <div class="card bg-success bg-opacity-25">
                  <div class="card-body">
                     <h6 class="text-muted">Total Contributions</h6>
                     <h3>${formatCurrencyLocale(stats.total_amount)}</h3>
                  </div>
               </div>
            </div>
            <div class="col-md-4 mb-3">
               <div class="card bg-warning bg-opacity-25">
                  <div class="card-body">
                     <h6 class="text-muted">Total Pledges</h6>
                     <h3>${formatCurrencyLocale(totalPledges)}</h3>
                  </div>
               </div>
            </div>
            <div class="col-md-4 mb-3">
               <div class="card bg-danger bg-opacity-25">
                  <div class="card-body">
                     <h6 class="text-muted">Total Expenses</h6>
                     <h3>${formatCurrencyLocale(totalExpenses)}</h3>
                  </div>
               </div>
            </div>
         </div>
         <div class="card bg-primary bg-opacity-25 mt-3">
            <div class="card-body">
               <h6 class="text-muted">Net Position</h6>
               <h3>${formatCurrencyLocale(stats.total_amount - totalExpenses)}</h3>
            </div>
         </div>
      `;
   }

   function exportReport(format) {
      Alerts.info(`Export to ${format.toUpperCase()} coming soon`);
   }
</script>

<?php require_once '../includes/footer.php'; ?>