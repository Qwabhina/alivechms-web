<script setup lang="ts">
/**
 * @component ChEmptyState
 * @path /frontend/src/design-system/components/data/ChEmptyState.vue
 * @description A component for displaying empty states when there is no data
 * to show. Provides a consistent, visually appealing way to communicate "no
 * results" and guide users toward next actions.
 *
 * ─── Design decisions ────────────────────────────────────────────────────────
 * - Centered layout with icon, title, description, and optional actions
 * - Multiple preset icon options for common scenarios
 * - Supports custom SVG icons or illustrations
 * - Action buttons for primary and secondary CTAs
 * - Compact mode for inline empty states
 *
 * ─── Accessibility ───────────────────────────────────────────────────────────
 * - Uses `role="status"` for screen reader announcement
 * - Icon has `aria-hidden="true"` (decorative)
 * - Action buttons are keyboard accessible
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic usage
 * <ChEmptyState
 *   title="No members found"
 *   description="Try adjusting your search or filter criteria."
 * />
 *
 * @example With action button
 * <ChEmptyState
 *   title="No members yet"
 *   description="Get started by adding your first member."
 *   action-label="Add Member"
 *   @action="navigateToNewMember"
 * />
 *
 * @example With custom icon
 * <ChEmptyState
 *   title="No notifications"
 *   description="You're all caught up!"
 *   icon="check-circle"
 * />
 *
 * @example With multiple actions
 * <ChEmptyState
 *   title="No events scheduled"
 *   description="Create an event to get started."
 *   primary-action="Create Event"
 *   secondary-action="Import Events"
 *   @primary-action="createEvent"
 *   @secondary-action="importEvents"
 * />
 *
 * @example With custom illustration
 * <ChEmptyState title="Page not found">
 *   <template #icon>
 *     <img src="/illustrations/404.svg" alt="" aria-hidden="true" />
 *   </template>
 *   <template #description>
 *     The page you're looking for doesn't exist.
 *     <RouterLink to="/">Go back home</RouterLink>
 *   </template>
 * </ChEmptyState>
 */

import { computed, useSlots } from 'vue'
import ChButton from '../core/ChButton.vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/** Preset icon options for common empty states */
export type EmptyStateIcon =
  | 'search'         // Search/not found
  | 'inbox'          // Empty inbox
  | 'folder'         // Empty folder
  | 'calendar'       // No events
  | 'users'          // No people
  | 'document'       // No documents
  | 'chart'          // No data
  | 'bell'           // No notifications
  | 'check-circle'   // Success/complete
  | 'star'          // No favorites
  | 'bookmark'      // No bookmarks
  | 'cart'          // Empty cart
  | 'mailbox'       // No messages

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Main heading text */
  title?: string
  /** Descriptive text below the title */
  description?: string
  /** Preset icon name */
  icon?: EmptyStateIcon
  /** Label for the primary action button */
  actionLabel?: string
  /** Label for the secondary action button */
  secondaryActionLabel?: string
  /** Whether the component is in compact mode */
  compact?: boolean
  /** Custom CSS class */
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  description: '',
  icon: 'inbox',
  actionLabel: '',
  secondaryActionLabel: '',
  compact: false,
  class: '',
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  action: []
  secondaryAction: []
}>()

// ─── Slots ────────────────────────────────────────────────────────────────────

const slots = useSlots()

// ─── Computed ─────────────────────────────────────────────────────────────────

const classes = computed(() => [
  'ch-empty-state',
  `ch-empty-state--${props.compact ? 'compact' : 'default'}`,
  props.class,
])

const hasIcon = computed(() => props.icon || !!slots.icon)

const hasActions = computed(() =>
  props.actionLabel || props.secondaryActionLabel || !!slots.actions
)

// ─── Icon paths ──────────────────────────────────────────────────────────────

const iconPaths: Record<EmptyStateIcon, string> = {
  'search': 'M11 19a8 8 0 100-16 8 8 0 000 16zM21 21l-4.35-4.35',
  'inbox': 'M22 12h-6l-2 3h-4l-2-3H2v10h20V12zM5.45 5.11L2 12v2h16l-3.45-6.89A2 2 0 0012.78 4H7.22a2 2 0 00-1.77 1.11z',
  'folder': 'M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2v11z',
  'calendar': 'M19 4H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2zM16 2v4M8 2v4M3 10h18',
  'users': 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
  'document': 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zM14 9V4h5v5h-5z',
  'chart': 'M18 20V10M12 20V4M6 20v-6',
  'bell': 'M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0',
  'check-circle': 'M22 11.08V12a10 10 0 11-5.93-9.14M22 4L12 14.01l-3-3',
  'star': 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
  'bookmark': 'M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2v16z',
  'cart': 'M9 20a1 1 0 100 2 1 1 0 000-2zM7 17h10M20 21a1 1 0 11-2 0 1 1 0 012 0zM1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6',
  'mailbox': 'M4 4h16c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zM12 13l-4-4M12 13l4-4',
}

const currentIconPath = computed(() => iconPaths[props.icon] ?? iconPaths.inbox)
</script>

<template>
  <div :class="classes" role="status" aria-live="polite">
    <!-- Icon slot -->
    <div class="ch-empty-state__icon-wrapper">
      <slot name="icon">
        <svg v-if="hasIcon" class="ch-empty-state__icon" width="48" height="48" viewBox="0 0 48 48" fill="none"
          stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path :d="currentIconPath" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </slot>
    </div>

    <!-- Content -->
    <div class="ch-empty-state__content">
      <h3 v-if="title" class="ch-empty-state__title">{{ title }}</h3>

      <div v-if="description || $slots.description" class="ch-empty-state__description">
        <slot name="description">
          {{ description }}
        </slot>
      </div>

      <!-- Actions -->
      <div v-if="hasActions" class="ch-empty-state__actions">
        <slot name="actions">
          <ChButton v-if="actionLabel" variant="primary" size="md" @click="emit('action')">
            {{ actionLabel }}
          </ChButton>
          <ChButton v-if="secondaryActionLabel" variant="ghost" size="md" @click="emit('secondaryAction')">
            {{ secondaryActionLabel }}
          </ChButton>
        </slot>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--ch-space-12) var(--ch-space-6);
  text-align: center;
}

.ch-empty-state--compact {
  padding: var(--ch-space-8) var(--ch-space-4);
}

/* ─── Icon wrapper ────────────────────────────────────────────────────────── */
.ch-empty-state__icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--ch-space-5);
}

/* ─── Icon ────────────────────────────────────────────────────────────────── */
.ch-empty-state__icon {
  color: var(--ch-color-text-subtle);
  opacity: 0.7;
}

.ch-empty-state--compact .ch-empty-state__icon {
  width: 40px;
  height: 40px;
}

/* ─── Content ─────────────────────────────────────────────────────────────── */
.ch-empty-state__content {
  max-width: 400px;
}

/* ─── Title ───────────────────────────────────────────────────────────────── */
.ch-empty-state__title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0 0 var(--ch-space-2);
}

.ch-empty-state--compact .ch-empty-state__title {
  font-size: var(--ch-text-base);
}

/* ─── Description ─────────────────────────────────────────────────────────── */
.ch-empty-state__description {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  line-height: var(--ch-leading-relaxed);
  margin-bottom: var(--ch-space-5);
}

.ch-empty-state--compact .ch-empty-state__description {
  font-size: var(--ch-text-xs);
  margin-bottom: var(--ch-space-4);
}

/* ─── Actions ─────────────────────────────────────────────────────────────── */
.ch-empty-state__actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--ch-space-3);
  flex-wrap: wrap;
}
</style>