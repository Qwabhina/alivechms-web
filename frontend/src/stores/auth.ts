import { defineStore } from 'pinia'
import api from '@/services/api'

interface User {
  user_id: string | number
  username: string
  role?: string
}

interface AuthState {
  user: User | null
  token: string | null
  loading: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: localStorage.getItem('access_token'),
    loading: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    currencySymbol: () => 'GH₵', // This could eventually come from settingsStore
  },

  actions: {
    async login(userid: string, passkey: string, remember: boolean = false) {
      this.loading = true
      try {
        const response = await api.post('auth/login', {
          userid,
          passkey,
          remember,
        })

        const { access_token, user } = response.data.data
        this.token = access_token
        this.user = user
        localStorage.setItem('access_token', access_token)
        
        return response.data
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await api.post('auth/logout')
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('access_token')
      }
    },

    async checkAuth() {
      if (!this.token) return false
      
      try {
        const response = await api.get('auth/status')
        if (response.data.data.authenticated) {
          this.user = {
            user_id: response.data.data.user_id,
            username: response.data.data.username,
          }
          return true
        }
        return false
      } catch (error) {
        this.token = null
        localStorage.removeItem('access_token')
        return false
      }
    },
  },
})
