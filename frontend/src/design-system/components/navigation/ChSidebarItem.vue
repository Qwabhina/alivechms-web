<script setup lang="ts">
/**
 * @component ChSidebarItem
 * @path /frontend/src/design-system/components/navigation/ChSidebarItem.vue
 * @description A single navigation entry in the sidebar.
 *
 * Handles three distinct modes:
 *   1. **Leaf item** — a simple link with icon + label (no children)
 *   2. **Group item** — a collapsible parent with nested children
 *   3. **Collapsed sidebar** — icon-only display with a tooltip
 *
 * ─── Active state detection ──────────────────────────────────────────────────
 * Active state is determined by comparing the item's `to` route with the
 * current route. The parent ChSidebar passes `currentRoute` as a prop so
 * ChSidebarItem doesn't need to import Vue Router directly — keeping it
 * decoupled and easier to test.
 *
 * For group items, the group header is considered "active" (highlighted)
 * when ANY of its children is the current route. This gives the user
 * a visual breadcrumb of where they are in the hierarchy.
 *
 * ─── Collapsed mode ──────────────────────────────────────────────────────────
 * When the sidebar is collapsed to icon-only mode, labels and badges
 * are hidden. A tooltip appears on hover to show the label. For group
 * items, the tooltip shows the group name instead of opening a submenu.
 *
 * @example Leaf item (used internally by ChSidebar)
 * <ChSidebarItem
 *   :item="{ label: 'Members', to: '/members', icon: UsersIcon }"
 *   :current-route="route.path"
 *   :collapsed="sidebarCollapsed"
 * />
 */

import { computed, ref } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Represents a single navigation item or group.
 * This type is exported so ChSidebar and consuming code can build
 * nav arrays in a type-safe way.
 */
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
   * Typed as `any` to be compatible with any icon library.
   * Usage: `import { UsersIcon } from 'lucide-vue-next'`
   */
  // icon?:    any
  icon?:    unknown

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
  /** The nav item data (label, route, icon, children, badge) */
  item:          NavItem

  /** The current route path — used for active state detection */
  currentRoute:  string

  /**
   * Whether the sidebar is in collapsed (icon-only) mode.
   * When true: hides labels, badges, chevrons; shows tooltips.
   */
  collapsed?:    boolean

  /**
   * Indentation depth — used for nested children.
   * Depth 0 = top-level, depth 1 = first-level children, etc.
   * Each level adds left padding to visually show hierarchy.
   */
  depth?:        number
}

const props = withDefaults(defineProps<Props>(), {
  collapsed: false,
  depth:     0,
})

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
  /**
   * Fired when the user clicks a leaf item (one with a `to` route).
   * ChSidebar listens to this to handle navigation and close mobile drawer.
   */
  navigate: [to: string]
}>()

// ─── State ────────────────────────────────────────────────────────────────────

/**
 * Controls whether the children of a group item are visible.
 * Starts expanded if any child is the current route (so the user
 * lands with the relevant section already open).
 */
const isOpen = ref(hasActiveChild())

/** Tracks tooltip visibility for collapsed mode */
const showTooltip = ref(false)

/** Show tooltip only when collapsed and hovering */
function handleMouseEnter() {
  if (props.collapsed) {
    showTooltip.value = true
  }
}

function handleMouseLeave() {
  showTooltip.value = false
}

// ─── Computed ─────────────────────────────────────────────────────────────────

/** True if this item has nested children (is a group header) */
const isGroup = computed(() => !!props.item.children?.length)

/**
 * True if this item's route exactly matches the current route.
 * Only meaningful for leaf items (no children).
 */
const isActive = computed(() =>
  !!props.item.to && props.currentRoute === props.item.to
)

/**
 * True if this is a group header AND any child's route matches the current route.
 * Used to highlight the group header when the user is inside that section.
 */
const isChildActive = computed(() =>
  isGroup.value && checkChildActive(props.item.children ?? [])
)

/**
 * A group header is "highlighted" if it's open OR if a child is active.
 * This ensures the parent always visually signals "you are here" even
 * when a child page is being viewed.
 */
const isGroupHighlighted = computed(() =>
  isGroup.value && (isOpen.value || isChildActive.value)
)

/**
 * Badge display: only show if badge value is a positive number.
 * `badge === 0` is treated as "nothing to show" (not "zero unread").
 */
const showBadge = computed(() =>
  typeof props.item.badge === 'number' && props.item.badge > 0
)

/**
 * Caps badge display at 99 to prevent overflow in the small bubble.
 * e.g. 142 → "99+"
 */
const badgeLabel = computed(() => {
  const n = props.item.badge ?? 0
  return n > 99 ? '99+' : String(n)
})

/**
 * Left padding increases with depth to show hierarchy.
 * depth 0 = 0 extra padding (uses base item padding)
 * depth 1 = 12px additional left padding
 * depth 2 = 24px additional left padding (unlikely but supported)
 */
const depthPadding = computed(() => `${props.depth * 12}px`)

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Checks if any child (or nested grandchild) route matches the current route.
 * Recursive so it works for arbitrarily deep nesting.
 */
function checkChildActive(children: NavItem[]): boolean {
  return children.some(child =>
    child.to === props.currentRoute ||
    (child.children ? checkChildActive(child.children) : false)
  )
}

/**
 * Called at initialization to determine if the group should start open.
 * We open it if the current route is within this group's children.
 */
function hasActiveChild(): boolean {
  if (!props.item.children) return false
  return checkChildActive(props.item.children)
}

// ─── Handlers ─────────────────────────────────────────────────────────────────

/**
 * Handles clicking on any nav item.
 *
 * Behavior:
 * - If disabled → do nothing
 * - If leaf item with `to` route → emit navigate event
 * - If group header → toggle open/close (don't navigate)
 * - In collapsed mode, group items show a tooltip instead of toggling
 */
function handleClick() {
  if (props.item.disabled) return

  if (isGroup.value) {
    // In collapsed mode, clicking a group doesn't expand it
    // (the sidebar is too narrow to show children)
    if (!props.collapsed) {
      isOpen.value = !isOpen.value
    }
    return
  }

  if (props.item.to) {
    emit('navigate', props.item.to)
  }
}
</script>

<template>
  <li class="ch-sidebar-item-wrapper">

    <!--
      ─── Item Button ──────────────────────────────────────────────────────────
      We use a `<button>` for all items (not `<a>`) because navigation is
      handled by the parent via the `navigate` emit — we don't want native
      anchor link behavior. The parent's router integration handles the actual
      route change.

      `data-tooltip` stores the label for the CSS tooltip in collapsed mode.
      The tooltip itself is shown via CSS `::after` with `attr(data-tooltip)`.

      `:style="{ paddingLeft: ... }"` adds depth-based indentation for children.
    -->
    <button
      :class="[
        'ch-sidebar-item',
        {
          'ch-sidebar-item--active':      isActive,
          'ch-sidebar-item--group-active': isGroupHighlighted,
          'ch-sidebar-item--collapsed':   collapsed,
          'ch-sidebar-item--disabled':    item.disabled,
          'ch-sidebar-item--depth':       depth > 0,
        }
      ]"
      :style="{ paddingLeft: depth > 0 ? `calc(var(--ch-space-4) + ${depthPadding})` : undefined }"
      :aria-current="isActive ? 'page' : undefined"
      :aria-expanded="isGroup ? isOpen : undefined"
      :disabled="item.disabled"
      :data-tooltip="collapsed ? item.label : undefined"
      type="button"
      @click="handleClick"
      @mouseenter="handleMouseEnter"
      @mouseleave="handleMouseLeave"
    >
      <!-- Icon slot — always visible even in collapsed mode -->
      <span v-if="item.icon" class="ch-sidebar-item__icon" aria-hidden="true">
        <component :is="item.icon" :size="18" />
      </span>

      <!-- Fallback dot if no icon provided -->
      <span v-else class="ch-sidebar-item__dot" aria-hidden="true" />

      <!-- Tooltip — shown on hover in collapsed mode -->
      <span
        v-if="showTooltip && collapsed"
        class="ch-sidebar-item__tooltip"
      >
        {{ item.label }}
      </span>

      <!--
        Label + badge — hidden in collapsed mode via CSS `display: none`.
        The `v-show` here is not used intentionally; we use CSS to hide
        these so transitions work smoothly with the sidebar width animation.
      -->
      <span class="ch-sidebar-item__label">{{ item.label }}</span>

      <!-- Badge count — shown for leaf items with a badge value -->
      <span
        v-if="showBadge && !isGroup"
        class="ch-sidebar-item__badge"
        :aria-label="`${item.badge} pending`"
      >
        {{ badgeLabel }}
      </span>

      <!--
        Chevron for group items — rotates 180° when the group is open.
        Uses a pure CSS transform so it animates smoothly.
      -->
      <span
        v-if="isGroup"
        :class="['ch-sidebar-item__chevron', { 'ch-sidebar-item__chevron--open': isOpen }]"
        aria-hidden="true"
      >
        <!-- Inline SVG chevron — no icon library dependency for this micro-icon -->
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </button>

    <!--
      ─── Children (nested nav items) ─────────────────────────────────────────
      Only rendered for group items. Hidden in collapsed sidebar mode.

      `v-show` (not `v-if`) keeps the DOM nodes alive so open/close
      can be animated with a CSS max-height transition.

      The `<ul>` is the semantic container for a list of nav links.
      `role="group"` + the parent's `aria-expanded` handles accessibility.
    -->
    <ul
      v-if="isGroup && !collapsed"
      v-show="isOpen"
      class="ch-sidebar-item__children"
      role="group"
    >
      <!--
        Recursively render ChSidebarItem for each child.
        `depth + 1` increases indentation for nested levels.
        We bubble up the navigate event from children to the top-level sidebar.
      -->
      <ChSidebarItem
        v-for="child in item.children"
        :key="child.label"
        :item="child"
        :current-route="currentRoute"
        :collapsed="collapsed"
        :depth="depth + 1"
        @navigate="emit('navigate', $event)"
      />
    </ul>
  </li>
</template>

<style scoped>
/* ─── Wrapper ─────────────────────────────────────────────────────────────── */
.ch-sidebar-item-wrapper {
  list-style: none;
  position:   relative; /* needed for tooltip absolute positioning */
}

/* ─── Item Button ─────────────────────────────────────────────────────────── */
.ch-sidebar-item {
  /* Full-width button that spans the sidebar */
  display:         flex;
  align-items:     center;
  gap:             var(--ch-space-3);  /* 12px between icon and label */
  width:           100%;
  padding:         var(--ch-space-2) var(--ch-space-3); /* 8px 12px */
  border-radius:   var(--ch-radius-lg);
  border:          none;
  background:      transparent;
  color:           var(--ch-color-text-muted);
  font-family:     var(--ch-font-sans);
  font-size:       var(--ch-text-sm);   /* 14px */
  font-weight:     var(--ch-font-medium);
  text-align:      left;
  cursor:          pointer;
  white-space:     nowrap;             /* prevent label from wrapping */
  overflow:        hidden;             /* clip overflowing text */
  min-height:      36px;

  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    color            var(--ch-duration-fast) var(--ch-ease-out),
    padding          var(--ch-duration-slow) var(--ch-ease-out);
}

/* Hover state — subtle background + text darkens */
.ch-sidebar-item:hover:not(:disabled):not(.ch-sidebar-item--active) {
  background-color: var(--ch-color-bg-muted);
  color:            var(--ch-color-text);
}

/* Active (current route) — filled with primary tint */
.ch-sidebar-item--active {
  background-color: var(--ch-color-primary-subtle);
  color:            var(--ch-color-primary);
  font-weight:      var(--ch-font-semibold);
}

/*
 * Active indicator bar on the left edge.
 * `::before` pseudo-element creates a 3px tall-ish accent strip.
 * We use `box-shadow` on the parent instead to avoid extra width.
 */
.ch-sidebar-item--active {
  box-shadow: inset 3px 0 0 var(--ch-color-primary);
}

/* Group header highlighted — when a child is the current page */
.ch-sidebar-item--group-active {
  color: var(--ch-color-text);
}

/* Disabled state */
.ch-sidebar-item--disabled {
  opacity: 0.45;
  cursor:  not-allowed;
}

/* Depth > 0 — children items are slightly smaller */
.ch-sidebar-item--depth {
  font-size:  var(--ch-text-xs);
  min-height: 32px;
}

/* ─── Icon ────────────────────────────────────────────────────────────────── */
.ch-sidebar-item__icon {
  display:         flex;
  align-items:     center;
  justify-content: center;
  flex-shrink:     0;       /* never compress the icon */
  width:           20px;    /* fixed width for icon alignment in collapsed mode */
  height:          20px;
}

/* Fallback dot for items without an icon */
.ch-sidebar-item__dot {
  flex-shrink: 0;
  width:        6px;
  height:       6px;
  border-radius: 50%;
  background-color: var(--ch-color-border-strong);
  margin-left: 7px;  /* center-align dot with icon column */
}

/* ─── Label ───────────────────────────────────────────────────────────────── */
.ch-sidebar-item__label {
  flex:          1;           /* take remaining space */
  overflow:      hidden;
  text-overflow: ellipsis;    /* truncate very long labels with "..." */

  /* Animate in/out with sidebar collapse */
  transition:
    opacity var(--ch-duration-slow) var(--ch-ease-out),
    width   var(--ch-duration-slow) var(--ch-ease-out);
}

/* ─── Badge ───────────────────────────────────────────────────────────────── */
.ch-sidebar-item__badge {
  flex-shrink:     0;
  display:         inline-flex;
  align-items:     center;
  justify-content: center;
  min-width:       18px;
  height:          18px;
  padding:         0 var(--ch-space-1);
  border-radius:   var(--ch-radius-full);
  background-color: var(--ch-color-primary);
  color:            var(--ch-color-primary-fg);
  font-size:        0.625rem;  /* 10px — tiny but legible */
  font-weight:      var(--ch-font-semibold);
  line-height:      1;
}

/* ─── Chevron ─────────────────────────────────────────────────────────────── */
.ch-sidebar-item__chevron {
  flex-shrink: 0;
  display:     flex;
  align-items: center;
  color:       var(--ch-color-text-subtle);
  transition:  transform var(--ch-duration-normal) var(--ch-ease-out);
}

/* Rotate 180° (point up) when the group is open */
.ch-sidebar-item__chevron--open {
  transform: rotate(180deg);
}

/* ─── Children list ───────────────────────────────────────────────────────── */
.ch-sidebar-item__children {
  /*
   * Animate open/close with max-height transition.
   * `max-height: 500px` is a safe upper bound for any group's children.
   * When v-show hides this element, max-height transitions to 0
   * (via `.ch-sidebar-item__children[style*="display: none"]` doesn't work
   * cleanly, so we rely on Vue's v-show + CSS transition on the wrapper).
   */
  overflow:   hidden;
  padding:    var(--ch-space-1) 0;
  margin:     0;
  list-style: none;
}

/* ─── Collapsed Mode ──────────────────────────────────────────────────────── */

/* Hide label, badge, chevron when sidebar is collapsed */
.ch-sidebar-item--collapsed .ch-sidebar-item__label,
.ch-sidebar-item--collapsed .ch-sidebar-item__badge,
.ch-sidebar-item--collapsed .ch-sidebar-item__chevron {
  display: none;
}

/* ─── Tooltip (collapsed mode) ───────────────────────────────────────────── */
.ch-sidebar-item__tooltip {
  position:         absolute;
  left:             100%;
  top:              50%;
  transform:        translateY(-50%);
  margin-left:      var(--ch-space-2);
  padding:          var(--ch-space-1) var(--ch-space-2);
  background-color: var(--ch-color-bg-strong);
  color:            var(--ch-color-text);
  font-size:        var(--ch-text-xs);
  font-weight:      var(--ch-font-medium);
  white-space:      nowrap;
  border-radius:    var(--ch-radius-md);
  box-shadow:       var(--ch-shadow-md);
  z-index:          1000;
  pointer-events:   none;
  opacity:          0;
  animation:        ch-tooltip-fade-in var(--ch-duration-fast) forwards;
}

/* Tooltip arrow */
.ch-sidebar-item__tooltip::before {
  content:          '';
  position:         absolute;
  right:            100%;
  top:              50%;
  transform:        translateY(-50%);
  border:           4px solid transparent;
  border-right-color: var(--ch-color-bg-strong);
}

@keyframes ch-tooltip-fade-in {
  to {
    opacity: 1;
  }
}

/* Center the icon when collapsed */
.ch-sidebar-item--collapsed {
  justify-content: center;
  padding:         var(--ch-space-2);
}

/*
 * Tooltip shown on hover in collapsed mode.
 * Uses CSS `attr(data-tooltip)` to read the label from the button's
 * data attribute without needing JavaScript or a separate component.
 *
 * The tooltip appears to the right of the icon, with a small left arrow.
 */
.ch-sidebar-item--collapsed[data-tooltip]:hover::after {
  content:    attr(data-tooltip);
  position:   absolute;
  left:       calc(100% + var(--ch-space-2));
  top:        50%;
  transform:  translateY(-50%);
  background-color: var(--ch-color-text);
  color:      var(--ch-color-text-inverse);
  padding:    var(--ch-space-1) var(--ch-space-2_5);
  border-radius: var(--ch-radius-md);
  font-size:  var(--ch-text-xs);
  font-weight: var(--ch-font-medium);
  white-space: nowrap;
  pointer-events: none;         /* tooltip shouldn't interfere with clicking */
  z-index:    var(--ch-z-tooltip);

  /* Subtle entrance animation */
  animation: ch-fade-in var(--ch-duration-fast) var(--ch-ease-out) both;
}
</style>
