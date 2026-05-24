/**
 * Communication service for API communication
 */

import http from './http'
import type {
  Communication,
  CommunicationTemplate,
  CommunicationCreateInput,
  CommunicationTemplateCreateInput,
  CommunicationTemplateUpdateInput,
  CommunicationListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const communicationService = {
  // Communications
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: CommunicationListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Communication>>> {
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

    return http.get(`communication/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<Communication>> {
    return http.get(`communication/view/${id}`).then((res) => res.data)
  },

  async send(data: CommunicationCreateInput): Promise<ApiResponse<Communication>> {
    return http.post('communication/send', data).then((res) => res.data)
  },

  async schedule(data: CommunicationCreateInput): Promise<ApiResponse<Communication>> {
    return http.post('communication/schedule', data).then((res) => res.data)
  },

  async cancel(id: number): Promise<ApiResponse<void>> {
    return http.post(`communication/${id}/cancel`, {}).then((res) => res.data)
  },

  async recipients(id: number): Promise<ApiResponse<Array<{
    RecipientID: number
    MemberName?: string
    Email?: string
    Phone?: string
    Status: string
  }>>> {
    return http.get(`communication/${id}/recipients`).then((res) => res.data)
  },

  // Templates
  async listTemplates(): Promise<ApiResponse<CommunicationTemplate[]>> {
    return http.get('communication/templates').then((res) => res.data)
  },

  async createTemplate(data: CommunicationTemplateCreateInput): Promise<ApiResponse<CommunicationTemplate>> {
    return http.post('communication/template/create', data).then((res) => res.data)
  },

  async updateTemplate(id: number, data: CommunicationTemplateUpdateInput): Promise<ApiResponse<CommunicationTemplate>> {
    return http.put(`communication/template/update/${id}`, { ...data, template_id: id }).then((res) => res.data)
  },

  async deleteTemplate(id: number): Promise<ApiResponse<void>> {
    return http.delete(`communication/template/delete/${id}`).then((res) => res.data)
  },

  // Bulk operations
  async sendBulkEmail(recipientIds: number[], subject: string, content: string): Promise<ApiResponse<void>> {
    return http.post('communication/bulk-email', {
      recipient_ids: recipientIds,
      subject,
      content,
    }).then((res) => res.data)
  },

  async sendBulkSMS(recipientIds: number[], content: string): Promise<ApiResponse<void>> {
    return http.post('communication/bulk-sms', {
      recipient_ids: recipientIds,
      content,
    }).then((res) => res.data)
  },
}
