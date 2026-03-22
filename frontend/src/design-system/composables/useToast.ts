/**
 * @file useToast.ts
 * @path /frontend/src/design-system/composables/useToast.ts
 * @description Composable for managing the application toast notification queue.
 *
 * ─── Architecture ────────────────────────────────────────────────────────────
 * The toast state lives in module-level refs (outside the composable function).
 * This makes them true singletons — every component that calls `useToast()`
 * shares the same queue. This is essential: a service file deep in the app
 * can push a toast and it will appear in the ChToastContainer that's mounted
 * at the app shell level, with no prop-drilling or event buses required.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example From a Vue component
 * const toast = useToast()
 * toast.success('Member saved successfully.')
 * toast.error('Failed to load contributions.')
 *
 * @example With options
 * toast.push({
 *   variant:   'warning',
 *   title:     'Session expiring',
 *   message:   'You will be logged out in 5 minutes.',
 *   duration:  8000,
 *   action:    { label: 'Stay logged in', onClick: refreshSession },
 * })
 *
 * @example From a non-component file (API service, router guard)
 * import { useToast } from '@/design-system'
 * const toast = useToast()
 * toast.error('Unauthorized. Please log in again.')
 */

import { ref, readonly } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/** Semantic intent of the toast notification */
export type ToastVariant = 'success' | 'warning' | 'danger' | 'info'

/** An optional call-to-action button rendered inside the toast */
export interface ToastAction {
  label:   string
  onClick: () => void
}

/** A single toast notification entry */
export interface Toast {
  /** Unique identifier — used as Vue :key and for programmatic dismissal */
  id:        string
  /** Semantic variant — drives icon and color */
  variant:   ToastVariant
  /** Short bold heading (optional). e.g. "Saved" or "Error" */
  title?:    string
  /** Main message body */
  message:   string
  /**
   * Auto-dismiss duration in milliseconds.
   * Default: 4500ms. Set to 0 to require manual dismissal.
   */
  duration:  number
  /** Optional call-to-action rendered inside the toast */
  action?:   ToastAction
  /**
   * Internal: the setTimeout ID for auto-dismiss.
   * Stored here so the timer can be cancelled on hover (pause-on-hover)
   * and restarted when the user moves away.
   */
  timerId?:  ReturnType<typeof setTimeout>
}

/** Options passed to push() / convenience methods */
export type ToastOptions = Omit<Toast, 'id' | 'timerId'>

// ─── Module-level singleton state ─────────────────────────────────────────────
/**
 * Declared at module scope (outside the composable function) so all callers
 * share the same reactive array regardless of where they call useToast().
 * This is the Vue 3 pattern for shared singleton state without Pinia.
 */
const toasts = ref<Toast[]>([])

/** Auto-incrementing counter for unique IDs */
let _idCounter = 0

// ─── Core queue functions ─────────────────────────────────────────────────────

/**
 * Adds a new toast to the queue and schedules its auto-dismissal.
 * Returns the generated ID so callers can dismiss it programmatically.
 */
function push(options: ToastOptions): string {
  const id = `toast-${++_idCounter}`

  const toast: Toast = {
    id,
    variant:  options.variant  ?? 'info',
    title:    options.title,
    message:  options.message,
    duration: options.duration ?? 4500,
    action:   options.action,
  }

  // Schedule auto-dismiss if duration > 0
  if (toast.duration > 0) {
    toast.timerId = setTimeout(() => dismiss(id), toast.duration)
  }

  toasts.value.push(toast)
  return id
}

/**
 * Removes a toast by ID. Clears its auto-dismiss timer first to prevent
 * the timer from firing after manual dismissal and causing a no-op
 * (harmless but wasteful) or a double-remove.
 */
function dismiss(id: string) {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index === -1) return

  const toast = toasts.value[index]!
  if (toast.timerId) clearTimeout(toast.timerId)

  toasts.value.splice(index, 1)
}

/** Removes all toasts immediately — useful on route navigation */
function dismissAll() {
  toasts.value.forEach(t => { if (t.timerId) clearTimeout(t.timerId) })
  toasts.value = []
}

/**
 * Pauses a toast's auto-dismiss timer.
 * Called by ChToast on mouseenter so the user can read the message
 * without it disappearing mid-read.
 */
function pause(id: string) {
  const toast = toasts.value.find(t => t.id === id)
  if (toast?.timerId) {
    clearTimeout(toast.timerId)
    toast.timerId = undefined
  }
}

/**
 * Resumes auto-dismiss after the user stops hovering.
 * Uses a shorter remaining duration (half of original) — feels more
 * responsive than restarting the full timer after a hover.
 */
function resume(id: string) {
  const toast = toasts.value.find(t => t.id === id)
  if (!toast || toast.duration === 0) return
  // Resume with half the original duration for a snappier feel after hover
  toast.timerId = setTimeout(() => dismiss(id), toast.duration / 2)
}

// ─── Composable ───────────────────────────────────────────────────────────────

export function useToast() {
  return {
    /** Reactive read-only array of active toasts — consumed by ChToastContainer */
    toasts: readonly(toasts),

    // Core
    push,
    dismiss,
    dismissAll,
    pause,
    resume,

    // ── Convenience methods ──────────────────────────────────────────────────
    // Sugar that sets the variant automatically so callers write less boilerplate.

    /** Green success toast. e.g. "Member saved." */
    success: (message: string, opts?: Partial<ToastOptions>) =>
      push({ variant: 'success', message, duration: opts?.duration ?? 4500, ...opts }),

    /** Red danger/error toast. e.g. "Failed to save. Please try again." */
    error: (message: string, opts?: Partial<ToastOptions>) =>
      push({ variant: 'danger', message, duration: opts?.duration ?? 6000, ...opts }),

    /** Amber warning toast. e.g. "No internet connection detected." */
    warning: (message: string, opts?: Partial<ToastOptions>) =>
      push({ variant: 'warning', message, duration: opts?.duration ?? 5500, ...opts }),

    /** Blue info toast. e.g. "Sync completed. 14 records updated." */
    info: (message: string, opts?: Partial<ToastOptions>) =>
      push({ variant: 'info', message, duration: opts?.duration ?? 4500, ...opts }),
  }
}
