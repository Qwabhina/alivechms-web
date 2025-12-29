</main>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Tabulator -->
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@6.2.5/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Choices.js -->
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>

<!-- FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<!-- Dropzone -->
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.4/dist/sweetalert2.all.min.js"></script>

<!-- Core JS -->
<script src="../assets/js/core/config.js"></script>
<script src="../assets/js/core/utils.js"></script>
<script src="../assets/js/core/api.js"></script>
<script src="../assets/js/core/auth.js"></script>
<script src="../assets/js/core/alerts.js"></script>
<script src="../assets/js/core/components.js"></script>

<!-- Layout Script -->
<script>
   document.addEventListener('DOMContentLoaded', () => {
      // Check authentication
      if (!Auth.requireAuth()) return;

      // Load user info
      const user = Auth.getUser();
      if (user) {
         const userName = Auth.getUserName();
         const userRole = Auth.getUserRole();
         const userInitials = Auth.getUserInitials();

         // Desktop header
         document.getElementById('userName').textContent = userName;
         document.getElementById('userRole').textContent = userRole;
         document.getElementById('userAvatar').textContent = userInitials;

         // Mobile sidebar
         const sidebarUserName = document.getElementById('sidebarUserName');
         const sidebarUserRole = document.getElementById('sidebarUserRole');
         const sidebarUserAvatar = document.getElementById('sidebarUserAvatar');
         if (sidebarUserName) sidebarUserName.textContent = userName;
         if (sidebarUserRole) sidebarUserRole.textContent = userRole;
         if (sidebarUserAvatar) sidebarUserAvatar.textContent = userInitials;
      }

      // Set active nav item
      const currentPage = window.location.pathname.split('/').pop().replace('.php', '') || 'index';
      document.querySelectorAll('.nav-link').forEach(link => {
         const page = link.getAttribute('data-page');
         if (page === currentPage || (currentPage === 'index' && page === 'dashboard')) {
            link.classList.add('active');

            // If it's a sub-menu item, expand its parent accordion
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
               const bsCollapse = new bootstrap.Collapse(parentCollapse, {
                  toggle: false
               });
               bsCollapse.show();

               // Mark parent accordion link as active
               const parentLink = document.querySelector(`[href="#${parentCollapse.id}"]`);
               if (parentLink) {
                  parentLink.classList.remove('collapsed');
               }
            }
         }
      });

      // Hide menu items based on permissions
      document.querySelectorAll('[data-permission]').forEach(item => {
         const permission = item.getAttribute('data-permission');
         if (permission && !Auth.hasPermission(permission)) {
            item.style.display = 'none';
         }
      });

      // Mobile sidebar toggle
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      const sidebarOverlay = document.getElementById('sidebarOverlay');

      if (sidebarToggle && sidebar) {
         sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
         });

         sidebarOverlay?.addEventListener('click', () => {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
         });
      }

      // Logout handler
      const handleLogout = async (e) => {
         e.preventDefault();
         const confirmed = await Alerts.confirm({
            title: 'Logout',
            text: 'Are you sure you want to logout?',
            icon: 'question',
            confirmButtonText: 'Yes, logout'
         });
         if (confirmed) {
            Alerts.loading('Logging out...');
            await Auth.logout();
         }
      };

      document.getElementById('logoutBtn')?.addEventListener('click', handleLogout);
      document.getElementById('sidebarLogoutBtn')?.addEventListener('click', handleLogout);

      // Sidebar notifications button
      document.getElementById('sidebarNotificationsBtn')?.addEventListener('click', () => {
         Alerts.info('No new notifications');
      });
   });
</script>
</body>

</html>