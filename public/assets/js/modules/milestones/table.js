/**
 * Milestones Table Component
 */

export class MilestoneTable {
   constructor(state, api, stats = null) {
      this.state = state;
      this.api = api;
      this.stats = stats;
      this.grid = null;
   }

   init() {
      this.initQMGrid();
      this.initFilters();
      console.log('✓ Milestone table initialized');
   }

   initQMGrid() {
      const year = this.state.getCurrentYear();
      
      this.grid = new QMGrid("#milestonesTable", {
        dataSource: {
          url: `${Config.API_BASE_URL}/milestone/all?year=${year}`,
          method: "GET",
          headers: () => ({
            Authorization: `Bearer ${Auth.getToken()}`,
          }),
          dataPath: "data",
          totalPath: "pagination.total",
        },
        columns: [
          {
            field: "member_name",
            title: "Member",
            sortable: true,
            render: (value, row) => `
                  <div class="d-flex align-items-center">
                     <div>
                        <div class="fw-semibold">${row.member_name || "-"}</div>
                        <small class="text-muted">${row.type_name || "-"}</small>
                     </div>
                  </div>
               `,
          },
          {
            field: "date",
            title: "Date",
            sortable: true,
            render: (value) =>
              value
                ? new Date(value).toLocaleDateString("en-US", {
                    year: "numeric",
                    month: "short",
                    day: "numeric",
                  })
                : "-",
          },
          {
            field: "location",
            title: "Location",
            render: (value) => value || "-",
          },
          {
            field: "officiating_pastor",
            title: "Officiating Pastor",
            render: (value) => value || "-",
          },
          {
            field: "certificate_number",
            title: "Certificate #",
            render: (value) => value || "-",
          },
          {
            field: "actions",
            title: "Actions",
            sortable: false,
            render: (value, row) => `
                  <div class="btn-group btn-group-sm">
                     <button class="btn btn-outline-primary" onclick="viewMilestone(${row.id})" title="View">
                        <i class="bi bi-eye"></i>
                     </button>
                     <button class="btn btn-outline-success" onclick="editMilestone(${row.id})" title="Edit" data-permission="manage_milestones">
                        <i class="bi bi-pencil"></i>
                     </button>
                     <button class="btn btn-outline-danger" onclick="deleteMilestone(${row.id})" title="Delete" data-permission="manage_milestones">
                        <i class="bi bi-trash"></i>
                     </button>
                  </div>
               `,
          },
        ],
        pagination: {
          enabled: true,
          pageSize: 25,
          pageSizes: [10, 25, 50, 100],
        },
        search: {
          enabled: true,
          placeholder: "Search milestones...",
        },
        sorting: {
          enabled: true,
          defaultSort: { field: "date", direction: "desc" },
        },
        responsive: true,
        emptyMessage: "No milestones found",
      });
   }

   initFilters() {
      // Type filter
      const typeFilter = document.getElementById('filterType');
      if (typeFilter) {
         typeFilter.addEventListener('change', () => {
            this.applyFilters();
         });
      }

      // Member filter
      const memberFilter = document.getElementById('filterMember');
      if (memberFilter) {
         memberFilter.addEventListener('change', () => {
            this.applyFilters();
         });
      }

      // Date filters
      const startDateFilter = document.getElementById('filterStartDate');
      const endDateFilter = document.getElementById('filterEndDate');
      
      if (startDateFilter) {
         startDateFilter.addEventListener('change', () => {
            this.applyFilters();
         });
      }

      if (endDateFilter) {
         endDateFilter.addEventListener('change', () => {
            this.applyFilters();
         });
      }

      // Clear filters button
      const clearBtn = document.getElementById('clearFiltersBtn');
      if (clearBtn) {
         clearBtn.addEventListener('click', () => {
            this.clearFilters();
         });
      }

      // Apply filters button
      const applyBtn = document.getElementById('applyFiltersBtn');
      if (applyBtn) {
         applyBtn.addEventListener('click', () => {
            this.applyFilters();
         });
      }

      // Search input (apply on Enter)
      const searchInput = document.getElementById('filterSearch');
      if (searchInput) {
         searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
               this.applyFilters();
            }
         });
      }

      // Refresh grid button
      const refreshBtn = document.getElementById('refreshGrid');
      if (refreshBtn) {
         refreshBtn.addEventListener('click', () => this.refresh());
      }
   }

   applyFilters() {
      const filters = {
         type: document.getElementById('filterType')?.value || '',
         member: document.getElementById('filterMember')?.value || '',
         startDate: document.getElementById('filterStartDate')?.value || '',
         endDate: document.getElementById('filterEndDate')?.value || '',
         search: document.getElementById('filterSearch')?.value || ''
      };

      this.state.setFilters(filters);

      // Build query params
      const params = new URLSearchParams();
      params.append('year', this.state.getCurrentYear());
      
      if (filters.type) params.append('milestone_type_id', filters.type);
      if (filters.member) params.append('member_id', filters.member);
      if (filters.startDate) params.append('start_date', filters.startDate);
      if (filters.endDate) params.append('end_date', filters.endDate);
      if (filters.search) params.append('search', filters.search);

      // Update grid URL
      if (this.grid) {
         this.grid.updateDataSource({
            url: `${Config.API_BASE_URL}/milestone/all?${params.toString()}`
         });
         this.grid.refresh();
      }
   }

   clearFilters() {
      this.state.clearFilters();
      
      // Clear UI
      const typeFilter = document.getElementById('filterType');
      const memberFilter = document.getElementById('filterMember');
      const startDateFilter = document.getElementById('filterStartDate');
      const endDateFilter = document.getElementById('filterEndDate');

      if (typeFilter) typeFilter.value = '';
      if (memberFilter) memberFilter.value = '';
      if (startDateFilter) startDateFilter.value = '';
      if (endDateFilter) endDateFilter.value = '';

      // Reload with no filters
      this.applyFilters();
   }

   async refresh() {
      if (this.grid) {
         await this.grid.refresh();
      }
   }

   async loadFilterOptions() {
      // Load milestone types for filter
      try {
         const typesResponse = await this.api.getAllTypes(true);
         const types = typesResponse?.data || typesResponse;
         
         const typeFilter = document.getElementById('filterType');
         if (typeFilter && types.length > 0) {
            typeFilter.innerHTML = '<option value="">All Types</option>' +
               types.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
         }
      } catch (error) {
         console.error('Failed to load filter options:', error);
      }

      // Load members for filter
      try {
         const membersResponse = await api.get('member/all?limit=1000');
         const members = Array.isArray(membersResponse) ? membersResponse : (membersResponse?.data || []);
         
         const memberFilter = document.getElementById('filterMember');
         if (memberFilter && members.length > 0) {
            memberFilter.innerHTML = '<option value="">All Members</option>' +
               members.map(m => `<option value="${m.MbrID}">${m.MbrFirstName} ${m.MbrFamilyName}</option>`).join('');
         }
      } catch (error) {
         console.error('Failed to load members:', error);
      }
   }
}
