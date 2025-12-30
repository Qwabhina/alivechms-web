<?php
$pageTitle = 'Contributions Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Contributions</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Contributions</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addContributionBtn" data-permission="create_contribution">
         <i class="bi bi-plus-circle me-2"></i>Record Contribution
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Total Contributions</p>
                     <h3 class="mb-0" id="totalAmount">-</h3>
                     <small class="text-muted"><span id="totalCount">0</span> records</small>
                  </div>
                  <div class="stat-icon bg-primary text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-cash-stack"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-success bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">This Month</p>
                     <h3 class="mb-0" id="monthAmount">-</h3>
                     <small class="text-muted">
                        <span id="monthGrowth" class="badge bg-success">+0%</span> vs last month
                     </small>
                  </div>
                  <div class="stat-icon bg-success text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-calendar-check"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-info bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">This Year</p>
                     <h3 class="mb-0" id="yearAmount">-</h3>
                     <small class="text-muted"><span id="yearCount">0</span> contributions</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-graph-up-arrow"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-warning bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Average</p>
                     <h3 class="mb-0" id="avgAmount">-</h3>
                     <small class="text-muted">Per contribution</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-calculator"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Filters and Charts Row -->
   <div class="row mb-4">
      <!-- Filters Card -->
      <div class="col-lg-4 mb-3">
         <div class="card">
            <div class="card-header">
               <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
            </div>
            <div class="card-body">
               <div class="mb-3">
                  <label class="form-label small">Contribution Type</label>
                  <select class="form-select form-select-sm" id="filterType">
                     <option value="">All Types</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label small">Date Range</label>
                  <div class="row g-2">
                     <div class="col-6">
                        <input type="date" class="form-control form-control-sm" id="filterStartDate" placeholder="Start">
                     </div>
                     <div class="col-6">
                        <input type="date" class="form-control form-control-sm" id="filterEndDate" placeholder="End">
                     </div>
                  </div>
               </div>
               <div class="d-grid gap-2">
                  <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                     <i class="bi bi-search me-1"></i>Apply Filters
                  </button>
                  <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                     <i class="bi bi-x-circle me-1"></i>Clear
                  </button>
               </div>
            </div>
         </div>
      </div>

      <!-- Top Contributors Card -->
      <div class="col-lg-8 mb-3">
         <div class="card">
            <div class="card-header">
               <h6 class="mb-0"><i class="bi bi-trophy me-2"></i>Top Contributors (This Year)</h6>
            </div>
            <div class="card-body">
               <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0">
                     <thead class="table-light">
                        <tr>
                           <th>Rank</th>
                           <th>Member</th>
                           <th class="text-end">Total Amount</th>
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

   <!-- Contributions Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>All Contributions</h5>
      </div>
      <div class="card-body">
         <div class="d-flex justify-content-end mb-3 gap-2">
            <button class="btn btn-success btn-sm" onclick="contributionsGrid.download('xlsx', 'contributions.xlsx')">
               <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="contributionsGrid.download('pdf', 'contributions.pdf', {orientation:'landscape', title:'Contributions Report'})">
               <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
            <button class="btn btn-primary btn-sm" onclick="contributionsGrid.print()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-secondary btn-sm" onclick="contributionsGrid.setData()">
               <i class="bi bi-arrow-clockwise"></i>
            </button>
         </div>
         <div class="table-responsive">
            <div id="contributionsGrid"></div>
         </div>
      </div>
   </div>
</div>
</main>

<!-- Contribution Modal -->
<div class="modal fade" id="contributionModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="contributionModalTitle">Record Contribution</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="contributionForm">
               <input type="hidden" id="contributionId">
               <div class="mb-3">
                  <label class="form-label">Member <span class="text-danger">*</span></label>
                  <select class="form-select" id="memberId" required>
                     <option value="">Select Member</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Amount (<span id="currencySymbol"></span>) <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="amount" step="0.01" min="0.01" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="contributionDate" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Contribution Type <span class="text-danger">*</span></label>
                  <select class="form-select" id="contributionType" required>
                     <option value="">Select Type</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                  <select class="form-select" id="paymentOption" required>
                     <option value="">Select Method</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                  <select class="form-select" id="fiscalYear" required>
                     <option value="">Select Fiscal Year</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" id="description" rows="2"></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer">
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
         <div class="modal-header">
            <h5 class="modal-title">Contribution Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="viewContributionContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="editContributionFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let contributionsGrid = null;
   let currentContributionId = null;
   let isEditMode = false;
   let memberChoices = null;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;

      // Wait for settings to load
      await Config.waitForSettings();

      // Set currency symbol in form label
      const currencySymbol = Config.getSetting('currency_symbol', 'GH₵');
      document.getElementById('currencySymbol').textContent = currencySymbol;

      await initPage();
   });

   async function initPage() {
      initGrid();
      initEventListeners();
      await loadDropdowns();
      loadStats();

      // Set default date to today
      document.getElementById('contributionDate').valueAsDate = new Date();
   }

   function initGrid() {
      contributionsGrid = new Tabulator("#contributionsGrid", {
         layout: "fitColumns",
         responsiveLayout: "collapse",
         resizableColumns: false,
         pagination: true,
         paginationMode: "remote",
         paginationSize: Config.getSetting('items_per_page', 10),
         paginationSizeSelector: [10, 25, 50, 100],
         ajaxURL: `${Config.API_BASE_URL}/contribution/all`,
         ajaxConfig: {
            headers: {
               'Authorization': `Bearer ${Auth.getToken()}`
            }
         },
         ajaxResponse: function(url, params, response) {
            const data = response?.data?.data || response?.data || [];
            const pagination = response?.data?.pagination || {};
            return {
               last_page: pagination.pages || 1,
               data: data.map(c => ({
                  member: `${c.MbrFirstName} ${c.MbrFamilyName}`,
                  amount: parseFloat(c.ContributionAmount),
                  date: c.ContributionDate,
                  type: c.ContributionTypeName,
                  payment: c.PaymentOptionName,
                  id: c.ContributionID
               }))
            };
         },
         ajaxURLGenerator: function(url, config, params) {
            let queryParams = [];
            if (params.page) queryParams.push(`page=${params.page}`);
            if (params.size) queryParams.push(`limit=${params.size}`);
            return queryParams.length ? `${url}?${queryParams.join('&')}` : url;
         },
         columns: [{
               title: "Member",
               field: "member",
               widthGrow: 2,
               responsive: 0,
               download: true
            },
            {
               title: "Amount",
               field: "amount",
               widthGrow: 1.5,
               responsive: 0,
               download: true,
               formatter: function(cell) {
                  return formatCurrency(parseFloat(cell.getValue()));
               }
            },
            {
               title: "Date",
               field: "date",
               widthGrow: 1.5,
               responsive: 1,
               download: true,
               formatter: function(cell) {
                  return new Date(cell.getValue()).toLocaleDateString();
               }
            },
            {
               title: "Type",
               field: "type",
               widthGrow: 1.5,
               responsive: 2,
               download: true
            },
            {
               title: "Payment Method",
               field: "payment",
               widthGrow: 1.5,
               responsive: 2,
               download: true
            },
            {
               title: "Actions",
               field: "id",
               width: 120,
               headerSort: false,
               responsive: 0,
               download: false,
               formatter: function(cell) {
                  const id = cell.getValue();
                  return `
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewContribution(${id})" title="View">
                           <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning" onclick="editContribution(${id})" title="Edit">
                           <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteContribution(${id})" title="Delete">
                           <i class="bi bi-trash"></i>
                        </button>
                     </div>
                  `;
               }
            }
         ]
      });
   }

   async function loadStats() {
      try {
         const stats = await api.get('contribution/stats');

         // Update stat cards
         document.getElementById('totalAmount').textContent = formatCurrencyLocale(stats.total_amount);
         document.getElementById('totalCount').textContent = stats.total_count.toLocaleString();
         document.getElementById('monthAmount').textContent = formatCurrencyLocale(stats.month_total);
         document.getElementById('yearAmount').textContent = formatCurrencyLocale(stats.year_total);
         document.getElementById('yearCount').textContent = stats.year_count.toLocaleString();
         document.getElementById('avgAmount').textContent = formatCurrencyLocale(stats.average_amount);

         // Update growth badge
         const growthBadge = document.getElementById('monthGrowth');
         const growth = stats.month_growth;
         if (growth > 0) {
            growthBadge.className = 'badge bg-success';
            growthBadge.textContent = `+${growth}%`;
         } else if (growth < 0) {
            growthBadge.className = 'badge bg-danger';
            growthBadge.textContent = `${growth}%`;
         } else {
            growthBadge.className = 'badge bg-secondary';
            growthBadge.textContent = '0%';
         }

         // Update top contributors
         const tbody = document.getElementById('topContributorsBody');
         if (stats.top_contributors && stats.top_contributors.length > 0) {
            tbody.innerHTML = stats.top_contributors.map((contributor, index) => `
               <tr>
                  <td>
                     ${index === 0 ? '<i class="bi bi-trophy-fill text-warning"></i>' : 
                       index === 1 ? '<i class="bi bi-trophy-fill text-secondary"></i>' :
                       index === 2 ? '<i class="bi bi-trophy-fill" style="color: #cd7f32;"></i>' : 
                       `<span class="text-muted">${index + 1}</span>`}
                  </td>
                  <td>${contributor.MbrFirstName} ${contributor.MbrFamilyName}</td>
                  <td class="text-end fw-semibold">${formatCurrencyLocale(parseFloat(contributor.total))}</td>
               </tr>
            `).join('');
         } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>';
         }
      } catch (error) {
         console.error('Load stats error:', error);
         Alerts.error('Failed to load statistics');
      }
   }

   async function loadDropdowns() {
      try {
         // Load members
         const membersResponse = await api.get('member/all?limit=1000');
         const members = membersResponse?.data?.data || membersResponse?.data || [];

         const memberSelect = document.getElementById('memberId');
         memberSelect.innerHTML = '<option value="">Select Member</option>';
         members.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.MbrID;
            opt.textContent = `${m.MbrFirstName} ${m.MbrFamilyName}`;
            memberSelect.appendChild(opt);
         });

         if (memberChoices) memberChoices.destroy();
         memberChoices = new Choices(memberSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search members...',
            itemSelectText: ''
         });

         // Load contribution types
         const typesResponse = await api.get('contribution/types');
         const types = typesResponse?.data || [];

         const typeSelect = document.getElementById('contributionType');
         const filterTypeSelect = document.getElementById('filterType');
         typeSelect.innerHTML = '<option value="">Select Type</option>';
         filterTypeSelect.innerHTML = '<option value="">All Types</option>';

         types.forEach(t => {
            const opt1 = document.createElement('option');
            opt1.value = t.ContributionTypeID;
            opt1.textContent = t.ContributionTypeName;
            typeSelect.appendChild(opt1);

            const opt2 = document.createElement('option');
            opt2.value = t.ContributionTypeID;
            opt2.textContent = t.ContributionTypeName;
            filterTypeSelect.appendChild(opt2);
         });

         // Load payment options
         const paymentResponse = await api.get('contribution/payment-options');
         const payments = paymentResponse?.data || [];

         const paymentSelect = document.getElementById('paymentOption');
         paymentSelect.innerHTML = '<option value="">Select Method</option>';
         payments.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.PaymentOptionID;
            opt.textContent = p.PaymentOptionName;
            paymentSelect.appendChild(opt);
         });

         // Load fiscal years
         const fiscalResponse = await api.get('fiscalyear/all?limit=10');
         const fiscalYears = fiscalResponse?.data?.data || fiscalResponse?.data || [];

         const fiscalSelect = document.getElementById('fiscalYear');
         fiscalSelect.innerHTML = '<option value="">Select Fiscal Year</option>';
         fiscalYears.forEach((fy, index) => {
            const opt = document.createElement('option');
            opt.value = fy.FiscalYearID;
            opt.textContent = fy.FiscalYearName || fy.FiscalYearID;
            if (index === 0 && fy.Status === 'Active') opt.selected = true;
            fiscalSelect.appendChild(opt);
         });
      } catch (error) {
         console.error('Load dropdowns error:', error);
      }
   }
   // const opt = document.createElement('option');
   // opt.value = year;
   // opt.textContent = year;
   // if (i === 0) opt.selected = true;
   // fiscalSelect.appendChild(opt);
   // }

   // catch (error) {
   //    console.error('Load dropdowns error:', error);
   // }


   function initEventListeners() {
      document.getElementById('addContributionBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('create_contribution')) {
            Alerts.error('You do not have permission to record contributions');
            return;
         }
         openContributionModal();
      });

      document.getElementById('saveContributionBtn').addEventListener('click', saveContribution);

      document.getElementById('editContributionFromViewBtn').addEventListener('click', () => {
         bootstrap.Modal.getInstance(document.getElementById('viewContributionModal')).hide();
         editContribution(currentContributionId);
      });
   }

   function openContributionModal(contributionId = null) {
      isEditMode = !!contributionId;
      currentContributionId = contributionId;

      document.getElementById('contributionForm').reset();
      document.getElementById('contributionId').value = '';
      document.getElementById('contributionModalTitle').textContent = isEditMode ? 'Edit Contribution' : 'Record Contribution';

      if (!isEditMode) {
         document.getElementById('contributionDate').valueAsDate = new Date();
      }

      const modal = new bootstrap.Modal(document.getElementById('contributionModal'));
      modal.show();

      if (isEditMode) loadContributionForEdit(contributionId);
   }

   async function loadContributionForEdit(contributionId) {
      try {
         const contribution = await api.get(`contribution/view/${contributionId}`);
         document.getElementById('contributionId').value = contribution.ContributionID;
         document.getElementById('amount').value = contribution.ContributionAmount;
         document.getElementById('contributionDate').value = contribution.ContributionDate;
         document.getElementById('description').value = contribution.Description || '';

         if (memberChoices) {
            memberChoices.setChoiceByValue(contribution.MbrID?.toString() || '');
         }
      } catch (error) {
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
         if (isEditMode) {
            await api.put(`contribution/update/${currentContributionId}`, payload);
         } else {
            await api.post('contribution/create', payload);
         }
         Alerts.closeLoading();
         Alerts.success(isEditMode ? 'Contribution updated successfully' : 'Contribution recorded successfully');
         bootstrap.Modal.getInstance(document.getElementById('contributionModal')).hide();
         contributionsGrid.setData();
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save contribution error:', error);
         Alerts.handleApiError(error);
      }
   }

   async function viewContribution(contributionId) {
      currentContributionId = contributionId;
      const modal = new bootstrap.Modal(document.getElementById('viewContributionModal'));
      modal.show();

      try {
         const contribution = await api.get(`contribution/view/${contributionId}`);

         document.getElementById('viewContributionContent').innerHTML = `
            <div class="mb-3">
               <div class="text-muted small">Member</div>
               <div class="fw-semibold fs-5">${contribution.MbrFirstName} ${contribution.MbrFamilyName}</div>
            </div>
            <div class="mb-3">
               <div class="text-muted small">Amount</div>
               <div class="fw-semibold text-success fs-4">${formatCurrency(parseFloat(contribution.ContributionAmount))}</div>
            </div>
            <div class="row mb-3">
               <div class="col-6">
                  <div class="text-muted small">Date</div>
                  <div>${new Date(contribution.ContributionDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
               </div>
               <div class="col-6">
                  <div class="text-muted small">Type</div>
                  <div>${contribution.ContributionTypeName}</div>
               </div>
            </div>
            <div class="mb-3">
               <div class="text-muted small">Payment Method</div>
               <div>${contribution.PaymentOptionName}</div>
            </div>
            ${contribution.Description ? `
            <div class="mb-3">
               <div class="text-muted small">Description</div>
               <div>${contribution.Description}</div>
            </div>
            ` : ''}
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

   function editContribution(contributionId) {
      if (!Auth.hasPermission('edit_contribution')) {
         Alerts.error('You do not have permission to edit contributions');
         return;
      }
      openContributionModal(contributionId);
   }

   function applyFilters() {
      const typeId = document.getElementById('filterType').value;
      const startDate = document.getElementById('filterStartDate').value;
      const endDate = document.getElementById('filterEndDate').value;

      let url = `${Config.API_BASE_URL}/contribution/all`;
      let params = [];

      if (typeId) params.push(`contribution_type_id=${typeId}`);
      if (startDate) params.push(`start_date=${startDate}`);
      if (endDate) params.push(`end_date=${endDate}`);

      if (params.length > 0) {
         url += '?' + params.join('&');
      }

      contributionsGrid.setData(url);
   }

   function clearFilters() {
      document.getElementById('filterType').value = '';
      document.getElementById('filterStartDate').value = '';
      document.getElementById('filterEndDate').value = '';
      contributionsGrid.setData();
   }

   async function deleteContribution(contributionId) {
      if (!Auth.hasPermission('delete_contribution')) {
         Alerts.error('You do not have permission to delete contributions');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Contribution',
         text: 'Are you sure you want to delete this contribution record?',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting contribution...');
         await api.delete(`contribution/delete/${contributionId}`);
         Alerts.closeLoading();
         Alerts.success('Contribution deleted successfully');
         contributionsGrid.setData();
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete contribution error:', error);
         Alerts.handleApiError(error);
      }
   }
</script>

<?php require_once '../includes/footer.php'; ?>