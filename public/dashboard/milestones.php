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

   <!-- Stats Filters -->
   <div class="card mb-4">
      <div class="card-body py-2">
         <div class="row g-2 align-items-center">
            <div class="col-md-auto">
               <span class="fw-medium small text-muted"><i class="bi bi-funnel me-1"></i>Stats Filters:</span>
            </div>
            <div class="col-md-2">
               <select class="form-select form-select-sm" id="statsFilterType">
                  <option value="">All Types</option>
               </select>
            </div>
            <div class="col-md-2">
               <input type="date" class="form-control form-control-sm" id="statsFilterStartDate" placeholder="Start Date">
            </div>
            <div class="col-md-2">
               <input type="date" class="form-control form-control-sm" id="statsFilterEndDate" placeholder="End Date">
            </div>
            <div class="col-md-auto">
               <button class="btn btn-primary btn-sm" id="applyStatsFiltersBtn">Apply</button>
               <button class="btn btn-outline-secondary btn-sm" id="clearStatsFiltersBtn">Clear</button>
            </div>
         </div>
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
      <div class="col-lg-8 mb-3">
         <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
               <ul class="nav nav-tabs card-header-tabs" id="milestoneListTabs" role="tablist">
                  <li class="nav-item" role="presentation">
                     <button class="nav-link active py-1" id="recent-tab" data-bs-toggle="tab" data-bs-target="#recent-content" type="button" role="tab"><i class="bi bi-clock-history me-1"></i>Recent</button>
                  </li>
                  <li class="nav-item" role="presentation">
                     <button class="nav-link py-1" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming-content" type="button" role="tab"><i class="bi bi-calendar-event me-1"></i>Upcoming</button>
                  </li>
               </ul>
            </div>
            <div class="card-body p-0">
               <div class="tab-content">
                  <div class="tab-pane fade show active" id="recent-content" role="tabpanel">
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
                  <div class="tab-pane fade" id="upcoming-content" role="tabpanel">
                     <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                           <thead class="table-light">
                              <tr>
                                 <th>Member</th>
                                 <th>Milestone</th>
                                 <th>Date</th>
                              </tr>
                           </thead>
                           <tbody id="upcomingAnniversariesBody">
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
                     <select class="form-select" id="milestoneMember" required>
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

<script src="../assets/js/vendor/chart.umd.min.js"></script>
<script src="../assets/js/core/qmgrid-helper.js"></script>
<script type="module" src="../assets/js/modules/milestones/index.js"></script>
<?php require_once '../includes/footer.php'; ?>
