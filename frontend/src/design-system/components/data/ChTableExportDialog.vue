<script setup lang="ts">
/**
 * @component ChTableExportDialog
 * @path /frontend/src/design-system/components/data/ChTableExportDialog.vue
 * @description A configuration dialog that lets the user choose format, columns,
 * data scope, filename, and page settings before exporting or printing a table.
 *
 * ─── Dialog sections ─────────────────────────────────────────────────────────
 * 1. Format selector — CSV, Excel, PDF, Print (icon cards)
 * 2. Columns         — checkboxes to include/exclude each column
 * 3. Data scope      — Current page vs All data (only shown when total > pageSize)
 * 4. Filename        — text input (hidden for Print)
 * 5. Page settings   — paper size + orientation (only for PDF and Print)
 * 6. Summary bar     — "X rows × Y columns" preview
 * 7. Footer          — Cancel + Export/Print buttons
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * This component is mounted inside ChTable and controlled via `v-model:open`.
 * It receives column definitions and a row-fetching callback from ChTable.
 *
 * @example
 * <ChTableExportDialog
 *   v-model:open="exportDialogOpen"
 *   :columns="tableColumns"
 *   :current-page-rows="currentRows"
 *   :total-rows="total"
 *   :page-size="pageSize"
 *   :table-title="'Member List'"
 *   @export="handleExport"
 * />
 */

import { ref, computed, watch } from 'vue'
import type {
  ExportFormat,
  PaperSize,
  Orientation,
  ExportColumn,
} from '../../composables/useTableExport'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * A column descriptor as seen by this dialog.
 * Uses only the fields needed for the export — decoupled from ColumnDef<T>.
 */
export interface DialogColumn {
  key: string
  label: string
}

/** The complete config object emitted when the user confirms the export */
export interface ExportDialogResult {
  format: ExportFormat
  columns: ExportColumn[]
  scope: 'page' | 'all'
  filename: string
  title: string
  subtitle: string
  paperSize: PaperSize
  orientation: Orientation
}

// ─── Format option definitions ────────────────────────────────────────────────

/**
 * Each format card shown in the format selector.
 * Contains the icon SVG path, display label, and a short description.
 */
const FORMAT_OPTIONS: {
  id: ExportFormat
  label: string
  description: string
  icon: string // SVG path data
  ext: string // file extension shown in UI
}[] = [
  {
    id: 'csv',
    label: 'CSV',
    ext: '.csv',
    description: 'Plain text, opens in any spreadsheet',
    // Table/grid icon
    icon: 'M3 5h18M3 10h18M3 15h18M8 5v15M13 5v15',
  },
  {
    id: 'excel',
    label: 'Excel',
    ext: '.xlsx',
    description: 'Microsoft Excel workbook with formatting',
    // Excel/sheet icon
    icon: 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1v5h5M8 13l2 2 4-4',
  },
  {
    id: 'pdf',
    label: 'PDF',
    ext: '.pdf',
    description: 'Portable document, great for sharing',
    // PDF/document icon
    icon: 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1v5h5m-5 5H8m8 4H8m2-8H8',
  },
  {
    id: 'print',
    label: 'Print',
    ext: '',
    description: 'Send directly to a printer',
    // Printer icon
    icon: 'M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-10 0h8v5H6v-5z',
  },
]

const PAPER_SIZES: { id: PaperSize; label: string }[] = [
  { id: 'a4', label: 'A4' },
  { id: 'a3', label: 'A3' },
  { id: 'letter', label: 'Letter' },
  { id: 'legal', label: 'Legal' },
]

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /** Controls dialog open/close — use v-model:open */
  open: boolean

  /** Column definitions from the parent table */
  columns: DialogColumn[]

  /**
   * Rows currently visible on the active page.
   * Shown as the "current page" option in the scope selector.
   */
  currentPageRows: number

  /**
   * Total rows across ALL pages (from the table's `total` prop).
   * When this equals currentPageRows (single page), scope selector is hidden.
   */
  totalRows?: number

  /** The table's page size — shown in the scope selector hint */
  pageSize?: number

  /** Pre-fills the title field. Default: empty */
  tableTitle?: string
}

const props = withDefaults(defineProps<Props>(), {
  totalRows: 0,
  pageSize: 10,
  tableTitle: '',
})

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
  /** Emitted to close the dialog */
  'update:open': [value: boolean]

  /**
   * Emitted when the user confirms — carries the full export config.
   * The parent (ChTable) listens for this and calls useTableExport().
   */
  export: [config: ExportDialogResult]
}>()

// ─── Local state ──────────────────────────────────────────────────────────────

/** Currently selected export format */
const selectedFormat = ref<ExportFormat>('csv')

/**
 * Which column keys are selected for export.
 * Initialized to ALL columns selected by default.
 */
const selectedColumns = ref<Set<string>>(new Set())

/** Whether to export the current page or all data */
const scope = ref<'page' | 'all'>('all')

/** The output filename (without extension) */
const filename = ref('')

/** Optional document title for PDF and Print */
const title = ref('')

/** Optional subtitle line (e.g. church name or date range) */
const subtitle = ref('')

/** Paper size for PDF and Print */
const paperSize = ref<PaperSize>('a4')

/** Page orientation for PDF and Print */
const orientation = ref<Orientation>('portrait')

// ─── Initialization ───────────────────────────────────────────────────────────

/**
 * When the dialog opens, reset all fields to sensible defaults.
 * This ensures a clean state each time it's opened.
 */
watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      // Select all columns by default
      selectedColumns.value = new Set(props.columns.map((c) => c.key))
      // Pre-fill title from table title prop
      title.value = props.tableTitle ?? ''
      filename.value = props.tableTitle
        ? props.tableTitle.toLowerCase().replace(/\s+/g, '-')
        : 'export'
      subtitle.value = ''
      scope.value = 'all'
      selectedFormat.value = 'csv'
      paperSize.value = 'a4'
      orientation.value = 'portrait'
    }
  },
)

// ─── Computed ─────────────────────────────────────────────────────────────────

/** True when the table spans multiple pages (scope selector is relevant) */
const isMultiPage = computed(() => !!props.totalRows && props.totalRows > props.currentPageRows)

/** True when PDF or Print is selected (shows page settings section) */
const showPageSettings = computed(
  () => selectedFormat.value === 'pdf' || selectedFormat.value === 'print',
)

/** True when Print is selected (hides the filename field) */
const showFilename = computed(() => selectedFormat.value !== 'print')

/** Number of rows that will be exported based on scope selection */
const exportRowCount = computed(() =>
  scope.value === 'all' ? props.totalRows || props.currentPageRows : props.currentPageRows,
)

/** Number of selected columns */
const selectedColumnCount = computed(() => selectedColumns.value.size)

/** Whether the confirm button should be enabled */
const canExport = computed(
  () =>
    selectedColumnCount.value > 0 && (showFilename.value ? filename.value.trim().length > 0 : true),
)

/** Label for the confirm button */
const confirmLabel = computed(() => (selectedFormat.value === 'print' ? 'Print' : 'Export'))

// ─── Handlers ─────────────────────────────────────────────────────────────────

function close() {
  emit('update:open', false)
}

/** Toggle a single column's selected state */
function toggleColumn(key: string) {
  if (selectedColumns.value.has(key)) {
    // Don't allow deselecting the last column
    if (selectedColumns.value.size > 1) {
      selectedColumns.value.delete(key)
    }
  } else {
    selectedColumns.value.add(key)
  }
}

/** Select or deselect all columns at once */
function toggleAllColumns() {
  if (selectedColumns.value.size === props.columns.length) {
    // Deselect all except the first (must have at least one)
    selectedColumns.value = new Set([props.columns[0]?.key].filter((k): k is string => !!k))
  } else {
    selectedColumns.value = new Set(props.columns.map((c) => c.key))
  }
}

/**
 * Builds the ExportDialogResult and emits it to the parent.
 * The parent is responsible for fetching "all" rows if scope === 'all'
 * and then calling useTableExport().exportData().
 */
function confirm() {
  if (!canExport.value) return

  // Build the ordered column list from the original columns array order
  // (preserving the display order, filtered to selected keys)
  const orderedColumns = props.columns
    .filter((col) => selectedColumns.value.has(col.key))
    .map((col) => ({ key: col.key, label: col.label }))

  emit('export', {
    format: selectedFormat.value,
    columns: orderedColumns,
    scope: scope.value,
    filename: filename.value.trim() || 'export',
    title: title.value.trim() || filename.value.trim() || 'Report',
    subtitle: subtitle.value.trim(),
    paperSize: paperSize.value,
    orientation: orientation.value,
  })

  close()
}

/** Close when clicking the backdrop */
function onBackdropClick(e: MouseEvent) {
  if (e.target === e.currentTarget) close()
}
</script>

<template>
  <!-- Backdrop overlay — covers the entire screen behind the dialog -->
  <Teleport to="body">
    <Transition name="ch-dialog-fade">
      <div
        v-if="open"
        class="ch-export-backdrop"
        @click="onBackdropClick"
        aria-modal="true"
        role="dialog"
        aria-labelledby="export-dialog-title"
      >
        <!-- Dialog panel -->
        <Transition name="ch-dialog-scale">
          <div v-if="open" class="ch-export-dialog">
            <!-- ── Header ── -->
            <div class="ch-export-dialog__header">
              <div>
                <h2 id="export-dialog-title" class="ch-export-dialog__title">Export / Print</h2>
                <p class="ch-export-dialog__subtitle">Configure your export settings below</p>
              </div>
              <!-- Close button -->
              <button class="ch-export-dialog__close" @click="close" aria-label="Close dialog">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path
                    d="M15 5L5 15M5 5l10 10"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                  />
                </svg>
              </button>
            </div>

            <!-- ── Scrollable body ── -->
            <div class="ch-export-dialog__body">
              <!-- ── Section 1: Format ── -->
              <section class="ch-export-section">
                <h3 class="ch-export-section__label">Format</h3>
                <div class="ch-export-format-grid">
                  <button
                    v-for="fmt in FORMAT_OPTIONS"
                    :key="fmt.id"
                    type="button"
                    class="ch-export-format-card"
                    :class="{ 'ch-export-format-card--active': selectedFormat === fmt.id }"
                    @click="selectedFormat = fmt.id"
                    :aria-pressed="selectedFormat === fmt.id"
                  >
                    <!-- Format icon -->
                    <svg
                      class="ch-export-format-card__icon"
                      width="24"
                      height="24"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.5"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      aria-hidden="true"
                    >
                      <path :d="fmt.icon" />
                    </svg>
                    <span class="ch-export-format-card__name">{{ fmt.label }}</span>
                    <span v-if="fmt.ext" class="ch-export-format-card__ext">{{ fmt.ext }}</span>
                    <span class="ch-export-format-card__desc">{{ fmt.description }}</span>
                    <!-- Active checkmark badge -->
                    <span
                      v-if="selectedFormat === fmt.id"
                      class="ch-export-format-card__check"
                      aria-hidden="true"
                    >
                      <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <circle cx="7" cy="7" r="7" fill="var(--ch-color-primary)" />
                        <path
                          d="M4 7l2 2 4-4"
                          stroke="white"
                          stroke-width="1.5"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                        />
                      </svg>
                    </span>
                  </button>
                </div>
              </section>

              <!-- ── Section 2: Columns ── -->
              <section class="ch-export-section">
                <div class="ch-export-section__header-row">
                  <h3 class="ch-export-section__label">Columns</h3>
                  <button type="button" class="ch-export-link-btn" @click="toggleAllColumns">
                    {{ selectedColumnCount === columns.length ? 'Deselect all' : 'Select all' }}
                  </button>
                </div>

                <div class="ch-export-columns-grid">
                  <label
                    v-for="col in columns"
                    :key="col.key"
                    class="ch-export-col-checkbox"
                    :class="{ 'ch-export-col-checkbox--checked': selectedColumns.has(col.key) }"
                  >
                    <input
                      type="checkbox"
                      :checked="selectedColumns.has(col.key)"
                      :disabled="selectedColumns.has(col.key) && selectedColumns.size === 1"
                      @change="toggleColumn(col.key)"
                    />
                    <span class="ch-export-col-checkbox__label">{{ col.label }}</span>
                  </label>
                </div>
              </section>

              <!-- ── Section 3: Data scope (only when multi-page) ── -->
              <section v-if="isMultiPage" class="ch-export-section">
                <h3 class="ch-export-section__label">Data Scope</h3>
                <div class="ch-export-scope-options">
                  <label
                    class="ch-export-radio"
                    :class="{ 'ch-export-radio--active': scope === 'all' }"
                  >
                    <input type="radio" v-model="scope" value="all" />
                    <div class="ch-export-radio__content">
                      <span class="ch-export-radio__title">All data</span>
                      <span class="ch-export-radio__hint"
                        >{{ totalRows?.toLocaleString() }} total rows</span
                      >
                    </div>
                  </label>

                  <label
                    class="ch-export-radio"
                    :class="{ 'ch-export-radio--active': scope === 'page' }"
                  >
                    <input type="radio" v-model="scope" value="page" />
                    <div class="ch-export-radio__content">
                      <span class="ch-export-radio__title">Current page only</span>
                      <span class="ch-export-radio__hint">{{ currentPageRows }} rows visible</span>
                    </div>
                  </label>
                </div>
              </section>

              <!-- ── Section 4: Filename (hidden for Print) ── -->
              <section v-if="showFilename" class="ch-export-section">
                <h3 class="ch-export-section__label">Filename</h3>
                <div class="ch-export-filename-row">
                  <input
                    v-model="filename"
                    type="text"
                    class="ch-export-input"
                    placeholder="e.g. members-report"
                    aria-label="Export filename"
                  />
                  <span class="ch-export-filename-ext">
                    {{ FORMAT_OPTIONS.find((f) => f.id === selectedFormat)?.ext }}
                  </span>
                </div>
              </section>

              <!-- ── Section 5: Document settings (PDF + Print) ── -->
              <section v-if="showPageSettings" class="ch-export-section">
                <h3 class="ch-export-section__label">Document Settings</h3>

                <!-- Title -->
                <div class="ch-export-field">
                  <label class="ch-export-field__label" for="export-title">Title</label>
                  <input
                    id="export-title"
                    v-model="title"
                    type="text"
                    class="ch-export-input"
                    placeholder="e.g. Member Directory"
                  />
                </div>

                <!-- Subtitle -->
                <div class="ch-export-field">
                  <label class="ch-export-field__label" for="export-subtitle">
                    Subtitle
                    <span class="ch-export-field__optional">(optional)</span>
                  </label>
                  <input
                    id="export-subtitle"
                    v-model="subtitle"
                    type="text"
                    class="ch-export-input"
                    placeholder="e.g. Grace Community Church · July 2025"
                  />
                </div>

                <!-- Paper size + orientation in a row -->
                <div class="ch-export-two-col">
                  <!-- Paper size -->
                  <div class="ch-export-field">
                    <label class="ch-export-field__label" for="export-paper">Paper Size</label>
                    <select id="export-paper" v-model="paperSize" class="ch-export-select">
                      <option v-for="ps in PAPER_SIZES" :key="ps.id" :value="ps.id">
                        {{ ps.label }}
                      </option>
                    </select>
                  </div>

                  <!-- Orientation -->
                  <div class="ch-export-field">
                    <label class="ch-export-field__label">Orientation</label>
                    <div class="ch-export-orientation">
                      <label
                        class="ch-export-orientation__opt"
                        :class="{
                          'ch-export-orientation__opt--active': orientation === 'portrait',
                        }"
                      >
                        <input type="radio" v-model="orientation" value="portrait" />
                        <!-- Portrait icon -->
                        <svg
                          width="20"
                          height="28"
                          viewBox="0 0 20 28"
                          fill="none"
                          aria-hidden="true"
                        >
                          <rect
                            x="1"
                            y="1"
                            width="18"
                            height="26"
                            rx="2"
                            :stroke="
                              orientation === 'portrait'
                                ? 'var(--ch-color-primary)'
                                : 'var(--ch-color-border-strong)'
                            "
                            :fill="
                              orientation === 'portrait'
                                ? 'var(--ch-color-primary-subtle)'
                                : 'var(--ch-color-bg-subtle)'
                            "
                            stroke-width="1.5"
                          />
                          <line
                            x1="4"
                            y1="8"
                            x2="16"
                            y2="8"
                            stroke="var(--ch-color-border-strong)"
                            stroke-width="1"
                          />
                          <line
                            x1="4"
                            y1="12"
                            x2="16"
                            y2="12"
                            stroke="var(--ch-color-border-strong)"
                            stroke-width="1"
                          />
                          <line
                            x1="4"
                            y1="16"
                            x2="12"
                            y2="16"
                            stroke="var(--ch-color-border-strong)"
                            stroke-width="1"
                          />
                        </svg>
                        <span>Portrait</span>
                      </label>

                      <label
                        class="ch-export-orientation__opt"
                        :class="{
                          'ch-export-orientation__opt--active': orientation === 'landscape',
                        }"
                      >
                        <input type="radio" v-model="orientation" value="landscape" />
                        <!-- Landscape icon (rotated rectangle) -->
                        <svg
                          width="28"
                          height="20"
                          viewBox="0 0 28 20"
                          fill="none"
                          aria-hidden="true"
                        >
                          <rect
                            x="1"
                            y="1"
                            width="26"
                            height="18"
                            rx="2"
                            :stroke="
                              orientation === 'landscape'
                                ? 'var(--ch-color-primary)'
                                : 'var(--ch-color-border-strong)'
                            "
                            :fill="
                              orientation === 'landscape'
                                ? 'var(--ch-color-primary-subtle)'
                                : 'var(--ch-color-bg-subtle)'
                            "
                            stroke-width="1.5"
                          />
                          <line
                            x1="4"
                            y1="6"
                            x2="24"
                            y2="6"
                            stroke="var(--ch-color-border-strong)"
                            stroke-width="1"
                          />
                          <line
                            x1="4"
                            y1="10"
                            x2="24"
                            y2="10"
                            stroke="var(--ch-color-border-strong)"
                            stroke-width="1"
                          />
                          <line
                            x1="4"
                            y1="14"
                            x2="16"
                            y2="14"
                            stroke="var(--ch-color-border-strong)"
                            stroke-width="1"
                          />
                        </svg>
                        <span>Landscape</span>
                      </label>
                    </div>
                  </div>
                </div>
              </section>
            </div>
            <!-- end body -->

            <!-- ── Footer: summary + actions ── -->
            <div class="ch-export-dialog__footer">
              <!-- Summary pill -->
              <div class="ch-export-summary">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                  <rect
                    x="1"
                    y="1"
                    width="12"
                    height="12"
                    rx="2"
                    stroke="currentColor"
                    stroke-width="1.2"
                  />
                  <path
                    d="M4 4h6M4 7h6M4 10h4"
                    stroke="currentColor"
                    stroke-width="1.2"
                    stroke-linecap="round"
                  />
                </svg>
                <span>
                  {{ exportRowCount.toLocaleString() }} rows &times;
                  {{ selectedColumnCount }} column{{ selectedColumnCount !== 1 ? 's' : '' }}
                </span>
              </div>

              <!-- Action buttons -->
              <div class="ch-export-dialog__actions">
                <button type="button" class="ch-export-btn ch-export-btn--ghost" @click="close">
                  Cancel
                </button>
                <button
                  type="button"
                  class="ch-export-btn ch-export-btn--primary"
                  :disabled="!canExport"
                  @click="confirm"
                >
                  <!-- Format icon in the button -->
                  <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                  >
                    <path
                      v-if="selectedFormat !== 'print'"
                      d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"
                    />
                    <path
                      v-else
                      d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-10 0h8v5H6v-5z"
                    />
                  </svg>
                  {{ confirmLabel }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ─── Backdrop ────────────────────────────────────────────────────────────── */
.ch-export-backdrop {
  position: fixed;
  inset: 0;
  /* top:0 right:0 bottom:0 left:0 — covers viewport */
  background: var(--ch-color-overlay);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: var(--ch-z-modal);
  padding: var(--ch-space-4);
  overflow-y: auto;
}

/* ─── Dialog panel ────────────────────────────────────────────────────────── */
.ch-export-dialog {
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-lg);
  box-shadow: var(--ch-shadow-lg);
  width: 100%;
  max-width: 560px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* ─── Header ──────────────────────────────────────────────────────────────── */
.ch-export-dialog__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: var(--ch-space-6) var(--ch-space-6) var(--ch-space-4);
  border-bottom: 1px solid var(--ch-color-border);
  flex-shrink: 0;
  /* never compress the header */
}

.ch-export-dialog__title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-xl);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  line-height: var(--ch-leading-tight);
}

.ch-export-dialog__subtitle {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin-top: var(--ch-space-0_5);
}

.ch-export-dialog__close {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--ch-color-text-subtle);
  padding: var(--ch-space-1);
  border-radius: var(--ch-radius-md);
  display: flex;
  align-items: center;
  transition:
    color var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-dialog__close:hover {
  color: var(--ch-color-text);
  background-color: var(--ch-color-bg-muted);
}

/* ─── Scrollable body ─────────────────────────────────────────────────────── */
.ch-export-dialog__body {
  flex: 1;
  overflow-y: auto;
  padding: var(--ch-space-5) var(--ch-space-6);
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
}

/* ─── Section ─────────────────────────────────────────────────────────────── */
.ch-export-section {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3);
}

.ch-export-section__header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.ch-export-section__label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

/* ─── Format selector grid ────────────────────────────────────────────────── */
.ch-export-format-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  /* 4 equal columns */
  gap: var(--ch-space-2);
}

.ch-export-format-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-1);
  padding: var(--ch-space-3) var(--ch-space-2);
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  text-align: center;
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-format-card:hover {
  border-color: var(--ch-color-border-strong);
  background-color: var(--ch-color-bg-muted);
}

.ch-export-format-card--active {
  border-color: var(--ch-color-primary);
  background-color: var(--ch-color-primary-subtle);
  box-shadow: 0 0 0 3px var(--ch-color-primary-muted);
}

.ch-export-format-card:active {
  transform: scale(0.97);
}

.ch-export-format-card__icon {
  color: var(--ch-color-text-muted);
  transition: color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-format-card--active .ch-export-format-card__icon {
  color: var(--ch-color-primary);
}

.ch-export-format-card__name {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.ch-export-format-card__ext {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
  font-family: var(--ch-font-mono);
}

.ch-export-format-card__desc {
  font-size: 0.6875rem;
  /* 11px */
  color: var(--ch-color-text-subtle);
  line-height: var(--ch-leading-snug);
}

/* Active checkmark badge — absolute top-right */
.ch-export-format-card__check {
  position: absolute;
  top: var(--ch-space-2);
  right: var(--ch-space-2);
  display: flex;
}

/* ─── Column checkboxes ───────────────────────────────────────────────────── */
.ch-export-columns-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: var(--ch-space-2);
}

.ch-export-col-checkbox {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  padding: var(--ch-space-2) var(--ch-space-3);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  background: var(--ch-color-bg-subtle);
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-col-checkbox:hover {
  border-color: var(--ch-color-border-strong);
}

.ch-export-col-checkbox--checked {
  border-color: var(--ch-color-primary-muted);
  background-color: var(--ch-color-primary-subtle);
}

.ch-export-col-checkbox input[type='checkbox'] {
  accent-color: var(--ch-color-primary);
  flex-shrink: 0;
  width: 14px;
  height: 14px;
  cursor: pointer;
}

.ch-export-col-checkbox__label {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
  line-height: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ─── Scope selector ──────────────────────────────────────────────────────── */
.ch-export-scope-options {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-2);
}

.ch-export-radio {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  padding: var(--ch-space-3) var(--ch-space-4);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-radio input[type='radio'] {
  accent-color: var(--ch-color-primary);
  flex-shrink: 0;
}

.ch-export-radio--active {
  border-color: var(--ch-color-primary);
  background-color: var(--ch-color-primary-subtle);
}

.ch-export-radio__content {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-0_5);
}

.ch-export-radio__title {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.ch-export-radio__hint {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* ─── Filename row ────────────────────────────────────────────────────────── */
.ch-export-filename-row {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.ch-export-filename-ext {
  font-size: var(--ch-text-sm);
  font-family: var(--ch-font-mono);
  color: var(--ch-color-text-muted);
  white-space: nowrap;
}

/* ─── Input ───────────────────────────────────────────────────────────────── */
.ch-export-input {
  flex: 1;
  width: 100%;
  padding: var(--ch-space-2) var(--ch-space-3_5);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  font-family: var(--ch-font-sans);
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
  outline: none;
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-input:focus {
  border-color: var(--ch-color-border-focus);
  box-shadow: 0 0 0 3px var(--ch-color-primary-muted);
}

/* ─── Document settings ───────────────────────────────────────────────────── */
.ch-export-field {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1_5);
}

.ch-export-field + .ch-export-field {
  margin-top: var(--ch-space-3);
}

.ch-export-field__label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text-muted);
}

.ch-export-field__optional {
  font-weight: var(--ch-font-normal);
  color: var(--ch-color-text-subtle);
  font-size: var(--ch-text-xs);
  margin-left: var(--ch-space-1);
}

.ch-export-two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-4);
  margin-top: var(--ch-space-3);
}

.ch-export-select {
  width: 100%;
  padding: var(--ch-space-2) var(--ch-space-3_5);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  font-family: var(--ch-font-sans);
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
  cursor: pointer;
  outline: none;
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    box-shadow var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-select:focus {
  border-color: var(--ch-color-border-focus);
  box-shadow: 0 0 0 3px var(--ch-color-primary-muted);
}

/* ─── Orientation picker ──────────────────────────────────────────────────── */
.ch-export-orientation {
  display: flex;
  gap: var(--ch-space-3);
}

.ch-export-orientation__opt {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-1);
  cursor: pointer;
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  transition: color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-orientation__opt input {
  display: none;
}

/* hide native radio, use SVG */

.ch-export-orientation__opt--active {
  color: var(--ch-color-primary);
  font-weight: var(--ch-font-medium);
}

/* ─── Footer ──────────────────────────────────────────────────────────────── */
.ch-export-dialog__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--ch-space-4) var(--ch-space-6);
  border-top: 1px solid var(--ch-color-border);
  background: var(--ch-color-bg-subtle);
  flex-shrink: 0;
  gap: var(--ch-space-3);
  flex-wrap: wrap;
}

/* Summary pill */
.ch-export-summary {
  display: inline-flex;
  align-items: center;
  gap: var(--ch-space-1_5);
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  background: var(--ch-color-bg-muted);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-sm);
  padding: var(--ch-space-1) var(--ch-space-3);
}

.ch-export-dialog__actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

/* ─── Action buttons ──────────────────────────────────────────────────────── */
.ch-export-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--ch-space-1_5);
  border-radius: var(--ch-radius-sm);
  font-family: var(--ch-font-sans);
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  padding: var(--ch-space-2) var(--ch-space-4);
  min-height: 36px;
  cursor: pointer;
  border: 1px solid transparent;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-btn:active:not(:disabled) {
  transform: scale(0.98);
}

.ch-export-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.ch-export-btn--ghost {
  background: var(--ch-color-bg-muted);
  border-color: var(--ch-color-border);
  color: var(--ch-color-text);
}

.ch-export-btn--ghost:hover:not(:disabled) {
  background: var(--ch-color-bg-subtle);
  border-color: var(--ch-color-border-strong);
}

.ch-export-btn--primary {
  background: var(--ch-color-primary);
  border-color: var(--ch-color-primary);
  color: var(--ch-color-primary-fg);
  box-shadow: 0 1px 2px rgb(0 0 0 / 0.12);
}

.ch-export-btn--primary:hover:not(:disabled) {
  background: var(--ch-color-primary-hover);
  border-color: var(--ch-color-primary-hover);
}

/* ─── Link button (Select all) ────────────────────────────────────────────── */
.ch-export-link-btn {
  background: none;
  border: none;
  padding: 0;
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-primary);
  cursor: pointer;
  transition: color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-export-link-btn:hover {
  color: var(--ch-color-primary-hover);
}

/* ─── Transitions ─────────────────────────────────────────────────────────── */
.ch-dialog-fade-enter-active,
.ch-dialog-fade-leave-active {
  transition: opacity var(--ch-duration-normal) var(--ch-ease-out);
}

.ch-dialog-fade-enter-from,
.ch-dialog-fade-leave-to {
  opacity: 0;
}

.ch-dialog-scale-enter-active {
  transition:
    opacity var(--ch-duration-normal) var(--ch-ease-out),
    transform var(--ch-duration-normal) var(--ch-ease-spring);
}

.ch-dialog-scale-leave-active {
  transition:
    opacity var(--ch-duration-fast) var(--ch-ease-in),
    transform var(--ch-duration-fast) var(--ch-ease-in);
}

.ch-dialog-scale-enter-from,
.ch-dialog-scale-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(8px);
}
</style>
