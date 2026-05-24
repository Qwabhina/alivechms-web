/**
 * Budget service for API communication
 */

import http from './http'
import type {
  Budget,
  BudgetDetail,
  BudgetCreateInput,
  BudgetUpdateInput,
  BudgetListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const budgetService = {
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: BudgetListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Budget>>> {
    const params = new URLSearchParams()
    params.append('page', page.toString())
    params.append('limit', limit.toString())

    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          params.append(key, String(value))
        }
      })
    }

    return http.get(`budget/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<BudgetDetail>> {
    return http.get(`budget/view/${id}`).then((res) => res.data)
  },

  async create(data: BudgetCreateInput): Promise<ApiResponse<Budget>> {
    return http.post('budget/create', data).then((res) => res.data)
  },

  async update(id: number, data: BudgetUpdateInput): Promise<ApiResponse<Budget>> {
    return http.put(`budget/update/${id}`, { ...data, budget_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`budget/delete/${id}`).then((res) => res.data)
  },

  async setAllocations(budgetId: number, allocations: Array<{ expense_category_id: number; allocated_amount: number }>): Promise<ApiResponse<void>> {
    return http.post(`budget/${budgetId}/allocations`, { allocations }).then((res) => res.data)
  },

  async byFiscalYear(fiscalYearId: number): Promise<ApiResponse<Budget[]>> {
    return http.get(`budget/by-fiscal-year/${fiscalYearId}`).then((res) => res.data)
  },

  async summary(): Promise<ApiResponse<{
    totalBudgeted: number
    totalSpent: number
    remainingBudget: number
    percentageUsed: number
  }>> {
    return http.get('budget/summary').then((res) => res.data)
  },
}
