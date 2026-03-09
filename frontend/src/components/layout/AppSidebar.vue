<template>
 <aside class="app-sidebar" :class="{ 'is-collapsed': layoutStore.isSidebarCollapsed }">
    <!-- Sidebar Container -->
    <div class="flex flex-col h-full">
      <!-- Header Area: Logo & Name -->
      <div class="sidebar-header">
        <div class="logo-container">
          <i class="pi pi-shield logo-icon"></i>
          <span class="app-name" v-if="!layoutStore.isSidebarCollapsed">AliveChMS</span>
        </div>
        <button v-if="!layoutStore.isSidebarCollapsed" class="mobile-close-btn lg:hidden"
          @click="layoutStore.setSidebarCollapsed(true)">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <!-- Scrollable Navigation Area -->
      <div class="sidebar-nav-container">
        <ul class="nav-list">
          <template v-for="(sectionItems, sectionName) in groupedNavigation" :key="sectionName">
            <!-- Section Header (Only if not collapsed) -->
            <li class="nav-section-wrapper" v-if="!layoutStore.isSidebarCollapsed">
              <div v-ripple
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-slidedown', leaveToClass: 'hidden', leaveActiveClass: 'animate-slideup' }"
                class="section-header">
                <span class="section-title">{{ sectionName }}</span>
                <i class="pi pi-chevron-down section-toggle"></i>
              </div>
              <!-- Section Content -->
              <ul class="sub-nav-list">
                <li v-for="item in sectionItems" :key="item.name">
                  <!-- Single Item or Parent with Children -->
                  <router-link v-if="!item.children" :to="item.path || '#'" v-ripple class="nav-item-link"
                    active-class="is-active">
                    <i :class="item.icon" class="nav-icon"></i>
                    <span class="nav-label">{{ item.label }}</span>
                  </router-link>
                  <template v-else>
                    <div v-ripple
                      v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-slidedown', leaveToClass: 'hidden', leaveActiveClass: 'animate-slideup' }"
                      class="nav-item-link group-trigger">
                      <i :class="item.icon" class="nav-icon"></i>
                      <span class="nav-label">{{ item.label }}</span>
                      <i class="pi pi-chevron-down arrow-icon"></i>
                    </div>
                    <!-- Sub-items Group -->
                    <ul class="nested-sub-nav hidden">
                      <li v-for="child in item.children" :key="child.name">
                        <router-link :to="child.path || '#'" v-ripple class="nav-item-link child-link"
                          active-class="is-active">
                          <i :class="child.icon" class="child-icon"></i>
                          <span class="nav-label">{{ child.label }}</span>
                        </router-link>
                      </li>
                    </ul>
                  </template>
                </li>
              </ul>
            </li>

            <!-- Collapsed State: Simple List of Icons -->
            <template v-else>
              <li v-for="item in sectionItems" :key="item.name + '-collapsed'">
                <router-link :to="item.path || '#'" v-ripple class="nav-item-link is-collapsed-link"
                  active-class="is-active" :title="item.label">
                  <i :class="item.icon" class="nav-icon"></i>
                </router-link>
              </li>
            </template>
          </template>
        </ul>
      </div>

      <!-- Footer: User Profile -->
      <div class="sidebar-footer">
        <hr class="footer-divider" />
        <div v-ripple class="user-card" @click="handleLogout">
          <div class="user-avatar-placeholder">
            {{ userInitials }}
          </div>
          <div class="user-info" v-if="!layoutStore.isSidebarCollapsed">
            <p class="user-display-name">{{ authStore.userFullName }}</p>
            <p class="user-role-label">{{ userRole }}</p>
          </div>
          <i v-if="!layoutStore.isSidebarCollapsed" class="pi pi-sign-out logout-mini-icon"></i>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { useLayoutStore } from '@/stores/layoutStore';

const authStore = useAuthStore();
const layoutStore = useLayoutStore();
const router = useRouter();

interface NavItem {
  name: string;
  label: string;
  path?: string;
  icon: string;
  permission?: string;
  section?: string;
  children?: NavItem[];
}

const userInitials = computed(() => {
  const name = authStore.userFullName || authStore.user?.Username || 'U';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const userRole = computed(() => {
  return authStore.user?.Role || 'Member';
});

async function handleLogout() {
  await authStore.logout();
  router.push('/login');
}

// Helper to check permissions recursively
function filterItems(items: NavItem[]): NavItem[] {
  return items.reduce((acc: NavItem[], item) => {
    if (item.permission && !authStore.can(item.permission)) {
      return acc;
    }

    if (item.children) {
      const filteredChildren = filterItems(item.children);
      if (filteredChildren.length > 0) {
        acc.push({ ...item, children: filteredChildren });
        return acc;
      }
      if (!item.path) return acc;
    }

    acc.push(item);
    return acc;
  }, []);
}

const allNavigationItems = ref<NavItem[]>([
  { name: 'dashboard', label: 'Dashboard', path: '/', icon: 'pi pi-chart-bar', section: 'CORE' },

  // People
  {
    name: 'people',
    label: 'People',
    icon: 'pi pi-users',
    section: 'PEOPLE',
    permission: 'members.view',
    children: [
      { name: 'members', label: 'Members', path: '/members', icon: 'pi pi-user', permission: 'members.view' },
      { name: 'families', label: 'Families', path: '/families', icon: 'pi pi-home', permission: 'families.view' },
      { name: 'groups', label: 'Groups', path: '/groups', icon: 'pi pi-user-plus', permission: 'groups.view' },
      { name: 'volunteers', label: 'Volunteers', path: '/volunteers', icon: 'pi pi-thumbs-up', permission: 'volunteers.view' },
      { name: 'milestones', label: 'Member Milestones', path: '/milestones', icon: 'pi pi-star', permission: 'milestones.view' },
    ]
  },

  // Finance
  {
    name: 'finance',
    label: 'Finance',
    icon: 'pi pi-money-bill',
    section: 'FINANCE',
    permission: 'contributions.view',
    children: [
      { name: 'contributions', label: 'Contributions', path: '/contributions', icon: 'pi pi-dollar', permission: 'contributions.view' },
      { name: 'pledges', label: 'Pledges', path: '/pledges', icon: 'pi pi-bookmark', permission: 'pledges.view' },
      { name: 'expenses', label: 'Expenses', path: '/expenses', icon: 'pi pi-receipt', permission: 'expenses.view' },
      { name: 'budgets', label: 'Budgets', path: '/budgets', icon: 'pi pi-chart-pie', permission: 'budgets.view' },
      { name: 'financial-reports', label: 'Financial Reports', path: '/financial-reports', icon: 'pi pi-chart-line', permission: 'reports.view' },
    ]
  },

  // Events
  {
    name: 'events-group',
    label: 'Events & Activities',
    icon: 'pi pi-calendar',
    section: 'ENGAGEMENT',
    permission: 'events.view',
    children: [
      { name: 'events', label: 'Events', path: '/events', icon: 'pi pi-calendar-plus', permission: 'events.view' },
      { name: 'attendance', label: 'Attendance', path: '/attendance', icon: 'pi pi-check-square', permission: 'attendance.view' },
    ]
  },

  // Communication
  {
    name: 'communication',
    label: 'Communication',
    icon: 'pi pi-envelope',
    section: 'ENGAGEMENT',
    children: [
      { name: 'messages', label: 'Messages', path: '/messages', icon: 'pi pi-comments', permission: 'messages.view' },
      { name: 'announcements', label: 'Announcements', path: '/announcements', icon: 'pi pi-megaphone', permission: 'announcements.view' },
    ]
  },

  // Assets
  {
    name: 'assets',
    label: 'Assets',
    icon: 'pi pi-box',
    section: 'SYSTEM',
    permission: 'assets.view',
    children: [
      { name: 'all-assets', label: 'All Assets', path: '/assets', icon: 'pi pi-inbox', permission: 'assets.view' },
      { name: 'asset-categories', label: 'Asset Categories', path: '/asset-categories', icon: 'pi pi-tags', permission: 'assets.manage' },
    ]
  },

  // Users & Roles
  {
    name: 'users-roles',
    label: 'Users & Roles',
    icon: 'pi pi-shield',
    section: 'SYSTEM',
    children: [
      { name: 'users', label: 'Users', path: '/users', icon: 'pi pi-user-plus', permission: 'users.view' },
      { name: 'roles', label: 'Roles & Permissions', path: '/roles', icon: 'pi pi-lock-open', permission: 'roles.view' },
    ]
  },

  // Settings
  {
    name: 'settings',
    label: 'Settings',
    icon: 'pi pi-cog',
    section: 'SYSTEM',
    permission: 'settings.view',
    children: [
      { name: 'general-settings', label: 'General', path: '/settings', icon: 'pi pi-sliders-h', permission: 'settings.view' },
      { name: 'branches', label: 'Branches', path: '/branches', icon: 'pi pi-building', permission: 'branches.view' },
      { name: 'fiscal-years', label: 'Fiscal Years', path: '/fiscal-years', icon: 'pi pi-calendar', permission: 'finance.params' },
      { name: 'audit-log', label: 'Audit Log', path: '/audit-log', icon: 'pi pi-history', permission: 'audit.view' },
    ]
  },
]);

const groupedNavigation = computed(() => {
  const filtered = filterItems(allNavigationItems.value);
  return filtered.reduce((acc: Record<string, NavItem[]>, item) => {
    const section = item.section || 'OTHER';
    if (!acc[section]) acc[section] = [];
    acc[section].push(item);
    return acc;
  }, {});
});
</script>

<style scoped>
.app-sidebar {
  width: 260px;
  background: #000250;
    color: #f1f5f9;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    height: 100vh;
    position: sticky;
    top: 0;
    z-index: 50;
}

.app-sidebar.is-collapsed {
  width: 72px;
  }
  
  /* Header */
  .sidebar-header {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  .logo-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  
  .logo-icon {
    font-size: 2rem;
    color: #e5a100;
  }
  
  .app-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #f1f5f9;
    letter-spacing: -0.025em;
  }
  
  /* Nav Container */
  .sidebar-nav-container {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: none;
  }
  
  .sidebar-nav-container::-webkit-scrollbar {
    display: none;
  }
  
  .nav-list {
    list-style: none;
    padding: 1rem;
    margin: 0;
  }
  
  .nav-section-wrapper {
    margin-bottom: 1rem;
  }
  
  .section-header {
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    border-radius: var(--radius-md);
    margin-bottom: 0.25rem;
  }
  
  .section-header:hover {
    background: rgba(255, 255, 255, 0.05);
  }
  
  .section-title {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }
  
  .section-toggle {
    font-size: 0.7rem;
}

.sub-nav-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item-link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-md);
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  transition: all 0.2s ease;
    cursor: pointer;
    gap: 0.75rem;
  }
  
  .nav-item-link:hover {
    background: rgba(255, 255, 255, 0.08);
    color: white;
  }
  
  .nav-item-link.is-active {
    background: rgba(229, 161, 0, 0.15);
    color: #e5a100;
    font-weight: 600;
  }
  
  .group-trigger {
    justify-content: space-between;
  }
  
  .nav-icon {
    font-size: 1.1rem;
    min-width: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .arrow-icon {
    font-size: 0.7rem;
    opacity: 0.5;
  }
  
  .nested-sub-nav {
    list-style: none;
    padding: 0.125rem 0 0.25rem 1.25rem;
    margin: 0;
  }
  
  .child-link {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }
  
  .child-icon {
    font-size: 0.9rem !important;
    opacity: 0.6;
  }
  
  /* Footer */
  .sidebar-footer {
    margin-top: auto;
    padding: 1rem;
  }
  
  .footer-divider {
    border: none;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 1rem;
  }
  
  .user-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .user-card:hover {
    background: rgba(255, 255, 255, 0.05);
  }
  
  .user-avatar-placeholder {
    width: 32px;
    height: 32px;
    background: #e5a100;
    color: #000250;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    flex-shrink: 0;
  }
  
  .user-info {
    flex: 1;
    min-width: 0;
  }
  
  .user-display-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    margin: 0;
  white-space: nowrap;
  overflow: hidden;
    text-overflow: ellipsis;
}

.user-role-label {
  font-size: 0.7rem;
  color: rgba(255, 255, 255, 0.4);
  margin: 0;
  text-transform: capitalize;
}

.logout-mini-icon {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.3);
}

/* Collapsed Overrides */
.is-collapsed-link {
  justify-content: center !important;
  padding: 1rem 0 !important;
  gap: 0 !important;
}

.is-collapsed .nav-icon {
  font-size: 1.4rem;
}

.is-collapsed .user-card {
  justify-content: center;
  padding: 0.5rem 0;
}

@media (max-width: 768px) {
  .app-sidebar {
    position: fixed;
    left: 0;
    top: 64px;
    bottom: 0;
    transform: translateX(-100%);
  }

  .app-sidebar:not(.is-collapsed) {
    transform: translateX(0);
    box-shadow: 20px 0 50px rgba(0, 0, 0, 0.3);
    }
    }
    
    /* Animations */
    .hidden {
      display: none;
    }
    
    .animate-slidedown {
      animation: slidedown 0.3s ease-out;
    }
    
    .animate-slideup {
      animation: slideup 0.3s ease-in;
    }
    
    @keyframes slidedown {
      from {
        max-height: 0;
        opacity: 0;
      }
    
      to {
        max-height: 1000px;
        opacity: 1;
      }
    }
@keyframes slideup {
  from {
    max-height: 1000px;
    opacity: 1;
  }

  to {
    max-height: 0;
    opacity: 0;
  }
}
</style>
