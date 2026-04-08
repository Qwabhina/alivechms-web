/**
 * @file stores/ui.store.ts
 * @description UI state: sidebar, loading overlays, mobile breakpoints.
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const sidebarCollapsed = ref(false)
  const sidebarMobileOpen = ref(false)
  const globalLoading = ref(false)

  const isMobile = ref(window.innerWidth < 768)

  function toggleSidebar() {
    if (isMobile.value) {
      sidebarMobileOpen.value = !sidebarMobileOpen.value
    } else {
      sidebarCollapsed.value = !sidebarCollapsed.value
    }
  }

  function closeMobileSidebar() {
    sidebarMobileOpen.value = false
  }

  function setGlobalLoading(loading: boolean) {
    globalLoading.value = loading
  }

  // Track window resize
  if (typeof window !== 'undefined') {
    window.addEventListener('resize', () => {
      isMobile.value = window.innerWidth < 768
      if (!isMobile.value) {
        sidebarMobileOpen.value = false
      }
    })
  }

  return {
    sidebarCollapsed,
    sidebarMobileOpen,
    globalLoading,
    isMobile,
    toggleSidebar,
    closeMobileSidebar,
    setGlobalLoading,
  }
})
