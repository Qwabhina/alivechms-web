/**
 * Volunteer service for API communication
 */

import http from './http'
import type {
  Volunteer,
  VolunteerDetail,
  VolunteerCreateInput,
  VolunteerUpdateInput,
  VolunteerListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const volunteerService = {
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: VolunteerListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Volunteer>>> {
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

    return http.get(`volunteer/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<VolunteerDetail>> {
    return http.get(`volunteer/view/${id}`).then((res) => res.data)
  },

  async create(data: VolunteerCreateInput): Promise<ApiResponse<Volunteer>> {
    return http.post('volunteer/create', data).then((res) => res.data)
  },

  async update(id: number, data: VolunteerUpdateInput): Promise<ApiResponse<Volunteer>> {
    return http.put(`volunteer/update/${id}`, { ...data, volunteer_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`volunteer/delete/${id}`).then((res) => res.data)
  },

  async assignToEvent(volunteerId: number, eventId: number, role: string): Promise<ApiResponse<void>> {
    return http.post(`volunteer/${volunteerId}/assign-event`, {
      event_id: eventId,
      role,
    }).then((res) => res.data)
  },

  async assignToGroup(volunteerId: number, groupId: number, role: string): Promise<ApiResponse<void>> {
    return http.post(`volunteer/${volunteerId}/assign-group`, {
      group_id: groupId,
      role,
    }).then((res) => res.data)
  },

  async removeAssignment(volunteerId: number, assignmentId: number): Promise<ApiResponse<void>> {
    return http.delete(`volunteer/${volunteerId}/assignments/${assignmentId}`).then((res) => res.data)
  },

  async active(): Promise<ApiResponse<Volunteer[]>> {
    return http.get('volunteer/active').then((res) => res.data)
  },
}
