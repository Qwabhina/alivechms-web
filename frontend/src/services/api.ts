import axios from 'axios'
import { Alerts } from '@/utils/alerts'

// Helper to get the correct base path for API calls and redirects
const getAppBase = () => {
   if (import.meta.env.VITE_API_BASE_URL) return import.meta.env.VITE_API_BASE_URL

   const path = window.location.pathname
   // Match everything before /public/ui/ or /ui/
   // This ensures APP_BASE is the folder containing index.php
   const matches = path.match(/(.*\/)public\/ui\/?$/) || path.match(/(.*\/)ui\/?$/)

   if (matches) {
      return matches[1]
   }
   return '/'
}

const APP_BASE = getAppBase()

/**
 * Resolves a URL relative to the application base.
 * Prevents double-prepending if the path already starts with the base.
 */
export const resolveUrl = (path: string | null | undefined) => {
   if (!path) return ''
   if (path.startsWith('http') || path.startsWith('data:')) return path

   const base = APP_BASE.endsWith('/') ? APP_BASE.slice(0, -1) : APP_BASE
   const cleanPath = path.startsWith('/') ? path : `/${path}`

   // If APP_BASE is not root, and path already starts with base, don't prepend base
   if (base && base !== '/' && cleanPath.startsWith(base)) {
      return cleanPath
   }

   return `${base}${cleanPath}`
}

const api = axios.create({
   baseURL: APP_BASE,
   withCredentials: true,
   headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
   },
})

// CSRF token storage
let csrfToken: string | null = null

// Request interceptor for Bearer and CSRF tokens
api.interceptors.request.use(async (config) => {
   const token = localStorage.getItem('access_token')
   if (token) {
      config.headers.Authorization = `Bearer ${token}`
   }

   // Add CSRF token for state-changing requests
   if (['post', 'put', 'delete', 'patch'].includes(config.method?.toLowerCase() || '')) {
      if (!csrfToken) {
         try {
            // Use axios instead of api to avoid interceptor recursion
            const response = await axios.get(`${APP_BASE}auth/csrf`, { withCredentials: true })
            csrfToken = response.data.data.csrf_token
         } catch (e) {
            console.error('Failed to fetch CSRF token', e)
         }
      }
      if (csrfToken) {
         config.headers['X-CSRF-Token'] = csrfToken
      }
   }

   return config
})

// Response interceptor for handling 401s, 403s (CSRF), and token refresh
api.interceptors.response.use(
   (response) => response,
   async (error) => {
      const originalRequest = error.config

      // Handle 403 Forbidden (likely CSRF failure)
      if (error.response?.status === 403 && !originalRequest._csrfRetry) {
         originalRequest._csrfRetry = true
         try {
            const response = await axios.get(`${APP_BASE}auth/csrf`, { withCredentials: true })
            csrfToken = response.data.data.csrf_token
            originalRequest.headers['X-CSRF-Token'] = csrfToken
            return api(originalRequest)
         } catch (csrfError) {
            return Promise.reject(csrfError)
         }
      }

      // Handle 401 Unauthorized
      if (error.response?.status === 401 && !originalRequest._retry) {
         originalRequest._retry = true
         try {
            const response = await api.post('auth/refresh')
            const { access_token } = response.data.data
            localStorage.setItem('access_token', access_token)
            originalRequest.headers.Authorization = `Bearer ${access_token}`
            return api(originalRequest)
         } catch (refreshError) {
            localStorage.removeItem('access_token')
            // Redirect using hash since we use Hash History
            window.location.hash = '#/login'
            return Promise.reject(refreshError)
         }
      }
      return Promise.reject(error)
   }
)

export default api
