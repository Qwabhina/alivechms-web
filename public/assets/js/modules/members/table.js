/**
 * Member Table Component
 */

export class MemberTable {
   constructor(state, api) {
      this.state = state;
      this.api = api;
      this.grid = null;
   }

   init() {
      this.grid = QMGridHelper.init('#membersTable', {
         url: '/member/all', // Absolute path from root
         pageSize: 25,
         selectable: false,
         multiSelect: false,
         exportable: true,
         
         columns: this.getColumns(),
         
         exportOptions: {
            filename: 'church-members',
            dateFormat: 'DD/MM/YYYY',
            includeHeaders: true
         },

         onDataLoaded: (data) => {
            console.log(`✓ Loaded ${data.data.length} of ${data.total} members`);
            this.updateCount(data.total);
         },

         onError: (error) => {
            console.error('✗ Failed to load members:', error);
            Alerts.error('Failed to load members');
         }
      });

      console.log('✓ Members table initialized');
   }

   getColumns() {
      return [
         {
            key: 'MbrProfilePicture',
            title: '',
            width: '55px',
            sortable: false,
            exportable: false,
            render: (value, row) => {
               const fullName = `${row.MbrFirstName || ''} ${row.MbrFamilyName || ''}`.trim();
               return QMGridHelper.formatProfilePicture(value, fullName, 42);
            }
         },
         {
            key: 'MbrFullName',
            title: 'Full Name',
            exportable: true,
            render: (value, row) => {
               const parts = [
                  row.MbrFirstName,
                  row.MbrOtherNames,
                  row.MbrFamilyName
               ].filter(Boolean);
               const fullName = parts.join(' ') || 'Unknown';
               return `<div class="fw-medium">${fullName}</div>`;
            }
         },
         {
            key: 'PhoneNumbers',
            title: 'Phone',
            sortable: false,
            exportable: true,
            render: (value, row) => {
               const phones = Array.isArray(value) ? value : 
                             (typeof value === 'string' && value ? value.split(',') : 
                             (row.PrimaryPhone ? [row.PrimaryPhone] : []));
               if (phones.length === 0) return '<span class="text-muted">-</span>';
               return `<span>${phones[0]}</span>`;
            }
         },
         {
            key: 'MbrEmailAddress',
            title: 'Email',
            exportable: true,
            render: (value) => {
               if (!value) return '<span class="text-muted">-</span>';
               return `<a href="mailto:${value}" class="text-decoration-none">${value}</a>`;
            }
         },
         {
            key: 'MbrGender',
            title: 'Gender',
            width: '80px',
            exportable: true,
            render: (value) => {
               if (!value) return '<span class="text-muted">-</span>';
               const icon = value === 'Male' ? 'gender-male' : 
                           value === 'Female' ? 'gender-female' : 'gender-ambiguous';
               return `<i class="bi bi-${icon} me-1"></i>${value}`;
            }
         },
         {
            key: 'MbrDateOfBirth',
            title: 'Age',
            width: '60px',
            exportable: true,
            render: (value) => {
               if (!value) return '<span class="text-muted">-</span>';
               try {
                  const birthDate = new Date(value);
                  const age = Math.floor((new Date() - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
                  return age > 0 ? `${age}` : '-';
               } catch (e) {
                  return '-';
               }
            }
         },
         {
            key: 'MbrResidentialAddress',
            title: 'Address',
            width: '100px',
            exportable: true,
            render: (value) => {
               if (!value) return '<span class="text-muted">-</span>';
               return `<span>${value}</span>`;
            }
         },
         {
            key: 'MembershipStatusName',
            title: 'Status',
            width: '85px',
            exportable: true,
            render: (value) => {
               if (!value) return '-';
               const color = value === 'Active' ? 'success' : 'secondary';
               return `<span class="badge bg-${color}">${value}</span>`;
            }
         },
         {
            key: 'MbrRegistrationDate',
            title: 'Joined',
            width: '100px',
            exportable: true,
            render: (value) => QMGridHelper.formatDate(value, 'short')
         },
         {
            key: 'MbrID',
            title: 'Actions',
            width: '130px',
            sortable: false,
            exportable: false,
            render: (value) => {
               const canView = Auth.hasPermission(Config.PERMISSIONS.VIEW_MEMBERS);
               const canEdit = Auth.hasPermission(Config.PERMISSIONS.EDIT_MEMBERS);
               const canDelete = Auth.hasPermission(Config.PERMISSIONS.DELETE_MEMBERS);
               
               let html = '<div class="btn-group btn-group-sm" role="group">';
               
               if (canView) {
                  html += `<button class="btn btn-primary btn-sm" onclick="viewMember(${value})" title="View">
                     <i class="bi bi-eye"></i>
                  </button>`;
               }
               
               if (canEdit) {
                  html += `<button class="btn btn-warning btn-sm" onclick="editMember(${value})" title="Edit">
                     <i class="bi bi-pencil"></i>
                  </button>`;
               }
               
               if (canDelete) {
                  html += `<button class="btn btn-danger btn-sm" onclick="deleteMember(${value})" title="Delete">
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
      const countElement = document.getElementById('total-members-count');
      if (countElement) {
         countElement.textContent = total.toLocaleString();
      }
   }
}
