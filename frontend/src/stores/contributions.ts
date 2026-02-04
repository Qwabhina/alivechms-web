import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export interface Contribution {
   ContributionID: number
   MbrID: number
   MbrFirstName: string
   MbrFamilyName: string
   ContributionAmount: number
   ContributionDate: string
   ContributionTypeID: number
   ContributionTypeName: string
   PaymentOptionID: number
   PaymentOptionName: string
   FiscalYearID: number
   FiscalYearName: string
   Notes?: string
   Deleted?: number
}

export interface ContributionType {
   ContributionTypeID: number
   ContributionTypeName: string
   ContributionTypeDescription?: string
}

export interface PaymentOption {
   PaymentOptionID: number
   PaymentOptionName: string
}

export interface FiscalYear {
   FiscalYearID: number
   FiscalYearName: string
   Status: string
}

export interface ContributionStats {
   total_amount: number
   total_count: number
   month_total: number
   month_growth: number
   week_total: number
   week_count: number
   today_total: number
   today_count: number
   average_amount: number
   average_per_contributor: number
   unique_contributors: number
   last_month_total: number
   fiscal_year?: { name: string; status: string }
   top_contributors: TopContributor[]
   by_type: ByTypeData[]
   monthly_trend: MonthlyTrendData[]
}

export interface TopContributor {
   MbrID: number
   MbrFirstName: string
   MbrFamilyName: string
   total: number
   contribution_count: number
}

export interface ByTypeData {
   ContributionTypeName: string
   total: number
}

export interface MonthlyTrendData {
   month_label: string
   total: number
}

export interface Pagination {
   page: number
   limit: number
   total: number
   pages: number
}

export const useContributionsStore = defineStore('contributions', () => {
   // State
   const contributions = ref<Contribution[]>([])
   const stats = ref<ContributionStats | null>(null)
   const pagination = ref<Pagination>({ page: 1, limit: 25, total: 0, pages: 0 })
   const loading = ref(false)
   const statsLoading = ref(false)

   // Lookup data
   const members = ref<any[]>([])
   const contributionTypes = ref<ContributionType[]>([])
   const paymentOptions = ref<PaymentOption[]>([])
   const fiscalYears = ref<FiscalYear[]>([])
   const selectedFiscalYearId = ref<number | null>(null)

   // Filters
   const filters = ref({
      contribution_type_id: '',
      start_date: '',
      end_date: '',
      include_deleted: false
   })

   // Currency symbol from settings
   const currencySymbol = ref('GH₵')

   // Computed
   const activeFiscalYear = computed(() =>
      fiscalYears.value.find(fy => fy.Status === 'Active')
   )

   // Actions
   async function fetchContributions(page = 1) {
      loading.value = true
      try {
         const params = new URLSearchParams()
         params.append('page', String(page))
         params.append('limit', String(pagination.value.limit))

         if (selectedFiscalYearId.value) {
            params.append('fiscal_year_id', String(selectedFiscalYearId.value))
         }
         if (filters.value.contribution_type_id && filters.value.contribution_type_id !== 'all') {
            params.append('contribution_type_id', filters.value.contribution_type_id)
         }
         if (filters.value.start_date) {
            params.append('start_date', filters.value.start_date)
         }
         if (filters.value.end_date) {
            params.append('end_date', filters.value.end_date)
         }
         if (filters.value.include_deleted) {
            params.append('include_deleted', '1')
         }

         const response = await api.get(`contribution/all?${params.toString()}`)
         contributions.value = response.data.data || []
         if (response.data.pagination) {
            pagination.value = response.data.pagination
         } else {
            pagination.value = {
               page,
               limit: pagination.value.limit,
               total: contributions.value.length,
               pages: 1
            }
         }
      } catch (error) {
         console.error('Fetch contributions error:', error)
         throw error
      } finally {
         loading.value = false
      }
   }

   async function fetchStats() {
      statsLoading.value = true
      try {
         let url = 'contribution/stats'
         if (selectedFiscalYearId.value) {
            url += `?fiscal_year_id=${selectedFiscalYearId.value}`
         }
         const response = await api.get(url)
         stats.value = response.data.data || response.data
      } catch (error) {
         console.error('Fetch stats error:', error)
         stats.value = null
      } finally {
         statsLoading.value = false
      }
   }

   async function fetchDropdowns() {
      try {
         const [membersRes, typesRes, paymentRes, fiscalRes] = await Promise.all([
            api.get('member/all?limit=1000'),
            api.get('contribution/types'),
            api.get('contribution/payment-options'),
            api.get('fiscalyear/all?limit=50')
         ])

         members.value = membersRes.data.data || []
         contributionTypes.value = typesRes.data.data || []
         paymentOptions.value = paymentRes.data.data || []
         fiscalYears.value = fiscalRes.data.data || []

         // Set selected fiscal year to active one
         const active = fiscalYears.value.find(fy => fy.Status === 'Active')
         if (active && !selectedFiscalYearId.value) {
            selectedFiscalYearId.value = active.FiscalYearID
         }
      } catch (error) {
         console.error('Fetch dropdowns error:', error)
      }
   }

   async function getContribution(id: number): Promise<Contribution> {
      const response = await api.get(`contribution/view/${id}`)
      return response.data.data
   }

   async function createContribution(data: {
      member_id: number
      amount: number
      date: string
      contribution_type_id: number
      payment_option_id: number
      fiscal_year_id: number
      description?: string
   }) {
      await api.post('contribution/create', data)
      await fetchContributions(pagination.value.page)
      await fetchStats()
   }

   async function updateContribution(id: number, data: {
      member_id: number
      amount: number
      date: string
      contribution_type_id: number
      payment_option_id: number
      fiscal_year_id: number
      description?: string
   }) {
      await api.put(`contribution/update/${id}`, data)
      await fetchContributions(pagination.value.page)
      await fetchStats()
   }

   async function deleteContribution(id: number) {
      await api.delete(`contribution/delete/${id}`)
      await fetchContributions(pagination.value.page)
      await fetchStats()
   }

   async function restoreContribution(id: number) {
      await api.post(`contribution/restore/${id}`)
      await fetchContributions(pagination.value.page)
      await fetchStats()
   }

   async function getReceipt(id: number) {
      const response = await api.get(`contribution/receipt/${id}`)
      return response.data.data
   }

   async function getMemberStatement(memberId: number) {
      let url = `contribution/statement/${memberId}`
      if (selectedFiscalYearId.value) {
         url += `?fiscal_year_id=${selectedFiscalYearId.value}`
      }
      const response = await api.get(url)
      return response.data.data
   }

   // Contribution Types CRUD
   async function fetchContributionTypes() {
      const res = await api.get('contribution/types')
      contributionTypes.value = res.data.data || []
   }

   async function createContributionType(name: string, description?: string) {
      await api.post('contribution/type/create', { name, description })
      await fetchContributionTypes()
   }

   async function updateContributionType(id: number, name: string, description?: string) {
      await api.put(`contribution/type/update/${id}`, { name, description })
      await fetchContributionTypes()
   }

   async function deleteContributionType(id: number) {
      await api.delete(`contribution/type/delete/${id}`)
      await fetchContributionTypes()
   }

   function applyFilters() {
      fetchContributions(1)
   }

   function clearFilters() {
      filters.value = {
         contribution_type_id: '',
         start_date: '',
         end_date: '',
         include_deleted: false
      }
      fetchContributions(1)
   }

   function setFiscalYear(id: number | null) {
      selectedFiscalYearId.value = id
      fetchContributions(1)
      fetchStats()
   }

   function setCurrencySymbol(symbol: string) {
      currencySymbol.value = symbol
   }

   return {
      // State
      contributions,
      stats,
      pagination,
      loading,
      statsLoading,
      members,
      contributionTypes,
      paymentOptions,
      fiscalYears,
      selectedFiscalYearId,
      filters,
      currencySymbol,
      // Computed
      activeFiscalYear,
      // Actions
      fetchContributions,
      fetchStats,
      fetchDropdowns,
      getContribution,
      createContribution,
      updateContribution,
      deleteContribution,
      restoreContribution,
      getReceipt,
      getMemberStatement,
      fetchContributionTypes,
      createContributionType,
      updateContributionType,
      deleteContributionType,
      applyFilters,
      clearFilters,
      setFiscalYear,
      setCurrencySymbol
   }
})
