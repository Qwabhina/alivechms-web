import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import apiClient from '@/api/apiClient';

interface User {
  UserID: number;
  MbrID: number;
  Username: string;
  Email: string;
  MbrFirstName?: string;
  MbrFamilyName?: string;
  Role?: string[];
}

interface LoginCredentials {
  userid: string;
  passkey: string;
  remember?: boolean;
}

interface LoginResponse {
  access_token: string;
  refresh_token: string;
  user: User;
  permissions: string[];
}

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null);
  const accessToken = ref<string | null>(localStorage.getItem('access_token'));
  const permissions = ref<string[]>([]);
  const isLoading = ref(false);

  // Getters
  // Authentication is determined by having a token. User data may be fetched later.
  const isAuthenticated = computed(() => !!accessToken.value);
  const userFullName = computed(() => {
    if (!user.value) return '';
    return `${user.value.MbrFirstName || ''} ${user.value.MbrFamilyName || ''}`.trim() || user.value.Username;
  });

  // Actions
  async function login(credentials: LoginCredentials): Promise<void> {
    isLoading.value = true;
    try {
      // Backend wraps response in { status, message, data: { access_token, user } }
      const response = await apiClient.post<{ status: string; message: string; data: LoginResponse }>('auth/login', credentials);
      const { access_token, user: userData } = response.data.data;

      // Extract permissions from user object if present
      const userPermissions = (userData as any).permissions || (response.data.data as any).permissions || [];

      accessToken.value = access_token;
      user.value = userData;
      permissions.value = userPermissions;

      // Store auth data in localStorage for persistence
      localStorage.setItem('access_token', access_token);
      localStorage.setItem('permissions', JSON.stringify(userPermissions || []));
      localStorage.setItem('user', JSON.stringify(userData));

      if (credentials.remember) {
        localStorage.setItem('remember_user', 'true');
      }
    } catch (error) {
      throw error;
    } finally {
      isLoading.value = false;
    }
  }

  async function logout(): Promise<void> {
    try {
      await apiClient.post('auth/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      clearAuth();
    }
  }

  async function refreshToken(): Promise<void> {
    try {
      const response = await apiClient.post<{ data: { access_token: string } }>('auth/refresh');
      const newToken = response.data.data.access_token;
      accessToken.value = newToken;
      localStorage.setItem('access_token', newToken);
    } catch (error) {
      // Refresh failed - clear auth state
      clearAuth();
      throw error;
    }
  }

  function can(permissionName: string): boolean {
    return permissions.value.includes(permissionName);
  }

  function hasAnyPermission(permissionNames: string[]): boolean {
    return permissionNames.some(perm => permissions.value.includes(perm));
  }

  function hasAllPermissions(permissionNames: string[]): boolean {
    return permissionNames.every(perm => permissions.value.includes(perm));
  }

  // Helper to clear all auth state and storage
  function clearAuth(): void {
    user.value = null;
    accessToken.value = null;
    permissions.value = [];
    localStorage.removeItem('access_token');
    localStorage.removeItem('permissions');
    localStorage.removeItem('user');
    localStorage.removeItem('remember_user');
  }

  // Validate current session with backend and populate user info
  async function validateSession(): Promise<boolean> {
    if (!accessToken.value) {
      return false;
    }

    try {
      // Use the auth/status endpoint to verify token is still valid
      const response = await apiClient.get<{ status: string; data: { authenticated: boolean; user?: any } }>('auth/status');
      if (response.data.data.authenticated) {
        if (response.data.data.user) {
          user.value = response.data.data.user;
          localStorage.setItem('user', JSON.stringify(response.data.data.user));
        }
        return true;
      } else {
        clearAuth();
        return false;
      }
    } catch (error) {
      // Token invalid or expired - clear auth state
      clearAuth();
      return false;
    }
  }

  // Initialize from localStorage on app load
  function initialize(): void {
    // Restore permissions
    const storedPermissions = localStorage.getItem('permissions');
    if (storedPermissions) {
      try {
        permissions.value = JSON.parse(storedPermissions);
      } catch (error) {
        console.error('Failed to parse permissions:', error);
        localStorage.removeItem('permissions');
      }
    }

    // Restore user data
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      try {
        user.value = JSON.parse(storedUser);
      } catch (error) {
        console.error('Failed to parse user:', error);
        localStorage.removeItem('user');
      }
    }
  }

  return {
    // State
    user,
    accessToken,
    permissions,
    isLoading,
    // Getters
    isAuthenticated,
    userFullName,
    // Actions
    login,
    logout,
    refreshToken,
    can,
    hasAnyPermission,
    hasAllPermissions,
    clearAuth,
    validateSession,
    initialize,
  };
});
