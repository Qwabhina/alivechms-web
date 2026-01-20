</main>

<!-- Bootstrap JS -->
<script src="../assets/js/vendor/bootstrap.bundle.min.js"></script>
<!-- QMGrid - Modern table library -->
<script src="../assets/js/vendor/qmgrid.min.js"></script>
<!-- Chart.js -->
<script src="../assets/js/vendor/chart.umd.min.js"></script>
<!-- Choices.js -->
<script src="../assets/js/vendor/choices.min.js"></script>
<!-- Flatpickr -->
<script src="../assets/js/vendor/flatpickr.min.js"></script>
<!-- FullCalendar -->
<script src="../assets/js/vendor/index.global.min.js"></script>
<!-- Dropzone -->
<script src="../assets/js/vendor/dropzone.min.js"></script>
<!-- SweetAlert2 -->
<script src="../assets/js/vendor/sweetalert2.all.min.js"></script>
<!-- Core JS -->
<script src="../assets/js/core/config.js"></script>
<script src="../assets/js/core/utils.js"></script>
<script src="../assets/js/core/api.js"></script>
<script src="../assets/js/core/auth.js"></script>
<script src="../assets/js/core/alerts.js"></script>
<script src="../assets/js/core/components.js"></script>

<!-- Layout Script -->
<script>
   // Global currency formatter using settings
   window.formatCurrency = function(amount) {
      if (amount === null || amount === undefined) return '-';
      const num = parseFloat(amount);
      if (isNaN(num)) return '-';

      const formatted = num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
      const symbol = Config.getSetting('currency_symbol', '₵');

      return `${symbol} ${formatted}`;
   };

   // Alternative using toLocaleString
   window.formatCurrencyLocale = function(amount) {
      if (amount === null || amount === undefined) return '-';
      const num = parseFloat(amount);
      if (isNaN(num)) return '-';

      const formatted = num.toLocaleString('en-US', {
         minimumFractionDigits: 2
      });
      const symbol = Config.getSetting('currency_symbol', '₵');

      return `${symbol} ${formatted}`;
   };

   document.addEventListener('DOMContentLoaded', () => {
      Config.log('Footer: DOMContentLoaded fired');

      // Check authentication FIRST - this must complete before anything else
      Config.log('Footer: Calling Auth.requireAuth()');
      if (!Auth.requireAuth()) {
         Config.log('Footer: Auth.requireAuth() returned false, redirecting to login');
         return; // Will redirect to login
      }
      Config.log('Footer: Auth.requireAuth() returned true');

      // Ensure token is restored and available
      const token = Auth.getToken();
      Config.log('Footer: Auth.getToken() returned:', token ? `token of length ${token.length}` : 'null');

      if (!token) {
         Config.error('No token available after requireAuth');

         // Debug: Check sessionStorage
         const sessionData = sessionStorage.getItem('alive_session');
         Config.error('SessionStorage alive_session:', sessionData ? 'exists' : 'missing');
         if (sessionData) {
            try {
               const parsed = JSON.parse(sessionData);
               Config.error('SessionStorage data:', {
                  hasToken: !!parsed.accessToken,
                  tokenLength: parsed.accessToken?.length,
                  timestamp: new Date(parsed.timestamp).toLocaleString(),
                  age: Date.now() - parsed.timestamp
               });
            } catch (e) {
               Config.error('Failed to parse sessionStorage:', e);
            }
         }

         window.location.href = '../login/';
         return;
      }

      Config.log('Auth initialized successfully, token length:', token.length);

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

      // Signal that auth is ready for page-specific scripts
      Config.log('Footer: Dispatching authReady event');
      window.dispatchEvent(new Event('authReady'));
   });
</script>
</body>

</html>