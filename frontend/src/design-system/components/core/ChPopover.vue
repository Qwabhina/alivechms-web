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
const actualPlacement = ref<PopoverPlacement>(props.placement)

// ─── Computed ─────────────────────────────────────────────────────────────────

const placementClass = computed(() => `ch-popover--${actualPlacement.value}`)

const popoverStyle = computed(() => ({
  '--ch-popover-min-width': props.minWidth,
  '--ch-popover-max-width': props.maxWidth,
}))

/**
 * Get transform based on actual placement
 */
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

// ─── Position Calculation ────────────────────────────────────────────────────

interface PopoverPosition {
  top: string
  left: string
  placement: PopoverPlacement
}

/**
 * Calculate popover position based on trigger element and available space
 */
function getPopoverPosition(): PopoverPosition {
  if (!triggerRef.value || !popoverRef.value) {
    return { top: '0', left: '0', placement: 'bottom' }
  }

  const trigger = triggerRef.value
  const popover = popoverRef.value
  const triggerRect = trigger.getBoundingClientRect()
  const popoverRect = popover.getBoundingClientRect()
  const offset = props.offset || 8
  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight

  // Determine the best placement
  let placement: PopoverPlacement = props.placement

  if (props.placement === 'auto') {
    // Calculate space available in each direction
    const spaceTop = triggerRect.top
    const spaceBottom = viewportHeight - triggerRect.bottom
    const spaceLeft = triggerRect.left
    const spaceRight = viewportWidth - triggerRect.right

    // Choose the direction with most space
    const spaces = [
      { dir: 'top' as PopoverPlacement, space: spaceTop },
      { dir: 'bottom' as PopoverPlacement, space: spaceBottom },
      { dir: 'left' as PopoverPlacement, space: spaceLeft },
      { dir: 'right' as PopoverPlacement, space: spaceRight },
    ]
    spaces.sort((a, b) => b.space - a.space)
    placement = spaces[0]?.dir || 'bottom'
  }

  // Calculate position based on placement
  let top: number
  let left: number

  switch (placement) {
    case 'top':
      top = triggerRect.top + window.scrollY - offset - popoverRect.height
      left = triggerRect.left + window.scrollX + triggerRect.width / 2
      break
    case 'bottom':
      top = triggerRect.bottom + window.scrollY + offset
      left = triggerRect.left + window.scrollX + triggerRect.width / 2
      break
    case 'left':
      top = triggerRect.top + window.scrollY + triggerRect.height / 2
      left = triggerRect.left + window.scrollX - offset - popoverRect.width
      break
    case 'right':
      top = triggerRect.top + window.scrollY + triggerRect.height / 2
      left = triggerRect.right + window.scrollX + offset
      break
    default:
      top = triggerRect.bottom + window.scrollY + offset
      left = triggerRect.left + window.scrollX + triggerRect.width / 2
  }

  actualPlacement.value = placement

  return {
    top: `${top}px`,
    left: `${left}px`,
    placement,
  }
}

const computedPosition = computed(getPopoverPosition)

/**
 * Update popover position
 */
function updatePosition() {
  if (isOpen.value && popoverRef.value) {
    // Force recompute
    const pos = getPopoverPosition()
  }
}

/**
 * Handle scroll and resize events
 */
function handleScrollOrResize() {
  if (isOpen.value) {
    updatePosition()
  }
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
  if (props.trigger === 'hover') {
    showPopover()
  }
}

function handleTriggerLeave() {
  if (props.trigger === 'hover') {
    hidePopover()
  }
}

function handleTriggerFocus() {
  if (props.trigger === 'focus') {
    showPopover()
  }
}

function handleTriggerBlur() {
  if (props.trigger === 'focus') {
    hidePopover()
  }
}

function handleClickOutside(e: MouseEvent) {
  if (!isOpen.value) return
  if (props.trigger !== 'click') return

  const target = e.target as Node
  const isInsideTrigger = triggerRef.value?.contains(target)
  const isInsidePopover = popoverRef.value?.contains(target)

  if (!isInsideTrigger && !isInsidePopover) {
    hidePopover()
  }
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape' && isOpen.value) {
    hidePopover()
  }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscape)
  // Add scroll and resize listeners for proper positioning
  window.addEventListener('scroll', handleScrollOrResize, true)
  window.addEventListener('resize', handleScrollOrResize)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscape)
  window.removeEventListener('scroll', handleScrollOrResize, true)
  window.removeEventListener('resize', handleScrollOrResize)
})

watch(() => props.open, (newVal) => {
  isOpen.value = newVal
})

watch(isOpen, async (newVal) => {
  emit('update:open', newVal)
  // Update position after popover is rendered
  if (newVal) {
    await nextTick()
    updatePosition()
  }
})
</script>

<template>
  <div class="ch-popover-wrapper" :class="{ 'ch-popover-wrapper--open': isOpen }">
    <div ref="triggerRef" class="ch-popover__trigger" @click="handleTriggerClick" @mouseenter="handleTriggerEnter"
      @mouseleave="handleTriggerLeave" @focus="handleTriggerFocus" @blur="handleTriggerBlur">
      <slot name="trigger"></slot>
    </div>

    <Teleport to="body">
      <Transition name="ch-popover-fade">
        <div v-if="isOpen" ref="popoverRef" class="ch-popover"
          :class="[placementClass, props.class, { 'ch-popover--modal': modal }]" :style="[popoverStyle, {
            position: 'fixed',
            top: computedPosition.top,
            left: computedPosition.left,
  transform: transformStyle
          }]" role="dialog"
          :aria-modal="modal ? 'true' : 'false'">
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
  border-radius: var(--ch-radius-none);
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
  padding: var(--ch-space-3) var(--ch-space-4);
  max-height: 400px;
  overflow-y: auto;
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
  border-radius: var(--ch-radius-none);
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
  background: rgb(0 0 0 / 0.4);
  z-index: -1;
}

.ch-popover-fade-enter-active,
.ch-popover-fade-leave-active {
  transition:
    opacity var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-spring);
}

.ch-popover-fade-enter-from,
.ch-popover-fade-leave-to {
  opacity: 0;
}

/* Initial state for different placements */
.ch-popover--top.ch-popover-fade-enter-from,
.ch-popover--top.ch-popover-fade-leave-to {
  transform: translate(-50%, 8px);
}
.ch-popover--bottom.ch-popover-fade-enter-from,
.ch-popover--bottom.ch-popover-fade-leave-to {
  transform: translate(-50%, -8px);
}

.ch-popover--left.ch-popover-fade-enter-from,
.ch-popover--left.ch-popover-fade-leave-to {
  transform: translate(0, -50%) translateX(8px);
}

.ch-popover--right.ch-popover-fade-enter-from,
.ch-popover--right.ch-popover-fade-leave-to {
  transform: translate(0, -50%) translateX(-8px);
}
</style>
