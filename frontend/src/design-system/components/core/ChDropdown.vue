<script setup lang="ts" generic="T extends string | number | object">
/**
 * @component ChDropdown
 * @path /frontend/src/design-system/components/core/ChDropdown.vue
 * @description A dropdown menu component for displaying a list of actions or
 * options. Supports icons, dividers, disabled items, and keyboard navigation.
 */

import { computed, ref, useSlots, watch } from 'vue'
import { Search, CircleHelp } from 'lucide-vue-next'
import ChPopover, { type PopoverPlacement } from './ChPopover.vue'
import ChInput from './ChInput.vue'
import ChDropdownItem from './ChDropdownItem.vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/** Visual variant for a dropdown item */
export type DropdownItemVariant = 'default' | 'primary' | 'danger' | 'success' | 'warning'

/** A dropdown item descriptor */
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
 * When true, we render the slot and skip the items array fallback so that
 * both never render at the same time.
 */
const hasCustomContent = computed(() => !!slots.default)

// ─── Local state ──────────────────────────────────────────────────────────────

const isOpen = ref(props.open)
const searchQuery = ref('')

// ─── Computed ─────────────────────────────────────────────────────────────────

const filteredItems = computed(() => {
  if (!props.searchable || !searchQuery.value || searchQuery.value.length < props.searchMinChars) {
    return props.items
  }

  const query = searchQuery.value.toLowerCase()
  return props.items.filter(item => {
    const searchable = item[props.searchKey] ?? item.label
    return String(searchable).toLowerCase().includes(query)
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

// ─── Watch ────────────────────────────────────────────────────────────────────

watch(() => props.open, (newVal) => {
  isOpen.value = newVal
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
        <ChInput v-model="searchQuery" :placeholder="searchPlaceholder" size="sm" clearable>
          <template #prefix>
            <Search :size="14" :stroke-width="2" />
          </template>
        </ChInput>
      </div>

      <!-- Items list -->
      <div class="ch-dropdown__items" role="menu">
        <!-- Empty state: only shown when using the items array (not custom slot) -->
        <div v-if="!hasItems" class="ch-dropdown__empty">
          <CircleHelp :size="24" :stroke-width="1.5" />
          <p>No results found</p>
        </div>

        <!--
          Custom slot content takes full control of the item list.
          The items array fallback is skipped so both never render simultaneously.
        -->
        <template v-if="hasCustomContent">
          <slot></slot>
        </template>

        <template v-else>
          <ChDropdownItem v-for="item in filteredItems" :key="String(item.value)" :label="item.label"
            :description="item.description" :variant="item.variant" :disabled="item.disabled"
            @click="handleSelect(item)">
            <!-- Forward the trailing slot down if the consumer needs it -->
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
/* ─── Dropdown container ──────────────────────────────────────────────────── */
.ch-dropdown {
  display: flex;
  flex-direction: column;
}

/* ─── Search input ────────────────────────────────────────────────────────── */
.ch-dropdown__search {
  padding: var(--ch-space-2) var(--ch-space-3);
  border-bottom: 1px solid var(--ch-color-border);
}

/* ─── Items list ──────────────────────────────────────────────────────────── */
.ch-dropdown__items {
  display: flex;
  flex-direction: column;
  max-height: var(--ch-dropdown-max-height, 300px);
  overflow-y: auto;
  padding: var(--ch-space-1) 0;
}

/* ─── Empty state ─────────────────────────────────────────────────────────── */
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