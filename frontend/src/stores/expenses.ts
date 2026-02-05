import { defineStore } from 'pinia'
import api from '@/services/api'

interface Expense {
  ExpenseID: number
  ExpenseTitle: string
  ExpenseAmount: number
  ExpenseDate: string
  ExpenseStatus: string
  CategoryName: string
  BranchName: string
  ExpensePurpose?: string
  FiscalYearName?: string
  ProofFile?: string
  RequesterName?: string
  ApproverName?: string
  ApprovalRemarks?: string
}

interface ExpenseStats {
  total_amount: number
  total_count: number
  approved_total: number
  approved_count: number
  pending_total: number
  pending_count: number
  rejected_total: number
  rejected_count: number
  month_total: number
  month_growth: number
  by_category: any[]
  by_status: any[]
  monthly_trend: any[]
}

interface ExpensesState {
  expenses: Expense[]
  stats: ExpenseStats | null
  loading: boolean
  pagination: {
    page: number
    limit: number
    total: number
    pages: number
  }
}

export const useExpensesStore = defineStore('expenses', {
  state: (): ExpensesState => ({
    expenses: [],
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
    async fetchExpenses(params: any = {}) {
      this.loading = true
      try {
        const response = await api.get('expense/all', { params })
        this.expenses = response.data.data
        this.pagination = response.data.pagination
      } catch (error) {
        console.error('Failed to fetch expenses:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchStats(fiscalYearId?: number) {
      try {
        const response = await api.get('expense/stats', { 
          params: { fiscal_year_id: fiscalYearId } 
        })
        this.stats = response.data.data
      } catch (error) {
        console.error('Failed to fetch expense stats:', error)
      }
    },

    async fetchExpenseById(id: number) {
      try {
        const response = await api.get(`expense/view/${id}`)
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch expense details:', error)
        throw error
      }
    },

    async createExpense(data: any) {
      try {
        const response = await api.post('expense/create', data)
        await this.fetchExpenses()
        return response.data
      } catch (error) {
        console.error('Failed to create expense:', error)
        throw error
      }
    },

    async updateExpense(id: number, data: any) {
      try {
        const response = await api.put(`expense/update/${id}`, data)
        await this.fetchExpenses()
        return response.data
      } catch (error) {
        console.error('Failed to update expense:', error)
        throw error
      }
    },

    async deleteExpense(id: number) {
      try {
        const response = await api.delete(`expense/delete/${id}`)
        await this.fetchExpenses()
        return response.data
      } catch (error) {
        console.error('Failed to delete expense:', error)
        throw error
      }
    },

    async reviewExpense(id: number, action: 'approve' | 'reject', remarks?: string) {
      try {
        const response = await api.post(`expense/review/${id}`, { action, remarks })
        await this.fetchExpenses()
        return response.data
      } catch (error) {
        console.error('Failed to review expense:', error)
        throw error
      }
    },

    async cancelExpense(id: number, reason: string) {
      try {
        const response = await api.post(`expense/cancel/${id}`, { reason })
        await this.fetchExpenses()
        return response.data
      } catch (error) {
        console.error('Failed to cancel expense:', error)
        throw error
      }
    },

    async uploadProof(id: number, file: File) {
      try {
        const formData = new FormData()
        formData.append('proof', file)
        const response = await api.post(`expense/upload-proof/${id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        return response.data
      } catch (error) {
        console.error('Failed to upload proof:', error)
        throw error
      }
    }
  }
})
