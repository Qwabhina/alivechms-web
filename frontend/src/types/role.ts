/**
 * @file types/role.ts
 * @description TypeScript interfaces for Role and Permission management.
 */

import type { Pagination } from './api'

/**
 * Role definition
 */
export interface Role {
  /** Role ID */
  id: number
  /** Role name */
  name: string
  /** Role description */
  description: string | null
  /** Whether the role is active */
  is_active: boolean
  /** When the role was created */
  created_at?: string
  /** When the role was last updated */
  updated_at?: string
}

/**
 * Role with permissions
 */
export interface RoleWithPermissions extends Role {
  /** Permissions assigned to this role */
  permissions: string[]
}

/**
 * Permission definition
 */
export interface Permission {
  /** Permission ID */
  id: number
  /** Permission name (e.g., 'users.view', 'members.create') */
  name: string
  /** Permission description */
  description: string | null
  /** Permission category */
  category?: string
}

/**
 * Permission category
 */
export interface PermissionCategory {
  id: string
  name: string
  description?: string
}

/**
 * Create role request
 */
export interface RoleCreateInput {
  /** Role name */
  name: string
  /** Role description */
  description?: string
  /** Whether the role is active */
  is_active?: boolean
  /** Permission IDs to assign */
  permission_ids?: number[]
}

/**
 * Update role request
 */
export interface RoleUpdateInput {
  /** New role name */
  name?: string
  /** New role description */
  description?: string
  /** Whether the role is active */
  is_active?: boolean
  /** Permission IDs to assign (replaces existing if provided) */
  permission_ids?: number[]
}

/**
 * Permission matrix entry
 */
export interface PermissionMatrixEntry {
  /** Role */
  role: Role
  /** Permissions with boolean flags */
  permissions: Record<string, boolean>
}

/**
 * Paginated roles response
 */
export interface PaginatedRoles {
  /** Role list */
  data: Role[]
  /** Pagination metadata */
  meta: Pagination
}



/* ---------- Role & Permission Types ---------- */

/* Role & Permission types are imported from './auth' to avoid duplicate exports */

export interface UserRoleT {
  UserRoleID: number
  UserID: number
  RoleID: number
  RoleName?: string
  AssignedBy?: number
  AssignedAt?: string
}

export interface AssignRoleInput {
  user_id: number
  role_id: number
}

export interface PermissionMatrix {
  role: Role
  permissions: Record<string, boolean>
}
