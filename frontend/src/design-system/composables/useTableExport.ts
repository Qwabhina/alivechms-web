/**
 * @file useTableExport.ts
 * @path /frontend/src/design-system/composables/useTableExport.ts
 * @description Composable that handles all table export and print operations.
 *
 * ─── Supported formats ───────────────────────────────────────────────────────
 * | Format  | Dependency        | Notes                                        |
 * |---------|-------------------|----------------------------------------------|
 * | CSV     | none              | Pure JS, always available                    |
 * | Excel   | exceljs           | npm install exceljs                          |
 * | PDF     | jspdf             | npm install jspdf jspdf-autotable            |
 * |         | jspdf-autotable   |                                              |
 * | Print   | none              | Opens browser print dialog via window.print()|
 *
 * ─── Graceful degradation ────────────────────────────────────────────────────
 * Excel and PDF imports are done dynamically (`await import(...)`) so the
 * app still loads even if the packages aren't installed. If an import fails,
 * the function returns an error result the caller can display to the user.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example
 * const { exportData, isExporting, exportError } = useTableExport()
 *
 * await exportData({
 *   format:   'csv',
 *   rows:     memberRows,
 *   columns:  [{ key: 'name', label: 'Full Name' }, { key: 'status', label: 'Status' }],
 *   filename: 'members-2024',
 * })
 */

import { ref } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/** Supported export format identifiers */
export type ExportFormat = 'csv' | 'excel' | 'pdf' | 'print'

/** Paper size options for PDF and Print */
export type PaperSize = 'a4' | 'a3' | 'letter' | 'legal'

/** Page orientation for PDF and Print */
export type Orientation = 'portrait' | 'landscape'

/**
 * A stripped-down column descriptor used by the export functions.
 * Decoupled from ColumnDef<T> so this composable works independently
 * of the ChTable component's generics.
 */
export interface ExportColumn {
  /** The key used to read the value from a row object */
  key:   string
  /** The column header label written to the export */
  label: string
}

/** Complete configuration passed to `exportData()` */
export interface ExportConfig {
  /** Target output format */
  format:      ExportFormat

  /** The data rows to export — plain objects */
  rows:        Record<string, unknown>[]

  /** Which columns to include and in what order */
  columns:     ExportColumn[]

  /**
   * Base filename for the download (no extension — added automatically).
   * e.g. 'members-report' → 'members-report.csv'
   * Default: 'export'
   */
  filename?:   string

  // ── PDF / Print specific ──────────────────────────────────────────────────

  /** Title printed at the top of the PDF or print page. Default: filename */
  title?:      string

  /** Paper size for PDF and Print. Default: 'a4' */
  paperSize?:  PaperSize

  /** Page orientation. Default: 'portrait' */
  orientation?: Orientation

  /**
   * Optional subtitle line (e.g. "As of 15 July 2025" or church name).
   * Printed below the title in PDF and Print outputs.
   */
  subtitle?:   string
}

/** Return value of exportData() — indicates success or failure */
export interface ExportResult {
  success: boolean
  error?:  string
}

// ─── Utility helpers ──────────────────────────────────────────────────────────

/**
 * Reads a cell value from a row using dot-notation for nested keys.
 * e.g. key = 'family.name' → row.family.name
 * Returns a display-safe string (never null/undefined).
 */
function getCellString(row: Record<string, unknown>, key: string): string {
  const value = key.split('.').reduce((obj: unknown, k) => {
    if (obj && typeof obj === 'object') return (obj as Record<string, unknown>)[k]
    return undefined
  }, row)

  if (value === null || value === undefined) return ''
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

/**
 * Triggers a file download in the browser without a server round-trip.
 * Creates a temporary anchor element, clicks it, then removes it.
 *
 * @param blob     - The file content as a Blob
 * @param filename - The filename the browser should save as
 */
function downloadBlob(blob: Blob, filename: string): void {
  const url  = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href     = url
  link.download = filename
  // Append to body (required in Firefox) then click programmatically
  document.body.appendChild(link)
  link.click()
  // Clean up: remove element and revoke the object URL to free memory
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

/**
 * Formats a Date as a locale-aware string for use in export titles/subtitles.
 */
function formatDate(date: Date): string {
  return date.toLocaleDateString(undefined, {
    year:  'numeric',
    month: 'long',
    day:   'numeric',
  })
}

// ─── Export functions ─────────────────────────────────────────────────────────

/**
 * Exports data as a CSV file.
 *
 * ─── CSV format rules ───────────────────────────────────────────────────────
 * - Values containing commas, double quotes, or newlines are wrapped in quotes
 * - Embedded double quotes are escaped by doubling them: `"` → `""`
 * - Header row is always first
 * - UTF-8 BOM (`\uFEFF`) is prepended so Excel opens the file correctly
 *   without garbling special characters (accented names, GH₵ symbol, etc.)
 */
function exportCSV(config: ExportConfig): ExportResult {
  try {
    const { rows, columns, filename = 'export' } = config

    // BOM: tells Excel this is UTF-8 encoded
    const BOM = '\uFEFF'

    /**
     * Escapes a cell value for CSV:
     * - Wrap in quotes if value contains comma, newline, or double-quote
     * - Double any existing double quotes inside the value
     */
    function escapeCSV(val: string): string {
      if (/[",\n\r]/.test(val)) {
        return `"${val.replace(/"/g, '""')}"`
      }
      return val
    }

    // Build the header row
    const header = columns.map(col => escapeCSV(col.label)).join(',')

    // Build one CSV line per row
    const dataRows = rows.map(row =>
      columns.map(col => escapeCSV(getCellString(row, col.key))).join(',')
    )

    // Join everything with CRLF (Windows line endings — more compatible)
    const csv  = BOM + [header, ...dataRows].join('\r\n')
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })

    downloadBlob(blob, `${filename}.csv`)
    return { success: true }
  } catch (e) {
    return { success: false, error: `CSV export failed: ${(e as Error).message}` }
  }
}

/**
 * Exports data as an Excel (.xlsx) file using ExcelJS.
 *
 * ─── Why ExcelJS over SheetJS ───────────────────────────────────────────────
 * ExcelJS is actively maintained and has a clean, promise-based API.
 * Unlike SheetJS (xlsx), it supports cell styling natively — no separate
 * style fork required. This lets us produce a properly branded output:
 * bold header row with the primary color background, alternating row shading,
 * and auto-sized columns, all in one library.
 *
 * ─── ExcelJS API notes ──────────────────────────────────────────────────────
 * - `workbook.addWorksheet()` creates the sheet
 * - `worksheet.columns` defines keys + widths in one step
 * - `worksheet.addRow()` adds data rows
 * - `worksheet.getRow(1)` targets the header for styling
 * - `workbook.xlsx.writeBuffer()` returns a Promise<ArrayBuffer>
 *
 * Column widths are auto-calculated from the longest value per column,
 * capped at 60 characters to prevent absurdly wide columns.
 */
async function exportExcel(config: ExportConfig): Promise<ExportResult> {
  try {
    const { rows, columns, filename = 'export', title } = config

    // Dynamic import — only loaded when Excel export is actually triggered
    const ExcelJS = await import('exceljs').catch(() => null)
    if (!ExcelJS) {
      return {
        success: false,
        error: 'Excel export requires the "exceljs" package. Run: npm install exceljs',
      }
    }

    const workbook = new ExcelJS.Workbook()
    workbook.creator = 'Church Management System'
    workbook.created = new Date()

    const worksheet = workbook.addWorksheet('Data')

    // ── Column definitions ────────────────────────────────────────────────────
    // Auto-calculate width: max char length across header + all cell values,
    // capped at 60 to avoid absurdly wide columns. +4 for cell padding.
    worksheet.columns = columns.map(col => {
      const maxLen = Math.min(
        60,
        Math.max(
          col.label.length,
          ...rows.map(row => getCellString(row, col.key).length)
        )
      )
      return {
        header: col.label,
        key: col.key,
        width: maxLen + 4,
      }
    })

    // ── Data rows ─────────────────────────────────────────────────────────────
    rows.forEach(row => {
      worksheet.addRow(
        Object.fromEntries(columns.map(col => [col.key, getCellString(row, col.key)]))
      )
    })

    // ── Style the header row (row 1) ──────────────────────────────────────────
    // Brand-colored background (#4f46e5 = --ch-color-primary default),
    // white bold text, border bottom to separate from data rows.
    const headerRow = worksheet.getRow(1)
    headerRow.eachCell(cell => {
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FF4F46E5' }, // #4f46e5 with full opacity prefix
      }
      cell.font = {
        bold: true,
        color: { argb: 'FFFFFFFF' },
        size: 10,
      }
      cell.alignment = { vertical: 'middle' }
      cell.border = {
        bottom: { style: 'thin', color: { argb: 'FF3730A3' } },
      }
    })
    headerRow.height = 22

    // ── Style data rows — alternating row shading ─────────────────────────────
    // Light grey fill on even rows for readability in large datasets.
    worksheet.eachRow((row, rowNumber) => {
      if (rowNumber === 1) return // skip header, already styled
      row.eachCell(cell => {
        if (rowNumber % 2 === 0) {
          cell.fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFF8FAFC' }, // --ch-color-bg-subtle equivalent
          }
        }
        cell.alignment = { vertical: 'middle', wrapText: false }
        cell.font = { size: 10 }
      })
      row.height = 18
    })

    // ── Write to buffer and trigger download ──────────────────────────────────
    const buffer = await workbook.xlsx.writeBuffer()
    const blob   = new Blob([buffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })

    downloadBlob(blob, `${filename}.xlsx`)
    return { success: true }
  } catch (e) {
    return { success: false, error: `Excel export failed: ${(e as Error).message}` }
  }
}

/**
 * Exports data as a PDF file using jsPDF + jsPDF-AutoTable.
 *
 * ─── jsPDF notes ────────────────────────────────────────────────────────────
 * `jspdf-autotable` is a plugin that adds `doc.autoTable()` to the jsPDF
 * instance. It handles multi-page tables, column widths, header repetition,
 * and cell overflow automatically.
 *
 * We use the system's sans-serif font (helvetica) since embedding custom
 * fonts in jsPDF requires base64 encoding the font files — out of scope here.
 */
async function exportPDF(config: ExportConfig): Promise<ExportResult> {
  try {
    const {
      rows,
      columns,
      filename    = 'export',
      title       = filename,
      subtitle,
      paperSize   = 'a4',
      orientation = 'portrait',
    } = config

    // Dynamic import of jsPDF
    const jsPDFModule = await import('jspdf').catch(() => null)
    if (!jsPDFModule) {
      return {
        success: false,
        error:   'PDF export requires the "jspdf" package. Run: npm install jspdf jspdf-autotable',
      }
    }

    // Dynamic import of autotable plugin
    // The plugin mutates the jsPDF prototype, so we just need to import it
    await import('jspdf-autotable').catch(() => {
      // If jspdf-autotable isn't installed, we proceed without it and fall back
    })

    const { jsPDF } = jsPDFModule

    // Map our PaperSize type to jsPDF's format string
    const formatMap: Record<PaperSize, string> = {
      a4:     'a4',
      a3:     'a3',
      letter: 'letter',
      legal:  'legal',
    }

    // Create the PDF document
    const doc = new jsPDF({
      orientation: orientation === 'landscape' ? 'l' : 'p',
      unit:        'mm',
      format:      formatMap[paperSize],
    })

    // ── Title block ──────────────────────────────────────────────────────────
    const pageWidth  = doc.internal.pageSize.getWidth()
    const marginLeft = 14  // 14mm left margin (jsPDF default)

    // Title — bold, 16pt
    doc.setFontSize(16)
    doc.setFont('helvetica', 'bold')
    doc.text(title, marginLeft, 20)

    let yOffset = 28 // current Y position in mm after the title

    // Subtitle — normal weight, 10pt, muted
    if (subtitle) {
      doc.setFontSize(10)
      doc.setFont('helvetica', 'normal')
      doc.setTextColor(100, 100, 100) // gray
      doc.text(subtitle, marginLeft, yOffset)
      yOffset += 7
    }

    // Auto-generated date line
    doc.setFontSize(9)
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(150, 150, 150)
    doc.text(`Generated: ${formatDate(new Date())}`, marginLeft, yOffset)
    yOffset += 8

    // Reset text color to black for the table
    doc.setTextColor(0, 0, 0)

    // ── Table ────────────────────────────────────────────────────────────────
    // Check if autoTable plugin was successfully loaded
    type DocWithAutoTable = typeof doc & {
      autoTable: (options: Record<string, unknown>) => void
    }
    const docWithTable = doc as DocWithAutoTable

    if (typeof docWithTable.autoTable === 'function') {
      docWithTable.autoTable({
        startY:      yOffset,
        head:        [columns.map(col => col.label)],
        body:        rows.map(row => columns.map(col => getCellString(row, col.key))),
        styles: {
          font:      'helvetica',
          fontSize:  9,
          cellPadding: 3,
        },
        headStyles: {
          fillColor:   [79, 70, 229], // --ch-color-primary default (indigo-600)
          textColor:   [255, 255, 255],
          fontStyle:   'bold',
          fontSize:    9,
        },
        alternateRowStyles: {
          fillColor: [248, 250, 252], // --ch-color-bg-subtle default
        },
        // Repeat header on each page
        showHead:    'everyPage',
        // Page number footer
        didDrawPage: (data: { pageNumber: number; pageCount: number }) => {
          const pageStr = `Page ${data.pageNumber}`
          doc.setFontSize(8)
          doc.setTextColor(150, 150, 150)
          doc.text(
            pageStr,
            pageWidth - marginLeft,
            doc.internal.pageSize.getHeight() - 8,
            { align: 'right' }
          )
        },
      })
    } else {
      // Fallback: render a simple manual table if autoTable plugin isn't loaded
      doc.setFontSize(9)
      let y = yOffset
      // Headers
      doc.setFont('helvetica', 'bold')
      columns.forEach((col, i) => doc.text(col.label, marginLeft + i * 40, y))
      y += 7
      // Rows
      doc.setFont('helvetica', 'normal')
      rows.forEach(row => {
        columns.forEach((col, i) => {
          doc.text(
            getCellString(row, col.key).substring(0, 20), // truncate for fallback
            marginLeft + i * 40,
            y
          )
        })
        y += 6
        if (y > 270) { doc.addPage(); y = 20 } // manual page break
      })
    }

    // Save the PDF and trigger the browser download
    doc.save(`${filename}.pdf`)
    return { success: true }
  } catch (e) {
    return { success: false, error: `PDF export failed: ${(e as Error).message}` }
  }
}

/**
 * Opens the browser's native print dialog with the table rendered in a
 * clean, printable HTML page injected into a hidden iframe.
 *
 * ─── Why an iframe? ──────────────────────────────────────────────────────────
 * Using `window.print()` directly would print the entire current app page.
 * By creating a hidden iframe, loading our table HTML into it, and calling
 * `iframe.contentWindow.print()`, only the table is printed.
 * The iframe is removed after the print dialog closes.
 *
 * ─── Why not @media print CSS? ───────────────────────────────────────────────
 * @media print on the main page would require complex CSS to hide the entire
 * app shell. The iframe approach is self-contained and framework-agnostic.
 */
function printTable(config: ExportConfig): ExportResult {
  try {
    const {
      rows,
      columns,
      title       = 'Report',
      subtitle,
      orientation = 'portrait',
      paperSize   = 'a4',
    } = config

    // ── Build the print HTML document ────────────────────────────────────────
    const tableRows = rows.map(row => `
      <tr>
        ${columns.map(col => `<td>${getCellString(row, col.key)}</td>`).join('')}
      </tr>
    `).join('')

    const htmlContent = `
      <!DOCTYPE html>
      <html lang="en">
      <head>
        <meta charset="UTF-8" />
        <title>${title}</title>
        <style>
          /* ── Page setup ── */
          @page {
            size: ${paperSize} ${orientation};
            margin: 20mm 15mm;
          }

          /* ── Reset ── */
          *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

          body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size:   10pt;
            color:       #0f172a;
            background:  white;
          }

          /* ── Title block ── */
          .print-title {
            font-size:     18pt;
            font-weight:   700;
            margin-bottom: 4pt;
            color:         #0f172a;
          }

          .print-subtitle {
            font-size:   10pt;
            color:       #64748b;
            margin-bottom: 2pt;
          }

          .print-date {
            font-size:   8pt;
            color:       #94a3b8;
            margin-bottom: 16pt;
          }

          /* ── Table ── */
          table {
            width:           100%;
            border-collapse: collapse;
            font-size:       9pt;
          }

          thead th {
            background-color: #4f46e5;  /* primary color */
            color:            white;
            font-weight:      600;
            padding:          7pt 8pt;
            text-align:       left;
            font-size:        8pt;
            letter-spacing:   0.03em;
            text-transform:   uppercase;
          }

          tbody tr:nth-child(even) {
            background-color: #f8fafc;  /* bg-subtle */
          }

          tbody tr:nth-child(odd) {
            background-color: #ffffff;
          }

          td {
            padding:      5pt 8pt;
            border-bottom:1px solid #e2e8f0;
            color:        #1e293b;
          }

          /* ── Footer ── */
          .print-footer {
            margin-top: 12pt;
            font-size:  8pt;
            color:      #94a3b8;
            display:    flex;
            justify-content: space-between;
          }

          /* Avoid splitting rows across pages */
          tr { page-break-inside: avoid; }

          /* Keep thead on every page */
          thead { display: table-header-group; }
        </style>
      </head>
      <body>
        <div class="print-title">${title}</div>
        ${subtitle ? `<div class="print-subtitle">${subtitle}</div>` : ''}
        <div class="print-date">Generated: ${formatDate(new Date())}</div>

        <table>
          <thead>
            <tr>
              ${columns.map(col => `<th>${col.label}</th>`).join('')}
            </tr>
          </thead>
          <tbody>
            ${tableRows}
          </tbody>
        </table>

        <div class="print-footer">
          <span>${title} · ${formatDate(new Date())}</span>
          <span>${rows.length} records</span>
        </div>
      </body>
      </html>
    `

    // ── Create hidden iframe and inject the HTML ──────────────────────────────
    const iframe = document.createElement('iframe')

    // Position the iframe off-screen (not display:none — some browsers
    // won't print an invisible iframe's content)
    iframe.style.cssText = `
      position: fixed;
      top: -9999px;
      left: -9999px;
      width: 1px;
      height: 1px;
      opacity: 0;
      border: none;
    `

    document.body.appendChild(iframe)

    const iframeDoc = iframe.contentDocument || iframe.contentWindow?.document
    if (!iframeDoc) throw new Error('Could not access iframe document')

    iframeDoc.open()
    iframeDoc.write(htmlContent)
    iframeDoc.close()

    // Wait for iframe content to load before printing
    iframe.onload = () => {
      iframe.contentWindow?.focus() // required in some browsers
      iframe.contentWindow?.print()

      // Remove the iframe after the print dialog is dismissed
      // setTimeout gives the browser time to queue the print job first
      setTimeout(() => {
        if (document.body.contains(iframe)) {
          document.body.removeChild(iframe)
        }
      }, 1000)
    }

    return { success: true }
  } catch (e) {
    return { success: false, error: `Print failed: ${(e as Error).message}` }
  }
}

// ─── Composable ──────────────────────────────────────────────────────────────

/**
 * Returns export functions and reactive state for use in components.
 */
export function useTableExport() {
  /**
   * True while an async export (Excel, PDF) is in progress.
   * Use to show a loading indicator on the export button.
   */
  const isExporting = ref(false)

  /** Holds the error message from the most recent failed export, or null */
  const exportError = ref<string | null>(null)

  /**
   * The main export dispatcher. Routes to the correct format handler
   * based on `config.format`.
   *
   * @param config - Full export configuration
   * @returns ExportResult with success flag and optional error message
   */
  async function exportData(config: ExportConfig): Promise<ExportResult> {
    isExporting.value  = true
    exportError.value  = null

    let result: ExportResult

    try {
      switch (config.format) {
        case 'csv':
          result = exportCSV(config)
          break
        case 'excel':
          result = await exportExcel(config)
          break
        case 'pdf':
          result = await exportPDF(config)
          break
        case 'print':
          result = printTable(config)
          break
        default:
          result = { success: false, error: `Unknown format: ${config.format}` }
      }
    } catch (e) {
      result = { success: false, error: (e as Error).message }
    } finally {
      // Always clear loading state, even on error
      isExporting.value = false
    }

    if (!result.success && result.error) {
      exportError.value = result.error
    }

    return result
  }

  return {
    exportData,
    isExporting,
    exportError,
  }
}
