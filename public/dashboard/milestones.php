<?php
$pageTitle = 'Member Milestones';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1"><i class="bi bi-trophy me-2"></i>Member Milestones</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Milestones</li>
            </ol>
         </nav>
      </div>
      <div class="d-flex align-items-center gap-2">
         <div class="d-flex align-items-center">
            <label class="form-label mb-0 me-2 text-muted small">Year:</label>
            <select class="form-select form-select-sm" id="statsYear" style="width: 120px;">
               <option value="">Loading...</option>
            </select>
         </div>
         <button class="btn btn-outline-secondary" id="manageMilestoneTypesBtn" data-permission="manage_milestone_types">
            <i class="bi bi-tags me-1"></i>Milestone Types
         </button>
         <button class="btn btn-primary" id="addMilestoneBtn" data-permission="manage_milestones">
            <i class="bi bi-plus-circle me-2"></i>Record Milestone
         </button>
      </div>
   </div>

   <!-- Stats Cards Row 1 -->
   <div class="row mb-3" id="statsCardsRow1">
      <div class="col-12 text-center py-4">
         <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
      </div>
   </div>

   <!-- Stats Cards Row 2 (By Type) -->
   <div class="row mb-4" id="statsCardsRow2"></div>

   <!-- Charts Row -->
   <div class="row mb-4">
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>By Milestone Type</h6>
            </div>
            <div class="card-body"><canvas id="byTypeChart" height="200"></canvas></div>
         </div>
      </div>
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Monthly Trend</h6>
            </div>
            <div class="card-body"><canvas id="monthlyTrendChart" height="200"></canvas></div>
         </div>
      </div>
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Milestones</h6>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                     <thead class="table-light">
                        <tr>
                           <th>Member</th>
                           <th>Type</th>
                           <th>Date</th>
                        </tr>
                     </thead>
                     <tbody id="recentMilestonesBody">
                        <tr>
                           <td colspan="3" class="text-center text-muted py-3">Loading...</td>
                        </tr>
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Filters Row -->
   <div class="row mb-4">
      <div class="col-12">
         <div class="card">
            <div class="card-body py-2">
               <div class="row g-2 align-items-end">
                  <div class="col-md-2">
                     <label class="form-label small mb-1">Milestone Type</label>
                     <select class="form-select form-select-sm" id="filterType">
                        <option value="">All Types</option>
                     </select>
                  </div>
                  <div class="col-md-2">
                     <label class="form-label small mb-1">Start Date</label>
                     <input type="date" class="form-control form-control-sm" id="filterStartDate">
                  </div>
                  <div class="col-md-2">
                     <label class="form-label small mb-1">End Date</label>
                     <input type="date" class="form-control form-control-sm" id="filterEndDate">
                  </div>
                  <div class="col-md-3">
                     <label class="form-label small mb-1">Search</label>
                     <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Member name...">
                  </div>
                  <div class="col-md-1">
                     <button class="btn btn-primary btn-sm w-100" id="applyFiltersBtn"><i class="bi bi-search"></i></button>
                  </div>
                  <div class="col-md-2">
                     <button class="btn btn-outline-secondary btn-sm w-100" id="clearFiltersBtn"><i class="bi bi-x-circle me-1"></i>Clear</button>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Milestones Table -->
   <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h5 class="mb-0"><i class="bi bi-table me-2"></i>All Milestones <span class="badge bg-primary ms-2" id="totalMilestonesCount">0</span></h5>
            </div>
            <div class="col-md-6 text-end">
               <button class="btn btn-sm btn-outline-secondary" id="refreshGrid" title="Refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
         </div>
      </div>
      <div class="card-body">
         <div id="milestonesTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Milestone Modal -->
<div class="modal fade" id="milestoneModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header bg-primary bg-opacity-10 border-0">
            <h5 class="modal-title" id="milestoneModalTitle"><i class="bi bi-trophy me-2"></i>Record Milestone</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body px-4">
            <form id="milestoneForm">
               <input type="hidden" id="milestoneId">
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Member <span class="text-danger">*</span></label>
                     <select class="form-select" id="memberId" required>
                        <option value="">Select Member</option>
                     </select>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Milestone Type <span class="text-danger">*</span></label>
                     <select class="form-select" id="milestoneTypeId" required>
                        <option value="">Select Type</option>
                     </select>
                  </div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Milestone Date <span class="text-danger">*</span></label>
                     <input type="date" class="form-control" id="milestoneDate" required>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Location</label>
                     <input type="text" class="form-control" id="location" maxlength="200" placeholder="e.g., Main Sanctuary">
                  </div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Officiating Pastor</label>
                     <input type="text" class="form-control" id="officiatingPastor" maxlength="150" placeholder="Pastor's name">
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Certificate Number</label>
                     <input type="text" class="form-control" id="certificateNumber" maxlength="100" placeholder="Certificate/Reference number">
                  </div>
               </div>
               <div class="mb-3">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" id="notes" rows="2" maxlength="1000" placeholder="Additional details..."></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveMilestoneBtn"><i class="bi bi-check-circle me-1"></i>Save Milestone</button>
         </div>
      </div>
   </div>
</div>

<!-- View Milestone Modal -->
<div class="modal fade" id="viewMilestoneModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header border-0 p-0">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewMilestoneContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading...</p>
            </div>
         </div>
         <div class="modal-footer border-0" id="viewMilestoneFooter">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<!-- Manage Milestone Types Modal -->
<div class="modal fade" id="milestoneTypesModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Manage Milestone Types</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="row mb-3 g-2">
               <div class="col-md-3">
                  <input type="text" class="form-control" id="newTypeName" placeholder="Type name *">
               </div>
               <div class="col-md-3">
                  <input type="text" class="form-control" id="newTypeDesc" placeholder="Description">
               </div>
               <div class="col-md-2">
                  <input type="text" class="form-control" id="newTypeIcon" placeholder="Icon (e.g., heart)">
               </div>
               <div class="col-md-2">
                  <select class="form-select" id="newTypeColor">
                     <option value="primary">Blue</option>
                     <option value="success">Green</option>
                     <option value="warning">Yellow</option>
                     <option value="danger">Red</option>
                     <option value="info">Cyan</option>
                     <option value="secondary">Gray</option>
                  </select>
               </div>
               <div class="col-md-2">
                  <button class="btn btn-primary w-100" id="addMilestoneTypeBtn"><i class="bi bi-plus"></i> Add</button>
               </div>
            </div>
            <div class="table-responsive">
               <table class="table table-hover" id="milestoneTypesTable">
                  <thead class="table-light">
                     <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Icon</th>
                        <th>Color</th>
                        <th width="100">Actions</th>
                     </tr>
                  </thead>
                  <tbody id="milestoneTypesBody">
                     <tr>
                        <td colspan="5" class="text-center py-3">Loading...</td>
                     </tr>
                  </tbody>
               </table>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<!-- Edit Milestone Type Modal -->
<div class="modal fade" id="editMilestoneTypeModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Milestone Type</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <input type="hidden" id="editTypeId">
            <div class="mb-3">
               <label class="form-label">Name <span class="text-danger">*</span></label>
               <input type="text" class="form-control" id="editTypeName" required>
            </div>
            <div class="mb-3">
               <label class="form-label">Description</label>
               <input type="text" class="form-control" id="editTypeDesc">
            </div>
            <div class="row g-3">
               <div class="col-md-6">
                  <label class="form-label">Icon</label>
                  <input type="text" class="form-control" id="editTypeIcon" placeholder="e.g., heart">
               </div>
               <div class="col-md-6">
                  <label class="form-label">Color</label>
                  <select class="form-select" id="editTypeColor">
                     <option value="primary">Blue</option>
                     <option value="success">Green</option>
                     <option value="warning">Yellow</option>
                     <option value="danger">Red</option>
                     <option value="info">Cyan</option>
                     <option value="secondary">Gray</option>
                  </select>
               </div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveMilestoneTypeBtn">Save</button>
         </div>
      </div>
   </div>
</div>

<!-- Print Certificate Modal -->
<div class="modal fade" id="printCertificateModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-printer me-2"></i>Milestone Certificate</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="printCertificateContent"></div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
         </div>
      </div>
   </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/core/qmgrid-helper.js"></script>
<script>
   (function() {
      'use strict';

      const State = {
         milestonesTable: null,
         currentMilestoneId: null,
         currentMilestoneData: null,
         membersData: [],
         milestoneTypesData: [],
         memberChoices: null,
         milestoneTypeChoices: null,
         selectedYear: null,
         byTypeChart: null,
         monthlyTrendChart: null,
         currentFilters: {},
         editingTypeId: null
      };

      document.addEventListener('DOMContentLoaded', async () => {
         if (!Auth.requireAuth()) return;
         await initPage();
      });

      async function initPage() {
         await loadYearsForStats();
         await loadMilestoneTypes();
         initTable();
         initEventListeners();
         loadStats();
         document.getElementById('milestoneDate').valueAsDate = new Date();
      }

      async function loadYearsForStats() {
         const select = document.getElementById('statsYear');
         select.innerHTML = '';
         const currentYear = new Date().getFullYear();
         State.selectedYear = currentYear;
         for (let y = currentYear; y >= currentYear - 10; y--) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            if (y === currentYear) opt.selected = true;
            select.appendChild(opt);
         }
         select.addEventListener('change', (e) => {
            State.selectedYear = e.target.value ? parseInt(e.target.value) : null;
            loadStats();
            reloadTable();
         });
      }

      async function loadMilestoneTypes() {
         try {
            const response = await api.get('milestone/types?active=1');
            State.milestoneTypesData = Array.isArray(response) ? response : (response?.data || []);
            const filterSelect = document.getElementById('filterType');
            filterSelect.innerHTML = '<option value="">All Types</option>';
            State.milestoneTypesData.forEach(t => {
               const opt = document.createElement('option');
               opt.value = t.MilestoneTypeID;
               opt.textContent = t.MilestoneTypeName;
               filterSelect.appendChild(opt);
            });
         } catch (error) {
            console.error('Load milestone types error:', error);
         }
      }

      function initTable() {
         const url = buildTableUrl();
         State.milestonesTable = QMGridHelper.init('#milestonesTable', {
            url: url,
            pageSize: 25,
            selectable: false,
            exportable: true,
            columns: [{
                  key: 'MemberName',
                  title: 'Member',
                  render: (v, row) => '<div class="fw-medium">' + (v || '-') + '</div>'
               },
               {
                  key: 'MilestoneTypeName',
                  title: 'Type',
                  render: (v, row) => '<span class="badge bg-' + (row.Color || 'primary') + '"><i class="bi bi-' + (row.Icon || 'trophy') + ' me-1"></i>' + v + '</span>'
               },
               {
                  key: 'MilestoneDate',
                  title: 'Date',
                  render: (v) => QMGridHelper.formatDate(v, 'medium')
               },
               {
                  key: 'Location',
                  title: 'Location',
                  render: (v) => v || '-'
               },
               {
                  key: 'OfficiatingPastor',
                  title: 'Pastor',
                  render: (v) => v || '-'
               },
               {
                  key: 'CertificateNumber',
                  title: 'Certificate #',
                  render: (v) => v ? '<code>' + v + '</code>' : '-'
               },
               {
                  key: 'MilestoneID',
                  title: 'Actions',
                  width: '140px',
                  sortable: false,
                  exportable: false,
                  render: (v, row) => '<div class="btn-group btn-group-sm"><button class="btn btn-primary btn-sm" onclick="viewMilestone(' + v + ')" title="View"><i class="bi bi-eye"></i></button><button class="btn btn-info btn-sm" onclick="printCertificate(' + v + ')" title="Print"><i class="bi bi-printer"></i></button><button class="btn btn-warning btn-sm" onclick="editMilestone(' + v + ')" title="Edit"><i class="bi bi-pencil"></i></button><button class="btn btn-danger btn-sm" onclick="deleteMilestone(' + v + ')" title="Delete"><i class="bi bi-trash"></i></button></div>'
               }
            ],
            onDataLoaded: (data) => {
               document.getElementById('totalMilestonesCount').textContent = data.pagination?.total || data.total || 0;
            }
         });
      }

      function buildTableUrl() {
         let url = Config.API_BASE_URL + '/milestone/all';
         const params = new URLSearchParams();
         if (State.selectedYear) params.append('year', State.selectedYear);
         if (State.currentFilters.milestone_type_id) params.append('milestone_type_id', State.currentFilters.milestone_type_id);
         if (State.currentFilters.start_date) params.append('start_date', State.currentFilters.start_date);
         if (State.currentFilters.end_date) params.append('end_date', State.currentFilters.end_date);
         if (State.currentFilters.search) params.append('search', State.currentFilters.search);
         if (params.toString()) url += '?' + params.toString();
         return url;
      }

      function reloadTable() {
         if (State.milestonesTable) State.milestonesTable.destroy();
         initTable();
      }

      async function loadStats() {
         try {
            let url = 'milestone/stats';
            if (State.selectedYear) url += '?year=' + State.selectedYear;
            const stats = await api.get(url);
            renderStatsCards(stats);
            renderRecentMilestones(stats.recent || []);
            renderByTypeChart(stats.by_type || []);
            renderMonthlyTrendChart(stats.monthly_trend || []);
         } catch (error) {
            console.error('Load stats error:', error);
            renderStatsCards({});
            renderRecentMilestones([]);
         }
      }

      function renderStatsCards(stats) {
         const row1Cards = [{
               title: 'Total Milestones',
               value: (stats.total_count || 0).toLocaleString(),
               subtitle: 'All time',
               icon: 'trophy',
               color: 'primary'
            },
            {
               title: 'Year ' + (stats.current_year || State.selectedYear),
               value: (stats.year_count || 0).toLocaleString(),
               subtitle: 'This year',
               icon: 'calendar-check',
               color: 'success'
            },
            {
               title: 'This Month',
               value: (stats.month_count || 0).toLocaleString(),
               subtitle: 'Current month',
               icon: 'calendar-event',
               color: 'info'
            },
            {
               title: 'Milestone Types',
               value: (stats.by_type?.length || 0).toLocaleString(),
               subtitle: 'Active types',
               icon: 'tags',
               color: 'warning'
            }
         ];
         document.getElementById('statsCardsRow1').innerHTML = row1Cards.map(renderStatCard).join('');
         const byType = stats.by_type || [];
         const row2Cards = byType.slice(0, 4).map(t => ({
            title: t.MilestoneTypeName,
            value: (t.count || 0).toLocaleString(),
            subtitle: 'milestones',
            icon: t.Icon || 'trophy',
            color: t.Color || 'secondary'
         }));
         document.getElementById('statsCardsRow2').innerHTML = row2Cards.map(renderStatCard).join('');
      }

      function renderStatCard(card) {
         return '<div class="col-lg-3 col-md-6"><div class="card stat-card bg-' + card.color + ' bg-opacity-10 mb-3"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted mb-1 small">' + card.title + '</p><h4 class="mb-0">' + card.value + '</h4><small class="text-muted">' + card.subtitle + '</small></div><div class="stat-icon bg-' + card.color + ' text-white rounded-circle"><i class="bi bi-' + card.icon + '"></i></div></div></div></div></div>';
      }

      function renderRecentMilestones(recent) {
         const tbody = document.getElementById('recentMilestonesBody');
         if (recent.length > 0) {
            tbody.innerHTML = recent.slice(0, 5).map(m => '<tr><td><div class="fw-medium">' + m.MbrFirstName + ' ' + m.MbrFamilyName + '</div></td><td><span class="badge bg-' + (m.Color || 'primary') + '">' + m.MilestoneTypeName + '</span></td><td class="text-muted small">' + new Date(m.MilestoneDate).toLocaleDateString() + '</td></tr>').join('');
         } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No recent milestones</td></tr>';
         }
      }

      function renderByTypeChart(byType) {
         const ctx = document.getElementById('byTypeChart').getContext('2d');
         if (State.byTypeChart) State.byTypeChart.destroy();
         if (!byType.length) {
            ctx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
         }
         const colorMap = {
            primary: '#0d6efd',
            success: '#198754',
            warning: '#ffc107',
            danger: '#dc3545',
            info: '#0dcaf0',
            secondary: '#6c757d'
         };
         const colors = byType.map(t => colorMap[t.Color] || '#6c757d');
         State.byTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
               labels: byType.map(t => t.MilestoneTypeName),
               datasets: [{
                  data: byType.map(t => parseInt(t.count)),
                  backgroundColor: colors,
                  borderWidth: 0
               }]
            },
            options: {
               responsive: true,
               maintainAspectRatio: false,
               plugins: {
                  legend: {
                     position: 'bottom',
                     labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: {
                           size: 11
                        }
                     }
                  }
               }
            }
         });
      }

      function renderMonthlyTrendChart(monthlyTrend) {
         const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
         if (State.monthlyTrendChart) State.monthlyTrendChart.destroy();
         if (!monthlyTrend.length) {
            ctx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
         }
         State.monthlyTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
               labels: monthlyTrend.map(m => m.month_label),
               datasets: [{
                  label: 'Milestones',
                  data: monthlyTrend.map(m => parseInt(m.count)),
                  borderColor: '#0d6efd',
                  backgroundColor: 'rgba(13, 110, 253, 0.1)',
                  fill: true,
                  tension: 0.3,
                  pointRadius: 3,
                  pointHoverRadius: 5
               }]
            },
            options: {
               responsive: true,
               maintainAspectRatio: false,
               plugins: {
                  legend: {
                     display: false
                  }
               },
               scales: {
                  y: {
                     beginAtZero: true,
                     ticks: {
                        stepSize: 1
                     }
                  }
               }
            }
         });
      }


      function initEventListeners() {
         document.getElementById('addMilestoneBtn').addEventListener('click', () => {
            if (!Auth.hasPermission('manage_milestones')) {
               Alerts.error('You do not have permission to record milestones');
               return;
            }
            openMilestoneModal();
         });
         document.getElementById('saveMilestoneBtn').addEventListener('click', saveMilestone);
         document.getElementById('refreshGrid').addEventListener('click', () => {
            loadStats();
            reloadTable();
         });
         document.getElementById('applyFiltersBtn').addEventListener('click', applyFilters);
         document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
         document.getElementById('manageMilestoneTypesBtn').addEventListener('click', () => {
            if (!Auth.hasPermission('manage_milestone_types')) {
               Alerts.error('You do not have permission to manage milestone types');
               return;
            }
            loadMilestoneTypesForManagement();
            new bootstrap.Modal(document.getElementById('milestoneTypesModal')).show();
         });
         document.getElementById('addMilestoneTypeBtn').addEventListener('click', addMilestoneType);
         document.getElementById('saveMilestoneTypeBtn').addEventListener('click', updateMilestoneType);
      }

      function applyFilters() {
         State.currentFilters = {
            milestone_type_id: document.getElementById('filterType').value,
            start_date: document.getElementById('filterStartDate').value,
            end_date: document.getElementById('filterEndDate').value,
            search: document.getElementById('filterSearch').value.trim()
         };
         reloadTable();
      }

      function clearFilters() {
         document.getElementById('filterType').value = '';
         document.getElementById('filterStartDate').value = '';
         document.getElementById('filterEndDate').value = '';
         document.getElementById('filterSearch').value = '';
         State.currentFilters = {};
         reloadTable();
      }

      async function openMilestoneModal(milestoneId = null) {
         const isEdit = !!milestoneId;
         State.currentMilestoneId = milestoneId;
         document.getElementById('milestoneForm').reset();
         document.getElementById('milestoneId').value = '';
         document.getElementById('milestoneModalTitle').innerHTML = '<i class="bi bi-trophy me-2"></i>' + (isEdit ? 'Edit' : 'Record') + ' Milestone';
         document.getElementById('milestoneDate').valueAsDate = new Date();
         await loadMembersForModal();
         await loadMilestoneTypesForModal();
         const modal = new bootstrap.Modal(document.getElementById('milestoneModal'));
         modal.show();
         if (isEdit) await loadMilestoneForEdit(milestoneId);
      }

      async function loadMembersForModal() {
         try {
            const response = await api.get('member/all?limit=1000');
            State.membersData = response?.data?.data || response?.data || [];
            if (Array.isArray(response)) State.membersData = response;
            const select = document.getElementById('memberId');
            select.innerHTML = '<option value="">Select Member</option>';
            State.membersData.forEach(m => {
               const opt = document.createElement('option');
               opt.value = m.MbrID;
               opt.textContent = m.MbrFirstName + ' ' + m.MbrFamilyName;
               select.appendChild(opt);
            });
            if (State.memberChoices) State.memberChoices.destroy();
            State.memberChoices = new Choices(select, {
               searchEnabled: true,
               searchPlaceholderValue: 'Search members...',
               itemSelectText: '',
               allowHTML: true,
               shouldSort: false
            });
         } catch (error) {
            console.error('Load members error:', error);
         }
      }

      async function loadMilestoneTypesForModal() {
         try {
            const response = await api.get('milestone/types?active=1');
            const types = Array.isArray(response) ? response : (response?.data || []);
            const select = document.getElementById('milestoneTypeId');
            select.innerHTML = '<option value="">Select Type</option>';
            types.forEach(t => {
               const opt = document.createElement('option');
               opt.value = t.MilestoneTypeID;
               opt.textContent = t.MilestoneTypeName;
               select.appendChild(opt);
            });
            if (State.milestoneTypeChoices) State.milestoneTypeChoices.destroy();
            State.milestoneTypeChoices = new Choices(select, {
               searchEnabled: true,
               searchPlaceholderValue: 'Search types...',
               itemSelectText: '',
               allowHTML: true,
               shouldSort: false
            });
         } catch (error) {
            console.error('Load milestone types error:', error);
         }
      }

      async function loadMilestoneForEdit(milestoneId) {
         try {
            const milestone = await api.get('milestone/view/' + milestoneId);
            State.currentMilestoneData = milestone;
            document.getElementById('milestoneId').value = milestone.MilestoneID;
            document.getElementById('milestoneDate').value = milestone.MilestoneDate;
            document.getElementById('location').value = milestone.Location || '';
            document.getElementById('officiatingPastor').value = milestone.OfficiatingPastor || '';
            document.getElementById('certificateNumber').value = milestone.CertificateNumber || '';
            document.getElementById('notes').value = milestone.Notes || '';
            if (State.memberChoices) State.memberChoices.setChoiceByValue(milestone.MbrID.toString());
            if (State.milestoneTypeChoices) State.milestoneTypeChoices.setChoiceByValue(milestone.MilestoneTypeID.toString());
         } catch (error) {
            console.error('Load milestone error:', error);
            Alerts.error('Failed to load milestone details');
         }
      }

      async function saveMilestone() {
         const memberId = document.getElementById('memberId').value;
         const typeId = document.getElementById('milestoneTypeId').value;
         const date = document.getElementById('milestoneDate').value;
         if (!memberId || !typeId || !date) {
            Alerts.warning('Please fill all required fields');
            return;
         }
         const payload = {
            member_id: parseInt(memberId),
            milestone_type_id: parseInt(typeId),
            milestone_date: date,
            location: document.getElementById('location').value.trim() || null,
            officiating_pastor: document.getElementById('officiatingPastor').value.trim() || null,
            certificate_number: document.getElementById('certificateNumber').value.trim() || null,
            notes: document.getElementById('notes').value.trim() || null
         };
         const isEdit = !!State.currentMilestoneId;
         const btn = document.getElementById('saveMilestoneBtn');
         btn.disabled = true;
         btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
         try {
            if (isEdit) {
               await api.put('milestone/update/' + State.currentMilestoneId, payload);
               Alerts.success('Milestone updated successfully');
            } else {
               await api.post('milestone/create', payload);
               Alerts.success('Milestone recorded successfully');
            }
            bootstrap.Modal.getInstance(document.getElementById('milestoneModal')).hide();
            loadStats();
            reloadTable();
            loadMilestoneTypes();
         } catch (error) {
            console.error('Save milestone error:', error);
            Alerts.error(error.message || 'Failed to save milestone');
         } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Milestone';
         }
      }

      window.viewMilestone = async function(milestoneId) {
         try {
            const milestone = await api.get('milestone/view/' + milestoneId);
            State.currentMilestoneId = milestoneId;
            State.currentMilestoneData = milestone;
            const color = milestone.Color || 'primary';
            const icon = milestone.Icon || 'trophy';
            let html = '<div class="bg-' + color + ' bg-opacity-10 p-4 text-center">';
            html += '<div class="bg-' + color + ' text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;"><i class="bi bi-' + icon + ' fs-1"></i></div>';
            html += '<h4 class="mb-1">' + milestone.MilestoneTypeName + '</h4>';
            html += '<p class="text-muted mb-0">' + milestone.MemberName + '</p></div>';
            html += '<div class="p-4"><div class="row g-3">';
            html += '<div class="col-md-6"><div class="text-muted small">Milestone Date</div><div class="fw-medium">' + new Date(milestone.MilestoneDate).toLocaleDateString('en-US', {
               year: 'numeric',
               month: 'long',
               day: 'numeric'
            }) + '</div></div>';
            html += '<div class="col-md-6"><div class="text-muted small">Location</div><div class="fw-medium">' + (milestone.Location || '-') + '</div></div>';
            html += '<div class="col-md-6"><div class="text-muted small">Officiating Pastor</div><div class="fw-medium">' + (milestone.OfficiatingPastor || '-') + '</div></div>';
            html += '<div class="col-md-6"><div class="text-muted small">Certificate Number</div><div class="fw-medium">' + (milestone.CertificateNumber ? '<code>' + milestone.CertificateNumber + '</code>' : '-') + '</div></div>';
            if (milestone.Notes) html += '<div class="col-12"><div class="text-muted small">Notes</div><div>' + milestone.Notes + '</div></div>';
            html += '<div class="col-md-6"><div class="text-muted small">Recorded By</div><div class="fw-medium">' + (milestone.RecorderName || '-') + '</div></div>';
            html += '<div class="col-md-6"><div class="text-muted small">Recorded At</div><div class="fw-medium">' + (milestone.RecordedAt ? new Date(milestone.RecordedAt).toLocaleString() : '-') + '</div></div>';
            html += '</div></div>';
            document.getElementById('viewMilestoneContent').innerHTML = html;
            document.getElementById('viewMilestoneFooter').innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-info" onclick="printCertificate(' + milestoneId + ')"><i class="bi bi-printer me-1"></i>Print Certificate</button><button type="button" class="btn btn-warning" onclick="editMilestoneFromView()"><i class="bi bi-pencil me-1"></i>Edit</button>';
            new bootstrap.Modal(document.getElementById('viewMilestoneModal')).show();
         } catch (error) {
            console.error('View milestone error:', error);
            Alerts.error('Failed to load milestone details');
         }
      };

      window.editMilestoneFromView = function() {
         bootstrap.Modal.getInstance(document.getElementById('viewMilestoneModal')).hide();
         editMilestone(State.currentMilestoneId);
      };

      window.editMilestone = function(milestoneId) {
         if (!Auth.hasPermission('manage_milestones')) {
            Alerts.error('You do not have permission to edit milestones');
            return;
         }
         openMilestoneModal(milestoneId);
      };

      window.deleteMilestone = async function(milestoneId) {
         if (!Auth.hasPermission('manage_milestones')) {
            Alerts.error('You do not have permission to delete milestones');
            return;
         }
         const confirmed = await Alerts.confirm({
            title: 'Delete Milestone',
            text: 'Are you sure you want to delete this milestone? This action cannot be undone.',
            icon: 'warning',
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc3545'
         });
         if (!confirmed) return;
         try {
            await api.delete('milestone/delete/' + milestoneId);
            Alerts.success('Milestone deleted successfully');
            loadStats();
            reloadTable();
         } catch (error) {
            console.error('Delete milestone error:', error);
            Alerts.error(error.message || 'Failed to delete milestone');
         }
      };

      window.printCertificate = async function(milestoneId) {
         try {
            let milestone = State.currentMilestoneData;
            if (!milestone || milestone.MilestoneID !== milestoneId) milestone = await api.get('milestone/view/' + milestoneId);
            const churchName = Config.getSetting('church_name', 'Church Name');
            const color = milestone.Color || 'primary';
            let html = '<div class="certificate-container p-4" style="border: 3px double #333; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">';
            html += '<div class="text-center mb-4"><h2 class="mb-1" style="font-family: serif;">' + churchName + '</h2><p class="text-muted mb-0">Certificate of ' + milestone.MilestoneTypeName + '</p></div>';
            html += '<hr class="my-4"><div class="text-center mb-4"><p class="mb-2">This is to certify that</p>';
            html += '<h3 class="text-' + color + ' mb-2" style="font-family: serif;">' + milestone.MemberName + '</h3>';
            html += '<p class="mb-0">has achieved the milestone of</p><h4 class="text-uppercase mb-3">' + milestone.MilestoneTypeName + '</h4>';
            html += '<p class="mb-0">on <strong>' + new Date(milestone.MilestoneDate).toLocaleDateString('en-US', {
               year: 'numeric',
               month: 'long',
               day: 'numeric'
            }) + '</strong></p>';
            if (milestone.Location) html += '<p class="text-muted">at ' + milestone.Location + '</p>';
            html += '</div><div class="row mt-5 pt-4"><div class="col-6 text-center"><div class="border-top border-dark pt-2 mx-4"><small class="text-muted">Officiating Pastor</small><div class="fw-medium">' + (milestone.OfficiatingPastor || '_______________') + '</div></div></div>';
            html += '<div class="col-6 text-center"><div class="border-top border-dark pt-2 mx-4"><small class="text-muted">Date</small><div class="fw-medium">' + new Date(milestone.MilestoneDate).toLocaleDateString() + '</div></div></div></div>';
            if (milestone.CertificateNumber) html += '<div class="text-center mt-4"><small class="text-muted">Certificate No: ' + milestone.CertificateNumber + '</small></div>';
            html += '</div>';
            document.getElementById('printCertificateContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('printCertificateModal')).show();
         } catch (error) {
            console.error('Print certificate error:', error);
            Alerts.error('Failed to generate certificate');
         }
      };


      // ========== MILESTONE TYPE MANAGEMENT ==========
      async function loadMilestoneTypesForManagement() {
         try {
            const response = await api.get('milestone/types');
            const types = Array.isArray(response) ? response : (response?.data || []);
            const tbody = document.getElementById('milestoneTypesBody');
            if (types.length === 0) {
               tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No milestone types found</td></tr>';
               return;
            }
            tbody.innerHTML = types.map(t => '<tr><td class="fw-medium">' + t.MilestoneTypeName + '</td><td class="text-muted">' + (t.Description || '-') + '</td><td><i class="bi bi-' + (t.Icon || 'trophy') + '"></i> ' + (t.Icon || 'trophy') + '</td><td><span class="badge bg-' + (t.Color || 'primary') + '">' + (t.Color || 'primary') + '</span></td><td><div class="btn-group btn-group-sm"><button class="btn btn-warning btn-sm" onclick="editMilestoneType(' + t.MilestoneTypeID + ')" title="Edit"><i class="bi bi-pencil"></i></button><button class="btn btn-danger btn-sm" onclick="deleteMilestoneType(' + t.MilestoneTypeID + ')" title="Delete"><i class="bi bi-trash"></i></button></div></td></tr>').join('');
         } catch (error) {
            console.error('Load milestone types error:', error);
            document.getElementById('milestoneTypesBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Failed to load types</td></tr>';
         }
      }

      async function addMilestoneType() {
         const name = document.getElementById('newTypeName').value.trim();
         if (!name) {
            Alerts.warning('Please enter a type name');
            return;
         }
         const payload = {
            name: name,
            description: document.getElementById('newTypeDesc').value.trim() || null,
            icon: document.getElementById('newTypeIcon').value.trim() || 'trophy',
            color: document.getElementById('newTypeColor').value || 'primary'
         };
         try {
            await api.post('milestone/type/create', payload);
            Alerts.success('Milestone type added successfully');
            document.getElementById('newTypeName').value = '';
            document.getElementById('newTypeDesc').value = '';
            document.getElementById('newTypeIcon').value = '';
            document.getElementById('newTypeColor').value = 'primary';
            loadMilestoneTypesForManagement();
            loadMilestoneTypes();
         } catch (error) {
            console.error('Add milestone type error:', error);
            Alerts.error(error.message || 'Failed to add milestone type');
         }
      }

      window.editMilestoneType = async function(typeId) {
         try {
            const response = await api.get('milestone/types');
            const types = Array.isArray(response) ? response : (response?.data || []);
            const type = types.find(t => t.MilestoneTypeID === typeId);
            if (!type) {
               Alerts.error('Milestone type not found');
               return;
            }
            State.editingTypeId = typeId;
            document.getElementById('editTypeId').value = typeId;
            document.getElementById('editTypeName').value = type.MilestoneTypeName;
            document.getElementById('editTypeDesc').value = type.Description || '';
            document.getElementById('editTypeIcon').value = type.Icon || '';
            document.getElementById('editTypeColor').value = type.Color || 'primary';
            new bootstrap.Modal(document.getElementById('editMilestoneTypeModal')).show();
         } catch (error) {
            console.error('Edit milestone type error:', error);
            Alerts.error('Failed to load milestone type');
         }
      };

      async function updateMilestoneType() {
         const typeId = State.editingTypeId;
         const name = document.getElementById('editTypeName').value.trim();
         if (!name) {
            Alerts.warning('Please enter a type name');
            return;
         }
         const payload = {
            name: name,
            description: document.getElementById('editTypeDesc').value.trim() || null,
            icon: document.getElementById('editTypeIcon').value.trim() || 'trophy',
            color: document.getElementById('editTypeColor').value || 'primary'
         };
         try {
            await api.put('milestone/type/update/' + typeId, payload);
            Alerts.success('Milestone type updated successfully');
            bootstrap.Modal.getInstance(document.getElementById('editMilestoneTypeModal')).hide();
            loadMilestoneTypesForManagement();
            loadMilestoneTypes();
         } catch (error) {
            console.error('Update milestone type error:', error);
            Alerts.error(error.message || 'Failed to update milestone type');
         }
      }

      window.deleteMilestoneType = async function(typeId) {
         const confirmed = await Alerts.confirm({
            title: 'Delete Milestone Type',
            text: 'Are you sure you want to delete this milestone type? This will only work if no milestones are using this type.',
            icon: 'warning',
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc3545'
         });
         if (!confirmed) return;
         try {
            await api.delete('milestone/type/delete/' + typeId);
            Alerts.success('Milestone type deleted successfully');
            loadMilestoneTypesForManagement();
            loadMilestoneTypes();
         } catch (error) {
            console.error('Delete milestone type error:', error);
            Alerts.error(error.message || 'Failed to delete milestone type');
         }
      };

   })();
</script>

<style>
   @media print {
      body * {
         visibility: hidden;
      }

      #printCertificateContent,
      #printCertificateContent * {
         visibility: visible;
      }

      #printCertificateContent {
         position: absolute;
         left: 0;
         top: 0;
         width: 100%;
      }

      .modal-header,
      .modal-footer {
         display: none !important;
      }
   }

   .stat-icon {
      width: 48px;
      height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
   }

   .stat-icon i {
      font-size: 1.25rem;
   }
</style>

<?php require_once '../includes/footer.php'; ?>