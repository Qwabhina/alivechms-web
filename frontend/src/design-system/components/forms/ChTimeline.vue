<script setup lang="ts">
/**
 * @component ChTimeline
 * @path /frontend/src/design-system/components/forms/ChTimeline.vue
 * @description A vertical timeline container for audit logs, activity feeds,
 * contribution histories, event records, and any chronological data.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Member activity log
 * <ChTimeline>
 *   <ChTimelineItem
 *     v-for="event in memberHistory"
 *     :key="event.id"
 *     :title="event.title"
 *     :timestamp="event.createdAt"
 *     :variant="event.type === 'warning' ? 'warning' : 'default'"
 *   >
 *     {{ event.description }}
 *   </ChTimelineItem>
 * </ChTimeline>
 *
 * ─── Layout ──────────────────────────────────────────────────────────────────
 * The timeline is a flex column. Each ChTimelineItem renders:
 *   [icon] ─ [connector line]
 *   [title]  [timestamp]
 *   [body content]
 */

interface Props {
  /**
   * When true, each item's connector line is dashed instead of solid.
   * Useful for "pending" or "future" timelines.
   */
  dashed?: boolean
}

withDefaults(defineProps<Props>(), { dashed: false })
</script>

<template>
  <div class="ch-timeline" :class="{ 'ch-timeline--dashed': dashed }">
    <slot />
  </div>
</template>

<style scoped>
.ch-timeline {
  display:        flex;
  flex-direction: column;
  width:          100%;
}
</style>
