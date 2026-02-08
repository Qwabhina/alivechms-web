<template>
  <aside class="app-sidebar" :class="{ 'is-collapsed': isCollapsed }">
    <nav class="sidebar-nav">
      <router-link
        v-for="item in navigationItems"
        :key="item.name"
        :to="item.path"
        class="nav-item"
        active-class="is-active"
      >
        <i :class="item.icon"></i>
        <span class="nav-label">{{ item.label }}</span>
      </router-link>
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/authStore';

const authStore = useAuthStore();
const isCollapsed = ref(false);

interface NavItem {
  name: string;
  label: string;
  path: string;
  icon: string;
  permission?: string;
}

const allNavigationItems: NavItem[] = [
  { name: 'dashboard', label: 'Dashboard', path: '/', icon: 'pi pi-home', permission: 'dashboard.view' },
  { name: 'members', label: 'Members', path: '/members', icon: 'pi pi-users', permission: 'members.view' },
  { name: 'families', label: 'Families', path: '/families', icon: 'pi pi-sitemap', permission: 'families.view' },
  { name: 'groups', label: 'Groups', path: '/groups', icon: 'pi pi-users', permission: 'groups.view' },
  { name: 'contributions', label: 'Contributions', path: '/contributions', icon: 'pi pi-money-bill', permission: 'contributions.view' },
  { name: 'expenses', label: 'Expenses', path: '/expenses', icon: 'pi pi-wallet', permission: 'expenses.view' },
  { name: 'budgets', label: 'Budgets', path: '/budgets', icon: 'pi pi-chart-line', permission: 'budgets.view' },
  { name: 'events', label: 'Events', path: '/events', icon: 'pi pi-calendar', permission: 'events.view' },
  { name: 'attendance', label: 'Attendance', path: '/attendance', icon: 'pi pi-check-circle', permission: 'attendance.view' },
  { name: 'documents', label: 'Documents', path: '/documents', icon: 'pi pi-file', permission: 'documents.view' },
  { name: 'settings', label: 'Settings', path: '/settings', icon: 'pi pi-cog', permission: 'settings.view' },
];

const navigationItems = allNavigationItems.filter(item => 
  !item.permission || authStore.can(item.permission)
);

function handleToggle() {
  isCollapsed.value = !isCollapsed.value;
}

onMounted(() => {
  document.addEventListener('toggle-sidebar', handleToggle);
});

onUnmounted(() => {
  document.removeEventListener('toggle-sidebar', handleToggle);
});
</script>

<style scoped>
.app-sidebar {
  width: 260px;
  background: var(--color-surface);
  border-right: 1px solid var(--color-border);
  transition: width var(--transition-normal);
  overflow-x: hidden;
}

.app-sidebar.is-collapsed {
  width: 64px;
}

.sidebar-nav {
  padding: var(--space-md);
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.nav-item {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-md);
  color: var(--color-text);
  text-decoration: none;
  transition: all var(--transition-fast);
  white-space: nowrap;
}

.nav-item:hover {
  background: var(--color-bg);
  color: var(--color-primary);
}

.nav-item.is-active {
  background: var(--color-primary);
  color: white;
}

.nav-item i {
  font-size: 1.25rem;
  min-width: 24px;
}

.is-collapsed .nav-label {
  display: none;
}

@media (max-width: 768px) {
  .app-sidebar {
    position: fixed;
    left: 0;
    top: 64px;
    bottom: 0;
    z-index: 100;
    transform: translateX(-100%);
  }

  .app-sidebar:not(.is-collapsed) {
    transform: translateX(0);
    box-shadow: var(--shadow-xl);
  }
}
</style>
