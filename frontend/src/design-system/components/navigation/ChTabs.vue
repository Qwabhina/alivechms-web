<script setup lang="ts">
/**
 * @component ChTabs
 * @path /frontend/src/design-system/components/navigation/ChTabs.vue
 * @description A horizontal tab bar for switching between related views
 * within the same page or section.
 *
 * ─── When to use tabs vs sidebar nav ─────────────────────────────────────────
 * - **Sidebar** → switches between PAGES (completely different routes)
 * - **Tabs**    → switches between VIEWS of the same content (no route change)
 *   e.g. a Member detail page with tabs: "Profile | Giving | Attendance | Groups"
 *
 * However, tabs CAN be route-linked (via the `to` field on each tab) for
 * cases where each tab IS a sub-route. Both modes are supported.
 *
 * ─── Variant guide ───────────────────────────────────────────────────────────
 * - `underline` → the default; a bottom border on the active tab.
 *   Best for: page-level tab bars (below the topbar title).
 *
 * - `pills` → filled background on the active tab.
 *   Best for: secondary tabs inside a card or panel.
 *
 * - `boxed` → card-like tabs with a visible border.
 *   Best for: tabbed content panels with clear visual containment.
 *
 * ─── Controlled vs uncontrolled ──────────────────────────────────────────────
 * The active tab is controlled via `v-model` (passes back the tab's `value`).
 * If you don't need programmatic control, just set `modelValue` to the
 * first tab's value initially and let the component handle the rest.
 *
 * @example Member detail tabs
 * <ChTabs
 *   v-model="activeTab"
 *   :tabs="[
 *     { label: 'Profile',    value: 'profile' },
 *     { label: 'Giving',     value: 'giving',     badge: 12 },
 *     { label: 'Attendance', value: 'attendance' },
 *     { label: 'Groups',     value: 'groups',     badge: 3 },
 *   ]"
 * />
 * <div v-if="activeTab === 'profile'">...</div>
 *
 * @example Route-linked tabs
 * <ChTabs
 *   :tabs="[
 *     { label: 'Overview',      value: '/finance',              to: '/finance' },
 *     { label: 'Contributions', value: '/finance/contributions', to: '/finance/contributions' },
 *     { label: 'Expenses',      value: '/finance/expenses',      to: '/finance/expenses' },
 *   ]"
 *   :model-value="route.path"
 * />
 */

import { computed } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * A single tab item definition.
 */
export interface Tab {
  /** Display text of the tab */
  label:      string

  /**
   * Unique identifier for this tab.
   * This is the value emitted and compared via v-model.
   * For route-linked tabs, use the route path as the value.
   */
  value:      string

  /**
   * Optional route path — when provided, clicking the tab
   * emits a `navigate` event instead of just updating the model.
   */
  to?:        string

  /**
   * Optional icon component (e.g. from lucide-vue-next).
   * Rendered before the label text.
   */
  icon?:      unknown

  /**
   * Optional numeric badge.
   * Shown as a small count pill after the label.
   * Common uses: unread items, pending counts.
   */
  badge?:     number

  /** When true, the tab is non-interactive */
  disabled?:  boolean
}

/**
 * Visual style variants for the tab bar.
 */
type Variant = 'underline' | 'pills' | 'boxed'

/**
 * Size of the tab items.
 */
type Size    = 'sm' | 'md' | 'lg'

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /** Array of tab definitions */
  tabs:        Tab[]

  /**
   * The value of the currently active tab.
   * Bind with v-model: `v-model="activeTab"`
   */
  modelValue?: string

  /** Visual style variant. Default: 'underline' */
  variant?:    Variant

  /** Tab size. Default: 'md' */
  size?:       Size

  /**
   * When true, tabs fill the full width of the container equally.
   * Each tab gets the same width via `flex: 1`.
   */
  fullWidth?:  boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant:   'underline',
  size:      'md',
  fullWidth: false,
})

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
  /** v-model update — carries the clicked tab's `value` */
  'update:modelValue': [value: string]

  /**
   * Fired when a route-linked tab is clicked.
   * The parent should call `router.push(to)`.
   */
  'navigate':          [to: string]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

/** Pre-computed set of root classes for the tab list */
const listClasses = computed(() => [
  'ch-tabs',
  `ch-tabs--${props.variant}`,
  `ch-tabs--${props.size}`,
  { 'ch-tabs--full-width': props.fullWidth },
])

// ─── Handlers ─────────────────────────────────────────────────────────────────

/**
 * Handles tab click.
 * - Emits `update:modelValue` with the tab's value (always)
 * - If the tab has a `to` route, also emits `navigate`
 */
function selectTab(tab: Tab) {
  if (tab.disabled) return

  // Update the active tab in the parent's v-model
  emit('update:modelValue', tab.value)

  // If route-linked, also trigger navigation
  if (tab.to) {
    emit('navigate', tab.to)
  }
}

/**
 * Handles keyboard navigation within the tab list.
 * Left/Right arrows move focus between tabs.
 * Enter/Space activates the focused tab.
 * This matches the ARIA tab panel keyboard interaction pattern.
 */
function handleKeydown(e: KeyboardEvent, index: number) {
  const enabledTabs = props.tabs
    .map((t, i) => ({ tab: t, index: i }))
    .filter(({ tab }) => !tab.disabled)

  const currentEnabledIndex = enabledTabs.findIndex(({ index: i }) => i === index)

  if (e.key === 'ArrowRight') {
    e.preventDefault()
    const next = enabledTabs[(currentEnabledIndex + 1) % enabledTabs.length]
    if (next) {
      // Focus the next tab button
      const buttons = (e.currentTarget as HTMLElement)
        ?.closest('[role="tablist"]')
        ?.querySelectorAll<HTMLButtonElement>('button:not(:disabled)')
      const target = buttons?.[currentEnabledIndex + 1] ?? buttons?.[0]
      target?.focus()
    }
  }

  if (e.key === 'ArrowLeft') {
    e.preventDefault()
    const prev = enabledTabs[(currentEnabledIndex - 1 + enabledTabs.length) % enabledTabs.length]
    if (prev) {
      const buttons = (e.currentTarget as HTMLElement)
        ?.closest('[role="tablist"]')
        ?.querySelectorAll<HTMLButtonElement>('button:not(:disabled)')
      const newIndex = currentEnabledIndex === 0 ? buttons!.length - 1 : currentEnabledIndex - 1
      buttons?.[newIndex]?.focus()
    }
  }
}
</script>

<template>
  <!--
    `role="tablist"` is the ARIA role for a group of tab elements.
    Screen readers use this to understand the tab navigation pattern.
    `aria-label` gives the tab list an accessible name in context.
  -->
  <div :class="listClasses" role="tablist" aria-label="Page sections">
    <button
      v-for="(tab, index) in tabs"
      :key="tab.value"
      :class="[
        'ch-tab',
        {
          'ch-tab--active':   modelValue === tab.value,
          'ch-tab--disabled': tab.disabled,
          'ch-tab--full-width': fullWidth,
        }
      ]"
      role="tab"
      type="button"
      :aria-selected="modelValue === tab.value"
      :aria-disabled="tab.disabled"
      :tabindex="modelValue === tab.value ? 0 : -1"
      :disabled="tab.disabled"
      @click="selectTab(tab)"
      @keydown="handleKeydown($event, index)"
    >
      <!-- Optional icon before the label -->
      <span v-if="tab.icon" class="ch-tab__icon" aria-hidden="true">
        <component :is="tab.icon" :size="16" />
      </span>

      <!-- Tab label text -->
      <span class="ch-tab__label">{{ tab.label }}</span>

      <!--
        Optional badge count — displayed inline after the label.
        Shows a small count pill.
        `aria-label` on the button already includes context,
        so the badge itself is `aria-hidden`.
      -->
      <span
        v-if="tab.badge && tab.badge > 0"
        class="ch-tab__badge"
        aria-hidden="true"
      >
        {{ tab.badge > 99 ? '99+' : tab.badge }}
      </span>
    </button>
  </div>
</template>

<style scoped>
/* ─── Tab List (container) ────────────────────────────────────────────────── */
.ch-tabs {
  display:     flex;
  align-items: flex-end;    /* aligns to bottom for underline variant */
  gap:         0;           /* tabs touch each other in underline mode */
  overflow-x:  auto;        /* scroll if tabs overflow the container width */
  overflow-y:  visible;     /* don't clip the active indicator */

  /* Hide scrollbar on most browsers but still allow scrolling */
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.ch-tabs::-webkit-scrollbar { display: none; }

/* Full width: each tab gets equal width */
.ch-tabs--full-width {
  width: 100%;
}

/* ─── Individual Tab Button ───────────────────────────────────────────────── */
.ch-tab {
  display:         inline-flex;
  align-items:     center;
  justify-content: center;
  gap:             var(--ch-space-1_5); /* 6px between icon, label, badge */
  border:          none;
  background:      transparent;
  cursor:          pointer;
  white-space:     nowrap;
  font-family:     var(--ch-font-sans);
  font-weight:     var(--ch-font-medium);
  color:           var(--ch-color-text-muted);
  position:        relative;   /* for ::after active indicator */
  flex-shrink:     0;

  transition:
    color            var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
}

/* Full-width tab: equal flex growth */
.ch-tab--full-width { flex: 1; }

/* Hover — darken text */
.ch-tab:hover:not(:disabled):not(.ch-tab--active) {
  color: var(--ch-color-text);
}

/* Active — brand primary color */
.ch-tab--active {
  color:       var(--ch-color-primary);
  font-weight: var(--ch-font-semibold);
}

/* Disabled */
.ch-tab--disabled {
  opacity: 0.45;
  cursor:  not-allowed;
}

/* ─── Sizes ───────────────────────────────────────────────────────────────── */
.ch-tabs--sm .ch-tab {
  font-size: var(--ch-text-xs);  /* 12px */
  padding:   var(--ch-space-1_5) var(--ch-space-3);
}

.ch-tabs--md .ch-tab {
  font-size: var(--ch-text-sm);  /* 14px */
  padding:   var(--ch-space-2) var(--ch-space-4);
}

.ch-tabs--lg .ch-tab {
  font-size: var(--ch-text-base); /* 16px */
  padding:   var(--ch-space-2_5) var(--ch-space-5);
}

/* ─── Variant: Underline ──────────────────────────────────────────────────── */
/*
 * The classic tab style. A bottom border spans the full list;
 * the active tab has a colored "indicator" bar on top of it.
 *
 * Implementation:
 *   - `.ch-tabs--underline` has a border-bottom (the gray baseline)
 *   - Active `.ch-tab::after` creates a colored bar that sits ON TOP
 *     of the gray border, visually "selecting" the active tab
 */
.ch-tabs--underline {
  border-bottom: 2px solid var(--ch-color-border);
}

/* Active indicator: 2px colored bar at the bottom of the active tab */
.ch-tabs--underline .ch-tab--active::after {
  content:    '';
  position:   absolute;
  bottom:     -2px;             /* align with the list's border-bottom */
  left:       0;
  right:      0;
  height:     2px;
  background: var(--ch-color-primary);
  border-radius: var(--ch-radius-none); /* 0px */
}

/* Hover underline hint — subtle indicator before the tab is selected */
.ch-tabs--underline .ch-tab:hover:not(:disabled):not(.ch-tab--active)::after {
  content:    '';
  position:   absolute;
  bottom:     -2px;
  left:       var(--ch-space-4);
  right:      var(--ch-space-4);
  height:     2px;
  background: var(--ch-color-border-strong);
  border-radius: var(--ch-radius-none); /* 0px */
}

/* ─── Variant: Pills ──────────────────────────────────────────────────────── */
/*
 * Filled background on the active tab — no underline.
 * Works well inside a card or panel where a softer selection style is needed.
 */
.ch-tabs--pills {
  background-color: var(--ch-color-bg-muted); /* subtle container */
  border-radius:    var(--ch-radius-none); /* sharp */
  padding:          var(--ch-space-1);
  gap:              var(--ch-space-1);
  align-items:      center;
  border-bottom:    none;
}

.ch-tabs--pills .ch-tab {
  border-radius: var(--ch-radius-none); /* sharp */
}

/* Active pill — white background with hard shadow (lifts off the gray container) */
.ch-tabs--pills .ch-tab--active {
  background-color: var(--ch-color-surface);
  box-shadow:       var(--ch-shadow-sm); /* stark offset */
  color:            var(--ch-color-text);
  border:           1px solid var(--ch-color-border-strong); /* sharp edge */
}

.ch-tabs--pills .ch-tab:hover:not(:disabled):not(.ch-tab--active) {
  background-color: var(--ch-color-bg-subtle);
}

/* ─── Variant: Boxed ──────────────────────────────────────────────────────── */
/*
 * Card-like tabs with visible borders. The active tab appears to be
 * "in front of" the content area below it (the bottom border is removed).
 *
 * Visually it looks like folder tabs on a desk.
 */
.ch-tabs--boxed {
  border-bottom: 2px solid var(--ch-color-border-strong);
  gap:           var(--ch-space-1);
  align-items:   flex-end;
}

.ch-tabs--boxed .ch-tab {
  border:        2px solid transparent;
  border-bottom: none;
  border-radius: var(--ch-radius-none); /* 0px corners */
  margin-bottom: -2px; /* overlap the list's border-bottom */
}

.ch-tabs--boxed .ch-tab--active {
  background-color: var(--ch-color-surface);
  border-color:     var(--ch-color-border-strong);
  /* Remove the bottom border so the tab "connects" to the content area below */
  border-bottom:    2px solid var(--ch-color-surface);
  color:            var(--ch-color-text);
}

.ch-tabs--boxed .ch-tab:hover:not(:disabled):not(.ch-tab--active) {
  background-color: var(--ch-color-bg-subtle);
  border-color:     var(--ch-color-border-strong);
}

/* ─── Icon ────────────────────────────────────────────────────────────────── */
.ch-tab__icon {
  display:    flex;
  align-items:center;
  flex-shrink:0;
}

/* ─── Badge ───────────────────────────────────────────────────────────────── */
.ch-tab__badge {
  display:          inline-flex;
  align-items:      center;
  justify-content:  center;
  min-width:        18px;
  height:           18px;
  padding:          0 var(--ch-space-1);
  border-radius:    var(--ch-radius-full);
  background-color: var(--ch-color-bg-muted);
  border:           1px solid var(--ch-color-border-strong);
  color:            var(--ch-color-text-muted);
  font-size:        0.625rem;  /* 10px */
  font-weight:      var(--ch-font-semibold);
  line-height:      1;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    color            var(--ch-duration-fast) var(--ch-ease-out);
}

/* Active tab badge gets brand colors */
.ch-tab--active .ch-tab__badge {
  background-color: var(--ch-color-primary-muted);
  border-color:     var(--ch-color-primary);
  color:            var(--ch-color-primary);
}
</style>
