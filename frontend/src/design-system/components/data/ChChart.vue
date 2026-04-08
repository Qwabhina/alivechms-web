<script setup lang="ts">
/**
 * @component ChChart
 * @path /frontend/src/design-system/components/data/ChChart.vue
 * @description A reactive Vue 3 wrapper around Chart.js that integrates
 * with the design system's token colors and handles the full lifecycle
 * of chart creation, updates, and teardown.
 *
 * ─── Why wrap Chart.js instead of using it directly? ─────────────────────────
 * 1. **Token integration**: Chart.js uses raw color strings. This wrapper
 *    reads live CSS custom property values (e.g. `--ch-color-primary`) at
 *    render time, so charts always use the current theme — including runtime
 *    overrides via `useTheme()`.
 *
 * 2. **Lifecycle management**: Chart.js instances must be explicitly destroyed
 *    when their canvas is removed. This wrapper handles `onMounted` creation
 *    and `onUnmounted` teardown automatically.
 *
 * 3. **Reactivity**: When `data` or `options` props change, the wrapper
 *    updates the Chart.js instance efficiently (update vs. full redraw).
 *
 * 4. **Defaults**: Opinionated defaults for fonts, grid lines, tooltips,
 *    and legends that match the design system's visual language.
 *
 * ─── Chart.js installation ───────────────────────────────────────────────────
 * Chart.js must be installed in the project:
 *   npm install chart.js
 *
 * ─── Supported chart types ───────────────────────────────────────────────────
 * All Chart.js types are supported via the `type` prop:
 *   'line' | 'bar' | 'doughnut' | 'pie' | 'radar' | 'polarArea' | 'bubble' | 'scatter'
 *
 * @example Attendance line chart
 * <ChChart
 *   type="line"
 *   label="Weekly attendance over the past 5 months"
 *   :data="{
 *     labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
 *     datasets: [{ label: 'Attendance', data: [220, 245, 198, 260, 231] }]
 *   }"
 *   height="300"
 * />
 *
 * @example Budget doughnut
 * <ChChart
 *   type="doughnut"
 *   label="Budget allocation by department"
 *   :data="budgetData"
 *   :options="{ plugins: { legend: { position: 'right' } } }"
 * />
 *
 * @example Multi-dataset bar chart (contributions vs expenses)
 * <ChChart
 *   type="bar"
 *   label="Monthly contributions vs expenses"
 *   :data="{
 *     labels: months,
 *     datasets: [
 *       { label: 'Contributions', data: contributionsByMonth },
 *       { label: 'Expenses',      data: expensesByMonth },
 *     ]
 *   }"
 * />
 */

import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'

import {
  Chart,
  CategoryScale,
  LinearScale,
  RadialLinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
  type ChartType,
  type ChartData,
  type ChartOptions,
  type ChartDataset,
} from 'chart.js'

// ─── Register Chart.js components ─────────────────────────────────────────────
/**
 * Chart.js v3+ requires explicit registration of every component used.
 * `Chart.register()` is idempotent — safe to call multiple times.
 */
Chart.register(
  CategoryScale,
  LinearScale,
  RadialLinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
)

// ─── CSS custom property reader ───────────────────────────────────────────────
/**
 * Reads any CSS custom property from the document root at call time.
 * Named `cssVar` rather than `tokenColor` because it is used for non-color
 * tokens too (e.g. `--ch-font-sans`).
 *
 * Called inside `onMounted`/`buildDefaultOptions()` — never at module import
 * time — so `injectCSSVars()` has already run and all tokens are available.
 */
function cssVar(name: string): string {
  return getComputedStyle(document.documentElement).getPropertyValue(name).trim()
}

/**
 * A default palette of 8 colors for multi-dataset/multi-slice charts.
 * Sourced from design system tokens so they always match the active theme.
 * Called lazily (inside chart creation) so tokens are already injected.
 */
function getDefaultPalette(): string[] {
  return [
    cssVar('--ch-color-primary'),
    cssVar('--ch-color-info'),
    cssVar('--ch-color-success'),
    cssVar('--ch-color-warning'),
    cssVar('--ch-color-danger'),
    cssVar('--ch-color-primary-hover'),
    cssVar('--ch-color-chart-7'),
    cssVar('--ch-color-chart-8'),
  ]
}

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** The Chart.js chart type */
  type: ChartType

  /**
   * Chart.js `ChartData` object — labels and datasets.
   * Datasets without explicit colors receive the default token palette.
   */
  data: ChartData

  /**
   * Chart.js `ChartOptions` — deeply merged on top of this wrapper's defaults.
   * Only specify what you want to override.
   */
  options?: ChartOptions

  /** Canvas height in pixels. Default: 300 */
  height?: number

  /**
   * Accessible description of the chart's content. Rendered as a visually
   * hidden `<figcaption>` and used as `aria-label` on the `<figure>`.
   * Should describe what the chart shows, e.g. "Weekly attendance over 5 months".
   */
  label?: string

  /**
   * Loading state — shows a skeleton shimmer and hides the canvas.
   * Use while the chart's data is being fetched.
   */
  loading?: boolean

  /**
   * Makes the chart fill its container's width automatically.
   * Sets `maintainAspectRatio: false` on Chart.js options.
   * Default: true
   */
  responsive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height: 300,
  label: 'Chart',
  loading: false,
  responsive: true,
})

// ─── Refs ─────────────────────────────────────────────────────────────────────

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

// ─── Default options builder ──────────────────────────────────────────────────
/**
 * Builds opinionated Chart.js defaults that match the design system's visual
 * language. Consumer `options` are deep-merged on top, so any default can be
 * overridden without re-specifying everything.
 */
function buildDefaultOptions(): ChartOptions {
  const textColor = cssVar('--ch-color-text-muted')
  const gridColor = cssVar('--ch-color-border')
  // CSS custom property may include quotes around the font stack — strip them
  const fontFamily = cssVar('--ch-font-sans').replace(/"/g, '')
  const fontSize = 12

  return {
    responsive: props.responsive,
    maintainAspectRatio: !props.responsive,

    plugins: {
      legend: {
        labels: {
          color: textColor,
          font: { family: fontFamily, size: fontSize },
          usePointStyle: true,
          pointStyle: 'circle',
          padding: 16,
        },
      },
      tooltip: {
        backgroundColor: cssVar('--ch-color-surface'),
        titleColor: cssVar('--ch-color-text'),
        bodyColor: textColor,
        borderColor: gridColor,
        borderWidth: 1,
        padding: 10,
        cornerRadius: 4,
        titleFont: { family: fontFamily, size: fontSize, weight: 'bold' },
        bodyFont: { family: fontFamily, size: fontSize },
      },
    },

    scales: {
      // Chart.js silently ignores cartesian scale config for non-cartesian
      // types (doughnut, pie, radar, polarArea) — safe to always include.
      x: {
        ticks: { color: textColor, font: { family: fontFamily, size: fontSize } },
        grid: { color: gridColor, drawOnChartArea: false },
        border: { color: gridColor },
      },
      y: {
        ticks: { color: textColor, font: { family: fontFamily, size: fontSize } },
        grid: { color: gridColor },
        border: { color: gridColor, dash: [4, 4] },
      },
    },
  } as ChartOptions
}

// ─── Deep merge ───────────────────────────────────────────────────────────────
/**
 * Recursively merges `override` into `base`. Arrays overwrite rather than
 * concatenate — intentional for Chart.js where you typically want to fully
 * replace arrays such as `ticks.callback` or `borderDash`.
 *
 * Typed as `object` at the boundary to avoid forcing double-casts at every
 * Chart.js call site; callers cast the result to `ChartOptions` once.
 */
function deepMerge(base: object, override: object): object {
  const result: Record<string, unknown> = { ...(base as Record<string, unknown>) }

  for (const [key, val] of Object.entries(override as Record<string, unknown>)) {
    const baseVal = result[key]
    if (
      val !== null &&
      val !== undefined &&
      typeof val === 'object' &&
      !Array.isArray(val) &&
      typeof baseVal === 'object' &&
      baseVal !== null &&
      !Array.isArray(baseVal)
    ) {
      result[key] = deepMerge(baseVal as object, val as object)
    } else if (val !== undefined) {
      result[key] = val
    }
  }

  return result
}

// ─── Dataset colour application ───────────────────────────────────────────────
/**
 * Applies the default token palette to datasets that do not specify colors.
 * Each color property is checked independently — a dataset that already sets
 * only `borderColor` still receives `backgroundColor` from the palette.
 *
 * ─── Per-type strategy ───────────────────────────────────────────────────────
 * - **line**:              `borderColor` per dataset + semi-transparent fill.
 * - **bar**:               `backgroundColor` per dataset at ~80% opacity.
 * - **pie/doughnut/polarArea**: `backgroundColor` mapped over *data points*
 *   (not datasets). These chart types have one dataset with N slices, each
 *   needing a distinct color — the old code used `datasets.length` (always 1)
 *   which caused all slices to receive the same single color.
 * - **other**:             solid `backgroundColor` per dataset.
 */
function applyDatasetColors(chartData: ChartData): ChartData {
  const palette = getDefaultPalette()
  const isSliced = ['doughnut', 'pie', 'polarArea'].includes(props.type)

  const datasets = (chartData.datasets ?? []).map((dataset: ChartDataset, i: number) => {
    const color = palette[i % palette.length]

    if (isSliced) {
      // Slice count comes from data points, not from the number of datasets
      const sliceCount = (dataset.data ?? []).length
      return {
        ...dataset,
        backgroundColor:
          dataset.backgroundColor ?? palette.slice(0, sliceCount).map((c) => `${c}dd`),
        borderColor: (dataset as ChartDataset<'doughnut'>).borderColor ?? '#fff',
        borderWidth: (dataset as ChartDataset<'doughnut'>).borderWidth ?? 2,
      }
    }

    if (props.type === 'line') {
      const ds = dataset as ChartDataset<'line'>
      return {
        ...dataset,
        borderColor: ds.borderColor ?? color,
        backgroundColor: dataset.backgroundColor ?? `${color}20`,
        borderWidth: ds.borderWidth ?? 2,
        pointBackgroundColor: ds.pointBackgroundColor ?? color,
        pointRadius: ds.pointRadius ?? 4,
        pointHoverRadius: ds.pointHoverRadius ?? 6,
        tension: ds.tension ?? 0.4,
        fill: ds.fill ?? false,
      }
    }

    if (props.type === 'bar') {
      const ds = dataset as ChartDataset<'bar'>
      return {
        ...dataset,
        backgroundColor: dataset.backgroundColor ?? `${color}cc`,
        borderColor: ds.borderColor ?? color,
        borderWidth: ds.borderWidth ?? 0,
      }
    }

    // Fallback: bubble, scatter, radar, and future types
    return {
      ...dataset,
      backgroundColor: dataset.backgroundColor ?? color,
    }
  })

  return { ...chartData, datasets: datasets as ChartData['datasets'] }
}

// ─── Chart lifecycle ──────────────────────────────────────────────────────────

function mergedOptions(): ChartOptions {
  return deepMerge(buildDefaultOptions(), props.options ?? {}) as ChartOptions
}

function createChart(): void {
  if (!canvasRef.value) return
  chartInstance = new Chart(canvasRef.value, {
    type: props.type,
    data: applyDatasetColors(props.data),
    options: mergedOptions(),
  })
}

function updateChart(): void {
  if (!chartInstance) return
  chartInstance.data = applyDatasetColors(props.data)
  chartInstance.options = mergedOptions()
  chartInstance.update()
}

function destroyChart(): void {
  // Optional chaining keeps this a one-liner; sets to null so watchers and
  // updateChart() guard correctly after a destroy.
  chartInstance?.destroy()
  chartInstance = null
}

// ─── Vue lifecycle ────────────────────────────────────────────────────────────

onMounted(() => {
  if (!props.loading) createChart()
})

onUnmounted(() => {
  // Always destroy — Chart.js holds a canvas context reference that prevents
  // reuse and causes "Canvas is already in use" errors if not released.
  destroyChart()
})

// ─── Watchers ─────────────────────────────────────────────────────────────────

watch(
  () => props.data,
  () => updateChart(),
  { deep: true },
)
watch(
  () => props.options,
  () => updateChart(),
  { deep: true },
)

/**
 * When loading flips false → canvas becomes visible via v-else.
 * `nextTick` waits for Vue to flush the DOM so the canvas node exists
 * before createChart() runs. This is more correct than `setTimeout(..., 0)`,
 * which defers to a separate task rather than the post-flush microtask.
 *
 * When loading flips true → destroy immediately; the canvas is about to
 * be removed from the DOM by v-else.
 */
watch(
  () => props.loading,
  async (isLoading) => {
    if (isLoading) {
      destroyChart()
    } else {
      await nextTick()
      if (canvasRef.value) createChart()
    }
  },
)
</script>

<template>
  <!--
    `<figure>` is the correct semantic wrapper for self-contained chart content.
    `aria-label` surfaces the description to assistive technologies.
    `aria-busy` signals the loading state to screen readers.
  -->
  <figure
    class="ch-chart"
    :style="{ height: `${height}px` }"
    :aria-label="label"
    :aria-busy="loading"
  >
    <!-- Loading skeleton — full-size shimmer matching the chart's height -->
    <div v-if="loading" class="ch-chart__skeleton" role="presentation"></div>

    <!--
      Canvas is only rendered when NOT loading so `canvasRef` is always
      valid by the time createChart() runs after nextTick in the watcher.

      `width`/`height` attributes set the canvas's internal resolution.
      CSS `width: 100%` / `height: 100%` scale it to fill the container.
      Both are needed: attributes control pixel density, CSS controls display size.
    -->
    <canvas
      v-else
      ref="canvasRef"
      class="ch-chart__canvas"
      :width="responsive ? undefined : 400"
      :height="height"
      :style="{
        width: responsive ? '100%' : undefined,
        height: `${height}px`,
      }"
    ></canvas>

    <!--
      Visually hidden caption — gives screen reader users a meaningful
      description of the chart's content beyond a generic "image" role.
      Hidden from sighted users via the sr-only pattern; does not affect layout.
    -->
    <figcaption v-if="label" class="ch-chart__caption">
      {{ label }}
    </figcaption>
  </figure>
</template>

<style scoped>
/* ─── Root container ──────────────────────────────────────────────────────── */
.ch-chart {
  position: relative;
  width: 100%;
  /* Reset <figure> browser default margin/padding */
  margin: 0;
  padding: 0;
}

/* ─── Canvas ──────────────────────────────────────────────────────────────── */
.ch-chart__canvas {
  display: block;
}

/* ─── Visually hidden caption (screen-reader accessible) ─────────────────── */
/*
 * Standard sr-only pattern: element is in the DOM and read by screen readers
 * but occupies no visible space and does not affect layout.
 */
.ch-chart__caption {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
/* ─── Loading skeleton ────────────────────────────────────────────────────── */
/*
 * Full-size shimmer that exactly matches the chart's height so there is
 * no layout shift when the real chart appears.
 */
.ch-chart__skeleton {
  width: 100%;
  height: 100%;
  border-radius: var(--ch-radius-sm);
  background: linear-gradient(
    90deg,
    var(--ch-color-bg-muted) 0%,
    var(--ch-color-bg-subtle) 50%,
    var(--ch-color-bg-muted) 100%
  );
  background-size: 200% 100%;
  animation: ch-shimmer 1.4s var(--ch-ease-in-out) infinite;
}

@keyframes ch-shimmer {
  0% {
    background-position: 200% 0;
  }

  100% {
    background-position: -200% 0;
  }
}
</style>
