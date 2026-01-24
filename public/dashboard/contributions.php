<?php
$pageTitle = 'Contributions Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">
            <i class="bi bi-cash-coin me-2"></i>Contributions
         </h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Contributions</li>
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
         <button class="btn btn-outline-secondary" id="manageTypesBtn" data-permission="manage_contribution_types">
            <i class="bi bi-tags me-1"></i>Types
         </button>
         <button class="btn btn-primary" id="addContributionBtn" data-permission="create_contribution">
            <i class="bi bi-plus-circle me-2"></i>Record Contribution
         </button>
      </div>
   </div>

   <!-- Stats Cards Row 1 -->
   <div class="row mb-3" id="statsCardsRow1">
      <div class="col-12 text-center py-4">
         <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
         </div>
      </div>
   </div>

   <!-- Stats Cards Row 2 -->
   <div class="row mb-4" id="statsCardsRow2"></div>

   <!-- Charts and Top Contributors Row -->
   <div class="row mb-4">
      <!-- Contribution by Type Chart -->
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>By Type</h6>
            </div>
            <div class="card-body">
               <canvas id="byTypeChart" height="200"></canvas>
            </div>
         </div>
      </div>

      <!-- Monthly Trend Chart -->
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Monthly Trend</h6>
            </div>
            <div class="card-body">
               <canvas id="monthlyTrendChart" height="200"></canvas>
            </div>
         </div>
      </div>

      <!-- Top Contributors -->
      <div class="col-lg-4 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-trophy me-2"></i>Top Contributors</h6>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                     <thead class="table-light">
                        <tr>
                           <th style="width:40px"></th>
                           <th>Member</th>
                           <th class="text-end">Total</th>
                        </tr>
                     </thead>
                     <tbody id="topContributorsBody">
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
                     <label class="form-label small mb-1">Contribution Type</label>
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
                     <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="showDeletedCheckbox">
                        <label class="form-check-label small" for="showDeletedCheckbox">
                           Show Deleted
                        </label>
                     </div>
                  </div>
                  <div class="col-md-2">
                     <button class="btn btn-primary btn-sm w-100" id="applyFiltersBtn">
                        <i class="bi bi-search me-1"></i>Filter
                     </button>
                  </div>
                  <div class="col-md-2">
                     <button class="btn btn-outline-secondary btn-sm w-100" id="clearFiltersBtn">
                        <i class="bi bi-x-circle me-1"></i>Clear
                     </button>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Contributions Table -->
   <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h5 class="mb-0">
                  <i class="bi bi-table me-2"></i>All Contributions
                  <span class="badge bg-primary ms-2" id="totalContributionsCount">0</span>
               </h5>
            </div>
            <div class="col-md-6 text-end">
               <button class="btn btn-sm btn-outline-secondary" id="refreshGrid" title="Refresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
               </button>
            </div>
         </div>
      </div>
      <div class="card-body">
         <div id="contributionsTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Contribution Modal -->
<div class="modal fade" id="contributionModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header bg-primary bg-opacity-10 border-0">
            <h5 class="modal-title" id="contributionModalTitle">
               <i class="bi bi-cash-coin me-2"></i>Record Contribution
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body px-4">
            <form id="contributionForm">
               <input type="hidden" id="contributionId">
               <div class="mb-3">
                  <label class="form-label">Member <span class="text-danger">*</span></label>
                  <select class="form-select" id="memberId" required>
                     <option value="">Select Member</option>
                  </select>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Amount <span class="text-danger">*</span></label>
                     <div class="input-group">
                        <span class="input-group-text" id="currencySymbol">GH₵</span>
                        <input type="number" class="form-control" id="amount" step="0.01" min="0.01" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Date <span class="text-danger">*</span></label>
                     <input type="date" class="form-control" id="contributionDate" required>
                  </div>
               </div>
               <div class="row g-3 mb-3">
                  <div class="col-md-6">
                     <label class="form-label">Contribution Type <span class="text-danger">*</span></label>
                     <select class="form-select" id="contributionType" required>
                        <option value="">Select Type</option>
                     </select>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                     <select class="form-select" id="paymentOption" required>
                        <option value="">Select Method</option>
                     </select>
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
                  <textarea class="form-control" id="description" rows="2" placeholder="Optional notes..."></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveContributionBtn">
               <i class="bi bi-check-circle me-1"></i>Save Contribution
            </button>
         </div>
      </div>
   </div>
</div>

<!-- View Contribution Modal -->
<div class="modal fade" id="viewContributionModal" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header border-0 p-0">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewContributionContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading...</p>
            </div>
         </div>
         <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-success" id="printReceiptBtn">
               <i class="bi bi-printer me-1"></i>Print Receipt
            </button>
            <button type="button" class="btn btn-primary" id="editContributionFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Receipt Print Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Contribution Receipt</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="receiptContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="printReceipt()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Member Statement Modal -->
<div class="modal fade" id="statementModal" tabindex="-1">
   <div class="modal-dialog modal-xl">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-file-text me-2"></i>Contribution Statement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="statementContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="printStatement()">
               <i class="bi bi-printer me-1"></i>Print Statement
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Manage Contribution Types Modal -->
<div class="modal fade" id="typesModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Manage Contribution Types</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div class="row mb-3">
               <div class="col-md-5">
                  <input type="text" class="form-control" id="newTypeName" placeholder="Type name (e.g., Tithe, Offering)">
               </div>
               <div class="col-md-5">
                  <input type="text" class="form-control" id="newTypeDesc" placeholder="Description (optional)">
               </div>
               <div class="col-md-2">
                  <button class="btn btn-primary w-100" id="addTypeBtn"><i class="bi bi-plus"></i> Add</button>
               </div>
            </div>
            <div class="table-responsive">
               <table class="table table-hover" id="typesTable">
                  <thead class="table-light">
                     <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th width="100">Actions</th>
                     </tr>
                  </thead>
                  <tbody id="typesBody">
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

<!-- Edit Contribution Type Modal -->
<div class="modal fade" id="editTypeModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-sm">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Type</h5>
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
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveTypeBtn">Save</button>
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
         contributionsTable: null,
         currentContributionId: null,
         isEditMode: false,
         membersData: [],
         typesData: [],
         paymentOptionsData: [],
         fiscalYearsData: [],
         memberChoices: null,
         typeChoices: null,
         paymentChoices: null,
         fiscalYearChoices: null,
         statsFiscalYearChoices: null,
         selectedFiscalYearId: null,
         currencySymbol: 'GH₵',
         byTypeChart: null,
         monthlyTrendChart: null,
         statsData: null,
         editingTypeId: null
      };

      document.addEventListener('DOMContentLoaded', async () => {
         if (!Auth.requireAuth()) return;
         await Config.waitForSettings();
         State.currencySymbol = Config.getSetting('currency_symbol', 'GH₵');
         document.getElementById('currencySymbol').textContent = State.currencySymbol;
         await initPage();
      });

      async function initPage() {
         await loadFiscalYearsForStats();
         await loadDropdowns();
         initTable();
         initEventListeners();
         loadStats();
         document.getElementById('contributionDate').valueAsDate = new Date();
      }

      async function loadFiscalYearsForStats() {
         try {
            const fiscalRes = await api.get('fiscalyear/all?limit=50');
            State.fiscalYearsData = Array.isArray(fiscalRes) ? fiscalRes : (fiscalRes?.data || []);

            const select = document.getElementById('statsFiscalYear');
            select.innerHTML = '';

            // Find active fiscal year
            const activeFY = State.fiscalYearsData.find(fy => fy.Status === 'Active');
            State.selectedFiscalYearId = activeFY?.FiscalYearID || null;

            // Populate options
            State.fiscalYearsData.forEach(fy => {
               const opt = document.createElement('option');
               opt.value = fy.FiscalYearID;
               opt.textContent = fy.FiscalYearName + (fy.Status === 'Active' ? ' (Active)' : fy.Status === 'Closed' ? ' (Closed)' : '');
               if (fy.FiscalYearID === State.selectedFiscalYearId) opt.selected = true;
               select.appendChild(opt);
            });

            // Initialize Choices.js
            if (State.statsFiscalYearChoices) State.statsFiscalYearChoices.destroy();
            State.statsFiscalYearChoices = new Choices(select, {
               searchEnabled: true,
               searchPlaceholderValue: 'Search fiscal years...',
               itemSelectText: '',
               allowHTML: true,
               shouldSort: false
            });

            // Add change listener - updates stats, table, and all fiscal year dependent features
            select.addEventListener('change', (e) => {
               State.selectedFiscalYearId = e.target.value ? parseInt(e.target.value) : null;
               loadStats();
               initTable(); // Reload table with new fiscal year filter
            });
         } catch (error) {
            console.error('Load fiscal years error:', error);
         }
      }

      function initTable(filters = {}) {
         let url = `${Config.API_BASE_URL}/contribution/all`;
         const params = new URLSearchParams();

         // Always include the selected fiscal year
         if (State.selectedFiscalYearId) {
            params.append('fiscal_year_id', State.selectedFiscalYearId);
         }

         if (filters.contribution_type_id) params.append('contribution_type_id', filters.contribution_type_id);
         if (filters.start_date) params.append('start_date', filters.start_date);
         if (filters.end_date) params.append('end_date', filters.end_date);
         if (params.toString()) url += '?' + params.toString();

         State.contributionsTable = QMGridHelper.init('#contributionsTable', {
            url: url,
            pageSize: 25,
            selectable: false,
            exportable: true,
            columns: [{
                  key: 'MbrFirstName',
                  title: 'Member',
                  render: (value, row) => `
                  <div class="d-flex align-items-center">
                     <div class="rounded-circle bg-primary bg-opacity-25 text-primary d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;font-size:0.85rem;">
                        ${(row.MbrFirstName?.[0] || '') + (row.MbrFamilyName?.[0] || '')}
                     </div>
                     <div>
                        <div class="fw-medium">${row.MbrFirstName || ''} ${row.MbrFamilyName || ''}</div>
                     </div>
                  </div>`
               },
               {
                  key: 'ContributionAmount',
                  title: 'Amount',
                  render: (value) => `<span class="fw-semibold text-success">${formatCurrency(value)}</span>`
               },
               {
                  key: 'ContributionDate',
                  title: 'Date',
                  render: (value) => QMGridHelper.formatDate(value, 'short')
               },
               {
                  key: 'ContributionTypeName',
                  title: 'Type',
                  render: (value) => value ? `<span class="badge bg-secondary">${value}</span>` : '-'
               },
               {
                  key: 'PaymentOptionName',
                  title: 'Payment',
                  render: (value) => value || '-'
               },
               {
                  key: 'ContributionID',
                  title: 'Actions',
                  width: '180px',
                  sortable: false,
                  exportable: false,
                  render: (value, row) => {
                     const isDeleted = row.Deleted == 1;
                     if (isDeleted) {
                        return `
                        <div class="btn-group btn-group-sm">
                           <button class="btn btn-success btn-sm" onclick="restoreContribution(${value})" title="Restore">
                              <i class="bi bi-arrow-counterclockwise"></i> Restore
                           </button>
                           <span class="badge bg-danger ms-2">Deleted</span>
                        </div>`;
                     }
                     return `
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary btn-sm" onclick="viewContribution(${value})" title="View"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-success btn-sm" onclick="showReceipt(${value})" title="Receipt"><i class="bi bi-receipt"></i></button>
                        <button class="btn btn-warning btn-sm" onclick="editContribution(${value})" title="Edit"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteContribution(${value})" title="Delete"><i class="bi bi-trash"></i></button>
                     </div>`;
                  }
               }
            ],
            onDataLoaded: (data) => {
               document.getElementById('totalContributionsCount').textContent = data.pagination?.total || data.total || 0;
            }
         });
      }

      async function loadStats() {
         try {
            // Build URL with optional fiscal year filter
            let url = 'contribution/stats';
            if (State.selectedFiscalYearId) {
               url += `?fiscal_year_id=${State.selectedFiscalYearId}`;
            }

            const stats = await api.get(url);
            State.statsData = stats;

            renderStatsCards(stats);
            renderTopContributors(stats.top_contributors || []);
            renderByTypeChart(stats.by_type || []);
            renderMonthlyTrendChart(stats.monthly_trend || []);
         } catch (error) {
            console.error('Load stats error:', error);
            renderStatsCards({});
            renderTopContributors([]);
         }
      }

      function renderStatsCards(stats) {
         const fyName = stats.fiscal_year?.name || 'Fiscal Year';
         const fyStatus = stats.fiscal_year?.status;
         const statusBadge = fyStatus === 'Closed' ? ' <span class="badge bg-secondary small">Closed</span>' : '';

         const row1Cards = [{
               title: `Total ${statusBadge}`,
               value: formatCurrency(stats.total_amount || 0),
               subtitle: `${(stats.total_count || 0).toLocaleString()} contributions`,
               icon: 'cash-stack',
               color: 'primary'
            },
            {
               title: 'This Month',
               value: formatCurrency(stats.month_total || 0),
               subtitle: `<span class="badge bg-${(stats.month_growth || 0) >= 0 ? 'success' : 'danger'}">${(stats.month_growth || 0) >= 0 ? '+' : ''}${stats.month_growth || 0}%</span> vs last month`,
               icon: 'calendar-check',
               color: 'success'
            },
            {
               title: 'This Week',
               value: formatCurrency(stats.week_total || 0),
               subtitle: `${(stats.week_count || 0).toLocaleString()} contributions`,
               icon: 'calendar-week',
               color: 'info'
            },
            {
               title: 'Today',
               value: formatCurrency(stats.today_total || 0),
               subtitle: `${(stats.today_count || 0).toLocaleString()} contributions`,
               icon: 'calendar-day',
               color: 'warning'
            }
         ];

         const row2Cards = [{
               title: 'Average Contribution',
               value: formatCurrency(stats.average_amount || 0),
               subtitle: 'Per transaction',
               icon: 'calculator',
               color: 'secondary'
            },
            {
               title: 'Avg Per Contributor',
               value: formatCurrency(stats.average_per_contributor || 0),
               subtitle: 'Per member',
               icon: 'person-check',
               color: 'dark'
            },
            {
               title: 'Unique Contributors',
               value: (stats.unique_contributors || 0).toLocaleString(),
               subtitle: 'Active givers',
               icon: 'people',
               color: 'primary'
            },
            {
               title: 'Last Month',
               value: formatCurrency(stats.last_month_total || 0),
               subtitle: 'Previous month total',
               icon: 'calendar-minus',
               color: 'secondary'
            }
         ];

         document.getElementById('statsCardsRow1').innerHTML = row1Cards.map(card => renderStatCard(card)).join('');
         document.getElementById('statsCardsRow2').innerHTML = row2Cards.map(card => renderStatCard(card)).join('');
      }

      function renderStatCard(card) {
         return `
         <div class="col-lg-3 col-md-6">
            <div class="card stat-card bg-${card.color} bg-opacity-10 mb-3">
               <div class="card-body py-3">
                  <div class="d-flex justify-content-between align-items-start">
                     <div>
                        <p class="text-muted mb-1 small">${card.title}</p>
                        <h4 class="mb-0">${card.value}</h4>
                        <small class="text-muted">${card.subtitle}</small>
                     </div>
                     <div class="stat-icon bg-${card.color} text-white rounded-circle">
                        <i class="bi bi-${card.icon}"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>`;
      }

      function renderTopContributors(contributors) {
         const tbody = document.getElementById('topContributorsBody');
         if (contributors.length > 0) {
            tbody.innerHTML = contributors.slice(0, 5).map((c, i) => `
            <tr class="cursor-pointer" onclick="showMemberStatement(${c.MbrID})">
               <td class="text-center">
                  ${i === 0 ? '<i class="bi bi-trophy-fill text-warning"></i>' : 
                    i === 1 ? '<i class="bi bi-trophy-fill text-secondary"></i>' :
                    i === 2 ? '<i class="bi bi-trophy-fill" style="color:#cd7f32;"></i>' : 
                    `<span class="text-muted">${i + 1}</span>`}
               </td>
               <td>
                  <div class="fw-medium">${c.MbrFirstName} ${c.MbrFamilyName}</div>
                  <small class="text-muted">${c.contribution_count} contributions</small>
               </td>
               <td class="text-end fw-semibold text-success">${formatCurrency(parseFloat(c.total))}</td>
            </tr>
         `).join('');
         } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>';
         }
      }

      function renderByTypeChart(byType) {
         const ctx = document.getElementById('byTypeChart').getContext('2d');

         if (State.byTypeChart) State.byTypeChart.destroy();

         const colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d'];

         State.byTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
               labels: byType.map(t => t.ContributionTypeName),
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

         State.monthlyTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
               labels: monthlyTrend.map(m => m.month_label),
               datasets: [{
                  label: 'Contributions',
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
                        callback: (value) => formatCurrencyShort(value)
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
            const [membersRes, typesRes, paymentRes, fiscalRes] = await Promise.all([
               api.get('member/all?limit=1000'),
               api.get('contribution/types'),
               api.get('contribution/payment-options'),
               api.get('fiscalyear/all?limit=20')
            ]);

            State.membersData = Array.isArray(membersRes) ? membersRes : (membersRes?.data || []);
            State.typesData = Array.isArray(typesRes) ? typesRes : (typesRes?.data || []);
            State.paymentOptionsData = Array.isArray(paymentRes) ? paymentRes : (paymentRes?.data || []);
            State.fiscalYearsData = Array.isArray(fiscalRes) ? fiscalRes : (fiscalRes?.data || []);

            // Populate filter type dropdown (not using Choices.js)
            const filterTypeSelect = document.getElementById('filterType');
            filterTypeSelect.innerHTML = '<option value="">All Types</option>';
            State.typesData.forEach(t => {
               filterTypeSelect.innerHTML += `<option value="${t.ContributionTypeID}">${t.ContributionTypeName}</option>`;
            });
         } catch (error) {
            console.error('Load dropdowns error:', error);
         }
      }

      function initEventListeners() {
         document.getElementById('addContributionBtn')?.addEventListener('click', () => {
            if (!Auth.hasPermission('create_contribution')) {
               Alerts.error('You do not have permission to record contributions');
               return;
            }
            openContributionModal();
         });

         document.getElementById('saveContributionBtn')?.addEventListener('click', saveContribution);
         document.getElementById('refreshGrid')?.addEventListener('click', () => {
            QMGridHelper.reload(State.contributionsTable);
            loadStats();
         });

         document.getElementById('editContributionFromViewBtn')?.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('viewContributionModal')).hide();
            editContribution(State.currentContributionId);
         });

         document.getElementById('printReceiptBtn')?.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('viewContributionModal')).hide();
            showReceipt(State.currentContributionId);
         });

         document.getElementById('applyFiltersBtn')?.addEventListener('click', applyFilters);
         document.getElementById('clearFiltersBtn')?.addEventListener('click', clearFilters);

         // Contribution Types Management
         document.getElementById('manageTypesBtn')?.addEventListener('click', openTypesModal);
         document.getElementById('addTypeBtn')?.addEventListener('click', addContributionType);
         document.getElementById('saveTypeBtn')?.addEventListener('click', saveEditType);
      }

      // ========== CONTRIBUTION TYPES MANAGEMENT ==========
      async function openTypesModal() {
         new bootstrap.Modal(document.getElementById('typesModal')).show();
         await loadTypesTable();
      }

      async function loadTypesTable() {
         const tbody = document.getElementById('typesBody');
         tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>';

         try {
            const res = await api.get('contribution/types');
            const types = Array.isArray(res) ? res : (res?.data || []);
            State.typesData = types;

            if (types.length === 0) {
               tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No contribution types found</td></tr>';
               return;
            }

            tbody.innerHTML = types.map(t => `
               <tr>
                  <td class="fw-medium">${t.ContributionTypeName}</td>
                  <td class="text-muted">${t.ContributionTypeDescription || '-'}</td>
                  <td>
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning btn-sm" onclick="editContributionType(${t.ContributionTypeID}, '${t.ContributionTypeName.replace(/'/g, "\\'")}', '${(t.ContributionTypeDescription || '').replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteContributionType(${t.ContributionTypeID}, '${t.ContributionTypeName.replace(/'/g, "\\'")}')" title="Delete"><i class="bi bi-trash"></i></button>
                     </div>
                  </td>
               </tr>
            `).join('');

            // Update filter dropdown
            const filterTypeSelect = document.getElementById('filterType');
            filterTypeSelect.innerHTML = '<option value="">All Types</option>';
            types.forEach(t => {
               filterTypeSelect.innerHTML += `<option value="${t.ContributionTypeID}">${t.ContributionTypeName}</option>`;
            });
         } catch (error) {
            console.error('Load types error:', error);
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Failed to load types</td></tr>';
         }
      }

      async function addContributionType() {
         const name = document.getElementById('newTypeName').value.trim();
         const desc = document.getElementById('newTypeDesc').value.trim();

         if (!name) {
            Alerts.warning('Please enter a type name');
            return;
         }

         try {
            Alerts.loading('Adding type...');
            await api.post('contribution/type/create', {
               name,
               description: desc || null
            });
            Alerts.closeLoading();
            Alerts.success('Contribution type added');
            document.getElementById('newTypeName').value = '';
            document.getElementById('newTypeDesc').value = '';
            await loadTypesTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      function editContributionType(id, name, desc) {
         State.editingTypeId = id;
         document.getElementById('editTypeId').value = id;
         document.getElementById('editTypeName').value = name;
         document.getElementById('editTypeDesc').value = desc || '';
         new bootstrap.Modal(document.getElementById('editTypeModal')).show();
      }
      window.editContributionType = editContributionType;

      async function saveEditType() {
         const id = State.editingTypeId;
         const name = document.getElementById('editTypeName').value.trim();
         const desc = document.getElementById('editTypeDesc').value.trim();

         if (!name) {
            Alerts.warning('Please enter a type name');
            return;
         }

         try {
            Alerts.loading('Saving...');
            await api.put(`contribution/type/update/${id}`, {
               name,
               description: desc || null
            });
            Alerts.closeLoading();
            Alerts.success('Contribution type updated');
            bootstrap.Modal.getInstance(document.getElementById('editTypeModal')).hide();
            await loadTypesTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }

      async function deleteContributionType(id, name) {
         const confirmed = await Alerts.confirm(`Delete contribution type "${name}"?`, 'This cannot be undone. Types in use cannot be deleted.');
         if (!confirmed) return;

         try {
            Alerts.loading('Deleting...');
            await api.delete(`contribution/type/delete/${id}`);
            Alerts.closeLoading();
            Alerts.success('Contribution type deleted');
            await loadTypesTable();
         } catch (error) {
            Alerts.closeLoading();
            Alerts.handleApiError(error);
         }
      }
      window.deleteContributionType = deleteContributionType;

      function openContributionModal(contributionId = null) {
         State.isEditMode = !!contributionId;
         State.currentContributionId = contributionId;

         document.getElementById('contributionForm').reset();
         document.getElementById('contributionId').value = '';
         document.getElementById('contributionModalTitle').innerHTML = State.isEditMode ?
            '<i class="bi bi-pencil-square me-2"></i>Edit Contribution' :
            '<i class="bi bi-cash-coin me-2"></i>Record Contribution';

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

         // Initialize Choices.js for contribution type select
         const typeSelect = document.getElementById('contributionType');
         typeSelect.innerHTML = '<option value="">Select Type</option>';
         State.typesData.forEach(t => {
            typeSelect.innerHTML += `<option value="${t.ContributionTypeID}">${t.ContributionTypeName}</option>`;
         });
         if (State.typeChoices) State.typeChoices.destroy();
         State.typeChoices = new Choices(typeSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search types...',
            itemSelectText: '',
            allowHTML: true
         });

         // Initialize Choices.js for payment option select
         const paymentSelect = document.getElementById('paymentOption');
         paymentSelect.innerHTML = '<option value="">Select Method</option>';
         State.paymentOptionsData.forEach(p => {
            paymentSelect.innerHTML += `<option value="${p.PaymentOptionID}">${p.PaymentOptionName}</option>`;
         });
         if (State.paymentChoices) State.paymentChoices.destroy();
         State.paymentChoices = new Choices(paymentSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search methods...',
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

         if (!State.isEditMode) {
            document.getElementById('contributionDate').valueAsDate = new Date();
            const activeYear = State.fiscalYearsData.find(fy => fy.Status === 'Active');
            if (activeYear && State.fiscalYearChoices) {
               State.fiscalYearChoices.setChoiceByValue(String(activeYear.FiscalYearID));
            }
         }

         const modal = new bootstrap.Modal(document.getElementById('contributionModal'));
         modal.show();

         if (State.isEditMode) loadContributionForEdit(contributionId);
      }

      async function loadContributionForEdit(contributionId) {
         try {
            Alerts.loading('Loading contribution...');
            const c = await api.get(`contribution/view/${contributionId}`);
            Alerts.closeLoading();

            document.getElementById('contributionId').value = c.ContributionID;
            document.getElementById('amount').value = c.ContributionAmount;
            document.getElementById('contributionDate').value = c.ContributionDate;
            document.getElementById('description').value = c.Notes || '';

            // Set Choices.js values
            if (State.memberChoices && c.MbrID) {
               State.memberChoices.setChoiceByValue(String(c.MbrID));
            }
            if (State.typeChoices && c.ContributionTypeID) {
               State.typeChoices.setChoiceByValue(String(c.ContributionTypeID));
            }
            if (State.paymentChoices && c.PaymentOptionID) {
               State.paymentChoices.setChoiceByValue(String(c.PaymentOptionID));
            }
            if (State.fiscalYearChoices && c.FiscalYearID) {
               State.fiscalYearChoices.setChoiceByValue(String(c.FiscalYearID));
            }
         } catch (error) {
            Alerts.closeLoading();
            console.error('Load contribution error:', error);
            Alerts.error('Failed to load contribution data');
         }
      }

      async function saveContribution() {
         const memberId = document.getElementById('memberId').value;
         const amount = document.getElementById('amount').value;
         const date = document.getElementById('contributionDate').value;
         const typeId = document.getElementById('contributionType').value;
         const paymentId = document.getElementById('paymentOption').value;
         const fiscalYearId = document.getElementById('fiscalYear').value;

         if (!memberId || !amount || !date || !typeId || !paymentId || !fiscalYearId) {
            Alerts.warning('Please fill all required fields');
            return;
         }

         const payload = {
            member_id: parseInt(memberId),
            amount: parseFloat(amount),
            date: date,
            contribution_type_id: parseInt(typeId),
            payment_option_id: parseInt(paymentId),
            fiscal_year_id: parseInt(fiscalYearId),
            description: document.getElementById('description').value.trim() || null
         };

         try {
            Alerts.loading('Saving contribution...');
            if (State.isEditMode) {
               await api.put(`contribution/update/${State.currentContributionId}`, payload);
            } else {
               await api.post('contribution/create', payload);
            }
            Alerts.closeLoading();
            Alerts.success(State.isEditMode ? 'Contribution updated' : 'Contribution recorded');
            bootstrap.Modal.getInstance(document.getElementById('contributionModal')).hide();
            QMGridHelper.reload(State.contributionsTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            console.error('Save contribution error:', error);
            Alerts.handleApiError(error);
         }
      }

      async function viewContribution(contributionId) {
         State.currentContributionId = contributionId;
         const modal = new bootstrap.Modal(document.getElementById('viewContributionModal'));
         modal.show();

         try {
            const c = await api.get(`contribution/view/${contributionId}`);

            document.getElementById('viewContributionContent').innerHTML = `
            <div class="contribution-view">
               <div class="text-center py-4" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                  <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                     <i class="bi bi-cash-coin text-success" style="font-size:2rem;"></i>
                  </div>
                  <h2 class="text-white mb-1">${formatCurrency(c.ContributionAmount)}</h2>
                  <p class="text-white-50 mb-0">${c.ContributionTypeName || 'Contribution'}</p>
               </div>
               <div class="p-4">
                  <div class="row g-3 mb-3">
                     <div class="col-6">
                        <div class="text-muted small text-uppercase">Member</div>
                        <div class="fw-semibold">${c.MbrFirstName} ${c.MbrFamilyName}</div>
                     </div>
                     <div class="col-6">
                        <div class="text-muted small text-uppercase">Date</div>
                        <div class="fw-semibold">${new Date(c.ContributionDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                     </div>
                  </div>
                  <div class="row g-3">
                     <div class="col-6">
                        <div class="text-muted small text-uppercase">Payment Method</div>
                        <div>${c.PaymentOptionName || '-'}</div>
                     </div>
                     <div class="col-6">
                        <div class="text-muted small text-uppercase">Fiscal Year</div>
                        <div>${c.FiscalYearName || '-'}</div>
                     </div>
                  </div>
                  ${c.Notes ? `
                  <div class="mt-3 pt-3 border-top">
                     <div class="text-muted small text-uppercase">Description</div>
                     <div>${c.Notes}</div>
                  </div>
                  ` : ''}
               </div>
            </div>
         `;
         } catch (error) {
            console.error('View contribution error:', error);
            document.getElementById('viewContributionContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load contribution details</p>
            </div>
         `;
         }
      }
      window.viewContribution = viewContribution;

      async function showReceipt(contributionId) {
         const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
         modal.show();

         try {
            const receipt = await api.get(`contribution/receipt/${contributionId}`);

            document.getElementById('receiptContent').innerHTML = `
            <div id="receiptPrintArea" class="receipt-container p-4">
               <div class="text-center mb-4">
                  <h4 class="mb-1">${receipt.church.name}</h4>
                  <p class="text-muted mb-0 small">${receipt.church.address || ''}</p>
                  ${receipt.church.phone ? `<p class="text-muted mb-0 small">Tel: ${receipt.church.phone}</p>` : ''}
               </div>
               
               <div class="text-center mb-4">
                  <h5 class="text-uppercase mb-1">Contribution Receipt</h5>
                  <p class="text-muted mb-0"><strong>Receipt #:</strong> ${receipt.receipt_number}</p>
               </div>

               <hr>

               <div class="row mb-3">
                  <div class="col-6">
                     <p class="mb-1"><strong>Received From:</strong></p>
                     <p class="mb-0">${receipt.member.name}</p>
                  </div>
                  <div class="col-6 text-end">
                     <p class="mb-1"><strong>Date:</strong></p>
                     <p class="mb-0">${new Date(receipt.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                  </div>
               </div>

               <div class="table-responsive mb-3">
                  <table class="table table-bordered mb-0">
                     <thead class="table-light">
                        <tr>
                           <th>Description</th>
                           <th class="text-end" style="width:150px">Amount</th>
                        </tr>
                     </thead>
                     <tbody>
                        <tr>
                           <td>
                              ${receipt.type}
                              ${receipt.description ? `<br><small class="text-muted">${receipt.description}</small>` : ''}
                           </td>
                           <td class="text-end fw-bold">${formatCurrency(receipt.amount)}</td>
                        </tr>
                     </tbody>
                     <tfoot class="table-light">
                        <tr>
                           <th>Total</th>
                           <th class="text-end">${formatCurrency(receipt.amount)}</th>
                        </tr>
                     </tfoot>
                  </table>
               </div>

               <div class="row mb-4">
                  <div class="col-6">
                     <p class="mb-1 small"><strong>Payment Method:</strong> ${receipt.payment_method}</p>
                     <p class="mb-0 small"><strong>Fiscal Year:</strong> ${receipt.fiscal_year || '-'}</p>
                  </div>
               </div>

               <hr>

               <div class="row mt-4">
                  <div class="col-6">
                     <p class="mb-0 small text-muted">Received By: _____________________</p>
                  </div>
                  <div class="col-6 text-end">
                     <p class="mb-0 small text-muted">Signature: _____________________</p>
                  </div>
               </div>

               <div class="text-center mt-4">
                  <p class="mb-0 small text-muted">Thank you for your generous contribution!</p>
                  <p class="mb-0 small text-muted">Generated: ${new Date(receipt.generated_at).toLocaleString()}</p>
               </div>
            </div>
         `;
         } catch (error) {
            console.error('Load receipt error:', error);
            document.getElementById('receiptContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load receipt</p>
            </div>
         `;
         }
      }
      window.showReceipt = showReceipt;

      async function showMemberStatement(memberId) {
         const modal = new bootstrap.Modal(document.getElementById('statementModal'));
         modal.show();

         try {
            // Include selected fiscal year in statement request
            let url = `contribution/statement/${memberId}`;
            if (State.selectedFiscalYearId) {
               url += `?fiscal_year_id=${State.selectedFiscalYearId}`;
            }
            const statement = await api.get(url);

            document.getElementById('statementContent').innerHTML = `
            <div id="statementPrintArea" class="statement-container p-4">
               <div class="row mb-4">
                  <div class="col-6">
                     <h4 class="mb-1">${statement.church.name}</h4>
                     <p class="text-muted mb-0 small">${statement.church.address || ''}</p>
                     ${statement.church.phone ? `<p class="text-muted mb-0 small">Tel: ${statement.church.phone}</p>` : ''}
                  </div>
                  <div class="col-6 text-end">
                     <h5 class="text-uppercase mb-1">Contribution Statement</h5>
                     <p class="text-muted mb-0 small"><strong>Statement #:</strong> ${statement.statement_number}</p>
                     <p class="text-muted mb-0 small"><strong>Fiscal Year:</strong> ${statement.fiscal_year?.name || 'All Time'}</p>
                  </div>
               </div>

               <div class="card mb-4">
                  <div class="card-body py-2">
                     <div class="row">
                        <div class="col-md-6">
                           <p class="mb-0"><strong>Member:</strong> ${statement.member.name}</p>
                           ${statement.member.email ? `<p class="mb-0 small text-muted">${statement.member.email}</p>` : ''}
                        </div>
                        <div class="col-md-6 text-md-end">
                           <p class="mb-0"><strong>Total Contributions:</strong> <span class="text-success fs-5">${formatCurrency(statement.grand_total)}</span></p>
                           <p class="mb-0 small text-muted">${statement.contribution_count} contributions</p>
                        </div>
                     </div>
                  </div>
               </div>

               <h6 class="mb-3">Summary by Type</h6>
               <div class="table-responsive mb-4">
                  <table class="table table-sm table-bordered mb-0">
                     <thead class="table-light">
                        <tr>
                           <th>Contribution Type</th>
                           <th class="text-center">Count</th>
                           <th class="text-end">Total</th>
                        </tr>
                     </thead>
                     <tbody>
                        ${statement.totals_by_type.map(t => `
                           <tr>
                              <td>${t.ContributionTypeName}</td>
                              <td class="text-center">${t.count}</td>
                              <td class="text-end fw-semibold">${formatCurrency(parseFloat(t.total))}</td>
                           </tr>
                        `).join('')}
                     </tbody>
                     <tfoot class="table-light">
                        <tr>
                           <th>Grand Total</th>
                           <th class="text-center">${statement.contribution_count}</th>
                           <th class="text-end">${formatCurrency(statement.grand_total)}</th>
                        </tr>
                     </tfoot>
                  </table>
               </div>

               <h6 class="mb-3">Contribution Details</h6>
               <div class="table-responsive">
                  <table class="table table-sm table-striped table-bordered mb-0">
                     <thead class="table-light">
                        <tr>
                           <th>Date</th>
                           <th>Type</th>
                           <th>Payment Method</th>
                           <th class="text-end">Amount</th>
                        </tr>
                     </thead>
                     <tbody>
                        ${statement.contributions.map(c => `
                           <tr>
                              <td>${new Date(c.ContributionDate).toLocaleDateString()}</td>
                              <td>${c.ContributionTypeName}</td>
                              <td>${c.PaymentOptionName}</td>
                              <td class="text-end">${formatCurrency(parseFloat(c.ContributionAmount))}</td>
                           </tr>
                        `).join('')}
                     </tbody>
                  </table>
               </div>

               <div class="text-center mt-4">
                  <p class="mb-0 small text-muted">Generated: ${new Date(statement.generated_at).toLocaleString()}</p>
               </div>
            </div>
         `;
         } catch (error) {
            console.error('Load statement error:', error);
            document.getElementById('statementContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load statement</p>
            </div>
         `;
         }
      }
      window.showMemberStatement = showMemberStatement;

      function printReceipt() {
         const content = document.getElementById('receiptPrintArea');
         if (!content) return;

         const printWindow = window.open('', '_blank');
         printWindow.document.write(`
         <!DOCTYPE html>
         <html>
         <head>
            <title>Contribution Receipt</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
               body { padding: 20px; }
               @media print {
                  body { padding: 0; }
                  .no-print { display: none; }
               }
            </style>
         </head>
         <body>
            ${content.innerHTML}
            <script>window.onload = function() { window.print(); }<\/script>
         </body>
         </html>
      `);
         printWindow.document.close();
      }
      window.printReceipt = printReceipt;

      function printStatement() {
         const content = document.getElementById('statementPrintArea');
         if (!content) return;

         const printWindow = window.open('', '_blank');
         printWindow.document.write(`
         <!DOCTYPE html>
         <html>
         <head>
            <title>Contribution Statement</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
               body { padding: 20px; }
               @media print {
                  body { padding: 0; }
                  .no-print { display: none; }
               }
            </style>
         </head>
         <body>
            ${content.innerHTML}
            <script>window.onload = function() { window.print(); }<\/script>
         </body>
         </html>
      `);
         printWindow.document.close();
      }
      window.printStatement = printStatement;

      function editContribution(contributionId) {
         if (!Auth.hasPermission('edit_contribution')) {
            Alerts.error('You do not have permission to edit contributions');
            return;
         }
         openContributionModal(contributionId);
      }
      window.editContribution = editContribution;

      async function deleteContribution(contributionId) {
         if (!Auth.hasPermission('delete_contribution')) {
            Alerts.error('You do not have permission to delete contributions');
            return;
         }

         const confirmed = await Alerts.confirm({
            title: 'Delete Contribution',
            text: 'Are you sure you want to delete this contribution record? You can restore it later if needed.',
            icon: 'warning',
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc3545'
         });

         if (!confirmed) return;

         try {
            Alerts.loading('Deleting contribution...');
            await api.delete(`contribution/delete/${contributionId}`);
            Alerts.closeLoading();
            Alerts.success('Contribution deleted successfully. You can restore it from the deleted items view.');
            QMGridHelper.reload(State.contributionsTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            console.error('Delete contribution error:', error);
            Alerts.handleApiError(error);
         }
      }
      window.deleteContribution = deleteContribution;

      async function restoreContribution(contributionId) {
         if (!Auth.hasPermission('delete_contribution')) {
            Alerts.error('You do not have permission to restore contributions');
            return;
         }

         const confirmed = await Alerts.confirm({
            title: 'Restore Contribution',
            text: 'Are you sure you want to restore this contribution record?',
            icon: 'question',
            confirmButtonText: 'Yes, restore',
            confirmButtonColor: '#198754'
         });

         if (!confirmed) return;

         try {
            Alerts.loading('Restoring contribution...');
            await api.post(`contribution/restore/${contributionId}`);
            Alerts.closeLoading();
            Alerts.success('Contribution restored successfully');
            QMGridHelper.reload(State.contributionsTable);
            loadStats();
         } catch (error) {
            Alerts.closeLoading();
            console.error('Restore contribution error:', error);
            Alerts.handleApiError(error);
         }
      }
      window.restoreContribution = restoreContribution;

      function applyFilters() {
         const filters = {
            contribution_type_id: document.getElementById('filterType').value,
            start_date: document.getElementById('filterStartDate').value,
            end_date: document.getElementById('filterEndDate').value,
            include_deleted: document.getElementById('showDeletedCheckbox').checked ? '1' : '0'
         };
         Object.keys(filters).forEach(k => !filters[k] && delete filters[k]);
         initTable(filters);
      }

      function clearFilters() {
         document.getElementById('filterType').value = '';
         document.getElementById('filterStartDate').value = '';
         document.getElementById('filterEndDate').value = '';
         document.getElementById('showDeletedCheckbox').checked = false;
         initTable();
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

   .contribution-view {
      border-radius: 0;
   }

   .cursor-pointer {
      cursor: pointer;
   }

   .cursor-pointer:hover {
      background-color: rgba(0, 0, 0, 0.02);
   }

   .receipt-container,
   .statement-container {
      background: white;
   }

   @media print {

      .modal-footer,
      .modal-header .btn-close {
         display: none;
      }
   }
</style>

<?php require_once '../includes/footer.php'; ?>