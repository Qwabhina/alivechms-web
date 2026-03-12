<script setup lang="ts">
/**
 * @component ChProgress
 * @path /frontend/src/design-system/components/cues/ChProgress.vue
 * @description A progress bar for communicating task completion.
 * Supports determinate (known percentage) and indeterminate (unknown duration)
 * modes, semantic color variants, and an optional label.
 *
 * ─── Determinate vs Indeterminate ────────────────────────────────────────────
 * - Determinate   → you know the percentage (file upload, form steps, bulk ops)
 *                   Pass a `value` between 0–100.
 * - Indeterminate → you know work is happening but not how long it'll take
 *                   (page load, API call with no progress events).
 *                   Omit `value` or pass `null`.
 *
 * ─── Accessibility ───────────────────────────────────────────────────────────
 * Uses the native `<progress>` element for semantics. Screen readers
 * announce progress changes automatically via the role="progressbar" ARiA role
 * that `<progress>` carries. We augment with aria-valuenow/min/max for
 * full compatibility across all assistive technology.
 *
 * @example File upload (determinate)
 * <ChProgress :value="uploadPercent" label="Uploading receipt..." />
 *
 * @example Page loading (indeterminate)
 * <ChProgress />
 *
 * @example Stepper progress
 * <ChProgress :value="(currentStep / totalSteps) * 100" variant="success" size="sm" />
 *
 * @example Danger state (e.g. storage near limit)
 * <ChProgress :value="92" variant="danger" show-value />
 */

import { computed } from 'vue'

type Variant = 'primary' | 'success' | 'warning' | 'danger' | 'info'
type Size = 'xs' | 'sm' | 'md' | 'lg'

interface Props {
  /**
   * Progress percentage: 0–100.
   * Omit or pass null for indeterminate mode.
   */
  value?: number | null

  /** Color variant. Default: 'primary' */
  variant?: Variant

  /** Bar height. Default: 'md' */
  size?: Size

  /** Accessible label describing what is progressing */
  label?: string

  /**
   * Shows the numeric value as text to the right of the bar.
   * Only meaningful in determinate mode.
   */
  showValue?: boolean

  /** Rounds the filled bar and track to pill shape. Default: true */
  rounded?: boolean

  /**
   * Adds a subtle animated stripe pattern to the filled bar.
   * Useful for in-progress states where you want extra visual emphasis.
   */
  striped?: boolean

  /**
   * Animates the stripes. Only effective when `striped` is also true.
   * Provides extra motion cue that the task is actively running.
   */
  animated?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  value: null,
  variant: 'primary',
  size: 'md',
  showValue: false,
  rounded: true,
  striped: false,
  animated: false,
})

/** True when a concrete percentage is available */
const isDeterminate = computed(() => props.value !== null && props.value !== undefined)

/** Clamps value to 0–100 range defensively */
const clampedValue = computed(() =>
  isDeterminate.value ? Math.min(100, Math.max(0, props.value!)) : 0
)

/** Formatted value string for display and aria */
const valueLabel = computed(() =>
  isDeterminate.value ? `${Math.round(clampedValue.value)}%` : ''
)
</script>

<template>
  <div class="ch-progress" :class="[`ch-progress--${size}`]" :aria-label="label">
    <!--
      Label row — shows the descriptive label on the left and the
      numeric value on the right (when showValue is true).
      Only rendered when either label or showValue is provided.
    -->
    <div v-if="label || showValue" class="ch-progress__header">
      <span v-if="label" class="ch-progress__label">{{ label }}</span>
      <span v-if="showValue && isDeterminate" class="ch-progress__value">
        {{ valueLabel }}
      </span>
    </div>

    <!--
      Track — the full-width container of the bar.
      `overflow: hidden` ensures the filled bar can't escape the rounded corners.
    -->
    <div class="ch-progress__track" :class="{ 'ch-progress__track--rounded': rounded }" role="progressbar"
      :aria-valuenow="isDeterminate ? clampedValue : undefined" :aria-valuemin="isDeterminate ? 0 : undefined"
      :aria-valuemax="isDeterminate ? 100 : undefined" :aria-valuetext="isDeterminate ? valueLabel : 'Loading'"
      :aria-label="label ?? 'Progress'">
      <!--
        Fill bar — its width is driven by the clamped value percentage.
        In indeterminate mode, the ch-progress-indeterminate keyframe
        (defined in animations.css) animates the width and position instead.

        Striped pattern is a repeating diagonal gradient overlay.
        The gradient is composited ON TOP of the variant background color
        via a background-image + background-color combination.
      -->
      <div class="ch-progress__fill" :class="[
        `ch-progress__fill--${variant}`,
        { 'ch-progress__fill--indeterminate': !isDeterminate },
        { 'ch-progress__fill--striped': striped },
        { 'ch-progress__fill--animated': striped && animated },
        { 'ch-progress__fill--rounded': rounded },
      ]" :style="isDeterminate ? { width: `${clampedValue}%` } : {}"></div>
    </div>
  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-progress {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1_5);
  width: 100%;
}

/* ─── Header row ──────────────────────────────────────────────────────────── */
.ch-progress__header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.ch-progress__label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text-muted);
}

.ch-progress__value {
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-semibold);
  font-family: var(--ch-font-mono);
  color: var(--ch-color-text-muted);
}

/* ─── Track ───────────────────────────────────────────────────────────────── */
.ch-progress__track {
  width: 100%;
  overflow: hidden;
  /* clips fill bar to track bounds */
  background-color: var(--ch-color-bg-muted);
}

.ch-progress__track--rounded {
  border-radius: var(--ch-radius-full);
}

/* ─── Size — track heights ────────────────────────────────────────────────── */
.ch-progress--xs .ch-progress__track {
  height: 4px;
}

.ch-progress--sm .ch-progress__track {
  height: 6px;
}

.ch-progress--md .ch-progress__track {
  height: 8px;
}

.ch-progress--lg .ch-progress__track {
  height: 12px;
}

/* ─── Fill ────────────────────────────────────────────────────────────────── */
.ch-progress__fill {
  height: 100%;
  transition: width var(--ch-duration-normal) var(--ch-ease-out);
}

.ch-progress__fill--rounded {
  border-radius: var(--ch-radius-full);
}

/* ─── Variant colors ──────────────────────────────────────────────────────── */
.ch-progress__fill--primary {
  background-color: var(--ch-color-primary);
}

.ch-progress__fill--success {
  background-color: var(--ch-color-success);
}

.ch-progress__fill--warning {
  background-color: var(--ch-color-warning);
}

.ch-progress__fill--danger {
  background-color: var(--ch-color-danger);
}

.ch-progress__fill--info {
  background-color: var(--ch-color-info);
}

/* ─── Indeterminate mode ──────────────────────────────────────────────────── */
/*
 * The fill bar is positioned absolutely inside the track so the
 * ch-progress-indeterminate keyframe can animate both its position (left)
 * and width simultaneously, producing the classic "traveling bar" effect.
 */
.ch-progress__fill--indeterminate {
  position: absolute;
  animation: ch-progress-indeterminate 1.4s var(--ch-ease-in-out) infinite;
}

/* Make the track relative so absolute positioning works for the fill */
.ch-progress__track:has(.ch-progress__fill--indeterminate) {
  position: relative;
}

/* ─── Striped overlay ─────────────────────────────────────────────────────── */
/*
 * A 45° repeating-linear-gradient of translucent white stripes.
 * background-size controls stripe density — smaller = tighter stripes.
 * The stripe color is semi-transparent so it composites over any variant color.
 */
.ch-progress__fill--striped {
  background-image: repeating-linear-gradient(45deg,
      rgba(255, 255, 255, 0.15) 0px,
      rgba(255, 255, 255, 0.15) 8px,
      transparent 8px,
      transparent 16px);
  background-size: 24px 24px;
}

/* Animated stripes — scrolls the gradient along the bar */
.ch-progress__fill--animated {
  animation: ch-progress-stripes 0.8s linear infinite;
}

@keyframes ch-progress-stripes {
  from {
    background-position: 0 0;
  }

  to {
    background-position: 24px 0;
  }
}
</style>
