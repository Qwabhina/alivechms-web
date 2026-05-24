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
  Users,
  Wallet,
  CalendarDays,
  Settings,
  LogOut,
  UsersRound,
  PiggyBank,
  Receipt,
  Gauge,
  UserCog,
  Shield,
  Home,
  Building2,
  ListTodo,
  FolderTree,
} from '@lucide/vue'

const auth = useAuthStore()
const ui = useUiStore()
const router = useRouter()
const route = useRoute()

/* ── Nav items ─────────────────────────────────────────────────────────── */
const navItems = computed<NavItem[]>(() => {
  const items: NavItem[] = []

  if (auth.hasPermission('reports.view')) {
    items.push({ label: 'Dashboard', to: '/dashboard', icon: Gauge })
  }

  // Members Section
  if (auth.hasPermission('members.view')) {
    items.push({
      label: 'Members & People',
      icon: UsersRound,
      children: [
        { label: 'Members', to: '/members', icon: Users },
        { label: 'Member Directory', to: '/members/directory', icon: Contact },
        { label: 'Families', to: '/families/directory', icon: Home },
      ],
    })
  }

  // Users Section (Members with system access)
  if (auth.hasPermission('reports.view') || auth.hasPermission('roles.view')) {
    const userChildren = []
    if (auth.hasPermission('reports.view')) {
      userChildren.push({ label: 'System Users', to: '/users', icon: UserCog })
    }
    if (auth.hasPermission('reports.view')) {
      userChildren.push({ label: 'Roles & Permissions', to: '/users/roles', icon: Shield })
    }
    items.push({
      label: 'User Management',
      icon: UserCog,
      children: userChildren,
    })
  }

  // Groups Section
  if (auth.hasPermission('groups.view')) {
    items.push({
      label: 'Groups',
      icon: UsersRound,
      children: [
        { label: 'Groups', to: '/groups', icon: UsersRound },
        { label: 'Group Directory', to: '/groups/directory', icon: Building2 },
      ],
    })
  }

  // Events Section
  if (auth.hasPermission('events.view')) {
    items.push({
      label: 'Events',
      icon: CalendarDays,
      children: [
        { label: 'Events', to: '/events', icon: CalendarDays },
        { label: 'Event Directory', to: '/events/directory', icon: ListTodo },
      ],
    })
  }

  // Finance Section with children
  if (auth.hasPermission('finances.view') || auth.hasPermission('contributions.view')) {
    const financeChildren = []
    if (auth.hasPermission('finances.view')) {
      financeChildren.push({ label: 'Contributions', to: '/finance/contributions', icon: PiggyBank })
    }
    if (auth.hasPermission('finances.view')) {
      financeChildren.push({ label: 'Expenses', to: '/finance/expenses', icon: Receipt })
    }
    items.push({
      label: 'Finance',
      icon: Wallet,
      children: financeChildren,
    })
  }

  // Settings Section
  if (auth.hasPermission('settings.view')) {
    items.push({
      label: 'Settings',
      icon: Settings,
      children: [
        { label: 'General Settings', to: '/settings', icon: Settings },
        { label: 'Branches', to: '/settings/branches', icon: Building2 },
        { label: 'Lookups', to: '/settings/lookups', icon: FolderTree },
      ],
    })
  }

  return items
})

/* ── Logout ────────────────────────────────────────────────────────────── */
async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

import { confirmModal, confirm } from '@/design-system/composables/useConfirm'

/* ── Sidebar navigation handler ────────────────────────────────────────── */
async function handleNavigate(to: string) {
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

/* ── Topbar user object ─────────────────────────────────────────────────── */
import { normalizeProfileImage } from '@/utils/image'
import { Contact } from '@lucide/vue'

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
    <ChSidebar
      :nav-items="navItems"
      :current-route="currentRoute"
      :collapsed="ui.sidebarCollapsed"
      :mobile-open="ui.sidebarMobileOpen"
      brand-name="AliveChMS"
      @navigate="handleNavigate"
      @collapse-toggle="ui.toggleSidebar()"
      @mobile-close="ui.closeMobileSidebar()"
    >
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

      <!-- ChTopbar — the #title slot renders a lean icon + label only.
           Full ChPageHeader (with subtitle, actions, etc.) lives in each view. -->
      <ChTopbar
        :user="topbarUser"
        :sidebar-collapsed="ui.sidebarCollapsed"
        @menu-click="ui.toggleSidebar()"
      >
        <template #title>
          <div v-if="route.meta.title" class="topbar-page-title">
            <component
              :is="route.meta.icon"
              v-if="route.meta.icon"
              :size="18"
              class="topbar-page-title__icon"
              aria-hidden="true"
            />
            <span class="topbar-page-title__text">{{ route.meta.title }}</span>
          </div>
        </template>
      </ChTopbar>

      <!-- Page content — each view owns its own ChPageHeader -->
      <main class="app-content">
        <RouterView />
      </main>

      <!-- Shared confirm modal instance -->
      <ChModal
        v-model:open="confirmModal.isOpen.value"
        :title="confirmModal.data.value?.title ?? 'Confirm'"
        size="sm"
      >
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

.app-content {
  flex: 1;
  padding: var(--ch-space-6);
}

/* ── Topbar title — icon + text, no ChPageHeader chrome ──────────────── */
.topbar-page-title {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.topbar-page-title__icon {
  color: var(--ch-color-text-muted);
  flex-shrink: 0;
}

.topbar-page-title__text {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ── Logout button ───────────────────────────────────────────────────── */
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