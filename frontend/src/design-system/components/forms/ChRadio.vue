<script setup lang="ts">
/**
 * @component ChRadio
 * @path /frontend/src/design-system/components/forms/ChRadio.vue
 * @description A radio button for single-choice selection within a group.
 * Bind all radios in the same group to the same v-model.
 *
 * @example Membership type selection
 * <ChFormField label="Membership Type">
 *   <div class="radio-group">
 *     <ChRadio v-model="form.type" value="full"      label="Full Member" />
 *     <ChRadio v-model="form.type" value="associate" label="Associate" />
 *     <ChRadio v-model="form.type" value="visitor"   label="Visitor" />
 *   </div>
 * </ChFormField>
 */

import { computed } from 'vue'

interface Props {
  /** Bind to a shared ref with the same v-model across all radios in the group */
  modelValue?: unknown
  /** The value this radio represents — emitted on selection */
  value:       unknown
  label?:      string
  hint?:       string
  disabled?:   boolean
  error?:      string | boolean
  id?:         string
  /** All radios in the same group should share the same name */
  name?:       string
  size?:       'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  size:     'md',
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: unknown]
  change: [value: unknown]
}>()

const isChecked = computed(() => props.modelValue === props.value)
const hasError  = computed(() => !!props.error)

function onChange() {
  emit('update:modelValue', props.value)
  emit('change', props.value)
}
</script>

<template>
  <label
    class="ch-radio"
    :class="[
      `ch-radio--${size}`,
      { 'ch-radio--disabled': disabled },
      { 'ch-radio--error':    hasError },
      { 'ch-radio--checked':  isChecked },
    ]"
  >
    <input
      type="radio"
      class="ch-radio__input"
      :id="id"
      :name="name"
      :checked="isChecked"
      :disabled="disabled"
      :aria-invalid="hasError"
      :value="value"
      @change="onChange"
    />

    <!--
      Custom radio circle.
      The inner dot scales in on selection via a CSS transform transition.
    -->
    <span class="ch-radio__circle" aria-hidden="true">
      <span class="ch-radio__dot" />
    </span>

    <span v-if="label || hint" class="ch-radio__content">
      <span v-if="label" class="ch-radio__label">{{ label }}</span>
      <span v-if="hint"  class="ch-radio__hint">{{ hint }}</span>
    </span>
  </label>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-radio {
  display:     inline-flex;
  align-items: flex-start;
  gap:         var(--ch-space-2_5);
  cursor:      pointer;
  user-select: none;
}

.ch-radio--disabled { cursor: not-allowed; opacity: 0.5; }

/* ─── Hide native input ───────────────────────────────────────────────────── */
.ch-radio__input {
  position: absolute;
  opacity:  0;
  width:    0;
  height:   0;
  margin:   0;
  pointer-events: none;
}

/* ─── Custom circle ───────────────────────────────────────────────────────── */
.ch-radio__circle {
  flex-shrink:    0;
  display:        flex;
  align-items:    center;
  justify-content:center;
  border-radius:  var(--ch-radius-full);
  border:         1.5px solid var(--ch-color-border-strong);
  background:     var(--ch-color-surface);
  transition:
    border-color     var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow       var(--ch-duration-fast) var(--ch-ease-out);
}

/* Sizes */
.ch-radio--sm .ch-radio__circle { width: 14px; height: 14px; }
.ch-radio--md .ch-radio__circle { width: 16px; height: 16px; }
.ch-radio--lg .ch-radio__circle { width: 20px; height: 20px; }

/* Checked outer ring */
.ch-radio--checked .ch-radio__circle {
  border-color: var(--ch-color-primary);
  background:   var(--ch-color-surface);
}

/* Focus ring */
.ch-radio__input:focus-visible ~ .ch-radio__circle {
  box-shadow: 0 0 0 3px var(--ch-color-primary-muted);
}

/* Error */
.ch-radio--error .ch-radio__circle { border-color: var(--ch-color-danger); }

/* ─── Inner dot ───────────────────────────────────────────────────────────── */
.ch-radio__dot {
  border-radius:  var(--ch-radius-full);
  background:     var(--ch-color-primary);
  /* Starts scaled to 0 — scales up on check */
  transform:      scale(0);
  transition:     transform var(--ch-duration-fast) var(--ch-ease-spring);
}

/* Dot sizes (proportional to circle) */
.ch-radio--sm .ch-radio__dot { width: 6px;  height: 6px; }
.ch-radio--md .ch-radio__dot { width: 7px;  height: 7px; }
.ch-radio--lg .ch-radio__dot { width: 9px;  height: 9px; }

/* Animate dot in when checked */
.ch-radio--checked .ch-radio__dot { transform: scale(1); }

/* ─── Label + hint ────────────────────────────────────────────────────────── */
.ch-radio__content {
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-0_5);
  padding-top:    1px;
}

.ch-radio__label {
  font-size:   var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color:       var(--ch-color-text);
  line-height: var(--ch-leading-snug);
}

.ch-radio__hint {
  font-size:  var(--ch-text-xs);
  color:      var(--ch-color-text-subtle);
  line-height:var(--ch-leading-normal);
}
</style>
