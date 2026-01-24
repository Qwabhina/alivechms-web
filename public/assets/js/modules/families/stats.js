/**
 * Family Statistics Component
 */

export class FamilyStats {
   constructor(state, api) {
      this.state = state;
      this.api = api;
   }

   async load() {
      try {
         const response = await this.api.getAll({ limit: 1000 });
         const families = Array.isArray(response) ? response : (response?.data || []);
         
         const totalMembers = families.reduce((sum, f) => sum + (parseInt(f.MemberCount) || 0), 0);
         const avgSize = families.length > 0 ? (totalMembers / families.length).toFixed(1) : 0;

         const thisMonth = new Date();
         thisMonth.setDate(1);
         thisMonth.setHours(0, 0, 0, 0);
         const newThisMonth = families.filter(f => f.CreatedAt && new Date(f.CreatedAt) >= thisMonth).length;

         this.renderCards({
            total: families.length,
            totalMembers,
            avgSize,
            newThisMonth
         });
      } catch (error) {
         console.error('Failed to load stats:', error);
         this.renderCards({
            total: 0,
            totalMembers: 0,
            avgSize: 0,
            newThisMonth: 0
         });
      }
   }

   renderCards(stats) {
      const cards = [
         {
            title: 'Total Families',
            value: stats.total,
            subtitle: 'All registered',
            icon: 'house-heart',
            color: 'primary'
         },
         {
            title: 'Total Members',
            value: stats.totalMembers,
            subtitle: 'In all families',
            icon: 'people',
            color: 'success'
         },
         {
            title: 'Average Size',
            value: stats.avgSize,
            subtitle: 'Members per family',
            icon: 'bar-chart',
            color: 'info'
         },
         {
            title: 'New This Month',
            value: stats.newThisMonth,
            subtitle: 'Recently added',
            icon: 'calendar-plus',
            color: 'warning'
         }
      ];

      const html = cards.map(card => `
         <div class="col-lg-3 col-md-6">
            <div class="card stat-card bg-${card.color} bg-opacity-25 mb-3">
               <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                     <div>
                        <p class="text-muted mb-1">${card.title}</p>
                        <h3 class="mb-0">${card.value}</h3>
                        <small class="text-muted">${card.subtitle}</small>
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
}
