<script setup lang="ts">
/**
 * @component ChSpinner
 * @path /frontend/src/design-system/components/cues/ChSpinner.vue
 * @description An animated SVG loading ring for indicating async activity.
 *
 * ─── When to use which loading indicator ─────────────────────────────────────
 * - ChSpinner   → inline or centered full-page/section loading state
 * - ChSkeleton  → placeholder for content that has a known shape (lists, cards)
 * - ChProgress  → when you know percentage completion (uploads, multi-step)
 * - ChButton's built-in spinner → loading state scoped to a single action
 *
 * ─── SVG ring technique ──────────────────────────────────────────────────────
 * The spinner uses two SVG circles:
 *   1. A faint track circle (full 360° — gives the ring its "rail")
 *   2. An animated arc that rotates via the ch-spin keyframe
 * The arc uses `stroke-dasharray` + `stroke-dashoffset` to render only
 * a partial circle (~75% of circumference), giving the classic spinner look.
 * `stroke-linecap: round` rounds the ends of the arc for polish.
 *
 * @example Inline with text
 * <ChSpinner size="sm" /> Loading members...
 *
 * @example Centered full-section
 * <div class="my-loading-container">
 *   <ChSpinner size="lg" label="Fetching data..." />
 * </div>
 *
 * @example Custom color (via variant)
 * <ChSpinner variant="success" />
 */

// ─── Types ────────────────────────────────────────────────────────────────────
type Size = 'xs' | 'sm' | 'md' | 'lg' | 'xl'
type Variant = 'primary' | 'white' | 'muted' | 'success' | 'danger'

interface Props {
  size?: Size
  variant?: Variant
  /**
   * Accessible label announced to screen readers.
   * If omitted, falls back to "Loading…"
   */
  label?: string
}

withDefaults(defineProps<Props>(), {
  size: 'md',
  variant: 'primary',
  label: 'Loading…',
})

// ─── Size map ─────────────────────────────────────────────────────────────────
// px dimensions for the SVG canvas at each size tier
const SIZE_MAP: Record<Size, number> = {
  xs: 12,
  sm: 16,
  md: 24,
  lg: 36,
  xl: 48,
}

// Stroke width scales with size — thick enough to be visible, not overwhelming
const STROKE_MAP: Record<Size, number> = {
  xs: 2,
  sm: 2,
  md: 2.5,
  lg: 3,
  xl: 3.5,
}
</script>

<template>
  <!--
    role="status" + aria-label: announces the loading state to screen readers
    without interrupting the user (same as aria-live="polite").
    The visible SVG is aria-hidden because the role="status" element
    already communicates the state.
  -->
  <span
    class="ch-spinner"
    :class="[`ch-spinner--${size}`, `ch-spinner--${variant}`]"
    role="status"
    :aria-label="label"
  >
    <svg
      :width="SIZE_MAP[size]"
      :height="SIZE_MAP[size]"
      :viewBox="`0 0 ${SIZE_MAP[size]} ${SIZE_MAP[size]}`"
      fill="none"
      aria-hidden="true"
      class="ch-spinner__svg"
    >
      <!--
        Track circle — faint full ring that acts as the spinner's rail.
        Drawn at 10% opacity so it's visible without competing with the arc.
      -->
      <circle
        :cx="SIZE_MAP[size] / 2"
        :cy="SIZE_MAP[size] / 2"
        :r="(SIZE_MAP[size] - STROKE_MAP[size] * 2) / 2"
        :stroke-width="STROKE_MAP[size]"
        stroke="currentColor"
        stroke-opacity="0.15"
      />

      <!--
        Animated arc — partial circle rendered via stroke-dasharray trick.
        circumference = 2πr. We show ~75% of it (hence * 0.75).
        The gap is the remaining 25%, achieved by setting dashoffset to 0
        and letting the dasharray handle the break.
        The ch-spin keyframe rotates the entire SVG 360° continuously.
      -->
      <circle
        :cx="SIZE_MAP[size] / 2"
        :cy="SIZE_MAP[size] / 2"
        :r="(SIZE_MAP[size] - STROKE_MAP[size] * 2) / 2"
        :stroke-width="STROKE_MAP[size]"
        stroke="currentColor"
        stroke-linecap="round"
        :stroke-dasharray="`${Math.PI * (SIZE_MAP[size] - STROKE_MAP[size] * 2) * 0.75} ${Math.PI * (SIZE_MAP[size] - STROKE_MAP[size] * 2) * 0.25}`"
        stroke-dashoffset="0"
      />
    </svg>
  </span>
</template>

<style scoped>
/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-spinner {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  line-height: 1;
}

/* Rotate the SVG — ch-spin keyframe is defined in animations.css */
.ch-spinner__svg {
  animation: ch-spin 0.75s linear infinite;
  /* Start the arc at the top (12 o'clock) not the right (3 o'clock) */
  transform-origin: center;
}

/* ─── Size ────────────────────────────────────────────────────────────────── */
/* Sizes are handled via the SVG's width/height attrs — no extra CSS needed */

/* ─── Variants ────────────────────────────────────────────────────────────── */
.ch-spinner--primary {
  color: var(--ch-color-primary);
}
.ch-spinner--white {
  color: var(--ch-color-text-inverse);
}
.ch-spinner--muted {
  color: var(--ch-color-text-subtle);
}
.ch-spinner--success {
  color: var(--ch-color-success);
}
.ch-spinner--danger {
  color: var(--ch-color-danger);
}
</style>
