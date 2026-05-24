/**
 * Audit service for API communication
 */

import http from './http'
import type {
  AuditLog,
  AuditLogListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const auditService = {
  async list(
    page: number = 1,
    limit: number = 50,
    filters?: AuditLogListFilters,
  ): Promise<ApiResponse<PaginatedResponse<AuditLog>>> {
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

    return http.get(`audit/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<AuditLog>> {
    return http.get(`audit/view/${id}`).then((res) => res.data)
  },

  async byEntity(entityType: string, entityId: number): Promise<ApiResponse<AuditLog[]>> {
    return http.get(`audit/entity?type=${entityType}&id=${entityId}`).then((res) => res.data)
  },

  async byUser(userId: number): Promise<ApiResponse<AuditLog[]>> {
    return http.get(`audit/user/${userId}`).then((res) => res.data)
  },

  async export(filters?: AuditLogListFilters): Promise<Blob> {
    const params = new URLSearchParams()
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          params.append(key, String(value))
        }
      })
    }

    return http.get(`audit/export?${params.toString()}`, { responseType: 'blob' }).then((res) => res.data)
  },
}
