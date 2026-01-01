<?php
$pageTitle = 'Events Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Events</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Events</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addEventBtn" data-permission="manage_events">
         <i class="bi bi-plus-circle me-2"></i>Create Event
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
         <div class="card stat-card bg-primary bg-opacity-25">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                     <p class="text-muted mb-1">Total Events</p>
                     <h3 class="mb-0" id="totalEvents">0</h3>
                     <small class="text-muted">All time</small>
                  </div>
                  <div class="stat-icon bg-primary text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-calendar-event"></i>
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
                     <p class="text-muted mb-1">Upcoming</p>
                     <h3 class="mb-0" id="upcomingEvents">0</h3>
                     <small class="text-muted">Next 30 days</small>
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
                     <p class="text-muted mb-1">This Month</p>
                     <h3 class="mb-0" id="monthEvents">0</h3>
                     <small class="text-muted">Events</small>
                  </div>
                  <div class="stat-icon bg-info text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-calendar-month"></i>
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
                     <p class="text-muted mb-1">Avg Attendance</p>
                     <h3 class="mb-0" id="avgAttendance">0</h3>
                     <small class="text-muted">Per event</small>
                  </div>
                  <div class="stat-icon bg-warning text-white text-opacity-50 rounded-circle p-3">
                     <i class="bi bi-people"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Filters -->
   <div class="card mb-4">
      <div class="card-header">
         <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
      </div>
      <div class="card-body">
         <div class="row g-3">
            <div class="col-md-3">
               <label class="form-label small">Branch</label>
               <select class="form-select form-select-sm" id="filterBranch">
                  <option value="">All Branches</option>
               </select>
            </div>
            <div class="col-md-3">
               <label class="form-label small">Start Date</label>
               <input type="date" class="form-control form-control-sm" id="filterStartDate">
            </div>
            <div class="col-md-3">
               <label class="form-label small">End Date</label>
               <input type="date" class="form-control form-control-sm" id="filterEndDate">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
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

   <!-- Events Table -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>All Events</h5>
      </div>
      <div class="card-body">
         <table id="eventsTable" class="table table-striped table-hover" style="width:100%">
            <thead>
               <tr>
                  <th>Event Title</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Location</th>
                  <th>Branch</th>
                  <th class="no-export">Actions</th>
               </tr>
            </thead>
            <tbody></tbody>
         </table>
      </div>
   </div>
</div>
</main>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="eventModalTitle">Create Event</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form id="eventForm">
               <input type="hidden" id="eventId">
               <div class="row">
                  <div class="col-md-8 mb-3">
                     <label class="form-label">Event Title <span class="text-danger">*</span></label>
                     <input type="text" class="form-control" id="title" required maxlength="150">
                  </div>
                  <div class="col-md-4 mb-3">
                     <label class="form-label">Branch <span class="text-danger">*</span></label>
                     <select class="form-select" id="branchId" required>
                        <option value="">Select Branch</option>
                     </select>
                  </div>
               </div>
               <div class="row">
                  <div class="col-md-4 mb-3">
                     <label class="form-label">Event Date <span class="text-danger">*</span></label>
                     <input type="date" class="form-control" id="eventDate" required>
                  </div>
                  <div class="col-md-4 mb-3">
                     <label class="form-label">Start Time</label>
                     <input type="time" class="form-control" id="startTime">
                  </div>
                  <div class="col-md-4 mb-3">
                     <label class="form-label">End Time</label>
                     <input type="time" class="form-control" id="endTime">
                  </div>
               </div>
               <div class="mb-3">
                  <label class="form-label">Location</label>
                  <input type="text" class="form-control" id="location" maxlength="200">
               </div>
               <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" id="description" rows="3" maxlength="1000"></textarea>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveEventBtn">
               <i class="bi bi-check-circle me-1"></i>Save Event
            </button>
         </div>
      </div>
   </div>
</div>

<!-- View Event Modal -->
<div class="modal fade" id="viewEventModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Event Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="viewEventContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary"></div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-success" id="recordAttendanceBtn">
               <i class="bi bi-clipboard-check me-1"></i>Record Attendance
            </button>
            <button type="button" class="btn btn-primary" id="editEventFromViewBtn">
               <i class="bi bi-pencil me-1"></i>Edit
            </button>
         </div>
      </div>
   </div>
</div>

<script>
   let eventsTable = null;
   let currentEventId = null;
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
      loadStats();
   }

   function initTable() {
      eventsTable = QMGridHelper.initWithButtons('#eventsTable', {
         ajax: {
            url: `${Config.API_BASE_URL}/event/all`,
            type: 'GET',
            data: function(d) {
               return {
                  page: Math.floor(d.start / d.length) + 1,
                  limit: d.length,
                  search: d.search.value || ''
               };
            },
            dataFilter: function(data) {
               return QMGridHelper.processServerResponse(data, function(e) {
                  return {
                     title: e.EventTitle,
                     date: e.EventDate,
                     time: e.StartTime ? `${e.StartTime}${e.EndTime ? ' - ' + e.EndTime : ''}` : 'N/A',
                     location: e.Location || 'N/A',
                     branch: e.BranchName,
                     id: e.EventID
                  };
               });
            }
         },
         columns: [{
               data: 'title',
               title: 'Event Title'
            },
            {
               data: 'date',
               title: 'Date',
               render: function(data) {
                  return new Date(data).toLocaleDateString('en-US', {
                     year: 'numeric',
                     month: 'short',
                     day: 'numeric'
                  });
               }
            },
            {
               data: 'time',
               title: 'Time'
            },
            {
               data: 'location',
               title: 'Location'
            },
            {
               data: 'branch',
               title: 'Branch'
            },
            {
               data: 'id',
               title: 'Actions',
               orderable: false,
               searchable: false,
               className: 'no-export',
               render: function(data) {
                  return QMGridHelper.actionButtons(data, {
                     viewFn: 'viewEvent',
                     editFn: 'editEvent',
                     deleteFn: 'deleteEvent'
                  });
               }
            }
         ],
         order: [
            [1, 'desc']
         ]
      });
   }

   async function loadStats() {
      try {
         const response = await api.get('event/all?limit=1000');
         const events = response?.data?.data || response?.data || [];

         const now = new Date();
         const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
         const monthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0);
         const futureDate = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));

         let upcomingCount = 0;
         let monthCount = 0;

         events.forEach(e => {
            const eventDate = new Date(e.EventDate);

            if (eventDate >= now && eventDate <= futureDate) {
               upcomingCount++;
            }

            if (eventDate >= monthStart && eventDate <= monthEnd) {
               monthCount++;
            }
         });

         document.getElementById('totalEvents').textContent = events.length;
         document.getElementById('upcomingEvents').textContent = upcomingCount;
         document.getElementById('monthEvents').textContent = monthCount;
         document.getElementById('avgAttendance').textContent = '0'; // Will be calculated from attendance data
      } catch (error) {
         console.error('Load stats error:', error);
      }
   }

   async function loadDropdowns() {
      try {
         const response = await api.get('branch/all?limit=100');
         const branches = response?.data?.data || response?.data || [];

         const branchSelect = document.getElementById('branchId');
         const filterBranchSelect = document.getElementById('filterBranch');

         branchSelect.innerHTML = '<option value="">Select Branch</option>';
         filterBranchSelect.innerHTML = '<option value="">All Branches</option>';

         branches.forEach(b => {
            const opt1 = document.createElement('option');
            opt1.value = b.BranchID;
            opt1.textContent = b.BranchName;
            branchSelect.appendChild(opt1);

            const opt2 = document.createElement('option');
            opt2.value = b.BranchID;
            opt2.textContent = b.BranchName;
            filterBranchSelect.appendChild(opt2);
         });
      } catch (error) {
         console.error('Load dropdowns error:', error);
      }
   }

   function initEventListeners() {
      document.getElementById('addEventBtn').addEventListener('click', () => {
         if (!Auth.hasPermission('manage_events')) {
            Alerts.error('You do not have permission to create events');
            return;
         }
         openEventModal();
      });

      document.getElementById('saveEventBtn').addEventListener('click', saveEvent);

      document.getElementById('editEventFromViewBtn').addEventListener('click', () => {
         bootstrap.Modal.getInstance(document.getElementById('viewEventModal')).hide();
         editEvent(currentEventId);
      });

      document.getElementById('recordAttendanceBtn').addEventListener('click', () => {
         window.location.href = `attendance.php?event_id=${currentEventId}`;
      });
   }

   function openEventModal(eventId = null) {
      isEditMode = !!eventId;
      currentEventId = eventId;

      document.getElementById('eventForm').reset();
      document.getElementById('eventId').value = '';
      document.getElementById('eventModalTitle').textContent = isEditMode ? 'Edit Event' : 'Create Event';

      const modal = new bootstrap.Modal(document.getElementById('eventModal'));
      modal.show();

      if (isEditMode) loadEventForEdit(eventId);
   }

   async function loadEventForEdit(eventId) {
      try {
         const event = await api.get(`event/view/${eventId}`);
         document.getElementById('eventId').value = event.EventID;
         document.getElementById('title').value = event.EventTitle;
         document.getElementById('branchId').value = event.BranchID;
         document.getElementById('eventDate').value = event.EventDate;
         document.getElementById('startTime').value = event.StartTime || '';
         document.getElementById('endTime').value = event.EndTime || '';
         document.getElementById('location').value = event.Location || '';
         document.getElementById('description').value = event.EventDescription || '';
      } catch (error) {
         console.error('Load event error:', error);
         Alerts.error('Failed to load event data');
      }
   }

   async function saveEvent() {
      const title = document.getElementById('title').value.trim();
      const branchId = document.getElementById('branchId').value;
      const eventDate = document.getElementById('eventDate').value;

      if (!title || !branchId || !eventDate) {
         Alerts.warning('Please fill all required fields');
         return;
      }

      const payload = {
         title,
         branch_id: parseInt(branchId),
         event_date: eventDate,
         start_time: document.getElementById('startTime').value || null,
         end_time: document.getElementById('endTime').value || null,
         location: document.getElementById('location').value.trim() || null,
         description: document.getElementById('description').value.trim() || null
      };

      try {
         Alerts.loading('Saving event...');
         if (isEditMode) {
            await api.put(`event/update/${currentEventId}`, payload);
         } else {
            await api.post('event/create', payload);
         }
         Alerts.closeLoading();
         Alerts.success(isEditMode ? 'Event updated successfully' : 'Event created successfully');
         bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
         QMGridHelper.reload(eventsTable);
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save event error:', error);
         Alerts.handleApiError(error);
      }
   }

   async function viewEvent(eventId) {
      currentEventId = eventId;
      const modal = new bootstrap.Modal(document.getElementById('viewEventModal'));
      modal.show();

      try {
         const event = await api.get(`event/view/${eventId}`);
         const summary = event.attendance_summary || {};

         document.getElementById('viewEventContent').innerHTML = `
            <div class="row">
               <div class="col-md-8">
                  <h4 class="mb-3">${event.EventTitle}</h4>
                  <div class="mb-3">
                     <div class="text-muted small">Date & Time</div>
                     <div class="fw-semibold">
                        ${new Date(event.EventDate).toLocaleDateString('en-US', { 
                           weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
                        })}
                        ${event.StartTime ? `<br><i class="bi bi-clock me-1"></i>${event.StartTime}${event.EndTime ? ' - ' + event.EndTime : ''}` : ''}
                     </div>
                  </div>
                  ${event.Location ? `
                  <div class="mb-3">
                     <div class="text-muted small">Location</div>
                     <div><i class="bi bi-geo-alt me-1"></i>${event.Location}</div>
                  </div>
                  ` : ''}
                  <div class="mb-3">
                     <div class="text-muted small">Branch</div>
                     <div>${event.BranchName}</div>
                  </div>
                  ${event.EventDescription ? `
                  <div class="mb-3">
                     <div class="text-muted small">Description</div>
                     <div>${event.EventDescription}</div>
                  </div>
                  ` : ''}
               </div>
               <div class="col-md-4">
                  <div class="card bg-light">
                     <div class="card-body">
                        <h6 class="card-title">Attendance Summary</h6>
                        <div class="mb-2">
                           <div class="d-flex justify-content-between">
                              <span><i class="bi bi-check-circle text-success me-1"></i>Present</span>
                              <strong>${summary.Present || 0}</strong>
                           </div>
                        </div>
                        <div class="mb-2">
                           <div class="d-flex justify-content-between">
                              <span><i class="bi bi-clock text-warning me-1"></i>Late</span>
                              <strong>${summary.Late || 0}</strong>
                           </div>
                        </div>
                        <div class="mb-2">
                           <div class="d-flex justify-content-between">
                              <span><i class="bi bi-x-circle text-danger me-1"></i>Absent</span>
                              <strong>${summary.Absent || 0}</strong>
                           </div>
                        </div>
                        <div class="mb-2">
                           <div class="d-flex justify-content-between">
                              <span><i class="bi bi-info-circle text-info me-1"></i>Excused</span>
                              <strong>${summary.Excused || 0}</strong>
                           </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                           <strong>Total</strong>
                           <strong class="text-primary">${event.total_attendance || 0}</strong>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         `;
      } catch (error) {
         console.error('View event error:', error);
         document.getElementById('viewEventContent').innerHTML = `
            <div class="text-center text-danger py-5">
               <i class="bi bi-exclamation-circle fs-1"></i>
               <p class="mt-2">Failed to load event details</p>
            </div>
         `;
      }
   }

   function editEvent(eventId) {
      if (!Auth.hasPermission('manage_events')) {
         Alerts.error('You do not have permission to edit events');
         return;
      }
      openEventModal(eventId);
   }

   async function deleteEvent(eventId) {
      if (!Auth.hasPermission('manage_events')) {
         Alerts.error('You do not have permission to delete events');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Delete Event',
         text: 'Are you sure you want to delete this event? This action cannot be undone.',
         icon: 'warning',
         confirmButtonText: 'Yes, delete',
         confirmButtonColor: '#dc3545'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Deleting event...');
         await api.delete(`event/delete/${eventId}`);
         Alerts.closeLoading();
         Alerts.success('Event deleted successfully');
         QMGridHelper.reload(eventsTable);
         loadStats();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Delete event error:', error);
         Alerts.handleApiError(error);
      }
   }

   function applyFilters() {
      const branchId = document.getElementById('filterBranch').value;
      const startDate = document.getElementById('filterStartDate').value;
      const endDate = document.getElementById('filterEndDate').value;

      let url = `${Config.API_BASE_URL}/event/all`;
      let params = [];

      if (branchId) params.push(`branch_id=${branchId}`);
      if (startDate) params.push(`start_date=${startDate}`);
      if (endDate) params.push(`end_date=${endDate}`);

      if (params.length > 0) url += '?' + params.join('&');
      eventsTable.ajax.url(url).load();
   }

   function clearFilters() {
      document.getElementById('filterBranch').value = '';
      document.getElementById('filterStartDate').value = '';
      document.getElementById('filterEndDate').value = '';
      eventsTable.ajax.url(`${Config.API_BASE_URL}/event/all`).load();
   }
</script>

<?php require_once '../includes/footer.php'; ?>