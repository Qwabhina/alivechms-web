/**
 * Milestones Statistics Component
 */

export class MilestoneStats {
   constructor(state, api) {
      this.state = state;
      this.api = api;
      this.charts = {
         byType: null,
         monthlyTrend: null
      };
   }

   init() {
      this.initYearSelector();
      console.log('✓ Milestone stats initialized');
   }

   initYearSelector() {
      const currentYear = new Date().getFullYear();
      const yearSelect = document.getElementById('statsYear');
      
      if (!yearSelect) return;

      // Generate years (current year and 5 years back)
      yearSelect.innerHTML = '';
      for (let i = 0; i <= 5; i++) {
         const year = currentYear - i;
         const option = document.createElement('option');
         option.value = year;
         option.textContent = year;
         if (i === 0) option.selected = true;
         yearSelect.appendChild(option);
      }

      yearSelect.addEventListener('change', () => {
         const year = parseInt(yearSelect.value);
         this.state.setCurrentYear(year);
         this.load(year);
      });
   }

   async load(year = null) {
      try {
         const response = await this.api.getStats(year);
         const stats = response?.data || response;
         
         this.state.setStats(stats);
         this.renderCards(stats);
         this.renderCharts(stats);
         this.renderRecentMilestones(stats.recent || []);
         
         console.log('✓ Stats loaded:', stats);
      } catch (error) {
         console.error('Load stats error:', error);
         this.renderError();
      }
   }

   renderCards(stats) {
      // Row 1: Summary cards
      const row1 = document.getElementById('statsCardsRow1');
      if (row1) {
         row1.innerHTML = `
            <div class="col-lg-3 col-md-6 mb-3">
               <div class="card stat-card bg-primary bg-opacity-10">
                  <div class="card-body">
                     <div class="d-flex justify-content-between align-items-start">
                        <div>
                           <p class="text-muted mb-1">Total Milestones</p>
                           <h3 class="mb-0">${stats.total_count || 0}</h3>
                           <small class="text-muted">All time</small>
                        </div>
                        <div class="stat-icon bg-primary text-white rounded-circle p-3">
                           <i class="bi bi-trophy"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
               <div class="card stat-card bg-success bg-opacity-10">
                  <div class="card-body">
                     <div class="d-flex justify-content-between align-items-start">
                        <div>
                           <p class="text-muted mb-1">This Year</p>
                           <h3 class="mb-0">${stats.year_count || 0}</h3>
                           <small class="text-muted">${stats.current_year || new Date().getFullYear()}</small>
                        </div>
                        <div class="stat-icon bg-success text-white rounded-circle p-3">
                           <i class="bi bi-calendar-check"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
               <div class="card stat-card bg-info bg-opacity-10">
                  <div class="card-body">
                     <div class="d-flex justify-content-between align-items-start">
                        <div>
                           <p class="text-muted mb-1">This Month</p>
                           <h3 class="mb-0">${stats.month_count || 0}</h3>
                           <small class="text-muted">${new Date().toLocaleDateString('en-US', { month: 'long' })}</small>
                        </div>
                        <div class="stat-icon bg-info text-white rounded-circle p-3">
                           <i class="bi bi-calendar-event"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
               <div class="card stat-card bg-warning bg-opacity-10">
                  <div class="card-body">
                     <div class="d-flex justify-content-between align-items-start">
                        <div>
                           <p class="text-muted mb-1">Milestone Types</p>
                           <h3 class="mb-0">${stats.by_type?.length || 0}</h3>
                           <small class="text-muted">Active types</small>
                        </div>
                        <div class="stat-icon bg-warning text-white rounded-circle p-3">
                           <i class="bi bi-tags"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         `;
      }

      // Row 2: By type cards
      const row2 = document.getElementById('statsCardsRow2');
      if (row2 && stats.by_type && stats.by_type.length > 0) {
         const colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
         row2.innerHTML = stats.by_type.slice(0, 6).map((type, index) => `
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
               <div class="card stat-card bg-${colors[index % colors.length]} bg-opacity-10 h-100">
                  <div class="card-body text-center">
                     <h4 class="mb-1">${type.count || 0}</h4>
                     <p class="mb-0 small text-muted">${type.MilestoneTypeName}</p>
                  </div>
               </div>
            </div>
         `).join('');
      }
   }

   renderCharts(stats) {
      this.renderByTypeChart(stats.by_type || []);
      this.renderMonthlyTrendChart(stats.monthly_trend || []);
   }

   renderByTypeChart(byType) {
      const canvas = document.getElementById('byTypeChart');
      if (!canvas) return;

      // Destroy existing chart
      if (this.charts.byType) {
         this.charts.byType.destroy();
      }

      if (byType.length === 0) {
         canvas.parentElement.innerHTML = '<p class="text-muted text-center py-4">No data available</p>';
         return;
      }

      const ctx = canvas.getContext('2d');
      const colors = [
         '#0d6efd', '#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6c757d',
         '#6f42c1', '#d63384', '#fd7e14', '#20c997'
      ];

      this.charts.byType = new Chart(ctx, {
         type: 'doughnut',
         data: {
            labels: byType.map(t => t.MilestoneTypeName),
            datasets: [{
               data: byType.map(t => t.count),
               backgroundColor: colors.slice(0, byType.length),
               borderWidth: 2,
               borderColor: '#fff'
            }]
         },
         options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
               legend: {
                  position: 'bottom',
                  labels: { padding: 10, font: { size: 11 } }
               }
            }
         }
      });
   }

   renderMonthlyTrendChart(monthlyTrend) {
      const canvas = document.getElementById('monthlyTrendChart');
      if (!canvas) return;

      // Destroy existing chart
      if (this.charts.monthlyTrend) {
         this.charts.monthlyTrend.destroy();
      }

      if (monthlyTrend.length === 0) {
         canvas.parentElement.innerHTML = '<p class="text-muted text-center py-4">No data available</p>';
         return;
      }

      const ctx = canvas.getContext('2d');

      this.charts.monthlyTrend = new Chart(ctx, {
         type: 'line',
         data: {
            labels: monthlyTrend.map(m => m.month_label),
            datasets: [{
               label: 'Milestones',
               data: monthlyTrend.map(m => m.count),
               borderColor: '#0d6efd',
               backgroundColor: 'rgba(13, 110, 253, 0.1)',
               tension: 0.4,
               fill: true
            }]
         },
         options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
               legend: { display: false }
            },
            scales: {
               y: {
                  beginAtZero: true,
                  ticks: { stepSize: 1 }
               }
            }
         }
      });
   }

   renderRecentMilestones(recent) {
      const tbody = document.getElementById('recentMilestonesBody');
      if (!tbody) return;

      if (recent.length === 0) {
         tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No recent milestones</td></tr>';
         return;
      }

      tbody.innerHTML = recent.map(m => `
         <tr>
            <td class="small">${m.MbrFirstName} ${m.MbrFamilyName}</td>
            <td class="small">${m.MilestoneTypeName}</td>
            <td class="small">${new Date(m.MilestoneDate).toLocaleDateString()}</td>
         </tr>
      `).join('');
   }

   renderError() {
      const row1 = document.getElementById('statsCardsRow1');
      if (row1) {
         row1.innerHTML = '<div class="col-12"><div class="alert alert-danger">Failed to load statistics</div></div>';
      }
   }

   updateTotalCount(count) {
      // Update total count in stats card
      const totalCard = document.querySelector('#statsCardsRow1 h3');
      if (totalCard) {
         totalCard.textContent = count;
      }
   }
}
