/**
 * Fiscal Year service for API communication
 */

import http from './http'
import type {
  FiscalYear,
  FiscalYearCreateInput,
  FiscalYearUpdateInput,
  FiscalYearListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const fiscalYearService = {
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: FiscalYearListFilters,
  ): Promise<ApiResponse<PaginatedResponse<FiscalYear>>> {
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

    return http.get(`fiscalyear/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<FiscalYear>> {
    return http.get(`fiscalyear/view/${id}`).then((res) => res.data)
  },

  async create(data: FiscalYearCreateInput): Promise<ApiResponse<FiscalYear>> {
    return http.post('fiscalyear/create', data).then((res) => res.data)
  },

  async update(id: number, data: FiscalYearUpdateInput): Promise<ApiResponse<FiscalYear>> {
    return http.put(`fiscalyear/update/${id}`, { ...data, fiscal_year_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`fiscalyear/delete/${id}`).then((res) => res.data)
  },

  async setActive(id: number): Promise<ApiResponse<FiscalYear>> {
    return http.post(`fiscalyear/${id}/activate`, {}).then((res) => res.data)
  },

  async close(id: number): Promise<ApiResponse<FiscalYear>> {
    return http.post(`fiscalyear/${id}/close`, {}).then((res) => res.data)
  },

  async current(): Promise<ApiResponse<FiscalYear>> {
    return http.get('fiscalyear/current').then((res) => res.data)
  },

  async allActive(): Promise<ApiResponse<FiscalYear[]>> {
    return http.get('fiscalyear/active').then((res) => res.data)
  },
}
