/**
 * Groups Table Component
 */

export class GroupTable {
   constructor(state, api, stats = null) {
      this.state = state;
      this.api = api;
      this.stats = stats;
      this.grid = null;
   }

   init() {
      this.grid = QMGridHelper.init('#groupsTable', {
         url: `${Config.API_BASE_URL}/group/all`,
         pageSize: 25,
         selectable: false,
         exportable: true,
         columns: [
            {
               key: 'GroupName',
               title: 'Group Name',
               render: (value, row) => `
                  <div class="d-flex align-items-center">
                     <div class="rounded-circle bg-success bg-opacity-25 text-success d-flex align-items-center justify-content-center me-2" style="width:38px;height:38px;">
                        <i class="bi bi-people"></i>
                     </div>
                     <div>
                        <div class="fw-medium">${value}</div>
                        <small class="text-muted">${row.MemberCount || 0} members</small>
                     </div>
                  </div>
               `
            },
            {
               key: 'GroupTypeName',
               title: 'Type',
               render: (value) => value ? `<span class="badge bg-secondary">${value}</span>` : '-'
            },
            {
               key: 'LeaderFirstName',
               title: 'Leader',
               render: (value, row) => {
                  const name = `${row.LeaderFirstName || ''} ${row.LeaderFamilyName || ''}`.trim();
                  return name || '<span class="text-muted">Not assigned</span>';
               }
            },
            {
               key: 'GroupID',
               title: 'Actions',
               width: '130px',
               sortable: false,
               exportable: false,
               render: (value) => `
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-primary btn-sm" onclick="viewGroup(${value})" title="View">
                        <i class="bi bi-eye"></i>
                     </button>
                     <button class="btn btn-warning btn-sm" onclick="editGroup(${value})" title="Edit">
                        <i class="bi bi-pencil"></i>
                     </button>
                     <button class="btn btn-danger btn-sm" onclick="deleteGroup(${value})" title="Delete">
                        <i class="bi bi-trash"></i>
                     </button>
                  </div>
               `
            }
         ],
         onDataLoaded: (data) => {
            const total = data.pagination?.total || data.total || 0;
            document.getElementById('totalGroupsCount').textContent = total;
            console.log(`✓ Loaded ${data.dataLength || 0} of ${total} groups`);
         }
      });

      this.initEventListeners();
      console.log('✓ Groups table initialized');
   }

   initEventListeners() {
      document.getElementById('refreshGroupGrid')?.addEventListener('click', () => {
         this.refresh();
         if (this.stats) {
            this.stats.load();
         }
      });
   }

   refresh() {
      QMGridHelper.reload(this.grid);
   }
}
