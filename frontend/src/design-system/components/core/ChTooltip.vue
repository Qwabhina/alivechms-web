<script setup lang="ts">
/**
 * @component ChTooltip
 * @path /frontend/src/design-system/components/core/ChTooltip.vue
 * @description A tooltip component that displays contextual information when
 * hovering or focusing on an element. Uses CSS-only positioning with smart
 * placement detection.
 *
 * ─── Design decisions ────────────────────────────────────────────────────────
 * - CSS-only show/hide via data attributes — no JavaScript animation overhead
 * - Supports 4 placements: top, bottom, left, right
 * - Arrow indicator points to the trigger element
 * - Accessible via focus — keyboard users can see tooltips
 * - Max-width ensures readability on long content
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic usage
 * <ChTooltip content="Delete this item">
 *   <ChButton :iconOnly="true"><TrashIcon /></ChButton>
 * </ChTooltip>
 *
 * @example With title and custom placement
 * <ChTooltip content="This action cannot be undone" placement="bottom" title="Delete">
 *   <ChButton variant="danger">Delete</ChButton>
 * </ChTooltip>
 *
 * @example Rich content with HTML
 * <ChTooltip>
 *   <template #content>
 *     <strong>Keyboard shortcut:</strong><br>
 *     <kbd>Ctrl</kbd> + <kbd>S</kbd> to save
 *   </template>
 *   <ChButton>Save</ChButton>
 * </ChTooltip>
 */

import { computed, onMounted, onUnmounted, ref } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Tooltip placement relative to the trigger:
 * - `top`    → above the trigger
 * - `bottom` → below the trigger
 * - `left`   → to the left of the trigger
 * - `right`  → to the right of the trigger
 * - `auto`   → automatically choose best placement based on available space
 */
export type TooltipPlacement = 'top' | 'bottom' | 'left' | 'right' | 'auto'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** The tooltip content text (simple string) */
  content?: string
  /** Optional title shown in bold above the content */
  title?: string
  /** Placement relative to trigger. Default: 'top' */
  placement?: TooltipPlacement
  /** Delay in ms before showing. Default: 200 */
  delay?: number
  /** Max width of the tooltip. Default: '240px' */
  maxWidth?: string
  /** When true, tooltip is always visible (for debugging or controlled use) */
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

// ─── Local State ──────────────────────────────────────────────────────────────

const actualPlacement = ref<TooltipPlacement>(props.placement)

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Builds the placement class for the tooltip.
 * E.g. 'ch-tooltip--top', 'ch-tooltip--bottom', etc.
 */
const placementClass = computed(() => `ch-tooltip--${actualPlacement.value}`)

// ─── Smart Placement Detection ────────────────────────────────────────────────

const wrapperRef = ref<HTMLElement | null>(null)

/**
 * Detect the best placement based on available viewport space
 */
function detectBestPlacement(): TooltipPlacement {
  if (props.placement !== 'auto' || !wrapperRef.value) {
    return props.placement
  }

  const wrapper = wrapperRef.value
  const rect = wrapper.getBoundingClientRect()
  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight

  // Calculate available space in each direction
  const spaceTop = rect.top
  const spaceBottom = viewportHeight - rect.bottom
  const spaceLeft = rect.left
  const spaceRight = viewportWidth - rect.right

  // Estimate tooltip size (approximate)
  const tooltipHeight = 50
  const tooltipWidth = 150

  // Find best placement
  const placements = [
    { dir: 'top' as TooltipPlacement, space: spaceTop, needed: tooltipHeight },
    { dir: 'bottom' as TooltipPlacement, space: spaceBottom, needed: tooltipHeight },
    { dir: 'left' as TooltipPlacement, space: spaceLeft, needed: tooltipWidth },
    { dir: 'right' as TooltipPlacement, space: spaceRight, needed: tooltipWidth },
  ]

  // Filter placements that have enough space
  const validPlacements = placements.filter(p => p.space >= p.needed)

  if (validPlacements.length === 0) {
    // Fallback to bottom if no placement fits
    return 'bottom'
  }

  // Choose the placement with most space
  validPlacements.sort((a, b) => b.space - a.space)
  return validPlacements[0]?.dir || 'bottom'
}

/**
 * Update placement on scroll/resize
 */
function updatePlacement() {
  actualPlacement.value = detectBestPlacement()
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  if (props.placement === 'auto') {
    window.addEventListener('scroll', updatePlacement, true)
    window.addEventListener('resize', updatePlacement)
    // Initial detection
    updatePlacement()
  }
})

onUnmounted(() => {
  window.removeEventListener('scroll', updatePlacement, true)
  window.removeEventListener('resize', updatePlacement)
})
</script>

<template>
  <div
ref="wrapperRef"
    class="ch-tooltip-wrapper"
    :class="{ 'ch-tooltip-wrapper--visible': visible }"
  >
    <!-- Trigger slot content -->
    <slot></slot>

    <!-- Tooltip popup -->
    <div
      class="ch-tooltip"
      :class="placementClass"
      :style="{ '--ch-tooltip-max-width': maxWidth }"
      role="tooltip"
      :aria-hidden="!visible && !$slots.default ? 'true' : 'false'"
    >
      <!-- Arrow indicator -->
      <div class="ch-tooltip__arrow" aria-hidden="true"></div>

      <!-- Content -->
      <div class="ch-tooltip__content">
        <div v-if="title" class="ch-tooltip__title">{{ title }}</div>
        <div v-if="content" class="ch-tooltip__text">{{ content }}</div>
        <slot v-if="$slots.content" name="content"></slot>
        <slot v-else-if="!content && !title"></slot>
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
  display: none;
  max-width: var(--ch-tooltip-max-width, 240px);
  padding: var(--ch-space-2) var(--ch-space-3);
  background: var(--ch-color-tooltip);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-none);
  box-shadow: var(--ch-shadow-lg);
  font-family: var(--ch-font-sans);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-tooltip-fg);
  line-height: var(--ch-leading-normal);
  pointer-events: none;
  /* don't interfere with mouse events */
}

/* Show tooltip on hover/focus of wrapper */
.ch-tooltip-wrapper:hover .ch-tooltip,
.ch-tooltip-wrapper:focus-within .ch-tooltip,
.ch-tooltip-wrapper--visible .ch-tooltip {
  display: block;
}

/* ─── Placement: Top ──────────────────────────────────────────────────────── */
.ch-tooltip--top {
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(-8px);
  margin-bottom: var(--ch-space-2);
}

.ch-tooltip--top .ch-tooltip__arrow {
  bottom: -4px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
}

/* ─── Placement: Bottom ───────────────────────────────────────────────────── */
.ch-tooltip--bottom {
  top: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(8px);
  margin-top: var(--ch-space-2);
}

.ch-tooltip--bottom .ch-tooltip__arrow {
  top: -4px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
}

/* ─── Placement: Left ─────────────────────────────────────────────────────── */
.ch-tooltip--left {
  right: 100%;
  top: 50%;
  transform: translateY(-50%) translateX(-8px);
  margin-right: var(--ch-space-2);
}

.ch-tooltip--left .ch-tooltip__arrow {
  right: -4px;
  top: 50%;
  transform: translateY(-50%) rotate(45deg);
}

/* ─── Placement: Right ────────────────────────────────────────────────────── */
.ch-tooltip--right {
  left: 100%;
  top: 50%;
  transform: translateY(-50%) translateX(8px);
  margin-left: var(--ch-space-2);
}

.ch-tooltip--right .ch-tooltip__arrow {
  left: -4px;
  top: 50%;
  transform: translateY(-50%) rotate(45deg);
}

/* ─── Arrow ───────────────────────────────────────────────────────────────── */
.ch-tooltip__arrow {
  position: absolute;
  width: 8px;
  height: 8px;
  background: var(--ch-color-tooltip);
  border: 1px solid var(--ch-color-border-strong);
  /* Clip the corner to match tooltip border */
}

/* Adjust arrow border to match placement */
.ch-tooltip--top .ch-tooltip__arrow {
  border-right: none;
  border-bottom: none;
}

.ch-tooltip--bottom .ch-tooltip__arrow {
  border-left: none;
  border-top: none;
}

.ch-tooltip--left .ch-tooltip__arrow {
  border-right: none;
  border-top: none;
}

.ch-tooltip--right .ch-tooltip__arrow {
  border-left: none;
  border-bottom: none;
}

/* ─── Content ─────────────────────────────────────────────────────────────── */
.ch-tooltip__content {
  position: relative;
  z-index: 1;
}

.ch-tooltip__title {
  font-weight: var(--ch-font-semibold);
  margin-bottom: var(--ch-space-0_5);
}

.ch-tooltip__text {
  line-height: var(--ch-leading-relaxed);
}
</style>
