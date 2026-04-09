<!-- Module-level counter for unique IDs across all instances. Must be in a
     plain <script> block — <script setup> runs per instance and would reset. -->
<script lang="ts">
let _selectCounter = 0
</script>

<script setup lang="ts">
/**
 * @component ChSelect
 * @path /frontend/src/design-system/components/forms/ChSelect.vue
 * @description A fully custom dropdown select with search, single/multi-select,
 * option groups, and keyboard navigation. Does not use the native <select>
 * element so it can be fully styled and support complex option rendering.
 *
 * ─── v-model ─────────────────────────────────────────────────────────────────
 * Single:  v-model binds to a single option value (or null)
 * Multi:   v-model binds to an array of values
 *
 * ─── Option shape ────────────────────────────────────────────────────────────
 * { value: string | number, label: string, hint?: string, disabled?: boolean }
 * Groups: { group: string, options: SelectOption[] }
 *
 * @example Single select
 * <ChSelect v-model="form.group" :options="groupOptions" placeholder="Select group" />
 *
 * @example Multi-select with search
 * <ChSelect v-model="form.roles" :options="roleOptions" :multiple="true"
 *           searchable placeholder="Assign roles..." />
 *
 * @example With groups
 * <ChSelect v-model="form.category" :options="[
 *   { group: 'Income',  options: [{ value: 'tithe',    label: 'Tithe' }] },
 *   { group: 'Expense', options: [{ value: 'salaries', label: 'Salaries' }] },
 * ]" />
 */

import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface SelectOption {
  value:     string | number
  label:     string
  hint?:     string
  disabled?: boolean
}

export interface SelectGroup {
  group:   string
  options: SelectOption[]
}

type SelectValue   = string | number | null
type MultiValue    = (string | number)[]
type OptionOrGroup = SelectOption | SelectGroup

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  modelValue:    SelectValue | MultiValue
  options:       OptionOrGroup[]
  placeholder?:  string
  multiple?:     boolean
  searchable?:   boolean
  disabled?:     boolean
  /** Error state — pass `true` for visual-only, or a string to show a message */
  error?:        string | boolean
  size?:         'sm' | 'md' | 'lg'
  id?:           string
  /** name attribute — written to a hidden input for native form participation */
  name?:         string
  /** Max height of the dropdown list in px. Default: 256 */
  maxHeight?:    number
  /** Message shown when search returns no results */
  emptyMessage?: string
  /** Max tags shown in multi-select trigger before "+N more". Default: 3 */
  maxTags?:      number
}

const props = withDefaults(defineProps<Props>(), {
  size:         'md',
  disabled:     false,
  multiple:     false,
  searchable:   false,
  maxHeight:    256,
  emptyMessage: 'No options found.',
  maxTags:      3,
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:modelValue': [value: SelectValue | MultiValue]
  change: [value: SelectValue | MultiValue]
  open:  []
  close: []
}>()

// ─── Unique IDs (per instance, globally unique) ───────────────────────────────

const _instanceId = ++_selectCounter
const listboxId = `ch-select-listbox-${_instanceId}`

/** Stable DOM id for a given option value — used for aria-activedescendant */
function optionId(value: string | number): string {
  return `ch-select-opt-${_instanceId}-${String(value).replace(/\s+/g, '-')}`
}

// ─── State ────────────────────────────────────────────────────────────────────

const isOpen       = ref(false)
const searchQuery  = ref('')
const highlightIdx = ref(-1)
const dropDirection = ref<'down' | 'up'>('down')

const rootRef = ref<HTMLElement | null>(null)
const searchRef = ref<HTMLInputElement | null>(null)
const listRef = ref<HTMLElement | null>(null)

// ─── Helpers ──────────────────────────────────────────────────────────────────

function isGroup(item: OptionOrGroup): item is SelectGroup {
  return 'group' in item
}

/** Flat list of ALL options (unwrapped from groups) — for label lookup */
const allOptions = computed<SelectOption[]>(() => {
  const flat: SelectOption[] = []
  for (const item of props.options) {
    if (isGroup(item)) flat.push(...item.options)
    else flat.push(item)
  }
  return flat
})

/** Options filtered by the current search query */
const filteredOptions = computed<OptionOrGroup[]>(() => {
  if (!searchQuery.value.trim()) return props.options
  const q = searchQuery.value.toLowerCase()
  return props.options.reduce<OptionOrGroup[]>((acc, item) => {
    if (isGroup(item)) {
      const matched = item.options.filter(o => o.label.toLowerCase().includes(q))
      if (matched.length) acc.push({ group: item.group, options: matched })
    } else if (item.label.toLowerCase().includes(q)) {
      acc.push(item)
    }
    return acc
  }, [])
})

/**
 * Flat list of SELECTABLE (non-disabled) options from filteredOptions.
 * Used for keyboard navigation — Tab/Arrow keys skip over disabled items.
 */
const flatFiltered = computed<SelectOption[]>(() => {
  const flat: SelectOption[] = []
  for (const item of filteredOptions.value) {
    if (isGroup(item)) flat.push(...item.options)
    else flat.push(item)
  }
  return flat.filter(o => !o.disabled)
})

/**
 * Maps each selectable option's value → its index in flatFiltered.
 * Replaces the O(n²) flatFiltered.indexOf(opt) calls in the template.
 * The template reads this map once per option instead of scanning the array.
 */
const flatFilteredIndexMap = computed<Map<string | number, number>>(() => {
  const map = new Map<string | number, number>()
  flatFiltered.value.forEach((opt, i) => map.set(opt.value, i))
  return map
})

/** The currently highlighted option, or null */
const highlightedOption = computed<SelectOption | null>(() =>
  highlightIdx.value >= 0 ? flatFiltered.value[highlightIdx.value] ?? null : null,
)

// ─── Display values ───────────────────────────────────────────────────────────

function getLabelFor(value: string | number): string {
  return allOptions.value.find(o => o.value === value)?.label ?? String(value)
}

const selectedValues = computed<(string | number)[]>(() => {
  if (props.multiple) return props.modelValue as MultiValue
  const v = props.modelValue as SelectValue
  return v !== null && v !== undefined && v !== '' ? [v] : []
})

const hasValue = computed(() => selectedValues.value.length > 0)

/** Values shown as tags (capped to maxTags) */
const visibleTagValues = computed(() =>
  selectedValues.value.slice(0, props.maxTags),
)

const overflowCount = computed(() =>
  Math.max(0, selectedValues.value.length - props.maxTags),
)

const triggerText = computed(() => {
  if (selectedValues.value.length === 0) return props.placeholder ?? 'Select...'
  if (!props.multiple) return getLabelFor(selectedValues.value[0]!)
  return '' // multi mode uses tags
})

/** Serialised value for the hidden input (form participation) */
const hiddenInputValue = computed(() => {
  if (props.multiple) return (props.modelValue as MultiValue).join(',')
  return String(props.modelValue ?? '')
})

/** Rendered error string — undefined when error is boolean or absent */
const errorMessage = computed(() =>
  typeof props.error === 'string' && props.error.length > 0 ? props.error : null,
)

const hasError = computed(() => !!props.error)

// ─── Viewport overflow detection ──────────────────────────────────────────────

/**
 * Measures available space below the trigger and flips the dropdown upward
 * if there isn't enough room. Called at open-time so the measurement uses
 * the actual current scroll position.
 */
function computeDropDirection() {
  if (!rootRef.value) return
  const rect = rootRef.value.getBoundingClientRect()
  const spaceBelow = window.innerHeight - rect.bottom
  dropDirection.value = spaceBelow < props.maxHeight + 8 && rect.top > props.maxHeight
    ? 'up'
    : 'down'
}

// ─── Open / Close ─────────────────────────────────────────────────────────────

function open() {
  if (props.disabled) return
  computeDropDirection()
  isOpen.value = true
  searchQuery.value = ''
  highlightIdx.value = -1
  emit('open')
  nextTick(() => searchRef.value?.focus())
}

function close() {
  isOpen.value = false
  searchQuery.value = ''
  highlightIdx.value = -1
  emit('close')
}

function toggle() {
  isOpen.value ? close() : open()
}

// ─── Selection ────────────────────────────────────────────────────────────────

function isSelected(value: string | number): boolean {
  if (props.multiple) return (props.modelValue as MultiValue).includes(value)
  return props.modelValue === value
}

function select(option: SelectOption) {
  if (option.disabled) return
  let next: SelectValue | MultiValue

  if (props.multiple) {
    const current = props.modelValue as MultiValue
    next = isSelected(option.value)
      ? current.filter(v => v !== option.value)
      : [...current, option.value]
  } else {
    next = option.value
    close()
  }

  emit('update:modelValue', next)
  emit('change', next)
}

function clearAll() {
  const next: SelectValue | MultiValue = props.multiple ? [] : null
  emit('update:modelValue', next)
  emit('change', next)
}

function removeTag(value: string | number) {
  const next = (props.modelValue as MultiValue).filter(v => v !== value)
  emit('update:modelValue', next)
  emit('change', next)
}

// ─── Keyboard navigation ──────────────────────────────────────────────────────

function onKeydown(e: KeyboardEvent) {
  if (!isOpen.value) {
    if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
      e.preventDefault()
      open()
    }
    return
  }

  switch (e.key) {
    case 'Escape':
      e.preventDefault()
      close()
      break

    case 'ArrowDown':
      e.preventDefault()
      highlightIdx.value = Math.min(highlightIdx.value + 1, flatFiltered.value.length - 1)
      scrollHighlightIntoView()
      break

    case 'ArrowUp':
      e.preventDefault()
      highlightIdx.value = Math.max(highlightIdx.value - 1, 0)
      scrollHighlightIntoView()
      break

    case 'Enter':
      e.preventDefault()
      if (highlightedOption.value) select(highlightedOption.value)
      break

    case 'Tab':
      close()
      break
  }
}

function scrollHighlightIntoView() {
  nextTick(() => {
    if (!highlightedOption.value) return
    const el = listRef.value?.querySelector(
      `#${optionId(highlightedOption.value.value)}`,
    ) as HTMLElement | null
    el?.scrollIntoView({ block: 'nearest' })
  })
}

// ─── Click outside ────────────────────────────────────────────────────────────

function onDocClick(e: MouseEvent) {
  if (rootRef.value && !rootRef.value.contains(e.target as Node)) close()
}

onMounted(() => document.addEventListener('mousedown', onDocClick))
onUnmounted(() => document.removeEventListener('mousedown', onDocClick))
</script>

<template>
  <div
    ref="rootRef"
    class="ch-select"
    :class="[
      `ch-select--${size}`,
  { 'ch-select--open': isOpen },
  { 'ch-select--disabled': disabled },
  { 'ch-select--error': hasError },
  { 'ch-select--has-value': hasValue },
  { 'ch-select--drop-up': dropDirection === 'up' },
    ]"
    @keydown="onKeydown"
  >
    <!--
      Hidden input for native form participation.
      The name prop is accepted but there is no native <select>, so we write
      the value here so forms and FormData can read it.
    -->
    <input v-if="name" type="hidden" :name="name" :value="hiddenInputValue" />

    <!-- ── Trigger ── -->
    <div
      class="ch-select__trigger"
      role="combobox"
      :aria-expanded="isOpen"
aria-haspopup="listbox"
      :aria-controls="listboxId"
      :aria-activedescendant="highlightedOption ? optionId(highlightedOption.value) : undefined"
      :aria-disabled="disabled ? 'true' : undefined" :aria-invalid="hasError ? 'true' : undefined"
      :tabindex="disabled ? -1 : 0"
      :id="id"
      @click="toggle"
    >
      <!-- Multi-select tags — iterated by value, not index -->
      <div v-if="multiple && hasValue" class="ch-select__tags">
        <span
v-for="value in visibleTagValues" :key="value"
          class="ch-select__tag"
        >
          {{ getLabelFor(value) }}
          <button
            type="button"
            class="ch-select__tag-remove"
:aria-label="`Remove ${getLabelFor(value)}`"
            @click.stop="removeTag(value)"
          >
            <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
              <path d="M7.5 2.5l-5 5M2.5 2.5l5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
            </svg>
          </button>
        </span>
        <span v-if="overflowCount > 0" class="ch-select__tag ch-select__tag--overflow">
          +{{ overflowCount }}
        </span>
      </div>

      <!-- Single / placeholder text -->
      <span
        v-else
        class="ch-select__display"
        :class="{ 'ch-select__display--placeholder': !hasValue }"
      >
        {{ triggerText }}
      </span>

      <!-- Right controls: clear + chevron -->
      <div class="ch-select__actions">
        <button
          v-if="hasValue && !disabled"
          type="button"
          class="ch-select__clear"
          aria-label="Clear selection"
          @click.stop="clearAll"
        >
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
            <path d="M10.5 3.5l-7 7M3.5 3.5l7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          </svg>
        </button>

        <svg
          class="ch-select__chevron"
          :class="{ 'ch-select__chevron--open': isOpen }"
          width="16" height="16" viewBox="0 0 16 16" fill="none"
          aria-hidden="true"
        >
          <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </div>
    </div>

    <!-- ── Dropdown ── -->
    <Transition name="ch-select-drop">
      <div
        v-if="isOpen"
:id="listboxId"
        class="ch-select__dropdown"
:class="`ch-select__dropdown--${dropDirection}`"
        :style="{ maxHeight: `${maxHeight}px` }"
        role="listbox"
:aria-multiselectable="multiple || undefined"
        :aria-label="props.placeholder ?? 'Options'"
      >
        <!-- Search -->
        <div v-if="searchable" class="ch-select__search-wrap">
          <input
            ref="searchRef"
            v-model="searchQuery"
            type="text"
            class="ch-select__search"
            placeholder="Search..."
            autocomplete="off"
aria-label="Search options"
            @click.stop
          />
        </div>

        <!-- Options list -->
        <div ref="listRef" class="ch-select__list">
          <div v-if="filteredOptions.length === 0" class="ch-select__empty">
            {{ emptyMessage }}
          </div>

          <template v-for="item in filteredOptions" :key="isGroup(item) ? item.group : (item as SelectOption).value">
            <!-- Group label -->
            <div v-if="isGroup(item)" class="ch-select__group-label" role="presentation">
              {{ item.group }}
            </div>

            <!--
              Options — iterated from the group's options array or from the
              flat item. Index is looked up from flatFilteredIndexMap (O(1))
              instead of calling flatFiltered.indexOf(opt) (O(n)) per option.
            -->
            <div
v-for="opt in (isGroup(item) ? item.options : [(item as SelectOption)])" :key="opt.value"
              :id="optionId(opt.value)"
class="ch-select__option" :class="{
  'ch-select__option--selected': isSelected(opt.value),
  'ch-select__option--highlighted': flatFilteredIndexMap.get(opt.value) === highlightIdx,
  'ch-select__option--disabled': opt.disabled,
  'ch-select__option--grouped': isGroup(item),
}" role="option" :aria-selected="isSelected(opt.value)"
              :aria-disabled="opt.disabled ? 'true' : undefined" :data-idx="flatFilteredIndexMap.get(opt.value)"
              @click.stop="select(opt)"
              @mouseenter="!opt.disabled && (highlightIdx = flatFilteredIndexMap.get(opt.value) ?? -1)">
              <!-- Checkmark for selected state -->
              <svg v-if="isSelected(opt.value)" class="ch-select__option-check" width="14" height="14"
                viewBox="0 0 14 14" fill="none"
aria-hidden="true"
>
                <path d="M2.5 7l3.5 3.5 5.5-6" stroke="currentColor" stroke-width="1.6"
stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
              <span v-else class="ch-select__option-check-placeholder" aria-hidden="true" />

              <div class="ch-select__option-content">
                <span class="ch-select__option-label">{{ opt.label }}</span>
                <span v-if="opt.hint" class="ch-select__option-hint">{{ opt.hint }}</span>
              </div>
            </div>
          </template>
        </div>
      </div>
    </Transition>

    <!-- Error message -->
    <p v-if="errorMessage" class="ch-select__error" role="alert">
      {{ errorMessage }}
    </p>
  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-select {
  position: relative;
    width: 100%;
    user-select: none;
}

/* ─── Trigger ─────────────────────────────────────────────────────────────── */
.ch-select__trigger {
  display: flex;
    align-items: center;
    gap: var(--ch-space-2);
    width: 100%;
    background: var(--ch-color-surface);
    border: 1px solid var(--ch-color-border-strong);
    border-radius: var(--ch-radius-md);
    cursor: pointer;
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow   var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-select--sm .ch-select__trigger {
  padding: var(--ch-space-1_5) var(--ch-space-3);
  min-height: 32px;
  font-size: var(--ch-text-xs);
}

.ch-select--md .ch-select__trigger {
  padding: var(--ch-space-2) var(--ch-space-3_5);
  min-height: 38px;
  font-size: var(--ch-text-sm);
}

.ch-select--lg .ch-select__trigger {
  padding: var(--ch-space-2_5) var(--ch-space-4);
  min-height: 44px;
  font-size: var(--ch-text-base);
}

.ch-select--open .ch-select__trigger,
.ch-select__trigger:focus {
  outline:        2px solid var(--ch-color-primary);
  outline-offset: -1px;
  border-color:   var(--ch-color-border-focus);
}

.ch-select--error .ch-select__trigger { border-color: var(--ch-color-danger); }
.ch-select--error .ch-select__trigger:focus,
.ch-select--error.ch-select--open .ch-select__trigger {
  outline: 2px solid var(--ch-color-danger);
  outline-offset: -1px;
}

.ch-select--disabled .ch-select__trigger {
  opacity: 0.5;
  cursor: not-allowed;
  background: var(--ch-color-bg-subtle);
}

/* ─── Display / placeholder ───────────────────────────────────────────────── */
.ch-select__display {
  flex: 1;
    color: var(--ch-color-text);
    white-space: nowrap;
    overflow: hidden;
  text-overflow: ellipsis;
}
.ch-select__display--placeholder { color: var(--ch-color-text-subtle); }

/* ─── Multi tags ──────────────────────────────────────────────────────────── */
.ch-select__tags {
  display:   flex;
  flex-wrap: wrap;
  gap:       var(--ch-space-1);
  flex:      1;
  min-width: 0;
}

.ch-select__tag {
  display:       inline-flex;
  align-items:   center;
  gap:           var(--ch-space-1);
  padding:       2px var(--ch-space-2);
  background:    var(--ch-color-primary-subtle);
  color:         var(--ch-color-primary);
  border-radius: var(--ch-radius-md);
  font-size:     var(--ch-text-xs);
  font-weight:   var(--ch-font-medium);
  line-height:   1;
  white-space:   nowrap;
}

.ch-select__tag--overflow {
  background: var(--ch-color-bg-muted);
  color:      var(--ch-color-text-muted);
}

.ch-select__tag-remove {
  background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: currentColor;
    display: flex;
    opacity: 0.6;
  transition: opacity var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-select__tag-remove:hover { opacity: 1; }

/* ─── Actions (clear + chevron) ───────────────────────────────────────────── */
.ch-select__actions {
  display:     flex;
  align-items: center;
  gap:         var(--ch-space-1);
  flex-shrink: 0;
  margin-left: auto;
}

.ch-select__clear {
  background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: var(--ch-color-text-subtle);
    display: flex;
    align-items: center;
  transition: color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-select__clear:hover { color: var(--ch-color-text); }

.ch-select__chevron {
  color: var(--ch-color-text-subtle);
    flex-shrink: 0;
    transition: transform var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-select__chevron--open { transform: rotate(180deg); }

/* ─── Dropdown ────────────────────────────────────────────────────────────── */
.ch-select__dropdown {
  position: absolute;
    left: 0;
    right: 0;
    background: var(--ch-color-surface);
    border: 1px solid var(--ch-color-border-strong);
    border-radius: var(--ch-radius-md);
    box-shadow: var(--ch-shadow-xl);
    z-index: var(--ch-z-dropdown);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  
  /* Opens downward (default) */
  .ch-select__dropdown--down {
    top: calc(100% + var(--ch-space-1));
  }
  
  /* Opens upward when near the bottom of the viewport */
  .ch-select__dropdown--up {
    bottom: calc(100% + var(--ch-space-1));
}

/* ─── Search input ────────────────────────────────────────────────────────── */
.ch-select__search-wrap {
  padding:       var(--ch-space-2);
  border-bottom: 1px solid var(--ch-color-border);
  flex-shrink:   0;
}

.ch-select__search {
  width: 100%;
    padding: var(--ch-space-1_5) var(--ch-space-3);
    background: var(--ch-color-bg-subtle);
    border: 1px solid var(--ch-color-border-strong);
    border-radius: var(--ch-radius-md);
    font-family: var(--ch-font-sans);
    font-size: var(--ch-text-sm);
    color: var(--ch-color-text);
    outline: none;
    transition: border-color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-select__search:focus { border-color: var(--ch-color-border-focus); }

/* ─── Options list ────────────────────────────────────────────────────────── */
.ch-select__list {
  overflow-y: auto;
  max-height: inherit;
  padding: var(--ch-space-1);
}

.ch-select__group-label {
  padding:        var(--ch-space-1_5) var(--ch-space-3);
  font-size:      var(--ch-text-xs);
  font-weight:    var(--ch-font-semibold);
  color:          var(--ch-color-text-subtle);
  letter-spacing: var(--ch-tracking-wide);
  text-transform: uppercase;
}

.ch-select__option {
  display:       flex;
  align-items:   center;
  gap:           var(--ch-space-2);
  padding:       var(--ch-space-2) var(--ch-space-3);
  border-radius: var(--ch-radius-md);
  cursor:        pointer;
  transition:    background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-select__option--grouped {
  padding-left: var(--ch-space-4);
}
.ch-select__option--highlighted { background: var(--ch-color-bg-subtle); }
.ch-select__option--selected    { background: var(--ch-color-primary-subtle); }
.ch-select__option--disabled    { opacity: 0.4; cursor: not-allowed; }

.ch-select__option-check {
  color: var(--ch-color-primary);
  flex-shrink: 0;
}
.ch-select__option-check-placeholder { width: 14px; flex-shrink: 0; }

.ch-select__option-content {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.ch-select__option-label {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
}

.ch-select__option-hint {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
}

.ch-select__empty {
  padding:    var(--ch-space-4) var(--ch-space-3);
  text-align: center;
  font-size:  var(--ch-text-sm);
  color:      var(--ch-color-text-subtle);
}

/* ─── Error message ───────────────────────────────────────────────────────── */
.ch-select__error {
  margin: var(--ch-space-1) 0 0;
  font-size: var(--ch-text-xs);
  color: var(--ch-color-danger);
  line-height: var(--ch-leading-normal);
}
/* ─── Dropdown transition ─────────────────────────────────────────────────── */
.ch-select-drop-enter-active {
  transition:
    opacity var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-spring);
}

.ch-select-drop-leave-active {
  transition: opacity var(--ch-duration-fast) var(--ch-ease-in);
}

.ch-select-drop-enter-from,
.ch-select-drop-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}
</style>