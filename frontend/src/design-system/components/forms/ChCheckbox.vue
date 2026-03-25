<script setup lang="ts">
/**
 * @component ChCheckbox
 * @path /frontend/src/design-system/components/forms/ChCheckbox.vue
 * @description A styled checkbox supporting checked, unchecked, and
 * indeterminate states. Can be used standalone or in a checkbox group.
 *
 * ─── v-model patterns ────────────────────────────────────────────────────────
 * Single boolean:
 *   <ChCheckbox v-model="isActive" label="Active member" />
 *
 * Array group (multiple checkboxes bound to same array):
 *   <ChCheckbox v-model="selectedGroups" value="youth" label="Youth" />
 *   <ChCheckbox v-model="selectedGroups" value="choir" label="Choir" />
 *   When `value` prop is set, the component adds/removes it from the array.
 *
 * ─── Indeterminate ───────────────────────────────────────────────────────────
 * Pass `:indeterminate="true"` for the "some but not all selected" state.
 * Common in select-all patterns (e.g. ChTable's header checkbox).
 */

import { computed } from 'vue'

interface Props {
  modelValue?:    boolean | unknown[]
  /** The value added to / removed from the array when used in group mode */
  value?:         unknown
  label?:         string
  /** Hint text displayed below the label */
  hint?:          string
  disabled?:      boolean
  indeterminate?: boolean
  error?:         string | boolean
  id?:            string
  name?:          string
  size?:          'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  size:          'md',
  disabled:      false,
  indeterminate: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean | unknown[]]
  change: [value: boolean | unknown[]]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Determines if the checkbox is currently checked.
 * - Boolean mode: modelValue is the bool directly
 * - Array mode: modelValue contains the `value` prop
 */
const isChecked = computed(() => {
  if (Array.isArray(props.modelValue)) {
    return props.modelValue.includes(props.value)
  }
  return !!props.modelValue
})

const hasError = computed(() => !!props.error)

// ─── Handler ──────────────────────────────────────────────────────────────────

function onChange(event: Event) {
  const checked = (event.target as HTMLInputElement).checked

  if (Array.isArray(props.modelValue)) {
    // Array group mode — add or remove value
    const next = checked
      ? [...props.modelValue, props.value]
      : props.modelValue.filter(v => v !== props.value)
    emit('update:modelValue', next)
    emit('change', next)
  } else {
    emit('update:modelValue', checked)
    emit('change', checked)
  }
}
</script>

<template>
  <label
    class="ch-checkbox"
    :class="[
      `ch-checkbox--${size}`,
      { 'ch-checkbox--disabled': disabled },
      { 'ch-checkbox--error':    hasError },
    ]"
  >
    <!-- Visually hidden native checkbox — drives all a11y behaviour -->
    <input
      type="checkbox"
      class="ch-checkbox__input"
      :id="id"
      :name="name"
      :checked="isChecked"
      :indeterminate="indeterminate"
      :disabled="disabled"
      :aria-invalid="hasError"
      @change="onChange"
    />

    <!--
      Custom visual checkbox box.
      The check/dash SVG is conditionally shown based on state.
      CSS handles all color states via the :checked and adjacent sibling selector.
    -->
    <span class="ch-checkbox__box" aria-hidden="true">
      <!-- Checkmark (checked state) -->
      <svg
        v-if="isChecked && !indeterminate"
        class="ch-checkbox__icon"
        width="10" height="10" viewBox="0 0 10 10" fill="none"
      >
        <path d="M2 5l2.5 2.5L8 3"
              stroke="currentColor" stroke-width="1.6"
              stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <!-- Dash (indeterminate state) -->
      <svg
        v-else-if="indeterminate"
        class="ch-checkbox__icon"
        width="10" height="10" viewBox="0 0 10 10" fill="none"
      >
        <path d="M2.5 5h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </span>

    <!-- Label + hint -->
    <span v-if="label || hint" class="ch-checkbox__content">
      <span v-if="label" class="ch-checkbox__label">{{ label }}</span>
      <span v-if="hint"  class="ch-checkbox__hint">{{ hint }}</span>
    </span>
  </label>
</template>

<style scoped>
/* ─── Root label ──────────────────────────────────────────────────────────── */
.ch-checkbox {
  display:     inline-flex;
  align-items: flex-start;
  gap:         var(--ch-space-2_5);
  cursor:      pointer;
  user-select: none;
}

.ch-checkbox--disabled { cursor: not-allowed; opacity: 0.5; }

/* ─── Hide native input (keep in a11y tree) ───────────────────────────────── */
.ch-checkbox__input {
  position:  absolute;
  opacity:   0;
  width:     0;
  height:    0;
  margin:    0;
  pointer-events: none;
}

/* ─── Custom box ──────────────────────────────────────────────────────────── */
.ch-checkbox__box {
  flex-shrink:    0;
  display:        flex;
  align-items:    center;
  justify-content:center;
  border-radius:  var(--ch-radius-none);
  border:         1.5px solid var(--ch-color-border-strong);
  background:     var(--ch-color-surface);
  color:          white;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    border-color     var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow       var(--ch-duration-fast) var(--ch-ease-out);
}

/* Sizes */
.ch-checkbox--sm .ch-checkbox__box { width: 14px; height: 14px; }
.ch-checkbox--md .ch-checkbox__box { width: 16px; height: 16px; }
.ch-checkbox--lg .ch-checkbox__box { width: 20px; height: 20px; }

/* Checked and indeterminate — fill with primary color */
.ch-checkbox__input:checked + .ch-checkbox__box,
.ch-checkbox__input:indeterminate + .ch-checkbox__box {
  background:   var(--ch-color-primary);
  border-color: var(--ch-color-primary);
}

/* Focus ring on the custom box when native input is focused */
.ch-checkbox__input:focus-visible + .ch-checkbox__box {
  outline: 2px solid var(--ch-color-primary);
  outline-offset: 2px;
}

/* Error state */
.ch-checkbox--error .ch-checkbox__box {
  border-color: var(--ch-color-danger);
}

/* ─── Label + hint ────────────────────────────────────────────────────────── */
.ch-checkbox__content {
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-0_5);
  /* Vertically center the label text with the checkbox box */
  padding-top:    1px;
}

.ch-checkbox--sm .ch-checkbox__content { padding-top: 0; }
.ch-checkbox--lg .ch-checkbox__content { padding-top: 2px; }

.ch-checkbox__label {
  font-size:   var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color:       var(--ch-color-text);
  line-height: var(--ch-leading-snug);
}

.ch-checkbox__hint {
  font-size:  var(--ch-text-xs);
  color:      var(--ch-color-text-subtle);
  line-height:var(--ch-leading-normal);
}
</style>
