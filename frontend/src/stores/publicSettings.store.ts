/**
 * @file stores/publicSettings.store.ts
 * @description Public settings store - accessible without authentication.
 * Provides church branding and theme settings with fallback defaults.
 */

import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { settingsService } from '@/services/settings.service'
import type { PublicSettings } from '@/types/settings'

/** Default/fallback settings when DB is unavailable */
const DEFAULT_SETTINGS: PublicSettings = {
  church_name: 'Alive Church',
  church_motto: 'Faith, Hope, and Love',
  church_website: null,
  church_logo: null,
  currency_symbol: 'GH₵',
  currency_code: 'GHS',
  date_format: 'Y-m-d',
  time_format: 'H:i',
  timezone: 'Africa/Accra',
  language: 'en',
  items_per_page: 10,
}

export const usePublicSettingsStore = defineStore('publicSettings', () => {
  // ── State ───────────────────────────────────────────────────────────────
  const settings = ref<PublicSettings>({ ...DEFAULT_SETTINGS })
  // Theme is managed by the design system via localStorage ('ch-theme').
  // Keep a local reactive copy so components can read/write it through
  // the store API without touching the server-side settings.
  const saved = localStorage.getItem('ch-theme')
  const localTheme = ref<'light' | 'dark' | 'system'>(
    (saved === 'light' || saved === 'dark') ? (saved as 'light' | 'dark') : 'system'
  )
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const isInitialized = ref(false)

  // ── Getters ──────────────────────────────────────────────────────────────
  const churchName = computed(() => settings.value.church_name ?? DEFAULT_SETTINGS.church_name)
  const churchMotto = computed(() => settings.value.church_motto ?? DEFAULT_SETTINGS.church_motto)
  
  /** Church logo URL - handles both full URLs and relative paths */
  const churchLogo = computed(() => {
    const logo = settings.value.church_logo
    if (!logo) return null
    // If already a full URL, return as-is
    if (logo.startsWith('http://') || logo.startsWith('https://')) return logo
    // Otherwise prepend base URL (remove trailing slash to avoid double slashes)
    const baseUrl = import.meta.env.VITE_API_BASE_URL?.replace(/\/$/, '') ?? ''
    return `${baseUrl}/${logo.replace(/^\//, '')}`
  })
  
  const churchWebsite = computed(() => settings.value.church_website)
  
  /** Current theme preference (local only) */
  const theme = computed(() => localTheme.value)

  /** Whether dark mode is currently active (respects system preference) */
  const isDarkMode = computed(() => {
    if (localTheme.value === 'dark') return true
    if (localTheme.value === 'light') return false
    return window.matchMedia('(prefers-color-scheme: dark)').matches
  })
  
  /** Computed branding for login page */
  const branding = computed(() => ({
    name: churchName.value,
    motto: churchMotto.value,
    logo: churchLogo.value,
    website: churchWebsite.value,
  }))

  // ── Actions ─────────────────────────────────────────────────────────────
  async function loadSettings() {
    if (isLoading.value) return
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await settingsService.getPublicSettings()
      // Merge DB settings with defaults (DB wins, defaults fallback)
      settings.value = {
        ...DEFAULT_SETTINGS,
        ...response.data.data,
      }
      isInitialized.value = true
    } catch (err) {
      error.value = 'Failed to load settings'
      // Keep defaults on error
      settings.value = { ...DEFAULT_SETTINGS }
    } finally {
      isLoading.value = false
    }
  }

  /** Force refresh settings */
  async function refresh() {
    isInitialized.value = false
    await loadSettings()
  }
  
  /** Update theme preference locally (persisted to localStorage only) */
  function setTheme(newTheme: 'light' | 'dark' | 'system') {
    localTheme.value = newTheme
    if (newTheme === 'system') {
      localStorage.removeItem('ch-theme')
      // Respect system preference — no explicit dark class persisted.
    } else {
      localStorage.setItem('ch-theme', newTheme)
    }
    // Notify design system directly so it applies the change immediately.
    // Notify any listeners (design-system or components) in this window.
    window.dispatchEvent(new CustomEvent('ch-theme-change', { detail: newTheme }))
  }

  return {
    // State
    settings,
    isLoading,
    error,
    isInitialized,
    // Getters
    churchName,
    churchMotto,
    churchLogo,
    churchWebsite,
    theme,
    isDarkMode,
    branding,
    // Actions
    loadSettings,
    refresh,
    setTheme,
  }
})
