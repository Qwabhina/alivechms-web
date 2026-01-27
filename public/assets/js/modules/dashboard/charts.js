export class DashboardCharts {
    constructor() {
        this.attendanceChart = null;
        this.financeChart = null;
    }

    renderAttendance(data) {
        const ctx = document.getElementById('attendanceChart');
        if (!ctx) return;

        if (this.attendanceChart) {
            this.attendanceChart.destroy();
        }

        const dates = data.map(d => Utils.formatDate(d.date, 'M d'));
        const attendance = data.map(d => parseInt(d.present) || 0);

        this.attendanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Attendance',
                    data: attendance,
                    borderColor: Config.CHART_COLORS.primary,
                    backgroundColor: Config.CHART_COLORS.primary + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10
                        }
                    }
                }
            }
        });
    }

    renderFinance(data) {
        const ctx = document.getElementById('financeChart');
        if (!ctx) return;

        if (this.financeChart) {
            this.financeChart.destroy();
        }

        // Safely parse numeric values with fallbacks
        const income = parseFloat(data.income || 0);
        const expenses = parseFloat(data.expenses || 0);
        const net = parseFloat(data.net || (income - expenses));

        // Update text values
        const totalIncomeEl = document.getElementById('totalIncome');
        const totalExpensesEl = document.getElementById('totalExpenses');
        const netBalanceEl = document.getElementById('netBalance');

        if (totalIncomeEl) totalIncomeEl.textContent = Utils.formatCurrency(income);
        if (totalExpensesEl) totalExpensesEl.textContent = Utils.formatCurrency(expenses);
        if (netBalanceEl) {
            netBalanceEl.textContent = Utils.formatCurrency(net);
            netBalanceEl.className = net >= 0 ? 'text-success' : 'text-danger';
        }

        this.financeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Income', 'Expenses'],
                datasets: [{
                    data: [income, expenses],
                    backgroundColor: [
                        Config.CHART_COLORS.success,
                        Config.CHART_COLORS.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }
}
