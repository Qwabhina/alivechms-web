import { defineStore } from 'pinia'
import api from '@/services/api'

interface Pledge {
  PledgeID: number
  MbrID: number
  MbrFirstName: string
  MbrFamilyName: string
  PledgeAmount: number
  PledgeDate: string
  DueDate?: string
  Status: string
  PledgeTypeName: string
  FiscalYearName?: string
  Description?: string
  total_paid: number
  balance: number
}

interface PledgePayment {
  PaymentID: number
  PledgeID: number
  PaymentAmount: number
  PaymentDate: string
  RecordedBy: number
  RecordedAt: string
}

interface PledgeStats {
  total_amount: number
  total_count: number
  active_amount: number
  fulfilled_amount: number
  payments_total: number
  outstanding_amount: number
}

interface PledgesState {
  pledges: Pledge[]
  stats: PledgeStats | null
  loading: boolean
  pagination: {
    page: number
    limit: number
    total: number
    pages: number
  }
}

export const usePledgesStore = defineStore('pledges', {
  state: (): PledgesState => ({
    pledges: [],
    stats: null,
    loading: false,
    pagination: {
      page: 1,
      limit: 10,
      total: 0,
      pages: 0
    }
  }),

  actions: {
    async fetchPledges(params: any = {}) {
      this.loading = true
      try {
        const response = await api.get('pledge/all', { params })
        this.pledges = response.data.data
        this.pagination = response.data.pagination
      } catch (error) {
        console.error('Failed to fetch pledges:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchStats(fiscalYearId?: number) {
      try {
        const response = await api.get('pledge/stats', { 
          params: { fiscal_year_id: fiscalYearId } 
        })
        this.stats = response.data.data
      } catch (error) {
        console.error('Failed to fetch pledge stats:', error)
      }
    },

    async fetchPledgeById(id: number) {
      try {
        const response = await api.get(`pledge/view/${id}`)
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch pledge details:', error)
        throw error
      }
    },

    async createPledge(data: any) {
      try {
        const response = await api.post('pledge/create', data)
        await this.fetchPledges()
        return response.data
      } catch (error) {
        console.error('Failed to create pledge:', error)
        throw error
      }
    },

    async updatePledge(id: number, data: any) {
      try {
        const response = await api.put(`pledge/update/${id}`, data)
        await this.fetchPledges()
        return response.data
      } catch (error) {
        console.error('Failed to update pledge:', error)
        throw error
      }
    },

    async deletePledge(id: number) {
      try {
        const response = await api.delete(`pledge/delete/${id}`)
        await this.fetchPledges()
        return response.data
      } catch (error) {
        console.error('Failed to delete pledge:', error)
        throw error
      }
    },

    async recordPayment(pledgeId: number, data: any) {
      try {
        const response = await api.post(`pledge/record-payment/${pledgeId}`, data)
        await this.fetchPledges()
        return response.data
      } catch (error) {
        console.error('Failed to record pledge payment:', error)
        throw error
      }
    }
  }
})
