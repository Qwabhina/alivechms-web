/**
 * Expense service for API communication
 */

import http from './http'
import type {
  Expense,
  ExpenseDetail,
  ExpenseCategory,
  ExpenseCreateInput,
  ExpenseUpdateInput,
  ExpenseReviewInput,
  ExpenseListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const expenseService = {
  // Expenses
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: ExpenseListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Expense>>> {
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

    return http.get(`expense/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<ExpenseDetail>> {
    return http.get(`expense/view/${id}`).then((res) => res.data)
  },

  async create(data: ExpenseCreateInput): Promise<ApiResponse<Expense>> {
    return http.post('expense/create', data).then((res) => res.data)
  },

  async update(id: number, data: ExpenseUpdateInput): Promise<ApiResponse<Expense>> {
    return http.put(`expense/update/${id}`, { ...data, expense_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`expense/delete/${id}`).then((res) => res.data)
  },

  async review(data: ExpenseReviewInput): Promise<ApiResponse<Expense>> {
    return http.post('expense/review', data).then((res) => res.data)
  },

  async cancel(id: number): Promise<ApiResponse<Expense>> {
    return http.post(`expense/${id}/cancel`, {}).then((res) => res.data)
  },

  async uploadProof(id: number, file: File): Promise<ApiResponse<void>> {
    const formData = new FormData()
    formData.append('file', file)
    return http.post(`expense/${id}/upload`, formData).then((res) => res.data)
  },

  // Expense Categories
  async listCategories(): Promise<ApiResponse<ExpenseCategory[]>> {
    return http.get('expensecategory/all').then((res) => res.data)
  },

  async createCategory(data: { category_name: string; description?: string; budget_limit?: number }): Promise<ApiResponse<ExpenseCategory>> {
    return http.post('expensecategory/create', data).then((res) => res.data)
  },

  async updateCategory(id: number, data: { category_name?: string; description?: string; budget_limit?: number }): Promise<ApiResponse<ExpenseCategory>> {
    return http.put(`expensecategory/update/${id}`, { ...data, expense_category_id: id }).then((res) => res.data)
  },

  async deleteCategory(id: number): Promise<ApiResponse<void>> {
    return http.delete(`expensecategory/delete/${id}`).then((res) => res.data)
  },
}
