
/**
 * Contribution Table Management
 */

export class ContributionTable {
   constructor(state, api, stats) {
      this.state = state;
      this.api = api;
      this.stats = stats;
   }

   init() {
      this.initTable();
      this.initEventListeners();
      
      // Listen for fiscal year changes
      document.addEventListener('fiscalYearChanged', () => {
         this.reload();
      });
   }

   initTable(filters = {}) {
      let url = `${Config.API_BASE_URL}/contribution/all`;
      const params = new URLSearchParams();

      // Always include the selected fiscal year
      if (this.state.selectedFiscalYearId) {
         params.append('fiscal_year_id', this.state.selectedFiscalYearId);
      }

      if (filters.contribution_type_id) params.append('contribution_type_id', filters.contribution_type_id);
      if (filters.start_date) params.append('start_date', filters.start_date);
      if (filters.end_date) params.append('end_date', filters.end_date);
      if (params.toString()) url += '?' + params.toString();

      this.state.contributionsTable = QMGridHelper.init('#contributionsTable', {
         url: url,
         pageSize: 25,
         selectable: false,
         exportable: true,
         columns: [{
               key: 'MbrFirstName',
               title: 'Member',
               render: (value, row) => `
               <div class="d-flex align-items-center">
                  <div class="rounded-circle bg-primary bg-opacity-25 text-primary d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;font-size:0.85rem;">
                     ${(row.MbrFirstName?.[0] || '') + (row.MbrFamilyName?.[0] || '')}
                  </div>
                  <div>
                     <div class="fw-medium">${row.MbrFirstName || ''} ${row.MbrFamilyName || ''}</div>
                  </div>
               </div>`
            },
            {
               key: 'ContributionAmount',
               title: 'Amount',
               render: (value) => `<span class="fw-semibold text-success">${formatCurrency(value)}</span>`
            },
            {
               key: 'ContributionDate',
               title: 'Date',
               render: (value) => QMGridHelper.formatDate(value, 'short')
            },
            {
               key: 'ContributionTypeName',
               title: 'Type',
               render: (value) => value ? `<span class="badge bg-secondary">${value}</span>` : '-'
            },
            {
               key: 'PaymentOptionName',
               title: 'Payment',
               render: (value) => value || '-'
            },
            {
               key: 'ContributionID',
               title: 'Actions',
               width: '180px',
               sortable: false,
               exportable: false,
               render: (value, row) => {
                  const isDeleted = row.Deleted == 1;
                  if (isDeleted) {
                     return `
                     <div class="btn-group btn-group-sm">
                        <button class="btn btn-success btn-sm" onclick="restoreContribution(${value})" title="Restore">
                           <i class="bi bi-arrow-counterclockwise"></i> Restore
                        </button>
                        <span class="badge bg-danger ms-2">Deleted</span>
                     </div>`;
                  }
                  return `
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-primary btn-sm" onclick="viewContribution(${value})" title="View"><i class="bi bi-eye"></i></button>
                     <button class="btn btn-success btn-sm" onclick="showReceipt(${value})" title="Receipt"><i class="bi bi-receipt"></i></button>
                     <button class="btn btn-warning btn-sm" onclick="editContribution(${value})" title="Edit"><i class="bi bi-pencil"></i></button>
                     <button class="btn btn-danger btn-sm" onclick="deleteContribution(${value})" title="Delete"><i class="bi bi-trash"></i></button>
                  </div>`;
               }
            }
         ],
         onDataLoaded: (data) => {
            document.getElementById('totalContributionsCount').textContent = data.pagination?.total || data.total || 0;
         }
      });
   }

   reload() {
      if (this.state.contributionsTable) {
         // QMGridHelper doesn't support changing URL on the fly easily without re-init or custom reload
         // But QMGridHelper.reload() just refreshes. 
         // Since filters might change the URL, we might need to destroy and re-init or use updateUrl if available.
         // Let's assume re-init is safer for now if filters change.
         // However, the original code called initTable() again which overwrites.
         
         // Get current filters
         const filters = {
             contribution_type_id: document.getElementById('filterType')?.value,
             start_date: document.getElementById('filterStartDate')?.value,
             end_date: document.getElementById('filterEndDate')?.value
         };
         
         // Destroy existing if possible, or just overwrite
         // QMGridHelper.init returns an instance.
         
         this.initTable(filters);
      }
   }

   initEventListeners() {
      document.getElementById('applyFiltersBtn')?.addEventListener('click', () => this.reload());
      document.getElementById('clearFiltersBtn')?.addEventListener('click', () => {
         document.getElementById('filterType').value = '';
         document.getElementById('filterStartDate').value = '';
         document.getElementById('filterEndDate').value = '';
         document.getElementById('showDeletedCheckbox').checked = false;
         this.reload();
      });
      document.getElementById('refreshGrid')?.addEventListener('click', () => {
         this.reload();
         this.stats.load();
      });
   }
}
