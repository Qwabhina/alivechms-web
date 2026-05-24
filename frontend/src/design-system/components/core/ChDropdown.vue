<script setup lang="ts" generic="T extends string | number | object">
/**
 * @component ChDropdown
 * @path /frontend/src/design-system/components/core/ChDropdown.vue
 * @description A dropdown menu component for displaying a list of actions or
 * options. Supports icons, dividers, disabled items, and keyboard navigation.
 *
 * ─── Keyboard interaction ─────────────────────────────────────────────────────
 * Follows the ARIA menu button pattern:
 *   - ArrowDown / ArrowUp → move focus between enabled items (wraps around)
 *   - Home / End          → jump to first / last enabled item
 *   - Escape              → close the menu
 *   - Enter / Space       → activate the focused item (handled in ChDropdownItem)
 *
 * When `searchable` is true, the search input is focused on open.
 * When not searchable, the first enabled item is focused on open.
 */

import { computed, nextTick, ref, useSlots, watch } from 'vue'
import { Search, CircleHelp } from '@lucide/vue'
import ChPopover, { type PopoverPlacement } from './ChPopover.vue'
import ChInput from './ChInput.vue'
import ChDropdownItem from './ChDropdownItem.vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export type DropdownItemVariant = 'default' | 'primary' | 'danger' | 'success' | 'warning'

export interface DropdownItem<T extends string | number | object = string> {
  /** Unique identifier — used as :key and emitted value */
  value: T
  /** Display label */
  label: string
  /** Visual variant for emphasis */
  variant?: DropdownItemVariant
  /** Whether the item is disabled */
  disabled?: boolean
  /** Optional description shown below the label */
  description?: string
}

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Controls open state — use v-model:open */
  open?: boolean
  /** Array of items to render */
  items?: DropdownItem<T>[]
  /** Placement relative to trigger. Default: 'bottom' */
  placement?: PopoverPlacement
  /** When true, shows a search input at the top */
  searchable?: boolean
  /** Placeholder for the search input */
  searchPlaceholder?: string
  /**
   * Which DropdownItem field to filter on.
   * Restricted to string fields to guarantee sensible results.
   * Default: 'label'
   */
  searchKey?: keyof Pick<DropdownItem<T>, 'label' | 'description'>
  /** Minimum characters before search filters. Default: 0 */
  searchMinChars?: number
  /** Custom CSS class forwarded to the ChPopover wrapper */
  popoverClass?: string
  /** Min width of dropdown. Default: '180px' */
  minWidth?: string
  /** Max width of dropdown. Default: '300px' */
  maxWidth?: string
  /** Max height of the items list before scrolling. Default: '300px' */
  maxHeight?: string
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
  items: () => [],
  placement: 'bottom',
  searchable: false,
  searchPlaceholder: 'Search...',
  searchKey: 'label',
  searchMinChars: 0,
  popoverClass: '',
  minWidth: '180px',
  maxWidth: '300px',
  maxHeight: '300px',
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:open': [value: boolean]
  select: [item: DropdownItem<T>]
}>()

// ─── Slots ────────────────────────────────────────────────────────────────────

const slots = useSlots()

/**
 * True when the consumer has provided custom slot content.
 * When true, we render the slot and skip the items array so both
 * never render simultaneously.
 */
const hasCustomContent = computed(() => !!slots.default)

// ─── Refs ─────────────────────────────────────────────────────────────────────

const isOpen = ref(props.open)
const searchQuery = ref('')
const itemsRef = ref<HTMLElement | null>(null)
const searchRef = ref<InstanceType<typeof ChInput> | null>(null)

// ─── Computed ─────────────────────────────────────────────────────────────────

const filteredItems = computed(() => {
  if (!props.searchable || !searchQuery.value || searchQuery.value.length < props.searchMinChars) {
    return props.items
  }
  const query = searchQuery.value.toLowerCase()
  return props.items.filter(item => {
    const field = item[props.searchKey] ?? item.label
    return String(field).toLowerCase().includes(query)
  })
})

const hasItems = computed(() =>
  hasCustomContent.value || filteredItems.value.length > 0
)

// ─── Methods ──────────────────────────────────────────────────────────────────

function handleSelect(item: DropdownItem<T>) {
  if (item.disabled) return
  emit('select', item)
  isOpen.value = false
  emit('update:open', false)
}

function handleOpenChange(newOpen: boolean) {
  isOpen.value = newOpen
  emit('update:open', newOpen)
  if (!newOpen) searchQuery.value = ''
}

/**
 * Returns all enabled menuitem elements inside the items container.
 * Used by keyboard navigation to build the focusable item list.
 * Filters out items with aria-disabled="true" so disabled items are skipped.
 */
function getFocusableItems(): HTMLElement[] {
  if (!itemsRef.value) return []
  return Array.from(
    itemsRef.value.querySelectorAll<HTMLElement>(
      '[role="menuitem"]:not([aria-disabled="true"])'
    )
  )
}

/**
 * Keyboard navigation handler for the items container.
 * Implements the ARIA menu keyboard interaction pattern:
 *   ArrowDown → next item (wraps to first)
 *   ArrowUp   → previous item (wraps to last)
 *   Home      → first item
 *   End       → last item
 *   Escape    → close
 */
function handleMenuKeydown(e: KeyboardEvent) {
  if (!['ArrowDown', 'ArrowUp', 'Home', 'End', 'Escape'].includes(e.key)) return
  e.preventDefault()

  if (e.key === 'Escape') {
    handleOpenChange(false)
    return
  }

  const items = getFocusableItems()
  if (!items.length) return

  const currentIndex = items.indexOf(document.activeElement as HTMLElement)

  if (e.key === 'Home') {
    items[0]?.focus()
    return
  }
  if (e.key === 'End') {
    items[items.length - 1]?.focus()
    return
  }

  if (e.key === 'ArrowDown') {
    // Wrap from last → first
    items[currentIndex < items.length - 1 ? currentIndex + 1 : 0]?.focus()
  }
  if (e.key === 'ArrowUp') {
    // Wrap from first → last
    items[currentIndex > 0 ? currentIndex - 1 : items.length - 1]?.focus()
  }
}

// ─── Watch ────────────────────────────────────────────────────────────────────

watch(() => props.open, (newVal) => {
  isOpen.value = newVal
})

/**
 * On open: focus the search input (if searchable) or the first enabled item.
 * `nextTick` is required because the popover DOM isn't present yet
 * in the same tick that `isOpen` becomes true.
 */
watch(isOpen, (newVal) => {
  if (!newVal) return
  nextTick(() => {
    if (props.searchable) {
      // ChInput exposes a native input ref — fall back to query if not available
      const input = itemsRef.value?.closest('.ch-dropdown')
        ?.querySelector<HTMLInputElement>('input')
      input?.focus()
    } else {
      getFocusableItems()[0]?.focus()
    }
  })
})
</script>

<template>
  <ChPopover :open="isOpen" :placement="placement" trigger="click" :class="popoverClass" :min-width="minWidth"
    :max-width="maxWidth" @update:open="handleOpenChange">
    <template #trigger>
      <slot name="trigger"></slot>
    </template>

    <div class="ch-dropdown" :style="{ '--ch-dropdown-max-height': maxHeight }">
      <!-- Search input -->
      <div v-if="searchable" class="ch-dropdown__search">
        <ChInput ref="searchRef" v-model="searchQuery" :placeholder="searchPlaceholder" size="sm" clearable>
          <template #prefix>
            <Search :size="14" :stroke-width="2" />
          </template>
        </ChInput>
      </div>

      <!--
        Items list.
        `@keydown` here handles arrow/home/end/escape navigation.
        Individual Enter/Space activation is handled inside ChDropdownItem.
      -->
      <div ref="itemsRef" class="ch-dropdown__items" role="menu" @keydown="handleMenuKeydown">
        <!-- Empty state — only shown when using the items array, not custom slot -->
        <div v-if="!hasItems" class="ch-dropdown__empty">
          <CircleHelp :size="24" :stroke-width="1.5" />
          <p>No results found</p>
        </div>

        <template v-if="hasCustomContent">
          <slot></slot>
        </template>

        <template v-else>
          <ChDropdownItem v-for="item in filteredItems" :key="String(item.value)" :label="item.label"
            :description="item.description" :variant="item.variant" :disabled="item.disabled"
            @click="handleSelect(item)">
            <template v-if="$slots['item-trailing']" #trailing>
              <slot name="item-trailing" :item="item"></slot>
            </template>
          </ChDropdownItem>
        </template>
      </div>
    </div>
  </ChPopover>
</template>

<style scoped>
.ch-dropdown {
  display: flex;
  flex-direction: column;
}

.ch-dropdown__search {
  padding: var(--ch-space-2) var(--ch-space-3);
  border-bottom: 1px solid var(--ch-color-border);
}

.ch-dropdown__items {
  display: flex;
  flex-direction: column;
  max-height: var(--ch-dropdown-max-height, 300px);
  overflow-y: auto;
  padding: var(--ch-space-1) 0;
}

.ch-dropdown__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-2);
  padding: var(--ch-space-6) var(--ch-space-4);
  color: var(--ch-color-text-subtle);
  text-align: center;
}

.ch-dropdown__empty .lucide {
  opacity: 0.5;
}

.ch-dropdown__empty p {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}
</style>