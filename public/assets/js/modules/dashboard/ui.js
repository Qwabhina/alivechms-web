export class DashboardUI {
    renderStats(data) {
        const membership = data.membership || {};
        const finance = data.finance || {};
        const pending = data.pending_approvals || {};

        const cards = [{
            title: 'Total Active Members',
            value: membership.total || 0,
            change: `+${membership.new_this_month || 0} this month`,
            icon: 'people',
            color: 'primary',
            link: 'members.php'
        },
        {
            title: 'Total Income',
            value: Utils.formatCurrency(parseFloat(finance.income || 0)),
            change: 'This fiscal year',
            icon: 'currency-dollar',
            color: 'success',
            link: 'contributions.php'
        },
        {
            title: 'Total Expenses',
            value: Utils.formatCurrency(parseFloat(finance.expenses || 0)),
            change: 'This fiscal year',
            icon: 'receipt',
            color: 'danger',
            link: 'expenses.php'
        },
        {
            title: 'Pending Approvals',
            value: (pending.budgets || 0) + (pending.expenses || 0),
            change: `${pending.budgets || 0} budgets, ${pending.expenses || 0} expenses`,
            icon: 'clock-history',
            color: 'warning',
            link: '#pendingApprovalsSection'
        }
        ];

        const html = cards.map(card => `
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card bg-${card.color} bg-opacity-25 mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="text-muted mb-1">${card.title}</p>
                                <h3 class="mb-0">${card.value}</h3>
                                <small class="text-muted">${card.change}</small>
                            </div>
                            <div class="stat-icon bg-${card.color} text-white text-opacity-50 rounded-circle p-3">
                                <i class="bi bi-${card.icon}"></i>
                            </div>
                        </div>
                        <a href="${card.link}" class="btn btn-sm btn-${card.color}">
                            View Details <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        `).join('');

        const container = document.getElementById('statsCards');
        if (container) container.innerHTML = html;
    }

    renderUpcomingEvents(events) {
        const container = document.getElementById('upcomingEvents');
        if (!container) return;

        if (events.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="bi bi-calendar-x fs-1"></i>
                    <p class="mb-0 mt-2">No upcoming events</p>
                </div>
            `;
            return;
        }

        const html = events.map(event => `
            <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                <div class="me-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${Utils.escapeHtml(event.EventTitle)}</h6>
                    <small class="text-muted">
                        <i class="bi bi-clock me-1"></i>${Utils.formatDate(event.EventDate)}
                        ${event.StartTime ? ` at ${event.StartTime}` : ''}
                    </small>
                    ${event.Location ? `<br><small class="text-muted"><i class="bi bi-geo-alt me-1"></i>${Utils.escapeHtml(event.Location)}</small>` : ''}
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderRecentActivity(activities) {
        const container = document.getElementById('recentActivity');
        if (!container) return;
        
        const countEl = document.getElementById('activityCount');
        if (countEl) countEl.textContent = activities.length;

        if (activities.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="bi bi-activity fs-1"></i>
                    <p class="mb-0 mt-2">No recent activity</p>
                </div>
            `;
            return;
        }

        const iconMap = {
            'Member Registered': {
                icon: 'person-plus',
                color: 'success'
            },
            'Contribution': {
                icon: 'currency-dollar',
                color: 'primary'
            },
            'Event Created': {
                icon: 'calendar-plus',
                color: 'info'
            },
            'Expense': {
                icon: 'receipt',
                color: 'danger'
            }
        };

        const html = activities.map(activity => {
            const info = iconMap[activity.type] || {
                icon: 'circle',
                color: 'secondary'
            };
            return `
                <div class="d-flex align-items-start mb-3">
                    <div class="me-3">
                        <div class="bg-${info.color} bg-opacity-10 text-${info.color} rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-${info.icon}"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${activity.type}</div>
                        <div class="text-muted small">${Utils.escapeHtml(activity.description)}</div>
                        <div class="text-muted small">${Utils.timeAgo(activity.timestamp)}</div>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    renderPendingApprovals(pending) {
        const total = (pending.budgets || 0) + (pending.expenses || 0);

        if (total === 0) return;

        const section = document.getElementById('pendingApprovalsSection');
        const countEl = document.getElementById('pendingCount');
        const container = document.getElementById('pendingApprovals');

        if (section) section.style.display = 'block';
        if (countEl) countEl.textContent = total;

        if (container) {
            const html = `
                <div class="col-md-6">
                    <div class="alert alert-warning mb-0">
                        <h6 class="alert-heading">
                            <i class="bi bi-folder-check me-2"></i>Budget Approvals
                        </h6>
                        <p class="mb-2">${pending.budgets || 0} budget(s) pending approval</p>
                        <a href="budgets.php?filter=pending" class="btn btn-sm btn-warning">Review Now</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info mb-0">
                        <h6 class="alert-heading">
                            <i class="bi bi-receipt-cutoff me-2"></i>Expense Approvals
                        </h6>
                        <p class="mb-2">${pending.expenses || 0} expense(s) pending approval</p>
                        <a href="expenses.php?filter=pending" class="btn btn-sm btn-info">Review Now</a>
                    </div>
                </div>
            `;
            container.innerHTML = html;
        }
    }
    
    showError() {
        const container = document.getElementById('statsCards');
        if (container) {
             container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Failed to load dashboard. Please refresh the page.
                    </div>
                </div>
            `;
        }
    }
}
