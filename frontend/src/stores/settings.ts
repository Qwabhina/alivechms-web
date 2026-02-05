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
         // Using a reliable placeholder if no logo is uploaded
         if (!state.settings.church_logo) return 'https://placehold.co/200x200?text=Church+Logo&font=roboto'
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

            if (data) {
               this.settings = { ...this.settings, ...data }
            }

            this.loaded = true
         } catch (error) {
            console.error('Failed to load settings:', error)
         } finally {
            this.loading = false
         }
      },

      async fetchAllSettings() {
         try {
            const response = await api.get('settings/all')
            const data = response.data.data
            return Array.isArray(data) ? data : []
         } catch (error) {
            console.error('Failed to fetch all settings:', error)
            return []
         }
      },

      async updateSettings(settings: { key: string; value: any; type?: string; category?: string }[]) {
         try {
            const response = await api.post('settings/update', { settings })
            // Refresh local public settings if any changed
            this.loaded = false
            await this.fetchSettings()
            return response.data
         } catch (error) {
            console.error('Failed to update settings:', error)
            throw error
         }
      },

      async uploadLogo(file: File) {
         try {
            const formData = new FormData()
            formData.append('logo', file)

            const response = await api.post('settings/upload-logo', formData, {
               headers: {
                  'Content-Type': 'multipart/form-data'
               }
            })

            // Update local state
            if (response.data.data?.path) {
               this.settings.church_logo = response.data.data.path
            }

            return response.data
         } catch (error) {
            console.error('Failed to upload logo:', error)
            throw error
         }
      },

      async initializeDefaults() {
         try {
            await api.post('settings/initialize')
            // Refresh
            this.loaded = false
            await this.fetchSettings()
         } catch (error) {
            console.error('Failed to initialize settings:', error)
            throw error
         }
      },

      async getSetting(setting_key: string) {
         try {
            const response = await this.fetchAllSettings()
            return response.find((setting: any) => setting.key === setting_key)?.value
         } catch (error) {
            console.error('Failed to get setting:', error)
            throw error
         }
      }
   },
})
