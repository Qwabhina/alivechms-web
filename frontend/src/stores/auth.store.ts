/**
 * @file stores/auth.store.ts
 * @description Auth state management.
 *
 * Responsibilities:
 * - Store access token (in memory only — never localStorage)
 * - Store user profile and permissions
 * - Login / logout / silent-refresh lifecycle
 * - Permission-checking helpers for UI rendering
 * - Wire up HTTP interceptors on init
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/auth.service'
import { configureAuth } from '@/services/http'
import type { AuthUser } from '@/types/auth'
import type { AxiosError } from 'axios'

export const useAuthStore = defineStore('auth', () => {
  /* ── State ─────────────────────────────────────────────────────── */

  const accessToken = ref<string | null>(null)
  const refreshToken = ref<string | null>(null)
  const csrfToken = ref<string | null>(null)
  const user = ref<AuthUser | null>(null)
  const permissions = ref<string[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const initialized = ref(false)

  /* ── Computed ──────────────────────────────────────────────────── */

  const isAuthenticated = computed(() => !!accessToken.value && !!user.value)

  const fullName = computed(() =>
    user.value ? `${user.value.MbrFirstName} ${user.value.MbrFamilyName}` : '',
  )

  const initials = computed(() =>
    user.value
      ? `${user.value.MbrFirstName.charAt(0)}${user.value.MbrFamilyName.charAt(0)}`.toUpperCase()
      : '',
  )

  /* ── Permission Helpers ───────────────────────────────────────── */

  function hasPermission(permission: string): boolean {
    return permissions.value.includes(permission)
  }

  function hasAnyPermission(...perms: string[]): boolean {
    return perms.some((p) => permissions.value.includes(p))
  }

  /* ── Actions ──────────────────────────────────────────────────── */

  /**
   * Login with credentials.
   */
  async function login(userid: string, passkey: string, remember = false) {
    isLoading.value = true
    error.value = null

    try {
      const { data: res } = await authService.login({ userid, passkey, remember })
      const payload = res.data!

      accessToken.value = payload.access_token
      refreshToken.value = payload.refresh_token ?? ''
      csrfToken.value = payload.csrf_token
      user.value = payload.user
      permissions.value = payload.user.permissions ?? []

      return true
    } catch (err) {
      const axiosErr = err as AxiosError<{ message: string }>
      error.value = axiosErr.response?.data?.message ?? 'Login failed'
      return false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Attempt to restore session using the HttpOnly refresh cookie.
   * Called on app boot.
   */
  async function tryRestoreSession(): Promise<boolean> {
    try {
      const { data: res } = await authService.refresh()
      const payload = res.data!

      accessToken.value = payload.access_token
      csrfToken.value = payload.csrf_token
      user.value = payload.user
      permissions.value = payload.user?.permissions ?? []
      refreshToken.value = payload.refresh_token ?? ''

      return true
    } catch {
      // No valid session — that's fine
      return false
    } finally {
      initialized.value = true
    }
  }

  /**
   * Logout — revoke session and clear state.
   */
  async function logout() {
    try {
      await authService.logout()
    } catch {
      // Even if API call fails, clear local state
    } finally {
      clearAuthState()
    }
  }

  /**
   * Clear all auth state (used by logout and on refresh failure).
   */
  function clearAuthState() {
    accessToken.value = null
    csrfToken.value = null
    user.value = null
    permissions.value = []
    error.value = null
  }

  /* ── Wire up HTTP interceptors ────────────────────────────────── */

  configureAuth({
    getAccessToken: () => accessToken.value,
    getCsrfToken: () => csrfToken.value,
    onRefreshSuccess: (newAccessToken: string, newCsrfToken: string) => {
      accessToken.value = newAccessToken
      csrfToken.value = newCsrfToken
    },
    onAuthFailure: () => {
      clearAuthState()
      // Navigation to /login is handled by the router guard
    },
  })

  /* ── Public API ───────────────────────────────────────────────── */

  return {
    // State
    accessToken,
    csrfToken,
    user,
    permissions,
    isLoading,
    error,
    initialized,

    // Computed
    isAuthenticated,
    fullName,
    initials,

    // Actions
    login,
    logout,
    tryRestoreSession,
    clearAuthState,
    hasPermission,
    hasAnyPermission,
  }
})
