/**
 * @file services/http.ts
 * @description Centralized HTTP client with Axios.
 *
 * Interceptor pipeline:
 * 1. Request  → attach Bearer token + CSRF header
 * 2. Response → unwrap envelope, handle 401 silent-refresh, toast errors
 *
 * Access tokens live **in memory only** (Pinia store).
 * Refresh tokens live in HttpOnly cookies (managed by the backend).
 */

import axios, {
  type AxiosInstance,
  type AxiosError,
  type InternalAxiosRequestConfig,
} from 'axios'

/* ── singleton refs (set by auth store at boot) ─────────────────────── */

let _getAccessToken: (() => string | null) | null = null
let _getCsrfToken: (() => string | null) | null = null
let _onRefreshSuccess: ((accessToken: string, csrfToken: string) => void) | null = null
let _onAuthFailure: (() => void) | null = null

/**
 * Called once from the auth store to wire up token accessors
 * without creating a circular import.
 */
export function configureAuth(opts: {
  getAccessToken: () => string | null
  getCsrfToken: () => string | null
  onRefreshSuccess: (accessToken: string, csrfToken: string) => void
  onAuthFailure: () => void
}) {
  _getAccessToken = opts.getAccessToken
  _getCsrfToken = opts.getCsrfToken
  _onRefreshSuccess = opts.onRefreshSuccess
  _onAuthFailure = opts.onAuthFailure
}

/* ── Axios instance ─────────────────────────────────────────────────── */

const http: AxiosInstance = axios.create({
  // Dev:  /api  → Vite proxy strips /api and forwards to www.onechurch.com
  // Prod: /     → root-relative, so requests go to www.onechurch.com/auth/login
  //              (not www.onechurch.com/public/auth/login)
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/',
  timeout: 30_000,
  headers: { 'Content-Type': 'application/json' },
  withCredentials: true, // send HttpOnly cookies (refresh token)
})

/* ── Request interceptor ────────────────────────────────────────────── */

http.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  // 1. Attach access token
  const token = _getAccessToken?.()
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`
  }

  // 2. Attach CSRF token on state-changing methods
  const csrf = _getCsrfToken?.()
  if (csrf && config.headers && ['post', 'put', 'delete', 'patch'].includes(config.method ?? '')) {
    config.headers['X-CSRF-Token'] = csrf
  }

  // 3. Don't send Content-Type for FormData (browser sets boundary)
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type']
  }

  return config
})

/* ── Response interceptor ───────────────────────────────────────────── */

let isRefreshing = false
let refreshSubscribers: Array<(token: string) => void> = []

function subscribeToRefresh(cb: (token: string) => void) {
  refreshSubscribers.push(cb)
}

function onRefreshed(token: string) {
  refreshSubscribers.forEach((cb) => cb(token))
  refreshSubscribers = []
}

http.interceptors.response.use(
  // Success – just pass through
  (response) => response,

  // Error handler
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean }

    // 401 — attempt silent refresh (but not if we're already refreshing or this *is* the refresh call)
    if (
      error.response?.status === 401 &&
      !originalRequest._retry &&
      originalRequest.url !== 'auth/refresh' &&
      originalRequest.url !== 'auth/login'
    ) {
      if (isRefreshing) {
        // Queue this request until refresh completes
        return new Promise((resolve) => {
          subscribeToRefresh((newToken: string) => {
            if (originalRequest.headers) {
              originalRequest.headers.Authorization = `Bearer ${newToken}`
            }
            resolve(http(originalRequest))
          })
        })
      }

      originalRequest._retry = true
      isRefreshing = true

      try {
        const { data } = await http.post('auth/refresh')
        const newAccessToken = data.data?.access_token ?? data.access_token
        const newCsrfToken = data.data?.csrf_token ?? data.csrf_token

        _onRefreshSuccess?.(newAccessToken, newCsrfToken)
        onRefreshed(newAccessToken)

        // Retry original request with new token
        if (originalRequest.headers) {
          originalRequest.headers.Authorization = `Bearer ${newAccessToken}`
        }
        return http(originalRequest)
      } catch {
        // Refresh failed – force logout
        _onAuthFailure?.()
        return Promise.reject(error)
      } finally {
        isRefreshing = false
      }
    }

    return Promise.reject(error)
  },
)

export { http }
export default http
