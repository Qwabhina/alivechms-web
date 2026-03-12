<script setup lang="ts">
/**
 * @component ChTimelineItem
 * @path /frontend/src/design-system/components/forms/ChTimelineItem.vue
 * @description A single entry in a ChTimeline. Renders a dot/icon on the
 * left, a connecting vertical line to the next item, a title and timestamp
 * header, and a body slot for content.
 *
 * @example Contribution record
 * <ChTimelineItem
 *   title="Tithe recorded"
 *   timestamp="2025-07-14T09:30:00"
 *   :by="'Admin'"
 *   variant="success"
 * >
 *   GH₵ 450.00 received via Mobile Money.
 * </ChTimelineItem>
 *
 * @example With custom icon slot
 * <ChTimelineItem title="Member joined" timestamp="2024-01-01">
 *   <template #icon>
 *     <svg .../>
 *   </template>
 *   Registered as a full member.
 * </ChTimelineItem>
 */

import { computed } from 'vue'

type Variant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'

interface Props {
  title?:     string
  /**
   * ISO 8601 timestamp string or Date object.
   * Formatted to a locale-friendly string automatically.
   */
  timestamp?: string | Date
  /**
   * "By" attribution — e.g. the user who performed the action.
   * Shown as "by {by}" next to the timestamp when provided.
   */
  by?:        string
  /** Dot and accent color variant. Default: 'default' */
  variant?:   Variant
  /**
   * Whether to render the vertical connector line below this item.
   * Set false on the last item in the list.
   * If omitted, ChTimeline handles it automatically via CSS :last-child.
   */
  isLast?:    boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
})

const formattedTime = computed(() => {
  if (!props.timestamp) return ''
  const d = typeof props.timestamp === 'string' ? new Date(props.timestamp) : props.timestamp
  return d.toLocaleString(undefined, {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
})
</script>

<template>
  <div
    class="ch-timeline-item"
    :class="[`ch-timeline-item--${variant}`, { 'ch-timeline-item--last': isLast }]"
  >
    <!-- Left column: dot + connector -->
    <div class="ch-timeline-item__track">
      <!-- Icon / dot -->
      <div class="ch-timeline-item__dot">
        <slot name="icon">
          <!-- Default: filled circle dot -->
          <div class="ch-timeline-item__default-dot" />
        </slot>
      </div>
      <!-- Vertical connector line — hidden on the last item via CSS -->
      <div class="ch-timeline-item__line" />
    </div>

    <!-- Right column: header + body -->
    <div class="ch-timeline-item__content">
      <div v-if="title || timestamp" class="ch-timeline-item__header">
        <span v-if="title" class="ch-timeline-item__title">{{ title }}</span>
        <span class="ch-timeline-item__meta">
          <time v-if="formattedTime" :datetime="String(timestamp)" class="ch-timeline-item__time">
            {{ formattedTime }}
          </time>
          <span v-if="by" class="ch-timeline-item__by">by {{ by }}</span>
        </span>
      </div>

      <div v-if="$slots.default" class="ch-timeline-item__body">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-timeline-item {
  display: flex;
  gap:     var(--ch-space-3);
  padding-bottom: var(--ch-space-6);
}

/* Last item: remove bottom padding (no connector below it) */
.ch-timeline-item--last,
.ch-timeline-item:last-child {
  padding-bottom: 0;
}

/* ─── Track (dot + line) ──────────────────────────────────────────────────── */
.ch-timeline-item__track {
  display:        flex;
  flex-direction: column;
  align-items:    center;
  flex-shrink:    0;
  width:          24px;
}

/* ─── Dot ─────────────────────────────────────────────────────────────────── */
.ch-timeline-item__dot {
  width:           24px;
  height:          24px;
  border-radius:   var(--ch-radius-full);
  display:         flex;
  align-items:     center;
  justify-content: center;
  background:      var(--ch-color-bg-muted);
  border:          1.5px solid var(--ch-color-border);
  flex-shrink:     0;
  z-index:         1;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    border-color     var(--ch-duration-fast) var(--ch-ease-out);
}

/* Default inner dot */
.ch-timeline-item__default-dot {
  width:         8px;
  height:        8px;
  border-radius: var(--ch-radius-full);
  background:    var(--ch-color-text-subtle);
  transition:    background-color var(--ch-duration-fast) var(--ch-ease-out);
}

/* Variant colors for dot ring + inner dot */
.ch-timeline-item--primary  .ch-timeline-item__dot { border-color: var(--ch-color-primary);  background: var(--ch-color-primary-subtle); }
.ch-timeline-item--success  .ch-timeline-item__dot { border-color: var(--ch-color-success);  background: color-mix(in srgb, var(--ch-color-success) 12%, transparent); }
.ch-timeline-item--warning  .ch-timeline-item__dot { border-color: var(--ch-color-warning);  background: color-mix(in srgb, var(--ch-color-warning) 12%, transparent); }
.ch-timeline-item--danger   .ch-timeline-item__dot { border-color: var(--ch-color-danger);   background: color-mix(in srgb, var(--ch-color-danger)  12%, transparent); }
.ch-timeline-item--info     .ch-timeline-item__dot { border-color: var(--ch-color-info);     background: color-mix(in srgb, var(--ch-color-info)    12%, transparent); }

.ch-timeline-item--primary  .ch-timeline-item__default-dot { background: var(--ch-color-primary); }
.ch-timeline-item--success  .ch-timeline-item__default-dot { background: var(--ch-color-success); }
.ch-timeline-item--warning  .ch-timeline-item__default-dot { background: var(--ch-color-warning); }
.ch-timeline-item--danger   .ch-timeline-item__default-dot { background: var(--ch-color-danger); }
.ch-timeline-item--info     .ch-timeline-item__default-dot { background: var(--ch-color-info); }

/* ─── Connector line ──────────────────────────────────────────────────────── */
.ch-timeline-item__line {
  flex:            1;
  width:           2px;
  background:      var(--ch-color-border);
  margin-top:      var(--ch-space-1);
  min-height:      var(--ch-space-4);
}

/* Hide connector on last item */
.ch-timeline-item--last .ch-timeline-item__line,
.ch-timeline-item:last-child .ch-timeline-item__line {
  display: none;
}

/* Dashed connector when parent ChTimeline has dashed prop */
:global(.ch-timeline--dashed) .ch-timeline-item__line {
  background: repeating-linear-gradient(
    to bottom,
    var(--ch-color-border) 0px, var(--ch-color-border) 4px,
    transparent 4px, transparent 8px
  );
}

/* ─── Content ─────────────────────────────────────────────────────────────── */
.ch-timeline-item__content {
  flex:           1;
  min-width:      0;
  padding-top:    var(--ch-space-0_5); /* align text baseline with dot center */
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-1_5);
}

/* ─── Header ──────────────────────────────────────────────────────────────── */
.ch-timeline-item__header {
  display:         flex;
  align-items:     baseline;
  justify-content: space-between;
  flex-wrap:       wrap;
  gap:             var(--ch-space-2);
}

.ch-timeline-item__title {
  font-size:   var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color:       var(--ch-color-text);
  line-height: var(--ch-leading-snug);
}

.ch-timeline-item__meta {
  display:     flex;
  align-items: center;
  gap:         var(--ch-space-1_5);
  flex-shrink: 0;
}

.ch-timeline-item__time {
  font-size:  var(--ch-text-xs);
  color:      var(--ch-color-text-subtle);
  white-space:nowrap;
}

.ch-timeline-item__by {
  font-size:  var(--ch-text-xs);
  color:      var(--ch-color-text-subtle);
  white-space:nowrap;
}
.ch-timeline-item__by::before { content: '·'; margin-right: var(--ch-space-1_5); }

/* ─── Body ────────────────────────────────────────────────────────────────── */
.ch-timeline-item__body {
  font-size:   var(--ch-text-sm);
  color:       var(--ch-color-text-muted);
  line-height: var(--ch-leading-relaxed);
}
</style>
