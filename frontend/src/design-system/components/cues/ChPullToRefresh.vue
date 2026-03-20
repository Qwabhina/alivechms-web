<script setup lang="ts">
/**
 * @component ChPullToRefresh
 * @path /frontend/src/design-system/components/cues/ChPullToRefresh.vue
 * @description Wraps any scrollable content and adds a pull-down-to-refresh
 * gesture on touch devices. Renders a pull indicator above the content that
 * shows pull progress, a spinner during refresh, and a checkmark on completion.
 *
 * ─── Setup ───────────────────────────────────────────────────────────────────
 * 1. Wrap your scrollable content in ChPullToRefresh
 * 2. Pass an async `onRefresh` function — it runs when the user releases
 *    after pulling past the threshold
 * 3. The component handles all touch tracking, animation, and cleanup
 *
 * ─── Indicator anatomy ───────────────────────────────────────────────────────
 * The indicator is absolutely positioned above the content. Its visibility
 * is controlled by translating it into view as the user pulls.
 *
 * Phases:
 *   idle        → indicator hidden (translateY: -100%)
 *   pulling     → indicator slides in, shows pull-down arrow + "Pull to refresh"
 *   ready       → arrow rotates 180° + "Release to refresh"
 *   refreshing  → spinner replaces arrow, indicator stays open at threshold
 *   completing  → checkmark briefly shown, then indicator slides back up
 *
 * ─── Non-touch devices ───────────────────────────────────────────────────────
 * Pull-to-refresh is a mobile pattern. On desktop, the gesture simply never
 * fires (no touch events). The component still renders correctly — it just
 * stays idle. You can show a manual refresh button via the `#actions` slot.
 *
 * @example Member list with pull-to-refresh
 * <ChPullToRefresh :on-refresh="fetchMembers">
 *   <MemberList :members="members" />
 * </ChPullToRefresh>
 *
 * @example With custom threshold and a manual refresh button fallback
 * <ChPullToRefresh :on-refresh="fetchData" :threshold="80">
 *   <template #actions="{ refresh, isRefreshing }">
 *     <ChButton variant="ghost" size="sm" :loading="isRefreshing"
 *               @click="refresh">Refresh</ChButton>
 *   </template>
 *   <DataTable :rows="rows" />
 * </ChPullToRefresh>
 */

import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePullToRefresh } from '../../composables/usePullToRefresh'

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
   /**
    * Async function called when the pull gesture completes.
    * The spinner stays visible until the Promise resolves.
    */
   onRefresh: () => Promise<void>

   /**
    * Pull distance (px, after resistance) to trigger a refresh.
    * Default: 64
    */
   threshold?: number

   /**
    * Maximum pull distance before the indicator stops moving.
    * Default: 120
    */
   maxPull?: number

   /**
    * Resistance factor (0–1). Lower = heavier feel.
    * Default: 0.4
    */
   resistance?: number

   /**
    * Duration (ms) the "done" checkmark is shown before snapping back.
    * Default: 400
    */
   completionDelay?: number

   /**
    * Disables the pull gesture entirely (e.g. when data is loading elsewhere).
    */
   disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
   threshold: 64,
   maxPull: 120,
   resistance: 0.4,
   completionDelay: 400,
   disabled: false,
})

// ─── Composable ───────────────────────────────────────────────────────────────
const { pullDistance, phase, attach, triggerRefresh } = usePullToRefresh({
   onRefresh: props.onRefresh,
   threshold: props.threshold,
   maxPull: props.maxPull,
   resistance: props.resistance,
   completionDelay: props.completionDelay,
})

// ─── Refs ─────────────────────────────────────────────────────────────────────
const containerRef = ref<HTMLElement | null>(null)
let detach: (() => void) | null = null

onMounted(() => {
   if (!containerRef.value || props.disabled) return
   detach = attach(containerRef.value)
})

onUnmounted(() => { detach?.() })

// ─── Derived display values ───────────────────────────────────────────────────

/**
 * How far (px) the indicator is translated into view.
 * The indicator's natural position is fully above the content (offset = -height).
 * We translate it down by `pullDistance` to reveal it.
 * During refreshing/completing, we lock it to the threshold position.
 */
const indicatorHeight = 56 // px — matches .ch-ptr__indicator height in CSS

const indicatorTranslateY = computed(() => {
   if (phase.value === 'idle') return -indicatorHeight
   return Math.min(pullDistance.value - indicatorHeight, 0)
})

/**
 * How far the content is pushed down to make room for the indicator.
 * Matches the indicator translation so the content appears to be
 * pushed down by the indicator rather than sliding under it.
 */
const contentTranslateY = computed(() => {
   if (phase.value === 'idle') return 0
   // Clamp so content never moves more than the indicator height
   return Math.min(pullDistance.value, indicatorHeight)
})

/**
 * The pull progress as a 0–1 fraction for the arc indicator and arrow opacity.
 * Reaches 1.0 exactly at the threshold.
 */
const pullProgress = computed(() =>
   Math.min(pullDistance.value / props.threshold, 1)
)

/**
 * Hint text shown in the indicator beneath the icon.
 */
const hintText = computed(() => {
   switch (phase.value) {
      case 'pulling': return 'Pull to refresh'
      case 'ready': return 'Release to refresh'
      case 'refreshing': return 'Refreshing…'
      case 'completing': return 'Done'
      default: return ''
   }
})

/**
 * SVG arc path for the progress ring drawn around the pull icon.
 * Uses a circular arc that fills from 0° to 360° as pullProgress goes 0 → 1.
 *
 * SVG arc formula:
 *   Start point fixed at 12 o'clock (cx, cy - r).
 *   End point calculated by rotating around the circle by (progress × 360°).
 *   `large-arc-flag` = 1 when progress > 0.5 (arc spans more than 180°).
 */
const arcPath = computed(() => {
   const cx = 18, cy = 18, r = 14
   const progress = pullProgress.value
   if (progress <= 0) return ''
   if (progress >= 0.99) {
      // Full circle — SVG can't draw a complete arc with a single A command
      return `M ${cx} ${cy - r} A ${r} ${r} 0 1 1 ${cx - 0.01} ${cy - r}`
   }
   const angle = progress * 2 * Math.PI - Math.PI / 2 // start from top
   const endX = cx + r * Math.cos(angle)
   const endY = cy + r * Math.sin(angle)
   const large = progress > 0.5 ? 1 : 0
   return `M ${cx} ${cy - r} A ${r} ${r} 0 ${large} 1 ${endX.toFixed(2)} ${endY.toFixed(2)}`
})
</script>

<template>
   <div class="ch-ptr">

      <!--
      ── Pull indicator ──────────────────────────────────────────────────────
      Positioned absolutely above the scroll container.
      Translated into view as the user pulls.
      CSS `transition` on transform creates the smooth snap-back animation.
    -->
      <div class="ch-ptr__indicator" :class="[
         `ch-ptr__indicator--${phase}`,
         { 'ch-ptr__indicator--snap': phase === 'idle' || phase === 'completing' },
      ]" :style="{ transform: `translateY(${indicatorTranslateY}px)` }" aria-hidden="true">
         <!-- Icon area: pull arc → spinner → checkmark -->
         <div class="ch-ptr__icon-wrap">

            <!-- Phase: pulling / ready — SVG with progress arc + arrow -->
            <svg v-if="phase === 'pulling' || phase === 'ready'" class="ch-ptr__arc-svg" width="36" height="36"
               viewBox="0 0 36 36" fill="none">
               <!-- Track ring -->
               <circle cx="18" cy="18" r="14" stroke="currentColor" stroke-opacity="0.15" stroke-width="2" />

               <!-- Progress arc — grows from 0 to full circle as user pulls -->
               <path v-if="arcPath" :d="arcPath" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                  fill="none" />

               <!-- Down arrow — rotates 180° in 'ready' phase to signal "release" -->
               <g class="ch-ptr__arrow" :class="{ 'ch-ptr__arrow--flipped': phase === 'ready' }"
                  :style="{ transformOrigin: '18px 18px' }">
                  <path d="M18 11v14M12 19l6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                     stroke-linejoin="round" />
               </g>
            </svg>

            <!--
          Phase: refreshing — animated spinner arc.
          Reuses the same SVG dimensions so the transition between
          pull-arc and spinner doesn't cause a layout shift.
        -->
            <svg v-else-if="phase === 'refreshing'" class="ch-ptr__spinner-svg" width="36" height="36"
               viewBox="0 0 36 36" fill="none">
               <!-- Faint track -->
               <circle cx="18" cy="18" r="14" stroke="currentColor" stroke-opacity="0.15" stroke-width="2" />
               <!-- Spinning arc — ch-spin keyframe from animations.css -->
               <circle cx="18" cy="18" r="14" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                  :stroke-dasharray="`${Math.PI * 28 * 0.75} ${Math.PI * 28 * 0.25}`" />
            </svg>

            <!-- Phase: completing — checkmark -->
            <svg v-else-if="phase === 'completing'" class="ch-ptr__check-svg" width="36" height="36" viewBox="0 0 36 36"
               fill="none">
               <circle cx="18" cy="18" r="14" stroke="currentColor" stroke-width="2" />
               <path d="M11 18l5 5 9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                  stroke-linejoin="round" />
            </svg>
         </div>

         <!-- Hint text -->
         <span class="ch-ptr__hint">{{ hintText }}</span>
      </div>

      <!--
      ── Scroll container ────────────────────────────────────────────────────
      This is the element that receives touch events.
      Translated down slightly while pulling to visually reveal the indicator.
      `overflow-y: auto` makes it scrollable on touch devices.
    -->
      <div ref="containerRef" class="ch-ptr__scroll"
         :class="{ 'ch-ptr__scroll--snap': phase === 'idle' || phase === 'completing' }"
         :style="{ transform: `translateY(${contentTranslateY}px)` }">
         <slot />
      </div>

      <!--
      ── Optional actions slot ───────────────────────────────────────────────
      Provides a manual "Refresh" button or other controls.
      Receives { refresh, isRefreshing } so the parent can wire a button.
    -->
      <slot name="actions" :refresh="triggerRefresh" :is-refreshing="phase === 'refreshing'" />

   </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-ptr {
   position: relative;
   width: 100%;
   height: 100%;
   overflow: hidden;
   /* clips the indicator when it's above the viewport */
}

/* ─── Pull indicator ──────────────────────────────────────────────────────── */
.ch-ptr__indicator {
   position: absolute;
   top: 0;
   left: 0;
   right: 0;
   height: 56px;
   display: flex;
   align-items: center;
   justify-content: center;
   gap: var(--ch-space-2);
   color: var(--ch-color-primary);
   background: var(--ch-color-surface);
   border-bottom: 1px solid var(--ch-color-border);
   box-shadow: var(--ch-shadow-sm);
   z-index: 10;
   /*
   * No transition by default — we move with the finger in real time.
   * Transition is enabled only during snap-back (idle / completing phases)
   * via the --snap modifier class below.
   */
}

/* Snap-back animation — smooth spring when releasing or resetting */
.ch-ptr__indicator--snap {
   transition: transform var(--ch-duration-slow) var(--ch-ease-spring);
}

/* Colour variants per phase */
.ch-ptr__indicator--completing {
   color: var(--ch-color-success);
}

.ch-ptr__indicator--refreshing {
   color: var(--ch-color-primary);
}

/* ─── Icon wrapper ────────────────────────────────────────────────────────── */
.ch-ptr__icon-wrap {
   position: relative;
   width: 36px;
   height: 36px;
   display: flex;
   align-items: center;
   justify-content: center;
}

/* ─── Progress arc SVG ────────────────────────────────────────────────────── */
.ch-ptr__arc-svg {
   transition: opacity var(--ch-duration-fast) var(--ch-ease-out);
}

/* ─── Arrow (inside the arc SVG) ─────────────────────────────────────────── */
/*
 * Rotates 180° when the pull crosses the threshold ('ready' phase)
 * to signal that releasing will trigger the refresh.
 */
.ch-ptr__arrow {
   transition: transform var(--ch-duration-fast) var(--ch-ease-spring);
}

.ch-ptr__arrow--flipped {
   transform: rotate(180deg);
}

/* ─── Spinner SVG ─────────────────────────────────────────────────────────── */
/*
 * The ch-spin keyframe is defined in animations.css and rotates
 * the SVG element 360° continuously.
 */
.ch-ptr__spinner-svg {
   animation: ch-spin 0.75s linear infinite;
   transform-origin: center;
}

/* ─── Checkmark SVG ───────────────────────────────────────────────────────── */
.ch-ptr__check-svg {
   animation: ch-scale-in var(--ch-duration-fast) var(--ch-ease-spring) both;
}

@keyframes ch-scale-in {
   from {
      transform: scale(0.6);
      opacity: 0;
   }

   to {
      transform: scale(1);
      opacity: 1;
   }
}

/* ─── Hint text ───────────────────────────────────────────────────────────── */
.ch-ptr__hint {
   font-size: var(--ch-text-xs);
   font-weight: var(--ch-font-medium);
   color: var(--ch-color-text-muted);
   white-space: nowrap;
   font-family: var(--ch-font-sans);
}

.ch-ptr__indicator--completing .ch-ptr__hint {
   color: var(--ch-color-success);
}

/* ─── Scroll container ────────────────────────────────────────────────────── */
.ch-ptr__scroll {
   width: 100%;
   height: 100%;
   overflow-y: auto;
   /* Prevent the browser adding its own pull-to-refresh on Chrome/Android */
   overscroll-behavior-y: contain;
   /* Hardware-accelerate the content layer for smoother 60fps dragging */
   will-change: transform;
}

/* Snap-back animation on the content (mirrors the indicator's snap) */
.ch-ptr__scroll--snap {
   transition: transform var(--ch-duration-slow) var(--ch-ease-spring);
}
</style>