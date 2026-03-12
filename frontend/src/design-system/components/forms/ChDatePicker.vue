<script setup lang="ts">
/**
 * @component ChDatePicker
 * @path /frontend/src/design-system/components/forms/ChDatePicker.vue
 * @description A calendar popup date picker with optional date range mode,
 * manual text entry, min/max constraints, and keyboard navigation.
 *
 * ─── v-model ─────────────────────────────────────────────────────────────────
 * Single date:  v-model binds to a Date | null
 * Range mode:   v-model binds to { start: Date | null; end: Date | null }
 *
 * ─── Implementation note ─────────────────────────────────────────────────────
 * This is a self-contained calendar with no external date library dependency.
 * Date arithmetic is done with native JS Date methods. If the project later
 * adds date-fns or dayjs, this component can be updated to use them internally
 * without changing the external API.
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
 *   :min-date="new Date()" :max-date="endOfYear" />
 */

import { ref, computed, watch } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface DateRange {
  start: Date | null
  end:   Date | null
}

interface Props {
  modelValue:         Date | DateRange | null
  range?:             boolean
  placeholder?:       string
  placeholderStart?:  string
  placeholderEnd?:    string
  minDate?:           Date
  maxDate?:           Date
  disabled?:          boolean
  error?:             string | boolean
  size?:              'sm' | 'md' | 'lg'
  id?:                string
  /** Format for the text input display. Default: 'dd/mm/yyyy' (Ghana standard) */
  displayFormat?:     'dd/mm/yyyy' | 'mm/dd/yyyy' | 'yyyy-mm-dd'
}

const props = withDefaults(defineProps<Props>(), {
  range:          false,
  disabled:       false,
  size:           'md',
  displayFormat:  'dd/mm/yyyy',
})

const emit = defineEmits<{
  'update:modelValue': [value: Date | DateRange | null]
  change: [value: Date | DateRange | null]
}>()

// ─── State ────────────────────────────────────────────────────────────────────

const isOpen         = ref(false)
const viewDate       = ref(new Date())  // the month/year currently in view
const hoverDate      = ref<Date | null>(null)
const rangeSelectStep = ref<'start' | 'end'>('start') // which end of range is being picked

// ─── Date utilities ───────────────────────────────────────────────────────────

const MONTHS  = ['January','February','March','April','May','June',
                 'July','August','September','October','November','December']
const DAYS    = ['Su','Mo','Tu','We','Th','Fr','Sa']

function sameDay(a: Date | null, b: Date | null): boolean {
  if (!a || !b) return false
  return a.getFullYear() === b.getFullYear() &&
         a.getMonth()    === b.getMonth()    &&
         a.getDate()     === b.getDate()
}

function formatDate(d: Date | null, fmt: string): string {
  if (!d) return ''
  const dd   = String(d.getDate()).padStart(2, '0')
  const mm   = String(d.getMonth() + 1).padStart(2, '0')
  const yyyy = String(d.getFullYear())
  if (fmt === 'dd/mm/yyyy') return `${dd}/${mm}/${yyyy}`
  if (fmt === 'mm/dd/yyyy') return `${mm}/${dd}/${yyyy}`
  return `${yyyy}-${mm}-${dd}`
}

function isDisabled(d: Date): boolean {
  if (props.minDate && d < props.minDate) return true
  if (props.maxDate && d > props.maxDate) return true
  return false
}

function isInRange(d: Date): boolean {
  if (!props.range) return false
  const val = props.modelValue as DateRange
  const start = val?.start, end = val?.end || hoverDate.value
  if (!start || !end) return false
  const lo = start < end ? start : end
  const hi = start < end ? end : start
  return d >= lo && d <= hi
}

// ─── Calendar grid ────────────────────────────────────────────────────────────

const calendarDays = computed(() => {
  const year  = viewDate.value.getFullYear()
  const month = viewDate.value.getMonth()
  const first = new Date(year, month, 1).getDay() // 0 = Sunday
  const total = new Date(year, month + 1, 0).getDate()

  const days: (Date | null)[] = []
  for (let i = 0; i < first; i++) days.push(null)    // leading blanks
  for (let d = 1; d <= total; d++) days.push(new Date(year, month, d))
  // Pad trailing to always have full rows
  while (days.length % 7 !== 0) days.push(null)
  return days
})

// ─── Selected value accessors ─────────────────────────────────────────────────

const selectedSingle = computed<Date | null>(() =>
  !props.range ? (props.modelValue as Date | null) : null
)

const selectedRange = computed<DateRange>(() =>
  props.range ? ((props.modelValue as DateRange) || { start: null, end: null }) : { start: null, end: null }
)

// ─── Display text ─────────────────────────────────────────────────────────────

const triggerText = computed(() => {
  if (props.range) {
    const { start, end } = selectedRange.value
    if (!start && !end) return ''
    return [formatDate(start, props.displayFormat), formatDate(end, props.displayFormat)]
      .filter(Boolean).join(' → ')
  }
  return formatDate(selectedSingle.value, props.displayFormat)
})

// ─── Navigation ───────────────────────────────────────────────────────────────

function prevMonth() {
  viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() - 1, 1)
}
function nextMonth() {
  viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() + 1, 1)
}
function prevYear() {
  viewDate.value = new Date(viewDate.value.getFullYear() - 1, viewDate.value.getMonth(), 1)
}
function nextYear() {
  viewDate.value = new Date(viewDate.value.getFullYear() + 1, viewDate.value.getMonth(), 1)
}

// ─── Selection ────────────────────────────────────────────────────────────────

function selectDay(d: Date | null) {
  if (!d || isDisabled(d)) return

  if (!props.range) {
    emit('update:modelValue', d)
    emit('change', d)
    isOpen.value = false
    return
  }

  // Range mode: first click sets start, second sets end
  const current = selectedRange.value
  if (rangeSelectStep.value === 'start' || !current.start) {
    emit('update:modelValue', { start: d, end: null })
    rangeSelectStep.value = 'end'
  } else {
    const start = current.start!
    const result = d >= start
      ? { start, end: d }
      : { start: d, end: start }
    emit('update:modelValue', result)
    emit('change', result)
    rangeSelectStep.value = 'start'
    isOpen.value = false
  }
}

function clearDate() {
  const next = props.range ? { start: null, end: null } : null
  emit('update:modelValue', next)
  emit('change', next)
  rangeSelectStep.value = 'start'
}

function open() {
  if (props.disabled) return
  isOpen.value = true
  // Navigate calendar to selected date's month
  const d = props.range ? selectedRange.value.start : selectedSingle.value
  if (d) viewDate.value = new Date(d.getFullYear(), d.getMonth(), 1)
}

// ─── Click outside ────────────────────────────────────────────────────────────
import { onMounted, onUnmounted } from 'vue'
const rootRef = ref<HTMLElement | null>(null)

function onDocClick(e: MouseEvent) {
  if (rootRef.value && !rootRef.value.contains(e.target as Node)) {
    isOpen.value = false
    rangeSelectStep.value = 'start'
  }
}
onMounted(() => document.addEventListener('mousedown', onDocClick))
onUnmounted(() => document.removeEventListener('mousedown', onDocClick))
</script>

<template>
  <div
    ref="rootRef"
    class="ch-datepicker"
    :class="[`ch-datepicker--${size}`, { 'ch-datepicker--disabled': disabled, 'ch-datepicker--error': !!error }]"
  >
    <!-- Trigger input -->
    <div class="ch-datepicker__trigger" @click="open">
      <svg class="ch-datepicker__icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <rect x="2" y="3" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/>
        <path d="M5 1v3M11 1v3M2 7h12" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
      </svg>

      <span
        class="ch-datepicker__display"
        :class="{ 'ch-datepicker__display--placeholder': !triggerText }"
      >
        {{ triggerText || placeholder || (range ? `${placeholderStart || 'Start date'} → ${placeholderEnd || 'End date'}` : 'Select date') }}
      </span>

      <button
        v-if="triggerText"
        type="button"
        class="ch-datepicker__clear"
        aria-label="Clear date"
        @click.stop="clearDate"
      >
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path d="M10.5 3.5l-7 7M3.5 3.5l7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <!-- Calendar popup -->
    <Transition name="ch-datepicker-drop">
      <div v-if="isOpen" class="ch-datepicker__popup">

        <!-- Navigation header -->
        <div class="ch-datepicker__nav">
          <button type="button" class="ch-datepicker__nav-btn" @click="prevYear"  title="Previous year">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
              <path d="M8.5 3L5 7l3.5 4M5.5 3L2 7l3.5 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <button type="button" class="ch-datepicker__nav-btn" @click="prevMonth" title="Previous month">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
              <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>

          <span class="ch-datepicker__month-label">
            {{ MONTHS[viewDate.getMonth()] }} {{ viewDate.getFullYear() }}
          </span>

          <button type="button" class="ch-datepicker__nav-btn" @click="nextMonth" title="Next month">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
              <path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <button type="button" class="ch-datepicker__nav-btn" @click="nextYear"  title="Next year">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
              <path d="M5.5 3L9 7l-3.5 4M8.5 3L12 7l-3.5 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <!-- Day-of-week headers -->
        <div class="ch-datepicker__grid">
          <div v-for="day in DAYS" :key="day" class="ch-datepicker__day-header">{{ day }}</div>

          <!-- Calendar day cells -->
          <div
            v-for="(day, i) in calendarDays"
            :key="i"
            class="ch-datepicker__day"
            :class="{
              'ch-datepicker__day--empty':    !day,
              'ch-datepicker__day--today':    day && sameDay(day, new Date()),
              'ch-datepicker__day--selected': day && !range && sameDay(day, selectedSingle),
              'ch-datepicker__day--range-start': day && range && sameDay(day, selectedRange.start),
              'ch-datepicker__day--range-end':   day && range && sameDay(day, selectedRange.end),
              'ch-datepicker__day--in-range':    day && isInRange(day),
              'ch-datepicker__day--disabled':    day && isDisabled(day),
            }"
            @click="selectDay(day)"
            @mouseenter="hoverDate = day"
            @mouseleave="hoverDate = null"
          >
            <span v-if="day" class="ch-datepicker__day-num">{{ day.getDate() }}</span>
          </div>
        </div>

        <!-- Range hint -->
        <div v-if="range" class="ch-datepicker__range-hint">
          {{ rangeSelectStep === 'start' ? 'Select start date' : 'Select end date' }}
        </div>

      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-datepicker { position: relative; width: 100%; }

/* ─── Trigger ─────────────────────────────────────────────────────────────── */
.ch-datepicker__trigger {
  display:       flex;
  align-items:   center;
  gap:           var(--ch-space-2);
  background:    var(--ch-color-surface);
  border:        1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-lg);
  cursor:        pointer;
  transition:    border-color var(--ch-duration-fast) var(--ch-ease-out),
                 box-shadow   var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-datepicker--sm .ch-datepicker__trigger { padding: var(--ch-space-1_5) var(--ch-space-3);   min-height: 32px; font-size: var(--ch-text-xs); }
.ch-datepicker--md .ch-datepicker__trigger { padding: var(--ch-space-2)   var(--ch-space-3_5); min-height: 38px; font-size: var(--ch-text-sm); }
.ch-datepicker--lg .ch-datepicker__trigger { padding: var(--ch-space-2_5) var(--ch-space-4);   min-height: 44px; font-size: var(--ch-text-base); }

.ch-datepicker__trigger:focus-within,
.ch-datepicker--open .ch-datepicker__trigger {
  border-color: var(--ch-color-border-focus);
  box-shadow:   0 0 0 3px var(--ch-color-primary-muted);
  outline:      none;
}
.ch-datepicker--error .ch-datepicker__trigger { border-color: var(--ch-color-danger); }
.ch-datepicker--disabled .ch-datepicker__trigger { opacity: 0.5; cursor: not-allowed; }

.ch-datepicker__icon    { color: var(--ch-color-text-subtle); flex-shrink: 0; }
.ch-datepicker__display { flex: 1; color: var(--ch-color-text); }
.ch-datepicker__display--placeholder { color: var(--ch-color-text-subtle); }

.ch-datepicker__clear {
  background: none; border: none; padding: 0; cursor: pointer;
  color: var(--ch-color-text-subtle); display: flex; align-items: center;
  transition: color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-datepicker__clear:hover { color: var(--ch-color-text); }

/* ─── Popup ───────────────────────────────────────────────────────────────── */
.ch-datepicker__popup {
  position:      absolute;
  top:           calc(100% + var(--ch-space-1));
  left:          0;
  background:    var(--ch-color-surface);
  border:        1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-xl);
  box-shadow:    var(--ch-shadow-lg);
  z-index:       var(--ch-z-dropdown);
  padding:       var(--ch-space-4);
  width:         280px;
  user-select:   none;
}

/* ─── Navigation ──────────────────────────────────────────────────────────── */
.ch-datepicker__nav {
  display:         flex;
  align-items:     center;
  justify-content: space-between;
  margin-bottom:   var(--ch-space-3);
}

.ch-datepicker__month-label {
  font-size:   var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color:       var(--ch-color-text);
}

.ch-datepicker__nav-btn {
  background: none; border: none; cursor: pointer;
  color: var(--ch-color-text-subtle); display: flex; align-items: center;
  padding: var(--ch-space-1); border-radius: var(--ch-radius-md);
  transition: color var(--ch-duration-fast) var(--ch-ease-out),
              background-color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-datepicker__nav-btn:hover {
  color: var(--ch-color-text);
  background-color: var(--ch-color-bg-muted);
}

/* ─── Calendar grid (7 columns) ───────────────────────────────────────────── */
.ch-datepicker__grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 2px;
}

.ch-datepicker__day-header {
  text-align:  center;
  font-size:   var(--ch-text-xs);
  font-weight: var(--ch-font-semibold);
  color:       var(--ch-color-text-subtle);
  padding:     var(--ch-space-1) 0;
  letter-spacing: var(--ch-tracking-wide);
}

.ch-datepicker__day {
  display:         flex;
  align-items:     center;
  justify-content: center;
  border-radius:   var(--ch-radius-md);
  cursor:          pointer;
  transition:      background-color var(--ch-duration-fast) var(--ch-ease-out);
  aspect-ratio:    1;
}

.ch-datepicker__day--empty { cursor: default; }
.ch-datepicker__day:not(.ch-datepicker__day--empty):not(.ch-datepicker__day--disabled):hover {
  background: var(--ch-color-bg-muted);
}
.ch-datepicker__day--today .ch-datepicker__day-num {
  color:       var(--ch-color-primary);
  font-weight: var(--ch-font-semibold);
}
.ch-datepicker__day--selected,
.ch-datepicker__day--range-start,
.ch-datepicker__day--range-end {
  background: var(--ch-color-primary);
  border-radius: var(--ch-radius-full);
}
.ch-datepicker__day--selected .ch-datepicker__day-num,
.ch-datepicker__day--range-start .ch-datepicker__day-num,
.ch-datepicker__day--range-end .ch-datepicker__day-num {
  color: white;
}
.ch-datepicker__day--in-range { background: var(--ch-color-primary-subtle); border-radius: 0; }
.ch-datepicker__day--range-start { border-radius: var(--ch-radius-full) 0 0 var(--ch-radius-full); }
.ch-datepicker__day--range-end   { border-radius: 0 var(--ch-radius-full) var(--ch-radius-full) 0; }
.ch-datepicker__day--disabled { opacity: 0.3; cursor: not-allowed; }

.ch-datepicker__day-num { font-size: var(--ch-text-xs); color: var(--ch-color-text); line-height: 1; }

/* ─── Range hint ──────────────────────────────────────────────────────────── */
.ch-datepicker__range-hint {
  margin-top:  var(--ch-space-3);
  text-align:  center;
  font-size:   var(--ch-text-xs);
  color:       var(--ch-color-text-subtle);
  padding-top: var(--ch-space-2);
  border-top:  1px solid var(--ch-color-border);
}

/* ─── Popup transition ────────────────────────────────────────────────────── */
.ch-datepicker-drop-enter-active { transition: opacity var(--ch-duration-fast) var(--ch-ease-out), transform var(--ch-duration-fast) var(--ch-ease-spring); }
.ch-datepicker-drop-leave-active { transition: opacity var(--ch-duration-fast) var(--ch-ease-in); }
.ch-datepicker-drop-enter-from, .ch-datepicker-drop-leave-to { opacity: 0; transform: translateY(-4px) scale(0.98); }
</style>
