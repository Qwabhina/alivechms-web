import axios from 'axios';
import type { AxiosInstance, AxiosError, InternalAxiosRequestConfig, AxiosResponse } from 'axios';
import { useAuthStore } from '@/stores/authStore';
import { toast } from '@/services/toast';

// Helper to get the correct base path for API calls and redirects
const getAppBase = () => {
   // If we have a dedicated API URL in env, use it
   if (import.meta.env.VITE_API_BASE_URL) return import.meta.env.VITE_API_BASE_URL;

   const path = window.location.pathname;
   // Match everything before /public/ui/ or /ui/
   // This ensures APP_BASE is the folder containing index.php
   const matches = path.match(/(.*\/)public\/ui\/?$/) || path.match(/(.*\/)ui\/?$/);

   if (matches) {
      return matches[1];
   }
   return '/';
};

const APP_BASE = getAppBase();

/**
 * Resolves a URL relative to the application base.
 */
export const resolveUrl = (path: string | null | undefined) => {
   if (!path) return '';
   if (path.startsWith('http') || path.startsWith('data:')) return path;

   const base = APP_BASE.endsWith('/') ? APP_BASE.slice(0, -1) : APP_BASE;
   const cleanPath = path.startsWith('/') ? path : `/${path}`;

   if (base && base !== '/' && cleanPath.startsWith(base)) {
      return cleanPath;
   }

   return `${base}${cleanPath}`;
};

const apiClient: AxiosInstance = axios.create({
   baseURL: APP_BASE,
   withCredentials: true,
   headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
   },
   timeout: 30000,
});

// CSRF token storage
let csrfToken: string | null = null;

// Request interceptor - Add auth token and CSRF
apiClient.interceptors.request.use(
   async (config: InternalAxiosRequestConfig) => {
      const authStore = useAuthStore();

      // Ensure we have the latest token from storage if not in store
      const token = authStore.accessToken || localStorage.getItem('access_token');
      if (token) {
         config.headers.Authorization = `Bearer ${token}`;
      }

      // Add CSRF token for state-changing requests
      if (['post', 'put', 'delete', 'patch'].includes(config.method?.toLowerCase() || '')) {
         if (!csrfToken) {
            try {
               // Use axios instead of apiClient to avoid interceptor recursion
               const response = await axios.get(`${APP_BASE}auth/csrf`, { withCredentials: true });
               csrfToken = response.data.data.csrf_token;
            } catch (e) {
               console.error('Failed to fetch CSRF token', e);
            }
         }
         if (csrfToken) {
            config.headers['X-CSRF-Token'] = csrfToken;
         }
      }

      return config;
   },
   (error: AxiosError) => {
      return Promise.reject(error);
   }
);

// Response interceptor - Handle errors and token refresh
apiClient.interceptors.response.use(
   (response: AxiosResponse) => {
      return response;
   },
   async (error: AxiosError) => {
      const authStore = useAuthStore();
      const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean; _csrfRetry?: boolean };

      // Handle 403 Forbidden (likely CSRF failure)
      if (error.response?.status === 403 && !originalRequest._csrfRetry) {
         originalRequest._csrfRetry = true;
         try {
            const response = await axios.get(`${APP_BASE}auth/csrf`, { withCredentials: true });
            csrfToken = response.data.data.csrf_token;
            originalRequest.headers['X-CSRF-Token'] = csrfToken;
            return apiClient(originalRequest);
         } catch (csrfError) {
            return Promise.reject(csrfError);
         }
      }

      // Handle 401 Unauthorized (but not for login requests)
      if (error.response?.status === 401 && !originalRequest._retry && !originalRequest.url?.includes('auth/login')) {
         originalRequest._retry = true;

         try {
            // Attempt token refresh
            await authStore.refreshToken();

            // Retry original request with new token
            if (authStore.accessToken) {
               originalRequest.headers.Authorization = `Bearer ${authStore.accessToken}`;
            }
            return apiClient(originalRequest);
         } catch (refreshError) {
            // Refresh failed - log out user
            toast.error('Session Expired');
            authStore.clearAuth();
            window.location.hash = '#/login';
            return Promise.reject(refreshError);
         }
      }

      // Handle other errors
      if (error.response) {
         const message = (error.response.data as { message?: string })?.message || 'An error occurred';

         switch (error.response.status) {
            case 404:
               toast.error('Resource Not Found');
               break;
            case 422:
               // Validation errors - handled by forms
               break;
            case 429:
               toast.warn('Too Many Requests. Please try again later.');
               break;
            case 500:
               toast.error('Server Error. Please contact support.');
               break;
            default:
               // Only show generic toast if it's not a handled validation error
               if (error.response.status !== 401 && error.response.status !== 403) {
                  toast.error(message);
               }
         }
      } else if (error.request) {
         toast.error('Network Error. Please check your connection.');
      }

      return Promise.reject(error);
   }
);

export default apiClient;
