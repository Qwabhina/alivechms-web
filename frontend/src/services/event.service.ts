/**
 * Event service for API communication
 */

import http from './http'
import type {
  ChurchEvent,
  EventDetail,
  EventAttendance,
  EventCreateInput,
  EventUpdateInput,
  EventListFilters,
  BulkAttendanceInput,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const eventService = {
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: EventListFilters,
  ): Promise<ApiResponse<PaginatedResponse<ChurchEvent>>> {
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

    return http.get(`event/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<EventDetail>> {
    return http.get(`event/view/${id}`).then((res) => res.data)
  },

  async create(data: EventCreateInput): Promise<ApiResponse<ChurchEvent>> {
    return http.post('event/create', data).then((res) => res.data)
  },

  async update(id: number, data: EventUpdateInput): Promise<ApiResponse<ChurchEvent>> {
    return http.put(`event/update/${id}`, { ...data, event_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`event/delete/${id}`).then((res) => res.data)
  },

  async recordAttendance(eventId: number, data: BulkAttendanceInput): Promise<ApiResponse<void>> {
    return http.post(`event/${eventId}/attendance`, data).then((res) => res.data)
  },

  async getAttendance(eventId: number): Promise<ApiResponse<EventAttendance[]>> {
    return http.get(`event/${eventId}/attendance`).then((res) => res.data)
  },

  async upcoming(): Promise<ApiResponse<ChurchEvent[]>> {
    return http.get('event/upcoming').then((res) => res.data)
  },

  async byDate(startDate: string, endDate: string): Promise<ApiResponse<ChurchEvent[]>> {
    return http.get(`event/by-date?start_date=${startDate}&end_date=${endDate}`).then((res) => res.data)
  },
}
