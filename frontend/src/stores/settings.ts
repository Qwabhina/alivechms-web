import { defineStore } from 'pinia'
import api, { resolveUrl } from '@/services/api'

interface Settings {
   church_name: string
   church_motto: string
   church_website: string
   church_logo: string
   currency_symbol: string
   currency_code: string
   date_format: string
   time_format: string
   timezone: string
   language: string
}

interface SettingsState {
   settings: Settings
   loading: boolean
   loaded: boolean
}

export const useSettingsStore = defineStore('settings', {
   state: (): SettingsState => ({
      settings: {
         church_name: 'AliveChMS Church',
         church_motto: 'Faith, Hope, and Love',
         church_website: '',
         church_logo: '',
         currency_symbol: 'GH₵',
         currency_code: 'GHS',
         date_format: 'Y-m-d',
         time_format: 'H:i',
         timezone: 'Africa/Accra',
         language: 'en',
      },
      loading: false,
      loaded: false,
   }),

   getters: {
      churchLogoUrl: (state) => {
         if (!state.settings.church_logo) return '/assets/img/logo.png'
         return resolveUrl(state.settings.church_logo)
      },
   },

   actions: {
      async fetchSettings() {
         if (this.loaded) return

         this.loading = true
         try {
            const response = await api.get('public/settings')
            const data = response.data.data

            // Map backend response to our settings object
            // The backend returns an array or object depending on current state
            if (data) {
               this.settings = { ...this.settings, ...data }
               console.log(this.settings);
            }

            this.loaded = true
         } catch (error) {
            console.error('Failed to load settings:', error)
         } finally {
            this.loading = false
         }
      },
   },
})
