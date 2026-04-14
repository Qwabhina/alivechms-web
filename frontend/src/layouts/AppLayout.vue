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
import { ChSidebar, ChSidebarItem, ChModal, ChButton, type NavItem } from '@/design-system'
import {
  LayoutDashboard,
  Users,
  UserCircle,
  Wallet,
  CalendarDays,
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

  // Logout is intentionally not part of the main nav list; it's rendered
  // separately in the sidebar footer for visual separation.

  return items
})

/* ── Logout ────────────────────────────────────────────────────────────── */
async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

// Use shared confirm modal
import { confirmModal, confirm } from '@/design-system/composables/useConfirm'

/* ── Sidebar navigation handler ────────────────────────────────────────── */
async function handleNavigate(to: string) {
  // Intercept the Logout pseudo-route to run the logout flow
  if (to === '/logout') {
    ui.closeMobileSidebar()
    const ok = await confirm({
      title: 'Confirm Logout',
      message: 'Are you sure you want to sign out of your account?',
      confirmLabel: 'Logout',
      cancelLabel: 'Cancel',
    })
    if (ok) await handleLogout()
    return
  }

  router.push(to)
  ui.closeMobileSidebar()
}

/* ── Topbar user object (ChTopbar shape) ───────────────────────────────── */
import { normalizeProfileImage } from '@/utils/image'

const topbarUser = computed(() =>
  auth.user
    ? {
        name: auth.fullName || auth.user.Username,
        role: auth.user.MembershipStatus || undefined,
        avatar: normalizeProfileImage(auth.user.MbrProfilePicture) ?? undefined,
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
        <div class="ch-sidebar-footer-theme"></div>

        <ChSidebarItem
          :item="{ label: 'Logout', to: '/logout', icon: LogOut }"
          :current-route="currentRoute"
          :collapsed="ui.sidebarCollapsed"
          class="ch-sidebar-item--logout"
          @navigate="handleNavigate"
        />
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
      <!-- Shared confirm modal instance -->
      <ChModal v-model:open="confirmModal.isOpen.value" :title="confirmModal.data.value?.title ?? 'Confirm'" size="sm">
        <p>{{ confirmModal.data.value?.message }}</p>
        <template #footer>
          <ChButton variant="ghost" @click="confirmModal.close(false)">
            {{ confirmModal.data.value?.cancelLabel ?? 'Cancel' }}
          </ChButton>
          <ChButton variant="danger" @click="confirmModal.close(true)">
            {{ confirmModal.data.value?.confirmLabel ?? 'Confirm' }}
          </ChButton>
        </template>
      </ChModal>
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
