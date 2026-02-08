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
}

interface LoginCredentials {
  username: string;
  password: string;
  rememberMe?: boolean;
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
  const accessToken = ref<string | null>(null);
  const permissions = ref<string[]>([]);
  const isLoading = ref(false);

  // Getters
  const isAuthenticated = computed(() => !!accessToken.value && !!user.value);
  const userFullName = computed(() => {
    if (!user.value) return '';
    return `${user.value.MbrFirstName || ''} ${user.value.MbrFamilyName || ''}`.trim() || user.value.Username;
  });

  // Actions
  async function login(credentials: LoginCredentials): Promise<void> {
    isLoading.value = true;
    try {
      const response = await apiClient.post<LoginResponse>('/auth/login', credentials);
      const { access_token, user: userData, permissions: userPermissions } = response.data;

      accessToken.value = access_token;
      user.value = userData;
      permissions.value = userPermissions;

      // Store in localStorage if "Remember Me" is checked
      if (credentials.rememberMe) {
        localStorage.setItem('remember_user', JSON.stringify(userData));
      }
    } catch (error) {
      throw error;
    } finally {
      isLoading.value = false;
    }
  }

  async function logout(): Promise<void> {
    try {
      await apiClient.post('/auth/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      // Clear state regardless of API response
      user.value = null;
      accessToken.value = null;
      permissions.value = [];
      localStorage.removeItem('remember_user');
    }
  }

  async function refreshToken(): Promise<void> {
    try {
      const response = await apiClient.post<{ access_token: string }>('/auth/refresh');
      accessToken.value = response.data.access_token;
    } catch (error) {
      // Refresh failed - clear auth state
      user.value = null;
      accessToken.value = null;
      permissions.value = [];
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

  // Initialize from localStorage on app load
  function initialize(): void {
    const rememberedUser = localStorage.getItem('remember_user');
    if (rememberedUser) {
      try {
        user.value = JSON.parse(rememberedUser);
      } catch (error) {
        console.error('Failed to parse remembered user:', error);
        localStorage.removeItem('remember_user');
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
    initialize,
  };
});
