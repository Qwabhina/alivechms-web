<script setup lang="ts">
/**
 * @component ChAccordion
 * @path /frontend/src/design-system/components/data/ChAccordion.vue
 * @description A vertically stacked component that displays collapsible content
 * sections. Supports a single open item (exclusive mode, default) or multiple
 * items open simultaneously.
 *
 * ─── Design decisions ────────────────────────────────────────────────────────
 * - State is managed here and shared with ChAccordionItem via provide/inject,
 *   rather than scoped slot props, so consumers don't need to wire anything up.
 * - Supports both controlled (v-model) and uncontrolled modes.
 *
 * ─── Accessibility ───────────────────────────────────────────────────────────
 * - No role on the root — an accordion is not a named landmark.
 * - `aria-multiselectable` is omitted: it is only valid on grid/listbox/
 *   tablist/tree, not on generic accordion containers.
 * - Individual panels carry `role="region"` and `aria-labelledby` per the
 *   ARIA Authoring Practices accordion pattern.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic usage
 * <ChAccordion>
 *   <ChAccordionItem value="1" title="Getting Started">
 *     Content for getting started...
 *   </ChAccordionItem>
 *   <ChAccordionItem value="2" title="Account Settings">
 *     Content for account settings...
 *   </ChAccordionItem>
 * </ChAccordion>
 *
 * @example With v-model for controlled state
 * <ChAccordion v-model="openItem">
 *   <ChAccordionItem value="faq-1" title="What is CHMS?">
 *     CHMS is a church management system...
 *   </ChAccordionItem>
 *   <ChAccordionItem value="faq-2" title="How do I add members?">
 *     Navigate to Members > Add New...
 *   </ChAccordionItem>
 * </ChAccordion>
 *
 * @example Allow multiple open items
 * <ChAccordion multiple>
 *   <ChAccordionItem value="1" title="Section 1">...</ChAccordionItem>
 *   <ChAccordionItem value="2" title="Section 2">...</ChAccordionItem>
 *   <ChAccordionItem value="3" title="Section 3">...</ChAccordionItem>
 * </ChAccordion>
 */

import { computed, provide, ref, watch } from 'vue'
import { ACCORDION_KEY } from '../../composables/useAccordion'
import type { AccordionValue } from '../../composables/useAccordion'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /**
   * Currently open item value(s) for controlled mode.
   * - Exclusive mode: `string | number` (or '' to close all)
   * - Multiple mode:  `(string | number)[]`
   */
  modelValue?: AccordionValue | AccordionValue[]
  /** Allow multiple items open simultaneously. Default: false */
  multiple?: boolean
  /** Custom CSS class for the accordion root */
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  /**
   * Default to an empty array so the type is always correct in multiple mode
   * without needing a runtime branch. Exclusive mode normalises to '' internally.
   */
  modelValue: () => [],
  multiple: false,
  class: '',
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:modelValue': [value: AccordionValue | AccordionValue[]]
  'item-open': [value: AccordionValue]
  'item-close': [value: AccordionValue]
}>()

// ─── Local state ──────────────────────────────────────────────────────────────

/**
 * Normalise the incoming modelValue so internal logic always works with
 * a consistent type:
 * - Multiple mode → always an array
 * - Exclusive mode → always a single value ('' means nothing open)
 */
function normalise(value: AccordionValue | AccordionValue[]): AccordionValue | AccordionValue[] {
  if (props.multiple) return Array.isArray(value) ? value : value !== '' ? [value] : []
  return Array.isArray(value) ? (value[0] ?? '') : value
}

const openItems = ref<AccordionValue | AccordionValue[]>(normalise(props.modelValue))

// ─── State helpers ────────────────────────────────────────────────────────────

function isOpen(itemValue: AccordionValue): boolean {
  if (props.multiple) {
    return Array.isArray(openItems.value) && openItems.value.includes(itemValue)
  }
  return openItems.value === itemValue
}

function toggleItem(itemValue: AccordionValue): void {
  if (props.multiple) {
    const current = Array.isArray(openItems.value) ? openItems.value : []
    const isCurrentlyOpen = current.includes(itemValue)
    const next = isCurrentlyOpen
      ? current.filter(v => v !== itemValue)
      : [...current, itemValue]

    openItems.value = next
    emit('update:modelValue', next)
    if (isCurrentlyOpen) {
      emit('item-close', itemValue)
    } else {
      emit('item-open', itemValue)
    }
  } else {
    const isCurrentlyOpen = openItems.value === itemValue
    const prev = openItems.value

    openItems.value = isCurrentlyOpen ? '' : itemValue
    emit('update:modelValue', openItems.value)

    if (!isCurrentlyOpen) {
      // Close whatever was previously open before opening the new one
      if (prev !== '') emit('item-close', prev as AccordionValue)
      emit('item-open', itemValue)
    } else {
      emit('item-close', itemValue)
    }
  }
}

// ─── Sync controlled value → local state ─────────────────────────────────────

watch(
  () => props.modelValue,
  (newVal) => { openItems.value = normalise(newVal) },
  /**
   * deep: true is required so that mutations to an array modelValue
   * (e.g. parent pushes a value into the array) are detected.
   */
  { deep: true },
)

// ─── Provide context ──────────────────────────────────────────────────────────

/**
 * ChAccordionItem injects this to call toggle() and check isOpen()
 * without needing scoped slot wiring at the consumer level.
 */
provide(ACCORDION_KEY, {
  isOpen,
  toggle: toggleItem,
  multiple: computed(() => props.multiple),
})
</script>

<template>
  <!--
    No role attribute: an accordion is not a named landmark region.
    aria-multiselectable is not valid on generic containers; it belongs
    on grid/listbox/tablist/tree only.
  -->
  <div :class="['ch-accordion', props.class]">
    <slot></slot>
  </div>
</template>

<style scoped>
/* ─── Accordion root ──────────────────────────────────────────────────────── */
.ch-accordion {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  overflow: hidden;
  background: var(--ch-color-surface);
}
</style>