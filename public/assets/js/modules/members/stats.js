/**
 * Member Statistics Component
 */

export class MemberStats {
   constructor(state, api) {
      this.state = state;
      this.api = api;
      this.genderChart = null;
      this.ageChart = null;
   }

   async load() {
      try {
         const response = await this.api.getStats();
         const stats = response?.data || response || {};
         
         this.renderCards({
            total: stats.total || 0,
            active: stats.active || 0,
            inactive: stats.inactive || 0,
            newThisMonth: stats.new_this_month || 0
         });

         this.renderGenderChart(stats.gender_distribution || {});
         this.renderAgeChart(stats.age_distribution || {});
      } catch (error) {
         console.error('Failed to load stats:', error);
         // Fallback to basic stats
         this.renderCards({ total: 0, active: 0, inactive: 0, newThisMonth: 0 });
         this.renderGenderChart({});
         this.renderAgeChart({});
      }
   }

   renderCards(stats) {
      const cards = [
         {
            title: 'Total Members',
            value: stats.total || 0,
            change: 'All registered members',
            icon: 'people',
            color: 'primary'
         },
         {
            title: 'Active Members',
            value: stats.active || 0,
            change: 'Currently active',
            icon: 'person-check',
            color: 'success'
         },
         {
            title: 'Inactive Members',
            value: stats.inactive || 0,
            change: 'Marked inactive',
            icon: 'person-dash',
            color: 'danger'
         },
         {
            title: 'New This Month',
            value: stats.newThisMonth || 0,
            change: 'Registered this month',
            icon: 'calendar-plus',
            color: 'warning'
         }
      ];

      const html = cards.map(card => `
         <div class="col-lg-3 col-md-6">
            <div class="card stat-card bg-${card.color} bg-opacity-25 mb-4">
               <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                     <div>
                        <p class="text-muted mb-1">${card.title}</p>
                        <h3 class="mb-0">${card.value.toLocaleString()}</h3>
                        <small class="text-muted">${card.change}</small>
                     </div>
                     <div class="stat-icon bg-${card.color} text-white text-opacity-50 rounded-circle p-3">
                        <i class="bi bi-${card.icon}"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      `).join('');

      document.getElementById('statsCards').innerHTML = html;
   }

   renderGenderChart(genderData) {
      const canvas = document.getElementById('genderChart');
      if (!canvas) return;

      if (this.genderChart) {
         this.genderChart.destroy();
      }

      const labels = Object.keys(genderData);
      const data = Object.values(genderData);

      if (labels.length === 0) {
         canvas.parentElement.innerHTML = '<p class="text-muted text-center py-5">No gender data available</p>';
         return;
      }

      const colors = {
         'Male': '#4e73df',
         'Female': '#e74a3b',
         'Other': '#f6c23e',
         'Unknown': '#858796'
      };

      this.genderChart = new Chart(canvas, {
         type: 'doughnut',
         data: {
            labels: labels,
            datasets: [{
               data: data,
               backgroundColor: labels.map(l => colors[l] || '#858796'),
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
                  labels: {
                     padding: 15,
                     usePointStyle: true
                  }
               },
               tooltip: {
                  callbacks: {
                     label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.raw / total) * 100).toFixed(1);
                        return `${context.label}: ${context.raw} (${percentage}%)`;
                     }
                  }
               }
            },
            cutout: '60%'
         }
      });
   }

   renderAgeChart(ageData) {
      const canvas = document.getElementById('ageChart');
      if (!canvas) return;

      if (this.ageChart) {
         this.ageChart.destroy();
      }

      const ageOrder = ['Under 18', '18-30', '31-45', '46-60', 'Over 60', 'Unknown'];
      const labels = ageOrder.filter(age => ageData[age] !== undefined);
      const data = labels.map(age => ageData[age] || 0);

      if (labels.length === 0 || data.every(d => d === 0)) {
         canvas.parentElement.innerHTML = '<p class="text-muted text-center py-5">No age data available</p>';
         return;
      }

      const colors = ['#36b9cc', '#1cc88a', '#4e73df', '#f6c23e', '#e74a3b', '#858796'];

      this.ageChart = new Chart(canvas, {
         type: 'bar',
         data: {
            labels: labels,
            datasets: [{
               label: 'Members',
               data: data,
               backgroundColor: colors.slice(0, labels.length),
               borderRadius: 4,
               borderSkipped: false
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
                     label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.raw / total) * 100).toFixed(1);
                        return `${context.raw} members (${percentage}%)`;
                     }
                  }
               }
            },
            scales: {
               y: {
                  beginAtZero: true,
                  ticks: {
                     stepSize: 1
                  },
                  grid: {
                     display: true,
                     drawBorder: false
                  }
               },
               x: {
                  grid: {
                     display: false
                  }
               }
            }
         }
      });
   }
}
