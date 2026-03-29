<script setup lang="ts">
/**
 * @component ChSlider
 * @path /frontend/src/design-system/components/forms/ChSlider.vue
 * @description A range slider for selecting a numeric value within a
 * bounded range. Supports a visible value display, optional suffix,
 * step increments, and full keyboard/screen-reader accessibility.
 *
 * @example Volume control
 * <ChFormField label="Volume">
 *   <ChSlider v-model="form.volume" :min="0" :max="100" show-value suffix="%" />
 * </ChFormField>
 *
 * @example Budget allocation (step of 500)
 * <ChSlider v-model="form.budget" :min="0" :max="10000" :step="500"
 *           show-value suffix=" GH₵" />
 *
 * @example Disabled
 * <ChSlider v-model="form.opacity" :disabled="true" />
 */

import { computed } from 'vue'

interface Props {
   modelValue: number
   label?: string
   hint?: string
   id?: string
   name?: string
   min?: number
   max?: number
   step?: number
   /** String appended to the displayed value: "%" → "75%", " GH₵" → "450 GH₵" */
   suffix?: string
   /** Shows the current numeric value to the right of the label */
   showValue?: boolean
   disabled?: boolean
   error?: string | boolean
   size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
   min: 0,
   max: 100,
   step: 1,
   suffix: '',
   showValue: false,
   disabled: false,
   size: 'md',
})

const emit = defineEmits<{
   'update:modelValue': [value: number]
   change: [value: number]
}>()

/**
 * Fill percentage — drives the width of the brand-colored fill bar.
 * Clamped defensively in case modelValue is outside [min, max].
 */
const fillPercent = computed(() => {
   const clamped = Math.min(props.max, Math.max(props.min, props.modelValue))
   return ((clamped - props.min) / (props.max - props.min)) * 100
})

/** Display string shown next to the label when showValue is true */
const displayValue = computed(() => `${props.modelValue}${props.suffix}`)

const hasError = computed(() => !!props.error)

function onInput(e: Event) {
   emit('update:modelValue', Number((e.target as HTMLInputElement).value))
}

function onChange(e: Event) {
   emit('change', Number((e.target as HTMLInputElement).value))
}
</script>

<template>
   <div class="ch-slider"
      :class="[`ch-slider--${size}`, { 'ch-slider--disabled': disabled, 'ch-slider--error': hasError }]">
      <!-- Label row: label text on left, current value on right -->
      <div v-if="label || showValue" class="ch-slider__header">
         <label v-if="label" class="ch-slider__label" :for="`${id}-input`">
            {{ label }}
         </label>
         <!--
        Value display uses a <span>, not a readonly <input>.
        A readonly input implies editability and receives focus in the tab order,
        which is confusing. A <span> is purely presentational.
      -->
         <span v-if="showValue" class="ch-slider__value" aria-hidden="true">
            {{ displayValue }}
         </span>
      </div>

      <!-- Track container: fill bar + native range input layered together -->
      <div class="ch-slider__track-wrap">
         <!--
        Fill bar — positioned absolutely behind the thumb.
        Width is driven by `fillPercent` computed from the current value.
        `pointer-events: none` so it doesn't intercept clicks meant for the input.
      -->
         <div class="ch-slider__fill" :style="{ width: `${fillPercent}%` }" aria-hidden="true" />

         <!--
        Native <input type="range"> handles all drag, keyboard, and touch
        behaviour. We just remove the native track and thumb styling via
        CSS and replace them with our own.
      -->
         <input :id="`${id}-input`" type="range" class="ch-slider__input" :name="name" :value="modelValue" :min="min"
            :max="max" :step="step" :disabled="disabled" :aria-label="label" :aria-valuemin="min" :aria-valuemax="max"
            :aria-valuenow="modelValue" :aria-valuetext="displayValue" :aria-invalid="hasError" @input="onInput"
            @change="onChange" />
      </div>

      <!-- Min / max labels -->
      <div class="ch-slider__range-labels">
         <span>{{ min }}{{ suffix }}</span>
         <span>{{ max }}{{ suffix }}</span>
      </div>

      <!-- Hint -->
      <p v-if="hint && !hasError" class="ch-slider__hint">{{ hint }}</p>

      <!-- Error -->
      <p v-if="hasError && typeof error === 'string'" class="ch-slider__error" role="alert" aria-live="polite">
         <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
            <circle cx="6" cy="6" r="5.25" stroke="currentColor" stroke-width="1.2" />
            <path d="M6 3.5v3M6 8.5v.25" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
         </svg>
         {{ error }}
      </p>
   </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-slider {
   display: flex;
   flex-direction: column;
   gap: var(--ch-space-1_5);
   width: 100%;
   font-family: var(--ch-font-sans);
}

.ch-slider--disabled {
   opacity: 0.5;
   pointer-events: none;
}

/* ─── Header row ──────────────────────────────────────────────────────────── */
.ch-slider__header {
   display: flex;
   align-items: baseline;
   justify-content: space-between;
   gap: var(--ch-space-2);
}

.ch-slider__label {
   font-size: var(--ch-text-sm);
   font-weight: var(--ch-font-medium);
   color: var(--ch-color-text);
   cursor: pointer;
}

/* Value display — monospace so the number doesn't shift width as it changes */
.ch-slider__value {
   font-size: var(--ch-text-sm);
   font-weight: var(--ch-font-semibold);
   font-family: var(--ch-font-mono);
   color: var(--ch-color-primary);
}

/* ─── Track wrap ──────────────────────────────────────────────────────────── */
.ch-slider__track-wrap {
   position: relative;
}

/* Sizes — controls track height and thumb size via CSS variables */
.ch-slider--sm .ch-slider__track-wrap {
   height: 4px;
}

.ch-slider--md .ch-slider__track-wrap {
   height: 6px;
}

.ch-slider--lg .ch-slider__track-wrap {
   height: 8px;
}

/* ─── Fill bar ────────────────────────────────────────────────────────────── */
.ch-slider__fill {
   position: absolute;
   top: 0;
   left: 0;
   height: 100%;
   background-color: var(--ch-color-primary);
   border-radius: var(--ch-radius-sm);
   pointer-events: none;
   transition: width var(--ch-duration-fast) var(--ch-ease-in-out);
}

.ch-slider--error .ch-slider__fill {
   background-color: var(--ch-color-danger);
}

/* ─── Native range input ──────────────────────────────────────────────────── */
/*
 * Positioned absolutely to fill the track-wrap so the clickable hit area
 * covers the whole track including the fill bar.
 */
.ch-slider__input {
   position: absolute;
   inset: 0;
   width: 100%;
   height: 100%;
   margin: 0;
   padding: 0;
   background: transparent;
   border: none;
   cursor: pointer;
   outline: none;
   /* Remove all native styling across browsers */
   -webkit-appearance: none;
   appearance: none;
}

/* ── Track styling ── */
/* The native track is hidden — our .ch-slider__fill takes its place */
.ch-slider__input::-webkit-slider-runnable-track {
   height: 100%;
   background-color: var(--ch-color-border-strong);
   border-radius: var(--ch-radius-sm);
}

.ch-slider__input::-moz-range-track {
   height: 100%;
   background-color: var(--ch-color-border-strong);
   border-radius: var(--ch-radius-sm);
}

/* ── Thumb styling ── */
/* Thumb is sized to be larger than the track so it "sits on" the track */
.ch-slider__input::-webkit-slider-thumb {
   -webkit-appearance: none;
   appearance: none;
   width: 20px;
   height: 20px;
   margin-top: -7px;
   /* center vertically on the track */
   background-color: var(--ch-color-surface);
   border: 2px solid var(--ch-color-primary);
   border-radius: var(--ch-radius-sm);
   cursor: pointer;
   box-shadow: var(--ch-shadow-sm);
   transition:
      transform var(--ch-duration-fast) var(--ch-ease-out),
      box-shadow var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-slider__input::-moz-range-thumb {
   width: 20px;
   height: 20px;
   background-color: var(--ch-color-surface);
   border: 2px solid var(--ch-color-primary);
   border-radius: var(--ch-radius-sm);
   cursor: pointer;
   box-shadow: var(--ch-shadow-sm);
   transition:
      transform var(--ch-duration-fast) var(--ch-ease-out),
      box-shadow var(--ch-duration-fast) var(--ch-ease-out);
}

/* Thumb hover / active */
.ch-slider__input:hover::-webkit-slider-thumb {
   transform: scale(1.15);
   box-shadow: 0 0 0 4px var(--ch-color-primary-muted);
}

.ch-slider__input:hover::-moz-range-thumb {
   transform: scale(1.15);
   box-shadow: 0 0 0 4px var(--ch-color-primary-muted);
}

.ch-slider__input:active::-webkit-slider-thumb {
   transform: scale(1.25);
}

.ch-slider__input:active::-moz-range-thumb {
   transform: scale(1.25);
}

/* Focus ring on the thumb */
.ch-slider__input:focus-visible::-webkit-slider-thumb {
   box-shadow: 0 0 0 3px var(--ch-color-primary-muted), var(--ch-shadow-sm);
}

.ch-slider__input:focus-visible::-moz-range-thumb {
   box-shadow: 0 0 0 3px var(--ch-color-primary-muted), var(--ch-shadow-sm);
}

/* Error state thumb */
.ch-slider--error .ch-slider__input::-webkit-slider-thumb {
   border-color: var(--ch-color-danger);
}

.ch-slider--error .ch-slider__input::-moz-range-thumb {
   border-color: var(--ch-color-danger);
}

/* ─── Range labels ────────────────────────────────────────────────────────── */
.ch-slider__range-labels {
   display: flex;
   justify-content: space-between;
   font-size: var(--ch-text-xs);
   color: var(--ch-color-text-subtle);
   font-family: var(--ch-font-mono);
}

/* ─── Hint / Error ────────────────────────────────────────────────────────── */
.ch-slider__hint {
   font-size: var(--ch-text-xs);
   color: var(--ch-color-text-subtle);
   line-height: var(--ch-leading-normal);
   margin: 0;
}

.ch-slider__error {
   display: flex;
   align-items: center;
   gap: var(--ch-space-1);
   font-size: var(--ch-text-xs);
   font-weight: var(--ch-font-medium);
   color: var(--ch-color-danger);
   margin: 0;
}
</style>