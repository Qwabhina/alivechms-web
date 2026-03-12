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
 * Chart.js uses a tree-shakable "component registration" model. This wrapper
 * registers all commonly needed components automatically. If you only use
 * a subset of chart types, you can reduce bundle size by importing and
 * registering only what you need (see the registerChartDefaults function).
 *
 * ─── Supported chart types ───────────────────────────────────────────────────
 * All Chart.js types are supported via the `type` prop:
 *   'line' | 'bar' | 'doughnut' | 'pie' | 'radar' | 'polarArea' | 'bubble' | 'scatter'
 *
 * Most common for a church management system:
 *   - `line`     → Attendance over time, contribution trends
 *   - `bar`      → Monthly comparisons, group breakdowns
 *   - `doughnut` → Budget allocation, membership type distribution
 *   - `pie`      → Contribution type breakdown
 *
 * @example Attendance line chart
 * <ChChart
 *   type="line"
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
 *   :data="budgetData"
 *   :options="{ plugins: { legend: { position: 'right' } } }"
 * />
 *
 * @example Multi-dataset bar chart (contributions vs expenses)
 * <ChChart
 *   type="bar"
 *   :data="{
 *     labels: months,
 *     datasets: [
 *       { label: 'Contributions', data: contributionsByMonth },
 *       { label: 'Expenses',      data: expensesByMonth },
 *     ]
 *   }"
 * />
 */

import {
  ref,
  watch,
  onMounted,
  onUnmounted,
} from 'vue'

import {
  Chart,
  // Core registrables — must be registered before any chart renders
  CategoryScale,   // x-axis with category (string) labels
  LinearScale,     // y-axis with numeric linear scale
  RadialLinearScale, // for radar/polar charts
  PointElement,    // dots on line/scatter charts
  LineElement,     // lines connecting points
  BarElement,      // bars in bar charts
  ArcElement,      // slices in doughnut/pie/polar charts
  // Plugins — enabled globally
  Tooltip,         // hover tooltips
  Legend,          // chart legend
  Filler,          // fills area under line charts
  type ChartType,
  type ChartData,
  type ChartOptions,
  type ChartDataset,
} from 'chart.js'

// ─── Register Chart.js components ─────────────────────────────────────────────
/**
 * Chart.js v3+ uses a tree-shakable architecture where no components are
 * globally registered by default. We must explicitly register every component
 * we intend to use. Calling `Chart.register()` is idempotent — safe to call
 * multiple times (subsequent calls are no-ops for already-registered items).
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

// ─── Token color reader ────────────────────────────────────────────────────────
/**
 * Reads a CSS custom property value from the document root at call time.
 * Called inside `onMounted` (not at module import time) to ensure
 * `injectCSSVars()` has already run and the vars are available.
 *
 * @param varName - e.g. '--ch-color-primary'
 * @returns The computed color string, e.g. '#4f46e5'
 */
function tokenColor(varName: string): string {
  return getComputedStyle(document.documentElement)
    .getPropertyValue(varName)
    .trim()
}

/**
 * A default palette of 8 colors for multi-dataset charts.
 * Sourced from design system tokens so they always match the active theme.
 * Called lazily (inside onMounted) so tokens are already injected.
 */
function getDefaultPalette(): string[] {
  return [
    tokenColor('--ch-color-primary'),
    tokenColor('--ch-color-info'),
    tokenColor('--ch-color-success'),
    tokenColor('--ch-color-warning'),
    tokenColor('--ch-color-danger'),
    tokenColor('--ch-color-primary-hover'),
    // Fall back to literal colors for charts with more than 5 datasets
    '#8b5cf6', '#ec4899',
  ]
}

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * All supported Chart.js chart types.
 * `'line' | 'bar' | 'doughnut' | 'pie' | 'radar' | 'polarArea' | 'bubble' | 'scatter'`
 */
type SupportedChartType = ChartType

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /**
   * The Chart.js chart type.
   * Most used in this system: 'line', 'bar', 'doughnut', 'pie'
   */
  type:     SupportedChartType

  /**
   * Chart.js `ChartData` object — labels and datasets.
   * When this prop changes reactively, the chart updates automatically.
   *
   * If datasets don't include a `backgroundColor`, the default token
   * palette is applied automatically.
   */
  data:     ChartData

  /**
   * Chart.js `ChartOptions` object — overrides for any Chart.js option.
   * Merged on top of this wrapper's opinionated defaults.
   * Deeply merged — you only need to specify what you want to override.
   */
  options?: ChartOptions

  /**
   * Canvas height in pixels. Chart.js uses the canvas's intrinsic size
   * for the aspect ratio calculation.
   * Default: 300
   */
  height?:  number

  /**
   * Loading state — shows a skeleton shimmer instead of the canvas.
   * Use while fetching the chart's data.
   */
  loading?: boolean

  /**
   * When true, makes the chart fill its container's width automatically.
   * Sets `maintainAspectRatio: false` on the Chart.js options and gives
   * the canvas `width: 100%`.
   * Default: true
   */
  responsive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height:     300,
  loading:    false,
  responsive: true,
})

// ─── Refs ─────────────────────────────────────────────────────────────────────

/** Template ref to the <canvas> DOM element */
const canvasRef = ref<HTMLCanvasElement | null>(null)

/** The active Chart.js instance — null before mount and after unmount */
let chartInstance: Chart | null = null

// ─── Defaults builder ─────────────────────────────────────────────────────────
/**
 * Builds the opinionated default Chart.js options for this design system.
 * These defaults apply consistent typography, colors, and grid styling
 * that match the app's visual language.
 *
 * Parent-provided `options` are deep-merged ON TOP of these defaults,
 * so any default can be overridden without re-specifying everything.
 */
function buildDefaultOptions(): ChartOptions {
  const textColor   = tokenColor('--ch-color-text-muted')
  const gridColor   = tokenColor('--ch-color-border')
  const fontFamily  = tokenColor('--ch-font-sans').replace(/"/g, '') // remove quotes
  const fontSize    = 12

  return {
    responsive:          props.responsive,
    maintainAspectRatio: !props.responsive,

    plugins: {
      legend: {
        labels: {
          color:     textColor,
          font:      { family: fontFamily, size: fontSize },
          // Rounded square legend boxes instead of the default rectangle
          usePointStyle: true,
          pointStyle: 'circle',
          padding:   16,
        },
      },
      tooltip: {
        backgroundColor: tokenColor('--ch-color-surface'),
        titleColor:      tokenColor('--ch-color-text'),
        bodyColor:       textColor,
        borderColor:     gridColor,
        borderWidth:     1,
        padding:         10,
        cornerRadius:    8,
        titleFont:       { family: fontFamily, size: fontSize, weight: 'bold' },
        bodyFont:        { family: fontFamily, size: fontSize },
      },
    },

    scales: {
      // Only applies to cartesian charts (line, bar, scatter).
      // Chart.js ignores these for polar/radial/doughnut/pie.
      x: {
        ticks: {
          color: textColor,
          font:  { family: fontFamily, size: fontSize },
        },
        grid: {
          color:       gridColor,
          // Remove vertical grid lines for a cleaner look
          drawOnChartArea: false,
        },
        border: {
          color: gridColor,
        },
      },
      y: {
        ticks: {
          color: textColor,
          font:  { family: fontFamily, size: fontSize },
        },
        grid: {
          color: gridColor,
        },
        border: {
          color: gridColor,
          dash:  [4, 4], // dashed horizontal grid lines
        },
      },
    },
  } as ChartOptions
}

/**
 * Deep merges two objects. Used to merge user `options` on top of defaults.
 * Does NOT handle arrays (they overwrite). This is intentional for Chart.js —
 * you typically want to fully replace arrays like `scales.x.ticks.callback`.
 */
function deepMerge<T extends Record<string, unknown>>(base: T, override: Partial<T>): T {
  const result = { ...base }
  for (const key in override) {
    const val = override[key]
    if (val && typeof val === 'object' && !Array.isArray(val) && key in result && typeof result[key] === 'object') {
      result[key] = deepMerge(result[key] as Record<string, unknown>, val as Record<string, unknown>) as T[typeof key]
    } else if (val !== undefined) {
      result[key] = val as T[typeof key]
    }
  }
  return result
}

/**
 * Applies the default color palette to datasets that don't specify their own colors.
 * For line charts, also sets `borderColor` and a semi-transparent `backgroundColor`
 * for the area fill.
 */
function applyDatasetColors(chartData: ChartData): ChartData {
  const palette = getDefaultPalette()
  const datasets = (chartData.datasets ?? []).map((dataset: ChartDataset, i: number) => {
    const color = palette[i % palette.length]

    // If the dataset already specifies colors, don't override
    if (dataset.backgroundColor || (dataset as ChartDataset<'line'>).borderColor) {
      return dataset
    }

    // Line chart datasets get a border color + transparent fill
    if (props.type === 'line') {
      return {
        ...dataset,
        borderColor:     color,
        backgroundColor: `${color}20`, // 20 = ~12% opacity in hex
        borderWidth:     2,
        pointBackgroundColor: color,
        pointRadius:     4,
        pointHoverRadius:6,
        tension:         0.4, // slightly curved lines
        fill:            false,
      }
    }

    // All other chart types get solid fill colors
    return {
      ...dataset,
      backgroundColor: props.type === 'bar'
        ? `${color}cc`   // bars at ~80% opacity (cc in hex)
        : palette.slice(0, (chartData.datasets ?? []).length).map(c => `${c}dd`),
      borderColor:     props.type === 'bar' ? color : '#fff',
      borderWidth:     props.type === 'bar' ? 0 : 2,
    }
  })

  return { ...chartData, datasets }
}

// ─── Chart lifecycle ──────────────────────────────────────────────────────────

/**
 * Creates the Chart.js instance on the canvas element.
 * Called in `onMounted` once the canvas DOM node exists.
 */
function createChart() {
  if (!canvasRef.value) return

  const mergedOptions = deepMerge(
    buildDefaultOptions() as Record<string, unknown>,
    (props.options ?? {}) as Record<string, unknown>
  ) as ChartOptions

  chartInstance = new Chart(canvasRef.value, {
    type:    props.type,
    data:    applyDatasetColors(props.data),
    options: mergedOptions,
  })
}

/**
 * Updates the existing Chart.js instance when data or options change.
 * Chart.js's `chart.update()` is efficient — it only redraws what changed.
 */
function updateChart() {
  if (!chartInstance) return
  chartInstance.data    = applyDatasetColors(props.data)
  chartInstance.options = deepMerge(
    buildDefaultOptions() as Record<string, unknown>,
    (props.options ?? {}) as Record<string, unknown>
  ) as ChartOptions
  chartInstance.update()
}

/**
 * Destroys the Chart.js instance and releases its canvas context.
 * MUST be called on component unmount — Chart.js holds a reference to the
 * canvas context and creating a new chart on the same canvas without
 * destroying the old one causes "Canvas is already in use" errors.
 */
function destroyChart() {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
}

// ─── Vue lifecycle hooks ──────────────────────────────────────────────────────

onMounted(() => {
  // Don't create the chart while in loading state — the canvas isn't rendered
  if (!props.loading) createChart()
})

onUnmounted(() => {
  // Always destroy on unmount to prevent memory leaks and canvas reuse errors
  destroyChart()
})

// ─── Watchers ─────────────────────────────────────────────────────────────────

/**
 * Watches the `data` prop for changes.
 * `deep: true` catches nested mutations (e.g. pushing to datasets[0].data).
 * When data changes, update the chart efficiently without full redraw.
 */
watch(() => props.data, () => updateChart(), { deep: true })

/**
 * Watches options changes.
 * Also triggers a full update since options affect rendering globally.
 */
watch(() => props.options, () => updateChart(), { deep: true })

/**
 * Watches the loading prop.
 * When loading transitions from true → false, the canvas becomes visible
 * and we need to create the chart for the first time.
 * When loading goes true → we destroy the chart (canvas is hidden).
 */
watch(() => props.loading, (isLoading) => {
  if (!isLoading) {
    // Use nextTick equivalent — wait one microtask for v-if to render the canvas
    setTimeout(() => createChart(), 0)
  } else {
    destroyChart()
  }
})
</script>

<template>
  <div
    class="ch-chart"
    :style="{ height: `${height}px` }"
    role="img"
    :aria-label="loading ? 'Chart loading' : 'Chart'"
  >
    <!-- Loading skeleton — shown while data is being fetched -->
    <div v-if="loading" class="ch-chart__skeleton"></div>

    <!--
      The Chart.js canvas element.
      `v-else` ensures it's only in the DOM when we're NOT loading —
      this guarantees `canvasRef` is valid when `createChart()` runs.

      `width` and `height` attributes set the canvas's INTERNAL resolution.
      CSS `width: 100%` and `height: 100%` then SCALE it to fill the container.
      Both are needed — the attributes define pixel density, CSS defines display size.
    -->
    <canvas
      v-else
      ref="canvasRef"
      class="ch-chart__canvas"
      :width="responsive ? undefined : 400"
      :height="height"
      :style="{
        width:  responsive ? '100%'  : undefined,
        height: `${height}px`,
      }"
    ></canvas>
  </div>
</template>

<style scoped>
/* ─── Root container ──────────────────────────────────────────────────────── */
.ch-chart {
  position: relative;
  width:    100%;
}

/* ─── Canvas ──────────────────────────────────────────────────────────────── */
.ch-chart__canvas {
  display: block;
}

/* ─── Loading skeleton ────────────────────────────────────────────────────── */
/*
 * Full-size shimmer that exactly matches the chart's height,
 * so there's no layout shift when the real chart appears.
 */
.ch-chart__skeleton {
  width:         100%;
  height:        100%;
  border-radius: var(--ch-radius-lg);
  background:    linear-gradient(
    90deg,
    var(--ch-color-bg-muted)  0%,
    var(--ch-color-bg-subtle) 50%,
    var(--ch-color-bg-muted)  100%
  );
  background-size: 200% 100%;
  animation:     ch-shimmer 1.4s var(--ch-ease-in-out) infinite;
}
</style>
