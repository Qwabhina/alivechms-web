/**
 * Role and Permission service for API communication
 */

import http from './http'
import type {
  Role,
  RoleWithPermissions,
  Permission,
  RoleCreateInput,
  RoleUpdateInput,
} from '@/types/role'
import type {
  AssignRoleInput,
  ApiResponse,
  PaginatedResponse,
} from '@/types'

export const roleService = {
  // Roles
  async list(): Promise<ApiResponse<Role[]>> {
    return http.get('role/all').then((res) => res.data)
  },

  async get(id: number): Promise<ApiResponse<RoleWithPermissions>> {
    return http.get(`role/view/${id}`).then((res) => res.data)
  },

  async create(data: RoleCreateInput): Promise<ApiResponse<Role>> {
    return http.post('role/create', data).then((res) => res.data)
  },

  async update(id: number, data: RoleUpdateInput): Promise<ApiResponse<Role>> {
    return http.put(`role/update/${id}`, { ...data, role_id: id }).then((res) => res.data)
  },

  async delete(id: number): Promise<ApiResponse<void>> {
    return http.delete(`role/delete/${id}`).then((res) => res.data)
  },

  // Permissions
  async listPermissions(): Promise<ApiResponse<Permission[]>> {
    return http.get('permission/all').then((res) => res.data)
  },

  async getPermissionMatrix(): Promise<ApiResponse<Array<{ role: Role; permissions: Record<string, boolean> }>>> {
    return http.get('permission/matrix').then((res) => res.data)
  },

  // User Roles
  async assignRole(data: AssignRoleInput): Promise<ApiResponse<void>> {
    return http.post('role/assign', data).then((res) => res.data)
  },

  async removeRole(userId: number, roleId: number): Promise<ApiResponse<void>> {
    return http.delete(`role/remove?user_id=${userId}&role_id=${roleId}`).then((res) => res.data)
  },

  async getUserRoles(userId: number): Promise<ApiResponse<Role[]>> {
    return http.get(`role/user/${userId}`).then((res) => res.data)
  },
}
