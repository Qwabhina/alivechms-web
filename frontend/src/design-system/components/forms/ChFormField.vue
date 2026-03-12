<script setup lang="ts">
/**
 * @component ChFormField
 * @path /frontend/src/design-system/components/forms/ChFormField.vue
 * @description The standard wrapper for all form controls. Provides a
 * label, optional hint text, and a consistently styled error message.
 *
 * ─── Architecture ────────────────────────────────────────────────────────────
 * ChFormField is a layout-only component — it renders no interactive element
 * itself. It wraps ChInput, ChTextarea, ChSelect, ChCheckbox, ChDatePicker,
 * or any custom control via the default slot.
 *
 * The label is linked to the control via `for` / `id`. Pass the same `inputId`
 * prop as the `id` prop on the inner control so clicking the label focuses it.
 *
 * ─── Error wiring ────────────────────────────────────────────────────────────
 * Pass the same error string to both ChFormField (for the message below the
 * field) and the inner control (for its red border). This keeps them in sync
 * without coupling the two components.
 *
 * @example Standard field
 * <ChFormField label="Full Name" input-id="member-name" :error="errors.name">
 *   <ChInput id="member-name" v-model="form.name" :error="errors.name" />
 * </ChFormField>
 *
 * @example With hint text
 * <ChFormField
 *   label="Date of Birth"
 *   hint="Used to calculate age and send birthday messages."
 *   input-id="member-dob"
 * >
 *   <ChDatePicker id="member-dob" v-model="form.dob" />
 * </ChFormField>
 *
 * @example Required field
 * <ChFormField label="Email" :required="true" input-id="member-email">
 *   <ChInput id="member-email" v-model="form.email" type="email" />
 * </ChFormField>
 */

interface Props {
  /** The <label> text */
  label?:       string

  /**
   * The `id` of the inner input element.
   * Links the <label> `for` attribute to the control for accessibility.
   * Always set this when the field has a label.
   */
  inputId?:     string

  /**
   * Hint text shown below the label in muted style.
   * Use for brief contextual guidance (character limits, format hints, etc.)
   * Don't use for error messages — use `error` for that.
   */
  hint?:        string

  /**
   * Error message displayed below the field in red.
   * When truthy, the message renders. When falsy, the space collapses.
   * Pass the same value to the inner control's `error` prop for the red border.
   */
  error?:       string | boolean

  /**
   * Marks the field as required — renders a red asterisk after the label.
   * This is visual only; the actual `required` attribute must still be set
   * on the inner <input> element.
   */
  required?:    boolean

  /**
   * Layout direction for the label and control.
   * - `vertical`   → label above control (default — standard form layout)
   * - `horizontal` → label left, control right (useful in compact detail forms)
   */
  layout?:      'vertical' | 'horizontal'

  /** Visually hides the label (still present for screen readers) */
  hideLabel?:   boolean
}

const props = withDefaults(defineProps<Props>(), {
  layout:    'vertical',
  required:  false,
  hideLabel: false,
})

const hasError = props.error && props.error !== false
</script>

<template>
  <div
    class="ch-form-field"
    :class="[
      `ch-form-field--${layout}`,
      { 'ch-form-field--error': hasError },
    ]"
  >
    <!-- Label -->
    <label
      v-if="label"
      class="ch-form-field__label"
      :class="{ 'ch-form-field__label--hidden': hideLabel }"
      :for="inputId"
    >
      {{ label }}
      <!--
        Required asterisk — `aria-hidden` because the required state
        is communicated via the `required` attribute on the input itself.
        The asterisk is a visual-only convention.
      -->
      <span v-if="required" class="ch-form-field__required" aria-hidden="true">*</span>
      <!-- Optional hint inline with label (used for brief notes) -->
      <span v-if="hint && layout === 'horizontal'" class="ch-form-field__hint-inline">
        — {{ hint }}
      </span>
    </label>

    <!-- Control slot + hint + error stacked in a flex column -->
    <div class="ch-form-field__control-wrap">
      <!-- The actual input control goes here -->
      <slot />

      <!-- Hint text (below control in vertical layout) -->
      <p
        v-if="hint && layout === 'vertical'"
        class="ch-form-field__hint"
      >
        {{ hint }}
      </p>

      <!--
        Error message.
        `role="alert"` announces the error to screen readers when it appears.
        `aria-live="polite"` replays if the message changes (e.g. different
        validation message on second submit).
      -->
      <p
        v-if="hasError && typeof error === 'string'"
        class="ch-form-field__error"
        role="alert"
        aria-live="polite"
      >
        <!-- Error icon -->
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
          <circle cx="6" cy="6" r="5.25" stroke="currentColor" stroke-width="1.2"/>
          <path d="M6 3.5v3M6 8.5v.25" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
        </svg>
        {{ error }}
      </p>
    </div>
  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-form-field {
  display: flex;
  width:   100%;
}

/* ─── Vertical layout (default) ───────────────────────────────────────────── */
.ch-form-field--vertical {
  flex-direction: column;
  gap:            var(--ch-space-1_5);
}

/* ─── Horizontal layout ───────────────────────────────────────────────────── */
.ch-form-field--horizontal {
  flex-direction: row;
  align-items:    flex-start;
  gap:            var(--ch-space-4);
}

.ch-form-field--horizontal .ch-form-field__label {
  flex:      0 0 160px; /* fixed label column width */
  max-width: 160px;
  padding-top: var(--ch-space-2); /* optically align label with input text */
}

.ch-form-field--horizontal .ch-form-field__control-wrap {
  flex: 1;
}

/* ─── Label ───────────────────────────────────────────────────────────────── */
.ch-form-field__label {
  font-size:   var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color:       var(--ch-color-text);
  line-height: var(--ch-leading-normal);
  cursor:      default;
}

/* Visually hidden but still in the accessibility tree */
.ch-form-field__label--hidden {
  position: absolute;
  width:     1px;
  height:    1px;
  padding:   0;
  margin:    -1px;
  overflow:  hidden;
  clip:      rect(0, 0, 0, 0);
  white-space: nowrap;
  border:    0;
}

/* Required asterisk */
.ch-form-field__required {
  color:       var(--ch-color-danger);
  margin-left: var(--ch-space-0_5);
  font-weight: var(--ch-font-semibold);
}

/* Inline hint (horizontal layout) */
.ch-form-field__hint-inline {
  font-weight: var(--ch-font-normal);
  color:       var(--ch-color-text-subtle);
  font-size:   var(--ch-text-xs);
}

/* ─── Control wrapper ─────────────────────────────────────────────────────── */
.ch-form-field__control-wrap {
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-1);
  width:          100%;
}

/* ─── Hint ────────────────────────────────────────────────────────────────── */
.ch-form-field__hint {
  font-size:   var(--ch-text-xs);
  color:       var(--ch-color-text-subtle);
  line-height: var(--ch-leading-normal);
  margin:      0;
}

/* ─── Error message ───────────────────────────────────────────────────────── */
.ch-form-field__error {
  display:     flex;
  align-items: center;
  gap:         var(--ch-space-1);
  font-size:   var(--ch-text-xs);
  font-weight: var(--ch-font-medium);
  color:       var(--ch-color-danger);
  margin:      0;
  line-height: var(--ch-leading-normal);
}
</style>
