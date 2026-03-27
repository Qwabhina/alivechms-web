<script setup lang="ts">
/**
 * @component ChBreadcrumbItem
 * @path /frontend/src/design-system/components/navigation/ChBreadcrumbItem.vue
 * @description A single breadcrumb item for use within ChBreadcrumb.
 * Provides a semantic link or span depending on whether it's the current item.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example
 * <ChBreadcrumb>
 *   <ChBreadcrumbItem href="/">Home</ChBreadcrumbItem>
 *   <ChBreadcrumbItem href="/members">Members</ChBreadcrumbItem>
 *   <ChBreadcrumbItem current>John Doe</ChBreadcrumbItem>
 * </ChBreadcrumb>
 */

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Link URL — omit or leave empty for the current item */
  href?: string
  /** Whether this is the current/active item (last in the trail) */
  current?: boolean
}

withDefaults(defineProps<Props>(), {
  href: '',
  current: false,
})
</script>

<template>
  <li class="ch-breadcrumb__item" :class="{ 'ch-breadcrumb__item--current': current }">
    <component
      :is="href && !current ? 'a' : 'span'"
      :href="href"
      class="ch-breadcrumb__link"
      :class="{ 'ch-breadcrumb__link--current': current }"
      :aria-current="current ? 'page' : undefined"
    >
      <slot></slot>
    </component>
  </li>
</template>

<style scoped>
/* Styles are inherited from ChBreadcrumb parent */
</style>
