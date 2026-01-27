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

<script type="module" src="../assets/js/modules/financial-reports/index.js"></script>

<?php require_once '../includes/footer.php'; ?>