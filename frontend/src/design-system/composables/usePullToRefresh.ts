/**
 * @file usePullToRefresh.ts
 * @path /frontend/src/design-system/composables/usePullToRefresh.ts
 * @description Composable that tracks a pull-down touch gesture on a scroll
 * container and fires a callback when the user pulls past a threshold.
 *
 * ─── How it works ────────────────────────────────────────────────────────────
 * 1. `touchstart`  → record the finger's Y position
 * 2. `touchmove`   → if the container is scrolled to the top, track how far
 *                    the finger has moved down. Apply a resistance factor so
 *                    the indicator moves slower than the finger (feels natural).
 *                    Call `preventDefault()` on the native scroll event so the
 *                    browser doesn't also bounce/scroll while we handle it.
 * 3. `touchend`    → if pull distance exceeded `threshold`, set state to
 *                    'refreshing' and call the `onRefresh` callback.
 *                    Otherwise snap the indicator back to 0 ('idle').
 *
 * ─── Resistance ──────────────────────────────────────────────────────────────
 * Raw touch distance is multiplied by `resistance` (default 0.4) before being
 * exposed as `pullDistance`. This means a 200px finger drag produces an 80px
 * indicator movement — it feels like the content has physical weight and makes
 * it harder to accidentally trigger a refresh on a small swipe.
 *
 * ─── Passive event listeners ─────────────────────────────────────────────────
 * `touchmove` must be registered with `{ passive: false }` so we can call
 * `preventDefault()` to stop the browser's native overscroll behaviour.
 * Without this, both our handler AND the browser's rubber-band effect fire,
 * which looks broken. `touchstart` and `touchend` stay passive for performance.
 *
 * ─── SSR safety ──────────────────────────────────────────────────────────────
 * All DOM operations are inside `onMounted`/`onUnmounted` so the composable
 * is safe to use in SSR environments (Nuxt, etc.) — it simply does nothing
 * on the server.
 *
 * @example Basic usage (see ChPullToRefresh for the full component wrapper)
 * const { pullDistance, phase, attach } = usePullToRefresh({
 *   onRefresh: async () => { await fetchData() },
 * })
 *
 * // Attach to a scroll container ref
 * onMounted(() => attach(containerRef.value))
 */

import { ref, readonly } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * The lifecycle phase of a pull-to-refresh interaction.
 *
 * idle        → no interaction happening
 * pulling     → user is actively dragging down (not yet past threshold)
 * ready       → drag has passed the threshold; release will trigger refresh
 * refreshing  → onRefresh() is running; show the spinner
 * completing  → refresh finished; brief pause before snapping back to idle
 */
export type PullPhase = 'idle' | 'pulling' | 'ready' | 'refreshing' | 'completing'

export interface UsePullToRefreshOptions {
   /**
    * Async function called when the user completes a pull gesture.
    * The component stays in 'refreshing' state until this Promise resolves.
    */
   onRefresh: () => Promise<void>

   /**
    * Pull distance (in px, after resistance) required to trigger a refresh.
    * Default: 64px
    */
   threshold?: number

   /**
    * Maximum pull distance (after resistance) before the indicator stops moving.
    * Prevents an infinitely long pull rubber-band.
    * Default: 120px
    */
   maxPull?: number

   /**
    * Fraction applied to raw touch distance to simulate physical resistance.
    * 0.4 means the indicator moves 40px for every 100px of finger movement.
    * Lower = more resistance. Range: 0–1. Default: 0.4
    */
   resistance?: number

   /**
    * How long (ms) the "completing" phase is shown after refresh resolves,
    * before snapping the indicator back to idle.
    * Gives the user visual confirmation that the refresh finished.
    * Default: 400
    */
   completionDelay?: number

   /**
    * Only fire if the container is scrolled to the very top.
    * Default: true. Set false only for containers that never scroll vertically.
    */
   requireScrollTop?: boolean
}

// ─── Composable ───────────────────────────────────────────────────────────────

export function usePullToRefresh(options: UsePullToRefreshOptions) {
   const {
      onRefresh,
      threshold = 64,
      maxPull = 120,
      resistance: rawResistance = 0.4,
      completionDelay = 400,
      requireScrollTop = true,
   } = options

   // Clamp resistance to [0.01, 1]. A value of 0 would result in zero pull
   // distance (unusable component). Negative values would invert the gesture.
   const resistance = Math.max(0.01, Math.min(1, rawResistance))

   // ── Reactive state ──────────────────────────────────────────────────────────

   /**
    * Current pull distance in px (after resistance applied).
    * Drives the CSS transform on the pull indicator in ChPullToRefresh.
    */
   const pullDistance = ref(0)

   /**
    * The current interaction phase. The component uses this to:
    * - Show/hide the indicator
    * - Swap the spinner for a checkmark on completing
    * - Display hint text ("Pull to refresh" / "Release to refresh")
    */
   const phase = ref<PullPhase>('idle')

   // ── Internal tracking ───────────────────────────────────────────────────────

   let startY = 0   // Y coordinate at touchstart
   let isPulling = false // guard: only pull when started from scroll-top

   // ── Event handlers ──────────────────────────────────────────────────────────

   function onTouchStart(e: TouchEvent) {
      // Don't start a pull if already refreshing, or mid-completion
      if (phase.value === 'refreshing' || phase.value === 'completing') return

      // Only pull when the element is scrolled all the way to the top
      const el = e.currentTarget as HTMLElement
      if (requireScrollTop && el.scrollTop > 0) return

      startY = e.touches[0]!.clientY
      isPulling = true
   }

   function onTouchMove(e: TouchEvent) {
      if (!isPulling) return

      const dy = e.touches[0]!.clientY - startY

      // Only respond to downward drags
      if (dy <= 0) {
         // Moved back up — reset without triggering
         pullDistance.value = 0
         phase.value = 'idle'
         return
      }

      // Prevent the browser's native overscroll / rubber-band effect.
      // IMPORTANT: requires the listener to be registered with { passive: false }.
      e.preventDefault()

      // Apply resistance so the indicator trails behind the finger
      const dampened = Math.min(dy * resistance, maxPull)
      pullDistance.value = dampened

      // Cross the threshold → switch to 'ready' (release will trigger refresh)
      phase.value = dampened >= threshold ? 'ready' : 'pulling'
   }

   function onTouchEnd() {
      if (!isPulling) return
      isPulling = false

      if (phase.value === 'ready') {
         // User pulled far enough and lifted — start refreshing
         triggerRefresh()
      } else {
         // Not far enough — snap back
         snapBack()
      }
   }

   // ── Refresh lifecycle ───────────────────────────────────────────────────────

   async function triggerRefresh() {
      phase.value = 'refreshing'
      // Hold the indicator open at the threshold position while refreshing
      pullDistance.value = threshold

      try {
         await onRefresh()
      } finally {
         // Show 'completing' (checkmark) briefly before hiding the indicator
         phase.value = 'completing'
         setTimeout(snapBack, completionDelay)
      }
   }

   function snapBack() {
      // CSS transition on the component handles the animated slide-up
      pullDistance.value = 0
      // Short delay before resetting phase so the transition plays out first
      setTimeout(() => { phase.value = 'idle' }, 300)
   }

   // ── Attach / detach ─────────────────────────────────────────────────────────

   /**
    * Attaches the touch listeners to a scroll container element.
    * Returns a cleanup function — call it in `onUnmounted` if using manually.
    * ChPullToRefresh handles this automatically.
    */
   function attach(el: HTMLElement): () => void {
      el.addEventListener('touchstart', onTouchStart, { passive: true })
      // passive: false is REQUIRED here so we can call e.preventDefault()
      el.addEventListener('touchmove', onTouchMove, { passive: false })
      el.addEventListener('touchend', onTouchEnd, { passive: true })

      return () => {
         el.removeEventListener('touchstart', onTouchStart)
         el.removeEventListener('touchmove', onTouchMove)
         el.removeEventListener('touchend', onTouchEnd)
      }
   }

   return {
      /** Current pull offset in px — bind to CSS transform in the component */
      pullDistance: readonly(pullDistance),
      /** Current phase — drives indicator icon and hint text */
      phase: readonly(phase),
      /**
       * Attaches touch listeners to a container element.
       * Returns a detach cleanup function.
       */
      attach,
      /**
       * Programmatically trigger a refresh without a gesture.
       * Useful for initial load states or a manual "Refresh" button fallback.
       */
      triggerRefresh,
   }
}