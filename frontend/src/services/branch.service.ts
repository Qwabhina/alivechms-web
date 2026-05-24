/**
 * Branch service for API communication
 */

import http from './http'
import type {
  Branch,
  BranchDetail,
  BranchCreateInput,
  BranchUpdateInput,
  BranchListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const branchService = {
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: BranchListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Branch>>> {
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

    return http.get(`branch/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<BranchDetail>> {
    return http.get(`branch/view/${id}`).then((res) => res.data)
  },

  async create(data: BranchCreateInput): Promise<ApiResponse<Branch>> {
    return http.post('branch/create', data).then((res) => res.data)
  },

  async update(id: number, data: BranchUpdateInput): Promise<ApiResponse<Branch>> {
    return http.put(`branch/update/${id}`, { ...data, branch_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`branch/delete/${id}`).then((res) => res.data)
  },

  async setPastor(branchId: number, pastorId: number): Promise<ApiResponse<void>> {
    return http.post(`branch/${branchId}/pastor`, { pastor_id: pastorId }).then((res) => res.data)
  },

  async allActive(): Promise<ApiResponse<Branch[]>> {
    return http.get('branch/active').then((res) => res.data)
  },

  async search(query: string): Promise<ApiResponse<Branch[]>> {
    return http.get(`branch/search?q=${encodeURIComponent(query)}`).then((res) => res.data)
  },
}
