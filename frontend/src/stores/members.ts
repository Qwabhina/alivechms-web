import { defineStore } from 'pinia'
import api from '@/services/api'
import { Alerts } from '@/utils/alerts'

interface MemberStats {
   gender_distribution: any[]
   age_distribution: any[]
   total_members: number
   active_members: number
   inactive_members: number
   new_this_month: number
}

interface MembersState {
   members: any[]
   stats: MemberStats | null
   lookupData: any | null
   roles: any[]
   loading: boolean
   pagination: {
      total: number
      page: number
      limit: number
   }
   filters: {
      status: string
      family_id: string
      branch_id: string
      search: string
   }
}

export const useMembersStore = defineStore('members', {
   state: (): MembersState => ({
      members: [],
      stats: null,
      lookupData: null,
      roles: [],
      loading: false,
      pagination: {
         total: 0,
         page: 1,
         limit: 25,
      },
      filters: {
         status: '',
         family_id: '',
         branch_id: '',
         search: '',
      },
   }),

   actions: {
      async fetchMembers(page = 1) {
         this.loading = true
         this.pagination.page = page
         try {
            const response = await api.get('member/all', {
               params: {
                  page,
                  limit: this.pagination.limit,
                  ...this.filters,
               },
            })
            this.members = response.data.data
            this.pagination.total = response.data.pagination.total
         } catch (error) {
            Alerts.handleApiError(error, 'Failed to fetch members')
         } finally {
            this.loading = false
         }
      },

      async fetchStats() {
         try {
            const response = await api.get('member/stats')
            this.stats = response.data.data
         } catch (error) {
            console.error('Failed to fetch member stats', error)
         }
      },

      async fetchLookupData() {
         if (this.lookupData) return
         try {
            const [lookupRes, rolesRes] = await Promise.all([
               api.get('lookups/all'),
               api.get('role/names')
            ])
            this.lookupData = lookupRes.data.data
            this.roles = rolesRes.data.data
         } catch (error) {
            console.error('Failed to fetch lookup data', error)
         }
      },

      setFilters(filters: Partial<MembersState['filters']>) {
         this.filters = { ...this.filters, ...filters }
         this.fetchMembers(1)
      },

      async deleteMember(id: number | string) {
         const confirmed = await Alerts.confirm({
            title: 'Are you sure?',
            text: 'This will soft-delete the member record.',
            icon: 'warning',
            confirmButtonText: 'Yes, delete it',
         })

         if (confirmed) {
            try {
               await api.delete(`member/delete/${id}`)
               Alerts.success('Member deleted successfully')
               this.fetchMembers(this.pagination.page)
               this.fetchStats()
            } catch (error) {
               Alerts.handleApiError(error, 'Failed to delete member')
            }
         }
      },
   },
})
