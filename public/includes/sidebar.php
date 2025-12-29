<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
   <nav class="sidebar-nav">
      <!-- Mobile-only: User section -->
      <div class="d-lg-none sidebar-mobile-header">
         <div class="d-flex align-items-center mb-3">
            <div class="user-avatar me-2" id="sidebarUserAvatar">?</div>
            <div class="flex-grow-1 text-truncate">
               <div class="fw-semibold text-white small" id="sidebarUserName">Loading...</div>
               <small class="text-white-50" id="sidebarUserRole">User</small>
            </div>
         </div>
         <div class="input-group input-group-sm mb-3">
            <span class="input-group-text bg-transparent border-secondary text-white-50">
               <i class="bi bi-search"></i>
            </span>
            <input type="text" class="form-control bg-transparent border-secondary text-white" placeholder="Search..." id="sidebarSearch">
         </div>
         <div class="d-flex gap-2 mb-3">
            <button class="btn btn-sm btn-outline-light flex-grow-1" id="sidebarNotificationsBtn">
               <i class="bi bi-bell me-1"></i>Notifications
            </button>
            <a href="profile.php" class="btn btn-sm btn-outline-light">
               <i class="bi bi-person"></i>
            </a>
         </div>
         <hr class="border-secondary my-2">
      </div>

      <ul class="nav flex-column">
         <!-- Dashboard -->
         <li class="nav-item">
            <a class="nav-link" href="./" data-page="dashboard">
               <i class="bi bi-speedometer2 me-2"></i>
               <span>Dashboard</span>
            </a>
         </li>

         <!-- Members & People -->
         <li class="nav-item" data-permission="view_members">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#membersMenu" role="button">
               <i class="bi bi-people me-2"></i>
               <span>Members & People</span>
               <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="membersMenu">
               <ul class="nav flex-column ms-3">
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="members.php" data-page="members">
                        <i class="bi bi-person me-2"></i>Members
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="families.php" data-page="families">
                        <i class="bi bi-house-heart me-2"></i>Families
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="groups.php" data-page="groups">
                        <i class="bi bi-diagram-3 me-2"></i>Groups
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="volunteers.php" data-page="volunteers">
                        <i class="bi bi-hand-thumbs-up me-2"></i>Volunteers
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="milestones.php" data-page="milestones">
                        <i class="bi bi-trophy me-2"></i>Member Milestones
                     </a>
                  </li>
               </ul>
            </div>
         </li>

         <!-- Finance -->
         <li class="nav-item" data-permission="view_contribution">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#financeMenu" role="button">
               <i class="bi bi-currency-dollar me-2"></i>
               <span>Finance</span>
               <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="financeMenu">
               <ul class="nav flex-column ms-3">
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="contributions.php" data-page="contributions">
                        <i class="bi bi-cash-coin me-2"></i>Contributions
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="pledges.php" data-page="pledges">
                        <i class="bi bi-bookmark-heart me-2"></i>Pledges
                     </a>
                  </li>
                  <li class="nav-item" data-permission="view_expenses">
                     <a class="nav-link nav-link-sub" href="expenses.php" data-page="expenses">
                        <i class="bi bi-receipt me-2"></i>Expenses
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="budgets.php" data-page="budgets">
                        <i class="bi bi-pie-chart me-2"></i>Budgets
                     </a>
                  </li>
                  <li class="nav-item" data-permission="view_financial_reports">
                     <a class="nav-link nav-link-sub" href="reports.php" data-page="reports">
                        <i class="bi bi-graph-up me-2"></i>Financial Reports
                     </a>
                  </li>
               </ul>
            </div>
         </li>

         <!-- Events & Activities -->
         <li class="nav-item" data-permission="view_events">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#eventsMenu" role="button">
               <i class="bi bi-calendar-event me-2"></i>
               <span>Events & Activities</span>
               <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="eventsMenu">
               <ul class="nav flex-column ms-3">
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="events.php" data-page="events">
                        <i class="bi bi-calendar-check me-2"></i>Events
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="attendance.php" data-page="attendance">
                        <i class="bi bi-clipboard-check me-2"></i>Attendance
                     </a>
                  </li>
               </ul>
            </div>
         </li>

         <!-- Communication -->
         <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#communicationMenu" role="button">
               <i class="bi bi-envelope me-2"></i>
               <span>Communication</span>
               <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="communicationMenu">
               <ul class="nav flex-column ms-3">
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="messages.php" data-page="messages">
                        <i class="bi bi-chat-dots me-2"></i>Messages
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="announcements.php" data-page="announcements">
                        <i class="bi bi-megaphone me-2"></i>Announcements
                     </a>
                  </li>
               </ul>
            </div>
         </li>

         <!-- Assets -->
         <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#assetsMenu" role="button">
               <i class="bi bi-box-seam me-2"></i>
               <span>Assets</span>
               <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="assetsMenu">
               <ul class="nav flex-column ms-3">
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="assets.php" data-page="assets">
                        <i class="bi bi-archive me-2"></i>All Assets
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="asset-categories.php" data-page="asset-categories">
                        <i class="bi bi-tags me-2"></i>Asset Categories
                     </a>
                  </li>
               </ul>
            </div>
         </li>

         <!-- Divider -->
         <li class="nav-item">
            <hr class="my-3" style="border-color: rgba(255,255,255,0.1);">
         </li>

         <!-- Settings -->
         <li class="nav-item" data-permission="manage_roles">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#settingsMenu" role="button">
               <i class="bi bi-gear me-2"></i>
               <span>Settings</span>
               <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="settingsMenu">
               <ul class="nav flex-column ms-3">
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="settings.php" data-page="settings">
                        <i class="bi bi-sliders me-2"></i>General
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="users.php" data-page="users">
                        <i class="bi bi-people-fill me-2"></i>Users
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="roles.php" data-page="roles">
                        <i class="bi bi-shield-check me-2"></i>Roles & Permissions
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="branches.php" data-page="branches">
                        <i class="bi bi-building me-2"></i>Branches
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="fiscal-years.php" data-page="fiscal-years">
                        <i class="bi bi-calendar-range me-2"></i>Fiscal Years
                     </a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-sub" href="audit-log.php" data-page="audit-log">
                        <i class="bi bi-clock-history me-2"></i>Audit Log
                     </a>
                  </li>
               </ul>
            </div>
         </li>

         <!-- Mobile-only: Logout -->
         <li class="nav-item d-lg-none">
            <a class="nav-link text-danger" href="#" id="sidebarLogoutBtn">
               <i class="bi bi-box-arrow-right me-2"></i>
               <span>Logout</span>
            </a>
         </li>
      </ul>
   </nav>
</aside>

<!-- Main Content -->
<main class="main-content">