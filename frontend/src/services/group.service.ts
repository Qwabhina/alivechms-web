/**
 * Group service for API communication
 */

import http from './http'
import type {
  Group,
  GroupDetail,
  GroupType,
  GroupCreateInput,
  GroupUpdateInput,
  GroupListFilters,
  GroupTypeCreateInput,
  GroupTypeUpdateInput,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const groupService = {
  // Groups
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: GroupListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Group>>> {
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

    return http.get(`group/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<GroupDetail>> {
    return http.get(`group/view/${id}`).then((res) => res.data)
  },

  async create(data: GroupCreateInput): Promise<ApiResponse<Group>> {
    return http.post('group/create', data).then((res) => res.data)
  },

  async update(id: number, data: GroupUpdateInput): Promise<ApiResponse<Group>> {
    return http.put(`group/update/${id}`, { ...data, group_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`group/delete/${id}`).then((res) => res.data)
  },

  async addMember(groupId: number, memberId: number, role?: string): Promise<ApiResponse<void>> {
    return http.post(`group/${groupId}/members`, {
      member_id: memberId,
      role,
    }).then((res) => res.data)
  },

  async removeMember(groupId: number, memberId: number): Promise<ApiResponse<void>> {
    return http.delete(`group/${groupId}/members/${memberId}`).then((res) => res.data)
  },

  // Group Types
  async listTypes(): Promise<ApiResponse<GroupType[]>> {
    return http.get('grouptype/all').then((res) => res.data)
  },

  async createType(data: GroupTypeCreateInput): Promise<ApiResponse<GroupType>> {
    return http.post('grouptype/create', data).then((res) => res.data)
  },

  async updateType(id: number, data: GroupTypeUpdateInput): Promise<ApiResponse<GroupType>> {
    return http.put(`grouptype/update/${id}`, { ...data, group_type_id: id }).then((res) => res.data)
  },

  async deleteType(id: number): Promise<ApiResponse<void>> {
    return http.delete(`grouptype/delete/${id}`).then((res) => res.data)
  },
}
