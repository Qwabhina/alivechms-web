<script setup lang="ts">
/**
 * @component ChPopover
 * @path /frontend/src/design-system/components/core/ChPopover.vue
 * @description A floating popup that displays rich content when triggered.
 */

import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { X } from 'lucide-vue-next'

// ─── Types ────────────────────────────────────────────────────────────────────

export type PopoverTrigger = 'click' | 'hover' | 'focus'
export type PopoverPlacement = 'top' | 'bottom' | 'left' | 'right'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Controls open state — use v-model:open */
  open?: boolean
  /** How the popover is triggered. Default: 'click' */
  trigger?: PopoverTrigger
  /** Placement relative to trigger. Default: 'bottom' */
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

// ─── Computed ─────────────────────────────────────────────────────────────────

const placementClass = computed(() => `ch-popover--${props.placement}`)

const popoverStyle = computed(() => ({
  '--ch-popover-min-width': props.minWidth,
  '--ch-popover-max-width': props.maxWidth,
}))

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
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscape)
})

watch(() => props.open, (newVal) => {
  isOpen.value = newVal
})

watch(isOpen, (newVal) => {
  emit('update:open', newVal)
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
          :class="[placementClass, props.class, { 'ch-popover--modal': modal }]" :style="popoverStyle" role="dialog"
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
  position: absolute;
  z-index: var(--ch-z-popover);
  min-width: var(--ch-popover-min-width, 200px);
  max-width: var(--ch-popover-max-width, 320px);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-none);
  box-shadow: var(--ch-shadow-xl);
  overflow: hidden;
}

.ch-popover--top {
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(-8px);
  margin-bottom: var(--ch-space-2);
}

.ch-popover--bottom {
  top: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(8px);
  margin-top: var(--ch-space-2);
}

.ch-popover--left {
  right: 100%;
  top: 50%;
  transform: translateY(-50%) translateX(-8px);
  margin-right: var(--ch-space-2);
}

.ch-popover--right {
  left: 100%;
  top: 50%;
  transform: translateY(-50%) translateX(8px);
  margin-left: var(--ch-space-2);
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
  transform: translateX(-50%) translateY(4px);
}

.ch-popover--left.ch-popover-fade-enter-from,
.ch-popover--left.ch-popover-fade-leave-to {
  transform: translateY(-50%) translateX(4px);
}

.ch-popover--right.ch-popover-fade-enter-from,
.ch-popover--right.ch-popover-fade-leave-to {
  transform: translateY(-50%) translateX(-4px);
}

.ch-popover--top.ch-popover-fade-enter-from,
.ch-popover--top.ch-popover-fade-leave-to {
  transform: translateX(-50%) translateY(4px);
}
</style>
