<?php
$pageTitle = 'Pledges Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1"><i class="bi bi-bookmark-heart me-2"></i>Pledges</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Pledges</li>
            </ol>
         </nav>
      </div>
      <div class="d-flex align-items-center gap-2">
         <div class="d-flex align-items-center">
            <label class="form-label mb-0 me-2 text-muted small">Fiscal Year:</label>
            <select class="form-select form-select-sm" id="statsFiscalYear" style="width: 180px;">
               <option value="">Loading...</option>
            </select>
         </div>
         <button class="btn btn-outline-secondary" id="managePledgeTypesBtn" data-permission="manage_pledge_types">
            <i class="bi bi-tags me-1"></i>Pledge Types
         </button>
         <button class="btn btn-primary" id="addPledgeBtn" data-permission="manage_pledges">
            <i class="bi bi-plus-circle me-2"></i>Create Pledge
         </button>
      </div>
   </div>

   <!-- Stats Cards Row 1 -->
   <div class="row mb-3" id="statsCardsRow1">
      <div class="col-12 text-center py-4">
         <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
      </div>
   </div>

   <!-- Stats Cards Row 2 -->
   <div class="row mb-4" id="statsCardsRow2"></div>

   <!-- Charts Row -->
   <div class="row mb-4">
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>By Pledge Type</h6>
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
               <h6 class="mb-0"><i class="bi bi-trophy me-2"></i>Top Pledgers</h6>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                     <thead class="table-light">
                        <tr>
                           <th>Member</th>
                           <th class="text-end">Total Pledged</th>
                        </tr>
                     </thead>
                     <tbody id="topPledgersBody">
                        <tr>
                           <td colspan="2" class="text-center text-muted py-3">Loading...</td>
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
                     <label class="form-label small mb-1">Status</label>
                     <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Fulfilled">Fulfilled</option>
                        <option value="Cancelled">Cancelled</option>
                     </select>
                  </div>
                  <div class="col-md-2">
                     <label class="form-label small mb-1">Pledge Type</label>
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
                  <div class="col-md-2">
                     <button class="btn btn-primary btn-sm w-100" id="applyFiltersBtn"><i class="bi bi-search me-1"></i>Filter</button>
                  </div>
                  <div class="col-md-2">
                     <button class="btn btn-outline-secondary btn-sm w-100" id="clearFiltersBtn"><i class="bi bi-x-circle me-1"></i>Clear</button>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Pledges Table -->
   <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h5 class="mb-0"><i class="bi bi-table me-2"></i>All Pledges <span class="badge bg-primary ms-2" id="totalPledgesCount">0</span></h5>
            </div>
            <div class="col-md-6 text-end">
               <button class="btn btn-sm btn-outline-secondary" id="refreshGrid" title="Refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
         </div>
      </div>
      <div class="card-body">
         <div id="pledgesTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Pledge Modal -->
<div class="modal fade" id="pledgeModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header bg-primary bg-opacity-10 border-0">
            <h5 class="modal-title" id="pledgeModalTitle"><i class="bi bi-bookmark-heart me-2"></i>Create Pledge</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body px-4">
            <form id="pledgeForm">
               <input type="hidden" id="pledgeId">
               <div class="mb-3">
                  <label class="form-label">Member <span class="text-danger">*</span></label>
                  <select class="form-select" id="memberId" required>
                     <option value="">Select Member</option>
                  </select>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Pledge Type <span class="text-danger">*</span></label>
                     <select class="form-select" id="pledgeTypeId" required>
                        <option value="">Select Type</option>
                     </select>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Amount <span class="text-danger">*</span></label>
                     <div class="input-group">
                        <span class="input-group-text" id="currencySymbol">GH₵</span>
                        <input type="number" class="form-control" id="amount" step="0.01" min="0.01" required>
                     </div>
                  </div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Pledge Date <span class="text-danger">*</span></label>
                     <input type="date" class="form-control" id="pledgeDate" required>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Due Date</label>
                     <input type="date" class="form-control" id="dueDate">
                  </div>
               </div>
               <div class="mb-3">
                  <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                  <select class="form-select" id="fiscalYear" required>
                     <option value="">Select Fiscal Year</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" id="description" rows="2" maxlength="500" placeholder="Optional description..."></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="savePledgeBtn"><i class="bi bi-check-circle me-1"></i>Save Pledge</button>
         </div>
      </div>
   </div>
</div>

<!-- View Pledge Modal -->
<div class="modal fade" id="viewPledgeModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header border-0 p-0">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewPledgeContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading...</p>
            </div>
         </div>
         <div class="modal-footer border-0" id="viewPledgeFooter">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-cash me-2"></i>Record Payment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div id="paymentPledgeDetails" class="mb-3"></div>
            <form id="paymentForm">
               <div class="mb-3">
                  <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                  <div class="input-group">
                     <span class="input-group-text" id="paymentCurrencySymbol">GH₵</span>
                     <input type="number" class="form-control" id="paymentAmount" step="0.01" min="0.01" required>
                  </div>
               </div>
               <div class="mb-3">
                  <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="paymentDate" required>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success" id="submitPaymentBtn"><i class="bi bi-check-circle me-1"></i>Record Payment</button>
         </div>
      </div>
   </div>
</div>

<!-- Manage Pledge Types Modal -->
<div class="modal fade" id="pledgeTypesModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Manage Pledge Types</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="row mb-3">
               <div class="col-md-5">
                  <input type="text" class="form-control" id="newPledgeTypeName" placeholder="Pledge type name">
               </div>
               <div class="col-md-5">
                  <input type="text" class="form-control" id="newPledgeTypeDesc" placeholder="Description (optional)">
               </div>
               <div class="col-md-2">
                  <button class="btn btn-primary w-100" id="addPledgeTypeBtn"><i class="bi bi-plus"></i> Add</button>
               </div>
            </div>
            <div class="table-responsive">
               <table class="table table-hover" id="pledgeTypesTable">
                  <thead class="table-light">
                     <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th width="100">Actions</th>
                     </tr>
                  </thead>
                  <tbody id="pledgeTypesBody">
                     <tr>
                        <td colspan="3" class="text-center py-3">Loading...</td>
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

<!-- Edit Pledge Type Modal -->
<div class="modal fade" id="editPledgeTypeModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-sm">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Pledge Type</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <input type="hidden" id="editPledgeTypeId">
            <div class="mb-3">
               <label class="form-label">Name <span class="text-danger">*</span></label>
               <input type="text" class="form-control" id="editPledgeTypeName" required>
            </div>
            <div class="mb-3">
               <label class="form-label">Description</label>
               <input type="text" class="form-control" id="editPledgeTypeDesc">
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="savePledgeTypeBtn">Save</button>
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
         pledgesTable: null,
         currentPledgeId: null,
         currentPledgeData: null,
         membersData: [],
         pledgeTypesData: [],
         fiscalYearsData: [],
         memberChoices: null,
         pledgeTypeChoices: null,
         fiscalYearChoices: null,
         statsFiscalYearChoices: null,
         selectedFiscalYearId: null,
         currencySymbol: 'GH₵',
         byTypeChart: null,
         monthlyTrendChart: null,
         currentFilters: {},
         editingPledgeTypeId: null
      };

      document.addEventListener('DOMContentLoaded', async () => {
         if (!Auth.requireAuth()) return;
         await Config.waitForSettings();
         State.currencySymbol = Config.getSetting('currency_symbol', 'GH₵');
         document.getElementById('currencySymbol').textContent = State.currencySymbol;
         document.getElementById('paymentCurrencySymbol').textContent = State.currencySymbol;
         await initPage();
      });

      async function initPage() {
         await loadFiscalYearsForStats();
         await loadDropdowns();
         initTable();
         initEventListeners();
         loadStats();
         document.getElementById('pledgeDate').valueAsDate = new Date();
         document.getElementById('paymentDate').valueAsDate = new Date();
      }

      async function loadFiscalYearsForStats() {
         try {
            const fiscalRes = await api.get('fiscalyear/all?limit=50');
            State.fiscalYearsData = Array.isArray(fiscalRes) ? fiscalRes : (fiscalRes?.data || []);

            const select = document.getElementById('statsFiscalYear');
            select.innerHTML = '';

            const activeFY = State.fiscalYearsData.find(fy => fy.Status === 'Active');
            State.selectedFiscalYearId = activeFY?.FiscalYearID || null;

            State.fiscalYearsData.forEach(fy => {
               const opt = document.createElement('option');
               opt.value = fy.FiscalYearID;
               opt.textContent = fy.FiscalYearName + (fy.Status === 'Active' ? ' (Active)' : fy.Status === 'Closed' ? ' (Closed)' : '');
               if (fy.FiscalYearID === State.selectedFiscalYearId) opt.selected = true;
               select.appendChild(opt);
            });

            if (State.statsFiscalYearChoices) State.statsFiscalYearChoices.destroy();
            State.statsFiscalYearChoices = new Choices(select, {
               searchEnabled: true,
               searchPlaceholderValue: 'Search fiscal years...',
               itemSelectText: '',
               allowHTML: true,
               shouldSort: false
            });

            select.addEventListener('change', (e) => {
               State.selectedFiscalYearId = e.target.value ? parseInt(e.target.value) : null;
               loadStats();
               reloadTable();
            });
         } catch (error) {
            console.error('Load fiscal years error:', error);
         }
      }

      function initTable() {
         const url = buildTableUrl();
         State.pledgesTable = QMGridHelper.init('#pledgesTable', {
            url: url,
            pageSize: 25,
            selectable: false,
            exportable: true,
            columns: [{
                  key: 'MemberName',
                  title: 'Member',
                  render: (v) => `<div class="fw-medium">${v || '-'}</div>`
               },
               {
                  key: 'PledgeTypeName',
                  title: 'Type',
                  render: (v) => v ? `<span class="badge bg-info">${v}</span>` : '-'
               },
               {
                  key: 'PledgeAmount',
                  title: 'Amount',
                  render: (v) => `<span class="fw-semibold text-primary">${formatCurrency(v)}</span>`
               },
               {
                  key: 'TotalPaid',
                  title: 'Paid',
                  render: (v) => `<span class="text-success">${formatCurrency(v)}</span>`
               },
               {
                  key: 'Progress',
                  title: 'Progress',
                  width: '120px',
                  render: (v, row) => {
                     const color = v >= 100 ? 'success' : v >= 50 ? 'warning' : 'danger';
                     return `<div class="progress" style="height: 20px;"><div class="progress-bar bg-${color}" style="width: ${Math.min(v, 100)}%">${v}%</div></div>`;
                  }
               },
               {
                  key: 'PledgeDate',
                  title: 'Date',
                  render: (v) => QMGridHelper.formatDate(v, 'short')
               },
               {
                  key: 'DueDate',
                  title: 'Due',
                  render: (v, row) => {
                     if (!v) return '-';
                     const isOverdue = row.PledgeStatus === 'Active' && new Date(v) < new Date();
                     return `<span class="${isOverdue ? 'text-danger fw-bold' : ''}">${QMGridHelper.formatDate(v, 'short')}${isOverdue ? ' <i class="bi bi-exclamation-triangle"></i>' : ''}</span>`;
                  }
               },
               {
                  key: 'PledgeStatus',
                  title: 'Status',
                  render: (v) => {
                     const badges = {
                        'Active': 'warning',
                        'Fulfilled': 'success',
                        'Cancelled': 'secondary'
                     };
                     return `<span class="badge bg-${badges[v] || 'secondary'}">${v || '-'}</span>`;
                  }
               },
               {
                  key: 'PledgeID',
                  title: 'Actions',
                  width: '140px',
                  sortable: false,
                  exportable: false,
                  render: (v, row) => {
                     const isActive = row.PledgeStatus === 'Active';
                     let btns = `<button class="btn btn-primary btn-sm" onclick="viewPledge(${v})" title="View"><i class="bi bi-eye"></i></button>`;
                     if (isActive) {
                        btns += `<button class="btn btn-success btn-sm" onclick="recordPayment(${v})" title="Record Payment"><i class="bi bi-cash"></i></button>`;
                        btns += `<button class="btn btn-warning btn-sm" onclick="editPledge(${v})" title="Edit"><i class="bi bi-pencil"></i></button>`;
                     }
                     return `<div class="btn-group btn-group-sm">${btns}</div>`;
                  }
               }
            ],
            onDataLoaded: (data) => {
               document.getElementById('totalPledgesCount').textContent = data.pagination?.total || data.total || 0;
            }
         });
      }

      function buildTableUrl() {
         let url = `${Config.API_BASE_URL}/pledge/all`;
         const params = new URLSearchParams();
         if (State.selectedFiscalYearId) params.append('fiscal_year_id', State.selectedFiscalYearId);
         if (State.currentFilters.status) params.append('status', State.currentFilters.status);
         if (State.currentFilters.pledge_type_id) params.append('pledge_type_id', State.currentFilters.pledge_type_id);
         if (State.currentFilters.start_date) params.append('start_date', State.currentFilters.start_date);
         if (State.currentFilters.end_date) params.append('end_date', State.currentFilters.end_date);
         if (params.toString()) url += '?' + params.toString();
         return url;
      }

      function reloadTable() {
         if (State.pledgesTable) State.pledgesTable.destroy();
         initTable();
      }

      async function loadStats() {
         try {
            let url = 'pledge/stats';
            if (State.selectedFiscalYearId) url += `?fiscal_year_id=${State.selectedFiscalYearId}`;
            const stats = await api.get(url);
            renderStatsCards(stats);
            renderTopPledgers(stats.top_pledgers || []);
            renderByTypeChart(stats.by_type || []);
            renderMonthlyTrendChart(stats.monthly_trend || []);
         } catch (error) {
            console.error('Load stats error:', error);
            renderStatsCards({});
            renderTopPledgers([]);
         }
      }

      function renderStatsCards(stats) {
         const fyStatus = stats.fiscal_year?.status;
         const statusBadge = fyStatus === 'Closed' ? ' <span class="badge bg-secondary small">Closed</span>' : '';
         const row1Cards = [{
               title: `Total Pledged ${statusBadge}`,
               value: formatCurrency(stats.total_amount || 0),
               subtitle: `${(stats.total_count || 0).toLocaleString()} pledges`,
               icon: 'bookmark-heart',
               color: 'primary'
            },
            {
               title: 'Fulfilled',
               value: formatCurrency(stats.fulfilled_amount || 0),
               subtitle: `${(stats.fulfilled_count || 0).toLocaleString()} pledges`,
               icon: 'check-circle',
               color: 'success'
            },
            {
               title: 'Active',
               value: formatCurrency(stats.active_amount || 0),
               subtitle: `${(stats.active_count || 0).toLocaleString()} pledges`,
               icon: 'hourglass-split',
               color: 'warning'
            },
            {
               title: 'Payments Received',
               value: formatCurrency(stats.payments_total || 0),
               subtitle: `${(stats.payments_count || 0).toLocaleString()} payments`,
               icon: 'cash-stack',
               color: 'info'
            }
         ];
         const row2Cards = [{
               title: 'Outstanding',
               value: formatCurrency(stats.outstanding_amount || 0),
               subtitle: 'Balance remaining',
               icon: 'exclamation-triangle',
               color: 'danger'
            },
            {
               title: 'Overdue',
               value: formatCurrency(stats.overdue_amount || 0),
               subtitle: `${(stats.overdue_count || 0).toLocaleString()} pledges`,
               icon: 'alarm',
               color: 'danger'
            },
            {
               title: 'Fulfillment Rate',
               value: `${stats.fulfillment_rate || 0}%`,
               subtitle: 'Pledges completed',
               icon: 'graph-up-arrow',
               color: 'success'
            },
            {
               title: 'Cancelled',
               value: formatCurrency(stats.cancelled_amount || 0),
               subtitle: `${(stats.cancelled_count || 0).toLocaleString()} pledges`,
               icon: 'x-circle',
               color: 'secondary'
            }
         ];
         document.getElementById('statsCardsRow1').innerHTML = row1Cards.map(renderStatCard).join('');
         document.getElementById('statsCardsRow2').innerHTML = row2Cards.map(renderStatCard).join('');
      }

      function renderStatCard(card) {
         return `<div class="col-lg-3 col-md-6"><div class="card stat-card bg-${card.color} bg-opacity-10 mb-3"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted mb-1 small">${card.title}</p><h4 class="mb-0">${card.value}</h4><small class="text-muted">${card.subtitle}</small></div><div class="stat-icon bg-${card.color} text-white rounded-circle"><i class="bi bi-${card.icon}"></i></div></div></div></div></div>`;
      }

      function renderTopPledgers(pledgers) {
         const tbody = document.getElementById('topPledgersBody');
         if (pledgers.length > 0) {
            tbody.innerHTML = pledgers.slice(0, 5).map(p => `<tr><td><div class="fw-medium">${p.MbrFirstName} ${p.MbrFamilyName}</div><small class="text-muted">${p.pledge_count} pledge(s)</small></td><td class="text-end fw-semibold text-primary">${formatCurrency(parseFloat(p.total_pledged))}</td></tr>`).join('');
         } else {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">No data available</td></tr>';
         }
      }

      function renderByTypeChart(byType) {
         const ctx = document.getElementById('byTypeChart').getContext('2d');
         if (State.byTypeChart) State.byTypeChart.destroy();
         if (!byType.length) {
            ctx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
         }
         const colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#6c757d'];
         State.byTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
               labels: byType.map(t => t.PledgeTypeName),
               datasets: [{
                  data: byType.map(t => parseFloat(t.total)),
                  backgroundColor: colors.slice(0, byType.length),
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
                  },
                  tooltip: {
                     callbacks: {
                        label: (ctx) => `${ctx.label}: ${formatCurrency(ctx.raw)}`
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
                  label: 'Pledges',
                  data: monthlyTrend.map(m => parseFloat(m.total)),
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
                  },
                  tooltip: {
                     callbacks: {
                        label: (ctx) => formatCurrency(ctx.raw)
                     }
                  }
               },
               scales: {
                  y: {
                     beginAtZero: true,
                     ticks: {
                        callback: (v) => formatCurrencyShort(v)
                     }
                  },
                  x: {
                     ticks: {
                        font: {
                           size: 10
                        }
                     }
                  }
               }
            }
         });
      }

      async function loadDropdowns() {
         try {
            const [membersRes, typesRes] = await Promise.all([
               api.get('member/all?limit=1000'),
               api.get('pledge/types')
            ]);

            State.membersData = Array.isArray(membersRes) ? membersRes : (membersRes?.data || []);
            State.pledgeTypesData = Array.isArray(typesRes) ? typesRes : (typesRes?.data || []);

            // Populate filter type dropdown (not using Choices.js)
            const filterTypeSelect = document.getElementById('filterType');
            filterTypeSelect.innerHTML = '<option value="">All Types</option>';
            State.pledgeTypesData.forEach(t => {
               filterTypeSelect.innerHTML += `<option value="${t.PledgeTypeID}">${t.PledgeTypeName}</option>`;
            });
         } catch (error) {
            console.error('Load dropdowns error:', error);
         }
      }

      function initEventListeners() {
         document.getElementById('addPledgeBtn')?.addEventListener('click', () => {
            if (!Auth.hasPermission('manage_pledges')) {
               Alerts.error('You do not have permission to create pledges');
               return;
            }
            openPledgeModal();
         });
         document.getElementById('savePledgeBtn')?.addEventListener('click', savePledge);
         document.getElementById('refreshGrid')?.addEventListener('click', () => {
            reloadTable();
            loadStats();
         });
         document.getElementById('applyFiltersBtn')?.addEventListener('click', applyFilters);
         document.getElementById('clearFiltersBtn')?.addEventListener('click', clearFilters);
         document.getElementById('submitPaymentBtn')?.addEventListener('click', submitPayment);

         // Pledge Types Management
         document.getElementById('managePledgeTypesBtn')?.addEventListener('click', openPledgeTypesModal);
         document.getElementById('addPledgeTypeBtn')?.addEventListener('click', addPledgeType);
         document.getElementById('savePledgeTypeBtn')?.addEventListener('click', saveEditPledgeType);
      }

      // ========== PLEDGE TYPES MANAGEMENT ==========
      async function openPledgeTypesModal() {
         new bootstrap.Modal(document.getElementById('pledgeTypesModal')).show();
         await loadPledgeTypesTable();
      }

      async function loadPledgeTypesTable() {
         const tbody = document.getElementById('pledgeTypesBody');
         tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>';

         try {
            const res = await api.get('pledge/types');
            const types = Array.isArray(res) ? res : (res?.data || []);
            State.pledgeTypesData = types;

            if (types.length === 0) {
               tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No pledge types found</td></tr>';
               return;
            }

            tbody.innerHTML = types.map(t => `
               <tr>
                  <td class="fw-medium">${t.PledgeTypeName}</td>
                  <td class="text-muted">${t.Description || '-'}</td>
                  <td>
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning btn-sm" onclick="editPledgeType(${t.PledgeTypeID}, '${t.PledgeTypeName.replace(/'/g, "\\'")}', '${(t.Description || '').replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deletePledgeType(${t.PledgeTypeID}, '${t.PledgeTypeName.replace(/'/g, "\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
                     </div>
                  </td>
               </tr>
            `).join('');

            // Update filter dropdown
            const filterTypeSelect = document.getElementById('filterType');
            filterTypeSelect.innerHTML = '<option value="">All Types</option>';
            types.forEach(t => {
               filterTypeSelect.innerHTML += `<option value="${t.PledgeTypeID}">${t.PledgeTypeName}</option>`;
            });
         } catch (error) {
            console.error('Load pledge types error:', error);
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Failed to load pledge types</td></tr>';
         }
      }

      async function addPledgeType() {
         const name = document.getElementById('newPledgeTypeName').value.trim();
         const desc = document.getElementById('newPledgeTypeDesc').value.trim();

         if (!name) {
            Alerts.warning('Please enter a pledge type name');
            return;
         }

         try {
            Alerts.loading('Adding pledge type...');
            await api.post('pledge/type/create', {
               name,
               description: desc || null
            });
            Alerts.closeLoading();
            Alerts.success('Pledge type added');
            document.getElementById('newPledgeTypeName').value = '';
            document.getElementById('newPledgeTypeDesc').value = '';
            await loadPledgeTypesTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      function editPledgeType(id, name, desc) {
         State.editingPledgeTypeId = id;
         document.getElementById('editPledgeTypeId').value = id;
         document.getElementById('editPledgeTypeName').value = name;
         document.getElementById('editPledgeTypeDesc').value = desc || '';
         new bootstrap.Modal(document.getElementById('editPledgeTypeModal')).show();
      }
      window.editPledgeType = editPledgeType;

      async function saveEditPledgeType() {
         const id = State.editingPledgeTypeId;
         const name = document.getElementById('editPledgeTypeName').value.trim();
         const desc = document.getElementById('editPledgeTypeDesc').value.trim();

         if (!name) {
            Alerts.warning('Please enter a pledge type name');
            return;
         }

         try {
            Alerts.loading('Saving...');
            await api.put(`pledge/type/update/${id}`, {
               name,
               description: desc || null
            });
            Alerts.closeLoading();
            Alerts.success('Pledge type updated');
            bootstrap.Modal.getInstance(document.getElementById('editPledgeTypeModal')).hide();
            await loadPledgeTypesTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function deletePledgeType(id, name) {
         const confirmed = await Alerts.confirm(`Delete pledge type "${name}"?`, 'This cannot be undone. Types in use cannot be deleted.');
         if (!confirmed) return;

         try {
            Alerts.loading('Deleting...');
            await api.delete(`pledge/type/delete/${id}`);
            Alerts.closeLoading();
            Alerts.success('Pledge type deleted');
            await loadPledgeTypesTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }
      window.deletePledgeType = deletePledgeType;

      function openPledgeModal(pledgeId = null) {
         State.currentPledgeId = pledgeId;
         document.getElementById('pledgeForm').reset();
         document.getElementById('pledgeId').value = pledgeId || '';
         document.getElementById('pledgeModalTitle').innerHTML = pledgeId ? '<i class="bi bi-pencil me-2"></i>Edit Pledge' : '<i class="bi bi-bookmark-heart me-2"></i>Create Pledge';
         document.getElementById('pledgeDate').valueAsDate = new Date();

         // Initialize Choices.js for member select
         const memberSelect = document.getElementById('memberId');
         memberSelect.innerHTML = '<option value="">Select Member</option>';
         State.membersData.forEach(m => {
            memberSelect.innerHTML += `<option value="${m.MbrID}">${m.MbrFirstName} ${m.MbrFamilyName}</option>`;
         });
         if (State.memberChoices) State.memberChoices.destroy();
         State.memberChoices = new Choices(memberSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search members...',
            itemSelectText: '',
            allowHTML: true
         });

         // Initialize Choices.js for pledge type select
         const typeSelect = document.getElementById('pledgeTypeId');
         typeSelect.innerHTML = '<option value="">Select Type</option>';
         State.pledgeTypesData.forEach(t => {
            typeSelect.innerHTML += `<option value="${t.PledgeTypeID}">${t.PledgeTypeName}</option>`;
         });
         if (State.pledgeTypeChoices) State.pledgeTypeChoices.destroy();
         State.pledgeTypeChoices = new Choices(typeSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search types...',
            itemSelectText: '',
            allowHTML: true
         });

         // Initialize Choices.js for fiscal year select
         const fiscalSelect = document.getElementById('fiscalYear');
         fiscalSelect.innerHTML = '<option value="">Select Fiscal Year</option>';
         State.fiscalYearsData.forEach(fy => {
            const opt = document.createElement('option');
            opt.value = fy.FiscalYearID;
            opt.textContent = fy.FiscalYearName + (fy.Status === 'Active' ? ' (Active)' : '');
            fiscalSelect.appendChild(opt);
         });
         if (State.fiscalYearChoices) State.fiscalYearChoices.destroy();
         State.fiscalYearChoices = new Choices(fiscalSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search fiscal years...',
            itemSelectText: '',
            allowHTML: true
         });

         // Set default fiscal year
         const activeYear = State.fiscalYearsData.find(fy => fy.Status === 'Active');
         if (activeYear && State.fiscalYearChoices) {
            State.fiscalYearChoices.setChoiceByValue(String(activeYear.FiscalYearID));
         }

         new bootstrap.Modal(document.getElementById('pledgeModal')).show();
      }

      async function savePledge() {
         const memberId = document.getElementById('memberId').value;
         const typeId = document.getElementById('pledgeTypeId').value;
         const amount = document.getElementById('amount').value;
         const pledgeDate = document.getElementById('pledgeDate').value;
         const fiscalYearId = document.getElementById('fiscalYear').value;

         if (!memberId || !typeId || !amount || !pledgeDate || !fiscalYearId) {
            Alerts.warning('Please fill all required fields');
            return;
         }

         const payload = {
            member_id: parseInt(memberId),
            pledge_type_id: parseInt(typeId),
            amount: parseFloat(amount),
            pledge_date: pledgeDate,
            due_date: document.getElementById('dueDate').value || null,
            fiscal_year_id: parseInt(fiscalYearId),
            description: document.getElementById('description').value.trim() || null
         };

         try {
            Alerts.loading('Saving pledge...');
            if (State.currentPledgeId) {
               await api.put(`pledge/update/${State.currentPledgeId}`, payload);
            } else {
               await api.post('pledge/create', payload);
            }
            Alerts.closeLoading();
            Alerts.success(`Pledge ${State.currentPledgeId ? 'updated' : 'created'} successfully`);
            bootstrap.Modal.getInstance(document.getElementById('pledgeModal')).hide();
            reloadTable();
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function viewPledge(pledgeId) {
         State.currentPledgeId = pledgeId;
         const modal = new bootstrap.Modal(document.getElementById('viewPledgeModal'));
         modal.show();

         try {
            const p = await api.get(`pledge/view/${pledgeId}`);
            State.currentPledgeData = p;
            const statusColors = {
               'Active': '#ffc107',
               'Fulfilled': '#198754',
               'Cancelled': '#6c757d'
            };
            const statusColor = statusColors[p.PledgeStatus] || '#6c757d';
            const progressColor = p.progress >= 100 ? '#198754' : p.progress >= 50 ? '#ffc107' : '#dc3545';

            let paymentsHtml = '';
            if (p.payments && p.payments.length > 0) {
               paymentsHtml = `<div class="mt-3 pt-3 border-top">
               <div class="text-muted small text-uppercase mb-2">Payment History</div>
               <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                     <thead class="table-light"><tr><th>Date</th><th>Amount</th><th>Recorded By</th></tr></thead>
                     <tbody>${p.payments.map(pay => `<tr><td>${new Date(pay.PaymentDate).toLocaleDateString()}</td><td class="text-success fw-semibold">${formatCurrency(pay.PaymentAmount)}</td><td>${pay.RecorderFirstName ? pay.RecorderFirstName + ' ' + pay.RecorderFamilyName : '-'}</td></tr>`).join('')}</tbody>
                  </table>
               </div>
            </div>`;
            }

            document.getElementById('viewPledgeContent').innerHTML = `
         <div class="pledge-view">
            <div class="text-center py-4" style="background: linear-gradient(135deg, ${statusColor} 0%, ${statusColor}99 100%);">
               <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                  <i class="bi bi-bookmark-heart" style="font-size:2rem;color:${statusColor};"></i>
               </div>
               <h2 class="text-white mb-1">${formatCurrency(p.PledgeAmount)}</h2>
               <p class="text-white-50 mb-0">${p.PledgeTypeName}</p>
               <span class="badge bg-white text-dark mt-2">${p.PledgeStatus}</span>
            </div>
            <div class="p-4">
               <div class="mb-3">
                  <div class="d-flex justify-content-between mb-1">
                     <span class="text-muted small">Progress</span>
                     <span class="fw-semibold">${p.progress}%</span>
                  </div>
                  <div class="progress" style="height: 25px;">
                     <div class="progress-bar" style="width: ${Math.min(p.progress, 100)}%; background-color: ${progressColor};">${formatCurrency(p.total_paid)} paid</div>
                  </div>
                  <div class="d-flex justify-content-between mt-1">
                     <small class="text-muted">Paid: ${formatCurrency(p.total_paid)}</small>
                     <small class="text-muted">Balance: ${formatCurrency(p.balance)}</small>
                  </div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-6"><div class="text-muted small text-uppercase">Member</div><div class="fw-semibold">${p.MemberName}</div></div>
                  <div class="col-6"><div class="text-muted small text-uppercase">Fiscal Year</div><div>${p.FiscalYearName || '-'}</div></div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-6"><div class="text-muted small text-uppercase">Pledge Date</div><div>${new Date(p.PledgeDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>
                  <div class="col-6"><div class="text-muted small text-uppercase">Due Date</div><div>${p.DueDate ? new Date(p.DueDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'Not set'}</div></div>
               </div>
               ${p.Description ? `<div class="mb-3 pt-3 border-top"><div class="text-muted small text-uppercase">Description</div><div>${p.Description}</div></div>` : ''}
               <div class="row g-3 pt-3 border-top">
                  <div class="col-6"><div class="text-muted small text-uppercase">Created By</div><div>${p.CreatorName || '-'}</div>${p.CreatedAt ? `<small class="text-muted">${new Date(p.CreatedAt).toLocaleString()}</small>` : ''}</div>
               </div>
               ${paymentsHtml}
            </div>
         </div>`;

            let footerHtml = `<button type="button" class="btn btn-outline-secondary" onclick="printPledge()"><i class="bi bi-printer me-1"></i>Print</button>`;
            if (p.PledgeStatus === 'Active') {
               footerHtml += `<button type="button" class="btn btn-success" onclick="recordPayment(${pledgeId})"><i class="bi bi-cash me-1"></i>Record Payment</button>`;
            }
            footerHtml += `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
            document.getElementById('viewPledgeFooter').innerHTML = footerHtml;
         } catch (error) {
            console.error('View pledge error:', error);
            document.getElementById('viewPledgeContent').innerHTML = `<div class="text-center text-danger py-5"><i class="bi bi-exclamation-circle fs-1"></i><p class="mt-2">Failed to load pledge details</p></div>`;
         }
      }
      window.viewPledge = viewPledge;

      function printPledge() {
         const p = State.currentPledgeData;
         if (!p) return;
         const printWindow = window.open('', '_blank');
         printWindow.document.write(`<!DOCTYPE html><html><head><title>Pledge Details - ${p.MemberName}</title>
         <style>body{font-family:Arial,sans-serif;padding:20px;max-width:800px;margin:0 auto}.header{text-align:center;border-bottom:2px solid #333;padding-bottom:20px;margin-bottom:20px}.header h1{margin:0;font-size:24px}.amount{font-size:32px;font-weight:bold;color:#0d6efd;text-align:center;margin:20px 0}.status{display:inline-block;padding:5px 15px;border-radius:20px;font-weight:bold}.status-active{background:#fff3cd;color:#856404}.status-fulfilled{background:#d4edda;color:#155724}.status-cancelled{background:#e2e3e5;color:#383d41}.details{margin:20px 0}.row{display:flex;border-bottom:1px solid #eee;padding:10px 0}.label{width:150px;font-weight:bold;color:#666}.value{flex:1}.progress-bar{background:#e9ecef;border-radius:10px;height:20px;overflow:hidden;margin:10px 0}.progress-fill{height:100%;background:#198754}.footer{margin-top:40px;text-align:center;font-size:12px;color:#999}@media print{body{padding:0}}</style>
      </head><body>
         <div class="header"><h1>PLEDGE COMMITMENT</h1><p>Reference: PLG-${String(p.PledgeID).padStart(6, '0')}</p></div>
         <div class="amount">${formatCurrency(p.PledgeAmount)}</div>
         <div style="text-align:center;margin-bottom:20px"><span class="status status-${p.PledgeStatus.toLowerCase()}">${p.PledgeStatus}</span></div>
         <div class="progress-bar"><div class="progress-fill" style="width:${Math.min(p.progress, 100)}%"></div></div>
         <div style="text-align:center;margin-bottom:20px"><small>Progress: ${p.progress}% | Paid: ${formatCurrency(p.total_paid)} | Balance: ${formatCurrency(p.balance)}</small></div>
         <div class="details">
            <div class="row"><div class="label">Member:</div><div class="value">${p.MemberName}</div></div>
            <div class="row"><div class="label">Pledge Type:</div><div class="value">${p.PledgeTypeName}</div></div>
            <div class="row"><div class="label">Pledge Date:</div><div class="value">${new Date(p.PledgeDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>
            ${p.DueDate ? `<div class="row"><div class="label">Due Date:</div><div class="value">${new Date(p.DueDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div></div>` : ''}
            <div class="row"><div class="label">Fiscal Year:</div><div class="value">${p.FiscalYearName || '-'}</div></div>
            ${p.Description ? `<div class="row"><div class="label">Description:</div><div class="value">${p.Description}</div></div>` : ''}
         </div>
         <div class="footer"><p>Printed on ${new Date().toLocaleString()}</p></div>
      </body></html>`);
         printWindow.document.close();
         printWindow.focus();
         setTimeout(() => {
            printWindow.print();
         }, 250);
      }
      window.printPledge = printPledge;

      async function editPledge(pledgeId) {
         try {
            const p = await api.get(`pledge/view/${pledgeId}`);
            State.currentPledgeId = pledgeId;
            State.currentPledgeData = p;

            document.getElementById('pledgeId').value = pledgeId;
            document.getElementById('pledgeModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Pledge';

            // Initialize Choices.js for member select
            const memberSelect = document.getElementById('memberId');
            memberSelect.innerHTML = '<option value="">Select Member</option>';
            State.membersData.forEach(m => {
               memberSelect.innerHTML += `<option value="${m.MbrID}">${m.MbrFirstName} ${m.MbrFamilyName}</option>`;
            });
            if (State.memberChoices) State.memberChoices.destroy();
            State.memberChoices = new Choices(memberSelect, {
               searchEnabled: true,
               searchPlaceholderValue: 'Search members...',
               itemSelectText: '',
               allowHTML: true
            });
            State.memberChoices.setChoiceByValue(String(p.MbrID));

            // Initialize Choices.js for pledge type select
            const typeSelect = document.getElementById('pledgeTypeId');
            typeSelect.innerHTML = '<option value="">Select Type</option>';
            State.pledgeTypesData.forEach(t => {
               typeSelect.innerHTML += `<option value="${t.PledgeTypeID}">${t.PledgeTypeName}</option>`;
            });
            if (State.pledgeTypeChoices) State.pledgeTypeChoices.destroy();
            State.pledgeTypeChoices = new Choices(typeSelect, {
               searchEnabled: true,
               searchPlaceholderValue: 'Search types...',
               itemSelectText: '',
               allowHTML: true
            });
            State.pledgeTypeChoices.setChoiceByValue(String(p.PledgeTypeID));

            // Initialize Choices.js for fiscal year select
            const fiscalSelect = document.getElementById('fiscalYear');
            fiscalSelect.innerHTML = '<option value="">Select Fiscal Year</option>';
            State.fiscalYearsData.forEach(fy => {
               const opt = document.createElement('option');
               opt.value = fy.FiscalYearID;
               opt.textContent = fy.FiscalYearName + (fy.Status === 'Active' ? ' (Active)' : '');
               fiscalSelect.appendChild(opt);
            });
            if (State.fiscalYearChoices) State.fiscalYearChoices.destroy();
            State.fiscalYearChoices = new Choices(fiscalSelect, {
               searchEnabled: true,
               searchPlaceholderValue: 'Search fiscal years...',
               itemSelectText: '',
               allowHTML: true
            });
            State.fiscalYearChoices.setChoiceByValue(String(p.FiscalYearID));

            document.getElementById('amount').value = p.PledgeAmount;
            document.getElementById('pledgeDate').value = p.PledgeDate;
            document.getElementById('dueDate').value = p.DueDate || '';
            document.getElementById('description').value = p.Description || '';

            new bootstrap.Modal(document.getElementById('pledgeModal')).show();
         } catch (error) {
            Alerts.error('Failed to load pledge for editing');
         }
      }
      window.editPledge = editPledge;

      async function recordPayment(pledgeId) {
         State.currentPledgeId = pledgeId;
         const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewPledgeModal'));
         if (viewModal) viewModal.hide();

         try {
            const p = State.currentPledgeData || await api.get(`pledge/view/${pledgeId}`);
            State.currentPledgeData = p;
            document.getElementById('paymentPledgeDetails').innerHTML = `
         <div class="card bg-light">
            <div class="card-body">
               <h6 class="mb-2">${p.MemberName} - ${p.PledgeTypeName}</h6>
               <div class="row">
                  <div class="col-6"><small class="text-muted">Pledged</small><div class="fw-bold text-primary">${formatCurrency(p.PledgeAmount)}</div></div>
                  <div class="col-6"><small class="text-muted">Balance</small><div class="fw-bold text-danger">${formatCurrency(p.balance)}</div></div>
               </div>
               <div class="progress mt-2" style="height: 10px;"><div class="progress-bar bg-success" style="width: ${Math.min(p.progress, 100)}%"></div></div>
               <small class="text-muted">${p.progress}% fulfilled</small>
            </div>
         </div>`;
            document.getElementById('paymentAmount').value = '';
            document.getElementById('paymentAmount').max = p.balance;
            document.getElementById('paymentDate').valueAsDate = new Date();
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
         } catch (error) {
            Alerts.error('Failed to load pledge details');
         }
      }
      window.recordPayment = recordPayment;

      async function submitPayment() {
         const amount = document.getElementById('paymentAmount').value;
         const paymentDate = document.getElementById('paymentDate').value;
         if (!amount || !paymentDate) {
            Alerts.warning('Please fill all required fields');
            return;
         }

         try {
            Alerts.loading('Recording payment...');
            await api.post(`pledge/payment/${State.currentPledgeId}`, {
               amount: parseFloat(amount),
               payment_date: paymentDate
            });
            Alerts.closeLoading();
            Alerts.success('Payment recorded successfully');
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            reloadTable();
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      function applyFilters() {
         State.currentFilters = {
            status: document.getElementById('filterStatus').value,
            pledge_type_id: document.getElementById('filterType').value,
            start_date: document.getElementById('filterStartDate').value,
            end_date: document.getElementById('filterEndDate').value
         };
         Object.keys(State.currentFilters).forEach(k => !State.currentFilters[k] && delete State.currentFilters[k]);
         reloadTable();
      }

      function clearFilters() {
         document.getElementById('filterStatus').value = '';
         document.getElementById('filterType').value = '';
         document.getElementById('filterStartDate').value = '';
         document.getElementById('filterEndDate').value = '';
         State.currentFilters = {};
         reloadTable();
      }

      function formatCurrency(amount) {
         if (amount === null || amount === undefined) return '-';
         const num = parseFloat(amount);
         if (isNaN(num)) return '-';
         return `${State.currencySymbol} ${num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
      }

      function formatCurrencyShort(amount) {
         if (amount >= 1000000) return `${State.currencySymbol}${(amount/1000000).toFixed(1)}M`;
         if (amount >= 1000) return `${State.currencySymbol}${(amount/1000).toFixed(1)}K`;
         return `${State.currencySymbol}${amount}`;
      }
   })();
</script>

<style>
   .stat-card {
      border: none;
      transition: transform 0.2s;
   }

   .stat-card:hover {
      transform: translateY(-2px);
   }

   .stat-icon {
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
   }

   .pledge-view {
      border-radius: 0;
   }

   .cursor-pointer {
      cursor: pointer;
   }

   .cursor-pointer:hover {
      background-color: rgba(0, 0, 0, 0.02);
   }
</style>

<?php require_once '../includes/footer.php'; ?>