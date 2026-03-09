import { ref } from 'vue';
import { defineStore } from 'pinia';

export const useLayoutStore = defineStore('layout', () => {
  const isSidebarCollapsed = ref(false);

  function toggleSidebar() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }

  function setSidebarCollapsed(value: boolean) {
    isSidebarCollapsed.value = value;
  }

  return {
    isSidebarCollapsed,
    toggleSidebar,
    setSidebarCollapsed
  };
});
