<script setup lang="ts">
/**
 * @component ChAlert
 * @path /frontend/src/design-system/components/cues/ChAlert.vue
 * @description A persistent inline alert / banner for displaying important
 * messages that require the user's attention.
 *
 * Unlike `ChToast` (ephemeral, auto-dismissing), `ChAlert` stays visible
 * until explicitly dismissed or until the condition it describes is resolved.
 *
 * ─── Use cases ───────────────────────────────────────────────────────────────
 * - Form-level validation errors ("Please fix 3 errors below")
 * - Page-level warnings ("Your subscription expires in 5 days")
 * - System notices ("Scheduled maintenance on Sunday 2am–4am")
 * - Success confirmation ("Member profile updated successfully")
 *
 * ─── When to use ChAlert vs ChToast ──────────────────────────────────────────
 * ChAlert  → persistent, inline, within page flow, user must see it
 * ChToast  → ephemeral, floating, auto-disappears, supplemental feedback
 *
 * @example Info alert (non-dismissible)
 * <ChAlert variant="info" title="Tip">
 *   You can import members from a CSV file using the bulk import tool.
 * </ChAlert>
 *
 * @example Danger alert (dismissible)
 * <ChAlert variant="danger" title="Payment Failed" dismissible @dismiss="handleDismiss">
 *   The transaction could not be completed. Please try a different payment method.
 * </ChAlert>
 *
 * @example Success with icon slot
 * <ChAlert variant="success">
 *   <template #icon><CheckCircleIcon /></template>
 *   Member added to the directory.
 * </ChAlert>
 */

import { computed } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

type Variant = 'info' | 'success' | 'warning' | 'danger'

interface Props {
  /** Visual style variant — drives colors and default icon. */
  variant?: Variant

  /** Optional bold title line above the description text. */
  title?: string

  /**
   * When true, shows a close button that emits `dismiss`.
   * Default: false
   */
  dismissible?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant:     'info',
  dismissible: false,
})

const emit = defineEmits<{
  /** Fired when the user clicks the dismiss button. */
  dismiss: []
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

const rootClasses = computed(() => [
  'ch-alert',
  `ch-alert--${props.variant}`,
])

/**
 * Accessible role: "alert" for danger/warning (assertive announcement),
 * "status" for info/success (polite announcement).
 */
const ariaRole = computed(() =>
  props.variant === 'danger' || props.variant === 'warning' ? 'alert' : 'status'
)
</script>

<template>
  <div :class="rootClasses" :role="ariaRole">
    <!-- Icon slot — provide your own icon component, or omit for the default SVG -->
    <div class="ch-alert__icon" aria-hidden="true">
      <slot name="icon">
        <!-- Default info icon -->
        <svg v-if="variant === 'info'" width="18" height="18" viewBox="0 0 18 18" fill="none">
          <circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.4"/>
          <path d="M9 8v4M9 6h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <!-- Success checkmark -->
        <svg v-else-if="variant === 'success'" width="18" height="18" viewBox="0 0 18 18" fill="none">
          <circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.4"/>
          <path d="M6 9l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <!-- Warning triangle -->
        <svg v-else-if="variant === 'warning'" width="18" height="18" viewBox="0 0 18 18" fill="none">
          <path d="M9 2L1.5 16h15L9 2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" fill="none"/>
          <path d="M9 7v4M9 13h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <!-- Danger X-circle -->
        <svg v-else width="18" height="18" viewBox="0 0 18 18" fill="none">
          <circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.4"/>
          <path d="M6.5 6.5l5 5M11.5 6.5l-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </slot>
    </div>

    <!-- Content: title + description -->
    <div class="ch-alert__content">
      <div v-if="title" class="ch-alert__title">{{ title }}</div>
      <div class="ch-alert__description">
        <slot />
      </div>
    </div>

    <!-- Dismiss button -->
    <button
      v-if="dismissible"
      class="ch-alert__dismiss"
      aria-label="Dismiss alert"
      type="button"
      @click="emit('dismiss')"
    >
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
        <path d="M10.5 3.5l-7 7M3.5 3.5l7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </button>
  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-alert {
  display:       flex;
  align-items:   flex-start;
  gap:           var(--ch-space-3);
  padding:       var(--ch-space-3) var(--ch-space-4);
  border-radius: var(--ch-radius-lg);
  border:        1px solid transparent;
  font-family:   var(--ch-font-sans);
  font-size:     var(--ch-text-sm);
  line-height:   var(--ch-leading-relaxed);
}

/* ─── Variant colors ──────────────────────────────────────────────────────── */
.ch-alert--info {
  background-color: var(--ch-color-info-bg);
  border-color:     var(--ch-color-info);
  color:            var(--ch-color-info-fg);
}

.ch-alert--success {
  background-color: var(--ch-color-success-bg);
  border-color:     var(--ch-color-success);
  color:            var(--ch-color-success-fg);
}

.ch-alert--warning {
  background-color: var(--ch-color-warning-bg);
  border-color:     var(--ch-color-warning);
  color:            var(--ch-color-warning-fg);
}

.ch-alert--danger {
  background-color: var(--ch-color-danger-bg);
  border-color:     var(--ch-color-danger);
  color:            var(--ch-color-danger-fg);
}

/* ─── Icon ────────────────────────────────────────────────────────────────── */
.ch-alert__icon {
  flex-shrink:     0;
  display:         flex;
  align-items:     center;
  justify-content: center;
  margin-top:      1px; /* optical alignment with first line of text */
}

/* ─── Content ─────────────────────────────────────────────────────────────── */
.ch-alert__content {
  flex: 1;
  min-width: 0; /* allow text truncation */
}

.ch-alert__title {
  font-weight:   var(--ch-font-semibold);
  margin-bottom: var(--ch-space-0_5);
}

.ch-alert__description {
  opacity: 0.9;
}

/* ─── Dismiss button ──────────────────────────────────────────────────────── */
.ch-alert__dismiss {
  flex-shrink:  0;
  background:   none;
  border:       none;
  padding:      var(--ch-space-1);
  border-radius: var(--ch-radius-md);
  cursor:       pointer;
  color:        currentColor;
  opacity:      0.6;
  display:      flex;
  align-items:  center;
  transition:   opacity var(--ch-duration-fast) var(--ch-ease-out),
                background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-alert__dismiss:hover {
  opacity: 1;
  background-color: rgba(0, 0, 0, 0.06);
}
</style>
