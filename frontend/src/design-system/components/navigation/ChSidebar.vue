<script setup lang="ts">
/**
 * @component ChSidebarItem
 * @description A single navigation entry with support for leaf items, groups, and collapsed mode.
 */

import { computed, ref, watch } from 'vue'
import type { Component } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface NavItem {
  label: string
  to?: string
  icon?: Component  // 👈 now type-safe
  badge?: number
  children?: NavItem[]
  disabled?: boolean
}

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  item: NavItem
  currentRoute: string
  collapsed?:    boolean
  depth?: number
}

const props = withDefaults(defineProps<Props>(), {
  collapsed: false,
  depth: 0,
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  navigate: [to: string]
}>()

// ─── State ────────────────────────────────────────────────────────────────────

const isOpen = ref(hasActiveChild())

// ─── Computed ─────────────────────────────────────────────────────────────────

const isGroup = computed(() => !!props.item.children?.length)

const isActive = computed(() =>
  !!props.item.to && props.currentRoute === props.item.to
)

const isChildActive = computed(() =>
  isGroup.value && checkChildActive(props.item.children ?? [])
)

const isGroupHighlighted = computed(() =>
  isGroup.value && (isOpen.value || isChildActive.value)
)

const showBadge = computed(() =>
  typeof props.item.badge === 'number' && props.item.badge > 0
)

const badgeLabel = computed(() => {
  const n = props.item.badge ?? 0
  return n > 99 ? '99+' : String(n)
})

// ─── Helpers ──────────────────────────────────────────────────────────────────

function checkChildActive(children: NavItem[]): boolean {
  return children.some(child =>
    child.to === props.currentRoute ||
    (child.children ? checkChildActive(child.children) : false)
  )
}

function hasActiveChild(): boolean {
  if (!props.item.children) return false
  return checkChildActive(props.item.children)
}

// ─── Watcher – auto‑expand group when a child becomes active ─────────────────

watch(() => props.currentRoute, () => {
  if (isGroup.value && !isOpen.value && hasActiveChild()) {
    isOpen.value = true
  }
})

// ─── Handlers ─────────────────────────────────────────────────────────────────

function handleClick() {
  if (props.item.disabled) return

  if (isGroup.value) {
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
      Use a button for all items; leaf items get role="link" for accessibility.
      Depth padding is handled via a CSS custom property.
    -->
    <button :class="[
  'ch-sidebar-item',
  {
    'ch-sidebar-item--active': isActive,
    'ch-sidebar-item--group-active': isGroupHighlighted,
    'ch-sidebar-item--collapsed': collapsed,
    'ch-sidebar-item--disabled': item.disabled,
    'ch-sidebar-item--depth': depth > 0,
  }
]"
:style="{ '--depth': depth }" :aria-current="isActive ? 'page' : undefined"
      :aria-expanded="isGroup ? isOpen : undefined" :disabled="item.disabled"
      :data-tooltip="collapsed ? item.label : undefined" :role="item.to && !isGroup ? 'link' : undefined" type="button"
      @click="handleClick">
      <!-- Icon – type‑safe component rendering -->
      <span v-if="item.icon" class="ch-sidebar-item__icon" aria-hidden="true">
        <component :is="item.icon" :size="18" />
      </span>
      <span v-else class="ch-sidebar-item__dot" aria-hidden="true" />

      <span class="ch-sidebar-item__label">{{ item.label }}</span>

      <span
v-if="showBadge && !isGroup" class="ch-sidebar-item__badge" :aria-label="`${item.badge} pending`">
        {{ badgeLabel }}
      </span>

      <span v-if="isGroup" :class="['ch-sidebar-item__chevron', { 'ch-sidebar-item__chevron--open': isOpen }]"
        aria-hidden="true"
      >
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </span>
    </button>

    <!--
      Children list – animated with max‑height transition.
      The class .ch-sidebar-item__children--open toggles the visible height.
    -->
    <ul
v-if="isGroup && !collapsed"
      :class="['ch-sidebar-item__children', { 'ch-sidebar-item__children--open': isOpen }]" role="group">
      <ChSidebarItem
v-for="child in item.children" :key="child.label" :item="child" :current-route="currentRoute"
        :collapsed="collapsed"
:depth="depth + 1" @navigate="emit('navigate', $event)" />
    </ul>
  </li>
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

/* ─── Children list with smooth animation ─────────────────────────────────── */
.ch-sidebar-item__children {
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.3s ease-out;
  margin: 0;
  padding: 0;
  list-style: none;
}

.ch-sidebar-item__children--open {
  max-height: 1000px;
  /* safe upper bound; adjust if groups can be larger */
}

/* ─── Depth padding using CSS custom property ────────────────────────────── */
.ch-sidebar-item--depth {
  padding-left: calc(var(--ch-space-4) + (var(--depth) * 12px));
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
