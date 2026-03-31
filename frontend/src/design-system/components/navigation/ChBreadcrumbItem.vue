<script setup lang="ts">
/**
 * @component ChBreadcrumbItem
 * @path /frontend/src/design-system/components/navigation/ChBreadcrumbItem.vue
 * @description A single breadcrumb item for use within ChBreadcrumb.
 * Renders a semantic link or span and automatically inserts the correct
 * separator from the parent ChBreadcrumb context.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example
 * <ChBreadcrumb>
 *   <ChBreadcrumbItem href="/">Home</ChBreadcrumbItem>
 *   <ChBreadcrumbItem href="/members">Members</ChBreadcrumbItem>
 *   <ChBreadcrumbItem current>John Doe</ChBreadcrumbItem>
 * </ChBreadcrumb>
 *
 * @example With icon slot
 * <ChBreadcrumbItem href="/settings">
 *   <template #icon><Settings :size="14" /></template>
 *   Settings
 * </ChBreadcrumbItem>
 */

import { inject } from 'vue'
import { BREADCRUMB_KEY, defaultBreadcrumbContext } from '../../composables/useBreadcrumb.ts'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Link URL — omit or leave empty for the current item */
  href?: string
  /** Whether this is the current/active item (last in the trail) */
  current?: boolean
}

withDefaults(defineProps<Props>(), {
  href: undefined,
  current: false,
})

// ─── Context ──────────────────────────────────────────────────────────────────

/**
 * Injected from the parent ChBreadcrumb.
 * Falls back to `defaultBreadcrumbContext` — a proper BreadcrumbContext with
 * real Vue refs — when used outside ChBreadcrumb (edge case). This avoids the
 * previous plain-object fallback that satisfied the type only via an unsafe cast.
 */
const breadcrumb = inject(BREADCRUMB_KEY, defaultBreadcrumbContext)
</script>

<template>
  <li class="ch-breadcrumb__item" :class="{ 'ch-breadcrumb__item--current': current }">
    <!--
      Separator is rendered before every item and hidden on :first-child via
      a :deep() rule in ChBreadcrumb. This avoids the need for each item to
      know its own index.
    -->
    <span class="ch-breadcrumb__separator" aria-hidden="true">
      <template v-if="breadcrumb.separator.value === '/'">/</template>
      <template v-else-if="breadcrumb.separator.value === '>'">&gt;</template>
      <svg v-else width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
        <path :d="breadcrumb.separatorPath.value" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </span>

    <!-- Link or static span depending on whether item is current -->
    <component
      :is="href && !current ? 'a' : 'span'"
:href="href && !current ? href : undefined"
      class="ch-breadcrumb__link"
      :class="{ 'ch-breadcrumb__link--current': current }"
      :aria-current="current ? 'page' : undefined"
    >
      <!-- Icon slot: accepts any component (Lucide, custom SVG, etc.) -->
      <span v-if="$slots.icon" class="ch-breadcrumb__icon" aria-hidden="true">
        <slot name="icon"></slot>
      </span>

      <slot></slot>
    </component>
  </li>
</template>

<style scoped>
/*
  ChBreadcrumbItem carries its own styles because Vue's scoped CSS does not
  cross component boundaries — the parent's scoped styles cannot reach
  elements rendered inside this component's template.
*/

/* ─── Item ────────────────────────────────────────────────────────────────── */
.ch-breadcrumb__item {
  display: flex;
  align-items: center;
}

/* ─── Separator ───────────────────────────────────────────────────────────── */
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
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--ch-color-text-subtle);
}
</style>