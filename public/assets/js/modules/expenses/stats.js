import { Alerts } from '../../utils/alerts.js';
import { Formatter } from '../../utils/formatter.js';

export class ExpenseStats {
    constructor(state, api, form) {
        this.state = state;
        this.api = api;
        this.form = form;
    }

    async load() {
        try {
            const stats = await this.api.getStats(this.state.selectedFiscalYearId);
            this.renderStatsCards(stats);
            this.renderTopExpenses(stats.top_expenses || []);
            this.renderByCategoryChart(stats.by_category || []);
            this.renderMonthlyTrendChart(stats.monthly_trend || []);
        } catch (error) {
            console.error('Load stats error:', error);
            this.renderStatsCards({});
            this.renderTopExpenses([]);
        }
    }

    renderStatsCards(stats) {
        const fyStatus = stats.fiscal_year?.status;
        const statusBadge = fyStatus === 'Closed' ? ' <span class="badge bg-secondary small">Closed</span>' : '';
        const row1Cards = [{
                title: `Total ${statusBadge}`,
                value: this.formatCurrency(stats.total_amount || 0),
                subtitle: `${(stats.total_count || 0).toLocaleString()} expenses`,
                icon: 'receipt',
                color: 'primary'
            },
            {
                title: 'Approved',
                value: this.formatCurrency(stats.approved_total || 0),
                subtitle: `${(stats.approved_count || 0).toLocaleString()} expenses`,
                icon: 'check-circle',
                color: 'success'
            },
            {
                title: 'Pending',
                value: this.formatCurrency(stats.pending_total || 0),
                subtitle: `${(stats.pending_count || 0).toLocaleString()} requests`,
                icon: 'clock-history',
                color: 'warning'
            },
            {
                title: 'Declined',
                value: this.formatCurrency(stats.rejected_total || 0),
                subtitle: `${(stats.rejected_count || 0).toLocaleString()} expenses`,
                icon: 'x-circle',
                color: 'danger'
            }
        ];
        const row2Cards = [{
                title: 'This Month',
                value: this.formatCurrency(stats.month_total || 0),
                subtitle: `<span class="badge bg-${(stats.month_growth || 0) >= 0 ? 'danger' : 'success'}">${(stats.month_growth || 0) >= 0 ? '+' : ''}${stats.month_growth || 0}%</span> vs last month`,
                icon: 'calendar-check',
                color: 'info'
            },
            {
                title: 'This Week',
                value: this.formatCurrency(stats.week_total || 0),
                subtitle: `${(stats.week_count || 0).toLocaleString()} expenses`,
                icon: 'calendar-week',
                color: 'secondary'
            },
            {
                title: 'Today',
                value: this.formatCurrency(stats.today_total || 0),
                subtitle: `${(stats.today_count || 0).toLocaleString()} expenses`,
                icon: 'calendar-day',
                color: 'dark'
            },
            {
                title: 'Average Expense',
                value: this.formatCurrency(stats.average_amount || 0),
                subtitle: 'Per transaction',
                icon: 'calculator',
                color: 'primary'
            }
        ];
        document.getElementById('statsCardsRow1').innerHTML = row1Cards.map(c => this.renderStatCard(c)).join('');
        document.getElementById('statsCardsRow2').innerHTML = row2Cards.map(c => this.renderStatCard(c)).join('');
    }

    renderStatCard(card) {
        return `<div class="col-lg-3 col-md-6"><div class="card stat-card bg-${card.color} bg-opacity-10 mb-3"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted mb-1 small">${card.title}</p><h4 class="mb-0">${card.value}</h4><small class="text-muted">${card.subtitle}</small></div><div class="stat-icon bg-${card.color} text-white rounded-circle"><i class="bi bi-${card.icon}"></i></div></div></div></div></div>`;
    }

    renderTopExpenses(expenses) {
        const tbody = document.getElementById('topExpensesBody');
        if (expenses.length > 0) {
            tbody.innerHTML = expenses.slice(0, 5).map(e => `<tr class="cursor-pointer" onclick="viewExpense(${e.ExpID})"><td><div class="fw-medium text-truncate" style="max-width:120px;" title="${e.ExpTitle}">${e.ExpTitle}</div></td><td><small class="text-muted">${e.CategoryName || '-'}</small></td><td class="text-end fw-semibold text-danger">${this.formatCurrency(parseFloat(e.ExpAmount))}</td></tr>`).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>';
        }
    }

    renderByCategoryChart(byCategory) {
        const ctx = document.getElementById('byCategoryChart').getContext('2d');
        if (this.state.byCategoryChart) this.state.byCategoryChart.destroy();
        if (!byCategory.length) {
            ctx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
        }
        const colors = ['#dc3545', '#fd7e14', '#ffc107', '#198754', '#0d6efd', '#6f42c1', '#20c997', '#6c757d'];
        this.state.byCategoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: byCategory.map(c => c.CategoryName),
                datasets: [{
                    data: byCategory.map(c => parseFloat(c.total)),
                    backgroundColor: colors.slice(0, byCategory.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.label}: ${this.formatCurrency(ctx.raw)}`
                        }
                    }
                }
            }
        });
    }

    renderMonthlyTrendChart(monthlyTrend) {
        const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
        if (this.state.monthlyTrendChart) this.state.monthlyTrendChart.destroy();
        if (!monthlyTrend.length) {
            ctx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
        }
        this.state.monthlyTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyTrend.map(m => m.month_label),
                datasets: [{
                    label: 'Expenses',
                    data: monthlyTrend.map(m => parseFloat(m.total)),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => this.formatCurrency(ctx.raw)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (v) => this.formatCurrencyShort(v)
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }

    formatCurrency(amount) {
        if (amount === null || amount === undefined) return '-';
        const num = parseFloat(amount);
        if (isNaN(num)) return '-';
        return `${this.state.currencySymbol} ${num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
    }

    formatCurrencyShort(amount) {
        if (amount >= 1000000) return `${this.state.currencySymbol}${(amount/1000000).toFixed(1)}M`;
        if (amount >= 1000) return `${this.state.currencySymbol}${(amount/1000).toFixed(1)}K`;
        return `${this.state.currencySymbol}${amount}`;
    }
}
