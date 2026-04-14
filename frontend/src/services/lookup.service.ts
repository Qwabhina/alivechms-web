/**
 * Lookup service for reference data
 */

import http from './http'
import type {
  LookupItem,
  LookupCategory,
  LookupCreateInput,
  LookupUpdateInput,
  MembershipType,
  MembershipTypeCreateInput,
  MembershipTypeUpdateInput,
  ApiResponse,
} from '@/types'

export const lookupService = {
  // General Lookups
  async getByCategory(category: string): Promise<ApiResponse<LookupItem[]>> {
    return http.get(`lookups/category/${category}`).then((res) => res.data)
  },

  async getCategories(): Promise<ApiResponse<LookupCategory[]>> {
    return http.get('lookups/categories').then((res) => res.data)
  },

  async create(data: LookupCreateInput): Promise<ApiResponse<LookupItem>> {
    return http.post('lookups/create', data).then((res) => res.data)
  },

  async update(id: number, data: LookupUpdateInput): Promise<ApiResponse<LookupItem>> {
    return http.put(`lookups/update/${id}`, { ...data, id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`lookups/delete/${id}`).then((res) => res.data)
  },

  // Membership Types
  async listMembershipTypes(): Promise<ApiResponse<MembershipType[]>> {
    return http.get('membershiptype/all').then((res) => res.data)
  },

  async createMembershipType(data: MembershipTypeCreateInput): Promise<ApiResponse<MembershipType>> {
    return http.post('membershiptype/create', data).then((res) => res.data)
  },

  async updateMembershipType(id: number, data: MembershipTypeUpdateInput): Promise<ApiResponse<MembershipType>> {
    return http.put(`membershiptype/update/${id}`, { ...data, membership_type_id: id }).then((res) => res.data)
  },

  async deleteMembershipType(id: number): Promise<ApiResponse<void>> {
    return http.delete(`membershiptype/delete/${id}`).then((res) => res.data)
  },

  // Member-related lookups
  async memberLookups(): Promise<ApiResponse<{
    marital_statuses: Array<{ id: number; name: string }>
    education_levels: Array<{ id: number; name: string }>
    membership_statuses: Array<{ id: number; name: string }>
    phone_types: Array<{ id: number; name: string }>
    branches: Array<{ id: number; name: string; code: string }>
  }>> {
    return http.get('lookups/member').then((res) => res.data)
  },

  // Finance-related lookups
  async financeLookups(): Promise<ApiResponse<{
    contribution_types: Array<{ id: number; name: string }>
    payment_methods: Array<{ id: number; name: string }>
    expense_categories: Array<{ id: number; name: string }>
    pledge_types: Array<{ id: number; name: string }>
  }>> {
    return http.get('lookups/finance').then((res) => res.data)
  },
}
