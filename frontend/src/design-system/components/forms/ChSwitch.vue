<script setup lang="ts">
/**
 * @component ChSwitch
 * @path /frontend/src/design-system/components/forms/ChSwitch.vue
 * @description A toggle switch for binary on/off state. Semantically a
 * `role="switch"` button, so it works with keyboard and screen readers
 * without needing a hidden checkbox underneath.
 *
 * ─── When to use Switch vs Checkbox ─────────────────────────────────────────
 * - Switch   → setting that takes immediate effect ("Enable notifications")
 * - Checkbox → item in a form submitted later ("I agree to terms")
 *
 * @example Basic
 * <ChSwitch v-model="form.isActive" label="Active member" />
 *
 * @example With hint
 * <ChSwitch v-model="form.emailConsent"
 *   label="Email updates"
 *   hint="We'll send at most one email per week." />
 *
 * @example Disabled
 * <ChSwitch v-model="form.archived" label="Archived" :disabled="true" />
 */

interface Props {
   modelValue: boolean
   label?: string
   hint?: string
   id?: string
   disabled?: boolean
   /** Error state — turns the track red and is readable by screen readers */
   error?: string | boolean
   size?: 'sm' | 'md'
}

const props = withDefaults(defineProps<Props>(), {
   disabled: false,
   size: 'md',
})

const emit = defineEmits<{
   'update:modelValue': [value: boolean]
   change: [value: boolean]
}>()

function toggle() {
   if (props.disabled) return
   emit('update:modelValue', !props.modelValue)
   emit('change', !props.modelValue)
}
</script>

<template>
   <button :id="id" type="button" role="switch" class="ch-switch" :class="[
      `ch-switch--${size}`,
      { 'ch-switch--checked': modelValue },
      { 'ch-switch--disabled': disabled },
      { 'ch-switch--error': !!error },
   ]" :aria-checked="modelValue" :aria-label="!label ? (typeof error === 'string' ? error : undefined) : undefined"
      :disabled="disabled" @click="toggle">
      <!-- Track + thumb -->
      <span class="ch-switch__track" aria-hidden="true">
         <span class="ch-switch__thumb" />
      </span>

      <!-- Label + hint -->
      <span v-if="label || hint" class="ch-switch__content">
         <span v-if="label" class="ch-switch__label">{{ label }}</span>
         <span v-if="hint" class="ch-switch__hint">{{ hint }}</span>
      </span>
   </button>

   <!-- Error message lives outside the button so it doesn't become its label -->
   <p v-if="error && typeof error === 'string'" class="ch-switch__error" role="alert" aria-live="polite">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
         <circle cx="6" cy="6" r="5.25" stroke="currentColor" stroke-width="1.2" />
         <path d="M6 3.5v3M6 8.5v.25" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
      </svg>
      {{ error }}
   </p>
</template>

<style scoped>
/* ─── Root button ─────────────────────────────────────────────────────────── */
/*
 * The outer element is a <button role="switch"> so clicking anywhere on
 * it — track, thumb, or label — toggles the state.
 * `display: inline-flex` lets it sit naturally inline in a form field.
 */
.ch-switch {
   display: inline-flex;
   align-items: flex-start;
   gap: var(--ch-space-2_5);
   background: none;
   border: none;
   padding: 0;
   cursor: pointer;
   user-select: none;
   font-family: var(--ch-font-sans);
   text-align: left;
}

.ch-switch--disabled {
   opacity: 0.5;
   cursor: not-allowed;
   pointer-events: none;
}

/* ─── Focus ring on the track ─────────────────────────────────────────────── */
.ch-switch:focus-visible .ch-switch__track {
   box-shadow: 0 0 0 3px var(--ch-color-primary-muted);
   outline: none;
}

.ch-switch--error:focus-visible .ch-switch__track {
   box-shadow: 0 0 0 3px var(--ch-color-danger-bg);
}

/* ─── Track ───────────────────────────────────────────────────────────────── */
.ch-switch__track {
   position: relative;
   flex-shrink: 0;
   background-color: var(--ch-color-border-strong);
   border-radius: var(--ch-radius-full);
   transition: background-color var(--ch-duration-fast) var(--ch-ease-out);
}

/* Sizes */
.ch-switch--sm .ch-switch__track {
   width: 32px;
   height: 18px;
}

.ch-switch--md .ch-switch__track {
   width: 40px;
   height: 22px;
}

/* Checked */
.ch-switch--checked .ch-switch__track {
   background-color: var(--ch-color-primary);
}

/* Error */
.ch-switch--error .ch-switch__track {
   background-color: var(--ch-color-danger);
}

/* ─── Thumb ───────────────────────────────────────────────────────────────── */
.ch-switch__thumb {
   position: absolute;
   top: 3px;
   left: 3px;
   background-color: var(--ch-color-surface);
   border-radius: var(--ch-radius-full);
   box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);
   /*
   * Use duration-instant (50ms) — defined in spacing.ts specifically for
   * toggle switches. Faster than duration-fast (100ms) so it feels snappy
   * rather than sluggish for a binary action.
   */
   transition:
      transform var(--ch-duration-instant) var(--ch-ease-out),
      background-color var(--ch-duration-instant) var(--ch-ease-out);
}

/* Sizes */
.ch-switch--sm .ch-switch__thumb {
   width: 12px;
   height: 12px;
}

.ch-switch--md .ch-switch__thumb {
   width: 16px;
   height: 16px;
}

/* Translate right when checked — distance = track width - thumb width - (left offset × 2) */
.ch-switch--sm.ch-switch--checked .ch-switch__thumb {
   transform: translateX(14px);
}

.ch-switch--md.ch-switch--checked .ch-switch__thumb {
   transform: translateX(18px);
}

/* ─── Label + hint ────────────────────────────────────────────────────────── */
.ch-switch__content {
   display: flex;
   flex-direction: column;
   gap: var(--ch-space-0_5);
   padding-top: 2px;
   /* optical alignment with track center */
}

.ch-switch--sm .ch-switch__content {
   padding-top: 1px;
}

.ch-switch__label {
   font-size: var(--ch-text-sm);
   font-weight: var(--ch-font-medium);
   color: var(--ch-color-text);
   line-height: var(--ch-leading-snug);
}

.ch-switch__hint {
   font-size: var(--ch-text-xs);
   color: var(--ch-color-text-subtle);
   line-height: var(--ch-leading-normal);
}

/* ─── Error message ───────────────────────────────────────────────────────── */
.ch-switch__error {
   display: flex;
   align-items: center;
   gap: var(--ch-space-1);
   margin-top: var(--ch-space-1);
   font-size: var(--ch-text-xs);
   font-weight: var(--ch-font-medium);
   color: var(--ch-color-danger);
   margin: var(--ch-space-1) 0 0;
}
</style>