<script setup lang="ts">
/**
 * @component ChSidebar
 * @path /frontend/src/design-system/components/navigation/ChSidebar.vue
 * @description The primary navigation panel for the church management system.
 *
 * ─── Layout ───────────────────────────────────────────────────────────────────
 * The sidebar is divided into four vertical zones:
 *
 *   ┌─────────────────────┐
 *   │  Header (logo/name) │  ← `header` slot or default logo area
 *   ├─────────────────────┤
 *   │  Nav sections       │  ← scrollable, built from `sections` prop
 *   │  (with group labels)│
 *   ├─────────────────────┤
 *   │  Footer nav items   │  ← pinned at bottom (settings, help, logout)
 *   ├─────────────────────┤
 *   │  User profile area  │  ← `user` slot (avatar, name, role)
 *   └─────────────────────┘
 *
 * ─── Collapse behavior ────────────────────────────────────────────────────────
 * The sidebar can be collapsed to icon-only mode (64px wide) or fully
 * expanded (240px wide). The collapse state is managed internally but
 * exposed via `v-model:collapsed` so the parent layout can react
 * (e.g. to adjust the main content's left margin).
 *
 * ─── Mobile behavior ──────────────────────────────────────────────────────────
 * On mobile, the sidebar is hidden by default and slides in as a drawer
 * over the content (with a dimmed overlay behind it). The `open` prop
 * controls the mobile drawer state from the parent (toggled by a
 * hamburger button in ChTopbar).
 *
 * ─── Route awareness ──────────────────────────────────────────────────────────
 * The sidebar expects to be used alongside Vue Router. It accepts a
 * `currentRoute` prop (typically `route.path` from `useRoute()`) to
 * determine which item is active.
 *
 * ─── Nav structure ────────────────────────────────────────────────────────────
 * Nav items are organized into named sections (groups with headings).
 * This maps directly to the backend's domain modules:
 *
 * @example
 * const sections: NavSection[] = [
 *   {
 *     label: 'People',
 *     items: [
 *       { label: 'Members',    to: '/members',    icon: UsersIcon,  badge: 3 },
 *       { label: 'Families',   to: '/families',   icon: HomeIcon },
 *       { label: 'Volunteers', to: '/volunteers', icon: HandIcon },
 *       { label: 'Visitors',   to: '/visitors',   icon: UserPlusIcon },
 *     ]
 *   },
 *   {
 *     label: 'Finance',
 *     items: [
 *       { label: 'Contributions', to: '/contributions', icon: BanknoteIcon },
 *       { label: 'Expenses',      to: '/expenses',      icon: ReceiptIcon },
 *       { label: 'Pledges',       to: '/pledges',       icon: HandshakeIcon },
 *       { label: 'Budgets',       to: '/budgets',       icon: PieChartIcon },
 *     ]
 *   },
 * ]
 */

// import { computed, ref, watch } from 'vue'
import { computed } from 'vue'

import ChSidebarItem, { type NavItem } from './ChSidebarItem.vue'

// Re-export NavItem type for use by consuming code
// (NavItem is imported from ChSidebarItem but not automatically re-exported)
export type { NavItem }

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * A labeled group of nav items.
 * The `label` is shown as a small section heading above the items.
 * Set `label: ''` or omit it to render items without a heading.
 */
export interface NavSection {
  /** Section heading (e.g. "People", "Finance", "Operations") */
  label?: string
  /** The nav items belonging to this section */
  items:  NavItem[]
}

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /** Main navigation sections — the core content of the sidebar */
  sections?:     NavSection[]

  /**
   * Footer nav items — pinned to the bottom of the sidebar.
   * Used for: Settings, Help, Logout.
   */
  footerItems?:  NavItem[]

  /** Current route path from Vue Router (`route.path`) */
  currentRoute?: string

  /**
   * Whether the sidebar is in collapsed (icon-only) mode.
   * Supports v-model: `v-model:collapsed="isCollapsed"`
   */
  collapsed?:    boolean

  /**
   * Controls the mobile drawer open state.
   * True = drawer slides in over content.
   * Supports v-model: `v-model:open="drawerOpen"`
   */
  open?:         boolean

  /** Church/organization name shown in the header area */
  churchName?:   string

  /** Church logo URL — displayed in the header */
  logoSrc?:      string
}

const props = withDefaults(defineProps<Props>(), {
  sections:     () => [],
  footerItems:  () => [],
  currentRoute: '/',
  collapsed:    false,
  open:         false,
})

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
  /** v-model:collapsed — emitted when the user clicks the collapse toggle */
  'update:collapsed': [value: boolean]
  /** v-model:open — emitted when the mobile drawer should close */
  'update:open':      [value: boolean]
  /**
   * Emitted when a nav item is clicked.
   * The parent should use this to call `router.push(to)`.
   * This keeps the sidebar decoupled from Vue Router.
   */
  'navigate':         [to: string]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

/** Whether any section has a label — affects layout spacing */
const hasSectionLabels = computed(() =>
  props.sections.some(s => !!s.label)
)

// ─── Handlers ─────────────────────────────────────────────────────────────────

/** Toggles the sidebar between expanded and collapsed */
function toggleCollapse() {
  emit('update:collapsed', !props.collapsed)
}

/**
 * Handles navigation events bubbled up from ChSidebarItem.
 * Emits navigate to the parent, then closes the mobile drawer.
 */
function handleNavigate(to: string) {
  emit('navigate', to)
  // Close mobile drawer after navigation
  if (props.open) {
    emit('update:open', false)
  }
}

/** Closes the mobile overlay when the user clicks the backdrop */
function closeDrawer() {
  emit('update:open', false)
}
</script>

<template>
  <!--
    ─── Mobile Overlay ──────────────────────────────────────────────────────
    A dark semi-transparent backdrop behind the sidebar on mobile.
    Only renders when the drawer is open.
    Clicking it closes the drawer.
  -->
  <Transition name="ch-overlay">
    <div
      v-if="open"
      class="ch-sidebar-overlay"
      aria-hidden="true"
      @click="closeDrawer"
    ></div>
  </Transition>

  <!--
    ─── Sidebar Container ────────────────────────────────────────────────────
    The main sidebar element.

    CSS classes:
    - `ch-sidebar--collapsed` → narrows to icon-only width (64px)
    - `ch-sidebar--open`      → slides in from left on mobile

    `aria-label` gives the nav landmark a name for screen readers.
    `<nav>` is the correct semantic element for a navigation region.
  -->
  <nav
    :class="[
      'ch-sidebar',
      {
        'ch-sidebar--collapsed': collapsed,
        'ch-sidebar--open':      open,
      }
    ]"
    aria-label="Main navigation"
  >

    <!--
      ─── Header ───────────────────────────────────────────────────────────
      Shows the logo and church name. Has a named `header` slot so the
      parent can provide a custom header (e.g. with a multi-branch selector).
      Falls back to the default logo + name display.
    -->
    <div class="ch-sidebar__header">
      <slot name="header">
        <!-- Default header: logo image or initials + church name -->
        <div class="ch-sidebar__brand">
          <div class="ch-sidebar__logo">
            <img
              v-if="logoSrc"
              :src="logoSrc"
              :alt="churchName || 'Church logo'"
              class="ch-sidebar__logo-img"
            />
            <!-- Fallback: first letter of church name in a colored square -->
            <span v-else class="ch-sidebar__logo-fallback">
              {{ churchName?.charAt(0) ?? 'A' }}
            </span>
          </div>

          <!--
            Church name — hidden in collapsed mode.
            `aria-hidden` when collapsed since it's not visible.
          -->
          <span
            v-if="!collapsed"
            class="ch-sidebar__church-name"
          >
            {{ churchName ?? 'AliveChms' }}
          </span>
        </div>
      </slot>

      <!--
        Collapse toggle button — visible on desktop only (CSS hides on mobile).
        Switches between "collapse" (←) and "expand" (→) arrows.
        `aria-label` updates to communicate current action to screen readers.
      -->
      <button
        class="ch-sidebar__collapse-btn"
        :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        type="button"
        @click="toggleCollapse"
      >
        <svg
          width="16" height="16" viewBox="0 0 16 16" fill="none"
          :style="{ transform: collapsed ? 'rotate(180deg)' : 'none' }"
          style="transition: transform 0.3s ease"
        >
          <!-- Double left-arrow chevron icon -->
          <path d="M10 4L6 8L10 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M6 4L2 8L6 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>

    <!-- ─── Divider below header ─────────────────────────────────────────── -->
    <div class="ch-sidebar__rule" aria-hidden="true"></div>

    <!--
      ─── Main Nav (scrollable) ────────────────────────────────────────────
      Contains all sections and their items.
      `overflow-y: auto` allows scrolling when there are many items.
    -->
    <div class="ch-sidebar__scroll">
      <ul
        class="ch-sidebar__nav"
        :class="{ 'ch-sidebar__nav--has-labels': hasSectionLabels }"
        role="list"
      >
        <!--
          Loop through sections.
          Each section optionally has a label heading above its items.
        -->
        <li
          v-for="(section, sIndex) in sections"
          :key="sIndex"
          class="ch-sidebar__section"
        >
          <!--
            Section label (e.g. "PEOPLE", "FINANCE").
            Hidden in collapsed mode — too wide to show.
            `aria-hidden` because the label is visual grouping only;
            the items themselves are accessible individually.
          -->
          <span
            v-if="section.label && !collapsed"
            class="ch-sidebar__section-label"
            aria-hidden="true"
          >
            {{ section.label }}
          </span>

          <!--
            Items list for this section.
            Each ChSidebarItem handles its own active state, children, badges.
          -->
          <ul class="ch-sidebar__section-items" role="list">
            <ChSidebarItem
              v-for="item in section.items"
              :key="item.label"
              :item="item"
              :current-route="currentRoute"
              :collapsed="collapsed"
              @navigate="handleNavigate"
            />
          </ul>
        </li>
      </ul>
    </div>

    <!-- Pushes footer to the bottom of the sidebar -->
    <div class="ch-sidebar__spacer" aria-hidden="true"></div>

    <!--
      ─── Footer Nav ───────────────────────────────────────────────────────
      Pinned at the bottom. Used for Settings, Help & Support, Logout.
      Separated from the main nav by a divider.
    -->
    <div v-if="footerItems.length" class="ch-sidebar__footer">
      <div class="ch-sidebar__rule" aria-hidden="true"></div>
      <ul class="ch-sidebar__section-items" role="list">
        <ChSidebarItem
          v-for="item in footerItems"
          :key="item.label"
          :item="item"
          :current-route="currentRoute"
          :collapsed="collapsed"
          @navigate="handleNavigate"
        />
      </ul>
    </div>

    <!--
      ─── User Profile Area ────────────────────────────────────────────────
      A slot for displaying the currently logged-in user.
      Typical content: ChAvatar + name + role badge + logout button.
    -->
    <div v-if="$slots.user" class="ch-sidebar__user">
      <div class="ch-sidebar__rule" aria-hidden="true"></div>
      <div class="ch-sidebar__user-content">
        <slot name="user" :collapsed="collapsed"></slot>
      </div>
    </div>

  </nav>
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
  flex-direction:column;
  overflow:      hidden;            /* clip content during width animation */

  background-color: var(--ch-color-surface); /* stark solid white */
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
  text-overflow:ellipsis;
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

/* When section labels are present, adjust spacing for better visual hierarchy */
.ch-sidebar__nav--has-labels {
  padding-top: var(--ch-space-1);
}

/* ─── Sections ────────────────────────────────────────────────────────────── */
.ch-sidebar__section {
  display:       flex;
  flex-direction:column;
  gap:           var(--ch-space-0_5);
}

/* Add top spacing between sections (not before the first one) */
.ch-sidebar__section + .ch-sidebar__section {
  margin-top: var(--ch-space-4);
}

.ch-sidebar__section-label {
  padding:     var(--ch-space-1) var(--ch-space-3);
  font-size:   0.625rem;               /* 10px — very small uppercase label */
  font-weight: var(--ch-font-semibold);
  letter-spacing: var(--ch-tracking-wider);
  text-transform: uppercase;
  color:       var(--ch-color-text-subtle);
}

.ch-sidebar__section-items {
  list-style:    none;
  margin:        0;
  padding:       0;
  display:       flex;
  flex-direction:column;
  gap:           var(--ch-space-0_5);
}

/* ─── Spacer (pushes footer down) ────────────────────────────────────────── */
.ch-sidebar__spacer {
  flex: 1;
}

/* ─── Footer ──────────────────────────────────────────────────────────────── */
.ch-sidebar__footer {
  flex-shrink: 0;
  padding:     var(--ch-space-2);
}

/* ─── User area ───────────────────────────────────────────────────────────── */
.ch-sidebar__user {
  flex-shrink: 0;
}

.ch-sidebar__user-content {
  padding: var(--ch-space-3) var(--ch-space-2);
}
</style>
