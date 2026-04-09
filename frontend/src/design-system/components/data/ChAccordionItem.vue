<script setup lang="ts">
/**
 * @component ChAccordionItem
 * @path /frontend/src/design-system/components/data/ChAccordionItem.vue
 * @description A single collapsible item within a ChAccordion. Contains a
 * clickable header and an expandable content panel.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic usage
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

import { computed, inject } from 'vue'
import { ACCORDION_KEY, defaultAccordionContext } from '../../composables/useAccordion.ts'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Unique identifier for this item — must match a value in the parent's state */
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

// ─── Context ──────────────────────────────────────────────────────────────────

/**
 * Injected from the parent ChAccordion.
 * Falls back to `defaultAccordionContext` (no-op toggle, always-false isOpen)
 * when rendered outside a ChAccordion, avoiding an unsafe plain-object cast.
 */
const accordion = inject(ACCORDION_KEY, defaultAccordionContext)

// ─── Derived state ────────────────────────────────────────────────────────────

const isOpen = computed(() => accordion.isOpen(props.value))

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

// ─── Handlers ─────────────────────────────────────────────────────────────────

function handleToggle() {
  if (props.disabled) return
  accordion.toggle(props.value)
}

/**
 * Arrow key navigation: move focus between sibling accordion headers.
 * Enter/Space are intentionally NOT handled here — <button> fires a click
 * event natively on both keys, so a redundant keydown handler would double-fire.
 */
function handleKeydown(e: KeyboardEvent) {
  if (props.disabled) return

  const headers = Array.from(
    document.querySelectorAll<HTMLButtonElement>('.ch-accordion-item__header:not(:disabled)'),
  )
  const currentIndex = headers.indexOf(e.currentTarget as HTMLButtonElement)
  if (currentIndex === -1) return

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    headers[(currentIndex + 1) % headers.length]?.focus()
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    headers[(currentIndex - 1 + headers.length) % headers.length]?.focus()
  } else if (e.key === 'Home') {
    e.preventDefault()
    headers[0]?.focus()
  } else if (e.key === 'End') {
    e.preventDefault()
    headers[headers.length - 1]?.focus()
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

      <!-- Trailing slot (e.g. a badge or status indicator) -->
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

    <!--
      Collapsible panel.

      The CSS grid trick animates from `grid-template-rows: 0fr` to `1fr`
      instead of `max-height: 0` → `max-height: 1000px`. This gives a
      genuine ease-out curve over the actual content height rather than
      interpolating over an arbitrarily large fixed value, which skews the
      perceived duration and easing for short content.

      The inner div requires `min-height: 0` to allow the row to collapse
      to zero — without it, the content overflows the 0fr row.
    -->
    <div
      :id="panelId"
      class="ch-accordion-item__panel"
:class="{ 'ch-accordion-item__panel--open': isOpen }"
      :aria-labelledby="headerId"
      role="region"
    >
      <div class="ch-accordion-item__content">
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

/* ─── Panel (grid-based expand/collapse) ─────────────────────────────────── */
/*
  Animating grid-template-rows from 0fr to 1fr collapses/expands to the
  exact natural height of the content — no fixed max-height guessing.
  This produces an accurate ease curve regardless of content length.

  overflow: hidden on the panel clips the content during animation.
  The inner .ch-accordion-item__content needs min-height: 0 so the
  grid row can actually collapse to zero height.
*/
.ch-accordion-item__panel {
  display: grid;
    grid-template-rows: 0fr;
  overflow: hidden;
  transition: grid-template-rows var(--ch-duration-slower) var(--ch-ease-out);
}

.ch-accordion-item__panel--open {
  grid-template-rows: 1fr;
}

/* ─── Content ─────────────────────────────────────────────────────────────── */
.ch-accordion-item__content {
  /*
      min-height: 0 allows the parent grid row to collapse to 0fr.
      Without this, content overflows even in the closed state.
    */
    min-height: 0;
  padding: 0 var(--ch-space-4) var(--ch-space-4);
  color: var(--ch-color-text);
  font-size: var(--ch-text-sm);
  line-height: var(--ch-leading-normal);
  overflow: hidden;
}
</style>