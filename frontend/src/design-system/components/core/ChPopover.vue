<script setup lang="ts">
/**
 * @component ChPopover
 * @path /frontend/src/design-system/components/core/ChPopover.vue
 * @description A floating popup that displays rich content when triggered.
 */

import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { X } from 'lucide-vue-next'

// ─── Types ────────────────────────────────────────────────────────────────────

export type PopoverTrigger = 'click' | 'hover' | 'focus'
export type PopoverPlacement = 'top' | 'bottom' | 'left' | 'right' | 'auto'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Controls open state — use v-model:open */
  open?: boolean
  /** How the popover is triggered. Default: 'click' */
  trigger?: PopoverTrigger
  /** Placement relative to trigger. Default: 'bottom'. Use 'auto' for smart placement */
  placement?: PopoverPlacement
  /** Offset from trigger in pixels. Default: 8 */
  offset?: number
  /** When true, shows a backdrop and traps focus */
  modal?: boolean
  /** When true, disables the popover */
  disabled?: boolean
  /** Custom CSS class for the popover content */
  class?: string
  /** Min width of popover. Default: '200px' */
  minWidth?: string
  /** Max width of popover. Default: '320px' */
  maxWidth?: string
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
  trigger: 'click',
  placement: 'bottom',
  offset: 8,
  modal: false,
  disabled: false,
  class: '',
  minWidth: '200px',
  maxWidth: '320px',
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:open': [value: boolean]
  'before-open': []
  'after-open': []
  'before-close': []
  'after-close': []
}>()

// ─── Local state ──────────────────────────────────────────────────────────────

const isOpen = ref(props.open)
const triggerRef = ref<HTMLElement | null>(null)
const popoverRef = ref<HTMLElement | null>(null)
const actualPlacement = ref<Exclude<PopoverPlacement, 'auto'>>(
  props.placement === 'auto' ? 'bottom' : props.placement,
)

// ─── Position state (imperative, not computed) ────────────────────────────────

/**
 * Position is stored as a plain ref and written imperatively so that
 * DOM measurements (getBoundingClientRect) — which Vue cannot track as
 * reactive dependencies — always produce the correct result.
 */
const position = ref({ top: '0px', left: '0px' })

// ─── Computed ─────────────────────────────────────────────────────────────────

const popoverStyle = computed(() => ({
  '--ch-popover-min-width': props.minWidth,
  '--ch-popover-max-width': props.maxWidth,
}))

const transformStyle = computed(() => {
  switch (actualPlacement.value) {
    case 'top':
    case 'bottom':
      return 'translate(-50%, 0)'
    case 'left':
    case 'right':
      return 'translate(0, -50%)'
    default:
      return 'translate(-50%, 0)'
  }
})

// ─── Position Calculation ─────────────────────────────────────────────────────

/**
 * Measure the DOM and write the result into `position` and `actualPlacement`.
 * Must be called after the popover element is mounted (nextTick after open).
 */
function updatePosition(): void {
  if (!triggerRef.value || !popoverRef.value) return

  const triggerRect = triggerRef.value.getBoundingClientRect()
  const popoverRect = popoverRef.value.getBoundingClientRect()
  const { offset } = props
  const vw = window.innerWidth
  const vh = window.innerHeight

  // ── Resolve placement ──────────────────────────────────────────────────────
  let placement: Exclude<PopoverPlacement, 'auto'>

  if (props.placement === 'auto') {
    const spaces = [
      { dir: 'bottom' as const, space: vh - triggerRect.bottom },
      { dir: 'top' as const, space: triggerRect.top },
      { dir: 'right' as const, space: vw - triggerRect.right },
      { dir: 'left' as const, space: triggerRect.left },
    ]
    placement = spaces.sort((a, b) => b.space - a.space)[0]?.dir ?? 'bottom'
  } else {
    placement = props.placement
  }

  actualPlacement.value = placement

  // ── Calculate raw position ─────────────────────────────────────────────────
  let top: number
  let left: number

  switch (placement) {
    case 'top':
      top = triggerRect.top - offset - popoverRect.height
      left = triggerRect.left + triggerRect.width / 2
      break
    case 'bottom':
      top = triggerRect.bottom + offset
      left = triggerRect.left + triggerRect.width / 2
      break
    case 'left':
      top = triggerRect.top + triggerRect.height / 2
      left = triggerRect.left - offset - popoverRect.width
      break
    case 'right':
      top = triggerRect.top + triggerRect.height / 2
      left = triggerRect.right + offset
      break
  }

  // ── Clamp to viewport (prevent overflow) ───────────────────────────────────
  // For top/bottom placements the transform shifts left by 50% of popover width
  if (placement === 'top' || placement === 'bottom') {
    const halfW = popoverRect.width / 2
    left = Math.min(Math.max(left, halfW + 8), vw - halfW - 8)
  }
  top = Math.min(Math.max(top, 8), vh - popoverRect.height - 8)

  position.value = { top: `${top}px`, left: `${left}px` }
}

// ─── Methods ──────────────────────────────────────────────────────────────────

function showPopover() {
  if (props.disabled || isOpen.value) return
  emit('before-open')
  isOpen.value = true
  emit('update:open', true)
  emit('after-open')
}

function hidePopover() {
  if (!isOpen.value) return
  emit('before-close')
  isOpen.value = false
  emit('update:open', false)
  emit('after-close')
}

function togglePopover() {
  isOpen.value ? hidePopover() : showPopover()
}

// ─── Event handlers ───────────────────────────────────────────────────────────

function handleTriggerClick(e: MouseEvent) {
  if (props.trigger === 'click') {
    e.stopPropagation()
    togglePopover()
  }
}

function handleTriggerEnter() {
  if (props.trigger === 'hover') showPopover()
}

function handleTriggerLeave() {
  if (props.trigger === 'hover') hidePopover()
}

function handleTriggerFocus() {
  if (props.trigger === 'focus') showPopover()
}

function handleTriggerBlur() {
  if (props.trigger === 'focus') hidePopover()
}

function handleClickOutside(e: MouseEvent) {
  if (!isOpen.value || props.trigger !== 'click') return
  const target = e.target as Node
  if (!triggerRef.value?.contains(target) && !popoverRef.value?.contains(target)) {
    hidePopover()
  }
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape' && isOpen.value) hidePopover()
}

function handleScrollOrResize() {
  if (isOpen.value) updatePosition()
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscape)
  window.addEventListener('scroll', handleScrollOrResize, { passive: true, capture: true })
  window.addEventListener('resize', handleScrollOrResize, { passive: true })
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscape)
  window.removeEventListener('scroll', handleScrollOrResize, true)
  window.removeEventListener('resize', handleScrollOrResize)
})

// ─── Watch ────────────────────────────────────────────────────────────────────

watch(
  () => props.open,
  (newVal) => {
    isOpen.value = newVal
  },
)

watch(isOpen, async (newVal) => {
  if (newVal) {
    // Wait for the popover element to mount, then measure and position it.
    await nextTick()
    updatePosition()
  }
})
</script>

<template>
  <div class="ch-popover-wrapper" :class="{ 'ch-popover-wrapper--open': isOpen }">
    <div
      ref="triggerRef"
      class="ch-popover__trigger"
      @click="handleTriggerClick"
      @mouseenter="handleTriggerEnter"
      @mouseleave="handleTriggerLeave"
      @focus="handleTriggerFocus"
      @blur="handleTriggerBlur"
    >
      <slot name="trigger"></slot>
    </div>

    <Teleport to="body">
      <Transition :name="`ch-popover-fade--${actualPlacement}`">
        <div
          v-if="isOpen"
          ref="popoverRef"
          class="ch-popover"
          :class="[`ch-popover--${actualPlacement}`, props.class, { 'ch-popover--modal': modal }]"
          :style="[
            popoverStyle,
            {
              position: 'fixed',
              top: position.top,
              left: position.left,
              transform: transformStyle,
            },
          ]"
          role="dialog"
          :aria-modal="modal ? 'true' : 'false'"
        >
          <div v-if="$slots.header" class="ch-popover__header">
            <slot name="header"></slot>
          </div>

          <div class="ch-popover__content">
            <slot></slot>
          </div>

          <div v-if="$slots.footer" class="ch-popover__footer">
            <slot name="footer"></slot>
          </div>

          <button v-if="modal" class="ch-popover__close" aria-label="Close" @click="hidePopover">
            <X :size="16" :stroke-width="2" />
          </button>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.ch-popover-wrapper {
  position: relative;
  display: inline-block;
}

.ch-popover__trigger {
  display: inline-block;
}

.ch-popover {
  min-width: var(--ch-popover-min-width, 200px);
  max-width: var(--ch-popover-max-width, 320px);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  box-shadow: var(--ch-shadow-xl);
  overflow: hidden;
  z-index: var(--ch-z-popover);
}

.ch-popover__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--ch-space-3) var(--ch-space-4);
  border-bottom: 1px solid var(--ch-color-border);
  background: var(--ch-color-bg-subtle);
}

.ch-popover__content {
  /* No hardcoded max-height — consumers (e.g. ChDropdown) control their own
       scrolling via --ch-dropdown-max-height on the inner items list. */
  padding: var(--ch-space-3) var(--ch-space-4);
}

.ch-popover__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--ch-space-2);
  padding: var(--ch-space-3) var(--ch-space-4);
  border-top: 1px solid var(--ch-color-border);
  background: var(--ch-color-bg-subtle);
}

.ch-popover__close {
  position: absolute;
  top: var(--ch-space-2);
  right: var(--ch-space-2);
  background: none;
  border: none;
  padding: var(--ch-space-1);
  color: var(--ch-color-text-subtle);
  cursor: pointer;
  border-radius: var(--ch-radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  transition:
    color var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-popover__close:hover {
  color: var(--ch-color-text);
  background-color: var(--ch-color-bg-muted);
}

.ch-popover--modal::before {
  content: '';
  position: fixed;
  inset: 0;
  background: var(--ch-color-overlay);
  z-index: -1;
}

/* ─── Transitions (one named transition per placement) ────────────────────── */

.ch-popover-fade--top-enter-active,
.ch-popover-fade--top-leave-active,
.ch-popover-fade--bottom-enter-active,
.ch-popover-fade--bottom-leave-active,
.ch-popover-fade--left-enter-active,
.ch-popover-fade--left-leave-active,
.ch-popover-fade--right-enter-active,
.ch-popover-fade--right-leave-active {
  transition:
    opacity var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-spring);
}

.ch-popover-fade--top-enter-from,
.ch-popover-fade--top-leave-to {
  opacity: 0;
  transform: translate(-50%, 6px);
}

.ch-popover-fade--bottom-enter-from,
.ch-popover-fade--bottom-leave-to {
  opacity: 0;
  transform: translate(-50%, -6px);
}

.ch-popover-fade--left-enter-from,
.ch-popover-fade--left-leave-to {
  opacity: 0;
  transform: translate(6px, -50%);
}

.ch-popover-fade--right-enter-from,
.ch-popover-fade--right-leave-to {
  opacity: 0;
  transform: translate(-6px, -50%);
}
</style>
