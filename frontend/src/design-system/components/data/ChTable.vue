<script setup lang="ts" generic="T extends Record<string, unknown>">
/**
 * @component ChTable
 * @path /frontend/src/design-system/components/data/ChTable.vue
 * @description A full-featured, accessible data table with sorting, row
 * selection, pagination, loading skeletons, empty states, and a built-in
 * export/print workflow via ChTableExportDialog + useTableExport.
 *
 * ─── Export / Print ──────────────────────────────────────────────────────────
 * When `exportable` is true, a toolbar appears above the table with an
 * "Export / Print" button. Clicking it opens ChTableExportDialog where
 * the user configures:
 *   - Format      → CSV, Excel (.xlsx), PDF, or Print
 *   - Columns     → which columns to include / exclude
 *   - Scope       → current page only vs. all data
 *   - Filename    → output filename (for file downloads)
 *   - Page setup  → paper size + orientation (PDF / Print only)
 *   - Title       → document heading (PDF / Print only)
 *
 * ─── All-data export ─────────────────────────────────────────────────────────
 * ChTable only holds the current page in memory. For "export all data",
 * provide `fetchAllRows` — an async fn that returns the full dataset.
 * Without it, "all data" falls back to current page + logs a warning.
 *
 * ─── QMGrid note ─────────────────────────────────────────────────────────────
 * If QMGrid is evaluated and adopted, the internals can be swapped while
 * keeping this component's props/emits/slots API identical.
 *
 * ─── Column flags ────────────────────────────────────────────────────────────
 *   exportable: false  →  hides column from export dialog (use for action cols)
 *
 * @example
 * <ChTable
 *   :columns="[
 *     { key: 'name',    label: 'Name',    sortable: true },
 *     { key: 'status',  label: 'Status',  type: 'badge',
 *       badgeVariant: v => v === 'Active' ? 'success' : 'default' },
 *     { key: 'actions', label: '',        type: 'slot', exportable: false },
 *   ]"
 *   :rows="members"
 *   :total="totalCount"
 *   :exportable="true"
 *   title="Member Directory"
 *   :fetch-all-rows="() => api.members.getAll()"
 *   v-model:page="page"
 *   @sort="onSort"
 * >
 *   <template #cell-actions="{ row }">
 *     <ChButton size="sm" variant="ghost" @click="edit(row)">Edit</ChButton>
 *   </template>
 * </ChTable>
 */

import { computed, ref } from 'vue'
import ChBadge             from '../core/ChBadge.vue'
import ChAvatar            from '../core/ChAvatar.vue'
import ChButton            from '../core/ChButton.vue'
import ChTableExportDialog from './ChTableExportDialog.vue'
import type { ExportDialogResult } from './ChTableExportDialog.vue'
import { useTableExport }  from '../../composables/useTableExport'

// ─── Types ────────────────────────────────────────────────────────────────────

type CellType = 'text' | 'badge' | 'avatar' | 'slot'
type Align    = 'left' | 'center' | 'right'
type SortDir  = 'asc' | 'desc' | null

interface ColumnDef<T> {
  /** Field key — supports dot-notation: 'family.name' */
  key:          keyof T | string
  /** Column header text */
  label:        string
  /** Enables click-to-sort */
  sortable?:    boolean
  /**
   * Set false to hide this column from the export dialog.
   * Use on action columns (Edit/Delete buttons) that have no export value.
   * Default: true
   */
  exportable?:  boolean
  /** Fixed CSS column width e.g. '120px' */
  width?:       string
  /** Cell alignment. Default: 'left' */
  align?:       Align
  /**
   * Cell render strategy:
   *   text   → plain string (default)
   *   badge  → ChBadge with optional badgeVariant
   *   avatar → ChAvatar using cell value as src
   *   slot   → custom: <template #cell-{key}="{ row, value }">
   */
  type?:        CellType
  /** Static badge variant, or fn (value) => variant for dynamic coloring */
  badgeVariant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
               | ((value: unknown) => 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info')
  /** Row field used as ChAvatar name when type is 'avatar' */
  rowNameKey?:  keyof T | string
}

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  columns:       ColumnDef<T>[]
  rows:          T[]
  total?:        number
  pageSize?:     number
  /** Current page (1-based). Use v-model:page */
  page?:         number
  selectable?:   boolean
  loading?:      boolean
  skeletonRows?: number
  emptyMessage?: string
  /** Row field used as Vue :key. Default: 'id' */
  rowKey?:       keyof T | string
  hoverable?:    boolean
  /** Makes rows clickable — emits row-click */
  clickable?:    boolean

  // ── Export ────────────────────────────────────────────────────────────────
  /** Enables the toolbar + Export / Print button. Default: false */
  exportable?:   boolean
  /**
   * Human-readable table name used as the default title + filename in the
   * export dialog. e.g. 'Member Directory', 'Contribution Report'
   */
  title?:        string
  /**
   * Async fn that returns ALL rows across all pages.
   * Called when the user picks "All data" scope in the export dialog.
   * Without it, "all data" falls back to current page rows.
   * @example :fetch-all-rows="() => api.members.getAll()"
   */
  fetchAllRows?: () => Promise<Record<string, unknown>[]>
}

const props = withDefaults(defineProps<Props>(), {
  pageSize:     10,
  page:         1,
  selectable:   false,
  loading:      false,
  skeletonRows: 5,
  emptyMessage: 'No records found.',
  rowKey:       'id',
  hoverable:    false,
  clickable:    false,
  exportable:   false,
})

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
  sort:              [key: string, direction: SortDir]
  'update:page':     [page: number]
  'update:selected': [rows: T[]]
  'row-click':       [row: T]
}>()

// ─── Export composable ────────────────────────────────────────────────────────
const { exportData, isExporting, exportError } = useTableExport()

// ─── Local state ──────────────────────────────────────────────────────────────
const exportDialogOpen = ref(false)
const sortKey          = ref<string | null>(null)
const sortDir          = ref<SortDir>(null)
const selectedKeys     = ref<Set<unknown>>(new Set())

// ─── Computed ─────────────────────────────────────────────────────────────────
const totalPages = computed(() =>
  props.total ? Math.ceil(props.total / props.pageSize) : 0
)

const allSelected = computed(() =>
  props.rows.length > 0 &&
  props.rows.every(r => selectedKeys.value.has(r[props.rowKey as keyof T]))
)

const someSelected = computed(() =>
  !allSelected.value &&
  props.rows.some(r => selectedKeys.value.has(r[props.rowKey as keyof T]))
)

/**
 * Columns surfaced to the export dialog — strips exportable:false columns
 * (action button columns, etc.) so they don't appear in the column picker.
 */
const exportableColumns = computed(() =>
  props.columns
    .filter(c => c.exportable !== false)
    .map(c => ({ key: String(c.key), label: c.label }))
)

const pageNumbers = computed(() => {
  const total = totalPages.value, cur = props.page
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const pages: (number | 0)[] = [1]
  if (cur > 3) pages.push(0)
  for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
  if (cur < total - 2) pages.push(0)
  pages.push(total)
  return pages
})

const skeletonArray = computed(() => Array.from({ length: props.skeletonRows }))

// ─── Table helpers ────────────────────────────────────────────────────────────

function handleSort(colKey: string) {
  if (sortKey.value === colKey) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : sortDir.value === 'desc' ? null : 'asc'
    if (!sortDir.value) sortKey.value = null
  } else {
    sortKey.value = colKey
    sortDir.value = 'asc'
  }
  emit('sort', sortKey.value ?? colKey, sortDir.value)
}

function toggleSelectAll() {
  allSelected.value
    ? props.rows.forEach(r => selectedKeys.value.delete(r[props.rowKey as keyof T]))
    : props.rows.forEach(r => selectedKeys.value.add(r[props.rowKey as keyof T]))
  emitSelected()
}

function toggleRow(row: T) {
  const k = row[props.rowKey as keyof T]
  selectedKeys.value.has(k) ? selectedKeys.value.delete(k) : selectedKeys.value.add(k)
  emitSelected()
}

function isSelected(row: T) { return selectedKeys.value.has(row[props.rowKey as keyof T]) }

function emitSelected() {
  emit('update:selected', props.rows.filter(r => selectedKeys.value.has(r[props.rowKey as keyof T])))
}

function goToPage(p: number) {
  if (p >= 1 && p <= totalPages.value && p !== props.page) emit('update:page', p)
}

/** Reads nested value via dot-notation key e.g. 'family.name' */
function getCellValue(row: T, key: string): unknown {
  return key.split('.').reduce((o: unknown, k) =>
    o && typeof o === 'object' ? (o as Record<string, unknown>)[k] : undefined, row)
}

function resolveBadgeVariant(col: ColumnDef<T>, value: unknown) {
  if (!col.badgeVariant) return 'default'
  return typeof col.badgeVariant === 'function' ? col.badgeVariant(value) : col.badgeVariant
}

// ─── Export handler ───────────────────────────────────────────────────────────
/**
 * Receives the confirmed export config from ChTableExportDialog.
 *
 * Scope resolution:
 *   'page' → use props.rows directly (already in memory)
 *   'all'  → call fetchAllRows() to get the complete dataset, falling back
 *             to current page rows if fetchAllRows wasn't provided.
 *
 * Then delegates the actual file generation / print to useTableExport.
 */
async function handleExport(config: ExportDialogResult) {
  let rows: Record<string, unknown>[]

  if (config.scope === 'all') {
    if (props.fetchAllRows) {
      rows = await props.fetchAllRows()
    } else {
      console.warn(
        '[ChTable] Export scope is "all" but fetchAllRows prop was not provided. ' +
        'Falling back to current page. Pass :fetch-all-rows="() => api.getAll()" to fix this.'
      )
      rows = props.rows as Record<string, unknown>[]
    }
  } else {
    rows = props.rows as Record<string, unknown>[]
  }

  const result = await exportData({
    format:      config.format,
    rows,
    columns:     config.columns,
    filename:    config.filename,
    title:       config.title,
    subtitle:    config.subtitle,
    paperSize:   config.paperSize,
    orientation: config.orientation,
  })

  if (!result.success) console.error('[ChTable] Export failed:', result.error)
}
</script>

<template>
  <div class="ch-table-root">

    <!-- ── Toolbar ── -->
    <!--
      Only rendered when exportable is true.
      Provides a #toolbar-left slot for the caller to inject search/filter
      controls, and a right section with the Export / Print button.
    -->
    <div v-if="exportable" class="ch-table-toolbar">
      <div class="ch-table-toolbar__left">
        <slot name="toolbar-left" />
      </div>

      <div class="ch-table-toolbar__right">
        <!--
          Inline error notice — surfaces export failures (missing dependency,
          network error, etc.) directly next to the button that caused them.
          Dismissible via the × button which clears the exportError ref.
        -->
        <Transition name="ch-fade">
          <div v-if="exportError" class="ch-table-error-notice" role="alert">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
              <circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.2"/>
              <path d="M7 4v3M7 9.5v.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            <span>{{ exportError }}</span>
            <button
              class="ch-table-error-dismiss"
              aria-label="Dismiss error"
              @click="exportError = null"
            >
              <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M9 3L3 9M3 3l6 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
        </Transition>

        <!--
          Export / Print button.
          `isExporting` drives the loading spinner on ChButton while an async
          format (Excel, PDF) is being generated — prevents double-clicks.
        -->
        <ChButton
          variant="outline"
          size="sm"
          :loading="isExporting"
          @click="exportDialogOpen = true"
        >
          <template #icon>
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
              <path d="M7 1v7M4 5l3 3 3-3M2 10v1a1 1 0 001 1h8a1 1 0 001-1v-1"/>
            </svg>
          </template>
          Export / Print
        </ChButton>
      </div>
    </div>

    <!-- ── Scrollable table wrapper ── -->
    <div class="ch-table-wrapper">
      <table class="ch-table" role="grid">

        <thead class="ch-table__head">
          <tr>
            <th v-if="selectable" class="ch-table__th ch-table__th--select" scope="col">
              <input
                type="checkbox"
                class="ch-table__checkbox"
                :checked="allSelected"
                :indeterminate="someSelected"
                :aria-label="allSelected ? 'Deselect all rows' : 'Select all rows'"
                @change="toggleSelectAll"
              />
            </th>

            <th
              v-for="col in columns"
              :key="String(col.key)"
              scope="col"
              class="ch-table__th"
              :class="[
                `ch-table__th--${col.align ?? 'left'}`,
                { 'ch-table__th--sortable': col.sortable },
                { 'ch-table__th--sorted':   sortKey === String(col.key) },
              ]"
              :style="col.width ? { width: col.width } : {}"
              :aria-sort="
                sortKey === String(col.key)
                  ? (sortDir === 'asc' ? 'ascending' : 'descending')
                  : col.sortable ? 'none' : undefined
              "
              @click="col.sortable && handleSort(String(col.key))"
            >
              <span class="ch-table__th-inner">
                {{ col.label }}
                <span v-if="col.sortable" class="ch-table__sort-icon" aria-hidden="true">
                  <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M7 3L4 6.5h6L7 3z"
                      :fill="sortKey === String(col.key) && sortDir === 'asc'
                        ? 'var(--ch-color-primary)' : 'var(--ch-color-text-subtle)'" />
                    <path d="M7 11L4 7.5h6L7 11z"
                      :fill="sortKey === String(col.key) && sortDir === 'desc'
                        ? 'var(--ch-color-primary)' : 'var(--ch-color-text-subtle)'" />
                  </svg>
                </span>
              </span>
            </th>
          </tr>
        </thead>

        <tbody class="ch-table__body">

          <!-- Skeleton rows -->
          <template v-if="loading">
            <tr v-for="(_, i) in skeletonArray" :key="`sk-${i}`" class="ch-table__row">
              <td v-if="selectable" class="ch-table__td">
                <div class="ch-table__skeleton" style="width:16px;height:16px;border-radius:4px" />
              </td>
              <td v-for="col in columns" :key="String(col.key)" class="ch-table__td">
                <div class="ch-table__skeleton"
                     :style="{ width: `${50 + ((i + columns.indexOf(col)) % 4) * 12}%` }" />
              </td>
            </tr>
          </template>

          <!-- Empty state -->
          <tr v-else-if="rows.length === 0">
            <td
              class="ch-table__td ch-table__td--empty"
              :colspan="selectable ? columns.length + 1 : columns.length"
            >
              <slot name="empty">
                <div class="ch-table__empty">
                  <svg width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                    <rect width="40" height="40" rx="8" fill="var(--ch-color-bg-muted)"/>
                    <path d="M12 28V14a2 2 0 012-2h8l6 6v10a2 2 0 01-2 2H14a2 2 0 01-2-2z"
                          stroke="var(--ch-color-text-subtle)" stroke-width="1.5" fill="none"/>
                    <path d="M22 12v6h6" stroke="var(--ch-color-text-subtle)" stroke-width="1.5" fill="none"/>
                  </svg>
                  <p class="ch-table__empty-message">{{ emptyMessage }}</p>
                </div>
              </slot>
            </td>
          </tr>

          <!-- Data rows -->
          <tr
            v-else
            v-for="row in rows"
            :key="String(getCellValue(row, String(rowKey)))"
            class="ch-table__row"
            :class="{
              'ch-table__row--selected':  isSelected(row),
              'ch-table__row--hoverable': hoverable || clickable,
              'ch-table__row--clickable': clickable,
            }"
            :aria-selected="selectable ? isSelected(row) : undefined"
            @click="clickable && emit('row-click', row)"
          >
            <td v-if="selectable" class="ch-table__td ch-table__td--select">
              <input
                type="checkbox"
                class="ch-table__checkbox"
                :checked="isSelected(row)"
                aria-label="Select row"
                @change.stop="toggleRow(row)"
                @click.stop
              />
            </td>

            <td
              v-for="col in columns"
              :key="String(col.key)"
              class="ch-table__td"
              :class="`ch-table__td--${col.align ?? 'left'}`"
            >
              <template v-if="!col.type || col.type === 'text'">
                {{ getCellValue(row, String(col.key)) ?? '—' }}
              </template>

              <ChBadge
                v-else-if="col.type === 'badge'"
                :variant="resolveBadgeVariant(col, getCellValue(row, String(col.key)))"
                size="sm"
              >
                {{ getCellValue(row, String(col.key)) ?? '—' }}
              </ChBadge>

              <ChAvatar
                v-else-if="col.type === 'avatar'"
                :src="String(getCellValue(row, String(col.key)) ?? '')"
                :name="col.rowNameKey ? String(getCellValue(row, String(col.rowNameKey)) ?? '') : ''"
                size="sm"
              />

              <slot
                v-else-if="col.type === 'slot'"
                :name="`cell-${String(col.key)}`"
                :row="row"
                :value="getCellValue(row, String(col.key))"
              >
                {{ getCellValue(row, String(col.key)) ?? '—' }}
              </slot>
            </td>
          </tr>

        </tbody>
      </table>
    </div>

    <!-- ── Pagination ── -->
    <div
      v-if="total && totalPages > 1"
      class="ch-table-pagination"
      role="navigation"
      aria-label="Table pagination"
    >
      <span class="ch-table-pagination__summary">
        {{ ((page - 1) * pageSize + 1).toLocaleString() }}–{{ Math.min(page * pageSize, total!).toLocaleString() }}
        of {{ total!.toLocaleString() }}
      </span>

      <div class="ch-table-pagination__pages">
        <ChButton variant="ghost" size="sm" :disabled="page <= 1"
                  :icon-only="true" aria-label="Previous page"
                  @click="goToPage(page - 1)">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </ChButton>

        <template v-for="(p, i) in pageNumbers" :key="i">
          <span v-if="p === 0" class="ch-table-pagination__ellipsis" aria-hidden="true">…</span>
          <ChButton v-else :variant="p === page ? 'primary' : 'ghost'" size="sm"
                    :aria-label="`Page ${p}`" :aria-current="p === page ? 'page' : undefined"
                    @click="goToPage(p)">{{ p }}</ChButton>
        </template>

        <ChButton variant="ghost" size="sm" :disabled="page >= totalPages"
                  :icon-only="true" aria-label="Next page"
                  @click="goToPage(page + 1)">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </ChButton>
      </div>
    </div>

    <!-- ── Export config dialog ── -->
    <!--
      Mounted only when exportable is true. Internally uses <Teleport to="body">
      so it renders above all z-index stacking contexts.

      On @export: ChTableExportDialog emits the confirmed config object.
      handleExport() resolves the row scope (page vs all), then passes
      everything to useTableExport().exportData() for file generation.
    -->
    <ChTableExportDialog
      v-if="exportable"
      v-model:open="exportDialogOpen"
      :columns="exportableColumns"
      :current-page-rows="rows.length"
      :total-rows="total"
      :page-size="pageSize"
      :table-title="title"
      @export="handleExport"
    />

  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-table-root {
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-3);
  width:          100%;
}

/* ─── Toolbar ─────────────────────────────────────────────────────────────── */
/*
 * Flex row above the table: left slot for search/filters, right for
 * error notice + export button. flex-wrap lets the right side drop
 * below on narrow screens instead of overflowing.
 */
.ch-table-toolbar {
  display:         flex;
  align-items:     center;
  justify-content: space-between;
  flex-wrap:       wrap;
  gap:             var(--ch-space-3);
}

.ch-table-toolbar__left {
  display:     flex;
  align-items: center;
  gap:         var(--ch-space-2);
  flex:        1;
}

.ch-table-toolbar__right {
  display:     flex;
  align-items: center;
  gap:         var(--ch-space-2);
  flex-shrink: 0;
}

/* ─── Error notice ────────────────────────────────────────────────────────── */
.ch-table-error-notice {
  display:       flex;
  align-items:   center;
  gap:           var(--ch-space-1_5);
  padding:       var(--ch-space-1_5) var(--ch-space-3);
  background:    var(--ch-color-danger-bg);
  border:        1px solid var(--ch-color-danger);
  border-radius: var(--ch-radius-none); /* sharp edge */
  color:         var(--ch-color-danger-fg);
  font-size:     var(--ch-text-xs);
  font-weight:   var(--ch-font-medium);
  max-width:     320px;
}

.ch-table-error-dismiss {
  background: none;
  border:     none;
  padding:    0;
  margin-left:var(--ch-space-1);
  cursor:     pointer;
  color:      currentColor;
  opacity:    0.7;
  display:    flex;
  align-items:center;
  transition: opacity var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-table-error-dismiss:hover { opacity: 1; }

/* ─── Error fade transition ───────────────────────────────────────────────── */
.ch-fade-enter-active, .ch-fade-leave-active { transition: opacity var(--ch-duration-fast) var(--ch-ease-out); }
.ch-fade-enter-from,   .ch-fade-leave-to     { opacity: 0; }

/* ─── Table wrapper ───────────────────────────────────────────────────────── */
.ch-table-wrapper {
  overflow-x:    auto;
  border:        1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-none);
  box-shadow:    var(--ch-shadow-md); /* sharp structural shadow */
}

/* ─── Table ───────────────────────────────────────────────────────────────── */
.ch-table {
  width:           100%;
  border-collapse: collapse;
  font-family:     var(--ch-font-sans);
  font-size:       var(--ch-text-sm);
}

/* ─── Header ──────────────────────────────────────────────────────────────── */
.ch-table__head {
  background-color: var(--ch-color-bg-subtle);
  border-bottom:    1px solid var(--ch-color-border);
}

.ch-table__th {
  padding:        var(--ch-space-3) var(--ch-space-4);
  text-align:     left;
  font-size:      var(--ch-text-xs);
  font-weight:    var(--ch-font-semibold);
  color:          var(--ch-color-text-muted);
  letter-spacing: var(--ch-tracking-wide);
  text-transform: uppercase;
  white-space:    nowrap;
  user-select:    none;
}

.ch-table__th--center, .ch-table__td--center { text-align: center; }
.ch-table__th--right,  .ch-table__td--right  { text-align: right; }

.ch-table__th--sortable {
  cursor: pointer;
  transition: color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-table__th--sortable:hover { color: var(--ch-color-text); }
.ch-table__th--sorted         { color: var(--ch-color-primary); }
.ch-table__th--select         { width: 48px; }

.ch-table__th-inner {
  display:     inline-flex;
  align-items: center;
  gap:         var(--ch-space-1);
}
.ch-table__sort-icon { display: inline-flex; align-items: center; flex-shrink: 0; }

/* ─── Rows ────────────────────────────────────────────────────────────────── */
.ch-table__row {
  border-bottom: 1px solid var(--ch-color-border);
  transition:    background-color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-table__row:last-child      { border-bottom: none; }
.ch-table__row--hoverable:hover{ background-color: var(--ch-color-bg-subtle); }
.ch-table__row--clickable      { cursor: pointer; }
.ch-table__row--selected       { background-color: var(--ch-color-primary-subtle); }
.ch-table__row--selected:hover { background-color: var(--ch-color-primary-muted); }

/* ─── Cells ───────────────────────────────────────────────────────────────── */
.ch-table__td {
  padding:        var(--ch-space-3) var(--ch-space-4);
  color:          var(--ch-color-text);
  line-height:    var(--ch-leading-normal);
  vertical-align: middle;
}
.ch-table__td--select { width: 48px; }

/* ─── Checkbox ────────────────────────────────────────────────────────────── */
.ch-table__checkbox {
  width:        16px;
  height:       16px;
  cursor:       pointer;
  accent-color: var(--ch-color-primary);
}

/* ─── Empty state ─────────────────────────────────────────────────────────── */
.ch-table__td--empty { padding: var(--ch-space-12) var(--ch-space-4); }
.ch-table__empty {
  display: flex; flex-direction: column; align-items: center; gap: var(--ch-space-3);
}
.ch-table__empty-message {
  font-size: var(--ch-text-sm); color: var(--ch-color-text-muted); text-align: center;
}

/* ─── Skeleton ────────────────────────────────────────────────────────────── */
.ch-table__skeleton {
  height:          14px;
  border-radius:   var(--ch-radius-none);
  background:      linear-gradient(90deg,
    var(--ch-color-bg-muted) 0%, var(--ch-color-bg-subtle) 50%, var(--ch-color-bg-muted) 100%);
  background-size: 200% 100%;
  animation:       ch-shimmer 1.4s var(--ch-ease-in-out) infinite;
}

/* ─── Pagination ──────────────────────────────────────────────────────────── */
.ch-table-pagination {
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
  gap: var(--ch-space-3);
}
.ch-table-pagination__summary { font-size: var(--ch-text-sm); color: var(--ch-color-text-muted); }
.ch-table-pagination__pages   { display: flex; align-items: center; gap: var(--ch-space-1); }
.ch-table-pagination__ellipsis {
  padding: 0 var(--ch-space-1); color: var(--ch-color-text-subtle);
  font-size: var(--ch-text-sm); user-select: none;
}
</style>
