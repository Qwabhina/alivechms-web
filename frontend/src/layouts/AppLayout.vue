<script setup lang="ts">
/**
 * AppLayout — Authenticated application shell.
 * Uses the design system ChSidebar, ChSidebarItem, and ChTopbar
 * components exactly per their documented APIs.
 */
import { computed } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useUiStore } from '@/stores/ui.store'
import { ChSidebar, type NavItem } from '@/design-system'
import { useTheme } from '@/composables/useTheme'
import {
  LayoutDashboard,
  Users,
  UserCircle,
  Wallet,
  CalendarDays,
  Settings,
  LogOut,
  Sun,
  Moon,
  Monitor,
} from 'lucide-vue-next'

const auth = useAuthStore()
const ui = useUiStore()
const { theme: dsTheme, setTheme: dsSetTheme } = useTheme()
const router = useRouter()
const route = useRoute()

/* ── Nav items ─────────────────────────────────────────────────────────── */
// Filtered based on user permissions
const navItems = computed<NavItem[]>(() => {
  const items: NavItem[] = []

  if (auth.hasPermission('reports.view')) {
    items.push({ label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard })
  }
  if (auth.hasPermission('members.view')) {
    items.push({ label: 'Members', to: '/members', icon: Users })
  }
  if (auth.hasPermission('members.view')) {
    items.push({ label: 'Families', to: '/families', icon: UserCircle })
  }
  if (auth.hasPermission('groups.view')) {
    items.push({ label: 'Groups', to: '/groups', icon: Users })
  }
  if (auth.hasPermission('events.view')) {
    items.push({ label: 'Events', to: '/events', icon: CalendarDays })
  }
  if (auth.hasPermission('finances.view') || auth.hasPermission('contributions.view')) {
    items.push({
      label: 'Finance',
      icon: Wallet,
      children: (auth.hasPermission('finances.view')
        ? [{ label: 'Contributions', to: '/finance/contributions' }]
        : []),
    })
  }
  if (auth.hasPermission('settings.view')) {
    items.push({ label: 'Settings', to: '/settings', icon: Settings })
  }

  return items
})

/* ── Logout ────────────────────────────────────────────────────────────── */
async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

/* ── Sidebar navigation handler ────────────────────────────────────────── */
function handleNavigate(to: string) {
  router.push(to)
  ui.closeMobileSidebar()
}

/* ── Topbar user object (ChTopbar shape) ───────────────────────────────── */
const topbarUser = computed(() =>
  auth.user
    ? {
        name: auth.fullName || auth.user.Username,
        role: auth.user.MembershipStatus || undefined,
        avatar: auth.user.MbrProfilePicture ?? undefined,
      }
    : undefined,
)

/* ── Current route for ChSidebarItem active state ──────────────────────── */
const currentRoute = computed(() => route.path)

/* ── Theme toggle ──────────────────────────────────────────────────────── */
const themeIcon = computed(() => {
  if (dsTheme.value === 'dark') return Moon
  if (dsTheme.value === 'light') return Sun
  return Monitor
})

const themeLabel = computed(() => {
  if (dsTheme.value === 'dark') return 'Dark'
  if (dsTheme.value === 'light') return 'Light'
  return 'System'
})

function cycleTheme() {
  const themes: ('light' | 'dark' | 'system')[] = ['light', 'dark', 'system']
  const currentTheme = dsTheme.value ?? 'system'
  const currentIndex = themes.indexOf(currentTheme)
  const nextTheme = themes[(currentIndex + 1) % themes.length] as 'light' | 'dark' | 'system'
  dsSetTheme(nextTheme)
}
</script>

<template>
  <div class="app-layout" :class="{ 'app-layout--collapsed': ui.sidebarCollapsed }">

    <!-- ── Sidebar ────────────────────────────────────────────────────── -->
    <ChSidebar :nav-items="navItems" :current-route="currentRoute" :collapsed="ui.sidebarCollapsed"
      :mobile-open="ui.sidebarMobileOpen" brand-name="AliveChMS" @navigate="handleNavigate"
      @collapse-toggle="ui.toggleSidebar()" @mobile-close="ui.closeMobileSidebar()">
      <template #footer>
        <div class="ch-sidebar-footer-theme">
          <button
            class="ch-sidebar-item ch-sidebar-item--theme"
            :class="{ 'ch-sidebar-item--collapsed': ui.sidebarCollapsed }"
            :data-tooltip="ui.sidebarCollapsed ? themeLabel : undefined"
            :aria-label="'Theme toggle'"
            :title="ui.sidebarCollapsed ? themeLabel : undefined"
            type="button"
            @click="cycleTheme"
          >
            <span class="ch-sidebar-item__icon">
              <component :is="themeIcon" :size="18" />
            </span>
            <span class="ch-sidebar-item__label">{{ themeLabel }}</span>
          </button>
        </div>

        <button
          class="ch-sidebar-item ch-sidebar-item--logout"
          :class="{ 'ch-sidebar-item--collapsed': ui.sidebarCollapsed }"
          :data-tooltip="ui.sidebarCollapsed ? 'Logout' : undefined"
          :aria-label="'Logout'"
          :title="ui.sidebarCollapsed ? 'Logout' : undefined"
          type="button"
          @click="handleLogout"
        >
          <span class="ch-sidebar-item__icon">
            <LogOut :size="18" />
          </span>
          <span class="ch-sidebar-item__label">Logout</span>
        </button>
      </template>
    </ChSidebar>

    <!-- ── Main content area ───────────────────────────────────────────── -->
    <div class="app-main">

      <!-- ChTopbar — design system top navigation bar -->
      <ChTopbar
        :user="topbarUser"
        :sidebar-collapsed="ui.sidebarCollapsed"
        @menu-click="ui.toggleSidebar()"
      >
        <template #title>
          <h1 class="ch-topbar__title">{{ route.meta?.title as string || '' }}</h1>
        </template>
      </ChTopbar>

      <!-- Page content -->
      <main class="app-content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
/* ── Shell layout ─────────────────────────────────────────────────────── */
.app-layout {
  display: flex;
  min-height: 100vh;
}

/* Main area sits to the right of the fixed sidebar */
.app-main {
  flex: 1;
  margin-left: 240px; /* matches .ch-sidebar width */
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin-left var(--ch-duration-slow) var(--ch-ease-out);
  background-color: var(--ch-color-bg-subtle);
}

.app-layout--collapsed .app-main {
  margin-left: 64px; /* matches .ch-sidebar--collapsed width */
}

/* Page content padding */
.app-content {
  flex: 1;
  padding: var(--ch-space-6);
}

/* ── Logout button — styled as a danger sidebar item ─────────────────── */
.ch-sidebar-item--logout {
  color: var(--ch-color-danger) !important;
  width: 100%;
}

.ch-sidebar-item--logout:hover {
  background-color: var(--ch-color-danger-bg) !important;
  color: var(--ch-color-danger) !important;
}

/* ── Theme toggle button ──────────────────────────────────────────────── */
.theme-toggle {
  display: inline-flex;
  align-items: center;
  gap: var(--ch-space-1_5);
  padding: var(--ch-space-2) var(--ch-space-3);
  border: 1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-md);
  background: transparent;
  color: var(--ch-color-text);
  cursor: pointer;
  font-size: var(--ch-text-sm);
  transition: all var(--ch-duration-fast) var(--ch-ease-out);
}

.theme-toggle:hover {
  background-color: var(--ch-color-surface);
  border-color: var(--ch-color-border-strong);
}

.theme-label {
  font-weight: var(--ch-font-medium);
}

@media (max-width: 640px) {
  .theme-label {
    display: none;
  }
}

/* Sidebar theme item — match other sidebar items */
.ch-sidebar-item--theme {
  width: 100%;
  color: var(--ch-color-text) !important;
}

.ch-sidebar-item--theme:hover {
  background-color: var(--ch-color-bg-subtle);
  color: var(--ch-color-text) !important;
}

.ch-sidebar-footer-theme {
  padding-bottom: var(--ch-space-3);
}

/* ── Mobile ───────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .app-main {
    margin-left: 0 !important;
  }
}
</style>
