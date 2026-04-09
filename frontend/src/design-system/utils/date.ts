/**
 * @file utils/date.ts
 * @path /frontend/src/design-system/utils/date.ts
 * @description Enhanced date utilities inspired by date-fns, providing
 * comprehensive date manipulation, formatting, and comparison functions.
 *
 * ─── Features ────────────────────────────────────────────────────────────────
 * - Parse dates from various formats
 * - Format dates with extensive token support
 * - Compare dates (isToday, isTomorrow, isSameDay, etc.)
 * - Add/subtract time (days, months, years, etc.)
 * - Get start/end of periods (day, week, month, year)
 * - Calculate differences between dates
 * - Human-readable relative time
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example
 * import {
 *   format,
 *   isToday,
 *   isTomorrow,
 *   addDays,
 *   startOfMonth,
 *   differenceInDays
 * } from '@/design-system/utils/date'
 *
 * format(new Date(), 'EEEE, MMMM d, yyyy') // "Friday, March 27, 2026"
 * isToday(new Date()) // true
 * addDays(new Date(), 7) // 7 days from now
 */

// ─── Type Definitions ────────────────────────────────────────────────────────

/** Locale object for internationalization */
export interface Locale {
  code: string
  formatDistance: (token: string, count: number) => string
  months: string[]
  monthsShort: string[]
  days: string[]
  daysShort: string[]
  am: string
  pm: string
}

/** Options for format function */
export interface FormatOptions {
  locale?: Locale
  weekStartsOn?: 0 | 1 | 2 | 3 | 4 | 5 | 6
}

/** Options for difference functions */
export interface DifferenceOptions {
  unit?: 'years' | 'months' | 'days' | 'hours' | 'minutes' | 'seconds'
  roundingMethod?: 'floor' | 'ceil' | 'round'
}

// ─── Default Locale (English) ────────────────────────────────────────────────

const defaultLocale: Locale = {
  code: 'en',
  formatDistance: (token, count) => {
    const distances: Record<string, string[]> = {
      lessThanXSeconds: ['less than a second', 'less than {count} seconds'],
      xSeconds: ['1 second', '{count} seconds'],
      halfAMinute: ['half a minute'],
      lessThanXMinutes: ['less than a minute', 'less than {count} minutes'],
      xMinutes: ['1 minute', '{count} minutes'],
      aboutXHours: ['about 1 hour', 'about {count} hours'],
      xHours: ['1 hour', '{count} hours'],
      xDays: ['1 day', '{count} days'],
      aboutXMonths: ['about 1 month', 'about {count} months'],
      xMonths: ['1 month', '{count} months'],
      aboutXYears: ['about 1 year', 'about {count} years'],
      xYears: ['1 year', '{count} years'],
      overXYears: ['over 1 year', 'over {count} years'],
      almostXYears: ['almost 1 year', 'almost {count} years'],
    }
    const variants = distances[token]
    if (!variants) return token
    const variant = count === 1 ? variants[0] : variants[1]
    return variant!.replace('{count}', String(count))
  },
  months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
  monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
  daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
  am: 'AM',
  pm: 'PM',
}

// ─── Helper Functions ────────────────────────────────────────────────────────

/**
 * Ensures the input is a Date object
 */
function toDate(date: Date | string | number): Date {
  if (date instanceof Date) return date
  const d = new Date(date)
  if (isNaN(d.getTime())) {
    throw new Error(`Invalid date: ${date}`)
  }
  return d
}

/**
 * Checks if a date is valid
 */
function isValid(date: Date | string | number): boolean {
  const d = toDate(date)
  return !isNaN(d.getTime())
}

// ─── Parse Functions ─────────────────────────────────────────────────────────

/**
 * Parses a date string in various formats
 *
 * @example
 * parse('2025-07-14') // ISO format
 * parse('14/07/2025') // DD/MM/YYYY
 * parse('07/14/2025') // MM/DD/YYYY
 * parse('July 14, 2025') // Natural language
 */
export function parse(dateString: string): Date {
  // Try ISO format first
  let date = new Date(dateString)
  if (!isNaN(date.getTime())) return date

  // Try DD/MM/YYYY or MM/DD/YYYY
  const slashMatch = dateString.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/)
  if (slashMatch) {
    const [, first, second, year] = slashMatch
    const firstNum = parseInt(first!, 10)
    const secondNum = parseInt(second!, 10)

    // If first number > 12, it's DD/MM/YYYY
    if (firstNum > 12) {
      date = new Date(parseInt(year!, 10), secondNum - 1, firstNum)
    }
    // If second number > 12, it's MM/DD/YYYY
    else if (secondNum > 12) {
      date = new Date(parseInt(year!, 10), firstNum - 1, secondNum)
    }
    // Ambiguous - assume MM/DD/YYYY (US format)
    else {
      date = new Date(parseInt(year!, 10), firstNum - 1, secondNum)
    }
    if (!isNaN(date.getTime())) return date
  }

  // Try natural language format: "July 14, 2025"
  const naturalMatch = dateString.match(/^([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})$/)
  if (naturalMatch) {
    const [, monthName, day, year] = naturalMatch
    const monthIndex = defaultLocale.months.findIndex(m =>
      m.toLowerCase().startsWith(monthName!.toLowerCase())
    )
    if (monthIndex !== -1) {
      date = new Date(parseInt(year!, 10), monthIndex, parseInt(day!, 10))
      if (!isNaN(date.getTime())) return date
    }
  }

  throw new Error(`Unable to parse date string: ${dateString}`)
}

// ─── Format Functions ────────────────────────────────────────────────────────

/**
 * Formats a date using format tokens.
 *
 * Supported tokens:
 * - Year: yyyy, yy, y
 * - Month: MMMM, MMM, MM, M
 * - Day: dddd, ddd, dd, d
 * - Date: DD, D
 * - Hour: HH, H, hh, h, a
 * - Minute: mm, m
 * - Second: ss, s
 * - Quarter: Q
 * - Week: ww, w
 * - Day of year: DDD
 *
 * @example
 * format(new Date(), 'yyyy-MM-dd') // "2025-07-14"
 * format(new Date(), 'EEEE, MMMM d, yyyy') // "Monday, July 14, 2025"
 * format(new Date(), 'MMM d, yyyy h:mm a') // "Jul 14, 2025 3:45 PM"
 */
export function format(
  date: Date | string | number,
  formatStr: string,
  options?: FormatOptions
): string {
  const d = toDate(date)
  const locale = options?.locale ?? defaultLocale
  const weekStartsOn = options?.weekStartsOn ?? 0

  if (!isValid(d)) return 'Invalid Date'

  const year = d.getFullYear()
  const month = d.getMonth()
  const day = d.getDate()
  const hours = d.getHours()
  const minutes = d.getMinutes()
  const seconds = d.getSeconds()
  const dayOfWeek = d.getDay()

  // Calculate week number
  const firstDayOfYear = new Date(year, 0, 1)
  const pastDays = Math.floor((d.getTime() - firstDayOfYear.getTime()) / 86400000)
  const week = Math.floor((pastDays + firstDayOfYear.getDay()) / 7) + 1

  // Calculate day of year
  const startOfYear = new Date(year, 0, 1)
  const dayOfYear = Math.floor((d.getTime() - startOfYear.getTime()) / 86400000) + 1

  // Calculate quarter
  const quarter = Math.floor(month / 3) + 1

  // 12-hour format
  const hours12 = hours % 12 || 12
  const ampm = hours >= 12 ? locale.pm : locale.am

  const tokens: Record<string, string> = {
    // Year
    yyyy: String(year),
    yy: String(year).slice(-2),
    y: String(year),

    // Month
    MMMM: locale.months[month]!,
    MMM: locale.monthsShort[month]!,
    MM: String(month + 1).padStart(2, '0'),
    M: String(month + 1),

    // Day of week
    dddd: locale.days[dayOfWeek]!,
    ddd: locale.daysShort[dayOfWeek]!,
    dd: String(dayOfWeek).padStart(2, '0'),
    d: String(dayOfWeek),

    // Date of month
    DD: String(day).padStart(2, '0'),
    D: String(day),

    // Hour (24-hour)
    HH: String(hours).padStart(2, '0'),
    H: String(hours),

    // Hour (12-hour)
    hh: String(hours12).padStart(2, '0'),
    h: String(hours12),

    // AM/PM
    a: ampm,

    // Minute
    mm: String(minutes).padStart(2, '0'),
    m: String(minutes),

    // Second
    ss: String(seconds).padStart(2, '0'),
    s: String(seconds),

    // Quarter
    Q: String(quarter),

    // Week
    ww: String(week).padStart(2, '0'),
    w: String(week),

    // Day of year
    DDD: String(dayOfYear).padStart(3, '0'),
  }

  // Sort tokens by length (longest first) to ensure proper replacement
  const sortedTokens = Object.keys(tokens).sort((a, b) => b.length - a.length)

  return formatStr.replace(new RegExp(sortedTokens.join('|'), 'g'), match => tokens[match] ?? match)
}

/**
 * Formats a date as ISO string (yyyy-MM-dd)
 */
export function formatISO(date: Date | string | number): string {
  return format(date, 'yyyy-MM-dd')
}

/**
 * Formats a date as time string (HH:mm:ss)
 */
export function formatTime(date: Date | string | number, includeSeconds = false): string {
  return format(date, includeSeconds ? 'HH:mm:ss' : 'HH:mm')
}

/**
 * Formats a date as relative time (e.g., "2 hours ago", "in 3 days")
 */
export function formatRelative(
  date: Date | string | number,
  baseDate: Date | string | number = new Date(),
  options?: { locale?: Locale }
): string {
  const d = toDate(date)
  const base = toDate(baseDate)
  const locale = options?.locale ?? defaultLocale

  const diffMs = d.getTime() - base.getTime()
  const diffSecs = Math.round(diffMs / 1000)
  const diffMins = Math.round(diffSecs / 60)
  const diffHours = Math.round(diffMins / 60)
  const diffDays = Math.round(diffHours / 24)
  const diffMonths = Math.round(diffDays / 30)
  const diffYears = Math.round(diffDays / 365)

  const absSecs = Math.abs(diffSecs)
  const absMins = Math.abs(diffMins)
  const absHours = Math.abs(diffHours)
  const absDays = Math.abs(diffDays)
  const absMonths = Math.abs(diffMonths)
  const absYears = Math.abs(diffYears)

  const isPast = diffMs < 0

  if (absSecs < 60) {
    return locale.formatDistance('xSeconds', absSecs) + (isPast ? ' ago' : ' from now')
  }
  if (absMins < 60) {
    return locale.formatDistance('xMinutes', absMins) + (isPast ? ' ago' : ' from now')
  }
  if (absHours < 24) {
    return locale.formatDistance('xHours', absHours) + (isPast ? ' ago' : ' from now')
  }
  if (absDays < 30) {
    return locale.formatDistance('xDays', absDays) + (isPast ? ' ago' : ' from now')
  }
  if (absMonths < 12) {
    return locale.formatDistance('xMonths', absMonths) + (isPast ? ' ago' : ' from now')
  }
  return locale.formatDistance('xYears', absYears) + (isPast ? ' ago' : ' from now')
}

/**
 * Formats a date as distance in words (similar to formatRelative but more natural)
 */
export function formatDistance(
  date: Date | string | number,
  baseDate: Date | string | number = new Date(),
  options?: { locale?: Locale; addSuffix?: boolean }
): string {
  const d = toDate(date)
  const base = toDate(baseDate)
  const locale = options?.locale ?? defaultLocale
  const addSuffix = options?.addSuffix ?? true

  const diffMs = Math.abs(d.getTime() - base.getTime())
  const diffSecs = Math.round(diffMs / 1000)
  const diffMins = Math.round(diffSecs / 60)
  const diffHours = Math.round(diffMins / 60)
  const diffDays = Math.round(diffHours / 24)
  const diffMonths = Math.round(diffDays / 30)
  const diffYears = Math.round(diffDays / 365)

  let result: string

  if (diffSecs < 60) {
    result = locale.formatDistance('xSeconds', diffSecs)
  } else if (diffMins < 60) {
    result = locale.formatDistance('xMinutes', diffMins)
  } else if (diffHours < 24) {
    result = locale.formatDistance('xHours', diffHours)
  } else if (diffDays < 30) {
    result = locale.formatDistance('xDays', diffDays)
  } else if (diffMonths < 12) {
    result = locale.formatDistance('xMonths', diffMonths)
  } else {
    result = locale.formatDistance('xYears', diffYears)
  }

  if (addSuffix) {
    const isPast = d.getTime() < base.getTime()
    return result + (isPast ? ' ago' : ' from now')
  }

  return result
}

// ─── Comparison Functions ────────────────────────────────────────────────────

/**
 * Checks if a date is today
 */
export function isToday(date: Date | string | number): boolean {
  const d = toDate(date)
  const today = new Date()
  return (
    d.getDate() === today.getDate() &&
    d.getMonth() === today.getMonth() &&
    d.getFullYear() === today.getFullYear()
  )
}

/**
 * Checks if a date is yesterday
 */
export function isYesterday(date: Date | string | number): boolean {
  const d = toDate(date)
  const yesterday = addDays(new Date(), -1)
  return (
    d.getDate() === yesterday.getDate() &&
    d.getMonth() === yesterday.getMonth() &&
    d.getFullYear() === yesterday.getFullYear()
  )
}

/**
 * Checks if a date is tomorrow
 */
export function isTomorrow(date: Date | string | number): boolean {
  const d = toDate(date)
  const tomorrow = addDays(new Date(), 1)
  return (
    d.getDate() === tomorrow.getDate() &&
    d.getMonth() === tomorrow.getMonth() &&
    d.getFullYear() === tomorrow.getFullYear()
  )
}

/**
 * Checks if two dates are on the same day
 */
export function isSameDay(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): boolean {
  const d1 = toDate(dateLeft)
  const d2 = toDate(dateRight)
  return (
    d1.getDate() === d2.getDate() &&
    d1.getMonth() === d2.getMonth() &&
    d1.getFullYear() === d2.getFullYear()
  )
}

/**
 * Checks if two dates are in the same month
 */
export function isSameMonth(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): boolean {
  const d1 = toDate(dateLeft)
  const d2 = toDate(dateRight)
  return d1.getMonth() === d2.getMonth() && d1.getFullYear() === d2.getFullYear()
}

/**
 * Checks if two dates are in the same year
 */
export function isSameYear(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): boolean {
  const d1 = toDate(dateLeft)
  const d2 = toDate(dateRight)
  return d1.getFullYear() === d2.getFullYear()
}

/**
 * Checks if a date is in the past
 */
export function isPast(date: Date | string | number): boolean {
  return toDate(date).getTime() < Date.now()
}

/**
 * Checks if a date is in the future
 */
export function isFuture(date: Date | string | number): boolean {
  return toDate(date).getTime() > Date.now()
}

/**
 * Checks if dateLeft is after dateRight
 */
export function isAfter(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): boolean {
  return toDate(dateLeft).getTime() > toDate(dateRight).getTime()
}

/**
 * Checks if dateLeft is before dateRight
 */
export function isBefore(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): boolean {
  return toDate(dateLeft).getTime() < toDate(dateRight).getTime()
}

/**
 * Checks if date is within a range
 */
export function isWithinRange(
  date: Date | string | number,
  start: Date | string | number,
  end: Date | string | number
): boolean {
  const d = toDate(date)
  const time = d.getTime()
  return time >= toDate(start).getTime() && time <= toDate(end).getTime()
}

// ─── Add/Subtract Functions ──────────────────────────────────────────────────

/**
 * Adds specified number of days to a date
 */
export function addDays(date: Date | string | number, amount: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setDate(result.getDate() + amount)
  return result
}

/**
 * Adds specified number of months to a date
 */
export function addMonths(date: Date | string | number, amount: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setMonth(result.getMonth() + amount)
  return result
}

/**
 * Adds specified number of years to a date
 */
export function addYears(date: Date | string | number, amount: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setFullYear(result.getFullYear() + amount)
  return result
}

/**
 * Adds specified number of hours to a date
 */
export function addHours(date: Date | string | number, amount: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setHours(result.getHours() + amount)
  return result
}

/**
 * Adds specified number of minutes to a date
 */
export function addMinutes(date: Date | string | number, amount: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setMinutes(result.getMinutes() + amount)
  return result
}

/**
 * Adds specified number of seconds to a date
 */
export function addSeconds(date: Date | string | number, amount: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setSeconds(result.getSeconds() + amount)
  return result
}

/**
 * Subtracts specified number of days from a date
 */
export function subDays(date: Date | string | number, amount: number): Date {
  return addDays(date, -amount)
}

/**
 * Subtracts specified number of months from a date
 */
export function subMonths(date: Date | string | number, amount: number): Date {
  return addMonths(date, -amount)
}

/**
 * Subtracts specified number of years from a date
 */
export function subYears(date: Date | string | number, amount: number): Date {
  return addYears(date, -amount)
}

// ─── Start/End Functions ─────────────────────────────────────────────────────

/**
 * Returns the start of the day (midnight)
 */
export function startOfDay(date: Date | string | number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setHours(0, 0, 0, 0)
  return result
}

/**
 * Returns the end of the day (23:59:59.999)
 */
export function endOfDay(date: Date | string | number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setHours(23, 59, 59, 999)
  return result
}

/**
 * Returns the start of the month
 */
export function startOfMonth(date: Date | string | number): Date {
  const d = toDate(date)
  const result = new Date(d.getFullYear(), d.getMonth(), 1)
  return result
}

/**
 * Returns the end of the month
 */
export function endOfMonth(date: Date | string | number): Date {
  const d = toDate(date)
  const result = new Date(d.getFullYear(), d.getMonth() + 1, 0)
  return result
}

/**
 * Returns the start of the year
 */
export function startOfYear(date: Date | string | number): Date {
  const d = toDate(date)
  const result = new Date(d.getFullYear(), 0, 1)
  return result
}

/**
 * Returns the end of the year
 */
export function endOfYear(date: Date | string | number): Date {
  const d = toDate(date)
  const result = new Date(d.getFullYear(), 11, 31, 23, 59, 59, 999)
  return result
}

/**
 * Returns the start of the week
 */
export function startOfWeek(
  date: Date | string | number,
  options?: { weekStartsOn?: 0 | 1 | 2 | 3 | 4 | 5 | 6 }
): Date {
  const d = toDate(date)
  const weekStartsOn = options?.weekStartsOn ?? 0
  const result = new Date(d)
  const day = result.getDay()
  const diff = (day < weekStartsOn ? 7 : 0) + day - weekStartsOn
  result.setDate(result.getDate() - diff)
  result.setHours(0, 0, 0, 0)
  return result
}

/**
 * Returns the end of the week
 */
export function endOfWeek(
  date: Date | string | number,
  options?: { weekStartsOn?: 0 | 1 | 2 | 3 | 4 | 5 | 6 }
): Date {
  const d = toDate(date)
  const weekStartsOn = options?.weekStartsOn ?? 0
  const result = new Date(d)
  const day = result.getDay()
  const diff = (day < weekStartsOn ? -7 : 0) + 6 - day + weekStartsOn
  result.setDate(result.getDate() + diff)
  result.setHours(23, 59, 59, 999)
  return result
}

// ─── Difference Functions ────────────────────────────────────────────────────

/**
 * Returns the difference in milliseconds
 */
export function differenceInMilliseconds(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): number {
  return toDate(dateLeft).getTime() - toDate(dateRight).getTime()
}

/**
 * Returns the difference in seconds
 */
export function differenceInSeconds(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): number {
  return Math.floor(differenceInMilliseconds(dateLeft, dateRight) / 1000)
}

/**
 * Returns the difference in minutes
 */
export function differenceInMinutes(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): number {
  return Math.floor(differenceInSeconds(dateLeft, dateRight) / 60)
}

/**
 * Returns the difference in hours
 */
export function differenceInHours(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): number {
  return Math.floor(differenceInMinutes(dateLeft, dateRight) / 60)
}

/**
 * Returns the difference in days
 */
export function differenceInDays(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): number {
  return Math.floor(differenceInHours(dateLeft, dateRight) / 24)
}

/**
 * Returns the difference in months (approximate)
 */
export function differenceInMonths(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): number {
  const d1 = toDate(dateLeft)
  const d2 = toDate(dateRight)
  const months = (d1.getFullYear() - d2.getFullYear()) * 12 + (d1.getMonth() - d2.getMonth())
  return months
}

/**
 * Returns the difference in years (approximate)
 */
export function differenceInYears(
  dateLeft: Date | string | number,
  dateRight: Date | string | number
): number {
  return Math.floor(differenceInMonths(dateLeft, dateRight) / 12)
}

// ─── Get Functions ───────────────────────────────────────────────────────────

/**
 * Gets the day of the month
 */
export function getDate(date: Date | string | number): number {
  return toDate(date).getDate()
}

/**
 * Gets the day of the week (0 = Sunday, 6 = Saturday)
 */
export function getDay(date: Date | string | number): number {
  return toDate(date).getDay()
}

/**
 * Gets the month (0 = January, 11 = December)
 */
export function getMonth(date: Date | string | number): number {
  return toDate(date).getMonth()
}

/**
 * Gets the year
 */
export function getYear(date: Date | string | number): number {
  return toDate(date).getFullYear()
}

/**
 * Gets the hours (0-23)
 */
export function getHours(date: Date | string | number): number {
  return toDate(date).getHours()
}

/**
 * Gets the minutes (0-59)
 */
export function getMinutes(date: Date | string | number): number {
  return toDate(date).getMinutes()
}

/**
 * Gets the seconds (0-59)
 */
export function getSeconds(date: Date | string | number): number {
  return toDate(date).getSeconds()
}

/**
 * Gets the milliseconds (0-999)
 */
export function getMilliseconds(date: Date | string | number): number {
  return toDate(date).getMilliseconds()
}

// ─── Set Functions ───────────────────────────────────────────────────────────

/**
 * Sets the date of the month
 */
export function setDate(date: Date | string | number, dayOfMonth: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setDate(dayOfMonth)
  return result
}

/**
 * Sets the day of the week
 */
export function setDay(
  date: Date | string | number,
  day: number,
  options?: { weekStartsOn?: 0 | 1 | 2 | 3 | 4 | 5 | 6 }
): Date {
  const d = toDate(date)
  const weekStartsOn = options?.weekStartsOn ?? 0
  const result = new Date(d)
  const currentDay = result.getDay()
  const diff = (day < weekStartsOn ? 7 : 0) + day - weekStartsOn - ((currentDay < weekStartsOn ? 7 : 0) + currentDay - weekStartsOn)
  result.setDate(result.getDate() + diff)
  return result
}

/**
 * Sets the month (0 = January, 11 = December)
 */
export function setMonth(date: Date | string | number, month: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setMonth(month)
  return result
}

/**
 * Sets the year
 */
export function setYear(date: Date | string | number, year: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setFullYear(year)
  return result
}

/**
 * Sets the hours
 */
export function setHours(date: Date | string | number, hours: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setHours(hours)
  return result
}

/**
 * Sets the minutes
 */
export function setMinutes(date: Date | string | number, minutes: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setMinutes(minutes)
  return result
}

/**
 * Sets the seconds
 */
export function setSeconds(date: Date | string | number, seconds: number): Date {
  const d = toDate(date)
  const result = new Date(d)
  result.setSeconds(seconds)
  return result
}

// ─── Utility Functions ───────────────────────────────────────────────────────

/**
 * Returns the current date and time
 */
export function now(): Date {
  return new Date()
}

/**
 * Returns the current timestamp in milliseconds
 */
export function timestamp(): number {
  return Date.now()
}

/**
 * Returns whether a year is a leap year
 */
export function isLeapYear(year: number): boolean {
  return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0)
}

/**
 * Returns the number of days in a month
 */
export function getDaysInMonth(date: Date | string | number): number {
  const d = toDate(date)
  return new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate()
}

/**
 * Returns the number of days in a year
 */
export function getDaysInYear(date: Date | string | number): number {
  const d = toDate(date)
  return isLeapYear(d.getFullYear()) ? 366 : 365
}

/**
 * Clamps a date between two dates
 */
export function clamp(
  date: Date | string | number,
  start: Date | string | number,
  end: Date | string | number
): Date {
  const d = toDate(date)
  const time = d.getTime()
  const startTime = toDate(start).getTime()
  const endTime = toDate(end).getTime()

  if (time < startTime) return toDate(start)
  if (time > endTime) return toDate(end)
  return d
}

/**
 * Returns the nearest date from an array of dates
 */
export function closestTo(
  date: Date | string | number,
  datesArray: (Date | string | number)[]
): Date | null {
  if (!datesArray.length) return null

  const d = toDate(date)
  let closest = datesArray[0]!
  let minDiff = Math.abs(d.getTime() - toDate(closest).getTime())

  for (let i = 1; i < datesArray.length; i++) {
    const diff = Math.abs(d.getTime() - toDate(datesArray[i]!).getTime())
    if (diff < minDiff) {
      minDiff = diff
      closest = datesArray[i]!
    }
  }

  return toDate(closest)
}

/**
 * Returns whether two date ranges overlap
 */
export function areIntervalsOverlapping(
  intervalLeft: { start: Date | string | number; end: Date | string | number },
  intervalRight: { start: Date | string | number; end: Date | string | number }
): boolean {
  const leftStart = toDate(intervalLeft.start).getTime()
  const leftEnd = toDate(intervalLeft.end).getTime()
  const rightStart = toDate(intervalRight.start).getTime()
  const rightEnd = toDate(intervalRight.end).getTime()

  return leftStart <= rightEnd && rightStart <= leftEnd
}
