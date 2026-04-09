<script setup lang="ts">
/**
 * @component ChTooltip
 * @path /frontend/src/design-system/components/core/ChTooltip.vue
 * @description A tooltip component that displays contextual information when
 * hovering or focusing on an element.
 *
 * ─── Design decisions ────────────────────────────────────────────────────────
 * - Visibility is JS-controlled (class-based) so opacity transitions work and
 *   the tooltip element stays in the DOM for accurate placement measurement.
 * - Auto placement measures the actual tooltip dimensions at show-time — no
 *   hardcoded size approximations.
 * - The tooltip hides on scroll so stale positioning is never visible.
 * - Arrow is rendered via CSS pseudo-elements to avoid DOM-node seam issues.
 * - Accessible via focus — keyboard users see tooltips via focusin/focusout.
 * - Supports v-model:visible for controlled / debugging use.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic
 * <ChTooltip content="Delete this item">
 *   <ChButton icon-only><TrashIcon /></ChButton>
 * </ChTooltip>
 *
 * @example With title and custom placement
 * <ChTooltip content="This action cannot be undone" placement="bottom" title="Warning">
 *   <ChButton variant="danger">Delete</ChButton>
 * </ChTooltip>
 *
 * @example Rich content via slot
 * <ChTooltip>
 *   <template #content>
 *     <strong>Keyboard shortcut:</strong><br>
 *     <kbd>Ctrl</kbd> + <kbd>S</kbd> to save
 *   </template>
 *   <ChButton>Save</ChButton>
 * </ChTooltip>
 *
 * @example Controlled / always visible (e.g. for debugging)
 * <ChTooltip v-model:visible="forceShow" content="Always here">
 *   <ChButton>Hover me</ChButton>
 * </ChTooltip>
 */

import { computed, onMounted, onUnmounted, ref } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export type TooltipPlacement = 'top' | 'bottom' | 'left' | 'right' | 'auto'
type ResolvedPlacement = Exclude<TooltipPlacement, 'auto'>

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** The tooltip content text (simple string) */
  content?: string
  /** Optional title shown in bold above the content */
  title?: string
  /** Placement relative to trigger. Default: 'auto' */
  placement?: TooltipPlacement
  /** Delay in ms before showing. Default: 200 */
  delay?: number
  /** Max width of the tooltip. Default: '240px' */
  maxWidth?: string
  /**
   * When true, forces the tooltip to be visible regardless of hover/focus.
   * Supports v-model:visible for two-way binding.
   */
  visible?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  content: '',
  title: '',
  placement: 'auto',
  delay: 200,
  maxWidth: '240px',
  visible: false,
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:visible': [value: boolean]
}>()

// ─── Refs ─────────────────────────────────────────────────────────────────────

const wrapperRef = ref<HTMLElement | null>(null)
const tooltipRef = ref<HTMLElement | null>(null)

// ─── Unique ID (for aria-describedby) ─────────────────────────────────────────

// A module-level counter guarantees uniqueness across all instances without
// needing Vue 3.5's useId().
let _counter = 0
const tooltipId = `ch-tooltip-${++_counter}`

// ─── Visibility ───────────────────────────────────────────────────────────────

/** Internal visibility driven by hover/focus events */
const internalVisible = ref(false)

/**
 * The tooltip is visible when either the internal hover/focus state is true
 * OR the consumer has forced it via the `visible` prop.
 */
const isVisible = computed(() => props.visible || internalVisible.value)

// ─── Placement ────────────────────────────────────────────────────────────────

const actualPlacement = ref<ResolvedPlacement>('top')

/**
 * Measure available viewport space and choose the placement with the most room.
 * Called at show-time so the tooltip element is always in the DOM (visibility:
 * hidden) and its real dimensions are available via getBoundingClientRect.
 */
function computePlacement(): ResolvedPlacement {
  if (props.placement !== 'auto') return props.placement as ResolvedPlacement
  if (!wrapperRef.value || !tooltipRef.value) return 'top'

  const wRect = wrapperRef.value.getBoundingClientRect()
  const tRect = tooltipRef.value.getBoundingClientRect()
  const vw = window.innerWidth
  const vh = window.innerHeight
  const gap = 8 // arrow + offset

  const candidates = [
    { dir: 'top' as const, space: wRect.top, needed: tRect.height + gap },
    { dir: 'bottom' as const, space: vh - wRect.bottom, needed: tRect.height + gap },
    { dir: 'left' as const, space: wRect.left, needed: tRect.width + gap },
    { dir: 'right' as const, space: vw - wRect.right, needed: tRect.width + gap },
  ]

  const valid = candidates.filter(p => p.space >= p.needed)
  // Fall back to all candidates if nothing fits perfectly
  const ranked = (valid.length > 0 ? valid : candidates).sort((a, b) => b.space - a.space)
  return ranked[0]?.dir ?? 'top'
}

// ─── Delay timers ─────────────────────────────────────────────────────────────

let showTimer: ReturnType<typeof setTimeout> | null = null

function clearShowTimer() {
  if (showTimer !== null) {
    clearTimeout(showTimer)
    showTimer = null
  }
}

// ─── Show / Hide ──────────────────────────────────────────────────────────────

function show() {
  clearShowTimer()
  showTimer = setTimeout(() => {
    actualPlacement.value = computePlacement()
    internalVisible.value = true
    emit('update:visible', true)
  }, props.delay)
}

function hide() {
  clearShowTimer()
  internalVisible.value = false
  emit('update:visible', false)
}

// ─── Scroll handling ──────────────────────────────────────────────────────────

/**
 * Hide on scroll so a tooltip never sits at a stale position.
 * Only fires when the tooltip is actually open to avoid unnecessary work.
 */
function handleScroll() {
  if (internalVisible.value) hide()
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true, capture: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll, true)
  clearShowTimer()
})
</script>

<template>
  <div
ref="wrapperRef"
    class="ch-tooltip-wrapper"
:aria-describedby="isVisible ? tooltipId : undefined"
    @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide"
  >
    <!-- Trigger — the default slot is ONLY used here, never inside the tooltip -->
    <slot></slot>

    <!-- Tooltip popup — always in the DOM so its dimensions are measurable -->
    <div
:id="tooltipId" ref="tooltipRef"
      class="ch-tooltip"
      :class="[`ch-tooltip--${actualPlacement}`, { 'ch-tooltip--visible': isVisible }]"
      :style="{ '--ch-tooltip-max-width': maxWidth }"
      role="tooltip"
:aria-hidden="isVisible ? 'false' : 'true'"
>
      <div class="ch-tooltip__content">
        <p v-if="title" class="ch-tooltip__title">{{ title }}</p>
        <p v-if="content" class="ch-tooltip__text">{{ content }}</p>
        <!-- Rich content slot — completely separate from the trigger slot -->
        <slot name="content"></slot>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ─── Wrapper ─────────────────────────────────────────────────────────────── */
.ch-tooltip-wrapper {
  position: relative;
  display: inline-block;
}

/* ─── Tooltip popup ───────────────────────────────────────────────────────── */
.ch-tooltip {
  position: absolute;
  z-index: var(--ch-z-tooltip);
  max-width: var(--ch-tooltip-max-width, 240px);
  width: max-content;
  padding: var(--ch-space-2) var(--ch-space-3);
  background: var(--ch-color-tooltip);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-sm);
  box-shadow: var(--ch-shadow-lg);
  font-family: var(--ch-font-sans);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-tooltip-fg);
  line-height: var(--ch-leading-normal);
  pointer-events: none;
/* Visibility is controlled via class — NOT display:none — so the element
     stays measurable (getBoundingClientRect works) and transitions can run. */
  visibility: hidden;
  opacity: 0;
  transition:
    opacity var(--ch-duration-fast) var(--ch-ease-out),
    visibility var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-tooltip--visible {
  visibility: visible;
  opacity: 1;
}

/* ─── Arrow via pseudo-elements ──────────────────────────────────────────────
   Two-layer approach eliminates the seam that appears when a single rotated
   element overlaps the tooltip border:
   ::before  — the border layer  (border-color background, sits behind)
   ::after   — the fill layer    (tooltip background, sits in front)
   Both are 8×8px squares rotated 45°; only the two sides facing away from the
   tooltip body have a border, so the inner edges blend seamlessly.
──────────────────────────────────────────────────────────────────────────── */
.ch-tooltip::before,
.ch-tooltip::after {
  content: '';
  position: absolute;
  width: 8px;
  height: 8px;
  transform-origin: center;
}

.ch-tooltip::before {
  background: var(--ch-color-border-strong);
}

.ch-tooltip::after {
  background: var(--ch-color-tooltip);
}

/* ─── Placement: top ──────────────────────────────────────────────────────── */
.ch-tooltip--top {
  bottom: calc(100% + 10px);
  left: 50%;
  transform: translateX(-50%);
}

.ch-tooltip--top::before {
  bottom: -5px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
}

.ch-tooltip--top::after {
  bottom: -4px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
}

/* ─── Placement: bottom ───────────────────────────────────────────────────── */
.ch-tooltip--bottom {
  top: calc(100% + 10px);
  left: 50%;
  transform: translateX(-50%);
}

.ch-tooltip--bottom::before {
  top: -5px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
}

.ch-tooltip--bottom::after {
  top: -4px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
}

/* ─── Placement: left ─────────────────────────────────────────────────────── */
.ch-tooltip--left {
  right: calc(100% + 10px);
  top: 50%;
  transform: translateY(-50%);
}

.ch-tooltip--left::before {
  right: -5px;
  top: 50%;
  transform: translateY(-50%) rotate(45deg);
}

.ch-tooltip--left::after {
  right: -4px;
  top: 50%;
  transform: translateY(-50%) rotate(45deg);
}

/* ─── Placement: right ────────────────────────────────────────────────────── */
.ch-tooltip--right {
  left: calc(100% + 10px);
  top: 50%;
  transform: translateY(-50%);
}

.ch-tooltip--right::before {
  left: -5px;
  top: 50%;
  transform: translateY(-50%) rotate(45deg);
}

.ch-tooltip--right::after {
  left: -4px;
  top: 50%;
  transform: translateY(-50%) rotate(45deg);
}

/* ─── Content ─────────────────────────────────────────────────────────────── */
.ch-tooltip__content {
  position: relative;
  /* Sits above the ::after fill layer so text is never obscured by the arrow */
  z-index: 1;
}

.ch-tooltip__title {
  font-weight: var(--ch-font-semibold);
  margin: 0 0 var(--ch-space-0_5);
}

.ch-tooltip__text {
  margin: 0;
  line-height: var(--ch-leading-relaxed);
}
</style>