<script setup lang="ts">
/**
 * @component ChTextarea
 * @path /frontend/src/design-system/components/core/ChTextarea.vue
 * @description A controlled, multi-line text input for longer-form content.
 *
 * ─── Why a separate component from ChInput? ──────────────────────────────────
 * `<textarea>` and `<input>` share similar states (focus, error, disabled)
 * but differ in key ways that warrant a dedicated component:
 *   - `<textarea>` is resizable and multi-line — layout must accommodate that
 *   - It has no leading/trailing adornment slots (doesn't make sense on a tall field)
 *   - It has a character count feature (rarely needed on single-line inputs)
 *   - The wrapper must grow vertically with the content
 *   - `rows` controls initial visible height (no equivalent on `<input>`)
 *
 * ─── Architecture ────────────────────────────────────────────────────────────
 * Same wrapper pattern as ChInput: a styled `<div>` owns the visual states
 * (border, focus ring, error styling) while the inner `<textarea>` is bare.
 * This keeps styles consistent with ChInput even though there are no adornments.
 *
 * ─── Use cases in the church management system ───────────────────────────────
 * - Member notes / pastoral notes
 * - Event descriptions
 * - Prayer request submissions
 * - Group announcements
 * - Communication message body
 * - Document descriptions
 *
 * @example Basic usage
 * <ChTextarea v-model="notes" placeholder="Add notes about this member..." />
 *
 * @example With row count and character limit
 * <ChTextarea
 *   v-model="description"
 *   :rows="6"
 *   :maxlength="500"
 *   :showCount="true"
 *   label="Event Description"
 * />
 *
 * @example Error state
 * <ChTextarea
 *   v-model="message"
 *   :error="errors.message"
 *   placeholder="Your message..."
 * />
 */

import { computed, ref } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/** Three sizes matching the rest of the design system */
type Size = 'sm' | 'md' | 'lg'

/**
 * Controls how the user can resize the textarea.
 * Maps directly to the CSS `resize` property values.
 *
 * - `none`       → not resizable (use when height is fixed by design)
 * - `vertical`   → can drag to resize height only (most common for text areas)
 * - `horizontal` → can drag to resize width only (rarely useful)
 * - `both`       → can resize in both directions
 */
type Resize = 'none' | 'vertical' | 'horizontal' | 'both'

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /**
   * The controlled value — bind with v-model.
   * The component never mutates this directly; it always emits upward.
   */
  modelValue?:  string

  /** Placeholder text shown when the textarea is empty */
  placeholder?: string

  /** Size — controls font size, padding, and minimum height */
  size?:        Size

  /**
   * Number of visible text rows at initial render.
   * Sets the `rows` attribute on the native `<textarea>`, which
   * determines the default height before any CSS `min-height` kicks in.
   * Default: 3 (enough for a short paragraph, compact on page).
   */
  rows?:        number

  /** Disables the textarea — non-interactive, visually dimmed */
  disabled?:    boolean

  /** Read-only — shows content but prevents editing */
  readonly?:    boolean

  /**
   * Error state. Can be:
   *   - `string` → error message (rendered by ChFormField parent)
   *   - `true`   → just shows error border without message text
   *   - falsy    → no error
   */
  error?:       string | boolean

  /**
   * Maximum character length. When combined with `showCount`,
   * displays a live "X / max" character counter below the textarea.
   */
  maxlength?:   number

  /**
   * When true, shows a character count below the textarea.
   * If `maxlength` is also set, displays as "current / max".
   * If `maxlength` is not set, displays just the current count.
   */
  showCount?:   boolean

  /**
   * Controls CSS resize behavior.
   * Default: `vertical` — users can drag to increase height, which is
   * the most natural behavior for comment/note fields.
   */
  resize?:      Resize

  /** Maps to the HTML `id` attribute — used to connect a <label> via `for` */
  id?:          string

  /** Maps to the HTML `name` attribute — used in form data serialization */
  name?:        string

  /** Maps to the HTML `autocomplete` attribute */
  autocomplete?: string
}

const props = withDefaults(defineProps<Props>(), {
  size:     'md',
  rows:     3,
  disabled: false,
  readonly: false,
  resize:   'vertical',
  showCount:false,
})

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
  /** Fired on every keystroke with the updated string value */
  'update:modelValue': [value: string]
  /** Fired when the textarea gains focus */
  focus: [event: FocusEvent]
  /** Fired when the textarea loses focus */
  blur:  [event: FocusEvent]
}>()

// ─── Local State ──────────────────────────────────────────────────────────────

/**
 * Tracks focus state to apply the focus ring to the wrapper div
 * (same pattern as ChInput — visual focus is on the wrapper, not the element).
 */
const isFocused = ref(false)

// ─── Computed ─────────────────────────────────────────────────────────────────

/** True when the error prop is truthy (string or boolean true) */
const hasError = computed(() => !!props.error)

/** Safe string value — normalizes undefined to empty string */
const textValue = computed(() => props.modelValue ?? '')

/**
 * Current character count for the counter display.
 * `.length` on a string always returns a number (0 for empty string).
 */
const charCount = computed(() => textValue.value.length)

/**
 * The counter label shown below the textarea when `showCount` is true.
 * Format: "42 / 500" when maxlength is set, "42" when it's not.
 */
const counterLabel = computed(() =>
  props.maxlength
    ? `${charCount.value} / ${props.maxlength}`
    : `${charCount.value}`
)

/**
 * True when the user is within 10% of the maxlength.
 * Triggers a warning color on the counter to give the user a heads-up.
 * e.g. for maxlength=500, warning shows at charCount >= 450.
 */
const isNearLimit = computed(() =>
  !!props.maxlength && charCount.value >= props.maxlength * 0.9
)

/**
 * True when the character count has hit the maxlength exactly.
 * Triggers the danger/red color on the counter.
 */
const isAtLimit = computed(() =>
  !!props.maxlength && charCount.value >= props.maxlength
)

/**
 * Builds the wrapper class list.
 * Carries all visual states — same pattern as ChInput's wrapper.
 */
const wrapperClasses = computed(() => [
  'ch-textarea-wrapper',
  `ch-textarea-wrapper--${props.size}`,
  {
    'ch-textarea-wrapper--focused':  isFocused.value,
    'ch-textarea-wrapper--error':    hasError.value,
    'ch-textarea-wrapper--disabled': props.disabled,
    'ch-textarea-wrapper--readonly': props.readonly,
  },
])

/**
 * Inline style for the `<textarea>` element.
 * The `resize` value is applied as an inline style because it's a
 * per-instance prop, not a static class variant — there's no meaningful
 * set of modifier classes for all four resize options.
 */
const textareaStyle = computed(() => ({
  resize: props.resize,
}))

// ─── Event Handlers ───────────────────────────────────────────────────────────

/** Emit the new value upward on every keystroke */
function onInput(e: Event) {
  emit('update:modelValue', (e.target as HTMLTextAreaElement).value)
}

function onFocus(e: FocusEvent) {
  isFocused.value = true
  emit('focus', e)
}

function onBlur(e: FocusEvent) {
  isFocused.value = false
  emit('blur', e)
}
</script>

<template>
  <!-- Root container — wraps the textarea and optional character counter -->
  <div class="ch-textarea-root">

    <!--
      Wrapper div — same visual role as ChInput's wrapper.
      Owns the border, background, focus ring, and error styles.
      The native <textarea> inside is bare (no border, transparent bg).
    -->
    <div :class="wrapperClasses">
      <!--
        Native <textarea> element.

        `:value="textValue"` — controlled, one-way binding.
          We do NOT use v-model on the native element to avoid a circular
          binding. We read from prop and emit changes upward.

        `:style="textareaStyle"` — applies the resize CSS property.

        `aria-invalid` — communicates error state to screen readers.

        Note: `rows` only sets the initial visible height.
          The textarea can still grow beyond this via CSS `min-height`
          and the user dragging the resize handle.
      -->
      <textarea
        :id="id"
        :name="name"
        :value="textValue"
        :rows="rows"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :maxlength="maxlength"
        :autocomplete="autocomplete"
        :aria-invalid="hasError"
        :style="textareaStyle"
        class="ch-textarea"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
      ></textarea>
    </div>

    <!--
      Character counter — only rendered when `showCount` is true.
      Sits below the wrapper, right-aligned, with color feedback
      as the user approaches or hits the maxlength.

      `aria-live="polite"` — screen readers will announce counter
      changes without interrupting the user while they're typing.
      "polite" means it waits for the user to pause before announcing.
    -->
    <div
      v-if="showCount"
      class="ch-textarea-counter"
      :class="{
        'ch-textarea-counter--warning': isNearLimit && !isAtLimit,
        'ch-textarea-counter--danger':  isAtLimit,
      }"
      aria-live="polite"
    >
      {{ counterLabel }}
    </div>

  </div>
</template>

<style scoped>
/* ─── Root container ──────────────────────────────────────────────────────── */
/*
 * A flex column so the counter sits directly below the textarea wrapper
 * with a small gap. The `display: flex` on the root does NOT affect the
 * textarea's internal layout — it only positions wrapper + counter.
 */
.ch-textarea-root {
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-1); /* 4px gap between textarea and counter */
  width:          100%;
}

/* ─── Wrapper ─────────────────────────────────────────────────────────────── */
/*
 * Same visual shell pattern as ChInput's wrapper.
 * Contains the bare <textarea> and provides the styled border/background.
 */
.ch-textarea-wrapper {
  background-color: var(--ch-color-surface);
  border:           1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
    /* control radius */
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow   var(--ch-duration-fast) var(--ch-ease-out);

  /*
   * `overflow: hidden` clips the textarea's resize handle to the border-radius.
   * Without this, the resize handle would extend beyond the rounded corners.
   * Trade-off: when `resize` is enabled, the user still gets the handle —
   * it just stays within the rounded corner shape.
   */
  overflow: hidden;
}

/* Focus: brand-colored border + sharp outline */
.ch-textarea-wrapper--focused {
  border-color: var(--ch-color-primary);
  outline:      2px solid var(--ch-color-primary);
  outline-offset: 1px;
}

/* Error: danger-colored border */
.ch-textarea-wrapper--error {
  border-color: var(--ch-color-danger);
}

/* Error + focused: danger sharp outline */
.ch-textarea-wrapper--error.ch-textarea-wrapper--focused {
  outline:      2px solid var(--ch-color-danger);
  outline-offset: 1px;
}

.ch-textarea-wrapper--disabled {
  background-color: var(--ch-color-bg-subtle);
  cursor:           not-allowed;
}

.ch-textarea-wrapper--readonly {
  background-color: var(--ch-color-bg-subtle);
}

/* ─── Native Textarea Element ─────────────────────────────────────────────── */
.ch-textarea {
  display:     block;    /* block so width: 100% works correctly */
  width:       100%;
  background:  transparent;
  border:      none;
  outline:     none;     /* focus ring is on the wrapper, not here */
  color:       var(--ch-color-text);
  font-family: var(--ch-font-sans);
  font-weight: var(--ch-font-normal);
  line-height: var(--ch-leading-relaxed); /* 1.625 — more generous for multi-line text */

  /*
   * `min-height` prevents the textarea from collapsing to near-zero
   * if the user accidentally drags the resize handle too far up.
   * The actual initial height is controlled by the `rows` attribute.
   */
  min-height: 80px;
}

.ch-textarea::placeholder {
  color: var(--ch-color-text-subtle);
}

.ch-textarea:disabled {
  cursor: not-allowed;
  color:  var(--ch-color-text-disabled);
}

/* ─── Sizes ───────────────────────────────────────────────────────────────── */
/*
 * Unlike ChInput where size affects `min-height`, here size primarily affects
 * padding (comfortable reading space inside the box) and font size.
 * The height is already controlled by the `rows` prop + `min-height` above.
 */
.ch-textarea-wrapper--sm .ch-textarea {
  font-size: var(--ch-text-xs);  /* 12px */
  padding:   var(--ch-space-2) var(--ch-space-3); /* 8px 12px */
}

.ch-textarea-wrapper--md .ch-textarea {
  font-size: var(--ch-text-sm);  /* 14px */
  padding:   var(--ch-space-3) var(--ch-space-3_5); /* 12px 14px */
}

.ch-textarea-wrapper--lg .ch-textarea {
  font-size: var(--ch-text-base); /* 16px */
  padding:   var(--ch-space-3_5) var(--ch-space-4); /* 14px 16px */
}

/* ─── Character Counter ───────────────────────────────────────────────────── */
.ch-textarea-counter {
  text-align:  right; /* right-aligned, directly below the textarea */
  font-size:   var(--ch-text-xs);
  font-family: var(--ch-font-sans);
  color:       var(--ch-color-text-subtle); /* de-emphasized by default */
  transition:  color var(--ch-duration-fast) var(--ch-ease-out);
}

/* Within 10% of the limit — amber warning color */
.ch-textarea-counter--warning {
  color: var(--ch-color-warning);
}

/* At the limit — red danger color */
.ch-textarea-counter--danger {
  color:       var(--ch-color-danger);
  font-weight: var(--ch-font-medium); /* slightly heavier for emphasis */
}
</style>
