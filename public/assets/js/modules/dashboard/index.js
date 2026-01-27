import { DashboardAPI } from './api.js';
import { DashboardCharts } from './charts.js';
import { DashboardUI } from './ui.js';

class DashboardModule {
    constructor() {
        this.api = new DashboardAPI();
        this.charts = new DashboardCharts();
        this.ui = new DashboardUI();
    }

    async init() {
        // Expose loadDashboard globally for refresh button
        window.loadDashboard = () => this.loadDashboard();

        // Listen for auth ready
        window.addEventListener('authReady', () => {
            Config.log('Dashboard: authReady event received');
            window.authReadyFired = true;

            // Check permission
            if (!Auth.requirePermission(Config.PERMISSIONS.VIEW_DASHBOARD)) {
                Config.warn('Dashboard: Permission denied');
                window.location.href = '../login/';
                return;
            }

            Config.log('Dashboard: Permission granted, loading dashboard');
            this.loadDashboard();

            // Auto-refresh every 5 minutes
            setInterval(() => this.loadDashboard(), 5 * 60 * 1000);
        }, { once: true });

        // Fallback: if authReady doesn't fire within 2 seconds, try anyway
        setTimeout(() => {
            if (!window.authReadyFired) {
                Config.warn('Dashboard: authReady timeout, attempting to load anyway');
                if (Auth.isAuthenticated()) {
                    Config.log('Dashboard: User is authenticated, loading dashboard');
                    this.loadDashboard();
                } else {
                    Config.error('Dashboard: User not authenticated after timeout');
                }
            }
        }, 2000);
    }

    async loadDashboard() {
        try {
            const data = await this.api.getOverview();

            this.ui.renderStats(data);
            this.charts.renderAttendance(data.attendance_last_4_sundays || []);
            this.charts.renderFinance(data.finance || {});
            this.ui.renderUpcomingEvents(data.upcoming_events || []);
            this.ui.renderRecentActivity(data.recent_activity || []);

            if (Auth.hasPermission(Config.PERMISSIONS.APPROVE_EXPENSES) ||
                Auth.hasPermission(Config.PERMISSIONS.APPROVE_BUDGETS)) {
                this.ui.renderPendingApprovals(data.pending_approvals || {});
            }

        } catch (error) {
            Alerts.handleApiError(error, 'Failed to load dashboard data');
            this.ui.showError();
        }
    }
}

// Initialize
const module = new DashboardModule();
module.init();
