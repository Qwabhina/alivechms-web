<script setup lang="ts">
/**
 * @component ChSkeleton
 * @path /frontend/src/design-system/components/cues/ChSkeleton.vue
 * @description A shimmer placeholder that mimics the shape of content
 * while it loads. Reduces perceived load time by showing structure
 * before data arrives, preventing jarring layout shifts.
 *
 * ─── Shape variants ──────────────────────────────────────────────────────────
 * - `line`   → horizontal text line (default). Stack multiple for a paragraph.
 * - `block`  → rectangular area — cards, images, chart placeholders.
 * - `circle` → round avatar / icon placeholder.
 * - `badge`  → small pill — for status badges, tags.
 *
 * ─── Usage pattern ───────────────────────────────────────────────────────────
 * Build skeleton layouts that mirror your real content layout.
 * When data arrives, swap the skeletons out with real components.
 * Use `v-if` / `v-else` on a `loading` ref.
 *
 * @example Member list skeleton row
 * <div class="member-row">
 *   <ChSkeleton shape="circle" size="40px" />
 *   <div>
 *     <ChSkeleton shape="line" width="160px" />
 *     <ChSkeleton shape="line" width="100px" size="12px" />
 *   </div>
 * </div>
 *
 * @example Card placeholder
 * <ChSkeleton shape="block" width="100%" height="180px" />
 *
 * @example Repeating list
 * <ChSkeleton v-for="i in 5" :key="i" shape="line"
 *   :width="`${60 + (i % 3) * 15}%`" />
 */

type Shape = 'line' | 'block' | 'circle' | 'badge'

interface Props {
  /** Visual shape of the placeholder. Default: 'line' */
  shape?: Shape
  /**
   * Width — any CSS value. Default: '100%' for line/block, size for circle.
   * e.g. '160px', '60%', '100%'
   */
  width?: string
  /**
   * Height — any CSS value.
   * - line:   defaults to '14px' (matches body text height)
   * - block:  defaults to '80px'
   * - circle: controlled by `size` prop instead
   * - badge:  defaults to '20px'
   */
  height?: string
  /**
   * Diameter for circles. Sets both width and height.
   * e.g. '40px', '32px'
   */
  size?: string
  /**
   * Animation variant:
   * - `shimmer` → sweeping gradient (default, best perceived performance)
   * - `pulse`   → fades in and out (less distracting for dense UIs)
   * - `none`    → static — use in reduced-motion contexts or tests
   */
  animate?: 'shimmer' | 'pulse' | 'none'
}

const props = withDefaults(defineProps<Props>(), {
  shape: 'line',
  animate: 'shimmer',
})

// ─── Resolved dimensions ──────────────────────────────────────────────────────
// Build the inline style object for the skeleton element.
// Each shape has sensible defaults that match typical content dimensions.
function resolveStyle(props: Props): Record<string, string> {
  const style: Record<string, string> = {}

  if (props.shape === 'circle') {
    const d = props.size ?? '40px'
    style.width = d
    style.height = d
  } else {
    // Width
    style.width = props.width ?? '100%'

    // Height per shape
    if (props.height) {
      style.height = props.height
    } else if (props.shape === 'line') style.height = '14px'
    else if (props.shape === 'block') style.height = '80px'
    else if (props.shape === 'badge') style.height = '20px'
  }

  return style
}
</script>

<template>
  <!--
    `aria-hidden="true"` — skeletons are purely visual placeholders.
    Screen readers don't need to announce them; they should announce
    the real content once it loads.
  -->
  <div class="ch-skeleton" :class="[
    `ch-skeleton--${shape}`,
    animate !== 'none' ? `ch-skeleton--${animate}` : '',
  ]" :style="resolveStyle(props)" aria-hidden="true"></div>
</template>

<style scoped>
/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-skeleton {
  display: block;
  background: var(--ch-color-bg-muted);
  flex-shrink: 0;
  /* Prevent margin collapse when stacking line skeletons */
  overflow: hidden;
  position: relative;
}

/* ─── Shapes ──────────────────────────────────────────────────────────────── */
.ch-skeleton--line {
  border-radius: var(--ch-radius-sm);
}

.ch-skeleton--block {
  border-radius: var(--ch-radius-sm);
}

.ch-skeleton--badge {
  border-radius: var(--ch-radius-sm);
}

.ch-skeleton--circle {
  border-radius: var(--ch-radius-sm);
}

/* ─── Shimmer animation ───────────────────────────────────────────────────── */
/*
 * A pseudo-element carries the shimmer gradient and slides across
 * the skeleton. Using ::after instead of background-animation avoids
 * repainting the entire element on every frame — only the pseudo-element
 * is composited, which keeps it on the GPU.
 */
.ch-skeleton--shimmer {
  overflow: hidden;
}

.ch-skeleton--shimmer::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
      transparent 0%,
      var(--ch-color-bg-subtle) 50%,
      transparent 100%);
  background-size: 200% 100%;
  animation: ch-shimmer 1.5s var(--ch-ease-in-out) infinite;
}

/* ─── Pulse animation ─────────────────────────────────────────────────────── */
.ch-skeleton--pulse {
  animation: ch-pulse 1.8s var(--ch-ease-in-out) infinite;
}
</style>
