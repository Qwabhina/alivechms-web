<?php
$pageTitle = 'Pledges Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Pledges</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Pledges</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addPledgeBtn" data-permission="create_pledge">
         <i class="bi bi-plus-circle me-2"></i>Create Pledge
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Total Pledges</p>
                     <h3 class="mb-0" id="totalPledges">-</h3>
                     <small class="text-muted"><span id="pledgeCount">0</span> pledges</small>
                  </div>
                  <div class="stat-icon bg-primary text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-clipboard-check"></i>
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
                     <p class="text-muted mb-1">Fulfilled</p>
                     <h3 class="mb-0" id="fulfilledAmount">-</h3>
                     <small class="text-muted"><span id="fulfilledCount">0</span> pledges</small>
                  </div>
                  <div class="stat-icon bg-success text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-check-circle"></i>
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
                     <p class="text-muted mb-1">Active</p>
                     <h3 class="mb-0" id="activeAmount">-</h3>
                     <small class="text-muted"><span id="activeCount">0</span> pledges</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-hourglass-split"></i>
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
                     <p class="text-muted mb-1">Outstanding</p>
                     <h3 class="mb-0" id="outstandingAmount">-</h3>
                     <small class="text-muted">Balance remaining</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-exclamation-triangle"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Pledges Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>All Pledges</h5>
      </div>
      <div class="card-body">
         <table id="pledgesTable" class="table table-striped table-hover" style="width:100%">
            <thead>
               <tr>
                  <th>Member</th>
                  <th>Type</th>
                  <th>Amount</th>
                  <th>Pledge Date</th>
                  <th>Due Date</th>
                  <th>Status</th>
                  <th class="no-export">Actions</th>
               </tr>
            </thead>
            <tbody></tbody>
         </table>
      </div>
   </div>
</div>
</main>

<!-- Pledge Modal -->
<div class="modal fade" id="pledgeModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="pledgeModalTitle">Create Pledge</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="pledgeForm">
               <input type="hidden" id="pledgeId">
               <div class="mb-3">
                  <label class="form-label">Member <span class="text-danger">*</span></label>
                  <select class="form-select" id="memberId" required>
                     <option value="">Select Member</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Pledge Type <span class="text-danger">*</span></label>
                  <select class="form-select" id="pledgeType" required>
                     <option value="">Select Type</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Amount (<span id="currencySymbol"></span>) <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="amount" step="0.01" min="0.01" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Pledge Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="pledgeDate" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Due Date</label>
                  <input type="date" class="form-control" id="dueDate">
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
            <button type="button" class="btn btn-primary" id="savePledgeBtn">
               <i class="bi bi-check-circle me-1"></i>Save Pledge
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let pledgesTable = null;
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
      initTable();
      initEventListeners();
      await loadDropdowns();
      loadStats();
      document.getElementById('pledgeDate').valueAsDate = new Date();
   }

   function initTable() {
      pledgesTable = QMGridHelper.initWithButtons('#pledgesTable', {
         ajax: {
            url: `${Config.API_BASE_URL}/pledge/all`,
            type: 'GET',
            data: function(d) {
               return {
                  page: Math.floor(d.start / d.length) + 1,
                  limit: d.length,
                  search: d.search.value || ''
               };
            },
            dataFilter: function(data) {
               return QMGridHelper.processServerResponse(data, function(p) {
                  return {
                     member: `${p.MbrFirstName} ${p.MbrFamilyName}`,
                     type: p.PledgeTypeName,
                     amount: parseFloat(p.PledgeAmount),
                     date: p.PledgeDate,
                     due_date: p.DueDate,
                     status: p.PledgeStatus,
                     id: p.PledgeID
                  };
               });
            }
         },
         columns: [{
               data: 'member',
               title: 'Member'
            },
            {
               data: 'type',
               title: 'Type'
            },
            {
               data: 'amount',
               title: 'Amount',
               render: function(data) {
                  return formatCurrency(parseFloat(data));
               }
            },
            {
               data: 'date',
               title: 'Pledge Date',
               render: function(data) {
                  return QMGridHelper.formatDate(data);
               }
            },
            {
               data: 'due_date',
               title: 'Due Date',
               render: function(data) {
                  return data ? QMGridHelper.formatDate(data) : 'N/A';
               }
            },
            {
               data: 'status',
               title: 'Status',
               render: function(data) {
                  const badges = {
                     'Active': 'warning',
                     'Fulfilled': 'success',
                     'Cancelled': 'danger'
                  };
                  return `<span class="badge bg-${badges[data] || 'secondary'}">${data}</span>`;
               }
            },
            {
               data: 'id',
               title: 'Actions',
               orderable: false,
               searchable: false,
               className: 'no-export',
               render: function(data) {
                  return `
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewPledge(${data})" title="View">
                           <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning" onclick="editPledge(${data})" title="Edit">
                           <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="recordPayment(${data})" title="Record Payment">
                           <i class="bi bi-cash"></i>
                        </button>
                     </div>
                  `;
               }
            }
         ],
         order: [
            [3, 'desc']
         ]
      });
   }

   async function loadStats() {
      try {
         const response = await api.get('pledge/all?limit=1000');
         const pledges = response?.data?.data || response?.data || [];

         let totalAmount = 0,
            totalCount = 0;
         let fulfilledAmount = 0,
            fulfilledCount = 0;
         let activeAmount = 0,
            activeCount = 0;
         let outstandingAmount = 0;

         pledges.forEach(p => {
            const amount = parseFloat(p.PledgeAmount);
            totalAmount += amount;
            totalCount++;

            if (p.PledgeStatus === 'Fulfilled') {
               fulfilledAmount += amount;
               fulfilledCount++;
            } else if (p.PledgeStatus === 'Active') {
               activeAmount += amount;
               activeCount++;
               outstandingAmount += amount;
            }
         });

         document.getElementById('totalPledges').textContent = formatCurrencyLocale(totalAmount);
         document.getElementById('pledgeCount').textContent = totalCount;
         document.getElementById('fulfilledAmount').textContent = formatCurrencyLocale(fulfilledAmount);
         document.getElementById('fulfilledCount').textContent = fulfilledCount;
         document.getElementById('activeAmount').textContent = formatCurrencyLocale(activeAmount);
         document.getElementById('activeCount').textContent = activeCount;
         document.getElementById('outstandingAmount').textContent = formatCurrencyLocale(outstandingAmount);
      } catch (error) {
         console.error('Load stats error:', error);
      }
   }

   async function loadDropdowns() {
      try {
         const [membersRes, typesRes, fiscalRes] = await Promise.all([
            api.get('member/all?limit=1000'),
            api.get('pledge/types'),
            api.get('fiscalyear/all?limit=10')
         ]);

         const members = membersRes?.data?.data || membersRes?.data || [];
         const types = typesRes?.data || [];
         const fiscalYears = fiscalRes?.data?.data || fiscalRes?.data || [];

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

         const typeSelect = document.getElementById('pledgeType');
         typeSelect.innerHTML = '<option value="">Select Type</option>';
         types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.PledgeTypeID;
            opt.textContent = t.PledgeTypeName;
            typeSelect.appendChild(opt);
         });

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

   function initEventListeners() {
      document.getElementById('addPledgeBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('create_pledge')) {
            Alerts.error('You do not have permission to create pledges');
            return;
         }
         openPledgeModal();
      });

      document.getElementById('savePledgeBtn').addEventListener('click', savePledge);
   }

   function openPledgeModal() {
      document.getElementById('pledgeForm').reset();
      document.getElementById('pledgeModalTitle').textContent = 'Create Pledge';
      document.getElementById('pledgeDate').valueAsDate = new Date();
      const modal = new bootstrap.Modal(document.getElementById('pledgeModal'));
      modal.show();
   }

   async function savePledge() {
      const memberId = document.getElementById('memberId').value;
      const typeId = document.getElementById('pledgeType').value;
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
         await api.post('pledge/create', payload);
         Alerts.closeLoading();
         Alerts.success('Pledge created successfully');
         bootstrap.Modal.getInstance(document.getElementById('pledgeModal')).hide();
         QMGridHelper.reload(pledgesTable);
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save pledge error:', error);
         Alerts.handleApiError(error);
      }
   }

   function viewPledge(pledgeId) {
      window.location.href = `pledge-details.php?id=${pledgeId}`;
   }

   function editPledge(pledgeId) {
      Alerts.info('Edit functionality coming soon');
   }

   function recordPayment(pledgeId) {
      Alerts.info('Payment recording coming soon');
   }
</script>

<?php require_once '../includes/footer.php'; ?>