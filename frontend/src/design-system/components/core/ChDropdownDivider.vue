<script setup lang="ts">
/**
 * @component ChDropdownDivider
 * @path /frontend/src/design-system/components/core/ChDropdownDivider.vue
 * @description A horizontal divider for visually separating groups of items
 * within a ChDropdown menu.
 *
 * Renders as an `<hr>` element, which carries implicit `role="separator"`
 * semantics natively. Screen readers announce it as a group boundary,
 * helping users understand the menu's structure without needing to read
 * every item to find the logical groupings.
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
  <!--
    `<hr>` has implicit role="separator" and is announced by screen readers
    as a thematic break — correctly communicating the group boundary.

    The previous `<div role="separator" aria-hidden="true">` was self-
    contradictory: aria-hidden told AT to ignore the element entirely,
    making the role="separator" annotation unreachable and pointless.

    `<hr>` requires no role or aria attributes — its semantics are built in.
  -->
  <hr class="ch-dropdown-divider" />
</template>

<style scoped>
.ch-dropdown-divider {
  height: 1px;
  margin: var(--ch-space-1) 0;
  background-color: var(--ch-color-border);
/* Reset browser default <hr> styles */
  border: none;
  padding: 0;
}
</style>