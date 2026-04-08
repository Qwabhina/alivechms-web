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
import { ChSidebar } from '@/design-system'
import type { NavItem } from '@/design-system/components/navigation/ChSidebar.vue'
import {
  LayoutDashboard,
  Users,
  Wallet,
  Settings,
  LogOut,
} from 'lucide-vue-next'

const auth = useAuthStore()
const ui = useUiStore()
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
  if (auth.hasPermission('finances.view') || auth.hasPermission('contributions.view')) {
    items.push({
      label: 'Finance',
      // icon: Wallet,
      children: (auth.hasPermission('finances.view')
        ? [{ label: 'Contributions', to: '/finance/contributions', icon: Wallet },
        { label: 'Expenses', to: '/finance/expenses' },
        { label: 'Financial Reports', to: '/finance/reports' },
        { label: 'Pledges', to: '/finance/pledges' }]
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
</script>

<template>
  <div class="app-layout" :class="{ 'app-layout--collapsed': ui.sidebarCollapsed }">

    <!-- ── Sidebar ────────────────────────────────────────────────────── -->
    <ChSidebar :nav-items="navItems" :current-route="currentRoute" :collapsed="ui.sidebarCollapsed"
      :mobile-open="ui.sidebarMobileOpen" brand-name="AliveChMS" @navigate="handleNavigate"
      @collapse-toggle="ui.toggleSidebar()" @mobile-close="ui.closeMobileSidebar()">
      <template #footer>
        <button
          class="ch-sidebar-item ch-sidebar-item--logout"
          :class="{ 'ch-sidebar-item--collapsed': ui.sidebarCollapsed }"
          :data-tooltip="ui.sidebarCollapsed ? 'Logout' : undefined"
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

/* ── Mobile ───────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .app-main {
    margin-left: 0 !important;
  }
}
</style>
