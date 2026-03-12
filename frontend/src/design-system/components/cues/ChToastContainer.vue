<script setup lang="ts">
/**
 * @component ChToastContainer
 * @path /frontend/src/design-system/components/cues/ChToastContainer.vue
 * @description Renders the active toast queue in a fixed screen position.
 * Mount this ONCE in your app shell (App.vue) — it handles everything.
 *
 * ─── Setup (one-time, in App.vue) ────────────────────────────────────────────
 * <template>
 *   <RouterView />
 *   <ChToastContainer />       <!-- bottom-right by default -->
 * </template>
 *
 * ─── Then push toasts from anywhere ──────────────────────────────────────────
 * const toast = useToast()
 * toast.success('Contribution recorded.')
 * toast.error('Could not connect to server.')
 *
 * ─── Position options ────────────────────────────────────────────────────────
 * <ChToastContainer position="top-right" />     (default)
 * <ChToastContainer position="top-left" />
 * <ChToastContainer position="bottom-right" />
 * <ChToastContainer position="bottom-left" />
 * <ChToastContainer position="top-center" />
 * <ChToastContainer position="bottom-center" />
 *
 * ─── Stacking direction ──────────────────────────────────────────────────────
 * Top positions:    new toasts appear below older ones (stack downward)
 * Bottom positions: new toasts appear above older ones (stack upward)
 * This is achieved by reversing the toasts array for bottom positions so
 * the most recent toast is always closest to the corner.
 *
 * ─── Teleport ────────────────────────────────────────────────────────────────
 * The container uses <Teleport to="body"> so it always renders at the
 * document root regardless of where ChToastContainer is placed in the
 * component tree. This guarantees it's above all z-index stacking contexts.
 */

import { computed } from 'vue'
import { useToast } from '../../composables/useToast'
import ChToast      from './ChToast.vue'

// ─── Types ────────────────────────────────────────────────────────────────────
type Position =
  | 'top-right'    | 'top-left'    | 'top-center'
  | 'bottom-right' | 'bottom-left' | 'bottom-center'

interface Props {
  /**
   * Screen corner / edge where toasts appear.
   * Default: 'top-right'
   */
  position?: Position
  /**
   * Maximum number of toasts visible simultaneously.
   * Oldest toasts are hidden (not dismissed) when the limit is exceeded.
   * They become visible as newer toasts are dismissed.
   * Default: 5
   */
  maxVisible?: number
}

const props = withDefaults(defineProps<Props>(), {
  position:   'top-right',
  maxVisible: 5,
})

// ─── State ────────────────────────────────────────────────────────────────────
const { toasts } = useToast()

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * For bottom positions, reverse the display order so the newest toast is
 * always closest to the corner (bottom of the stack).
 * For top positions, oldest toast is at top, newest appears below.
 */
const visibleToasts = computed(() => {
  const isBottom = props.position.startsWith('bottom')
  const list = isBottom ? [...toasts.value].reverse() : toasts.value
  return list.slice(0, props.maxVisible)
})

/**
 * Builds the positioning CSS classes for the container.
 * The container is fixed-position; these classes apply inset values.
 */
const positionClass = computed(() => `ch-toast-container--${props.position}`)
</script>

<template>
  <!--
    Teleport ensures the container renders directly on <body>,
    above all stacking contexts (modals, dropdowns, etc.)
  -->
  <Teleport to="body">
    <div
      class="ch-toast-container"
      :class="positionClass"
      aria-live="off"
      aria-atomic="false"
    >
      <!--
        TransitionGroup animates toasts in and out of the DOM.
        `tag="div"` wraps the list in a div (required by TransitionGroup).
        The enter/leave transition classes map to keyframes in animations.css.

        `move-class` handles the smooth reflow when a toast in the middle
        of the stack is dismissed — remaining toasts slide to fill the gap.
      -->
      <TransitionGroup
        tag="div"
        class="ch-toast-list"
        :class="`ch-toast-list--${position}`"
        enter-active-class="ch-toast-enter"
        leave-active-class="ch-toast-leave"
        move-class="ch-toast-move"
      >
        <ChToast
          v-for="toast in visibleToasts"
          :key="toast.id"
          :toast="toast"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
/* ─── Fixed container ─────────────────────────────────────────────────────── */
/*
 * Fixed to the viewport edge. `pointer-events: none` on the container
 * means clicks pass through the empty space around toasts. Individual
 * ChToast elements reset this to `pointer-events: all` so they remain
 * interactive.
 */
.ch-toast-container {
  position:       fixed;
  z-index:        var(--ch-z-toast);
  pointer-events: none;
  padding:        var(--ch-space-4);
}

/* ─── Position variants ───────────────────────────────────────────────────── */
.ch-toast-container--top-right {
  top: 0; right: 0;
}
.ch-toast-container--top-left {
  top: 0; left: 0;
}
.ch-toast-container--top-center {
  top: 0; left: 50%; transform: translateX(-50%);
}
.ch-toast-container--bottom-right {
  bottom: 0; right: 0;
}
.ch-toast-container--bottom-left {
  bottom: 0; left: 0;
}
.ch-toast-container--bottom-center {
  bottom: 0; left: 50%; transform: translateX(-50%);
}

/* ─── Toast list ──────────────────────────────────────────────────────────── */
.ch-toast-list {
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-2);
  /* Align toasts to match container corner */
  align-items:    flex-end;
}

.ch-toast-list--top-left,
.ch-toast-list--bottom-left { align-items: flex-start; }

.ch-toast-list--top-center,
.ch-toast-list--bottom-center { align-items: center; }

/* ─── Enter / Leave transitions ───────────────────────────────────────────── */
/*
 * Enter: slide in from the right edge + fade in
 * Leave: slide out to the right edge + fade out
 * The `position: absolute` on leave is required by TransitionGroup to let
 * remaining toasts smoothly fill the gap via the move transition.
 */
.ch-toast-enter {
  animation: ch-toast-in var(--ch-duration-normal) var(--ch-ease-spring) both;
}

.ch-toast-leave {
  animation:  ch-toast-out var(--ch-duration-normal) var(--ch-ease-in) both;
  position:   absolute; /* required for smooth gap-fill on remove */
  pointer-events: none;
}

/* Move transition — smoothly repositions remaining toasts when one is removed */
.ch-toast-move {
  transition: transform var(--ch-duration-normal) var(--ch-ease-spring);
}
</style>
