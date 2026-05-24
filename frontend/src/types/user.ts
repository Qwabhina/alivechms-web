/**
 * @file types/user.ts
 * @description TypeScript interfaces for User Management module.
 */

import type { Pagination } from './api'

/**
 * User with system access (extends member with login capabilities)
 */
export interface User {
  /** Member ID */
  MbrID: number
  /** Unique member identifier */
  MbrUniqueID: string
  /** First name */
  MbrFirstName: string
  /** Family name */
  MbrFamilyName: string
  /** Full name display */
  FullName: string
  /** Email address */
  MbrEmail: string
  /** Username for login */
  Username: string | null
  /** Whether the user account is active */
  IsActive: boolean
  /** Whether the member has login credentials */
  HasLogin: boolean
  /** Profile photo URL */
  ProfilePhoto: string | null
  /** Last login timestamp */
  LastLoginAt: string | null
  /** Account creation date */
  CreatedAt: string
  /** Member roles */
  roles?: UserRole[]
  /** Direct permissions */
  permissions?: string[]
}

/**
 * User role assignment
 */
export interface UserRole {
  /** Role ID */
  RoleID: number
  /** Role name */
  RoleName: string
  /** Role description */
  Description: string | null
  /** Whether this role assignment is active */
  IsActive: boolean
  /** Assignment start date */
  StartDate: string | null
  /** Assignment end date */
  EndDate: string | null
}

/**
 * Filters for user listing
 */
export interface UserFilters {
  /** Search by name, email, or username */
  search?: string
  /** Filter by account status */
  is_active?: boolean
  /** Filter by role ID */
  role_id?: number
  /** Filter users with/without login */
  has_login?: boolean
  /** Sort field */
  sort_by?: 'MbrFirstName' | 'MbrFamilyName' | 'CreatedAt' | 'LastLoginAt'
  /** Sort direction */
  sort_dir?: 'ASC' | 'DESC'
}

/**
 * Create user account request
 */
export interface CreateUserAccount {
  /** Member ID to grant access */
  mbr_id: number
  /** Username for login */
  username: string
  /** Initial password */
  password: string
  /** Whether to activate immediately */
  is_active?: boolean
  /** Role IDs to assign */
  role_ids?: number[]
}

/**
 * Update user account request
 */
export interface UpdateUserAccount {
  /** New username */
  username?: string
  /** Whether account is active */
  is_active?: boolean
  /** New password (for reset) */
  password?: string
}

/**
 * Assign roles to user
 */
export interface AssignUserRoles {
  /** Role IDs to assign */
  role_ids: number[]
  /** Whether to replace existing roles */
  replace?: boolean
}

/**
 * User activity log entry
 */
export interface UserActivityLog {
  /** Log entry ID */
  LogID: number
  /** Activity type */
  ActivityType: string
  /** Activity description */
  Description: string
  /** IP address */
  IPAddress: string | null
  /** User agent */
  UserAgent: string | null
  /** Timestamp */
  CreatedAt: string
}

/**
 * User statistics
 */
export interface UserStats {
  /** Total users with system access */
  total_users: number
  /** Active users */
  active_users: number
  /** Inactive users */
  inactive_users: number
  /** Users logged in today */
  logged_in_today: number
  /** New users this month */
  new_this_month: number
}

/**
 * Paginated user response
 */
export interface PaginatedUsers {
  /** User list */
  data: User[]
  /** Pagination metadata */
  meta: Pagination
}
