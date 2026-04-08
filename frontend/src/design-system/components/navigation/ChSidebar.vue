<script setup lang="ts">
/**
 * @component ChSidebar
 * @path /frontend/src/design-system/components/navigation/ChSidebar.vue
 * @description Main sidebar navigation component with collapsible layout.
 *
 * The ChSidebar provides a complete navigation sidebar with:
 * - Header with logo/brand and collapse toggle
 * - Scrollable navigation area with nav items
 * - Footer for user actions
 * - Mobile-responsive behavior with overlay
 * - Collapsible to icon-only mode
 *
 * ─── Props ────────────────────────────────────────────────────────────────────
 * Pass navItems as an array of NavItem objects. The component handles
 * rendering ChSidebarItem components for each nav item.
 *
 * ─── Mobile Behavior ──────────────────────────────────────────────────────────
 * On mobile (< 768px), the sidebar slides in from the left as an overlay.
 * Clicking outside or navigating closes the mobile drawer.
 *
 * ─── Collapse Mode ────────────────────────────────────────────────────────────
 * When collapsed, the sidebar shrinks to 64px width (icon-only). Labels,
 * badges, and group children are hidden. Tooltips appear on hover.
 *
 * @example Basic usage
 * <ChSidebar
 *   :nav-items="navigationItems"
 *   :current-route="route.path"
 *   :collapsed="sidebarCollapsed"
 *   :mobile-open="sidebarMobileOpen"
 *   @navigate="handleNavigate"
 *   @collapse-toggle="toggleSidebar"
 *   @mobile-close="closeMobileSidebar"
 * >
 *   <template #brand>
 *     <img src="/logo.png" alt="Company" />
 *   </template>
 *   <template #footer>
 *     <button @click="logout">Logout</button>
 *   </template>
 * </ChSidebar>
 */

import { computed } from 'vue'
import type { Component } from 'vue'
import ChSidebarItem from './ChSidebarItem.vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface NavItem {
  /** Display label — shown in full sidebar, used as tooltip in collapsed mode */
  label:    string

  /**
   * Route path this item links to.
   * For group headers (items with `children`), this is optional —
   * clicking the group just expands/collapses it, not navigates.
   */
  to?:      string

  /**
   * Icon component — a Vue component (e.g. from lucide-vue-next).
   * Usage: `import { UsersIcon } from 'lucide-vue-next'`
   */
  icon?:    Component

  /**
   * Optional badge value — shown as a count bubble on the right.
   * Common uses: unread message counts, pending approval counts.
   * Pass a number; if 0 or undefined, no badge is shown.
   */
  badge?:   number

  /**
   * Nested child navigation items.
   * If provided, this item renders as a collapsible group.
   * Groups cannot have a `to` route themselves.
   */
  children?: NavItem[]

  /** When true, this item is permanently disabled — no hover, no click */
  disabled?: boolean
}

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Array of navigation items to render */
  navItems: NavItem[]

  /** Current route path for active state detection */
  currentRoute: string

  /** Whether the sidebar is in collapsed (icon-only) mode */
  collapsed?: boolean

  /** Whether the mobile sidebar overlay is open */
  mobileOpen?: boolean

  /** Custom logo image URL */
  logo?: string

  /** Church/company name to display in header */
  brandName?: string
}

const props = withDefaults(defineProps<Props>(), {
  collapsed:  false,
  mobileOpen: false,
  brandName:  'App',
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  /** Fired when a nav item is clicked — parent handles navigation */
  navigate: [to: string]

  /** Fired when the collapse toggle button is clicked */
  'collapse-toggle': []

  /** Fired when the mobile overlay should close (user clicked outside) */
  'mobile-close': []
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

/** Generates initials from brand name for logo fallback */
const brandInitials = computed(() => {
  const name = props.brandName?.trim()
  if (!name) return 'A'

  const parts = name.split(/\s+/)
  const firstPart = parts[0]

  if (!firstPart) return 'A'

  if (parts.length === 1) {
    return firstPart[0]?.toUpperCase() ?? 'A'
  }

  const lastPart = parts[parts.length - 1]
  const firstChar = firstPart[0] ?? ''
  const lastChar = lastPart?.[0] ?? ''

  return (firstChar + lastChar).toUpperCase() || 'A'
})

// ─── Handlers ─────────────────────────────────────────────────────────────────

/** Handles navigation from child ChSidebarItem components */
function handleNavigate(to: string) {
  emit('navigate', to)
  // Auto-close mobile sidebar after navigation
  if (props.mobileOpen) {
    emit('mobile-close')
  }
}

/** Handles collapse toggle button click */
function handleCollapseToggle() {
  emit('collapse-toggle')
}

/** Handles mobile overlay click (close sidebar) */
function handleMobileClose() {
  emit('mobile-close')
}
</script>

<template>
  <!--
    ─── Mobile Overlay ─────────────────────────────────────────────────────────
    Only renders when mobile sidebar is open. Clicking it closes the sidebar.
    Uses Vue Transition for fade animation.
  -->
  <Transition name="ch-overlay">
    <div
      v-if="mobileOpen"
      class="ch-sidebar-overlay"
      @click="handleMobileClose"
    />
  </Transition>

  <!-- ─── Sidebar Container ──────────────────────────────────────────────────── -->
  <aside
    class="ch-sidebar"
    :class="{
      'ch-sidebar--collapsed': collapsed,
      'ch-sidebar--open': mobileOpen,
    }"
    role="navigation"
    aria-label="Main navigation"
  >
    <!-- ─── Header ──────────────────────────────────────────────────────────── -->
    <div class="ch-sidebar__header">
      <!-- Brand / Logo area -->
      <div class="ch-sidebar__brand">
        <div class="ch-sidebar__logo">
          <!-- Custom logo slot or fallback -->
          <slot name="brand">
            <img
              v-if="logo"
              :src="logo"
              :alt="brandName"
              class="ch-sidebar__logo-img"
            />
            <!-- Initials fallback when no logo provided -->
            <span v-else class="ch-sidebar__logo-fallback">
              {{ brandInitials }}
            </span>
          </slot>
        </div>

        <!-- Brand name — hidden in collapsed mode -->
        <span class="ch-sidebar__church-name">{{ brandName }}</span>
      </div>

      <!-- Collapse toggle button — hidden on mobile -->
      <button
        class="ch-sidebar__collapse-btn"
        :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        type="button"
        @click="handleCollapseToggle"
      >
        <svg
          width="14"
          height="14"
          viewBox="0 0 14 14"
          fill="none"
          :style="{
            transform: collapsed ? 'rotate(180deg)' : 'none',
            transition: 'transform var(--ch-duration-normal) var(--ch-ease-out)'
          }"
        >
          <path
            d="M9 2L4 7l5 5"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>
    </div>

    <!-- Visual separator -->
    <hr class="ch-sidebar__rule" />

    <!-- ─── Scrollable Navigation Area ──────────────────────────────────────── -->
    <div class="ch-sidebar__scroll">
      <ul class="ch-sidebar__nav">
        <ChSidebarItem
          v-for="item in navItems"
          :key="item.label"
          :item="item"
          :current-route="currentRoute"
          :collapsed="collapsed"
          @navigate="handleNavigate"
        />
      </ul>
    </div>

    <!-- ─── Footer ──────────────────────────────────────────────────────────── -->
    <!-- Custom footer content (e.g. logout button, user info) -->
    <div v-if="$slots.footer" class="ch-sidebar__footer">
      <slot name="footer" />
    </div>
  </aside>
</template>

<style scoped>
/* ─── Sidebar Container ───────────────────────────────────────────────────── */
.ch-sidebar {
  /* Fixed position so it stays in place while content scrolls */
  position:   fixed;
  top:        0;
  left:       0;
  bottom:     0;
  z-index:    var(--ch-z-sticky);  /* above page content, below modals */

  /* Width animates on collapse */
  width:      240px;
  transition: width var(--ch-duration-slow) var(--ch-ease-out);

  display:       flex;
  flex-direction: column;
  overflow:      hidden;            /* clip content during width animation */

  background-color: var(--ch-color-surface);
  border-right:     1px solid var(--ch-color-border-strong);
}

/* Collapsed: shrink to icon-only width */
.ch-sidebar--collapsed {
  width: 64px;
}

/* ─── Mobile behavior ─────────────────────────────────────────────────────── */
/*
 * On mobile (< 768px), the sidebar is off-screen by default.
 * `translateX(-100%)` moves it completely to the left (off-screen).
 * Adding `ch-sidebar--open` slides it in with a translate transition.
 */
@media (max-width: 768px) {
  .ch-sidebar {
    transform:  translateX(-100%);
    transition:
      transform var(--ch-duration-slow) var(--ch-ease-out),
      width     var(--ch-duration-slow) var(--ch-ease-out);
    width:     240px !important; /* always full-width on mobile — no collapse */
    z-index:   var(--ch-z-modal); /* above the overlay on mobile */
    box-shadow: var(--ch-shadow-xl);
  }

  .ch-sidebar--open {
    transform: translateX(0);
  }

  /* Hide collapse toggle on mobile — not relevant */
  .ch-sidebar__collapse-btn {
    display: none;
  }
}

/* ─── Mobile Overlay ──────────────────────────────────────────────────────── */
.ch-sidebar-overlay {
  position:   fixed;
  inset:      0;                        /* top: 0, right: 0, bottom: 0, left: 0 */
  background: rgb(0 0 0 / 0.4);        /* 40% black scrim */
  z-index:    var(--ch-z-overlay);     /* below sidebar, above content */
  backdrop-filter: blur(2px);          /* subtle blur of page content behind overlay */
}

/* Overlay fade transition (Vue <Transition> names) */
.ch-overlay-enter-active,
.ch-overlay-leave-active {
  transition: opacity var(--ch-duration-slow) var(--ch-ease-out);
}
.ch-overlay-enter-from,
.ch-overlay-leave-to {
  opacity: 0;
}

/* ─── Header ──────────────────────────────────────────────────────────────── */
.ch-sidebar__header {
  display:     flex;
  align-items: center;
  justify-content: space-between;
  padding:     var(--ch-space-4) var(--ch-space-3);
  flex-shrink: 0;   /* never compress the header */
  min-height:  64px;
}

/* ─── Brand / Logo area ───────────────────────────────────────────────────── */
.ch-sidebar__brand {
  display:     flex;
  align-items: center;
  gap:         var(--ch-space-2_5);
  overflow:    hidden; /* clip during collapse animation */
  min-width:   0;
}

.ch-sidebar__logo {
  flex-shrink: 0;
  width:       32px;
  height:      32px;
  border-radius: var(--ch-radius-lg);
  overflow:    hidden;
}

.ch-sidebar__logo-img {
  width:  100%;
  height: 100%;
  object-fit: cover;
}

/* Fallback colored square with a letter when no logo is provided */
.ch-sidebar__logo-fallback {
  display:          flex;
  align-items:      center;
  justify-content:  center;
  width:            100%;
  height:           100%;
  background-color: var(--ch-color-primary);
  color:            var(--ch-color-primary-fg);
  font-size:        var(--ch-text-base);
  font-weight:      var(--ch-font-bold);
  font-family:      var(--ch-font-display);
}

.ch-sidebar__church-name {
  font-size:    var(--ch-text-sm);
  font-weight:  var(--ch-font-semibold);
  color:        var(--ch-color-text);
  overflow:     hidden;
  text-overflow: ellipsis;
  white-space:  nowrap;
  font-family:  var(--ch-font-display);
}

/* ─── Collapse Toggle ─────────────────────────────────────────────────────── */
.ch-sidebar__collapse-btn {
  flex-shrink: 0;
  display:     flex;
  align-items: center;
  justify-content: center;
  width:       28px;
  height:      28px;
  border-radius: var(--ch-radius-md);
  border:      1px solid var(--ch-color-border);
  background:  transparent;
  color:       var(--ch-color-text-muted);
  cursor:      pointer;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    color            var(--ch-duration-fast) var(--ch-ease-out),
    border-color     var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-sidebar__collapse-btn:hover {
  background-color: var(--ch-color-bg-muted);
  color:            var(--ch-color-text);
  border-color:     var(--ch-color-border-strong);
}

/* In collapsed mode, the toggle button gets centered */
.ch-sidebar--collapsed .ch-sidebar__header {
  justify-content: center;
  padding: var(--ch-space-4) var(--ch-space-2);
}

.ch-sidebar--collapsed .ch-sidebar__brand {
  display: none; /* hide brand entirely in collapsed mode */
}

/* ─── Divider Rule ────────────────────────────────────────────────────────── */
.ch-sidebar__rule {
  height:     1px;
  margin:     0 var(--ch-space-3);
  background: var(--ch-color-border);
  flex-shrink: 0;
}

/* ─── Scrollable Nav Area ─────────────────────────────────────────────────── */
.ch-sidebar__scroll {
  flex:       1;             /* fill remaining vertical space */
  overflow-y: auto;          /* scroll if content overflows */
  overflow-x: hidden;        /* never scroll horizontally */
  padding:    var(--ch-space-2) var(--ch-space-2);

  /* Thin scrollbar in the nav */
  scrollbar-width: thin;
  scrollbar-color: var(--ch-color-border) transparent;
}

.ch-sidebar__nav {
  list-style: none;
  margin:     0;
  padding:   0;
  display:   flex;
  flex-direction: column;
  gap:       var(--ch-space-1);
}

/* ─── Footer ──────────────────────────────────────────────────────────────── */
.ch-sidebar__footer {
  flex-shrink: 0;
  padding: var(--ch-space-2);
}
</style>
