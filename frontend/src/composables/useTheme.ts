/**
 * @file composables/useTheme.ts
 * @description Theme management composable - applies theme to document
 */

import { onMounted, computed } from 'vue'
import { usePublicSettingsStore } from '@/stores/publicSettings.store'
import { useTheme as useDesignTheme } from '@/design-system/composables/useTheme'

/** Initialize theme wiring between store and design-system */
export function useTheme() {
  const publicSettings = usePublicSettingsStore()
  const ds = useDesignTheme()

  // Ensure initial design-system state matches stored preference
  onMounted(() => {
    const t = publicSettings.theme
    if (t === 'dark') ds.applyDarkMode(true)
    else if (t === 'light') ds.applyDarkMode(false)
    // 'system' leaves the design-system to follow OS setting (default behavior)
  })

  // Listen for local theme change events (dispatched by the store)
  onMounted(() => {
    const handler = (e: Event) => {
      const detail = (e as CustomEvent).detail as 'light' | 'dark' | 'system'
      if (detail === 'dark') ds.applyDarkMode(true)
      else if (detail === 'light') ds.applyDarkMode(false)
      else {
        // system: choose based on OS preference
        ds.applyDarkMode(window.matchMedia('(prefers-color-scheme: dark)').matches)
      }
    }
    window.addEventListener('ch-theme-change', handler as EventListener)
    // also clean-up on unmount if needed (not strictly necessary for app-level composable)
  })

  return {
    isDarkMode: ds.isDarkMode,
    theme: computed(() => publicSettings.theme),
    setTheme: publicSettings.setTheme,
  }
}
