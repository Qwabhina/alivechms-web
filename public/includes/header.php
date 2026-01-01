<?php
// Load settings helper with error handling
try {
   // Load composer autoloader if not already loaded
   if (!class_exists('Dotenv\Dotenv')) {
      require_once __DIR__ . '/../../vendor/autoload.php';
   }

   // Load environment if not already loaded
   if (!isset($_ENV['DB_HOST'])) {
      $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
      $dotenv->load();
   }

   // Load required classes if not already loaded
   if (!class_exists('Database')) {
      require_once __DIR__ . '/../../core/Database.php';
   }
   if (!class_exists('ORM')) {
      require_once __DIR__ . '/../../core/ORM.php';
   }
   if (!class_exists('Helpers')) {
      require_once __DIR__ . '/../../core/Helpers.php';
   }
   if (!class_exists('Settings')) {
      require_once __DIR__ . '/../../core/Settings.php';
   }
   if (!class_exists('SettingsHelper')) {
      require_once __DIR__ . '/../../core/SettingsHelper.php';
   }

   $churchName = SettingsHelper::getChurchName();
   $language = SettingsHelper::getLanguage();
} catch (Exception $e) {
   // Fallback if settings can't be loaded
   error_log("Header settings load error: " . $e->getMessage());
   $churchName = 'AliveChMS Church';
   $language = 'en';
}

$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta name="description" content="<?= htmlspecialchars($churchName) ?> - Modern Church Management System">
   <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($churchName) ?></title>

   <!-- Bootstrap CSS -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

   <!-- Bootstrap Icons -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

   <!-- QMGrid - Modern table library -->
   <link href="https://cdn.jsdelivr.net/gh/Qwabhina/qmgrid@latest/qmgrid.min.css" rel="stylesheet">

   <!-- Choices.js -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">

   <!-- Flatpickr -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">

   <!-- FullCalendar -->
   <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">

   <!-- Dropzone -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css">

   <!-- SweetAlert2 -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.4/dist/sweetalert2.min.css">

   <!-- Custom CSS -->
   <link rel="stylesheet" href="../assets/css/app.css">
</head>

<body>
   <!-- Header -->
   <header class="main-header">
      <nav class="navbar">
         <div class="d-flex align-items-center">
            <!-- Mobile menu toggle -->
            <button class="btn btn-link d-lg-none me-3" id="sidebarToggle">
               <i class="bi bi-list fs-4"></i>
            </button>

            <!-- Brand - Desktop -->
            <a class="navbar-brand d-none d-md-flex" href="./">
               <?php if (isset($churchName) && SettingsHelper::hasChurchLogo()): ?>
                  <img src="<?= SettingsHelper::getChurchLogoUrl() ?>" alt="<?= htmlspecialchars($churchName) ?>" style="height: 32px; margin-right: 8px;">
               <?php else: ?>
                  <i class="bi bi-church me-2"></i>
               <?php endif; ?>
               <?= htmlspecialchars($churchName) ?>
            </a>
         </div>

         <!-- Brand - Mobile (Centered) -->
         <a class="navbar-brand d-md-none position-absolute start-50 translate-middle-x" href="./">
            <?php if (isset($churchName) && SettingsHelper::hasChurchLogo()): ?>
               <img src="<?= SettingsHelper::getChurchLogoUrl() ?>" alt="<?= htmlspecialchars($churchName) ?>" style="height: 32px;">
            <?php else: ?>
               <i class="bi bi-church"></i>
            <?php endif; ?>
         </a>

         <div class="d-flex align-items-center gap-3">
            <!-- Search (hidden on mobile) -->
            <div class="d-none d-lg-block">
               <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white border-end-0">
                     <i class="bi bi-search"></i>
                  </span>
                  <input type="text" class="form-control border-start-0" placeholder="Search..." id="globalSearch">
               </div>
            </div>

            <!-- Notifications (hidden on mobile) -->
            <div class="dropdown d-none d-lg-block">
               <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown">
                  <i class="bi bi-bell fs-5"></i>
                  <span class="notification-badge d-none" id="notificationBadge">0</span>
               </button>
               <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                  <div class="dropdown-header d-flex justify-content-between align-items-center">
                     <strong>Notifications</strong>
                     <a href="#" class="text-decoration-none small">Mark all read</a>
                  </div>
                  <div id="notificationList" class="notification-list">
                     <div class="text-center text-muted py-4">
                        <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                        No new notifications
                     </div>
                  </div>
               </div>
            </div>

            <!-- User Menu (hidden on mobile) -->
            <div class="dropdown d-none d-lg-block">
               <button class="btn d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                  <div class="user-avatar" id="userAvatar">?</div>
                  <div class="text-start">
                     <div class="fw-semibold small" id="userName">Loading...</div>
                     <small class="text-muted" id="userRole">User</small>
                  </div>
                  <i class="bi bi-chevron-down small"></i>
               </button>
               <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                     <a class="dropdown-item" href="profile.php">
                        <i class="bi bi-person me-2"></i>My Profile
                     </a>
                  </li>
                  <li>
                     <a class="dropdown-item" href="settings.php">
                        <i class="bi bi-gear me-2"></i>Settings
                     </a>
                  </li>
                  <li>
                     <hr class="dropdown-divider">
                  </li>
                  <li>
                     <a class="dropdown-item text-danger" href="#" id="logoutBtn">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                     </a>
                  </li>
               </ul>
            </div>
         </div>
      </nav>
   </header>

   <!-- Sidebar Overlay (Mobile) -->
   <div class="sidebar-overlay" id="sidebarOverlay"></div>