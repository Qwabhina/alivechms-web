<?php
$pageTitle = 'Event Attendance';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Event Attendance</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item"><a href="events.php">Events</a></li>
               <li class="breadcrumb-item active">Attendance</li>
            </ol>
         </nav>
      </div>
      <div class="btn-group">
         <button class="btn btn-success" id="saveAttendanceBtn" data-permission="record_attendance">
            <i class="bi bi-check-circle me-2"></i>Save Attendance
         </button>
         <button class="btn btn-outline-secondary" onclick="window.location.href='events.php'">
            <i class="bi bi-arrow-left me-2"></i>Back to Events
         </button>
      </div>
   </div>

   <!-- Event Info Card -->
   <div class="card mb-4" id="eventInfoCard" style="display: none;">
      <div class="card-body">
         <div class="row align-items-center">
            <div class="col-md-8">
               <h5 class="mb-2" id="eventTitle">Loading...</h5>
               <div class="text-muted">
                  <i class="bi bi-calendar me-2"></i><span id="eventDate"></span>
                  <span id="eventTime" class="ms-3"></span>
                  <span id="eventLocation" class="ms-3"></span>
               </div>
            </div>
            <div class="col-md-4 text-end">
               <div class="d-flex justify-content-end gap-3">
                  <div>
                     <div class="text-muted small">Present</div>
                     <h4 class="mb-0 text-success" id="presentCount">0</h4>
                  </div>
                  <div>
                     <div class="text-muted small">Absent</div>
                     <h4 class="mb-0 text-danger" id="absentCount">0</h4>
                  </div>
                  <div>
                     <div class="text-muted small">Total</div>
                     <h4 class="mb-0 text-primary" id="totalCount">0</h4>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Quick Actions -->
   <div class="card mb-4">
      <div class="card-header">
         <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
      </div>
      <div class="card-body">
         <div class="row g-2">
            <div class="col-md-3">
               <button class="btn btn-outline-success w-100" onclick="markAllAs('Present')">
                  <i class="bi bi-check-all me-1"></i>Mark All Present
               </button>
            </div>
            <div class="col-md-3">
               <button class="btn btn-outline-danger w-100" onclick="markAllAs('Absent')">
                  <i class="bi bi-x-circle me-1"></i>Mark All Absent
               </button>
            </div>
            <div class="col-md-3">
               <button class="btn btn-outline-secondary w-100" onclick="clearAll()">
                  <i class="bi bi-arrow-counterclockwise me-1"></i>Clear All
               </button>
            </div>
            <div class="col-md-3">
               <input type="text" class="form-control" id="searchMembers" placeholder="Search members...">
            </div>
         </div>
      </div>
   </div>

   <!-- Attendance Grid -->
   <div class="card">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-people me-2"></i>Member Attendance</h5>
      </div>
      <div class="card-body">
         <div class="table-responsive">
            <div id="attendanceGrid"></div>
         </div>
      </div>
   </div>
</div>
</main>

<script>
   let attendanceGrid = null;
   let currentEventId = null;
   let attendanceData = {};

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;

      const urlParams = new URLSearchParams(window.location.search);
      currentEventId = urlParams.get('event_id');

      if (!currentEventId) {
         Alerts.error('No event selected');
         setTimeout(() => window.location.href = 'events.php', 2000);
         return;
      }

      await initPage();
   });

   async function initPage() {
      await loadEventInfo();
      await initGrid();
      initEventListeners();
   }

   async function loadEventInfo() {
      try {
         const event = await api.get(`event/view/${currentEventId}`);

         document.getElementById('eventTitle').textContent = event.EventTitle;
         document.getElementById('eventDate').textContent = new Date(event.EventDate).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
         });

         if (event.StartTime) {
            document.getElementById('eventTime').innerHTML = `<i class="bi bi-clock me-1"></i>${event.StartTime}${event.EndTime ? ' - ' + event.EndTime : ''}`;
         }

         if (event.Location) {
            document.getElementById('eventLocation').innerHTML = `<i class="bi bi-geo-alt me-1"></i>${event.Location}`;
         }

         document.getElementById('eventInfoCard').style.display = 'block';
      } catch (error) {
         console.error('Load event error:', error);
         Alerts.error('Failed to load event information');
      }
   }

   async function initGrid() {
      try {
         const response = await api.get('member/all?limit=1000');
         const members = response?.data?.data || response?.data || [];

         attendanceGrid = new Tabulator("#attendanceGrid", {
            layout: "fitColumns",
            responsiveLayout: "collapse",
            resizableColumns: false,
            pagination: true,
            paginationSize: 50,
            paginationSizeSelector: [25, 50, 100, 200],
            data: members.map(m => ({
               member_id: m.MbrID,
               name: `${m.MbrFirstName} ${m.MbrFamilyName}`,
               phone: m.MbrPhone || 'N/A',
               status: ''
            })),
            columns: [{
                  title: "Member Name",
                  field: "name",
                  widthGrow: 2,
                  responsive: 0,
                  headerFilter: "input",
                  headerFilterPlaceholder: "Search..."
               },
               {
                  title: "Phone",
                  field: "phone",
                  widthGrow: 1.5,
                  responsive: 2
               },
               {
                  title: "Attendance Status",
                  field: "status",
                  widthGrow: 2,
                  responsive: 0,
                  formatter: cell => {
                     const memberId = cell.getRow().getData().member_id;
                     const currentStatus = attendanceData[memberId] || '';

                     return `
                        <div class="btn-group btn-group-sm w-100" role="group">
                           <button type="button" class="btn ${currentStatus === 'Present' ? 'btn-success' : 'btn-outline-success'}" 
                              onclick="setAttendance(${memberId}, 'Present')">
                              <i class="bi bi-check-circle"></i> Present
                           </button>
                           <button type="button" class="btn ${currentStatus === 'Late' ? 'btn-warning' : 'btn-outline-warning'}" 
                              onclick="setAttendance(${memberId}, 'Late')">
                              <i class="bi bi-clock"></i> Late
                           </button>
                           <button type="button" class="btn ${currentStatus === 'Absent' ? 'btn-danger' : 'btn-outline-danger'}" 
                              onclick="setAttendance(${memberId}, 'Absent')">
                              <i class="bi bi-x-circle"></i> Absent
                           </button>
                           <button type="button" class="btn ${currentStatus === 'Excused' ? 'btn-info' : 'btn-outline-info'}" 
                              onclick="setAttendance(${memberId}, 'Excused')">
                              <i class="bi bi-info-circle"></i> Excused
                           </button>
                        </div>
                     `;
                  }
               }
            ]
         });

         // Load existing attendance if any
         await loadExistingAttendance();
      } catch (error) {
         console.error('Init grid error:', error);
         Alerts.error('Failed to load members');
      }
   }

   async function loadExistingAttendance() {
      try {
         const event = await api.get(`event/view/${currentEventId}`);
         // If there's existing attendance data, we would load it here
         // For now, we'll start fresh
         updateCounts();
      } catch (error) {
         console.error('Load attendance error:', error);
      }
   }

   function setAttendance(memberId, status) {
      attendanceData[memberId] = status;
      attendanceGrid.redraw(true);
      updateCounts();
   }

   function markAllAs(status) {
      const data = attendanceGrid.getData();
      data.forEach(row => {
         attendanceData[row.member_id] = status;
      });
      attendanceGrid.redraw(true);
      updateCounts();
   }

   function clearAll() {
      attendanceData = {};
      attendanceGrid.redraw(true);
      updateCounts();
   }

   function updateCounts() {
      const statuses = Object.values(attendanceData);
      const presentCount = statuses.filter(s => s === 'Present').length;
      const absentCount = statuses.filter(s => s === 'Absent').length;
      const totalCount = statuses.length;

      document.getElementById('presentCount').textContent = presentCount;
      document.getElementById('absentCount').textContent = absentCount;
      document.getElementById('totalCount').textContent = totalCount;
   }

   function initEventListeners() {
      document.getElementById('saveAttendanceBtn').addEventListener('click', saveAttendance);

      document.getElementById('searchMembers').addEventListener('input', (e) => {
         attendanceGrid.setFilter("name", "like", e.target.value);
      });
   }

   async function saveAttendance() {
      if (!Auth.hasPermission('record_attendance')) {
         Alerts.error('You do not have permission to record attendance');
         return;
      }

      const attendances = Object.entries(attendanceData).map(([memberId, status]) => ({
         member_id: parseInt(memberId),
         status: status
      }));

      if (attendances.length === 0) {
         Alerts.warning('Please mark attendance for at least one member');
         return;
      }

      const confirmed = await Alerts.confirm({
         title: 'Save Attendance',
         text: `You are about to save attendance for ${attendances.length} member(s). Continue?`,
         icon: 'question',
         confirmButtonText: 'Yes, save',
         confirmButtonColor: '#198754'
      });

      if (!confirmed) return;

      try {
         Alerts.loading('Saving attendance...');
         await api.post(`event/attendance/bulk/${currentEventId}`, {
            attendances
         });
         Alerts.closeLoading();
         Alerts.success('Attendance saved successfully');

         setTimeout(() => {
            window.location.href = 'events.php';
         }, 1500);
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save attendance error:', error);
         Alerts.handleApiError(error);
      }
   }
</script>

<?php require_once '../includes/footer.php'; ?>