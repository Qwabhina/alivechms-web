import axios from 'axios';
import type { AxiosInstance, AxiosError, InternalAxiosRequestConfig, AxiosResponse } from 'axios';
import { useAuthStore } from '@/stores/authStore';
import { useNotification } from '@/composables/useNotification';

// Create axios instance
const apiClient: AxiosInstance = axios.create({
   baseURL: '/api',
   timeout: 30000,
   headers: {
      'Content-Type': 'application/json',
   },
   withCredentials: true, // For httpOnly cookies
});

// Request interceptor - Add auth token
apiClient.interceptors.request.use(
   (config: InternalAxiosRequestConfig) => {
      const authStore = useAuthStore();

      if (authStore.accessToken) {
         config.headers.Authorization = `Bearer ${authStore.accessToken}`;
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
      const notification = useNotification();
      const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

      // Handle 401 Unauthorized
      if (error.response?.status === 401 && !originalRequest._retry) {
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
            notification.show('Session Expired', 'error');
            authStore.logout();
            window.location.href = '/login';
            return Promise.reject(refreshError);
         }
      }

      // Handle other errors
      if (error.response) {
         const message = (error.response.data as { message?: string })?.message || 'An error occurred';

         switch (error.response.status) {
            case 403:
               notification.show('Access Denied', 'error');
               break;
            case 404:
               notification.show('Resource Not Found', 'error');
               break;
            case 422:
               // Validation errors - handled by forms
               break;
            case 429:
               notification.show('Too Many Requests. Please try again later.', 'warning');
               break;
            case 500:
               notification.show('Server Error. Please contact support.', 'error');
               break;
            default:
               notification.show(message, 'error');
         }
      } else if (error.request) {
         notification.show('Network Error. Please check your connection.', 'error');
      }

      return Promise.reject(error);
   }
);

export default apiClient;
