/**
 * @file services/user.service.ts
 * @description API calls for User Management module.
 */

import http from './http'
import type { ApiResponse, PaginatedResponse } from '@/types/api'
import type {
  User,
  UserFilters,
  UserStats,
  CreateUserAccount,
  UpdateUserAccount,
  AssignUserRoles,
  UserActivityLog,
  UserRole,
} from '@/types/user'

export const userService = {
  /**
   * Get paginated list of users with system access
   */
  list(
    page = 1,
    limit = 25,
    filters?: UserFilters,
  ): Promise<ApiResponse<PaginatedResponse<User>>> {
    const params = new URLSearchParams()
    params.append('page', page.toString())
    params.append('limit', limit.toString())
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, String(value))
        }
      })
    }
    return http.get(`user/all?${params.toString()}`).then((res) => res.data)
  },

  /**
   * Get user by member ID
   */
  getById(id: number): Promise<ApiResponse<User>> {
    return http.get(`user/view/${id}`).then((res) => res.data)
  },

  /**
   * Get user statistics
   */
  getStats(): Promise<ApiResponse<UserStats>> {
    return http.get('user/stats').then((res) => res.data)
  },

  /**
   * Grant system access to a member (create user account)
   */
  grantAccess(data: CreateUserAccount): Promise<ApiResponse<{ status: string; mbr_id: number }>> {
    return http.post('user/grant-access', data).then((res) => res.data)
  },

  /**
   * Revoke system access from a member
   */
  revokeAccess(id: number): Promise<ApiResponse<{ status: string }>> {
    return http.post(`user/revoke-access/${id}`, {}).then((res) => res.data)
  },

  /**
   * Toggle user account active status
   */
  toggleActive(id: number, isActive: boolean): Promise<ApiResponse<{ status: string; is_active: boolean }>> {
    return http.post(`user/toggle-active/${id}`, { is_active: isActive }).then((res) => res.data)
  },

  /**
   * Update user account details
   */
  updateAccount(id: number, data: UpdateUserAccount): Promise<ApiResponse<{ status: string }>> {
    return http.put(`user/update/${id}`, data).then((res) => res.data)
  },

  /**
   * Reset user password
   */
  resetPassword(id: number, newPassword: string): Promise<ApiResponse<{ status: string }>> {
    return http.post(`user/reset-password/${id}`, { password: newPassword }).then((res) => res.data)
  },

  /**
   * Get user's assigned roles
   */
  getUserRoles(id: number): Promise<ApiResponse<{ roles: UserRole[] }>> {
    return http.get(`user/roles/${id}`).then((res) => res.data)
  },

  /**
   * Assign roles to user
   */
  assignRoles(id: number, data: AssignUserRoles): Promise<ApiResponse<{ status: string }>> {
    return http.post(`user/assign-roles/${id}`, data).then((res) => res.data)
  },

  /**
   * Remove role from user
   */
  removeRole(id: number, roleId: number): Promise<ApiResponse<{ status: string }>> {
    return http.delete(`user/remove-role/${id}/${roleId}`).then((res) => res.data)
  },

  /**
   * Get user's activity log
   */
  getActivityLog(
    id: number,
    page = 1,
    limit = 25,
  ): Promise<ApiResponse<PaginatedResponse<UserActivityLog>>> {
    return http
      .get(`user/activity-log/${id}?page=${page}&limit=${limit}`)
      .then((res) => res.data)
  },

  /**
   * Search members who can be granted system access
   */
  searchEligibleMembers(query: string): Promise<ApiResponse<Array<{ MbrID: number; FullName: string; MbrEmail: string; HasLogin: boolean }>>> {
    return http.get(`user/eligible-members?query=${encodeURIComponent(query)}`).then((res) => res.data)
  },

  /**
   * Bulk grant system access
   */
  bulkGrantAccess(data: { mbr_ids: number[]; role_ids?: number[] }): Promise<ApiResponse<{ status: string; granted: number; failed: number }>> {
    return http.post('user/bulk-grant-access', data).then((res) => res.data)
  },

  /**
   * Bulk revoke system access
   */
  bulkRevokeAccess(ids: number[]): Promise<ApiResponse<{ status: string; revoked: number; failed: number }>> {
    return http.post('user/bulk-revoke-access', { mbr_ids: ids }).then((res) => res.data)
  },

  /**
   * Bulk toggle active status
   */
  bulkToggleActive(ids: number[], isActive: boolean): Promise<ApiResponse<{ status: string; updated: number; failed: number }>> {
    return http.post('user/bulk-toggle-active', { mbr_ids: ids, is_active: isActive }).then((res) => res.data)
  },
}
