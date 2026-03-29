import { type InjectionKey, type ComputedRef } from 'vue'

/**
 * Separator styles:
 * - `/`       → Slash (default, classic breadcrumb)
 * - `>`       → Greater-than symbol
 * - `chevron` → SVG chevron-right
 * - `arrow`   → SVG arrow-right
 */
export type BreadcrumbSeparator = '/' | '>' | 'chevron' | 'arrow'

/** A single breadcrumb item descriptor */
export interface BreadcrumbItem {
  /** Display text */
  label: string
  /** Link URL — omit for the current (last) item */
  href?: string
  /** Optional SVG path for an icon rendered before the label */
  icon?: string
}

/** Context provided to ChBreadcrumbItem children in slot mode */
export interface BreadcrumbContext {
  separator: ComputedRef<BreadcrumbSeparator>
  separatorPath: ComputedRef<string>
}

/** Typed injection key — import this in ChBreadcrumbItem */
export const BREADCRUMB_KEY: InjectionKey<BreadcrumbContext> = Symbol('ChBreadcrumb')