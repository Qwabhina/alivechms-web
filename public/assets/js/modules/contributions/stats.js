
/**
 * Contribution Statistics & Charts
 */

export class ContributionStats {
   constructor(state, api) {
      this.state = state;
      this.api = api;
   }

   async init() {
      await this.loadFiscalYears();
   }

   async loadFiscalYears() {
      try {
         const fiscalRes = await this.api.getFiscalYears();
         this.state.fiscalYears = Array.isArray(fiscalRes) ? fiscalRes : (fiscalRes?.data || []);

         const select = document.getElementById('statsFiscalYear');
         if (!select) return;
         
         select.innerHTML = '';

         // Find active fiscal year
         const activeFY = this.state.fiscalYears.find(fy => fy.Status === 'Active');
         this.state.selectedFiscalYearId = activeFY?.FiscalYearID || null;

         // Populate options
         this.state.fiscalYears.forEach(fy => {
            const opt = document.createElement('option');
            opt.value = fy.FiscalYearID;
            opt.textContent = fy.FiscalYearName + (fy.Status === 'Active' ? ' (Active)' : fy.Status === 'Closed' ? ' (Closed)' : '');
            if (fy.FiscalYearID === this.state.selectedFiscalYearId) opt.selected = true;
            select.appendChild(opt);
         });

         // Initialize Choices.js
         if (this.state.statsFiscalYearChoices) this.state.statsFiscalYearChoices.destroy();
         this.state.statsFiscalYearChoices = new Choices(select, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search fiscal years...',
            itemSelectText: '',
            allowHTML: true,
            shouldSort: false
         });

         // Add change listener
         select.addEventListener('change', (e) => {
            this.state.selectedFiscalYearId = e.target.value ? parseInt(e.target.value) : null;
            this.load();
            // Trigger table reload event or callback
            document.dispatchEvent(new CustomEvent('fiscalYearChanged', { 
               detail: { fiscalYearId: this.state.selectedFiscalYearId } 
            }));
         });

      } catch (error) {
         console.error('Load fiscal years error:', error);
      }
   }

   async load() {
      try {
         const stats = await this.api.getStats(this.state.selectedFiscalYearId);
         this.state.statsData = stats;

         this.renderCards(stats);
         this.renderTopContributors(stats.top_contributors || []);
         this.renderByTypeChart(stats.by_type || []);
         this.renderMonthlyTrendChart(stats.monthly_trend || []);
      } catch (error) {
         console.error('Load stats error:', error);
         this.renderCards({});
         this.renderTopContributors([]);
      }
   }

   renderCards(stats) {
      const fyStatus = stats.fiscal_year?.status;
      const statusBadge = fyStatus === 'Closed' ? ' <span class="badge bg-secondary small">Closed</span>' : '';

      const row1Cards = [{
            title: `Total ${statusBadge}`,
            value: formatCurrency(stats.total_amount || 0),
            subtitle: `${(stats.total_count || 0).toLocaleString()} contributions`,
            icon: 'cash-stack',
            color: 'primary'
         },
         {
            title: 'This Month',
            value: formatCurrency(stats.month_total || 0),
            subtitle: `<span class="badge bg-${(stats.month_growth || 0) >= 0 ? 'success' : 'danger'}">${(stats.month_growth || 0) >= 0 ? '+' : ''}${stats.month_growth || 0}%</span> vs last month`,
            icon: 'calendar-check',
            color: 'success'
         },
         {
            title: 'This Week',
            value: formatCurrency(stats.week_total || 0),
            subtitle: `${(stats.week_count || 0).toLocaleString()} contributions`,
            icon: 'calendar-week',
            color: 'info'
         },
         {
            title: 'Today',
            value: formatCurrency(stats.today_total || 0),
            subtitle: `${(stats.today_count || 0).toLocaleString()} contributions`,
            icon: 'calendar-day',
            color: 'warning'
         }
      ];

      const row2Cards = [{
            title: 'Average Contribution',
            value: formatCurrency(stats.average_amount || 0),
            subtitle: 'Per transaction',
            icon: 'calculator',
            color: 'secondary'
         },
         {
            title: 'Avg Per Contributor',
            value: formatCurrency(stats.average_per_contributor || 0),
            subtitle: 'Per member',
            icon: 'person-check',
            color: 'dark'
         },
         {
            title: 'Unique Contributors',
            value: (stats.unique_contributors || 0).toLocaleString(),
            subtitle: 'Active givers',
            icon: 'people',
            color: 'primary'
         },
         {
            title: 'Last Month',
            value: formatCurrency(stats.last_month_total || 0),
            subtitle: 'Previous month total',
            icon: 'calendar-minus',
            color: 'secondary'
         }
      ];

      const row1 = document.getElementById('statsCardsRow1');
      const row2 = document.getElementById('statsCardsRow2');
      
      if (row1) row1.innerHTML = row1Cards.map(card => this.createCardHtml(card)).join('');
      if (row2) row2.innerHTML = row2Cards.map(card => this.createCardHtml(card)).join('');
   }

   createCardHtml(card) {
      return `
      <div class="col-lg-3 col-md-6">
         <div class="card stat-card bg-${card.color} bg-opacity-10 mb-3">
            <div class="card-body py-3">
               <div class="d-flex justify-content-between align-items-start">
                  <div>
                     <p class="text-muted mb-1 small">${card.title}</p>
                     <h4 class="mb-0">${card.value}</h4>
                     <small class="text-muted">${card.subtitle}</small>
                  </div>
                  <div class="stat-icon bg-${card.color} text-white rounded-circle">
                     <i class="bi bi-${card.icon}"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>`;
   }

   renderTopContributors(contributors) {
      const tbody = document.getElementById('topContributorsBody');
      if (!tbody) return;

      if (contributors.length > 0) {
         tbody.innerHTML = contributors.slice(0, 5).map((c, i) => `
         <tr class="cursor-pointer" onclick="showMemberStatement(${c.MbrID})">
            <td class="text-center">
               ${i === 0 ? '<i class="bi bi-trophy-fill text-warning"></i>' : 
                 i === 1 ? '<i class="bi bi-trophy-fill text-secondary"></i>' :
                 i === 2 ? '<i class="bi bi-trophy-fill" style="color:#cd7f32;"></i>' : 
                 `<span class="text-muted">${i + 1}</span>`}
            </td>
            <td>
               <div class="fw-medium">${c.MbrFirstName} ${c.MbrFamilyName}</div>
               <small class="text-muted">${c.contribution_count} contributions</small>
            </td>
            <td class="text-end fw-semibold text-success">${formatCurrency(parseFloat(c.total))}</td>
         </tr>
         `).join('');
      } else {
         tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>';
      }
   }

   renderByTypeChart(byType) {
      const canvas = document.getElementById('byTypeChart');
      if (!canvas) return;
      
      const ctx = canvas.getContext('2d');
      if (this.state.byTypeChart) this.state.byTypeChart.destroy();

      const colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d'];

      this.state.byTypeChart = new Chart(ctx, {
         type: 'doughnut',
         data: {
            labels: byType.map(t => t.ContributionTypeName),
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
                  labels: { boxWidth: 12, padding: 8, font: { size: 11 } }
               },
               tooltip: {
                  callbacks: { label: (ctx) => `${ctx.label}: ${formatCurrency(ctx.raw)}` }
               }
            }
         }
      });
   }

   renderMonthlyTrendChart(monthlyTrend) {
      const canvas = document.getElementById('monthlyTrendChart');
      if (!canvas) return;
      
      const ctx = canvas.getContext('2d');
      if (this.state.monthlyTrendChart) this.state.monthlyTrendChart.destroy();

      this.state.monthlyTrendChart = new Chart(ctx, {
         type: 'line',
         data: {
            labels: monthlyTrend.map(m => m.month_label),
            datasets: [{
               label: 'Contributions',
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
               legend: { display: false },
               tooltip: { callbacks: { label: (ctx) => formatCurrency(ctx.raw) } }
            },
            scales: {
               y: {
                  beginAtZero: true,
                  ticks: { callback: (value) => formatCurrencyShort(value) }
               },
               x: { ticks: { font: { size: 10 } } }
            }
         }
      });
   }
}
