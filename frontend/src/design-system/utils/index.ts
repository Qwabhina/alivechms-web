/**
 * @file utils/index.ts
 * @path /frontend/src/design-system/utils/index.ts
 * @description Utility functions for the design system and consuming application.
 *
 * Functions are organised by category. All are pure (no side effects) except
 * the DOM helpers, which are clearly marked.
 */

// ─── File Type Constants ───────────────────────────────────────────────────────
/**
 * MIME type constants for use with ChFileUpload's `accept` prop.
 * Import individual types or combine them using ACCEPT_PRESETS below.
 *
 * @example Single type
 * <ChFileUpload :accept="MIME.PDF" />
 *
 * @example Combined via preset
 * <ChFileUpload :accept="ACCEPT_PRESETS.documents" />
 *
 * @example Custom combination
 * <ChFileUpload :accept="[MIME.PDF, MIME.JPEG, MIME.PNG].join(',')" />
 */
export const MIME = {
   // Documents
   PDF: 'application/pdf',
   DOC: 'application/msword',
   DOCX: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
   XLS: 'application/vnd.ms-excel',
   XLSX: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
   TXT: 'text/plain',

   // Images
   JPEG: 'image/jpeg',
   PNG: 'image/png',
   GIF: 'image/gif',
} as const

/**
 * Pre-built `accept` strings for common upload scenarios.
 * Pass directly to ChFileUpload's `accept` prop.
 *
 * @example
 * <ChFileUpload :accept="ACCEPT_PRESETS.images" label="Member photo" />
 * <ChFileUpload :accept="ACCEPT_PRESETS.documents" label="Supporting document" />
 */
export const ACCEPT_PRESETS = {
   /** PDF, Word (.doc / .docx), plain text */
   documents: [MIME.PDF, MIME.DOC, MIME.DOCX, MIME.TXT].join(','),

   /** Excel (.xls / .xlsx) */
   spreadsheets: [MIME.XLS, MIME.XLSX].join(','),

   /** JPEG, PNG, GIF */
   images: [MIME.JPEG, MIME.PNG, MIME.GIF].join(','),

   /** All of the above — useful for general-purpose attachment fields */
   all: Object.values(MIME).join(','),
} as const

// ─── String Utilities ──────────────────────────────────────────────────────────

/**
 * Truncates a string to a maximum length and appends an ellipsis.
 *
 * @example
 * truncate('This is a long string', 10) // 'This is...'
 */
export function truncate(str: string, maxLength: number): string {
   if (str.length <= maxLength) return str
   return str.slice(0, maxLength - 3) + '...'
}

/**
 * Capitalises the first letter of a string.
 *
 * @example
 * capitalize('hello world') // 'Hello world'
 */
export function capitalize(str: string): string {
   if (!str) return str
   return str.charAt(0).toUpperCase() + str.slice(1)
}

/**
 * Converts a string to kebab-case.
 *
 * @example
 * toKebabCase('HelloWorld')  // 'hello-world'
 * toKebabCase('Hello World') // 'hello-world'
 */
export function toKebabCase(str: string): string {
   return str
      .replace(/([a-z])([A-Z])/g, '$1-$2')
      .replace(/\s+/g, '-')
      .toLowerCase()
}

// ─── Number Utilities ──────────────────────────────────────────────────────────

/**
 * Formats a number as a currency string.
 *
 * Defaults to GHS (Ghanaian Cedi) and the `en-GH` locale — the standard
 * for this application. Pass explicit arguments to override for other contexts.
 *
 * @param amount   - The number to format
 * @param currency - ISO 4217 currency code. Default: 'GHS'
 * @param locale   - BCP 47 locale tag. Default: 'en-GH'
 *
 * @example
 * formatCurrency(1234.56)          // 'GH₵1,234.56'
 * formatCurrency(1234.56, 'USD')   // '$1,234.56'
 * formatCurrency(1234.56, 'EUR', 'de-DE') // '1.234,56 €'
 */
export function formatCurrency(
   amount: number,
   currency: string = 'GHS',
   locale: string = 'en-GH',
): string {
   return new Intl.NumberFormat(locale, { style: 'currency', currency }).format(amount)
}

/**
 * Formats a number as a percentage string.
 *
 * @param value         - A fraction between 0 and 1
 * @param decimalPlaces - Decimal places to include. Default: 0
 *
 * @example
 * formatPercentage(0.1234)    // '12%'
 * formatPercentage(0.1234, 2) // '12.34%'
 */
export function formatPercentage(value: number, decimalPlaces: number = 0): string {
   return `${(value * 100).toFixed(decimalPlaces)}%`
}

/**
 * Formats a number as a compact string (1.2k, 1.5M, etc.).
 *
 * @param number        - The number to format
 * @param decimalPlaces - Decimal places to include. Default: 1
 * @param locale        - BCP 47 locale tag. Default: 'en-GH'
 *
 * @example
 * formatCompactNumber(1234)    // '1.2K'
 * formatCompactNumber(1234567) // '1.2M'
 */
export function formatCompactNumber(
   number: number,
   decimalPlaces: number = 1,
   locale: string = 'en-GH',
): string {
   return new Intl.NumberFormat(locale, {
     notation: 'compact',
     maximumFractionDigits: decimalPlaces,
  }).format(number)
}

// ─── Date Utilities ────────────────────────────────────────────────────────────

/**
 * Formats a date using a simple token-based format string.
 *
 * Supported tokens: YYYY MM DD HH mm ss
 *
 * @example
 * formatDate(new Date(2025, 6, 14), 'DD/MM/YYYY') // '14/07/2025'
 * formatDate(new Date(2025, 6, 14, 9, 5), 'YYYY-MM-DD HH:mm') // '2025-07-14 09:05'
 */
export function formatDate(date: Date | string | number, format: string): string {
   const d = new Date(date)

   const tokens: Record<string, string> = {
      YYYY: d.getFullYear().toString(),
     MM: String(d.getMonth() + 1).padStart(2, '0'),
     DD: String(d.getDate()).padStart(2, '0'),
     HH: String(d.getHours()).padStart(2, '0'),
     mm: String(d.getMinutes()).padStart(2, '0'),
     ss: String(d.getSeconds()).padStart(2, '0'),
  }

   return format.replace(/YYYY|MM|DD|HH|mm|ss/g, match => tokens[match] ?? match)
}

/**
 * Formats a date as a human-readable relative time string.
 * Handles both past dates ("2 hours ago") and future dates ("in 3 days").
 *
 * @example
 * formatRelativeTime(Date.now() - 3_600_000) // '1 hour ago'
 * formatRelativeTime(Date.now() + 86_400_000) // 'in 1 day'
 */
export function formatRelativeTime(date: Date | string | number): string {
   const diff = Date.now() - new Date(date).getTime() // positive = past, negative = future

   const MINUTE = 60_000
   const HOUR = 60 * MINUTE
   const DAY = 24 * HOUR
   const WEEK = 7 * DAY
   const MONTH = 30 * DAY
   const YEAR = 365 * DAY

   const abs = Math.abs(diff)
   const past = diff >= 0

   function unit(n: number, singular: string, plural: string): string {
      const label = n === 1 ? singular : plural
      return past ? `${n} ${label} ago` : `in ${n} ${label}`
   }

   if (abs < MINUTE) return 'just now'
   if (abs < HOUR) return unit(Math.floor(abs / MINUTE), 'minute', 'minutes')
   if (abs < DAY) return unit(Math.floor(abs / HOUR), 'hour', 'hours')
   if (abs < WEEK) return unit(Math.floor(abs / DAY), 'day', 'days')
   if (abs < MONTH) return unit(Math.floor(abs / WEEK), 'week', 'weeks')
   if (abs < YEAR) return unit(Math.floor(abs / MONTH), 'month', 'months')
   return unit(Math.floor(abs / YEAR), 'year', 'years')
}

// ─── Array Utilities ───────────────────────────────────────────────────────────

/**
 * Groups an array of objects by a property value.
 *
 * @example
 * groupBy(members, 'status')
 * // { Active: [...], Inactive: [...] }
 */
export function groupBy<T extends Record<string, unknown>>(
   array: T[],
   key: keyof T,
): Record<string, T[]> {
   return array.reduce<Record<string, T[]>>((acc, item) => {
      const group = String(item[key])
        ; (acc[group] ??= []).push(item)
     return acc
  }, {})
}

/**
 * Returns a new shuffled copy of an array using the Fisher-Yates algorithm.
 * Does not mutate the original.
 */
export function shuffle<T>(array: T[]): T[] {
   // Spread without cast — TypeScript already infers T[] from T[]
   const result = [...array]
   for (let i = result.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1))
         ;[result[i], result[j]] = [result[j], result[i]] as [T, T]
   }
   return result
}

// ─── DOM Utilities ────────────────────────────────────────────────────────────

/**
 * Returns true if the element's bounding box is fully within the viewport.
 */
export function isInViewport(element: HTMLElement): boolean {
   const { top, left, bottom, right } = element.getBoundingClientRect()
   return (
     top >= 0 &&
     left >= 0 &&
     bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
     right <= (window.innerWidth || document.documentElement.clientWidth)
  )
}

/**
 * Scrolls the element into view. Thin wrapper around `scrollIntoView`
 * with sensible defaults.
 */
export function scrollToElement(
   element: HTMLElement,
   behavior: ScrollBehavior = 'smooth',
   block: ScrollLogicalPosition = 'start',
   inline: ScrollLogicalPosition = 'nearest',
): void {
   element.scrollIntoView({ behavior, block, inline })
}

// ─── Validation Utilities ─────────────────────────────────────────────────────

/**
 * Returns true if the string is a structurally valid email address.
 * Does not verify deliverability — use server-side verification for that.
 */
export function isValidEmail(email: string): boolean {
   return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

/**
 * Returns true if the string looks like a valid phone number.
 * Accepts optional leading +, 10–14 digits, and ignores spaces/dashes/parens.
 *
 * Ghanaian mobile numbers are 10 digits (e.g. 0244123456) or 12 with
 * country code (+233244123456). Both pass this check.
 */
export function isValidPhone(phone: string): boolean {
   return /^\+?[0-9]{10,14}$/.test(phone.replace(/[\s()\-]/g, ''))
}

/**
 * Returns true if the string is a valid absolute URL.
 * Uses the URL constructor — no regex, handles edge cases correctly.
 */
export function isValidUrl(url: string): boolean {
   try { new URL(url); return true } catch { return false }
}

// ─── Color Utilities ──────────────────────────────────────────────────────────

/**
 * Parses a hex color string into `{ r, g, b }`.
 * Returns null for invalid input.
 *
 * @example
 * hexToRgb('#4f46e5') // { r: 79, g: 70, b: 229 }
 */
export function hexToRgb(hex: string): { r: number; g: number; b: number } | null {
   const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
   return m ? {
      r: parseInt(m[1]!, 16),
      g: parseInt(m[2]!, 16),
      b: parseInt(m[3]!, 16),
   } : null
}

/**
 * Converts r/g/b values to a lowercase hex string.
 *
 * @example
 * rgbToHex(79, 70, 229) // '#4f46e5'
 */
export function rgbToHex(r: number, g: number, b: number): string {
   return '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('')
}

/**
 * Returns true if the color is perceptually light (luminance > 0.5).
 * Useful for deciding whether to place dark or light text on a colored background.
 *
 * @example
 * isLightColor('#ffffff') // true
 * isLightColor('#4f46e5') // false  → use white text
 */
export function isLightColor(color: string | { r: number; g: number; b: number }): boolean {
   const rgb = typeof color === 'string' ? hexToRgb(color) : color
   if (!rgb) return true // default to light on invalid input
   // ITU-R BT.601 luma coefficients
   return (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255 > 0.5
}

// ─── Function Utilities ───────────────────────────────────────────────────────

/**
 * Returns a debounced version of `func` that delays invoking it until
 * `delay` ms have elapsed since the last call.
 *
 * Uses `ReturnType<typeof setTimeout>` (not `number`) so this works in
 * both browser and Node/SSR environments without `window.setTimeout`.
 *
 * @example
 * const debouncedSearch = debounce(fetchResults, 300)
 * searchInput.addEventListener('input', debouncedSearch)
 */
export function debounce<T extends (...args: unknown[]) => unknown>(
   func: T,
   delay: number,
): (...args: Parameters<T>) => void {
   let timer: ReturnType<typeof setTimeout> | null = null
   return (...args: Parameters<T>) => {
      if (timer !== null) clearTimeout(timer)
      timer = setTimeout(() => func(...args), delay)
   }
}

/**
 * Returns a throttled version of `func` that invokes it at most once
 * per `delay` ms, no matter how frequently it is called.
 *
 * @example
 * const throttledScroll = throttle(onScroll, 100)
 * window.addEventListener('scroll', throttledScroll)
 */
export function throttle<T extends (...args: unknown[]) => unknown>(
   func: T,
   delay: number,
): (...args: Parameters<T>) => void {
   let lastCall = 0
   return (...args: Parameters<T>) => {
      const now = Date.now()
      if (now - lastCall >= delay) {
         lastCall = now
         func(...args)
      }
   }
}