<?php
$pageTitle = 'Audit Log';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Audit Log</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Audit Log</li>
            </ol>
         </nav>
      </div>
   </div>

   <!-- Filters -->
   <div class="card mb-4">
      <div class="card-header">
         <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
      </div>
      <div class="card-body">
         <div class="row g-3">
            <div class="col-md-2">
               <label class="form-label small">Action</label>
               <select class="form-select form-select-sm" id="filterAction">
                  <option value="">All Actions</option>
                  <option value="create">Create</option>
                  <option value="update">Update</option>
                  <option value="delete">Delete</option>
                  <option value="approve">Approve</option>
                  <option value="reject">Reject</option>
               </select>
            </div>
            <div class="col-md-2">
               <label class="form-label small">Entity Type</label>
               <select class="form-select form-select-sm" id="filterEntity">
                  <option value="">All Entities</option>
                  <option value="member">Member</option>
                  <option value="contribution">Contribution</option>
                  <option value="expense">Expense</option>
                  <option value="budget">Budget</option>
                  <option value="event">Event</option>
                  <option value="pledge">Pledge</option>
               </select>
            </div>
            <div class="col-md-2">
               <label class="form-label small">Start Date</label>
               <input type="date" class="form-control form-control-sm" id="filterStartDate">
            </div>
            <div class="col-md-2">
               <label class="form-label small">End Date</label>
               <input type="date" class="form-control form-control-sm" id="filterEndDate">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
               <button class="btn btn-primary btn-sm flex-grow-1" onclick="applyFilters()">
                  <i class="bi bi-search me-1"></i>Apply
               </button>
               <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                  <i class="bi bi-x-circle"></i>
               </button>
            </div>
         </div>
      </div>
   </div>

   <!-- Audit Log Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Activity Log</h5>
      </div>
      <div class="card-body">
         <div class="d-flex justify-content-end mb-3 gap-2">
            <button class="btn btn-success btn-sm" onclick="auditLogGrid.download('xlsx', 'audit-log.xlsx')">
               <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="auditLogGrid.download('pdf', 'audit-log.pdf', {orientation:'landscape', title:'Audit Log Report'})">
               <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
            <button class="btn btn-primary btn-sm" onclick="auditLogGrid.print()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-secondary btn-sm" onclick="auditLogGrid.setData()">
               <i class="bi bi-arrow-clockwise"></i>
            </button>
         </div>
         <div class="table-responsive">
            <div id="auditLogGrid"></div>
         </div>
      </div>
   </div>
</div>
</main>

<!-- View Log Modal -->
<div class="modal fade" id="viewLogModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Log Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="viewLogContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>

<script>
   let auditLogGrid = null;

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await initPage();
   });

   async function initPage() {
      initGrid();
   }

   function initGrid() {
      auditLogGrid = new Tabulator("#auditLogGrid", {
         layout: "fitColumns",
         responsiveLayout: "collapse",
         resizableColumns: false,
         pagination: true,
         paginationMode: "remote",
         paginationSize: 50,
         paginationSizeSelector: [25, 50, 100, 200],
         ajaxURL: `${Config.API_BASE_URL}/audit/search`,
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
               data: data.map(log => ({
                  user: log.MbrFirstName && log.MbrFamilyName ? `${log.MbrFirstName} ${log.MbrFamilyName}` : 'System',
                  action: log.action,
                  entity: log.entity_type,
                  entity_id: log.entity_id,
                  timestamp: log.created_at,
                  ip: log.ip_address,
                  changes: log.changes,
                  metadata: log.metadata,
                  user_agent: log.user_agent
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
               title: "Timestamp",
               field: "timestamp",
               widthGrow: 1.5,
               responsive: 0,
               download: true,
               formatter: cell => {
                  const date = new Date(cell.getValue());
                  return date.toLocaleString('en-US', {
                     year: 'numeric',
                     month: 'short',
                     day: 'numeric',
                     hour: '2-digit',
                     minute: '2-digit'
                  });
               }
            },
            {
               title: "User",
               field: "user",
               widthGrow: 1.5,
               responsive: 0,
               download: true
            },
            {
               title: "Action",
               field: "action",
               widthGrow: 1,
               responsive: 1,
               download: true,
               formatter: cell => {
                  const action = cell.getValue();
                  const badges = {
                     'create': 'success',
                     'update': 'primary',
                     'delete': 'danger',
                     'approve': 'info',
                     'reject': 'warning'
                  };
                  return `<span class="badge bg-${badges[action] || 'secondary'}">${action}</span>`;
               }
            },
            {
               title: "Entity",
               field: "entity",
               widthGrow: 1,
               responsive: 2,
               download: true
            },
            {
               title: "Entity ID",
               field: "entity_id",
               widthGrow: 0.8,
               responsive: 2,
               download: true
            },
            {
               title: "IP Address",
               field: "ip",
               widthGrow: 1.2,
               responsive: 2,
               download: true
            },
            {
               title: "Actions",
               field: "timestamp",
               width: 80,
               headerSort: false,
               responsive: 0,
               download: false,
               formatter: cell => {
                  const rowData = cell.getRow().getData();
                  return `
                     <button class="btn btn-sm btn-outline-primary" onclick='viewLog(${JSON.stringify(rowData)})' title="View Details">
                        <i class="bi bi-eye"></i>
                     </button>
                  `;
               }
            }
         ]
      });
   }

   function viewLog(logData) {
      const modal = new bootstrap.Modal(document.getElementById('viewLogModal'));
      modal.show();

      let changesHtml = '';
      if (logData.changes) {
         try {
            const changes = JSON.parse(logData.changes);
            changesHtml = `
               <div class="mb-3">
                  <h6>Changes</h6>
                  <pre class="bg-light p-3 rounded"><code>${JSON.stringify(changes, null, 2)}</code></pre>
               </div>
            `;
         } catch (e) {
            changesHtml = `<div class="mb-3"><h6>Changes</h6><p class="text-muted">No changes recorded</p></div>`;
         }
      }

      let metadataHtml = '';
      if (logData.metadata) {
         try {
            const metadata = JSON.parse(logData.metadata);
            metadataHtml = `
               <div class="mb-3">
                  <h6>Metadata</h6>
                  <pre class="bg-light p-3 rounded"><code>${JSON.stringify(metadata, null, 2)}</code></pre>
               </div>
            `;
         } catch (e) {
            // No metadata
         }
      }

      document.getElementById('viewLogContent').innerHTML = `
         <div class="row mb-3">
            <div class="col-md-6">
               <div class="mb-2">
                  <div class="text-muted small">User</div>
                  <div class="fw-semibold">${logData.user}</div>
               </div>
               <div class="mb-2">
                  <div class="text-muted small">Action</div>
                  <div><span class="badge bg-primary">${logData.action}</span></div>
               </div>
               <div class="mb-2">
                  <div class="text-muted small">Entity</div>
                  <div>${logData.entity} (ID: ${logData.entity_id})</div>
               </div>
            </div>
            <div class="col-md-6">
               <div class="mb-2">
                  <div class="text-muted small">Timestamp</div>
                  <div>${new Date(logData.timestamp).toLocaleString()}</div>
               </div>
               <div class="mb-2">
                  <div class="text-muted small">IP Address</div>
                  <div>${logData.ip || 'N/A'}</div>
               </div>
               <div class="mb-2">
                  <div class="text-muted small">User Agent</div>
                  <div class="small text-truncate" title="${logData.user_agent || 'N/A'}">${logData.user_agent || 'N/A'}</div>
               </div>
            </div>
         </div>
         ${changesHtml}
         ${metadataHtml}
      `;
   }

   function applyFilters() {
      const action = document.getElementById('filterAction').value;
      const entity = document.getElementById('filterEntity').value;
      const startDate = document.getElementById('filterStartDate').value;
      const endDate = document.getElementById('filterEndDate').value;

      let url = `${Config.API_BASE_URL}/audit/search`;
      let params = [];

      if (action) params.push(`action=${action}`);
      if (entity) params.push(`entity_type=${entity}`);
      if (startDate) params.push(`start_date=${startDate}`);
      if (endDate) params.push(`end_date=${endDate}`);

      if (params.length > 0) url += '?' + params.join('&');
      auditLogGrid.setData(url);
   }

   function clearFilters() {
      document.getElementById('filterAction').value = '';
      document.getElementById('filterEntity').value = '';
      document.getElementById('filterStartDate').value = '';
      document.getElementById('filterEndDate').value = '';
      auditLogGrid.setData();
   }
</script>

<?php require_once '../includes/footer.php'; ?>