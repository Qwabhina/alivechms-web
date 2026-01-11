<?php
$pageTitle = 'Fiscal Years';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Fiscal Years</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Fiscal Years</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addFiscalYearBtn" data-permission="manage_fiscal_years">
         <i class="bi bi-plus-circle me-2"></i>Create Fiscal Year
      </button>
   </div>

   <!-- Fiscal Years Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-calendar-range me-2"></i>All Fiscal Years</h5>
      </div>
      <div class="card-body">
         <div id="fiscalYearsTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Fiscal Year Modal -->
<div class="modal fade" id="fiscalYearModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="fiscalYearModalTitle">Create Fiscal Year</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="fiscalYearForm">
               <input type="hidden" id="fiscalYearId">
               <div class="mb-3">
                  <label class="form-label">Start Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="startDate" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">End Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="endDate" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Branch <span class="text-danger">*</span></label>
                  <select class="form-select" id="branchId" required>
                     <option value="">Select Branch</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Status <span class="text-danger">*</span></label>
                  <select class="form-select" id="status" required>
                     <option value="Active">Active</option>
                     <option value="Closed">Closed</option>
                  </select>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveFiscalYearBtn">
               <i class="bi bi-check-circle me-1"></i>Save Fiscal Year
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let fiscalYearsTable = null;
   let currentFiscalYearId = null;
   let isEditMode = false;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;

      // Wait for settings to load
      await Config.waitForSettings();

      await initPage();
   });

   async function initPage() {
      initTable();
      initEventListeners();
      await loadDropdowns();
   }

   async function initTable() {
      fiscalYearsTable = await QMGridHelper.initWithButtons('#fiscalYearsTable', {
         url: `${Config.API_BASE_URL}/fiscalyear/all`,
         pageSize: 10,
         columns: [{
               key: 'FiscalYearStartDate',
               title: 'Start Date',
               render: (data) => QMGridHelper.formatDate(data)
            },
            {
               key: 'FiscalYearEndDate',
               title: 'End Date',
               render: (data) => QMGridHelper.formatDate(data)
            },
            {
               key: 'BranchName',
               title: 'Branch'
            },
            {
               key: 'Status',
               title: 'Status',
               render: (data) => QMGridHelper.statusBadge(data)
            },
            {
               key: 'FiscalYearID',
               title: 'Actions',
               sortable: false,
               className: 'no-export',
               render: (data, row) => {
                  const customButtons = row.Status === 'Active' ? [{
                     icon: 'lock',
                     color: 'info',
                     fn: 'closeFiscalYear',
                     title: 'Close'
                  }] : [];

                  return QMGridHelper.actionButtons(data, {
                     view: false,
                     editFn: 'editFiscalYear',
                     deleteFn: 'deleteFiscalYear',
                     custom: customButtons
                  });
               }
            }
         ]
      });
   }

   async function loadDropdowns() {
      try {
         const response = await api.get('branch/all?limit=100');
         const branches = response?.data?.data || response?.data || [];

         const branchSelect = document.getElementById('branchId');
         branchSelect.innerHTML = '<option value="">Select Branch</option>';
         branches.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.BranchID;
            opt.textContent = b.BranchName;
            branchSelect.appendChild(opt);
         });
      } catch (error) {
         console.error('Load dropdowns error:', error);
      }
   }

   function initEventListeners() {
      document.getElementById('addFiscalYearBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('manage_fiscal_years')) {
            Alerts.error('You do not have permission to create fiscal years');
            return;
         }
         openFiscalYearModal();
      });

      document.getElementById('saveFiscalYearBtn').addEventListener('click', saveFiscalYear);
   }

   function openFiscalYearModal(fiscalYearId = null) {
      isEditMode = !!fiscalYearId;
      currentFiscalYearId = fiscalYearId;

      document.getElementById('fiscalYearForm').reset();
      document.getElementById('fiscalYearId').value = '';
      document.getElementById('fiscalYearModalTitle').textContent = isEditMode ? 'Edit Fiscal Year' : 'Create Fiscal Year';

      const modal = new bootstrap.Modal(document.getElementById('fiscalYearModal'));
      modal.show();

      if (isEditMode) loadFiscalYearForEdit(fiscalYearId);
   }

   async function loadFiscalYearForEdit(fiscalYearId) {
      try {
         const fy = await api.get(`fiscalyear/view/${fiscalYearId}`);
         document.getElementById('fiscalYearId').value = fy.FiscalYearID;
         document.getElementById('startDate').value = fy.FiscalYearStartDate;
         document.getElementById('endDate').value = fy.FiscalYearEndDate;
         document.getElementById('branchId').value = fy.BranchID;
         document.getElementById('status').value = fy.Status;
      } catch (error) {
         console.error('Load fiscal year error:', error);
         Alerts.error('Failed to load fiscal year data');
      }
   }

   async function saveFiscalYear() {
      const startDate = document.getElementById('startDate').value;
      const endDate = document.getElementById('endDate').value;
      const branchId = document.getElementById('branchId').value;
      const status = document.getElementById('status').value;

      if (!startDate || !endDate || !branchId || !status) {
         Alerts.warning('Please fill all required fields');
         return;
      }

      const payload = {
         start_date: startDate,
         end_date: endDate,
         branch_id: parseInt(branchId),
         status: status
      };

      try {
         Alerts.loading('Saving fiscal year...');
         if (isEditMode) {
            await api.put(`fiscalyear/update/${currentFiscalYearId}`, payload);
         } else {
            await api.post('fiscalyear/create', payload);
         }
         Alerts.closeLoading();
         Alerts.success(isEditMode ? 'Fiscal year updated successfully' : 'Fiscal year created successfully');
         bootstrap.Modal.getInstance(document.getElementById('fiscalYearModal')).hide();
         QMGridHelper.reload(fiscalYearsTable);
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save fiscal year error:', error);
         Alerts.handleApiError(error);
      }
   }

   function editFiscalYear(fiscalYearId) {
      if (!Auth.hasPermission('manage_fiscal_years')) {
         Alerts.error('You do not have permission to edit fiscal years');
         return;
      }
      openFiscalYearModal(fiscalYearId);
   }

   async function closeFiscalYear(fiscalYearId) {
      if (!Auth.hasPermission('manage_fiscal_years')) {
         Alerts.error('You do not have permission to close fiscal years');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Close Fiscal Year',
         text: 'Are you sure you want to close this fiscal year? This action cannot be undone.',
         icon: 'warning',
         confirmButtonText: 'Yes, close',
         confirmButtonColor: '#0d6efd'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Closing fiscal year...');
         await api.post(`fiscalyear/close/${fiscalYearId}`);
         Alerts.closeLoading();
         Alerts.success('Fiscal year closed successfully');
         QMGridHelper.reload(fiscalYearsTable);
      } catch (error) {
         Alerts.closeLoading();
         console.error('Close fiscal year error:', error);
         Alerts.handleApiError(error);
      }
   }

   async function deleteFiscalYear(fiscalYearId) {
      if (!Auth.hasPermission('manage_fiscal_years')) {
         Alerts.error('You do not have permission to delete fiscal years');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Fiscal Year',
         text: 'Are you sure you want to delete this fiscal year? This action cannot be undone.',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting fiscal year...');
         await api.delete(`fiscalyear/delete/${fiscalYearId}`);
         Alerts.closeLoading();
         Alerts.success('Fiscal year deleted successfully');
         QMGridHelper.reload(fiscalYearsTable);
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete fiscal year error:', error);
         Alerts.handleApiError(error);
      }
   }
</script>

<?php require_once '../includes/footer.php'; ?>