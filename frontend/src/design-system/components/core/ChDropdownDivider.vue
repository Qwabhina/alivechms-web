<script setup lang="ts">
/**
 * @component ChDropdownDivider
 * @path /frontend/src/design-system/components/core/ChDropdownDivider.vue
 * @description A horizontal divider for visually separating groups of items
 * within a ChDropdown menu.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example
 * <ChDropdown>
 *   <template #trigger>
 *     <ChButton>Actions</ChButton>
 *   </template>
 *   <ChDropdownItem label="Edit" @click="edit" />
 *   <ChDropdownItem label="Share" @click="share" />
 *   <ChDropdownDivider />
 *   <ChDropdownItem label="Delete" variant="danger" @click="remove" />
 * </ChDropdown>
 */
</script>

<template>
  <div class="ch-dropdown-divider" role="separator" aria-hidden="true"></div>
</template>

<style scoped>
/* ─── Divider ─────────────────────────────────────────────────────────────── */
.ch-dropdown-divider {
  height: 1px;
  margin: var(--ch-space-1) 0;
  background-color: var(--ch-color-border);
}
</style>
