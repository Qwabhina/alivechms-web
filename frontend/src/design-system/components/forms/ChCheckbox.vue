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
 * Array group (multiple checkboxes bound to the same array):
 *   <ChCheckbox v-model="selectedGroups" value="youth" label="Youth" />
 *   <ChCheckbox v-model="selectedGroups" value="choir" label="Choir" />
 *   When `value` is set, the component adds/removes it from the array.
 *
 * ─── Indeterminate ───────────────────────────────────────────────────────────
 * Pass `:indeterminate="true"` for the "some but not all selected" state.
 * Common in select-all patterns (e.g. a table header checkbox).
 * NOTE: `indeterminate` is a DOM property, not an HTML attribute — the
 * component sets it imperatively via a template ref + watch.
 */

import { computed, ref, watch } from 'vue'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  modelValue?:    boolean | unknown[]
  /** The value added to / removed from the array when used in group mode */
  value?:         unknown
  label?:         string
  /** Hint text displayed below the label */
  hint?:          string
  disabled?:      boolean
  indeterminate?: boolean
  /** Error state — pass `true` for visual-only error, or a string to show a message */
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

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:modelValue': [value: boolean | unknown[]]
  change: [value: boolean | unknown[]]
}>()

// ─── Template ref + indeterminate DOM property ────────────────────────────────

/**
 * `indeterminate` is a JS DOM property, not an HTML attribute.
 * It cannot be set with `:indeterminate="..."` — it must be assigned
 * imperatively on the element reference.
 */
const inputRef = ref<HTMLInputElement | null>(null)

watch(
  () => props.indeterminate,
  (val) => {
    if (inputRef.value) inputRef.value.indeterminate = val
  },
  { immediate: true },
)

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Determines if the checkbox is currently checked.
 * - Boolean mode: modelValue is the bool directly
 * - Array mode:   modelValue contains the `value` prop
 */
const isChecked = computed(() => {
  if (Array.isArray(props.modelValue)) return props.modelValue.includes(props.value)
  return !!props.modelValue
})

const hasError = computed(() => !!props.error)

/** Render the error message string only when error is a non-empty string */
const errorMessage = computed(() =>
  typeof props.error === 'string' && props.error.length > 0 ? props.error : null
)

// ─── Handler ──────────────────────────────────────────────────────────────────

function onChange(event: Event) {
  const checked = (event.target as HTMLInputElement).checked

  if (Array.isArray(props.modelValue)) {
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
  <div class="ch-checkbox-wrapper" :class="`ch-checkbox-wrapper--${size}`">
    <label class="ch-checkbox" :class="[
      `ch-checkbox--${size}`,
      { 'ch-checkbox--disabled': disabled },
      { 'ch-checkbox--error': hasError },
    ]">
      <!-- Native checkbox — visually hidden but present in the a11y tree -->
      <input
ref="inputRef"
type="checkbox" class="ch-checkbox__input" :id="id" :name="name" :checked="isChecked"
        :disabled="disabled"
:aria-invalid="hasError ? 'true' : undefined"
        :aria-describedby="errorMessage ? `${id}-error` : undefined" @change="onChange" />

      <!--
        Custom visual box.
        CSS drives checked/indeterminate fill via the adjacent sibling selector
        (:checked + .ch-checkbox__box, :indeterminate + .ch-checkbox__box).
        The SVG icon is a pure visual affordance — aria is handled by the native input.
      -->
      <span class="ch-checkbox__box" aria-hidden="true">
        <!-- Checkmark -->
        <svg v-if="isChecked && !indeterminate" class="ch-checkbox__icon" width="10" height="10" viewBox="0 0 10 10"
          fill="none">
          <path d="M2 5l2.5 2.5L8 3" stroke="currentColor" stroke-width="1.6"
stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        <!-- Dash (indeterminate) -->
        <svg v-else-if="indeterminate" class="ch-checkbox__icon" width="10" height="10" viewBox="0 0 10 10" fill="none">
          <path d="M2.5 5h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        </svg>
      </span>

      <!-- Label text + hint — also accepts rich content via #label slot -->
      <span v-if="label || hint || $slots.label" class="ch-checkbox__content">
        <span class="ch-checkbox__label">
          <slot name="label">{{ label }}</slot>
        </span>
        <span v-if="hint" class="ch-checkbox__hint">{{ hint }}</span>
      </span>
    </label>

    <!-- Error message (only rendered when error is a non-empty string) -->
    <p v-if="errorMessage" :id="id ? `${id}-error` : undefined" class="ch-checkbox__error" role="alert">
      {{ errorMessage }}
    </p>
  </div>
</template>

<style scoped>
/* ─── Wrapper ─────────────────────────────────────────────────────────────── */
.ch-checkbox-wrapper {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1);
}
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
  flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--ch-radius-sm);
    border: 1.5px solid var(--ch-color-border-strong);
    background: var(--ch-color-surface);
    color: white;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    border-color     var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow       var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-checkbox--sm .ch-checkbox__box { width: 14px; height: 14px; }
.ch-checkbox--md .ch-checkbox__box { width: 16px; height: 16px; }
.ch-checkbox--lg .ch-checkbox__box { width: 20px; height: 20px; }

/* Checked / indeterminate — fill with primary color */
.ch-checkbox__input:checked+.ch-checkbox__box,
.ch-checkbox__input:indeterminate + .ch-checkbox__box {
  background:   var(--ch-color-primary);
  border-color: var(--ch-color-primary);
}

/* Focus ring forwarded from native input to custom box */
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
  gap: var(--ch-space-0_5);
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
  font-size: var(--ch-text-xs);
    color: var(--ch-color-text-subtle);
    line-height: var(--ch-leading-normal);
  }
  
  /* ─── Error message ───────────────────────────────────────────────────────── */
  .ch-checkbox__error {
    margin: 0;
  font-size:  var(--ch-text-xs);
  color: var(--ch-color-danger);
  line-height:var(--ch-leading-normal);
}
</style>