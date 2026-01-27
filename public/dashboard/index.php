<?php $pageTitle = 'Dashboard'; ?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="page-header">
   <div class="row align-items-center">
      <div class="col">
         <h1>Dashboard</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
               <li class="breadcrumb-item active">Dashboard</li>
            </ol>
         </nav>
      </div>
      <div class="col-auto">
         <!-- <button class="btn btn-primary" onclick="location.reload()"> -->
         <button class="btn btn-primary" onclick="loadDashboard()">
            <i class="bi bi-arrow-clockwise me-2"></i>Refresh
         </button>
      </div>
   </div>
</div>

<!-- Stats Cards -->
<div class="row" id="statsCards">
   <!-- Will be populated dynamically -->
   <div class="col-12 text-center py-5">
      <div class="spinner-border text-primary" role="status">
         <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-2">Loading dashboard data...</p>
   </div>
</div>

<!-- Charts Row -->
<div class="row mt-4">
   <!-- Attendance Chart -->
   <div class="col-lg-8 col-md-12">
      <div class="card">
         <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Attendance Trend (Last 4 Sundays)</h5>
            <i class="bi bi-graph-up-arrow text-primary"></i>
         </div>
         <div class="card-body">
            <canvas id="attendanceChart" height="180"></canvas>
         </div>
      </div>
   </div>

   <!-- Financial Overview -->
   <div class="col-lg-4 col-md-12">
      <div class="card">
         <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Financial Overview</h5>
            <i class="bi bi-currency-dollar text-success"></i>
         </div>
         <div class="card-body">
            <canvas id="financeChart" height="180"></canvas>
            <div class="mt-3">
               <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Income:</span>
                  <strong class="text-success" id="totalIncome">-</strong>
               </div>
               <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Expenses:</span>
                  <strong class="text-danger" id="totalExpenses">-</strong>
               </div>
               <hr>
               <div class="d-flex justify-content-between">
                  <span class="fw-bold">Net:</span>
                  <strong id="netBalance">-</strong>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<!-- Upcoming Events & Recent Activity -->
<div class="row mt-4">
   <!-- Upcoming Events -->
   <div class="col-lg-6 col-md-12">
      <div class="card">
         <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Upcoming Events</h5>
            <a href="events.php" class="btn btn-sm btn-outline-primary">View All</a>
         </div>
         <div class="card-body">
            <div id="upcomingEvents">
               <div class="text-center py-3">
                  <div class="spinner-border spinner-border-sm text-primary"></div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Recent Activity -->
   <div class="col-lg-6 col-md-12">
      <div class="card">
         <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Activity</h5>
            <span class="badge bg-primary" id="activityCount">0</span>
         </div>
         <div class="card-body">
            <div id="recentActivity" style="max-height: 400px; overflow-y: auto;">
               <div class="text-center py-3">
                  <div class="spinner-border spinner-border-sm text-primary"></div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<!-- Pending Approvals (if applicable) -->
<div class="row mt-4" id="pendingApprovalsSection" style="display: none;">
   <div class="col-12">
      <div class="card">
         <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pending Approvals</h5>
            <span class="badge bg-warning text-dark" id="pendingCount">0</span>
         </div>
         <div class="card-body">
            <div class="row" id="pendingApprovals">
               <!-- Populated dynamically -->
            </div>
         </div>
      </div>
   </div>
</div>

<script type="module" src="../assets/js/modules/dashboard/index.js"></script>

<?php include '../includes/footer.php'; ?>