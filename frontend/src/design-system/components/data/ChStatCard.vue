<script setup lang="ts">
/**
 * @component ChStatCard
 * @path /frontend/src/design-system/components/data/ChStatCard.vue
 * @description A compact card for displaying a single key metric with an
 * optional trend indicator, icon, and comparison context.
 *
 * Used across dashboards to surface high-level numbers at a glance —
 * total members, weekly attendance, contributions this month, etc.
 *
 * ─── Anatomy ─────────────────────────────────────────────────────────────────
 *   ┌─────────────────────────────────────┐
 *   │  [icon]           [trend +12%  ↑]   │
 *   │  1,248                              │
 *   │  Total Members                      │
 *   │  vs 1,116 last month                │
 *   └─────────────────────────────────────┘
 *
 * @example Basic
 * <ChStatCard label="Total Members" value="1,248" />
 *
 * @example With trend
 * <ChStatCard
 *   label="Monthly Contributions"
 *   value="GH₵ 24,500"
 *   :trend="12.4"
 *   trendLabel="vs last month"
 * />
 *
 * @example With icon slot
 * <ChStatCard label="Active Volunteers" value="86" :trend="-3.2">
 *   <template #icon><UsersIcon /></template>
 * </ChStatCard>
 */

import { computed } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Controls the overall color accent of the card.
 * Matches the badge/button variant system for consistency.
 */
type Variant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'

interface Props {
  /** The primary metric value displayed large. e.g. "1,248" or "GH₵ 24,500" */
  value: string | number

  /** Descriptive label below the value. e.g. "Total Members" */
  label: string

  /**
   * Percentage change vs a previous period.
   * Positive → green upward arrow. Negative → red downward arrow.
   * Zero or undefined → no trend shown.
   */
  trend?: number

  /**
   * Context label for the trend. e.g. "vs last month", "vs last Sunday"
   * Only shown when `trend` is provided.
   */
  trendLabel?: string

  /**
   * Optional loading state — replaces value/trend with skeleton shimmer.
   * Use while fetching dashboard data.
   */
  loading?: boolean

  /** Color accent variant for the icon background. Default: 'primary' */
  variant?: Variant
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  variant: 'primary',
})

// ─── Computed ─────────────────────────────────────────────────────────────────

/** True when trend is a non-zero number */
const hasTrend = computed(() =>
  props.trend !== undefined && props.trend !== null
)

/** Positive trend = up, negative = down */
const trendDirection = computed<'up' | 'down' | 'neutral'>(() => {
  if (!hasTrend.value || props.trend === 0) return 'neutral'
  return props.trend! > 0 ? 'up' : 'down'
})

/** Formatted trend string. e.g. "+12.4%" or "-3.2%" */
const trendFormatted = computed(() => {
  if (!hasTrend.value) return ''
  const sign = props.trend! > 0 ? '+' : ''
  return `${sign}${props.trend!.toFixed(1)}%`
})

/** CSS class for the trend chip — drives color */
const trendClasses = computed(() => [
  'ch-stat-card__trend',
  `ch-stat-card__trend--${trendDirection.value}`,
])

/** CSS class for the icon background — uses variant token */
const iconClasses = computed(() => [
  'ch-stat-card__icon',
  `ch-stat-card__icon--${props.variant}`,
])
</script>

<template>
  <div class="ch-stat-card" :class="{ 'ch-stat-card--loading': loading }">

    <!-- Top row: icon (left) + trend chip (right) -->
    <div class="ch-stat-card__top">

      <!--
        Icon slot — wrap any icon component here.
        The colored background circle is styled via the variant class.
        e.g. <template #icon><UsersIcon :size="20" /></template>
-->
      <div v-if="$slots.icon" :class="iconClasses" aria-hidden="true">
        <slot name="icon"></slot>
      </div>
      <!-- Spacer keeps trend right-aligned when no icon is present -->
      <div v-else class="ch-stat-card__icon-placeholder"></div>

      <!-- Trend chip — only rendered when trend prop is provided -->
      <div v-if="hasTrend && !loading" :class="trendClasses" aria-label="`Trend: ${trendFormatted}`">
        <!-- Arrow icon: up or down depending on direction -->
        <svg v-if="trendDirection !== 'neutral'" class="ch-stat-card__trend-arrow" width="12" height="12"
          viewBox="0 0 12 12" fill="none" aria-hidden="true">
          <!-- Up arrow path -->
          <path v-if="trendDirection === 'up'" d="M6 10V2M6 2L2 6M6 2L10 6" stroke="currentColor" stroke-width="1.5"
            stroke-linecap="round" stroke-linejoin="round" />
          <!-- Down arrow path -->
          <path v-else d="M6 2v8M6 10L2 6M6 10L10 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        <span>{{ trendFormatted }}</span>
      </div>
    </div>

    <!-- Value — the primary large number/metric -->
    <div class="ch-stat-card__body">
      <!--
        Loading shimmer skeleton — replaces value and label during data fetch.
        Two lines mimic the height of value + label so there's no layout shift
        when real data arrives.
      -->
      <template v-if="loading">
        <div class="ch-stat-card__skeleton ch-stat-card__skeleton--value"></div>
        <div class="ch-stat-card__skeleton ch-stat-card__skeleton--label"></div>
      </template>

      <template v-else>
        <!-- Primary metric value -->
        <div class="ch-stat-card__value">{{ value }}</div>

        <!-- Descriptive label -->
        <div class="ch-stat-card__label">{{ label }}</div>

        <!-- Trend context label (e.g. "vs last month") -->
        <div v-if="hasTrend && trendLabel" class="ch-stat-card__trend-label">
          {{ trendLabel }}
        </div>
      </template>
    </div>

  </div>
</template>

<style scoped>
/* ─── Card shell ──────────────────────────────────────────────────────────── */
.ch-stat-card {
  background-color: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-lg);
  padding: var(--ch-space-5);
  box-shadow: var(--ch-shadow-md);
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3);
  transition:
    box-shadow var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-out);
}

/* Sudden lift on hover — sharp tactile feel */
.ch-stat-card:hover {
  box-shadow: var(--ch-shadow-lg);
  transform: translate(-2px, -2px);
}

/* ─── Top row ─────────────────────────────────────────────────────────────── */
.ch-stat-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

/* ─── Icon ────────────────────────────────────────────────────────────────── */
.ch-stat-card__icon {
  width: 40px;
  height: 40px;
  border-radius: var(--ch-radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Variant icon backgrounds — soft tinted backgrounds with matching icon color */
.ch-stat-card__icon--default {
  background: var(--ch-color-bg-muted);
  color: var(--ch-color-text-muted);
}

.ch-stat-card__icon--primary {
  background: var(--ch-color-primary-muted);
  color: var(--ch-color-primary);
}

.ch-stat-card__icon--success {
  background: var(--ch-color-success-bg);
  color: var(--ch-color-success);
}

.ch-stat-card__icon--warning {
  background: var(--ch-color-warning-bg);
  color: var(--ch-color-warning);
}

.ch-stat-card__icon--danger {
  background: var(--ch-color-danger-bg);
  color: var(--ch-color-danger);
}

.ch-stat-card__icon--info {
  background: var(--ch-color-info-bg);
  color: var(--ch-color-info);
}

/* Empty spacer when no icon slot is provided */
.ch-stat-card__icon-placeholder {
  width: 40px;
}

/* ─── Trend chip ──────────────────────────────────────────────────────────── */
.ch-stat-card__trend {
  display: inline-flex;
  align-items: center;
  gap: var(--ch-space-0_5);
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-medium);
  padding: var(--ch-space-1) var(--ch-space-2);
  border-radius: var(--ch-radius-sm);
  line-height: 1;
}

/* Positive trend — green */
.ch-stat-card__trend--up {
  background-color: var(--ch-color-success-bg);
  color: var(--ch-color-success-fg);
}

/* Negative trend — red */
.ch-stat-card__trend--down {
  background-color: var(--ch-color-danger-bg);
  color: var(--ch-color-danger-fg);
}

/* Zero — neutral gray */
.ch-stat-card__trend--neutral {
  background-color: var(--ch-color-bg-muted);
  color: var(--ch-color-text-muted);
}

.ch-stat-card__trend-arrow {
  flex-shrink: 0;
}

/* ─── Body ────────────────────────────────────────────────────────────────── */
.ch-stat-card__body {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-0_5);
}

/* Large prominent metric number.
 *
 * ─── Design choice: serif font ──────────────────────────────────────────
 * Uses --ch-font-display (Lora, a serif typeface) instead of the default
 * sans-serif. This is intentional: large numeric values feel more
 * authoritative and distinguished in a serif face, creating visual
 * hierarchy that separates the primary metric from surrounding UI text.
 * This is a deliberate departure from the system's sans-serif default
 * and should NOT be "fixed" to use --ch-font-sans.
 */
.ch-stat-card__value {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-3xl);
  /* 30px */
  font-weight: var(--ch-font-semibold);
  line-height: var(--ch-leading-tight);
  color: var(--ch-color-text);
  letter-spacing: var(--ch-tracking-tight);
}

/* Descriptive label below the number */
.ch-stat-card__label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text-muted);
}

/* Context for the trend (e.g. "vs last month") */
.ch-stat-card__trend-label {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
  margin-top: var(--ch-space-0_5);
}

/* ─── Loading skeletons ───────────────────────────────────────────────────── */
/*
 * Shimmer placeholders rendered while data is loading.
 * The gradient animates left-to-right via ch-shimmer keyframe.
 * background-size: 200% ensures the gradient is wide enough to sweep fully.
 */
.ch-stat-card__skeleton {
  border-radius: var(--ch-radius-sm);
  background: linear-gradient(90deg,
      var(--ch-color-bg-muted) 0%,
      var(--ch-color-bg-subtle) 50%,
      var(--ch-color-bg-muted) 100%);
  background-size: 200% 100%;
  animation: ch-shimmer 1.4s var(--ch-ease-in-out) infinite;
}

/* Value skeleton — tall, matches ~30px text height */
.ch-stat-card__skeleton--value {
  height: 36px;
  width: 60%;
}

/* Label skeleton — shorter, matches ~14px text height */
.ch-stat-card__skeleton--label {
  height: 16px;
  width: 40%;
}
</style>