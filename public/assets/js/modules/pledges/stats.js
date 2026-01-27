import { Alerts } from '../../utils/alerts.js';

export class PledgeStats {
    constructor(state, api, form) {
        this.state = state;
        this.api = api;
        this.form = form;
    }

    async load() {
        try {
            const stats = await this.api.getStats(this.state.selectedFiscalYearId);
            this.renderStatsCards(stats);
            this.renderTopPledgers(stats.top_pledgers || []);
            this.renderByTypeChart(stats.by_type || []);
            this.renderMonthlyTrendChart(stats.monthly_trend || []);
        } catch (error) {
            console.error('Load stats error:', error);
            this.renderStatsCards({});
            this.renderTopPledgers([]);
        }
    }

    renderStatsCards(stats) {
        const fyStatus = stats.fiscal_year?.status;
        const statusBadge = fyStatus === 'Closed' ? ' <span class="badge bg-secondary small">Closed</span>' : '';
        const row1Cards = [{
                title: `Total Pledged ${statusBadge}`,
                value: this.formatCurrency(stats.total_amount || 0),
                subtitle: `${(stats.total_count || 0).toLocaleString()} pledges`,
                icon: 'bookmark-heart',
                color: 'primary'
            },
            {
                title: 'Fulfilled',
                value: this.formatCurrency(stats.fulfilled_amount || 0),
                subtitle: `${(stats.fulfilled_count || 0).toLocaleString()} pledges`,
                icon: 'check-circle',
                color: 'success'
            },
            {
                title: 'Active',
                value: this.formatCurrency(stats.active_amount || 0),
                subtitle: `${(stats.active_count || 0).toLocaleString()} pledges`,
                icon: 'hourglass-split',
                color: 'warning'
            },
            {
                title: 'Payments Received',
                value: this.formatCurrency(stats.payments_total || 0),
                subtitle: `${(stats.payments_count || 0).toLocaleString()} payments`,
                icon: 'cash-stack',
                color: 'info'
            }
        ];
        const row2Cards = [{
                title: 'Outstanding',
                value: this.formatCurrency(stats.outstanding_amount || 0),
                subtitle: 'Balance remaining',
                icon: 'exclamation-triangle',
                color: 'danger'
            },
            {
                title: 'Overdue',
                value: this.formatCurrency(stats.overdue_amount || 0),
                subtitle: `${(stats.overdue_count || 0).toLocaleString()} pledges`,
                icon: 'alarm',
                color: 'danger'
            },
            {
                title: 'Fulfillment Rate',
                value: `${stats.fulfillment_rate || 0}%`,
                subtitle: 'Pledges completed',
                icon: 'graph-up-arrow',
                color: 'success'
            },
            {
                title: 'Cancelled',
                value: this.formatCurrency(stats.cancelled_amount || 0),
                subtitle: `${(stats.cancelled_count || 0).toLocaleString()} pledges`,
                icon: 'x-circle',
                color: 'secondary'
            }
        ];
        document.getElementById('statsCardsRow1').innerHTML = row1Cards.map(c => this.renderStatCard(c)).join('');
        document.getElementById('statsCardsRow2').innerHTML = row2Cards.map(c => this.renderStatCard(c)).join('');
    }

    renderStatCard(card) {
        return `<div class="col-lg-3 col-md-6"><div class="card stat-card bg-${card.color} bg-opacity-10 mb-3"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted mb-1 small">${card.title}</p><h4 class="mb-0">${card.value}</h4><small class="text-muted">${card.subtitle}</small></div><div class="stat-icon bg-${card.color} text-white rounded-circle"><i class="bi bi-${card.icon}"></i></div></div></div></div></div>`;
    }

    renderTopPledgers(pledgers) {
        const tbody = document.getElementById('topPledgersBody');
        if (pledgers.length > 0) {
            tbody.innerHTML = pledgers.slice(0, 5).map(p => `<tr><td><div class="fw-medium">${p.MbrFirstName} ${p.MbrFamilyName}</div><small class="text-muted">${p.pledge_count} pledge(s)</small></td><td class="text-end fw-semibold text-primary">${this.formatCurrency(parseFloat(p.total_pledged))}</td></tr>`).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">No data available</td></tr>';
        }
    }

    renderByTypeChart(byType) {
        const ctx = document.getElementById('byTypeChart').getContext('2d');
        if (this.state.byTypeChart) this.state.byTypeChart.destroy();
        if (!byType.length) {
            ctx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
        }
        const colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#6c757d'];
        this.state.byTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: byType.map(t => t.PledgeTypeName),
                datasets: [{
                    data: byType.map(t => parseFloat(t.total)),
                    backgroundColor: colors.slice(0, byType.length),
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
                    label: 'Pledges',
                    data: monthlyTrend.map(m => parseFloat(m.total)),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
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
