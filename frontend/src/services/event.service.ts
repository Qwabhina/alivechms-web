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
  EventRegistration,
  EventVolunteer,
  EventResource,
  EventCheckIn,
  EventTemplate,
  EventConflict,
  EventStatus,
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

  // Registration Management
  async getRegistrations(eventId: number): Promise<ApiResponse<EventRegistration[]>> {
    return http.get(`event/${eventId}/registrations`).then((res) => res.data)
  },

  async registerAttendee(eventId: number, data: { email: string }): Promise<ApiResponse<EventRegistration>> {
    return http.post(`event/${eventId}/register`, data).then((res) => res.data)
  },

  async unregisterAttendee(eventId: number, registrationId: number): Promise<ApiResponse<void>> {
    return http.delete(`event/${eventId}/registrations/${registrationId}`).then((res) => res.data)
  },

  // Volunteer Management
  async getVolunteers(eventId: number): Promise<ApiResponse<EventVolunteer[]>> {
    return http.get(`event/${eventId}/volunteers`).then((res) => res.data)
  },

  async assignVolunteer(eventId: number, data: { member_id: number; role: string }): Promise<ApiResponse<EventVolunteer>> {
    return http.post(`event/${eventId}/volunteers`, data).then((res) => res.data)
  },

  async removeVolunteer(eventId: number, volunteerId: number): Promise<ApiResponse<void>> {
    return http.delete(`event/${eventId}/volunteers/${volunteerId}`).then((res) => res.data)
  },

  // Check-In System
  async checkIn(eventId: number, memberId: number): Promise<ApiResponse<EventCheckIn>> {
    return http.post(`event/${eventId}/checkin`, { member_id: memberId }).then((res) => res.data)
  },

  async checkOut(eventId: number, memberId: number): Promise<ApiResponse<void>> {
    return http.post(`event/${eventId}/checkout`, { member_id: memberId }).then((res) => res.data)
  },

  async getCheckInList(eventId: number): Promise<ApiResponse<EventCheckIn[]>> {
    return http.get(`event/${eventId}/checkins`).then((res) => res.data)
  },

  // Resources/Materials
  async getResources(eventId: number): Promise<ApiResponse<EventResource[]>> {
    return http.get(`event/${eventId}/resources`).then((res) => res.data)
  },

  async addResource(eventId: number, data: FormData): Promise<ApiResponse<EventResource>> {
    return http.post(`event/${eventId}/resources`, data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }).then((res) => res.data)
  },

  async deleteResource(eventId: number, resourceId: number): Promise<ApiResponse<void>> {
    return http.delete(`event/${eventId}/resources/${resourceId}`).then((res) => res.data)
  },

  // Event Status
  async updateStatus(eventId: number, status: EventStatus): Promise<ApiResponse<void>> {
    return http.put(`event/${eventId}/status`, { status }).then((res) => res.data)
  },

  // Templates
  async getTemplates(): Promise<ApiResponse<EventTemplate[]>> {
    return http.get('event/templates').then((res) => res.data)
  },

  // Conflict Detection
  async checkConflicts(eventId: number, data: { event_date: string; start_time: string; end_time: string; location?: string }): Promise<ApiResponse<EventConflict[]>> {
    return http.post(`event/${eventId}/conflicts`, data).then((res) => res.data)
  },

  // Waitlist
  async joinWaitlist(eventId: number, memberId: number): Promise<ApiResponse<void>> {
    return http.post(`event/${eventId}/waitlist`, { member_id: memberId }).then((res) => res.data)
  },

  async getWaitlist(eventId: number): Promise<ApiResponse<{ position: number; member_name: string }[]>> {
    return http.get(`event/${eventId}/waitlist`).then((res) => res.data)
  },
}
