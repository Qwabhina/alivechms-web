import { defineStore } from 'pinia'
import api from '@/services/api'

interface BudgetItem {
  ItemID: number
  BudgetID: number
  ItemName: string
  Amount: number
  CategoryType: 'Income' | 'Expense'
  SubcategoryID: number
}

interface Budget {
  BudgetID: number
  BudgetTitle: string
  BudgetSummary?: string
  TotalAmount: number
  BudgetStatus: 'Draft' | 'Submitted' | 'Approved' | 'Rejected'
  FiscalYearID: number
  FiscalYearName: string
  BranchID: number
  BranchName: string
  CreatedAt: string
  items?: BudgetItem[]
}

interface BudgetsState {
  budgets: Budget[]
  loading: boolean
  pagination: {
    page: number
    limit: number
    total: number
    pages: number
  }
}


export const useBudgetsStore = defineStore('budgets', {
  state: (): BudgetsState => ({
    budgets: [],
    loading: false,
    pagination: {
      page: 1,
      limit: 10,
      total: 0,
      pages: 0
    }
  }),

  actions: {
    async fetchBudgets(params: any = {}) {
      this.loading = true
      try {
        const response = await api.get('budget/all', { params })
        this.budgets = response.data.data
        this.pagination = response.data.pagination
      } catch (error) {
        console.error('Failed to fetch budgets:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchBudgetById(id: number) {
      try {
        const response = await api.get(`budget/view/${id}`)
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch budget details:', error)
        throw error
      }
    },

    async createBudget(data: any) {
      try {
        const response = await api.post('budget/create', data)
        await this.fetchBudgets()
        return response.data
      } catch (error) {
        console.error('Failed to create budget:', error)
        throw error
      }
    },

    async updateBudget(id: number, data: any) {
      try {
        const response = await api.put(`budget/update/${id}`, data)
        await this.fetchBudgets()
        return response.data
      } catch (error) {
        console.error('Failed to update budget:', error)
        throw error
      }
    },

    async submitBudget(id: number) {
      try {
        const response = await api.put(`budget/submit/${id}`)
        await this.fetchBudgets()
        return response.data
      } catch (error) {
        console.error('Failed to submit budget:', error)
        throw error
      }
    },

    async reviewBudget(id: number, action: 'approve' | 'reject', remarks?: string) {
      try {
        const response = await api.post(`budget/review/${id}`, { action, remarks })
        await this.fetchBudgets()
        return response.data
      } catch (error) {
        console.error('Failed to review budget:', error)
        throw error
      }
    },

    async addItem(budgetId: number, item: any) {
      try {
        const response = await api.post(`budget/item/add/${budgetId}`, item)
        return response.data
      } catch (error) {
        console.error('Failed to add budget item:', error)
        throw error
      }
    },

    async updateItem(budgetId: number, itemId: number, item: any) {
      try {
        const response = await api.put(`budget/item/update/${budgetId}/${itemId}`, item)
        return response.data
      } catch (error) {
        console.error('Failed to update budget item:', error)
        throw error
      }
    },

    async deleteItem(budgetId: number, itemId: number) {
      try {
        const response = await api.delete(`budget/item/delete/${budgetId}/${itemId}`)
        return response.data
      } catch (error) {
        console.error('Failed to delete budget item:', error)
        throw error
      }
    }
  }
})
