/**
 * Family Table Component
 */

export class FamilyTable {
   constructor(state, api) {
      this.state = state;
      this.api = api;
      this.grid = null;
   }

   init() {
      this.grid = QMGridHelper.init('#familiesTable', {
         url: '/family/all',
         pageSize: 25,
         selectable: false,
         exportable: true,
         
         columns: this.getColumns(),
         
         exportOptions: {
            filename: 'church-families',
            dateFormat: 'DD/MM/YYYY',
            includeHeaders: true
         },

         onDataLoaded: (data) => {
            console.log(`✓ Loaded ${data.data.length} of ${data.total} families`);
            this.updateCount(data.total);
         },

         onError: (error) => {
            console.error('✗ Failed to load families:', error);
            Alerts.error('Failed to load families');
         }
      });

      console.log('✓ Families table initialized');
   }

   getColumns() {
      return [
         {
            key: 'FamilyName',
            title: 'Family Name',
            exportable: true,
            render: (value, row) => `
               <div class="d-flex align-items-center">
                  <div class="rounded-circle bg-primary bg-opacity-25 text-primary d-flex align-items-center justify-content-center me-2" style="width:38px;height:38px;">
                     <i class="bi bi-house-heart"></i>
                  </div>
                  <div>
                     <div class="fw-medium">${value || 'Unnamed'}</div>
                     <small class="text-muted">${row.MemberCount || 0} member${(row.MemberCount || 0) !== 1 ? 's' : ''}</small>
                  </div>
               </div>`
         },
         {
            key: 'HeadOfHouseholdName',
            title: 'Head of Household',
            exportable: true,
            render: (value) => value || '<span class="text-muted">Not assigned</span>'
         },
         {
            key: 'MemberCount',
            title: 'Members',
            width: '90px',
            exportable: true,
            render: (value) => `<span class="badge bg-secondary">${value || 0}</span>`
         },
         {
            key: 'CreatedAt',
            title: 'Created',
            width: '100px',
            exportable: true,
            render: (value) => QMGridHelper.formatDate(value, 'short')
         },
         {
            key: 'FamilyID',
            title: 'Actions',
            width: '130px',
            sortable: false,
            exportable: false,
            render: (value) => {
               const canView = Auth.hasPermission('manage_families');
               const canEdit = Auth.hasPermission('manage_families');
               const canDelete = Auth.hasPermission('manage_families');
               
               let html = '<div class="btn-group btn-group-sm" role="group">';
               
               if (canView) {
                  html += `<button class="btn btn-primary btn-sm" onclick="viewFamily(${value})" title="View">
                     <i class="bi bi-eye"></i>
                  </button>`;
               }
               
               if (canEdit) {
                  html += `<button class="btn btn-warning btn-sm" onclick="editFamily(${value})" title="Edit">
                     <i class="bi bi-pencil"></i>
                  </button>`;
               }
               
               if (canDelete) {
                  html += `<button class="btn btn-danger btn-sm" onclick="deleteFamily(${value})" title="Delete">
                     <i class="bi bi-trash"></i>
                  </button>`;
               }
               
               html += '</div>';
               return html;
            }
         }
      ];
   }

   refresh() {
      if (this.grid) {
         QMGridHelper.reload(this.grid);
      }
   }

   updateCount(total) {
      const countElement = document.getElementById('totalFamiliesCount');
      if (countElement) {
         countElement.textContent = total.toLocaleString();
      }
   }
}
