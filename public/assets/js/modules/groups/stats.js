/**
 * Groups Statistics Component
 */

export class GroupStats {
   constructor(state, api) {
      this.state = state;
      this.api = api;
   }

   init() {
      // Stats initialization if needed
   }

   async load() {
      try {
         const response = await this.api.getAll({ limit: 1000 });
         const groups = Array.isArray(response) ? response : (response?.data || []);
         
         const totalMembers = groups.reduce((sum, g) => sum + (parseInt(g.MemberCount) || 0), 0);
         const avgSize = groups.length > 0 ? (totalMembers / groups.length).toFixed(1) : 0;

         this.render({
            total: groups.length,
            totalMembers: totalMembers,
            types: this.state.groupTypesData.length,
            avgSize: avgSize
         });
      } catch (error) {
         console.error('Failed to load stats:', error);
         this.render({
            total: 0,
            totalMembers: 0,
            types: 0,
            avgSize: 0
         });
      }
   }

   render(stats) {
      const cards = [
         {
            title: 'Total Groups',
            value: stats.total,
            subtitle: 'All active groups',
            icon: 'people-fill',
            color: 'primary'
         },
         {
            title: 'Total Members',
            value: stats.totalMembers,
            subtitle: 'In all groups',
            icon: 'person-check',
            color: 'success'
         },
         {
            title: 'Group Types',
            value: stats.types,
            subtitle: 'Categories',
            icon: 'tags',
            color: 'info'
         },
         {
            title: 'Average Size',
            value: stats.avgSize,
            subtitle: 'Members per group',
            icon: 'bar-chart',
            color: 'warning'
         }
      ];

      document.getElementById('statsCards').innerHTML = cards.map(card => `
         <div class="col-lg-3 col-md-6">
            <div class="card stat-card bg-${card.color} bg-opacity-25 mb-3">
               <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                     <div>
                        <p class="text-muted mb-1">${card.title}</p>
                        <h3 class="mb-0">${card.value}</h3>
                        <small class="text-muted">${card.subtitle}</small>
                     </div>
                     <div class="stat-icon bg-${card.color} text-white rounded-circle p-3">
                        <i class="bi bi-${card.icon}"></i>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      `).join('');
   }
}
