/**
 * Family service for API communication
 */

import http from './http'
import type {
  Family,
  FamilyDetail,
  FamilyCreateInput,
  FamilyUpdateInput,
  FamilyListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const familyService = {
  /**
   * Get paginated list of families
   */
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: FamilyListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Family>>> {
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

    return http.get(`family/all?${params.toString()}`).then((res) => res.data)
  },

  /**
   * Get single family by ID
   */
  async get(id: number): Promise<ApiResponse<FamilyDetail>> {
    return http.get(`family/view/${id}`).then((res) => res.data)
  },

  /**
   * Create new family
   */
  async create(data: FamilyCreateInput): Promise<ApiResponse<Family>> {
    return http.post('family/create', data).then((res) => res.data)
  },

  /**
   * Update family
   */
  async update(id: number, data: FamilyUpdateInput): Promise<ApiResponse<Family>> {
    return http.put(`family/update/${id}`, { ...data, family_id: id }).then((res) => res.data)
  },

  /**
   * Delete family
   */
  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`family/delete/${id}`).then((res) => res.data)
  },

  /**
   * Add member to family
   */
  async addMember(familyId: number, memberId: number, relationship: string): Promise<ApiResponse<void>> {
    return http.post(`family/${familyId}/members`, {
      member_id: memberId,
      relationship,
    }).then((res) => res.data)
  },

  /**
   * Remove member from family
   */
  async removeMember(familyId: number, memberId: number): Promise<ApiResponse<void>> {
    return http.delete(`family/${familyId}/members/${memberId}`).then((res) => res.data)
  },

  /**
   * Set family head
   */
  async setFamilyHead(familyId: number, memberId: number): Promise<ApiResponse<void>> {
    return http.put(`family/${familyId}/head`, {
      family_head_id: memberId,
    }).then((res) => res.data)
  },

  /**
   * Search families
   */
  async search(query: string): Promise<ApiResponse<Family[]>> {
    return http.get(`family/search?q=${encodeURIComponent(query)}`).then((res) => res.data)
  },
}
