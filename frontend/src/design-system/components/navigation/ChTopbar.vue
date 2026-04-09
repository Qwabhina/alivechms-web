<script setup lang="ts">
/**
 * @component ChTopbar
 * @path /frontend/src/design-system/components/navigation/ChTopbar.vue
 * @description The horizontal top navigation bar for the app shell.
 *
 * The topbar sits at the top of the main content area (to the right of
 * the sidebar) and provides:
 *   - Mobile menu toggle (hamburger button → opens ChSidebar drawer)
 *   - Page title or breadcrumb display
 *   - Global search trigger
 *   - Notification bell with unread count badge
 *   - User avatar / quick actions
 *
 * ─── Slot-first design ───────────────────────────────────────────────────────
 * The topbar is intentionally slot-heavy. Different pages will need different
 * content in the title area (just a heading, a breadcrumb trail, a tab bar
 * below the title, etc.). Slots make this flexible without needing many props.
 *
 * ─── Positioning ─────────────────────────────────────────────────────────────
 * The topbar uses `position: sticky` (not `fixed`) so it scrolls with
 * the page layout but sticks once the top of its scroll container is reached.
 * The parent layout is responsible for giving the main area the right
 * left margin to clear the sidebar.
 *
 * @example Minimal usage
 * <ChTopbar
 *   page-title="Members"
 *   :notifications="5"
 *   @menu-click="drawerOpen = true"
 * >
 *   <template #actions>
 *     <ChButton @click="addMember">Add Member</ChButton>
 *   </template>
 * </ChTopbar>
 *
 * @example With breadcrumb
 * <ChTopbar @menu-click="drawerOpen = true">
 *   <template #title>
 *     <ChBreadcrumb :items="[{ label: 'Members', to: '/members' }, { label: 'John Addo' }]" />
 *   </template>
 * </ChTopbar>
 */

import { computed } from 'vue'

/** Shape of the user object shown in the topbar right area */
export interface TopbarUser {
  name: string
  role?: string
  avatar?: string // URL to profile photo
}

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /**
   * Simple string title for the current page.
   * Use this OR the `title` slot — not both.
   * If the `title` slot is provided, this prop is ignored.
   */
  pageTitle?: string

  /**
   * Number of unread notifications.
   * Shows a red badge on the bell icon when > 0.
   * Pass 0 or undefined to hide the badge.
   */
  notifications?: number

  /** The logged-in user — drives the avatar in the top-right */
  user?: TopbarUser

  /**
   * Whether the sidebar is currently collapsed.
   * When true, the topbar stretches all the way to the left edge on desktop.
   * (The parent layout adjusts the margin; this prop is informational only.)
   */
  sidebarCollapsed?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  notifications: 0,
})

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
  /** Fired when the hamburger menu is clicked — parent should open ChSidebar */
  'menu-click': []
  /** Fired when the search icon/button is clicked */
  'search-click': []
  /** Fired when the notifications bell is clicked */
  'notifications-click': []
  /** Fired when the user avatar area is clicked */
  'user-click': []
}>()

// ─── Computed Helpers ─────────────────────────────────────────────────────────

/**
 * Caps notification badge at 99 to prevent overflow.
 * Shows "99+" for counts above 99.
 */
const notificationLabel = computed(() => {
  const n = props.notifications ?? 0
  return n > 99 ? '99+' : String(n)
})

const hasNotifications = computed(() => (props.notifications ?? 0) > 0)

/**
 * Generates initials from the user's name for the avatar fallback.
 * "John Addo" → "JA", "Grace" → "G"
 */
const userInitials = computed(() => {
  const name = props.user?.name?.trim()
  if (!name) return 'U'

  const parts = name.split(/\s+/)
  const firstPart = parts[0]

  if (!firstPart) return 'U'

  if (parts.length === 1) {
    return firstPart[0]?.toUpperCase() ?? 'U'
  }

  const lastPart = parts[parts.length - 1]
  const firstChar = firstPart[0] ?? ''
  const lastChar = lastPart?.[0] ?? ''

  return (firstChar + lastChar).toUpperCase() || 'U'
})
</script>

<template>
  <!--
    Topbar root element.
    `<header>` is semantically correct for a page-level banner/header region.
    `role="banner"` is implicit on `<header>` but explicit here for clarity.
  -->
  <header class="ch-topbar" role="banner">
    <!-- ─── Left Zone ──────────────────────────────────────────────────────
      Contains: mobile menu toggle + page title/breadcrumb
    -->
    <div class="ch-topbar__left">
      <!--
        Mobile hamburger menu button.
        `aria-label` gives it an accessible name.
        Only visible on mobile via CSS — hidden on desktop.
      -->
      <button
        class="ch-topbar__menu-btn"
        aria-label="Open navigation menu"
        type="button"
        @click="emit('menu-click')"
      >
        <!-- Three-line hamburger icon (pure CSS/SVG, no library) -->
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path
            d="M3 5h14M3 10h14M3 15h14"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
      </button>

      <!--
        Page title / breadcrumb area.
        If the parent provides a `title` slot, render that.
        Otherwise fall back to the `pageTitle` prop as a simple heading.
      -->
      <div class="ch-topbar__title-area">
        <slot name="title">
          <h1 v-if="pageTitle" class="ch-topbar__title">
            {{ pageTitle }}
          </h1>
        </slot>
      </div>
    </div>

    <!-- ─── Center Zone ────────────────────────────────────────────────────
      Optional custom center content (e.g. a date range picker, view toggle).
      Hidden if no content is provided.
    -->
    <div v-if="$slots.center" class="ch-topbar__center">
      <slot name="center" />
    </div>

    <!-- ─── Right Zone ─────────────────────────────────────────────────────
      Contains: custom action buttons + search + notifications + user avatar.
      Items are laid out in a flex row with consistent gaps.
    -->
    <div class="ch-topbar__right">
      <!--
        Custom action buttons slot — placed before the utility icons.
        Use this for page-specific CTAs: "Add Member", "Export", etc.
        These are typically ChButton components.
      -->
      <div v-if="$slots.actions" class="ch-topbar__actions">
        <slot name="actions" />
      </div>

      <!-- Visual separator between actions and utility icons -->
      <div v-if="$slots.actions" class="ch-topbar__divider" aria-hidden="true" />

      <!--
        Search button — triggers a global search modal/overlay.
        The actual search UI is not part of the topbar (it's a modal).
      -->
      <button
        class="ch-topbar__icon-btn"
        aria-label="Search"
        type="button"
        @click="emit('search-click')"
      >
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.5" />
          <path d="M13 13L16 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
      </button>

      <!--
        Notification bell.
        The badge (`ch-topbar__notif-badge`) is absolutely positioned
        on the top-right corner of the button.
        The `aria-label` dynamically communicates the count to screen readers.
      -->
      <button
        class="ch-topbar__icon-btn ch-topbar__notif-btn"
        :aria-label="hasNotifications ? `${notifications} unread notifications` : 'Notifications'"
        type="button"
        @click="emit('notifications-click')"
      >
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <path
            d="M9 2.5A5.5 5.5 0 003.5 8v3.5L2 13h14l-1.5-1.5V8A5.5 5.5 0 009 2.5z"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linejoin="round"
          />
          <path d="M7 13.5a2 2 0 004 0" stroke="currentColor" stroke-width="1.5" />
        </svg>

        <!-- Badge — only renders when there are unread notifications -->
        <span v-if="hasNotifications" class="ch-topbar__notif-badge" aria-hidden="true">
          {{ notificationLabel }}
        </span>
      </button>

      <!-- Visual separator before user area -->
      <div class="ch-topbar__divider" aria-hidden="true" />

      <!--
        User avatar button — clicking opens a dropdown or profile panel.
        Shows profile photo if available, falls back to initials.
      -->
      <button
        class="ch-topbar__user-btn"
        :aria-label="`${user?.name ?? 'User'} — account menu`"
        type="button"
        @click="emit('user-click')"
      >
        <!-- Profile photo -->
        <img
          v-if="user?.avatar"
          :src="user.avatar"
          :alt="user.name"
          class="ch-topbar__avatar-img"
        />

        <!-- Initials fallback when no photo available -->
        <span v-else class="ch-topbar__avatar-initials">
          {{ userInitials }}
        </span>

        <!-- Name + role — visible on desktop, hidden on small screens -->
        <span v-if="user?.name" class="ch-topbar__user-info">
          <span class="ch-topbar__user-name">{{ user.name }}</span>
          <span v-if="user?.role" class="ch-topbar__user-role">{{ user.role }}</span>
        </span>

        <!-- Chevron indicator -->
        <svg class="ch-topbar__user-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path
            d="M2.5 4.5L6 8L9.5 4.5"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>
    </div>
  </header>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-topbar {
  position: sticky; /* sticks to top of its scroll container */
  top: 0;
  z-index: var(--ch-z-sticky);

  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--ch-space-4);

  height: 64px; /* matches sidebar header height */
  padding: 0 var(--ch-space-6);

  background-color: var(--ch-color-surface);
  border-bottom: 1px solid var(--ch-color-border-strong);
}

/* ─── Left Zone ───────────────────────────────────────────────────────────── */
.ch-topbar__left {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  min-width: 0; /* allow flex shrink for long breadcrumbs */
  flex: 1;
}

/* Mobile menu button — hidden on desktop */
.ch-topbar__menu-btn {
  display: none; /* hidden by default — shown via media query */
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: var(--ch-radius-md);
  border: none;
  background: transparent;
  color: var(--ch-color-text-muted);
  cursor: pointer;
  flex-shrink: 0;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-topbar__menu-btn:hover {
  background-color: var(--ch-color-bg-muted);
  color: var(--ch-color-text);
}

/* Show hamburger on mobile */
@media (max-width: 768px) {
  .ch-topbar__menu-btn {
    display: flex;
  }

  .ch-topbar {
    padding: 0 var(--ch-space-4);
  }
}

/* ─── Title ───────────────────────────────────────────────────────────────── */
.ch-topbar__title-area {
  min-width: 0;
  overflow: hidden;
}

.ch-topbar__title {
  font-size: var(--ch-text-lg); /* 18px */
  font-weight: var(--ch-font-semibold);
  font-family: var(--ch-font-display); /* display serif for page titles */
  color: var(--ch-color-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.2;
  margin: 0; /* reset default h1 margin */
}

/* ─── Center Zone ─────────────────────────────────────────────────────────── */
.ch-topbar__center {
  flex-shrink: 0;
}

/* ─── Right Zone ──────────────────────────────────────────────────────────── */
.ch-topbar__right {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
  flex-shrink: 0;
}

.ch-topbar__actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

/* ─── Vertical Divider ────────────────────────────────────────────────────── */
.ch-topbar__divider {
  width: 1px;
  height: 20px;
  background-color: var(--ch-color-border-strong);
  margin: 0 var(--ch-space-1);
  flex-shrink: 0;
}

/* ─── Icon Buttons (search, notifications) ────────────────────────────────── */
.ch-topbar__icon-btn {
  position: relative; /* for notification badge positioning */
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: var(--ch-radius-md);
  border: none;
  background: transparent;
  color: var(--ch-color-text-muted);
  cursor: pointer;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-topbar__icon-btn:hover {
  background-color: var(--ch-color-bg-muted);
  color: var(--ch-color-text);
}

/* ─── Notification Badge ──────────────────────────────────────────────────── */
.ch-topbar__notif-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  min-width: 16px;
  height: 16px;
  padding: 0 var(--ch-space-1);
  border-radius: var(--ch-radius-md);

  background-color: var(--ch-color-danger);
  color: var(--ch-color-text-inverse);
  font-size: 0.5625rem; /* 9px — very small */
  font-weight: var(--ch-font-bold);
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;

  /* White border creates visual separation from the bell icon */
  border: 1px solid var(--ch-color-surface);
}

/* ─── User Button ─────────────────────────────────────────────────────────── */
.ch-topbar__user-btn {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  padding: var(--ch-space-1) var(--ch-space-2);
  border-radius: var(--ch-radius-sm);
  border: none;
  background: transparent;
  cursor: pointer;
  color: var(--ch-color-text);
  transition: background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-topbar__user-btn:hover {
  background-color: var(--ch-color-bg-muted);
}

/* Avatar image */
.ch-topbar__avatar-img {
  width: 32px;
  height: 32px;
  border-radius: var(--ch-radius-sm);
  object-fit: cover;
  flex-shrink: 0;
}

/* Initials fallback circle */
.ch-topbar__avatar-initials {
  width: 32px;
  height: 32px;
  border-radius: var(--ch-radius-sm);
  background-color: var(--ch-color-primary-muted);
  color: var(--ch-color-primary);
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-semibold);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* User name + role text — hidden on small screens */
.ch-topbar__user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 1px;

  /* Hide on medium-and-below screens to keep topbar uncluttered */
  @media (max-width: 1024px) {
    display: none;
  }
}

.ch-topbar__user-name {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
  white-space: nowrap;
}

.ch-topbar__user-role {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  white-space: nowrap;
}

.ch-topbar__user-chevron {
  color: var(--ch-color-text-subtle);
  flex-shrink: 0;

  @media (max-width: 1024px) {
    display: none;
  }
}
</style>
