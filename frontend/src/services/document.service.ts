/**
 * Document service for API communication
 */

import http from './http'
import type {
  Document,
  DocumentDetail,
  DocumentCreateInput,
  DocumentUpdateInput,
  DocumentListFilters,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const documentService = {
  async list(
    page: number = 1,
    limit: number = 10,
    filters?: DocumentListFilters,
  ): Promise<ApiResponse<PaginatedResponse<Document>>> {
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

    return http.get(`document/all?${params.toString()}`).then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<DocumentDetail>> {
    return http.get(`document/view/${id}`).then((res) => res.data)
  },

  async upload(file: File, data: DocumentCreateInput): Promise<ApiResponse<Document>> {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('document_name', data.document_name)
    formData.append('document_type', data.document_type)
    if (data.related_entity_type) {
      formData.append('related_entity_type', data.related_entity_type)
    }
    if (data.related_entity_id) {
      formData.append('related_entity_id', data.related_entity_id.toString())
    }
    if (data.description) {
      formData.append('description', data.description)
    }
    if (data.tags) {
      formData.append('tags', JSON.stringify(data.tags))
    }

    return http.post('document/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }).then((res) => res.data)
  },

  async update(id: number, data: DocumentUpdateInput): Promise<ApiResponse<Document>> {
    return http.put(`document/update/${id}`, { ...data, document_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`document/delete/${id}`).then((res) => res.data)
  },

  async download(id: number): Promise<Blob> {
    return http.get(`document/download/${id}`, { responseType: 'blob' }).then((res) => res.data)
  },

  async byEntity(entityType: string, entityId: number): Promise<ApiResponse<Document[]>> {
    return http.get(`document/entity?type=${entityType}&id=${entityId}`).then((res) => res.data)
  },

  async addTags(id: number, tags: string[]): Promise<ApiResponse<void>> {
    return http.post(`document/${id}/tags`, { tags }).then((res) => res.data)
  },

  async removeTag(id: number, tag: string): Promise<ApiResponse<void>> {
    return http.delete(`document/${id}/tags/${encodeURIComponent(tag)}`).then((res) => res.data)
  },
}
