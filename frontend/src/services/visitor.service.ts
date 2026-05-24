/**
 * Visitor service for API communication
 */

import http from './http'
import type {
  Visitor,
  VisitorDetail,
  VisitorCreateInput,
  VisitorUpdateInput,
  VisitorListFilters,
  FollowUpCreateInput,
  FollowUpUpdateInput,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const visitorService = {
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: VisitorListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Visitor>>> {
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

    return http.get(`visitor/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<VisitorDetail>> {
    return http.get(`visitor/view/${id}`).then((res) => res.data)
  },

  async create(data: VisitorCreateInput): Promise<ApiResponse<Visitor>> {
    return http.post('visitor/create', data).then((res) => res.data)
  },

  async update(id: number, data: VisitorUpdateInput): Promise<ApiResponse<Visitor>> {
    return http.put(`visitor/update/${id}`, { ...data, visitor_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`visitor/delete/${id}`).then((res) => res.data)
  },

  async createFollowUp(data: FollowUpCreateInput): Promise<ApiResponse<void>> {
    return http.post('visitor/follow-up', data).then((res) => res.data)
  },

  async updateFollowUp(id: number, data: FollowUpUpdateInput): Promise<ApiResponse<void>> {
    return http.put(`visitor/follow-up/${id}`, { ...data, follow_up_id: id }).then((res) => res.data)
  },

  async convertToMember(visitorId: number): Promise<ApiResponse<void>> {
    return http.post(`visitor/${visitorId}/convert`, {}).then((res) => res.data)
  },

  async recent(): Promise<ApiResponse<Visitor[]>> {
    return http.get('visitor/recent').then((res) => res.data)
  },
}
