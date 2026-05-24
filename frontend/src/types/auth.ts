/**
 * @file auth.ts
 * @description Types for the Identity & Security module.
 */

/* ---------- Login ---------- */

export interface LoginPayload {
  userid: string
  passkey: string
  remember?: boolean
}

export interface LoginResponse {
  access_token: string
  csrf_token: string
  user: AuthUser
  refresh_token: string | null
}

/* ---------- User ---------- */

export interface AuthUser {
  MbrID: number
  UserID: number
  Username: string
  MbrFirstName: string
  MbrFamilyName: string
  MbrEmailAddress: string
  MbrProfilePicture: string | null
  MembershipStatus: string
  BranchID: number
  permissions: string[]
  /** Role names resolved from member_role / RBAC */
  Role?: string[]
}

/* ---------- Token Refresh ---------- */

export interface RefreshResponse {
  access_token: string
  csrf_token: string
  user: AuthUser
  refresh_token: string | null
}

/* ---------- Auth Status ---------- */

export interface AuthStatusResponse {
  authenticated: boolean
  user_id?: number
  username?: string
}

/* ---------- Sessions ---------- */

export interface UserSession {
  SessionID: number
  UserID: number
  IPAddress: string | null
  UserAgent: string | null
  CreatedAt: string
  ExpiresAt: string
  IsRevoked: number
}

/* ---------- CSRF Config ---------- */

export interface CsrfConfig {
  csrf_token: string
  config: {
    csrf_header: string
    csrf_cookie: string
    csrf_ttl: number
  }
}
