<script setup lang="ts">
/**
 * @component ChToast
 * @path /frontend/src/design-system/components/cues/ChToast.vue
 * @description A single toast notification. Renders the icon, title,
 * message, optional action button, and dismiss button for one Toast entry.
 *
 * ─── Relationship to other parts ─────────────────────────────────────────────
 * - useToast      → manages the queue (push, dismiss, pause, resume)
 * - ChToast       → renders a single toast (this file)
 * - ChToastContainer → mounts all active toasts in the correct screen position
 *
 * ChToast is mostly an internal component — consumers interact with useToast()
 * and ChToastContainer, rarely with ChToast directly.
 *
 * ─── Pause on hover ──────────────────────────────────────────────────────────
 * On mouseenter, the auto-dismiss timer is paused so the user can read longer
 * messages or click the action button without racing the timer.
 * On mouseleave, the timer resumes at half-duration.
 * This is handled by forwarding to useToast().pause() / resume().
 */

import { computed } from 'vue'
import { useToast } from '../../composables/useToast'
import type { Toast } from '../../composables/useToast'

interface Props {
  toast: Toast
}

const props = defineProps<Props>()
const { dismiss, pause, resume } = useToast()

// ─── Icon paths ───────────────────────────────────────────────────────────────
// SVG path data for each variant's icon. Inline SVGs avoid a dependency on any
// icon library and keep the toast self-contained.
const ICONS: Record<string, string> = {
  // Checkmark circle
  success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  // Warning triangle with exclamation
  warning: 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
  // X circle
  danger:  'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
  // Info circle
  info:    'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
}

const iconPath = computed(() => ICONS[props.toast.variant] ?? ICONS.info)
</script>

<template>
  <!--
    The toast container div.
    - `role="alert"` for danger/warning: announces immediately to screen readers
    - `role="status"` for success/info: announces politely (waits for silence)
    Both are live regions, but alert is more urgent than status.
  -->
  <div
    class="ch-toast"
    :class="`ch-toast--${toast.variant}`"
    :role="toast.variant === 'danger' || toast.variant === 'warning' ? 'alert' : 'status'"
    :aria-live="toast.variant === 'danger' || toast.variant === 'warning' ? 'assertive' : 'polite'"
    @mouseenter="pause(toast.id)"
    @mouseleave="resume(toast.id)"
  >
    <!-- Variant icon -->
    <div class="ch-toast__icon" aria-hidden="true">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path :d="iconPath" />
      </svg>
    </div>

    <!-- Content: title + message + optional action -->
    <div class="ch-toast__body">
      <p v-if="toast.title" class="ch-toast__title">{{ toast.title }}</p>
      <p class="ch-toast__message">{{ toast.message }}</p>

      <!-- Optional action button -->
      <button
        v-if="toast.action"
        class="ch-toast__action"
        @click="toast.action!.onClick(); dismiss(toast.id)"
      >
        {{ toast.action.label }}
      </button>
    </div>

    <!-- Dismiss button -->
    <button
      class="ch-toast__dismiss"
      aria-label="Dismiss notification"
      @click="dismiss(toast.id)"
    >
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
        <path d="M10.5 3.5l-7 7M3.5 3.5l7 7"
              stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </button>
  </div>
</template>

<style scoped>
/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-toast {
  display:       flex;
  align-items:   flex-start;
  gap:           var(--ch-space-3);
  padding:       var(--ch-space-3_5) var(--ch-space-4);
  border-radius: var(--ch-radius-lg);
  border:        1px solid var(--ch-color-border-strong);
  box-shadow:    var(--ch-shadow-lg);
  min-width:     280px;
  max-width:     420px;
  background:    var(--ch-color-surface);
  transition:    box-shadow var(--ch-duration-fast) var(--ch-ease-out);
  pointer-events: all;
}

/* Lift on hover — gives the user a clear signal that hovering paused the timer */
.ch-toast:hover {
  box-shadow: var(--ch-shadow-xl);
}

/* ─── Variant borders + icon colors ──────────────────────────────────────── */
.ch-toast--success { border-color: var(--ch-color-success); }
.ch-toast--warning { border-color: var(--ch-color-warning); }
.ch-toast--danger  { border-color: var(--ch-color-danger); }
.ch-toast--info    { border-color: var(--ch-color-info); }

/* ─── Icon ────────────────────────────────────────────────────────────────── */
.ch-toast__icon {
  flex-shrink: 0;
  display:     flex;
  align-items: center;
  margin-top:  1px; /* optical alignment with first line of text */
}

.ch-toast--success .ch-toast__icon { color: var(--ch-color-success); }
.ch-toast--warning .ch-toast__icon { color: var(--ch-color-warning); }
.ch-toast--danger  .ch-toast__icon { color: var(--ch-color-danger); }
.ch-toast--info    .ch-toast__icon { color: var(--ch-color-info); }

/* ─── Body ────────────────────────────────────────────────────────────────── */
.ch-toast__body {
  flex:           1;
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-0_5);
  min-width:      0; /* allow text to wrap */
}

.ch-toast__title {
  font-size:   var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color:       var(--ch-color-text);
  line-height: var(--ch-leading-snug);
}

.ch-toast__message {
  font-size:   var(--ch-text-sm);
  color:       var(--ch-color-text-muted);
  line-height: var(--ch-leading-normal);
}

/* ─── Action button ───────────────────────────────────────────────────────── */
.ch-toast__action {
  background:    none;
  border:        none;
  padding:       0;
  margin-top:    var(--ch-space-1);
  font-size:     var(--ch-text-sm);
  font-weight:   var(--ch-font-semibold);
  font-family:   var(--ch-font-sans);
  cursor:        pointer;
  text-decoration: underline;
  text-underline-offset: 2px;
  transition:    opacity var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-toast__action:hover { opacity: 0.75; }

.ch-toast--success .ch-toast__action { color: var(--ch-color-success); }
.ch-toast--warning .ch-toast__action { color: var(--ch-color-warning); }
.ch-toast--danger  .ch-toast__action { color: var(--ch-color-danger); }
.ch-toast--info    .ch-toast__action { color: var(--ch-color-info); }

/* ─── Dismiss button ──────────────────────────────────────────────────────── */
.ch-toast__dismiss {
  flex-shrink:   0;
  background:    none;
  border:        none;
  padding:       var(--ch-space-0_5);
  cursor:        pointer;
  color:         var(--ch-color-text-subtle);
  border-radius: var(--ch-radius-md);
  display:       flex;
  align-items:   center;
  transition:
    color            var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
  margin-top: -2px; /* align with title top */
}

.ch-toast__dismiss:hover {
  color:            var(--ch-color-text);
  background-color: var(--ch-color-bg-muted);
}
</style>
