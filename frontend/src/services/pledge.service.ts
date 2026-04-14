/**
 * Pledge service for API communication
 */

import http from './http'
import type {
  Pledge,
  PledgeDetail,
  PledgeType,
  PledgeCreateInput,
  PledgeUpdateInput,
  PledgeListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const pledgeService = {
  // Pledges
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: PledgeListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Pledge>>> {
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

    return http.get(`pledge/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<PledgeDetail>> {
    return http.get(`pledge/view/${id}`).then((res) => res.data)
  },

  async create(data: PledgeCreateInput): Promise<ApiResponse<Pledge>> {
    return http.post('pledge/create', data).then((res) => res.data)
  },

  async update(id: number, data: PledgeUpdateInput): Promise<ApiResponse<Pledge>> {
    return http.put(`pledge/update/${id}`, { ...data, pledge_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`pledge/delete/${id}`).then((res) => res.data)
  },

  async recordPayment(pledgeId: number, contributionId: number, amount: number): Promise<ApiResponse<void>> {
    return http.post(`pledge/${pledgeId}/payment`, {
      contribution_id: contributionId,
      amount,
    }).then((res) => res.data)
  },

  async byMember(memberId: number): Promise<ApiResponse<Pledge[]>> {
    return http.get(`pledge/by-member/${memberId}`).then((res) => res.data)
  },

  // Pledge Types
  async listTypes(): Promise<ApiResponse<PledgeType[]>> {
    return http.get('lookups/pledge-types').then((res) => res.data)
  },
}
