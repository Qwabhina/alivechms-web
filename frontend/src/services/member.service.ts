/**
 * @file services/member.service.ts
 * @description API calls for the People module (Members).
 */

import http from './http'
import type { ApiResponse, PaginatedResponse } from '@/types/api'
import type {
  Member,
  MemberCreate,
  MemberUpdate,
  MemberStats,
  MemberLookupData,
  MemberFilters,
} from '@/types/member'

export const memberService = {
  /** Paginated list with filters & sorting */
  getAll(page = 1, limit = 25, filters?: MemberFilters) {
    return http.get<PaginatedResponse<Member>>('member/all', {
      params: { page, limit, ...filters },
    })
  },

  /** Single member with phones + milestones */
  getById(id: number) {
    return http.get<ApiResponse<Member>>(`member/view/${id}`)
  },

  /** Recent members (last 10) */
  getRecent() {
    return http.get<ApiResponse<{ data: Member[] }>>('member/recent')
  },

  /** Membership statistics */
  getStats() {
    return http.get<ApiResponse<MemberStats>>('member/stats')
  },

  /** Create member (JSON body) */
  create(data: MemberCreate) {
    return http.post<ApiResponse<{ status: string; mbr_id: number }>>('member/create', data)
  },

  /** Create member with profile picture (FormData) */
  createWithPhoto(formData: FormData) {
    return http.post<ApiResponse<{ status: string; mbr_id: number }>>('member/create', formData)
  },

  /** Update member (JSON body) */
  update(id: number, data: MemberUpdate) {
    return http.put<ApiResponse<{ status: string; mbr_id: number }>>(`member/update/${id}`, data)
  },

  /** Update member with photo (FormData, POST fallback for multipart) */
  updateWithPhoto(id: number, formData: FormData) {
    return http.post<ApiResponse<{ status: string; mbr_id: number }>>(`member/update/${id}`, formData)
  },

  /** Upload profile picture only */
  uploadPhoto(id: number, file: File) {
    const fd = new FormData()
    fd.append('profile_picture', file)
    return http.post<ApiResponse<{ path: string; url: string }>>(`member/upload-photo/${id}`, fd)
  },

  /** Soft delete */
  delete(id: number) {
    return http.delete<ApiResponse<{ status: string }>>(`member/delete/${id}`)
  },

  /** Lookup data for forms (statuses, branches, phone types, etc.) */
  getLookupData() {
    return http.get<ApiResponse<MemberLookupData>>('member/lookup-data')
  },

  /** Lookup by unique ID */
  getByUniqueId(uid: string) {
    return http.get<ApiResponse<Member>>(`member/by-unique-id/${uid}`)
  },

  /** Toggle system access */
  toggleAuth(id: number, isActive: boolean) {
    return http.post<ApiResponse<{ status: string; is_active: boolean }>>(`member/toggle-auth/${id}`, {
      is_active: isActive,
    })
  },
}
