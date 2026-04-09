<script setup lang="ts">
/**
 * @component ChDatePicker
 * @path /frontend/src/design-system/components/forms/ChDatePicker.vue
 * @description A calendar popup date picker with optional date range mode,
 * min/max constraints, and keyboard navigation.
 *
 * ─── v-model ─────────────────────────────────────────────────────────────────
 * Single date:  v-model → Date | null
 * Range mode:   v-model → { start: Date | null; end: Date | null }
 *
 * ─── Implementation note ─────────────────────────────────────────────────────
 * No external date library dependency — pure native JS Date arithmetic.
 *
 * @example Single date
 * <ChDatePicker v-model="form.dob" placeholder="Date of birth" />
 *
 * @example Date range
 * <ChDatePicker v-model="form.period" :range="true"
 *   placeholder-start="From" placeholder-end="To" />
 *
 * @example With constraints
 * <ChDatePicker v-model="form.eventDate"
 *   :min-date="today" :max-date="endOfYear" />
 */

import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface DateRange {
  start: Date | null
  end: Date | null
}

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  modelValue: Date | DateRange | null
  range?: boolean
  placeholder?: string
  placeholderStart?: string
  placeholderEnd?: string
  minDate?: Date
  maxDate?: Date
  disabled?: boolean
  /** Error state — pass `true` for visual-only, or a string to show a message */
  error?: string | boolean
  size?: 'sm' | 'md' | 'lg'
  id?: string
  /** Display format for the trigger text. Default: 'dd/mm/yyyy' */
  displayFormat?: 'dd/mm/yyyy' | 'mm/dd/yyyy' | 'yyyy-mm-dd'
}

const props = withDefaults(defineProps<Props>(), {
  range: false,
  disabled: false,
  size: 'md',
  displayFormat: 'dd/mm/yyyy',
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:modelValue': [value: Date | DateRange | null]
  change: [value: Date | DateRange | null]
}>()

// ─── Refs ─────────────────────────────────────────────────────────────────────

const rootRef = ref<HTMLElement | null>(null)
const popupRef = ref<HTMLElement | null>(null)

// ─── State ────────────────────────────────────────────────────────────────────

const isOpen = ref(false)
const viewDate = ref(new Date())
const hoverDate = ref<Date | null>(null)
const rangeSelectStep = ref<'start' | 'end'>('start')

/** Popup position — 'bottom' (default) or 'top' when near the viewport bottom */
const popupPosition = ref<'bottom' | 'top'>('bottom')

// ─── Date utilities ───────────────────────────────────────────────────────────

const MONTHS = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
]
const DAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

/** Normalise a Date to midnight so time components don't affect comparisons */
function toMidnight(d: Date): Date {
  return new Date(d.getFullYear(), d.getMonth(), d.getDate())
}

function sameDay(a: Date | null, b: Date | null): boolean {
  if (!a || !b) return false
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  )
}

function formatDate(d: Date | null, fmt: string): string {
  if (!d) return ''
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const yyyy = String(d.getFullYear())
  if (fmt === 'dd/mm/yyyy') return `${dd}/${mm}/${yyyy}`
  if (fmt === 'mm/dd/yyyy') return `${mm}/${dd}/${yyyy}`
  return `${yyyy}-${mm}-${dd}`
}

/**
 * All date comparisons are normalised to midnight so that a `minDate` of
 * `new Date()` (which includes the current time) still allows today to be
 * selected.
 */
function isDisabled(d: Date): boolean {
  const day = toMidnight(d)
  if (props.minDate && day < toMidnight(props.minDate)) return true
  if (props.maxDate && day > toMidnight(props.maxDate)) return true
  return false
}

function isInRange(d: Date): boolean {
  if (!props.range) return false
  const val = props.modelValue as DateRange
  const start = val?.start
  const end = val?.end ?? hoverDate.value
  if (!start || !end) return false
  const lo = start <= end ? start : end
  const hi = start <= end ? end : start
  return d >= lo && d <= hi
}

// ─── Calendar grid ────────────────────────────────────────────────────────────

const calendarDays = computed<(Date | null)[]>(() => {
  const year = viewDate.value.getFullYear()
  const month = viewDate.value.getMonth()
  const first = new Date(year, month, 1).getDay()
  const total = new Date(year, month + 1, 0).getDate()

  const days: (Date | null)[] = []
  for (let i = 0; i < first; i++) days.push(null)
  for (let d = 1; d <= total; d++) days.push(new Date(year, month, d))
  while (days.length % 7 !== 0) days.push(null)
  return days
})

// ─── Selected value accessors ─────────────────────────────────────────────────

const selectedSingle = computed<Date | null>(() =>
  !props.range ? (props.modelValue as Date | null) : null,
)

const selectedRange = computed<DateRange>(() =>
  props.range
    ? ((props.modelValue as DateRange) ?? { start: null, end: null })
    : { start: null, end: null },
)

// ─── Display text ─────────────────────────────────────────────────────────────

const triggerText = computed(() => {
  if (props.range) {
    const { start, end } = selectedRange.value
    if (!start && !end) return ''
    return [formatDate(start, props.displayFormat), formatDate(end, props.displayFormat)]
      .filter(Boolean)
      .join(' → ')
  }
  return formatDate(selectedSingle.value, props.displayFormat)
})

const triggerPlaceholder = computed(() => {
  if (props.placeholder) return props.placeholder
  if (props.range) {
    const start = props.placeholderStart ?? 'Start date'
    const end = props.placeholderEnd ?? 'End date'
    return `${start} → ${end}`
  }
  return 'Select date'
})

/** Rendered error message string — undefined when error is boolean or absent */
const errorMessage = computed(() =>
  typeof props.error === 'string' && props.error.length > 0 ? props.error : null,
)

const hasError = computed(() => !!props.error)

// ─── Navigation ───────────────────────────────────────────────────────────────

function prevMonth() {
  const v = viewDate.value
  viewDate.value = new Date(v.getFullYear(), v.getMonth() - 1, 1)
}
function nextMonth() {
  const v = viewDate.value
  viewDate.value = new Date(v.getFullYear(), v.getMonth() + 1, 1)
}
function prevYear() {
  const v = viewDate.value
  viewDate.value = new Date(v.getFullYear() - 1, v.getMonth(), 1)
}
function nextYear() {
  const v = viewDate.value
  viewDate.value = new Date(v.getFullYear() + 1, v.getMonth(), 1)
}

// ─── Popup position ───────────────────────────────────────────────────────────

/**
 * Determines whether the popup should open downward or upward based on
 * available space below the trigger vs. the estimated popup height (~320px).
 */
function computePopupPosition() {
  if (!rootRef.value) return
  const rect = rootRef.value.getBoundingClientRect()
  const spaceBelow = window.innerHeight - rect.bottom
  const estimatedHeight = 320
  popupPosition.value =
    spaceBelow < estimatedHeight && rect.top > estimatedHeight ? 'top' : 'bottom'
}

// ─── Open / close ─────────────────────────────────────────────────────────────

function openCalendar() {
  if (props.disabled) return
  computePopupPosition()
  isOpen.value = true
  // Navigate to the month of the currently selected date
  const d = props.range ? selectedRange.value.start : selectedSingle.value
  if (d) viewDate.value = new Date(d.getFullYear(), d.getMonth(), 1)
}

function closeCalendar() {
  isOpen.value = false
  hoverDate.value = null
}

// ─── Selection ────────────────────────────────────────────────────────────────

function selectDay(d: Date | null) {
  if (!d || isDisabled(d)) return

  if (!props.range) {
    emit('update:modelValue', d)
    emit('change', d)
    closeCalendar()
    return
  }

  const current = selectedRange.value

  if (rangeSelectStep.value === 'start' || !current.start) {
    // First click — set start, keep picking for end
    const partial: DateRange = { start: d, end: null }
    emit('update:modelValue', partial)
    emit('change', partial)
    rangeSelectStep.value = 'end'
  } else {
    // Second click — complete the range, normalise order
    const start = current.start
    const result: DateRange = d >= start ? { start, end: d } : { start: d, end: start }
    emit('update:modelValue', result)
    emit('change', result)
    rangeSelectStep.value = 'start'
    closeCalendar()
  }
}

function clearDate() {
  const next = props.range ? { start: null, end: null } : null
  emit('update:modelValue', next)
  emit('change', next)
  rangeSelectStep.value = 'start'
}

// ─── Watch ────────────────────────────────────────────────────────────────────

/**
 * When the parent resets the model to an empty range, reset the step tracker
 * so the next click correctly sets the start rather than the end.
 */
watch(
  () => props.modelValue,
  (val) => {
    if (props.range) {
      const range = val as DateRange | null
      if (!range?.start && !range?.end) rangeSelectStep.value = 'start'
    }
  },
)

// ─── Click outside ────────────────────────────────────────────────────────────

function onDocClick(e: MouseEvent) {
  if (rootRef.value && !rootRef.value.contains(e.target as Node)) {
    closeCalendar()
  }
}

function onDocKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && isOpen.value) closeCalendar()
}

onMounted(() => {
  document.addEventListener('mousedown', onDocClick)
  document.addEventListener('keydown', onDocKeydown)
})
onUnmounted(() => {
  document.removeEventListener('mousedown', onDocClick)
  document.removeEventListener('keydown', onDocKeydown)
})
</script>

<template>
  <div
    ref="rootRef"
    class="ch-datepicker"
    :class="[
      `ch-datepicker--${size}`,
      {
        'ch-datepicker--disabled': disabled,
        'ch-datepicker--error': hasError,
        'ch-datepicker--open': isOpen,
      },
    ]"
  >
    <!-- ── Trigger ─────────────────────────────────────────────────────────── -->
    <div
      class="ch-datepicker__trigger"
      role="button"
      :aria-haspopup="true"
      :aria-expanded="isOpen"
      :aria-label="triggerPlaceholder"
      :tabindex="disabled ? -1 : 0"
      @click="openCalendar"
      @keydown.enter.prevent="openCalendar"
      @keydown.space.prevent="openCalendar"
    >
      <svg
        class="ch-datepicker__icon"
        width="16"
        height="16"
        viewBox="0 0 16 16"
        fill="none"
        aria-hidden="true"
      >
        <rect x="2" y="3" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3" />
        <path
          d="M5 1v3M11 1v3M2 7h12"
          stroke="currentColor"
          stroke-width="1.3"
          stroke-linecap="round"
        />
      </svg>

      <span
        class="ch-datepicker__display"
        :class="{ 'ch-datepicker__display--placeholder': !triggerText }"
        aria-live="polite"
      >
        {{ triggerText || triggerPlaceholder }}
      </span>

      <button
        v-if="triggerText"
        type="button"
        class="ch-datepicker__clear"
        aria-label="Clear date"
        @click.stop="clearDate"
      >
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
          <path
            d="M10.5 3.5l-7 7M3.5 3.5l7 7"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
      </button>
    </div>

    <!-- ── Calendar popup ──────────────────────────────────────────────────── -->
    <Transition name="ch-datepicker-drop">
      <div
        v-if="isOpen"
        ref="popupRef"
        class="ch-datepicker__popup"
        :class="`ch-datepicker__popup--${popupPosition}`"
        role="dialog"
        aria-modal="true"
        :aria-label="`Choose ${range ? 'date range' : 'date'}`"
      >
        <!-- Navigation header -->
        <div class="ch-datepicker__nav">
          <button
            type="button"
            class="ch-datepicker__nav-btn"
            aria-label="Previous year"
            @click="prevYear"
          >
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
              <path
                d="M8.5 3L5 7l3.5 4M5.5 3L2 7l3.5 4"
                stroke="currentColor"
                stroke-width="1.3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>
          <button
            type="button"
            class="ch-datepicker__nav-btn"
            aria-label="Previous month"
            @click="prevMonth"
          >
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
              <path
                d="M9 3L5 7l4 4"
                stroke="currentColor"
                stroke-width="1.3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>

          <span class="ch-datepicker__month-label" aria-live="polite">
            {{ MONTHS[viewDate.getMonth()] }} {{ viewDate.getFullYear() }}
          </span>

          <button
            type="button"
            class="ch-datepicker__nav-btn"
            aria-label="Next month"
            @click="nextMonth"
          >
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
              <path
                d="M5 3l4 4-4 4"
                stroke="currentColor"
                stroke-width="1.3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>
          <button
            type="button"
            class="ch-datepicker__nav-btn"
            aria-label="Next year"
            @click="nextYear"
          >
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
              <path
                d="M5.5 3L9 7l-3.5 4M8.5 3L12 7l-3.5 4"
                stroke="currentColor"
                stroke-width="1.3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>
        </div>

        <!-- Day-of-week headers -->
        <div
          class="ch-datepicker__grid"
          role="grid"
          :aria-label="`${MONTHS[viewDate.getMonth()]} ${viewDate.getFullYear()}`"
        >
          <div
            v-for="day in DAYS"
            :key="day"
            class="ch-datepicker__day-header"
            role="columnheader"
            :aria-label="day"
          >
            {{ day }}
          </div>

          <!-- Calendar day cells — <button> for keyboard accessibility -->
          <button
            v-for="(day, i) in calendarDays"
            :key="i"
            type="button"
            class="ch-datepicker__day"
            :class="{
              'ch-datepicker__day--empty': !day,
              'ch-datepicker__day--today': day && sameDay(day, new Date()),
              'ch-datepicker__day--selected': day && !range && sameDay(day, selectedSingle),
              'ch-datepicker__day--range-start': day && range && sameDay(day, selectedRange.start),
              'ch-datepicker__day--range-end': day && range && sameDay(day, selectedRange.end),
              'ch-datepicker__day--in-range': day && isInRange(day),
              'ch-datepicker__day--disabled': day && isDisabled(day),
            }"
            :disabled="!day || isDisabled(day) || undefined"
            :tabindex="day ? 0 : -1"
            :aria-label="
              day ? `${day.getDate()} ${MONTHS[day.getMonth()]} ${day.getFullYear()}` : undefined
            "
            :aria-pressed="
              day
                ? (!range && sameDay(day, selectedSingle)) ||
                  (range && (sameDay(day, selectedRange.start) || sameDay(day, selectedRange.end)))
                : undefined
            "
            :aria-disabled="day && isDisabled(day) ? 'true' : undefined"
            role="gridcell"
            @click="selectDay(day)"
            @mouseenter="hoverDate = day"
            @mouseleave="hoverDate = null"
          >
            <span v-if="day" class="ch-datepicker__day-num" aria-hidden="true">{{
              day.getDate()
            }}</span>
          </button>
        </div>

        <!-- Range selection hint -->
        <div v-if="range" class="ch-datepicker__range-hint" aria-live="polite">
          {{ rangeSelectStep === 'start' ? 'Select start date' : 'Select end date' }}
        </div>
      </div>
    </Transition>

    <!-- Error message -->
    <p
      v-if="errorMessage"
      :id="id ? `${id}-error` : undefined"
      class="ch-datepicker__error"
      role="alert"
    >
      {{ errorMessage }}
    </p>
  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-datepicker {
  position: relative;
  width: 100%;
}

/* ─── Trigger ─────────────────────────────────────────────────────────────── */
.ch-datepicker__trigger {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    outline-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-datepicker--sm .ch-datepicker__trigger {
  padding: var(--ch-space-1_5) var(--ch-space-3);
  min-height: 32px;
  font-size: var(--ch-text-xs);
}
.ch-datepicker--md .ch-datepicker__trigger {
  padding: var(--ch-space-2) var(--ch-space-3_5);
  min-height: 38px;
  font-size: var(--ch-text-sm);
}
.ch-datepicker--lg .ch-datepicker__trigger {
  padding: var(--ch-space-2_5) var(--ch-space-4);
  min-height: 44px;
  font-size: var(--ch-text-base);
}

/* Open state uses the isOpen class binding — now actually applied */
.ch-datepicker--open .ch-datepicker__trigger,
.ch-datepicker__trigger:focus-within {
  border-color: var(--ch-color-border-focus);
  outline: 2px solid var(--ch-color-primary);
  outline-offset: -1px;
}
.ch-datepicker--error .ch-datepicker__trigger {
  border-color: var(--ch-color-danger);
}
.ch-datepicker--error.ch-datepicker--open .ch-datepicker__trigger,
.ch-datepicker--error .ch-datepicker__trigger:focus-within {
  outline: 2px solid var(--ch-color-danger);
  outline-offset: -1px;
}
.ch-datepicker--disabled .ch-datepicker__trigger {
  opacity: 0.5;
  cursor: not-allowed;
}

.ch-datepicker__icon {
  color: var(--ch-color-text-subtle);
  flex-shrink: 0;
}
.ch-datepicker__display {
  flex: 1;
  color: var(--ch-color-text);
}
.ch-datepicker__display--placeholder {
  color: var(--ch-color-text-subtle);
}

.ch-datepicker__clear {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: var(--ch-color-text-subtle);
  display: flex;
  align-items: center;
  transition: color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-datepicker__clear:hover {
  color: var(--ch-color-text);
}

/* ─── Popup ───────────────────────────────────────────────────────────────── */
.ch-datepicker__popup {
  position: absolute;
  left: 0;
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  box-shadow: var(--ch-shadow-xl);
  z-index: var(--ch-z-dropdown);
  padding: var(--ch-space-4);
  width: 280px;
  user-select: none;
}

/* Opens downward (default) */
.ch-datepicker__popup--bottom {
  top: calc(100% + var(--ch-space-1));
}

/* Opens upward when near the viewport bottom */
.ch-datepicker__popup--top {
  bottom: calc(100% + var(--ch-space-1));
}
/* ─── Navigation ──────────────────────────────────────────────────────────── */
.ch-datepicker__nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--ch-space-3);
}

.ch-datepicker__month-label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.ch-datepicker__nav-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--ch-color-text-subtle);
  display: flex;
  align-items: center;
  padding: var(--ch-space-1);
  border-radius: var(--ch-radius-md);
  transition:
    color var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-datepicker__nav-btn:hover {
  color: var(--ch-color-text);
  background-color: var(--ch-color-bg-muted);
}

/* ─── Calendar grid ───────────────────────────────────────────────────────── */
.ch-datepicker__grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 2px;
}

.ch-datepicker__day-header {
  text-align: center;
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text-subtle);
  padding: var(--ch-space-1) 0;
  letter-spacing: var(--ch-tracking-wide);
}

/* Day cells — now <button> elements for keyboard accessibility */
.ch-datepicker__day {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--ch-radius-sm);
  border: none;
  background: none;
  cursor: pointer;
  padding: 0;
  transition: background-color var(--ch-duration-fast) var(--ch-ease-out);
  aspect-ratio: 1;
}

.ch-datepicker__day:disabled {
  opacity: 0.3;
  cursor: not-allowed;
  pointer-events: none;
}

/* Empty placeholder cells */
.ch-datepicker__day--empty {
  cursor: default;
  pointer-events: none;
}

.ch-datepicker__day:not(:disabled):not(.ch-datepicker__day--empty):hover {
  background: var(--ch-color-bg-muted);
}
.ch-datepicker__day:focus-visible {
  outline: 2px solid var(--ch-color-primary);
  outline-offset: 1px;
}

/* Today */
.ch-datepicker__day--today .ch-datepicker__day-num {
  color: var(--ch-color-primary);
  font-weight: var(--ch-font-semibold);
}
/* Selected (single) / range endpoints */
.ch-datepicker__day--selected,
.ch-datepicker__day--range-start,
.ch-datepicker__day--range-end {
  background: var(--ch-color-primary);
  border-radius: var(--ch-radius-sm);
}

.ch-datepicker__day--selected .ch-datepicker__day-num,
.ch-datepicker__day--range-start .ch-datepicker__day-num,
.ch-datepicker__day--range-end .ch-datepicker__day-num {
  color: var(--ch-color-primary-fg);
}
/* Range fill — flat edges to create a continuous band */
.ch-datepicker__day--in-range {
  background: var(--ch-color-primary-subtle);
  border-radius: 0;
}

.ch-datepicker__day--range-start {
  border-radius: var(--ch-radius-sm) 0 0 var(--ch-radius-sm);
}

.ch-datepicker__day--range-end {
  border-radius: 0 var(--ch-radius-sm) var(--ch-radius-sm) 0;
}

.ch-datepicker__day-num {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text);
  line-height: 1;
}

/* ─── Range hint ──────────────────────────────────────────────────────────── */
.ch-datepicker__range-hint {
  margin-top: var(--ch-space-3);
  text-align: center;
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
  padding-top: var(--ch-space-2);
  border-top: 1px solid var(--ch-color-border);
}

/* ─── Error message ───────────────────────────────────────────────────────── */
.ch-datepicker__error {
  margin: var(--ch-space-1) 0 0;
  font-size: var(--ch-text-xs);
  color: var(--ch-color-danger);
  line-height: var(--ch-leading-normal);
}
/* ─── Popup transition ────────────────────────────────────────────────────── */
.ch-datepicker-drop-enter-active {
  transition:
    opacity var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-spring);
}

.ch-datepicker-drop-leave-active {
  transition: opacity var(--ch-duration-fast) var(--ch-ease-in);
}

.ch-datepicker-drop-enter-from,
.ch-datepicker-drop-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}
</style>
