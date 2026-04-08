<script setup lang="ts">
/**
 * @component ChButton
 * @path /frontend/src/design-system/components/core/ChButton.vue
 * @description The primary interactive element of the design system.
 * Handles all clickable actions: form submissions, navigation triggers,
 * confirmations, and destructive operations.
 *
 * ─── Design decisions ────────────────────────────────────────────────────────
 * - Variants communicate semantic meaning: `primary` for the main action,
 *   `secondary` for alternatives, `ghost` for low-emphasis actions,
 *   `outline` for outlined brand actions, `danger` for destructive operations.
 * - A `loading` state prevents double-submission and communicates async work.
 * - `iconOnly` mode collapses padding to a square — used for toolbar buttons.
 * - Primary/secondary/danger buttons shift slightly on `:active` (translate
 *   1px down-right) to mimic physical press. Ghost/outline scale down 2%
 *   instead (no shadow to collapse, so translate has no visual payoff).
 *
 * ─── Loading vs disabled ─────────────────────────────────────────────────────
 * Both states prevent interaction, but they communicate different things:
 * - `disabled` → action is not available (wrong context, missing permissions)
 * - `loading`  → action was taken; system is working (shows cursor: wait)
 *
 * Internally, loading sets `aria-disabled` + `pointer-events: none` rather
 * than the native `disabled` attribute. This allows `cursor: wait` to be
 * visible (native `:disabled` forces `cursor: not-allowed`, overriding `wait`),
 * and keeps the button focusable so AT can still announce the busy state.
 *
 * @example Basic usage
 * <ChButton>Save</ChButton>
 *
 * @example With variant and loading
 * <ChButton variant="danger" :loading="isDeleting" @click="deleteRecord">
 *   Delete Member
 * </ChButton>
 *
 * @example With leading icon
 * <ChButton variant="primary">
 *   <template #icon><PlusIcon /></template>
 *   Add Member
 * </ChButton>
 *
 * @example Icon-only (square button)
 * <ChButton :iconOnly="true" variant="ghost">
 *   <SettingsIcon />
 * </ChButton>
 */

import { computed } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Visual style variants. Each communicates different semantic intent:
 * - `primary`   → the ONE main action on a page/form (e.g. "Save", "Submit")
 * - `secondary` → alternative or supplementary actions (e.g. "Cancel", "Back")
 * - `outline`   → branded but unfilled — for secondary CTAs with brand color
 * - `ghost`     → minimal — for toolbar buttons, icon actions, low-emphasis
 * - `danger`    → destructive actions (e.g. "Delete", "Remove") — draws attention
 */
type Variant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'outline'

/**
 * Three standard sizes to fit different UI contexts:
 * - `sm` → used inside dense UI (table rows, small toolbars)
 * - `md` → default for most interactions
 * - `lg` → primary CTAs, hero sections, prominent form actions
 */
type Size = 'sm' | 'md' | 'lg'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Visual style — controls color, border, background. Default: 'primary' */
  variant?: Variant
  /** Size — controls padding, font size, and min-height. Default: 'md' */
  size?: Size
  /** When true, the button is non-interactive and visually dimmed */
  disabled?: boolean
  /**
   * When true, shows an animated spinner and prevents click events.
   * Communicates async work in progress. Uses `aria-disabled` + `pointer-events: none`
   * (not the native `disabled` attribute) so that `cursor: wait` remains visible
   * and AT can still read the `aria-busy` announcement.
   */
  loading?: boolean
  /**
   * Maps to the HTML `type` attribute. ALWAYS set to `'submit'` for
   * form submission buttons, or Vue will submit the form on Enter presses
   * with any button inside a <form>. Default is `'button'` (no form behavior).
   */
  type?: 'button' | 'submit' | 'reset'
  /** When true, `width: 100%` — use for mobile or full-column CTAs */
  fullWidth?: boolean
  /**
   * When true, collapses to a square with equal padding on all sides.
   * The default slot becomes the icon. Used for toolbar/action icon buttons.
   */
  iconOnly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
  fullWidth: false,
  iconOnly: false,
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

const classes = computed(() => [
  'ch-btn',
  `ch-btn--${props.variant}`,
  `ch-btn--${props.size}`,
  {
    'ch-btn--loading': props.loading,
    'ch-btn--full-width': props.fullWidth,
    'ch-btn--icon-only': props.iconOnly,
  },
])

/**
 * For `loading`, we use `aria-disabled` instead of the native `disabled`
 * attribute. This allows `cursor: wait` to show (native `:disabled` always
 * overrides cursor to `not-allowed`), and keeps the button reachable by
 * keyboard so AT can announce the `aria-busy` busy state.
 *
 * For `disabled`, we use the native `disabled` attribute — the button should
 * be fully removed from the interaction model.
 */
const isNativeDisabled = computed(() => props.disabled)
const isAriaDisabled = computed(() => props.loading || props.disabled)

/**
 * Guards clicks during disabled or loading states.
 * The native `disabled` attribute blocks clicks for the `disabled` prop,
 * but since `loading` uses `aria-disabled` (not native disabled), we need
 * `pointer-events: none` in CSS plus this handler as a defence-in-depth check.
 */
function handleClick(e: MouseEvent) {
  if (!props.disabled && !props.loading) {
    emit('click', e)
  }
}
</script>

<template>
  <!--
    `:disabled` — only set for the `disabled` prop. Loading uses aria-disabled
      instead so the `cursor: wait` CSS can take effect (native :disabled
      always forces cursor: not-allowed, which would override it).

    `aria-disabled` — true for BOTH disabled and loading, so AT understands
      the button is not interactive in either state.

    `aria-busy` — signals an in-progress operation to screen readers.
  -->
  <button
    :class="classes"
    :type="type"
    :disabled="isNativeDisabled"
    :aria-disabled="isAriaDisabled"
    :aria-busy="loading"
    @click="handleClick"
  >
    <!--
      Loading spinner.
      `aria-hidden="true"` — decorative; `aria-busy` on the button handles AT.
      The @keyframes ch-spin is defined at the bottom of this file's <style>.
    -->
    <span v-if="loading" class="ch-btn__spinner" aria-hidden="true"></span>

    <!-- Leading icon slot — hidden during loading (spinner replaces it). -->
    <span v-if="$slots.icon && !loading" class="ch-btn__icon ch-btn__icon--leading">
      <slot name="icon"></slot>
    </span>

    <!-- Default label slot — hidden in iconOnly mode. -->
    <span v-if="$slots.default && !iconOnly" class="ch-btn__label">
      <slot></slot>
    </span>

    <!-- Icon-only default slot — treated as an icon, not a label. -->
    <span v-if="iconOnly && $slots.default" class="ch-btn__icon">
      <slot></slot>
    </span>

    <!-- Trailing icon slot — not shown during loading or in icon-only mode. -->
    <span
      v-if="$slots.trailingIcon && !loading && !iconOnly"
      class="ch-btn__icon ch-btn__icon--trailing"
    >
      <slot name="trailingIcon"></slot>
    </span>
  </button>
</template>

<style scoped>
/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--ch-space-2);
  /* 8px between icon and label */

  /*
     * Transparent border always present — prevents 1px layout shift when
     * a variant's border becomes visible on hover/focus.
     */
  border: 1px solid transparent;
  border-radius: var(--ch-radius-md);

  font-family: var(--ch-font-sans);
  font-weight: var(--ch-font-medium);
  line-height: 1;
  white-space: nowrap;
  cursor: pointer;
  user-select: none;
  position: relative;
  overflow: hidden;

  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    color var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-out),
    opacity var(--ch-duration-fast) var(--ch-ease-out);
}

/*
 * Ghost and outline have no shadow to collapse on press,
 so scale(0.98) * gives better physical feedback than a translate. Filled variants * (primary, secondary, danger) override this with translate(1px, 1px) * to pair with their shadow animation.
 */
.ch-btn:active:not(:disabled):not([aria-disabled='true']) {
  transform: scale(0.98);
}

/*
 * Native disabled: full lock-out, dimmed appearance.
 * pointer-events: none is redundant with :disabled on buttons but
 * helps for any edge-case wrapping elements.
 */
.ch-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
  transform: none;
  pointer-events: none;
}

/*
   * Loading uses aria-disabled (not native :disabled) so cursor: wait
   * can show. pointer-events: none prevents interaction since the native
   * disabled attribute isn't set.
   */
.ch-btn--loading {
  cursor: wait;
  pointer-events: none;
  opacity: 0.8;
  /* slightly less dim than disabled — the action IS happening */
}

.ch-btn--full-width {
  width: 100%;
}

/* ─── Sizes ───────────────────────────────────────────────────────────────── */
.ch-btn--sm {
  font-size: var(--ch-text-xs);
  /* 12px */
  padding: var(--ch-space-1_5) var(--ch-space-3);
  /* 6px 12px */
  min-height: 28px;
}

.ch-btn--md {
  font-size: var(--ch-text-sm);
  /* 14px */
  padding: var(--ch-space-2) var(--ch-space-4);
  /* 8px 16px */
  min-height: 36px;
}

.ch-btn--lg {
  font-size: var(--ch-text-base);
  /* 16px */
  padding: var(--ch-space-2_5) var(--ch-space-6);
  /* 10px 24px */
  min-height: 44px;
  /* ★ meets WCAG touch target recommendation */
}

/* Icon-only: equal padding on all sides makes the button a perfect square */
.ch-btn--icon-only.ch-btn--sm {
  padding: var(--ch-space-1_5);
  width: 28px;
}

.ch-btn--icon-only.ch-btn--md {
  padding: var(--ch-space-2);
  width: 36px;
}

.ch-btn--icon-only.ch-btn--lg {
  padding: var(--ch-space-2_5);
  width: 44px;
}

/* ─── Variants ────────────────────────────────────────────────────────────── */

/*
 * PRIMARY — Filled with brand color.
 * Border uses a slightly darker brand shade (not a gray border-strong token)
 * so it reads as part of the button rather than a foreign element.
 */
.ch-btn--primary {
  background-color: var(--ch-color-primary);
  border-color: var(--ch-color-primary-dark);
  /* brand-tinted edge, not generic gray */
  color: var(--ch-color-primary-fg);
  box-shadow: var(--ch-shadow-sm);
}

.ch-btn--primary:hover:not(:disabled):not([aria-disabled='true']) {
  background-color: var(--ch-color-primary-hover);
  box-shadow: var(--ch-shadow-md);
  transform: translate(-1px, -1px);
}

.ch-btn--primary:active:not(:disabled):not([aria-disabled='true']) {
  transform: translate(1px, 1px);
  box-shadow: none;
}

.ch-btn--primary:focus-visible {
  outline: 2px solid var(--ch-color-primary);
  outline-offset: 2px;
}

/* SECONDARY — Neutral fill. Alternative/cancel actions. */
.ch-btn--secondary {
  background-color: var(--ch-color-bg-muted);
  border-color: var(--ch-color-border);
  color: var(--ch-color-text);
  box-shadow: var(--ch-shadow-sm);
}

.ch-btn--secondary:hover:not(:disabled):not([aria-disabled='true']) {
  background-color: var(--ch-color-bg-subtle);
  box-shadow: var(--ch-shadow-md);
  transform: translate(-1px, -1px);
}

.ch-btn--secondary:active:not(:disabled):not([aria-disabled='true']) {
  transform: translate(1px, 1px);
  box-shadow: none;
}

.ch-btn--secondary:focus-visible {
  outline: 2px solid var(--ch-color-border-strong);
  outline-offset: 2px;
}

/* OUTLINE — Transparent fill, brand-colored border and text. */
.ch-btn--outline {
  background-color: transparent;
  border-color: var(--ch-color-primary);
  color: var(--ch-color-primary);
}

.ch-btn--outline:hover:not(:disabled):not([aria-disabled='true']) {
  background-color: var(--ch-color-primary-subtle);
}

/* Outline active falls through to base scale(0.98) — intentional */

.ch-btn--outline:focus-visible {
  outline: 2px solid var(--ch-color-primary);
  outline-offset: 2px;
}

/* GHOST — No border or background. Lowest visual emphasis. */
.ch-btn--ghost {
  background-color: transparent;
  border-color: transparent;
  color: var(--ch-color-text-muted);
}

.ch-btn--ghost:hover:not(:disabled):not([aria-disabled='true']) {
  background-color: var(--ch-color-bg-muted);
  color: var(--ch-color-text);
}

/* Ghost active falls through to base scale(0.98) — intentional */

.ch-btn--ghost:focus-visible {
  outline: 2px solid var(--ch-color-border-strong);
  outline-offset: 2px;
}

/*
 * DANGER — Red-filled. Exclusively for destructive actions.
 * Border uses a darker danger shade (not a generic gray).
 * Text uses var(--ch-color-danger-fg) — a token, not a hardcoded #fff —
 * so dark-mode or high-contrast themes can override it correctly.
 */
.ch-btn--danger {
  background-color: var(--ch-color-danger);
  border-color: var(--ch-color-danger-dark);
  /* danger-tinted edge */
  color: var(--ch-color-text-inverse);
  /* white text on filled danger button */
  box-shadow: var(--ch-shadow-sm);
}

.ch-btn--danger:hover:not(:disabled):not([aria-disabled='true']) {
  filter: brightness(0.9);
  box-shadow: var(--ch-shadow-md);
  transform: translate(-1px, -1px);
}

.ch-btn--danger:active:not(:disabled):not([aria-disabled='true']) {
  transform: translate(1px, 1px);
  box-shadow: none;
}

.ch-btn--danger:focus-visible {
  outline: 2px solid var(--ch-color-danger);
  outline-offset: 2px;
}

/* ─── Spinner ─────────────────────────────────────────────────────────────── */

/*
 * CSS-only spinner: a circle where the top border segment is transparent,
 * creating the visual "gap" . Rotating the whole element animates that gap.
 *
 * `1em` sizing scales with the button's current font-size automatically.
 * `currentColor` inherits the variant's text color — works across all variants
 * with no per-variant overrides needed.
 */
.ch-btn__spinner {
  display: inline-block;
  width: 1em;
  height: 1em;
  border: 2px solid currentColor;
  border-top-color: transparent;
  /* the "gap" in the ring */
  border-radius: 50%;
  flex-shrink: 0;
  animation: ch-btn-spin var(--ch-duration-slower, 700ms) linear infinite;
}

/*
   * @keyframes defined here (in the component) so ChButton is self-contained.
   * Without this definition the spinner renders but never rotates — it is
   * a silent failure with no console error.
   *
   * Scoped styles in Vue DO scope regular selectors, but @keyframes are
   * not scoped — they are injected globally under the name given here.
   * Using a prefixed name (`ch-btn-spin` rather than `spin`) avoids
   * collisions with other components that might define their own `spin`.
   */
@keyframes ch-btn-spin {
  to {
    transform: rotate(360deg);
  }
}

/* ─── Icon Slots ──────────────────────────────────────────────────────────── */
.ch-btn__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.ch-btn__label {
  display: inline-block;
}
</style>
