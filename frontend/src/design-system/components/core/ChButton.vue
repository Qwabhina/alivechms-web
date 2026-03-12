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
 * - The button scales down slightly on `:active` (`scale(0.98)`) to give
 *   physical feedback that mimics pressing a real button.
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

/**
 * Props interface uses a TypeScript interface (not Vue's `propType` runtime
 * validators) for full type-safety and IDE autocompletion.
 */
interface Props {
  /** Visual style — controls color, border, background. Default: 'primary' */
  variant?: Variant
  /** Size — controls padding, font size, and min-height. Default: 'md' */
  size?: Size
  /** When true, the button is non-interactive and visually dimmed */
  disabled?: boolean
  /**
   * When true, shows an animated spinner and prevents click events.
   * Use for async operations (API calls, form submissions) to prevent
   * double-clicks and communicate that work is happening.
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

// `withDefaults` pairs with `defineProps` to provide prop defaults.
// Without this, all Props with `?` would be `undefined` at runtime.
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

/**
 * Typed emit declaration.
 * We re-emit `click` manually (instead of relying on native event bubbling)
 * so we can gate it: disabled and loading states should swallow clicks.
 */
const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Dynamically builds the class list for the button element.
 * Using `computed` ensures this re-evaluates only when props change.
 *
 * Class strategy:
 *   - `ch-btn`              → base styles (always present)
 *   - `ch-btn--{variant}`   → color/border/bg styles
 *   - `ch-btn--{size}`      → padding/font-size/height styles
 *   - Conditional modifiers → loading, full-width, icon-only states
 */
const classes = computed(() => [
  'ch-btn',                        // always: base layout and transition styles
  `ch-btn--${props.variant}`,      // e.g. 'ch-btn--primary'
  `ch-btn--${props.size}`,         // e.g. 'ch-btn--md'
  {
    'ch-btn--loading': props.loading,    // swap cursor and show spinner
    'ch-btn--full-width': props.fullWidth,  // width: 100%
    'ch-btn--icon-only': props.iconOnly,   // square equal-padding layout
  },
])

/**
 * Click handler that guards against clicks during disabled or loading states.
 * Even though the `<button>` has `:disabled`, some browsers still fire
 * click events on disabled buttons in certain edge cases.
 */
function handleClick(e: MouseEvent) {
  if (!props.disabled && !props.loading) {
    emit('click', e)
  }
}
</script>

<template>
  <!--
    The native <button> element.

    `:disabled` — combines both `disabled` prop AND `loading` prop.
      When loading, the button should be non-interactive (can't click again).

    `aria-busy` — communicates to screen readers that an async operation
      is in progress. Screen readers may announce "busy" to the user.

    `@click` — routes through handleClick() to gate disabled/loading states.
  -->
  <button :class="classes" :type="type" :disabled="disabled || loading" :aria-busy="loading" @click="handleClick">
    <!--
      Loading spinner — shown ONLY when `loading` is true.
      Replaces the icon slot (we hide the icon to avoid double icons).
      `aria-hidden="true"` — the spinner is decorative; the `aria-busy`
      attribute on the button itself communicates the loading state to AT.
    -->
    <span v-if="loading" class="ch-btn__spinner" aria-hidden="true"></span>

    <!--
      Leading icon slot — shown BEFORE the label text.
      Typical use: <template #icon><PlusIcon /></template>
Hidden during loading (spinner replaces it visually).
-->
    <span v-if="$slots.icon && !loading" class="ch-btn__icon ch-btn__icon--leading">
      <slot name="icon"></slot>
    </span>

    <!--
      Default label slot — the button's text content.
      Hidden in `iconOnly` mode (the default slot becomes the icon in that case).
    -->
    <span v-if="$slots.default && !iconOnly" class="ch-btn__label">
      <slot></slot>
    </span>

    <!--
      Icon-only default slot — when `iconOnly` is true, the default slot
      is treated as an icon (rendered without the label wrapper).
      e.g. <ChButton :iconOnly="true"><EditIcon /></ChButton>
    -->
    <span v-if="iconOnly && $slots.default" class="ch-btn__icon">
      <slot></slot>
    </span>

    <!--
      Trailing icon slot — shown AFTER the label text.
      Used for: dropdown chevrons, external link indicators, arrow icons.
      e.g. <template #trailingIcon><ChevronDownIcon /></template>
      Not shown during loading or in icon-only mode.
    -->
    <span v-if="$slots.trailingIcon && !loading && !iconOnly" class="ch-btn__icon ch-btn__icon--trailing">
      <slot name="trailingIcon"></slot>
    </span>
  </button>
</template>

<style scoped>
/*
 * `scoped` means these styles ONLY apply to elements in this component.
 * Vue adds a unique data attribute (e.g. `data-v-3a8b2c`) to every element
 * and rewrites the selectors to `.ch-btn[data-v-3a8b2c]`.
 * This prevents any of these styles from leaking to child components or global.
 */

/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-btn {
  /*
   * `inline-flex` makes the button shrink to content width by default
   * (unlike `flex` which is block-level). Children (icon, label) are
   * laid out in a horizontal flex row.
   */
  display: inline-flex;
  align-items: center;
  /* vertically center icon and text */
  justify-content: center;
  /* horizontally center content */
  gap: var(--ch-space-2);
  /* 8px gap between icon and label */

  /* Transparent border by default — variants will override this.
   * Always having a border (even transparent) prevents layout shift
   * when the border becomes visible (e.g. on focus or hover). */
  border: 1px solid transparent;

  border-radius: var(--ch-radius-lg);
  /* 8px — rounded but not pill */
  font-family: var(--ch-font-sans);
  font-weight: var(--ch-font-medium);
  /* 500 — heavier than body, not bold */
  line-height: 1;
  /* 1 prevents extra height from line-height */
  white-space: nowrap;
  /* prevent text wrapping inside buttons */
  cursor: pointer;
  user-select: none;
  /* prevent text selection on rapid clicks */
  position: relative;
  /* needed if we add ::after ripple effects later */
  overflow: hidden;
  /* clip any child overflow (e.g. ripples) */

  /*
   * The transition shorthand animates multiple properties simultaneously.
   * Each property has its own duration and easing for fine control.
   * `var(--ch-duration-fast)` is 100ms — fast enough to feel snappy,
   * slow enough to be perceivable.
   */
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    color var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-out),
    opacity var(--ch-duration-fast) var(--ch-ease-out);
}

/*
 * Physical "press" feedback: scale down 2% on click.
 * `:not(:disabled)` prevents this on disabled buttons
 * (pressing a disabled button should feel like nothing happened).
 */
.ch-btn:active:not(:disabled) {
  transform: scale(0.98);
}

.ch-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
  /* dim to communicate non-interactivity */
  transform: none;
  /* override the :active scale — disabled shouldn't bounce */
}

.ch-btn--full-width {
  width: 100%;
  /* override inline-flex's shrink-to-content behavior */
}

/* ─── Sizes ───────────────────────────────────────────────────────────────── */
/*
 * Each size defines:
 *   - `font-size` — text size
 *   - `padding`   — space inside the button (affects visual weight)
 *   - `min-height` — guarantees touch-friendly target size
 *     (44px minimum is the WCAG and Apple HIG recommendation for touch targets)
 */

.ch-btn--sm {
  font-size: var(--ch-text-xs);
  /* 12px */
  padding: var(--ch-space-1_5) var(--ch-space-3);
  /* 6px 12px */
  min-height: 28px;
  /* compact — table/toolbar use */
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

/*
 * Icon-only: override horizontal padding to be the same as vertical,
 * making the button a perfect square. The explicit `width` prevents the
 * flexbox from stretching the button based on its content.
 */
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

/* PRIMARY — Filled with brand color. Use for the one main action. */
.ch-btn--primary {
  background-color: var(--ch-color-primary);
  /* brand color fill */
  border-color: var(--ch-color-primary);
  /* border matches bg */
  color: var(--ch-color-primary-fg);
  /* white text */
  box-shadow: 0 1px 2px rgb(0 0 0 / 0.12);
  /* subtle depth */
}

.ch-btn--primary:hover:not(:disabled) {
  background-color: var(--ch-color-primary-hover);
  /* slightly darker */
  border-color: var(--ch-color-primary-hover);
  box-shadow: 0 2px 6px rgb(0 0 0 / 0.15);
  /* slightly larger shadow on hover */
}

/* Focus ring for keyboard navigation — uses a colored halo */
.ch-btn--primary:focus-visible {
  box-shadow: 0 0 0 3px var(--ch-color-primary-muted);
}

/* SECONDARY — Neutral fill. Use for alternative/cancel actions. */
.ch-btn--secondary {
  background-color: var(--ch-color-bg-muted);
  /* light gray fill */
  border-color: var(--ch-color-border);
  color: var(--ch-color-text);
}

.ch-btn--secondary:hover:not(:disabled) {
  background-color: var(--ch-color-bg-subtle);
  border-color: var(--ch-color-border-strong);
}

/* OUTLINE — Transparent fill with brand-colored border and text. */
.ch-btn--outline {
  background-color: transparent;
  border-color: var(--ch-color-primary);
  color: var(--ch-color-primary);
}

.ch-btn--outline:hover:not(:disabled) {
  background-color: var(--ch-color-primary-subtle);
  /* light brand tint on hover */
}

/* GHOST — No border or background. Lowest visual emphasis. */
.ch-btn--ghost {
  background-color: transparent;
  border-color: transparent;
  color: var(--ch-color-text-muted);
  /* de-emphasized text */
}

.ch-btn--ghost:hover:not(:disabled) {
  background-color: var(--ch-color-bg-muted);
  /* subtle fill appears on hover */
  color: var(--ch-color-text);
  /* text becomes full strength */
}

/* DANGER — Red-filled. Reserve exclusively for destructive actions. */
.ch-btn--danger {
  background-color: var(--ch-color-danger);
  border-color: var(--ch-color-danger);
  color: #fff;
  box-shadow: 0 1px 2px rgb(0 0 0 / 0.12);
}

.ch-btn--danger:hover:not(:disabled) {
  /* `brightness(0.9)` darkens the fill by 10% without needing a separate
   * hover color token for danger — convenient for semantic colors. */
  filter: brightness(0.9);
}

.ch-btn--danger:focus-visible {
  box-shadow: 0 0 0 3px var(--ch-color-danger-bg);
  /* red-tinted halo */
}

/* ─── Loading State ───────────────────────────────────────────────────────── */

/* Change cursor to 'wait' so the user knows something is happening */
.ch-btn--loading {
  cursor: wait;
}

/*
 * CSS-only spinner: a circle border where only 3 sides are visible
 * (achieved by making border-top-color transparent), then rotating.
 *
 * `1em` sizing means the spinner scales with the button's font-size.
 * `currentColor` inherits the button's text color, so it works across all variants.
 * `flex-shrink: 0` prevents the spinner from being compressed in tight layouts.
 */
.ch-btn__spinner {
  display: inline-block;
  width: 1em;
  height: 1em;
  border: 2px solid currentColor;
  /* visible on 3 sides */
  border-top-color: transparent;
  /* invisible top creates the gap */
  border-radius: 50%;
  /* circular */
  animation: ch-spin var(--ch-duration-slower) linear infinite;
  flex-shrink: 0;
}

/* ─── Icon Slots ──────────────────────────────────────────────────────────── */
.ch-btn__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  /* icon never compresses, even if label is long */
}

/*
 * `.ch-btn__label` wraps the default slot text. This wrapper is needed so
 * Vue can conditionally show/hide text content without affecting icon slots.
 */
.ch-btn__label {
  display: inline-block;
}
</style>