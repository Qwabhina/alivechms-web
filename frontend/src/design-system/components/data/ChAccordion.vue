<script setup lang="ts">
/**
 * @component ChAccordion
 * @path /frontend/src/design-system/components/data/ChAccordion.vue
 * @description A vertically stacked component that displays collapsible content
 * sections. Only one section can be open at a time (exclusive mode) or multiple
 * sections can be open simultaneously.
 *
 * ─── Design decisions ────────────────────────────────────────────────────────
 * - Supports both controlled (v-model) and uncontrolled modes
 * - Smooth CSS transitions for expand/collapse animations
 * - Keyboard accessible — Enter/Space to toggle, Arrow keys to navigate
 * - Icon rotation indicates open/closed state
 *
 * ─── Accessibility ───────────────────────────────────────────────────────────
 * - Uses `role="region"` for accordion panels
 * - Headers use button elements with `aria-expanded`
 * - `aria-controls` links headers to their panels
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

import { computed, ref, watch } from 'vue'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Currently open item value(s) — use v-model */
  modelValue?: string | number | (string | number)[]
  /** Allow multiple items to be open simultaneously. Default: false */
  multiple?: boolean
  /** Custom CSS class for the accordion root */
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => '',
  multiple: false,
  class: '',
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:modelValue': [value: string | number | (string | number)[]]
  'item-open': [value: string | number]
  'item-close': [value: string | number]
}>()

// ─── Local state ──────────────────────────────────────────────────────────────

const openItems = ref<string | number | (string | number)[]>(props.modelValue)

// ─── Methods ──────────────────────────────────────────────────────────────────

function toggleItem(itemValue: string | number) {
  if (props.multiple) {
    // Multiple mode: toggle item in/out of array
    const current = Array.isArray(openItems.value) ? openItems.value : []
    const index = current.indexOf(itemValue)

    if (index > -1) {
      // Close this item
      const newValue = current.filter(v => v !== itemValue)
      openItems.value = newValue
      emit('update:modelValue', newValue)
      emit('item-close', itemValue)
    } else {
      // Open this item
      const newValue = [...current, itemValue]
      openItems.value = newValue
      emit('update:modelValue', newValue)
      emit('item-open', itemValue)
    }
  } else {
    // Exclusive mode: only one item open at a time
    if (openItems.value === itemValue) {
      // Close if already open
      openItems.value = ''
      emit('update:modelValue', '')
      emit('item-close', itemValue)
    } else {
      // Open this item (closes any other)
      const prevValue = openItems.value
      const prevItemValue = typeof prevValue === 'string' || typeof prevValue === 'number' ? prevValue : null
      openItems.value = itemValue
      emit('update:modelValue', itemValue)
      if (prevItemValue) {
        emit('item-close', prevItemValue)
      }
      emit('item-open', itemValue)
    }
  }
}

function isOpen(itemValue: string | number): boolean {
  if (props.multiple) {
    return Array.isArray(openItems.value) && openItems.value.includes(itemValue)
  }
  return openItems.value === itemValue
}

// ─── Watch ────────────────────────────────────────────────────────────────────

watch(() => props.modelValue, (newVal) => {
  openItems.value = newVal
})
</script>

<template>
  <div :class="['ch-accordion', props.class]" role="region" :aria-multiselectable="props.multiple ? 'true' : 'false'">
    <slot :open="openItems" :toggle="toggleItem" :is-open="isOpen"></slot>
  </div>
</template>

<style scoped>
/* ─── Accordion root ──────────────────────────────────────────────────────── */
.ch-accordion {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-lg);
  overflow: hidden;
  background: var(--ch-color-surface);
}
</style>
