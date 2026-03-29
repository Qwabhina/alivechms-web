<script setup lang="ts">
/**
 * @component ChBreadcrumb
 * @path /frontend/src/design-system/components/navigation/ChBreadcrumb.vue
 * @description A navigation component that displays the current page's location
 * within a hierarchical site structure. Supports automatic item rendering or
 * fully custom content via slots.
 *
 * ─── Accessibility ───────────────────────────────────────────────────────────
 * Uses `aria-label="Breadcrumb"` and a `<nav>` element for screen reader
 * recognition. The last item receives `aria-current="page"` to indicate the
 * current location in the trail.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example With items array
 * <ChBreadcrumb :items="[
 *   { label: 'Home', href: '/' },
 *   { label: 'Members', href: '/members' },
 *   { label: 'John Doe' },
 * ]" />
 *
 * @example With custom slots
 * <ChBreadcrumb>
 *   <ChBreadcrumbItem href="/">Home</ChBreadcrumbItem>
 *   <ChBreadcrumbItem href="/members">Members</ChBreadcrumbItem>
 *   <ChBreadcrumbItem current>John Doe</ChBreadcrumbItem>
 * </ChBreadcrumb>
 *
 * @example With custom separator
 * <ChBreadcrumb :items="items" separator=">" />
 * <ChBreadcrumb :items="items" separator="chevron" />
 * <ChBreadcrumb :items="items" separator="arrow" />
 */

import { computed, provide, type InjectionKey, type ComputedRef } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Separator styles:
 * - `/`       → Slash (default, classic breadcrumb)
 * - `>`       → Greater-than symbol
 * - `chevron` → SVG chevron-right
 * - `arrow`   → SVG arrow-right
 */
export type BreadcrumbSeparator = '/' | '>' | 'chevron' | 'arrow'

/** A single breadcrumb item descriptor */
export interface BreadcrumbItem {
  /** Display text */
  label: string
  /** Link URL — omit for the current (last) item */
  href?: string
  /** Optional SVG path for an icon rendered before the label */
  icon?: string
}

/** Context provided to ChBreadcrumbItem children in slot mode */
export interface BreadcrumbContext {
  separator: ComputedRef<BreadcrumbSeparator>
  separatorPath: ComputedRef<string>
}

/** Typed injection key — import this in ChBreadcrumbItem */
export const BREADCRUMB_KEY: InjectionKey<BreadcrumbContext> = Symbol('ChBreadcrumb')

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Array of breadcrumb items to render */
  items?: BreadcrumbItem[]
  /** Separator style between items. Default: '/' */
  separator?: BreadcrumbSeparator
  /** CSS class applied to the root `<nav>` element */
  navClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  items: () => [],
  separator: '/',
  navClass: '',
})

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * SVG path data for the selected separator.
 * Only used when separator is 'chevron' or 'arrow'.
 */
const separatorPath = computed<string>(() => {
  switch (props.separator) {
    case 'chevron': return 'M5 3l6 6-6 6'
    case 'arrow': return 'M4 7l6 6-6 6M10 13h8'
    default: return ''
  }
})

// ─── Provide context to ChBreadcrumbItem children ─────────────────────────────

/**
 * ChBreadcrumbItem injects this context to render the correct separator
 * without requiring the consumer to repeat separator config per-item.
 */
provide(BREADCRUMB_KEY, {
  separator: computed(() => props.separator),
  separatorPath,
})
</script>

<template>
  <nav :class="['ch-breadcrumb', navClass]" aria-label="Breadcrumb">
    <ol class="ch-breadcrumb__list">
      <!--
        Slot mode: consumer provides ChBreadcrumbItem children.
        The items-array fallback only renders when no slot content is given.
      -->
      <slot>
        <template v-for="(item, index) in items" :key="item.href ?? item.label">
          <!-- Separator (aria-hidden so screen readers skip it) -->
          <li v-if="index > 0" class="ch-breadcrumb__separator" aria-hidden="true">
            <template v-if="separator === '/'">/</template>
            <template v-else-if="separator === '>'">&gt;</template>
            <svg v-else width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
              <path :d="separatorPath" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </li>

          <!-- Breadcrumb item -->
          <li class="ch-breadcrumb__item" :class="{ 'ch-breadcrumb__item--current': index === items.length - 1 }">
            <component
              :is="item.href && index !== items.length - 1 ? 'a' : 'span'"
              :href="item.href && index !== items.length - 1 ? item.href : undefined"
              :class="['ch-breadcrumb__link', { 'ch-breadcrumb__link--current': index === items.length - 1 }]"
              :aria-current="index === items.length - 1 ? 'page' : undefined"
            >
              <svg v-if="item.icon" class="ch-breadcrumb__icon" width="14" height="14" viewBox="0 0 14 14" fill="none"
                stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path :d="item.icon" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              {{ item.label }}
            </component>
          </li>
        </template>
      </slot>
    </ol>
  </nav>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-breadcrumb {
  width: 100%;
}

/* ─── List ────────────────────────────────────────────────────────────────── */
.ch-breadcrumb__list {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0;
  margin: 0;
  padding: 0;
  list-style: none;
}

/* ─── Item ────────────────────────────────────────────────────────────────── */
.ch-breadcrumb__item {
  display: flex;
  align-items: center;
}

/* ─── Separator (items-array mode) ───────────────────────────────────────── */
.ch-breadcrumb__separator {
  display: flex;
  align-items: center;
  padding: 0 var(--ch-space-2);
  color: var(--ch-color-text-subtle);
  font-size: var(--ch-text-sm);
  flex-shrink: 0;
}

.ch-breadcrumb__separator svg {
  width: 14px;
  height: 14px;
}

/* ─── Separator (slot mode via ChBreadcrumbItem) ─────────────────────────── */
/*
  ChBreadcrumbItem renders a .ch-breadcrumb__separator before its link.
  We hide it on the first item so only inter-item separators are visible.
  :deep() is required because the element lives in a child component's template.
*/
:deep(.ch-breadcrumb__item:first-child .ch-breadcrumb__separator) {
  display: none;
}

/* ─── Link ────────────────────────────────────────────────────────────────── */
.ch-breadcrumb__link {
  display: inline-flex;
  align-items: center;
  gap: var(--ch-space-1);
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text-muted);
  text-decoration: none;
  transition:
    color var(--ch-duration-fast) var(--ch-ease-out),
    text-decoration var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-breadcrumb__link:hover {
  color: var(--ch-color-text);
  text-decoration: underline;
}

.ch-breadcrumb__link--current {
  color: var(--ch-color-text);
  font-weight: var(--ch-font-semibold);
  cursor: default;
}

.ch-breadcrumb__link--current:hover {
  text-decoration: none;
}

/* ─── Icon ────────────────────────────────────────────────────────────────── */
.ch-breadcrumb__icon {
  flex-shrink: 0;
  color: var(--ch-color-text-subtle);
}
</style>