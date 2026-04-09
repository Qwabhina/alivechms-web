<script setup lang="ts">
/**
 * @component ChInput
 * @path /frontend/src/design-system/components/core/ChInput.vue
 * @description A controlled, single-line text input with adornment slots,
 * clearable mode, and full error/disabled/readonly state support.
 *
 * ─── Architecture: Wrapper + native input ────────────────────────────────────
 * Rather than styling the raw `<input>` element directly, ChInput uses a
 * wrapper `<div>` that handles the visual states (focus ring, border, bg),
 * while the inner `<input>` is visually "bare" (no border, transparent bg).
 *
 * Why? Because the border and focus ring need to surround the ENTIRE widget
 * (including leading/trailing icons), not just the text field itself.
 * If we put the border on the `<input>`, the icons would be outside it.
 *
 * ─── v-model integration ─────────────────────────────────────────────────────
 * ChInput is a controlled component: the parent owns the value via v-model.
 * The component NEVER mutates its `modelValue` prop directly (that would
 * violate Vue's one-way data flow). Instead:
 *   1. It reads `modelValue` from the prop and passes it to the input's `:value`
 *   2. On user input, it emits `update:modelValue` with the new string
 *   3. The parent updates its state, which flows back down as a new prop
 *
 * @example Basic v-model
 * <ChInput v-model="searchQuery" placeholder="Search members..." />
 *
 * @example With error state
 * <ChInput v-model="email" :error="errors.email" type="email" />
 *
 * @example With leading icon
 * <ChInput v-model="query" placeholder="Search...">
 *   <template #leading><SearchIcon /></template>
 * </ChInput>
 *
 * @example Password field with show/hide toggle
 * <ChInput v-model="password" :type="showPwd ? 'text' : 'password'">
 *   <template #trailing>
 *     <button @click="showPwd = !showPwd"><EyeIcon /></button>
 *   </template>
 * </ChInput>
 */

import { computed, ref, useSlots } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Supported HTML input types for text-style inputs.
 * We intentionally exclude `checkbox`, `radio`, `file`, `range`, `color`, etc.
 * — those are distinct components (ChCheckbox, ChRadio, etc.)
 */
type InputType = 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'

/** Three sizes matching the rest of the design system */
type Size = 'sm' | 'md' | 'lg'

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /**
   * The controlled value — bind with v-model.
   * Accepts `string | number` since number inputs return numbers natively,
   * but we always emit strings for consistency.
   */
  modelValue?:   string | number

  /** HTML input type. Defaults to 'text'. */
  type?:         InputType

  /** Placeholder text shown when the input is empty */
  placeholder?:  string

  /** Size of the input — affects padding, font size, and height */
  size?:         Size

  /** Disables the input entirely — non-interactive, dimmed */
  disabled?:     boolean

  /** Makes the input read-only — shows value but can't edit */
  readonly?:     boolean

  /**
   * Error state. Can be:
   *   - `string` — the error message to display (used by ChFormField)
   *   - `true`   — just shows the error styling without a message
   *   - `false` or `undefined` — no error
   */
  error?:        string | boolean

  /**
   * When true, shows an × button to clear the input value.
   * Only visible when the field has content.
   */
  clearable?:    boolean

  /** Maps to the HTML `id` attribute — used to connect a <label> */
  id?:           string

  /** Maps to the HTML `name` attribute — used in form data serialization */
  name?:         string

  /** Maps to the HTML `autocomplete` attribute ('on' | 'off' | 'email' | etc.) */
  autocomplete?: string

  /** Maximum character length for the input */
  maxlength?:    number
}

const props = withDefaults(defineProps<Props>(), {
  type:     'text',
  size:     'md',
  disabled: false,
  readonly: false,
})

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
  /** Fired on every keystroke — carries the new string value */
  'update:modelValue': [value: string]
  /** Fired when the input gains focus */
  focus:  [event: FocusEvent]
  /** Fired when the input loses focus */
  blur:   [event: FocusEvent]
  /** Fired when the × clear button is clicked */
  clear:  []
  /** Fired when Enter key is pressed — useful for search inputs */
  enter:  [event: KeyboardEvent]
}>()

// ─── Local State ──────────────────────────────────────────────────────────────

/**
 * Tracks whether the native input currently has focus.
 * This drives the focused ring/border on the WRAPPER element
 * (not the input itself), since the visual focus styles are
 * applied to the wrapper div via `.ch-input-wrapper--focused`.
 */
const isFocused = ref(false)

// ─── Slots ────────────────────────────────────────────────────────────────────

/**
 * We need to know which slots are provided BEFORE rendering, so we can
 * apply the correct padding-adjustment classes to the input.
 * `useSlots()` returns a reactive map of provided slot content.
 */
const slots = useSlots()

// ─── Computed ─────────────────────────────────────────────────────────────────

/** True if the error prop is truthy (either a string or boolean true) */
const hasError = computed(() => !!props.error)

/**
 * Normalize the modelValue to always be a string for the native input.
 * `?? ''` handles undefined modelValue (empty string default).
 */
const inputValue = computed(() => props.modelValue?.toString() ?? '')

/**
 * Show the clear button only when:
 * 1. The `clearable` prop is enabled
 * 2. There is content to clear (length > 0)
 * 3. The input is not disabled (disabled inputs can't be interacted with)
 */
const canClear = computed(() =>
  props.clearable && inputValue.value.length > 0 && !props.disabled
)

/**
 * Builds the wrapper div's class list.
 * The wrapper carries ALL visual state — focus ring, error border,
 * disabled background, and padding offsets for adornments.
 */
const wrapperClasses = computed(() => [
  'ch-input-wrapper',
  `ch-input-wrapper--${props.size}`, // size affects padding and height

  {
    // State modifiers
    'ch-input-wrapper--focused':  isFocused.value, // primary border + sharp outline
    'ch-input-wrapper--error':    hasError.value,  // red border + red outline
    'ch-input-wrapper--disabled': props.disabled,  // dimmed background
    'ch-input-wrapper--readonly': props.readonly,  // subtle background

    // Adornment modifiers — remove the input's left/right padding when a
    // slot is present, since the adornment provides the visual spacing instead
    'ch-input-wrapper--has-leading':  !!slots.leading,
    'ch-input-wrapper--has-trailing': !!slots.trailing || canClear.value,
  },
])

// ─── Event Handlers ───────────────────────────────────────────────────────────

/**
 * Handles every keystroke on the native input.
 * Casts `event.target` to `HTMLInputElement` (TypeScript needs the type assertion
 * since `event.target` is typed as `EventTarget`, not `HTMLInputElement`).
 * Emits the new string value upward to the parent.
 */
function onInput(e: Event) {
  emit('update:modelValue', (e.target as HTMLInputElement).value)
}

/** Sets focused state and re-emits the raw FocusEvent for parent handling */
function onFocus(e: FocusEvent) {
  isFocused.value = true
  emit('focus', e)
}

/** Clears focused state and re-emits the raw FocusEvent */
function onBlur(e: FocusEvent) {
  isFocused.value = false
  emit('blur', e)
}

/** Emits `enter` when the user presses Enter — useful for search inputs */
function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter') emit('enter', e)
}

/**
 * Clears the input by emitting an empty string update.
 * Also emits the `clear` event so parents can react to intentional clearing
 * (e.g. to also clear search results).
 */
function onClear() {
  emit('update:modelValue', '')
  emit('clear')
}
</script>

<template>
  <!--
    Wrapper div — owns all the visual "input box" styles.
    The native <input> inside is bare (no border, transparent bg).

    This div carries the focus ring, border color, background, and
    accommodates leading/trailing adornments within its flex layout.
  -->
  <div :class="wrapperClasses">

    <!--
      Leading adornment slot — rendered BEFORE the input text.
      Typical uses: search icon, currency symbol ($), country flag, user icon.
      The slot content is wrapped in a span with padding so it has breathing room.
      Example: <template #leading><SearchIcon /></template>
    -->
    <span v-if="$slots.leading" class="ch-input__adornment ch-input__adornment--leading">
      <slot name="leading"></slot>
    </span>

    <!--
      The native <input> element.

      `:value="inputValue"` — controlled: always reflects the prop value.
        We do NOT use v-model here because v-model on a native input inside
        a component would create a circular binding. We manually handle
        value → input → emit instead.

      `@input="onInput"` — fires on every keystroke, emits up to parent.

      `aria-invalid` — accessibility attribute. Screen readers use this to
        announce that the field has an error. We pass a boolean (true/false)
        rather than a string because that's the spec-correct value.
    -->
    <input
      :id="id"
      :name="name"
      :type="type"
      :value="inputValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :autocomplete="autocomplete"
      :maxlength="maxlength"
      :aria-invalid="hasError"
      class="ch-input"
      @input="onInput"
      @focus="onFocus"
      @blur="onBlur"
      @keydown="onKeydown"
    />

    <!--
      Clear button — appears when `clearable` is true and input has content.
      `type="button"` prevents this from submitting any parent <form>.
      `tabindex="-1"` keeps it out of the keyboard tab order — the user
        shouldn't need to tab to the clear button; it's a mouse convenience.
    -->
    <button
      v-if="canClear"
      type="button"
      class="ch-input__clear"
      tabindex="-1"
      aria-label="Clear input"
      @click="onClear"
    >
      <!-- Inline SVG × icon — no external dependency needed for a simple icon -->
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
        <path
          d="M10.5 3.5L3.5 10.5M3.5 3.5l7 7"
          stroke="currentColor"
          stroke-width="1.5"
          stroke-linecap="round"
        />
      </svg>
    </button>

    <!--
      Trailing adornment slot — rendered AFTER the input text.
      If provided, it overrides/replaces the clear button visually.
      Typical uses: password toggle, unit label (kg, %),  validation check.
      Example: <template #trailing><EyeIcon @click="togglePwd" /></template>
    -->
    <span v-if="$slots.trailing" class="ch-input__adornment ch-input__adornment--trailing">
      <slot name="trailing"></slot>
    </span>

  </div>
</template>

<style scoped>
/* ─── Wrapper ─────────────────────────────────────────────────────────────── */
.ch-input-wrapper {
  position:         relative;
  display:          flex;
  align-items:      center;
  background-color: var(--ch-color-surface);
  border:           1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
    /* 4px — input radius */

  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow   var(--ch-duration-fast) var(--ch-ease-out);
}

/* Hover state */
.ch-input-wrapper:hover:not(.ch-input-wrapper--disabled) {
  border-color: var(--ch-color-border-focus);
}

/* Focus state: brand-colored border + sharp outline */
.ch-input-wrapper--focused {
  border-color: var(--ch-color-primary);
  outline:      2px solid var(--ch-color-primary);
  outline-offset: 1px;
}

/* Error state */
.ch-input-wrapper--error {
  border-color: var(--ch-color-danger);
}

/* Error + focused: danger sharp outline */
.ch-input-wrapper--error.ch-input-wrapper--focused {
  outline:      2px solid var(--ch-color-danger);
  outline-offset: 1px;
}

/* Disabled */
.ch-input-wrapper--disabled {
  background-color: var(--ch-color-bg-subtle);
  cursor:           not-allowed;
}

/* Readonly */
.ch-input-wrapper--readonly {
  background-color: var(--ch-color-bg-subtle);
}

/* ─── Native Input Element ────────────────────────────────────────────────── */
.ch-input {
  flex:       1;        /* grows to fill all available space in the flex row */
  min-width:  0;        /* prevents flex overflow — flex children can shrink below content-size */
  background: transparent; /* shows wrapper background through */
  border:     none;
  outline:    none;     /* focus styles are on the WRAPPER, not this element */
  color:      var(--ch-color-text);
  font-family:var(--ch-font-sans);
  font-weight:var(--ch-font-normal);
  line-height:var(--ch-leading-normal);
  width:      100%;     /* fill the flex item width */
}

.ch-input::placeholder {
  color: var(--ch-color-text-subtle); /* lighter than normal text */
}

.ch-input:disabled {
  cursor: not-allowed;
  color:  var(--ch-color-text-disabled);
}

/* ─── Sizes ───────────────────────────────────────────────────────────────── */
/*
 * Padding goes on the INNER input, not the wrapper.
 * This ensures the clickable/tappable area is correct.
 * The `min-height` guarantees touch target sizes.
 */
.ch-input-wrapper--sm .ch-input {
  font-size:  var(--ch-text-xs);  /* 12px */
  padding:    var(--ch-space-1_5) var(--ch-space-3); /* 6px 12px */
  min-height: 28px;
}

.ch-input-wrapper--md .ch-input {
  font-size:  var(--ch-text-sm);  /* 14px */
  padding:    var(--ch-space-2) var(--ch-space-3_5); /* 8px 14px */
  min-height: 36px;
}

.ch-input-wrapper--lg .ch-input {
  font-size:  var(--ch-text-base); /* 16px */
  padding:    var(--ch-space-2_5) var(--ch-space-4); /* 10px 16px */
  min-height: 44px;
}

/* ─── Adornments ──────────────────────────────────────────────────────────── */
.ch-input__adornment {
  display:         flex;
  align-items:     center;
  justify-content: center;
  flex-shrink:     0;                        /* never compress the icon */
  color:           var(--ch-color-text-muted); /* de-emphasized icon color */
}

/* Adornment padding scales with input size */
.ch-input-wrapper--sm .ch-input__adornment { padding: 0 var(--ch-space-2); }
.ch-input-wrapper--md .ch-input__adornment { padding: 0 var(--ch-space-3); }
.ch-input-wrapper--lg .ch-input__adornment { padding: 0 var(--ch-space-3_5); }

/*
 * Remove the input's left/right padding when an adornment is present.
 * Without this, there'd be double-padding between the icon and the text cursor.
 * The adornment's own padding provides the spacing instead.
 */
.ch-input-wrapper--has-leading .ch-input  { padding-left:  0; }
.ch-input-wrapper--has-trailing .ch-input { padding-right: 0; }

/* ─── Clear Button ────────────────────────────────────────────────────────── */
.ch-input__clear {
  display:         flex;
  align-items:     center;
  justify-content: center;
  flex-shrink:     0;
  background:      none;
  border:          none;
  padding:         0 var(--ch-space-2);
  color:           var(--ch-color-text-subtle);
  cursor:          pointer;
  transition:      color var(--ch-duration-fast) var(--ch-ease-out);
}

/* Darken the × icon on hover to signal interactivity */
.ch-input__clear:hover {
  color: var(--ch-color-text);
}
</style>
