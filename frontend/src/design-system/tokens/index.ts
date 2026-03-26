/**
 * @file tokens/index.ts
 * @path /frontend/src/design-system/tokens/index.ts
 * @description The token injection system — the bridge between TypeScript
 * token definitions and live CSS custom properties in the browser.
 *
 * ─── How the system works ────────────────────────────────────────────────────
 * 1. You define token values in `colors.ts`, `typography.ts`, `spacing.ts`.
 * 2. This file merges all tokens into one flat object.
 * 3. Each key is prefixed with `--ch-` to create a CSS custom property name.
 * 4. Those properties are written to `document.documentElement` (the <html>
 *    element), making them available to ALL CSS on the page as `:root` vars.
 * 5. Every component's scoped CSS uses `var(--ch-*)` to read these values.
 *
 * ─── Why CSS custom properties instead of just importing TS values? ──────────
 * CSS custom properties are:
 *   - Inherited — child elements automatically get parent values
 *   - Overridable — you can scope a theme to any DOM subtree
 *   - Runtime-changeable — you can update them with JS without re-rendering
 *   - Framework-agnostic — work in Vue SFCs, plain CSS, and inline styles
 *
 * ─── Usage in your app's entry point (main.ts) ───────────────────────────────
 * @example
 * import { injectCSSVars } from '@/design-system/tokens'
 * import '@/design-system/styles/base.css'
 * import '@/design-system/styles/animations.css'
 *
 * Writes all --ch-* vars to document.documentElement (:root)
 * injectCSSVars()
 *
 * Or with a brand color override:
 * injectCSSVars({ '--ch-color-primary': '#e11d48' })
 */

// Re-export all token objects and types so consumers can import from
// one place: `import { palette, semanticColors, spacing } from '@/design-system/tokens'`
export { palette, semanticColors }      from './colors'
export { typography }                   from './typography'
export { spacing, radius, shadows, transitions, zIndex } from './spacing'

// Also re-export all the TypeScript types for use in consuming code
export type { SemanticColor }                         from './colors'
export type { TypographyToken }                       from './typography'
export type { SpacingToken, RadiusToken, ShadowToken,
              TransitionToken, ZIndexToken }          from './spacing'

// Import all token maps so we can merge them in the functions below
import { semanticColors }  from './colors'
import { typography }      from './typography'
import { spacing, radius, shadows, transitions, zIndex } from './spacing'

/**
 * A map of CSS custom property names to string values.
 * Used as the shape for both generated vars and user overrides.
 *
 * Keys must be full CSS var names including the `--ch-` prefix.
 * @example
 * const overrides: ThemeOverrides = {
 *   '--ch-color-primary': '#e11d48',
 *   '--ch-font-sans': '"Nunito", sans-serif',
 * }
 */
export type ThemeOverrides = Partial<Record<string, string>>

// ─── generateCSSVars ──────────────────────────────────────────────────────────
/**
 * Merges all token objects into a single flat map of CSS custom properties.
 *
 * Each token key is prefixed with `--ch-` to namespace it, preventing
 * collisions with other CSS variables in the project.
 *
 * If `overrides` are provided, those values take precedence over the
 * default token values. This is how runtime theming works.
 *
 * @param overrides - Optional map of `--ch-*` var names to override values
 * @returns A flat `Record<string, string>` of all CSS custom properties
 *
 * @example
 * const vars = generateCSSVars({ '--ch-color-primary': '#e11d48' })
 * { '--ch-color-primary': '#e11d48', '--ch-text-sm': '0.875rem', ... }
 */
export function generateCSSVars(overrides: ThemeOverrides = {}): Record<string, string> {
  // Merge all token objects into one flat map.
  // The spread order doesn't matter here — all keys are unique across files.
  const allTokens: Record<string, string> = {
    ...semanticColors,  // color-primary, color-bg, color-text, etc.
    ...typography,      // font-sans, text-sm, font-bold, etc.
    ...spacing,         // space-4, space-8, etc.
    ...radius,          // radius-lg, radius-full, etc.
    ...shadows,         // shadow-sm, shadow-md, etc.
    ...transitions,     // duration-fast, ease-out, etc.
    ...zIndex,          // z-modal, z-toast, etc.
  }

  // Build the output map: prefix each key with `--ch-` and apply any overrides.
  const vars: Record<string, string> = {}

  for (const [key, value] of Object.entries(allTokens)) {
    // Convert token key → CSS custom property name
    // e.g. 'color-primary' → '--ch-color-primary'
    const varName = `--ch-${key}`

    // If the user provided an override for this var, use it.
    // Otherwise fall back to the token's default value.
    // The `?? ` (nullish coalescing) means: use override only if it's not null/undefined
    vars[varName] = overrides[varName] ?? (value as string)
  }

  return vars
}

// ─── Shared initial overrides ─────────────────────────────────────────────────
/**
 * Stores the overrides passed to `injectCSSVars()` so that other modules
 * (e.g. `useTheme.ts`) can read the brand customizations that were set at
 * app startup. This bridges the gap between the one-time CSS injection in
 * `main.ts` and the reactive theme system in `useTheme`.
 *
 * Mutable by design — `injectCSSVars()` writes to it, `useTheme` reads from it.
 */
export let _initialOverrides: Record<string, string> = {}

// ─── injectCSSVars ────────────────────────────────────────────────────────────
/**
 * Writes all CSS custom properties directly onto a DOM element's inline style.
 *
 * By default this targets `document.documentElement` (the `<html>` element),
 * which is equivalent to setting them on `:root` in CSS.
 *
 * Call this **once** in `main.ts` during app initialization, before mounting Vue.
 * This ensures all `--ch-*` vars exist before any component renders.
 *
 * @param overrides - Optional theme overrides (will replace default token values)
 * @param target    - DOM element to inject onto (default: `<html>`)
 *
 * @example
 * main.ts
 * import { injectCSSVars } from '@/design-system/tokens'
 * injectCSSVars() // uses defaults
 *
 * With a brand override:
 * injectCSSVars({ '--ch-color-primary': '#e11d48' })
 *
 * Scoped to a specific element (e.g. for per-tenant theming):
 * const tenantRoot = document.getElementById('tenant-app')!
 * injectCSSVars({ '--ch-color-primary': tenant.brandColor }, tenantRoot)
 */
export function injectCSSVars(
  overrides: ThemeOverrides = {},
  target: HTMLElement = document.documentElement // defaults to <html> = :root
): void {
  // Store the overrides so useTheme can read brand tokens later (e.g. on theme toggle)
  _initialOverrides = { ...overrides } as Record<string, string>

  // Generate the full flat map of CSS var name → value
  const vars = generateCSSVars(overrides)

  // Write each property onto the target element's inline style.
  // `style.setProperty` is the correct API for CSS custom properties
  // (you can't use `el.style['--ch-color-primary']` directly).
  for (const [prop, value] of Object.entries(vars)) {
    target.style.setProperty(prop, value)
  }
}

// ─── generateStyleTag ─────────────────────────────────────────────────────────
/**
 * Generates a complete CSS string (not a DOM node) with all vars under
 * a given selector. Useful for:
 *   - SSR environments (Nuxt) where `document` doesn't exist at build time
 *   - Injecting a `<style>` tag server-side into the HTML response
 *   - Generating a CSS file with default tokens for static deployment
 *
 * @param selector - The CSS selector to wrap vars in (default: `':root'`)
 * @param overrides - Optional overrides applied before generating
 * @returns A CSS string like `:root { --ch-color-primary: #4f46e5; ... }`
 *
 * @example
 * In a Nuxt plugin or server middleware:
 * const css = generateStyleTag(':root')
 * useHead({ style: [{ innerHTML: css }] })
 *
 * For a scoped theme on a specific element:
 * const css = generateStyleTag('[data-theme="dark"]', darkOverrides)
 */
export function generateStyleTag(selector = ':root', overrides: ThemeOverrides = {}): string {
  const vars = generateCSSVars(overrides)

  // Build the CSS declaration block, one line per property
  const declarations = Object.entries(vars)
    .map(([prop, value]) => `  ${prop}: ${value};`) // indent for readability
    .join('\n')

  // Wrap in the selector and return as a complete CSS rule
  return `${selector} {\n${declarations}\n}`
}
