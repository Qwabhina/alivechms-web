<script setup lang="ts">
/**
 * @component ChDropdownItem
 * @path /frontend/src/design-system/components/core/ChDropdownItem.vue
 * @description A single item within a ChDropdown menu. Supports icons,
 * descriptions, disabled state, and visual variants.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example
 * <ChDropdownItem
 *   label="Edit"
 *   :icon="editIconPath"
 *   @click="handleEdit"
 * />
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
  /** Optional icon SVG path */
  icon?: string
  /** Visual variant for emphasis. Default: 'default' */
  variant?: DropdownItemVariant
  /** Whether the item is disabled */
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  description: '',
  icon: '',
  variant: 'default',
  disabled: false,
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

const classes = computed(() => [
  'ch-dropdown-item',
  `ch-dropdown-item--${props.variant}`,
  {
    'ch-dropdown-item--disabled': props.disabled,
  },
])
</script>

<template>
  <div
    :class="classes"
    role="menuitem"
    :aria-disabled="disabled ? 'true' : 'false'"
    @click="!disabled && emit('click', $event)"
  >
    <!-- Icon -->
    <svg
      v-if="icon"
      class="ch-dropdown-item__icon"
      width="16"
      height="16"
      viewBox="0 0 16 16"
      fill="none"
      stroke="currentColor"
      stroke-width="1.5"
      :class="`ch-dropdown-item__icon--${variant}`"
    >
      <path :d="icon" stroke-linecap="round" stroke-linejoin="round" />
    </svg>

    <!-- Content -->
    <div class="ch-dropdown-item__content">
      <span class="ch-dropdown-item__label">{{ label }}</span>
      <span v-if="description" class="ch-dropdown-item__description">{{ description }}</span>
    </div>

    <!-- Custom trailing slot -->
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
  border-radius: var(--ch-radius-none);
  cursor: pointer;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-dropdown-item:hover:not(.ch-dropdown-item--disabled) {
  background-color: var(--ch-color-bg-muted);
}

.ch-dropdown-item--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ─── Icon ────────────────────────────────────────────────────────────────── */
.ch-dropdown-item__icon {
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
  color: var(--ch-color-text);
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

/* ─── Variant styles ──────────────────────────────────────────────────────── */
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
