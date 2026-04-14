import { ref, type InjectionKey, type Ref } from 'vue'

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
  label?: string
  /** Link URL — omit for the current (last) item */
  href?: string
  /** Vue Router `to` target — convenience for views that use router links */
  to?: string
  /** Optional SVG path for an icon rendered before the label */
  icon?: string
}

/**
 * Context provided to ChBreadcrumbItem children in slot mode.
 *
 * Uses `Ref` rather than `ComputedRef` so the fallback constant below can
 * use plain `ref()` without lying to TypeScript. `ComputedRef<T>` extends
 * `Ref<T>`, so the computed refs provided by ChBreadcrumb still satisfy this
 * interface — no cast required at either end.
 */
export interface BreadcrumbContext {
  separator: Ref<BreadcrumbSeparator>
  separatorPath: Ref<string>
}

/**
 * Honest fallback used by ChBreadcrumbItem when it is rendered outside a
 * ChBreadcrumb (edge case). Satisfies `BreadcrumbContext` with real Vue refs
 * rather than plain-object duck-typing.
 */
export const defaultBreadcrumbContext: BreadcrumbContext = {
  separator: ref('/'),
  separatorPath: ref(''),
}

/** Typed injection key — import this in ChBreadcrumbItem */
export const BREADCRUMB_KEY: InjectionKey<BreadcrumbContext> = Symbol('ChBreadcrumb')