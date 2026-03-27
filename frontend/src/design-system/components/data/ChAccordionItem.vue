<script setup lang="ts">
/**
 * @component ChAccordionItem
 * @path /frontend/src/design-system/components/data/ChAccordionItem.vue
 * @description A single collapsible item within a ChAccordion. Contains a
 * clickable header and an expandable content panel.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example
 * <ChAccordion>
 *   <ChAccordionItem value="1" title="Getting Started">
 *     <p>Content goes here...</p>
 *   </ChAccordionItem>
 * </ChAccordion>
 *
 * @example With custom header content
 * <ChAccordionItem value="1">
 *   <template #header>
 *     <div class="flex items-center gap-2">
 *       <ChIcon name="star" />
 *       <span>Featured Section</span>
 *     </div>
 *   </template>
 *   <p>Content...</p>
 * </ChAccordionItem>
 *
 * @example With custom trailing content
 * <ChAccordionItem value="1" title="Settings">
 *   <template #trailing>
 *     <ChBadge variant="primary">New</ChBadge>
 *   </template>
 *   <p>Content...</p>
 * </ChAccordionItem>
 */

import { computed, ref } from 'vue'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Unique identifier for this item */
  value: string | number
  /** Title shown in the header (unless header slot is provided) */
  title?: string
  /** Optional description shown below the title */
  description?: string
  /** Whether the item is disabled (cannot be toggled) */
  disabled?: boolean
  /** Custom CSS class for the item */
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  description: '',
  disabled: false,
  class: '',
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  toggle: [value: string | number]
}>()

// ─── Injected state from parent ──────────────────────────────────────────────

// These will be provided by the parent ChAccordion via slot props
const isOpen = defineModel<boolean>('open', { default: false })

// ─── Local state ──────────────────────────────────────────────────────────────

const contentRef = ref<HTMLElement | null>(null)
const isAnimating = ref(false)

// ─── Computed ─────────────────────────────────────────────────────────────────

const classes = computed(() => [
  'ch-accordion-item',
  {
    'ch-accordion-item--open': isOpen.value,
    'ch-accordion-item--disabled': props.disabled,
  },
  props.class,
])

const panelId = computed(() => `accordion-panel-${props.value}`)
const headerId = computed(() => `accordion-header-${props.value}`)

// ─── Methods ──────────────────────────────────────────────────────────────────

function handleToggle() {
  if (props.disabled) return
  emit('toggle', props.value)
}

function handleKeydown(e: KeyboardEvent) {
  if (props.disabled) return
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault()
    handleToggle()
  }
}
</script>

<template>
  <div :class="classes">
    <!-- Header button -->
    <button
      :id="headerId"
      class="ch-accordion-item__header"
      :aria-expanded="isOpen ? 'true' : 'false'"
      :aria-controls="panelId"
      :disabled="disabled"
      @click="handleToggle"
      @keydown="handleKeydown"
    >
      <div class="ch-accordion-item__header-content">
        <slot name="header">
          <div class="ch-accordion-item__title-wrapper">
            <span v-if="title" class="ch-accordion-item__title">{{ title }}</span>
            <span v-if="description" class="ch-accordion-item__description">{{ description }}</span>
          </div>
        </slot>
      </div>

      <!-- Trailing slot -->
      <slot name="trailing"></slot>

      <!-- Chevron icon -->
      <svg
        class="ch-accordion-item__chevron"
        :class="{ 'ch-accordion-item__chevron--open': isOpen }"
        width="20"
        height="20"
        viewBox="0 0 20 20"
        fill="none"
        aria-hidden="true"
      >
        <path
          d="M6 8l4 4 4-4"
          stroke="currentColor"
          stroke-width="1.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </button>

    <!-- Collapsible panel -->
    <div
      :id="panelId"
      class="ch-accordion-item__panel"
      :aria-labelledby="headerId"
      role="region"
    >
      <div
        ref="contentRef"
        class="ch-accordion-item__content"
        :class="{ 'ch-accordion-item__content--open': isOpen }"
      >
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ─── Accordion item ──────────────────────────────────────────────────────── */
.ch-accordion-item {
  border-bottom: 1px solid var(--ch-color-border-strong);
  background: var(--ch-color-surface);
}

.ch-accordion-item:last-child {
  border-bottom: none;
}

.ch-accordion-item--open {
  background: var(--ch-color-bg-subtle);
}

/* ─── Header button ───────────────────────────────────────────────────────── */
.ch-accordion-item__header {
  display: flex;
  align-items: center;
  width: 100%;
  padding: var(--ch-space-4);
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  gap: var(--ch-space-3);
  transition: background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-accordion-item__header:hover:not(:disabled) {
  background: var(--ch-color-bg-muted);
}

.ch-accordion-item__header:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.ch-accordion-item__header:focus-visible {
  outline: 2px solid var(--ch-color-primary);
  outline-offset: -2px;
}

/* ─── Header content ──────────────────────────────────────────────────────── */
.ch-accordion-item__header-content {
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 0;
}

.ch-accordion-item__title-wrapper {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-0_5);
}

.ch-accordion-item__title {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.ch-accordion-item__description {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* ─── Chevron icon ────────────────────────────────────────────────────────── */
.ch-accordion-item__chevron {
  flex-shrink: 0;
  color: var(--ch-color-text-subtle);
  transition: transform var(--ch-duration-normal) var(--ch-ease-out);
}

.ch-accordion-item__chevron--open {
  transform: rotate(180deg);
}

/* ─── Panel ───────────────────────────────────────────────────────────────── */
.ch-accordion-item__panel {
  overflow: hidden;
}

.ch-accordion-item__content {
  max-height: 0;
  opacity: 0;
  overflow: hidden;
  transition:
    max-height var(--ch-duration-slower) var(--ch-ease-out),
    opacity var(--ch-duration-normal) var(--ch-ease-out);
}

.ch-accordion-item__content--open {
  max-height: 1000px;
  opacity: 1;
}

.ch-accordion-item__content {
  padding: 0 var(--ch-space-4) var(--ch-space-4);
  color: var(--ch-color-text);
  font-size: var(--ch-text-sm);
  line-height: var(--ch-leading-normal);
}
</style>
