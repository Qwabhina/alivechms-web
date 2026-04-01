<script setup lang="ts">
/**
 * @component ChDropdownItem
 * @path /frontend/src/design-system/components/core/ChDropdownItem.vue
 * @description A single item within a ChDropdown menu. Supports icons,
 * descriptions, disabled state, and visual variants.
 *
 * ─── Keyboard interaction ─────────────────────────────────────────────────────
 * Items receive `tabindex="-1"` so they can be focused programmatically by
 * ChDropdown's arrow-key navigation. When focused, Enter and Space activate
 * the item (matching native button behavior).
 *
 * Tab is intentionally NOT used for menu navigation — the ARIA menu pattern
 * uses arrow keys for item focus and Tab to exit the menu entirely.
 *
 * @example Basic
 * <ChDropdownItem label="Edit" @click="handleEdit" />
 *
 * @example With a Lucide (or any component) icon via slot
 * <ChDropdownItem label="Edit" @click="handleEdit">
 *   <template #icon><Pencil :size="16" /></template>
 * </ChDropdownItem>
 *
 * @example With legacy SVG path (backwards compatible)
 * <ChDropdownItem label="Delete" variant="danger" :icon-path="myPath" />
 *
 * @example With description
 * <ChDropdownItem
 *   label="Delete"
 *   description="Remove this item permanently"
 *   variant="danger"
 *   @click="handleDelete"
 * />
 */

import { computed } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export type DropdownItemVariant = 'default' | 'primary' | 'danger' | 'success' | 'warning'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Display label */
  label: string
  /** Optional description shown below the label */
  description?: string
  /**
   * Legacy SVG path string (the `d` attribute).
   * Prefer the `#icon` slot for component-based icons (e.g. Lucide).
   * @deprecated Use the `#icon` slot instead.
   */
  iconPath?: string
  /** Visual variant for emphasis. Default: 'default' */
  variant?: DropdownItemVariant
  /** Whether the item is disabled */
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  description: '',
  iconPath: '',
  variant: 'default',
  disabled: false,
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  click: [event: MouseEvent | KeyboardEvent]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

const classes = computed(() => [
  'ch-dropdown-item',
  `ch-dropdown-item--${props.variant}`,
  { 'ch-dropdown-item--disabled': props.disabled },
])

// ─── Handlers ─────────────────────────────────────────────────────────────────

function handleClick(e: MouseEvent) {
  if (!props.disabled) emit('click', e)
}

/**
 * Activates the item on Enter or Space — required by the ARIA menuitem pattern.
 * Space default (page scroll) is suppressed via `e.preventDefault()`.
 */
function handleKeydown(e: KeyboardEvent) {
  if (props.disabled) return
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault()
    emit('click', e)
  }
}
</script>

<template>
  <div
    :class="classes"
    role="menuitem"
:tabindex="disabled ? undefined : -1"
    :aria-disabled="disabled ? true : undefined" @click="handleClick" @keydown="handleKeydown"
  >
    <!-- Icon slot: accepts any component (Lucide, custom SVG, etc.) -->
    <span v-if="$slots.icon" class="ch-dropdown-item__icon" :class="`ch-dropdown-item__icon--${variant}`">
      <slot name="icon"></slot>
    </span>

    <!-- Legacy path-string fallback (backwards compat) -->
    <svg
v-else-if="iconPath"
      class="ch-dropdown-item__icon"
:class="`ch-dropdown-item__icon--${variant}`"
      width="16"
      height="16"
      viewBox="0 0 16 16"
      fill="none"
      stroke="currentColor"
      stroke-width="1.5"
aria-hidden="true"
    >
      <path :d="iconPath" stroke-linecap="round" stroke-linejoin="round" />
    </svg>

    <!-- Content -->
    <div class="ch-dropdown-item__content">
      <span class="ch-dropdown-item__label">{{ label }}</span>
      <span v-if="description" class="ch-dropdown-item__description">{{ description }}</span>
    </div>

    <!-- Trailing slot (e.g. badge, shortcut hint) -->
    <slot name="trailing"></slot>
  </div>
</template>

<style scoped>
/* ─── Dropdown item ───────────────────────────────────────────────────────── */
.ch-dropdown-item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  padding: var(--ch-space-2) var(--ch-space-3);
  margin: 0 var(--ch-space-1);
  border-radius: var(--ch-radius-md);
/*
   * Base text color set here so variant hover colors cascade correctly
   * to child elements (label, icon). Child elements use `color: inherit`
   * rather than explicit token values, so this one declaration drives all of them.
   */
  color: var(--ch-color-text);
  cursor: pointer;
  outline: none;
    /* focus ring handled by :focus-visible below */
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-dropdown-item:hover:not(.ch-dropdown-item--disabled) {
  background-color: var(--ch-color-bg-muted);
}

/*
 * Focus ring for keyboard navigation (arrow keys via ChDropdown).
 * Uses inset so it doesn't affect layout or get clipped by overflow:hidden.
 */
.ch-dropdown-item:focus-visible {
  box-shadow: inset 0 0 0 2px var(--ch-color-border-focus);
}
.ch-dropdown-item--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ─── Icon ────────────────────────────────────────────────────────────────── */
.ch-dropdown-item__icon {
  display: flex;
  align-items: center;
    justify-content: center;
  flex-shrink: 0;
  color: var(--ch-color-text-muted);
}

.ch-dropdown-item__icon--danger {
  color: var(--ch-color-danger);
}

.ch-dropdown-item__icon--success {
  color: var(--ch-color-success);
}

.ch-dropdown-item__icon--warning {
  color: var(--ch-color-warning);
}

.ch-dropdown-item__icon--primary {
  color: var(--ch-color-primary);
}

/* ─── Content ─────────────────────────────────────────────────────────────── */
.ch-dropdown-item__content {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-0_5);
  flex: 1;
  min-width: 0;
}

.ch-dropdown-item__label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  /*
     * `inherit` rather than a fixed token — this lets variant hover colors
     * (set on the parent .ch-dropdown-item) flow through to the label text.
     * Without inherit, an explicit color here would block the cascade.
     */
    color: inherit;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ch-dropdown-item__description {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ─── Variant hover styles ────────────────────────────────────────────────── */
/*
 * Setting `color` on the item itself is sufficient — label inherits it.
 * Icon colors are explicitly set above and don't change on hover;
 * the icon already matches its variant color at rest.
 */
.ch-dropdown-item--danger:hover:not(.ch-dropdown-item--disabled) {
  background-color: var(--ch-color-danger-bg);
  color: var(--ch-color-danger-fg);
}

.ch-dropdown-item--success:hover:not(.ch-dropdown-item--disabled) {
  background-color: var(--ch-color-success-bg);
  color: var(--ch-color-success-fg);
}

.ch-dropdown-item--warning:hover:not(.ch-dropdown-item--disabled) {
  background-color: var(--ch-color-warning-bg);
  color: var(--ch-color-warning-fg);
}

.ch-dropdown-item--primary:hover:not(.ch-dropdown-item--disabled) {
  background-color: var(--ch-color-primary-subtle);
  color: var(--ch-color-primary-fg);
}
</style>