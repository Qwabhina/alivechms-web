/**
 * @file tokens/index.ts
 * @path /frontend/src/design-system/tokens/index.ts
 * @description The token system — the bridge between TypeScript token definitions
 * and live CSS custom properties in the browser.
 *
 * ─── How the system works ────────────────────────────────────────────────────
 * 1. Token values are defined in `colors.ts`, `typography.ts`, `spacing.ts`.
 * 2. This file merges them and provides utilities to generate CSS custom props.
 * 3. Each token key is prefixed with `--ch-` when written to the DOM.
 * 4. `useTheme.ts` automatically initializes all tokens at module load time —
 *    you do NOT need to call any initialization function in `main.ts`.
 *
 * ─── Initialization ──────────────────────────────────────────────────────────
 * CSS tokens are initialized automatically when the module is first imported.
 * `useTheme.ts` runs at module-load time, reads the user's dark mode preference
 * (localStorage → OS), and writes all tokens to `document.documentElement`.
 *
 * In `main.ts`, you only need to import the global styles:
 *
 *   import '@/design-system/styles/base.css'
 *
 * ─── Startup brand overrides ─────────────────────────────────────────────────
 * To apply a brand color at app startup (e.g. from a config file), use
 * `useTheme().applyOverrides()`. Unlike the old `injectCSSVars()` approach,
 * overrides set this way survive dark mode toggles and `applyTheme()` calls.
 *
 * @example Apply a startup brand color
 * // main.ts
 * import { useTheme } from '@/design-system'
 * import '@/design-system/styles/base.css'
 *
 * const { applyOverrides } = useTheme()
 * applyOverrides({ '--ch-color-primary': appConfig.brandColor })
 *
 * @example Switch to a named theme post-login
 * import { useTheme, defineTheme } from '@/design-system'
 * const { applyTheme } = useTheme()
 * applyTheme(defineTheme({ primary900: church.brandColor }, church.name))
 *
 * @example SSR — generate a static CSS string (no DOM required)
 * import { generateStyleTag } from '@/design-system'
 * const css = generateStyleTag(':root')
 * useHead({ style: [{ innerHTML: css }] })
 */

// ─── Color token re-exports ───────────────────────────────────────────────────
// Everything you need to work with the color system:
//
//   palette              — raw hex values (the paint swatches)
//   semanticColors       — default light-mode semantic token map
//   darkSemanticColors   — default dark-mode semantic token map
//   createSemanticColors — factory: build a light semantic map from any palette
//   createDarkSemanticColors — factory: build a dark semantic map from any palette
//   defaultTheme         — the built-in Theme object (light + dark co-located)
//   defineTheme          — create a custom Theme by merging palette overrides
//
// Types:
//   SemanticColor        — union of all valid semantic token key names
//   Theme                — a portable { name, palette, light, dark } bundle
//   Palette              — record type matching all palette keys (for overrides)
export {
  palette,
  semanticColors,
  darkSemanticColors,
  createSemanticColors,
  createDarkSemanticColors,
  defaultTheme,
  defineTheme,
} from './colors'

export type { SemanticColor, Theme, Palette } from './colors'

// ─── Other token re-exports ───────────────────────────────────────────────────
export { typography } from './typography'
export { spacing, radius, shadows, transitions, zIndex } from './spacing'

export type { TypographyToken } from './typography'
export type {
  SpacingToken,
  RadiusToken,
  ShadowToken,
  TransitionToken,
  ZIndexToken,
} from './spacing'

// ─── Internal imports (for CSS var utilities below) ───────────────────────────
import { semanticColors } from './colors'
import { typography } from './typography'
import { spacing, radius, shadows, transitions, zIndex } from './spacing'

// ─── ThemeOverrides ───────────────────────────────────────────────────────────
/**
 * A map of CSS custom property names to string values.
 * Used as the shape for both generated vars and caller-supplied overrides.
 *
 * Keys must include the `--ch-` prefix, though helper functions such as
 * `generateCSSVars` and `injectCSSVars` also accept bare token keys
 * (e.g. `'color-primary'`) and normalize them automatically.
 *
 * @example
 * const overrides: ThemeOverrides = {
 *   '--ch-color-primary': '#e11d48',
 *   '--ch-font-sans':     '"Nunito", sans-serif',
 * }
 */
export type ThemeOverrides = Partial<Record<string, string>>

// ─── Internal constants ───────────────────────────────────────────────────────

/** The `--ch-` namespace prefix applied to every design token. */
const CSS_VAR_PREFIX = '--ch-'

/**
 * All design tokens merged into one flat object, built once at module init.
 *
 * `semanticColors` provides the LIGHT MODE color defaults. At runtime,
 * `useTheme.ts` passes the active theme's color map as overrides to
 * `generateCSSVars`, so those overrides take precedence over these defaults.
 * Spacing, typography, radius, etc. are always static and come from here.
 */
const ALL_TOKENS: Record<string, string> = {
  ...semanticColors,
  ...typography,
  ...spacing,
  ...radius,
  ...shadows,
  ...transitions,
  ...zIndex,
}

// ─── normalizeOverrides ───────────────────────────────────────────────────────
/**
 * Normalizes override keys so callers can provide either full CSS var names
 * (`--ch-color-primary`) or bare token keys (`color-primary`).
 * Returns a record keyed by fully-prefixed CSS custom property names.
 */
function normalizeOverrides(overrides: ThemeOverrides = {}): Record<string, string> {
  const normalized: Record<string, string> = {}
  for (const [k, v] of Object.entries(overrides)) {
    const name = k.startsWith('--') ? k : `${CSS_VAR_PREFIX}${k.replace(/^--?/, '')}`
    normalized[name] = v as string
  }
  return normalized
}

// ─── generateCSSVars ──────────────────────────────────────────────────────────
/**
 * Merges all token definitions into a single flat map of CSS custom properties.
 *
 * Each token key is prefixed with `--ch-`. When `overrides` are provided those
 * values take precedence over the defaults in `ALL_TOKENS` — this is how
 * `useTheme.ts` applies theme or dark-mode color vars on top of static defaults.
 *
 * @param overrides - Optional `--ch-*` overrides (bare token keys accepted too)
 * @returns A flat `Record<string, string>` ready to be written to the DOM
 *
 * @example
 * const vars = generateCSSVars({ '--ch-color-primary': '#e11d48' })
 * // → { '--ch-color-primary': '#e11d48', '--ch-text-sm': '0.875rem', ... }
 */
export function generateCSSVars(overrides: ThemeOverrides = {}): Record<string, string> {
  const normalizedOverrides = normalizeOverrides(overrides)
  const vars: Record<string, string> = {}
  for (const [key, value] of Object.entries(ALL_TOKENS)) {
    const varName = `${CSS_VAR_PREFIX}${key}`
    vars[varName] = normalizedOverrides[varName] ?? (value as string)
  }
  return vars
}

// ─── injectCSSVars ────────────────────────────────────────────────────────────
/**
 * Applies a specific set of CSS custom property overrides to a DOM element.
 *
 * ⚠ **Changed behavior (v2)**: This function no longer writes the full default
 * token set to the DOM. The complete token set — including correct dark/light
 * mode colors — is now initialized automatically by `useTheme.ts` at module
 * load time. Rewriting all tokens here would overwrite those dark mode colors
 * with the light-mode snapshot baked into `ALL_TOKENS`.
 *
 * Calling this with **no arguments is a no-op** — initialization is handled
 * automatically and requires nothing from `main.ts`.
 *
 * Only the keys you supply are written. Overrides applied this way do NOT
 * survive dark mode toggles or `applyTheme()` calls, because `useTheme.ts`
 * has no knowledge of them. For persistent overrides, use
 * `useTheme().applyOverrides()` instead.
 *
 * @param overrides - Specific CSS properties to set (default: `{}`)
 * @param target    - DOM element to write to (default: `<html>` = `:root`)
 *
 * @example One-shot startup override (NOT dark-mode-persistent)
 * injectCSSVars({ '--ch-color-primary': startupConfig.brandColor })
 *
 * @example Preferred: persistent override via useTheme
 * import { useTheme } from '@/design-system'
 * const { applyOverrides } = useTheme()
 * applyOverrides({ '--ch-color-primary': church.brandColor })
 * // ↑ Survives dark mode toggles and applyTheme() calls automatically.
 */
export function injectCSSVars(
  overrides: ThemeOverrides = {},
  target: HTMLElement = document.documentElement,
): void {
  const normalized = normalizeOverrides(overrides)
  // Write ONLY the provided overrides — do NOT write the full default token set.
  // The full token set (with correct dark/light mode colors) is owned by
  // useTheme.ts which already ran at module load time.
  for (const [prop, value] of Object.entries(normalized)) {
    target.style.setProperty(prop, value)
  }
}

// ─── generateStyleTag ─────────────────────────────────────────────────────────
/**
 * Generates a complete CSS string with all token vars under a given selector.
 * Produces a pure string — does NOT touch the DOM. Designed for SSR environments
 * where `document` doesn't exist at build time.
 *
 * @param selector  - CSS selector wrapping the declarations (default: `':root'`)
 * @param overrides - Optional overrides applied before generating
 * @returns A CSS string like `:root { --ch-color-primary: #00026d; ... }`
 *
 * @example Nuxt plugin / server middleware
 * const css = generateStyleTag(':root')
 * useHead({ style: [{ innerHTML: css }] })
 *
 * @example Scoped theme on a specific selector
 * const css = generateStyleTag('[data-theme="forest"]', forestOverrides)
 */
export function generateStyleTag(selector = ':root', overrides: ThemeOverrides = {}): string {
  const vars = generateCSSVars(overrides)
  const declarations = Object.entries(vars)
    .map(([prop, value]) => `  ${prop}: ${value};`)
    .join('\n')
  return `${selector} {\n${declarations}\n}`
}
