/**
 * @file services/auth.service.ts
 * @description API calls for the Identity & Security module.
 */

import http from './http'
import type { ApiResponse } from '@/types/api'
import type {
  LoginPayload,
  LoginResponse,
  RefreshResponse,
  AuthStatusResponse,
  UserSession,
  CsrfConfig,
} from '@/types/auth'

export const authService = {
  /**
   * Authenticate with username + password.
   * Backend sets refresh token as HttpOnly cookie.
   */
  login(payload: LoginPayload) {
    return http.post<ApiResponse<LoginResponse>>('auth/login', payload)
  },

  /**
   * Refresh access token using HttpOnly cookie (sent automatically).
   */
  refresh() {
    return http.post<ApiResponse<RefreshResponse>>('auth/refresh')
  },

  /**
   * Logout – revokes session and clears cookie.
   */
  logout() {
    return http.post<ApiResponse<null>>('auth/logout')
  },

  /**
   * Check if current session is valid.
   */
  status() {
    return http.get<ApiResponse<AuthStatusResponse>>('auth/status')
  },

  /**
   * Get a fresh CSRF token.
   */
  getCsrfToken() {
    return http.get<ApiResponse<CsrfConfig>>('auth/csrf')
  },

  /**
   * List active sessions for the current user.
   */
  getSessions() {
    return http.get<ApiResponse<UserSession[]>>('auth/sessions')
  },

  /**
   * Revoke a specific session.
   */
  revokeSession(sessionId: number) {
    return http.delete<ApiResponse<null>>(`auth/sessions/${sessionId}`)
  },

  /**
   * Revoke all sessions except the current one.
   */
  revokeAllSessions() {
    return http.post<ApiResponse<{ revoked_count: number }>>('auth/sessions/revoke-all')
  },
}
