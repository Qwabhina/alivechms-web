/**
 * @file colors.ts
 * @path /frontend/src/design-system/tokens/colors.ts
 * @description Design token definitions for the color system.
 *
 * The color system is organized in three layers:
 *
 * 1. **Primitive palette** (`palette`)
 *    Raw, named color values with no semantic meaning. Think of these as your
 *    "paint swatches". Brand: Deep Navy (#00026d) + Amber Gold (#ffb300).
 *
 * 2. **Semantic factories** (`createSemanticColors`, `createDarkSemanticColors`)
 *    Pure functions that map a palette to a full set of semantic tokens. Because
 *    the semantic layer is a *function* of the palette — not hardcoded against it —
 *    you can swap palettes entirely by calling the factory with a different input.
 *
 * 3. **Theme definition** (`defineTheme`)
 *    Bundles a palette with its derived light + dark semantic maps into a single
 *    portable `Theme` object that `useTheme().applyTheme()` can consume.
 *
 * ─── Pluggability model ───────────────────────────────────────────────────────
 * Themes are composable through palette merging. You only describe what changes:
 *
 *   export const roseTheme = defineTheme({
 *     primary900: '#881337', primary800: '#9f1239',
 *     primary300: '#fda4af', primary950: '#4c0519',
 *   }, 'rose')
 *
 *   // At runtime (per-tenant, post-login, etc.)
 *   const { applyTheme } = useTheme()
 *   applyTheme(defineTheme({ primary900: tenant.brandColor }, tenant.name))
 *
 * ─── Amber secondary: contrast rules ─────────────────────────────────────────
 * Amber (#ffb300) fails as text on white (1.4:1 — never use as a text color).
 * Amber as a *background* paired with navy text gives 13.4:1 — WCAG AAA.
 * This is why `color-secondary-fg` is `primary900` (navy), not `neutral0` (white).
 * The factories enforce this automatically; do not override `color-secondary-fg`
 * to white without providing a high-contrast secondary color.
 *
 * ─── Secondary color ─────────────────────────────────────────────────────────
 * The system supports a secondary brand color. Use it for:
 *   - Secondary CTA buttons (`variant="secondary"` on ChButton)
 *   - Accent UI elements and highlights
 *   - Chart data series 6
 * Override at runtime:
 *   injectCSSVars({ '--ch-color-secondary': '#ffb300' })
 *
 * ─── Status token naming convention ──────────────────────────────────────────
 * Each status color (success/warning/danger/info) ships with a full set:
 *   color-{status}          — primary fill (buttons, icons)
 *   color-{status}-hover    — darkened fill for hover states
 *   color-{status}-dark     — dark border tint (pairs with the fill)
 *   color-{status}-bg       — very light tint (badge/alert backgrounds)
 *   color-{status}-fg       — foreground text ON the light bg (badge text)
 *   color-{status}-border   — subtle border for status containers
 *
 * ─── Chart palette ───────────────────────────────────────────────────────────
 * `color-chart-1` through `color-chart-8` provide a themed 8-color palette.
 * Series 4 uses chartOrange (not warning amber) to avoid visual collision with
 * series 6 (secondary amber brand color). Override individually:
 *   injectCSSVars({ '--ch-color-chart-3': '#custom' })
 */

// ─── Primitive Palette ────────────────────────────────────────────────────────
/**
 * Raw color values with no semantic meaning.
 * Named by hue + lightness step (50 = lightest, 950 = darkest).
 *
 * `as const` freezes the object so TypeScript infers exact string literal types
 * (e.g. `'#00026d'`) rather than just `string`, enabling type-safe autocompletion
 * when referencing palette values in the factories below.
 */
export const palette = {
  // ── Primary brand color (Deep Navy — #00026d) ────────────────────────────
  // Contrast ratios on white: primary900 = 15.2:1 (WCAG AAA) ✓
  // Dark-mode usage: primary300 = 7.8:1 on primary950 ✓
  primary50: '#eef0ff', // Very subtle brand tint
  primary100: '#dde1ff', // Light brand background
  primary200: '#bbc3fe', // Brand borders / muted text
  primary300: '#8f9cf8', // ← dark-mode brand color (7.8:1 on primary950)
  primary400: '#6673ef', // Icons / mid-tones
  primary500: '#4450de', // Mid-point
  primary600: '#2d35be', // Secondary actions
  primary700: '#191fa0', // ← dark border tint (color-primary-dark)
  primary800: '#0c1084', // ← hover state
  primary900: '#00026d', // ★ Main brand color (Deep Navy) — 15.2:1 on white
  primary950: '#000148', // ← dark-mode page bg / extreme contrast

  // ── Secondary brand color (Amber Gold — #ffb300) ─────────────────────────
  // ⚠ Amber fails as standalone text on white (1.4:1 — do NOT use as text).
  //    Use as background only, always with primary900 (navy) text on top.
  //    Amber bg + navy text = 13.4:1 (WCAG AAA) ✓
  // Dark-mode usage: secondary400 = 12.1:1 on primary950 ✓
  secondary50: '#fffde7',
  secondary100: '#fff8c4',
  secondary200: '#ffec80',
  secondary300: '#ffda33',
  secondary400: '#ffc500', // ← dark-mode accent color (12.1:1 on primary950)
  secondary500: '#ffbb00',
  secondary600: '#ffb300', // ★ Main secondary color
  secondary700: '#e09c00', // ← dark border tint + hover
  secondary800: '#c27f00',
  secondary900: '#9a6100',
  secondary950: '#6a4100',

  // ── Neutral grays (Stark and clean) ─────────────────────────────────────
  neutral0: '#ffffff', // Pure white
  neutral50: '#fafafa', // Off-white
  neutral100: '#f4f4f5', // Light gray
  neutral200: '#e4e4e7', // Border gray
  neutral300: '#d4d4d8', // Medium-light border
  neutral400: '#a1a1aa', // Muted gray
  neutral500: '#71717a', // Mid gray
  neutral600: '#52525b', // Medium-dark
  neutral700: '#3f3f46', // Dark gray
  neutral800: '#27272a', // Very dark
  neutral900: '#18181b', // Near-black
  neutral950: '#09090b', // Deepest shade

  // ── Success (Rich Emerald) ───────────────────────────────────────────────
  success50: '#ecfdf5',
  success100: '#d1fae5',
  success200: '#a7f3d0', // ← subtle border for status containers
  success500: '#10b981',
  success600: '#059669', // ★ Primary success
  success700: '#047857', // ← dark border tint + fg text on light bg

  // ── Warning (Deep Amber) ────────────────────────────────────────────────
  warning50: '#fffbeb',
  warning100: '#fef3c7',
  warning200: '#fde68a', // ← subtle border for status containers
  warning500: '#f59e0b',
  warning600: '#d97706', // ★ Primary warning
  warning700: '#b45309', // ← dark border tint + fg text on light bg

  // ── Danger (Sharp Crimson) ───────────────────────────────────────────────
  danger50: '#fef2f2',
  danger100: '#fee2e2',
  danger200: '#fecaca', // ← subtle border for status containers
  danger500: '#ef4444',
  danger600: '#dc2626', // ★ Primary danger
  danger700: '#b91c1c', // ← dark border tint + fg text on light bg

  // ── Info (Electric Blue) ────────────────────────────────────────────────
  info50: '#eff6ff',
  info100: '#dbeafe',
  info200: '#bfdbfe', // ← subtle border for status containers
  info500: '#3b82f6',
  info600: '#2563eb', // ★ Primary info
  info700: '#1d4ed8', // ← dark border tint + fg text on light bg

  // ── Extended chart hues ─────────────────────────────────────────────────
  // With amber as the secondary brand color, chart series 4 uses orange
  // (not warning amber) to keep all 8 chart series visually distinct.
  chartTeal: '#14b8a6', // teal   — chart series 7
  chartOrange: '#f97316', // orange — chart series 4 (amber-distinct)
  chartPink: '#ec4899', // pink   — chart series 8
} as const

// ─── Palette Type ──────────────────────────────────────────────────────────────
/**
 * A record with the same keys as the default palette but accepting any string value.
 *
 * Use this when creating custom palettes for `defineTheme()`. You only need to
 * provide the values you're changing — `defineTheme` merges the rest from the
 * default palette automatically.
 *
 * @example
 * const rosePalette: Partial<Palette> = {
 *   primary900: '#881337',
 *   primary800: '#9f1239',
 *   primary300: '#fda4af',
 *   primary950: '#4c0519',
 * }
 */
export type Palette = Record<keyof typeof palette, string>

// ─── Light Mode Semantic Factory ──────────────────────────────────────────────
/**
 * Generates the full set of semantic color tokens for LIGHT MODE from a palette.
 *
 * Making this a factory (vs hardcoding against the default palette) means the
 * entire semantic layer is swappable: pass any `Palette`-shaped object and get
 * a fully themed semantic map. This is what makes `defineTheme()` possible.
 *
 * Notable decisions:
 * - `color-secondary-fg` → `p.primary900` (navy), NOT `p.neutral0` (white).
 *   Amber has 1.4:1 contrast with white — completely unusable. Navy = 13.4:1.
 * - `color-primary-subtle` → `p.primary50` (brand-tinted) not neutral gray.
 *   Ghost button hover states feel on-brand rather than generic.
 * - `color-chart-4` → `p.chartOrange` not warning amber, to avoid amber collision
 *   with `color-chart-6` (secondary amber brand color).
 * - `color-border-focus` → `p.primary900` for a strong, brand-colored focus ring.
 */
export function createSemanticColors(p: Palette) {
  return {
    // ── Brand / Primary ─────────────────────────────────────────────────────
    'color-primary': p.primary900,
    'color-primary-hover': p.primary800,
    'color-primary-active': p.primary950,
    'color-primary-dark': p.primary700, // dark border tint (e.g. button borders)
    'color-primary-subtle': p.primary50, // brand-tinted bg for ghost/hover states
    'color-primary-muted': p.primary100,
    'color-primary-fg': p.neutral0, // white text on dark primary bg

    // ── Brand / Secondary ────────────────────────────────────────────────────
    // color-secondary-fg MUST be dark (navy) — amber backgrounds require it.
    // Changing this to neutral0 will fail WCAG AA for any amber-value secondary.
    'color-secondary': p.secondary600,
    'color-secondary-hover': p.secondary700,
    'color-secondary-active': p.secondary800,
    'color-secondary-dark': p.secondary700,
    'color-secondary-subtle': p.secondary50,
    'color-secondary-muted': p.secondary100,
    'color-secondary-fg': p.primary900, // ⚠ navy on amber — do NOT set to neutral0

    // ── Page & Surface Backgrounds ────────────────────────────────────────────
    'color-bg': p.neutral50, // slightly off-white page bg
    'color-bg-subtle': p.neutral100,
    'color-bg-muted': p.neutral200,
    'color-surface': p.neutral0, // pure white cards/panels
    'color-surface-raised': p.neutral0,
    'color-surface-overlay': p.neutral0,

    // ── Borders ───────────────────────────────────────────────────────────────
    'color-border': p.neutral200,
    'color-border-strong': p.neutral900, // high-contrast borders
    'color-border-focus': p.primary900, // brand-colored focus ring

    // ── Typography ────────────────────────────────────────────────────────────
    'color-text': p.neutral900,
    'color-text-muted': p.neutral500,
    'color-text-subtle': p.neutral400,
    'color-text-disabled': p.neutral300,
    'color-text-inverse': p.neutral0, // white text on dark fills
    'color-text-on-primary': p.neutral0, // white text on primary-colored fills

    // ── Status: Success ───────────────────────────────────────────────────────
    'color-success': p.success600,
    'color-success-hover': p.success700,
    'color-success-dark': p.success700,
    'color-success-bg': p.success50,
    'color-success-fg': p.success700,
    'color-success-border': p.success200,

    // ── Status: Warning ───────────────────────────────────────────────────────
    'color-warning': p.warning600,
    'color-warning-hover': p.warning700,
    'color-warning-dark': p.warning700,
    'color-warning-bg': p.warning50,
    'color-warning-fg': p.warning700,
    'color-warning-border': p.warning200,

    // ── Status: Danger ────────────────────────────────────────────────────────
    'color-danger': p.danger600,
    'color-danger-hover': p.danger700,
    'color-danger-dark': p.danger700,
    'color-danger-bg': p.danger50,
    'color-danger-fg': p.danger700,
    'color-danger-border': p.danger200,

    // ── Status: Info ──────────────────────────────────────────────────────────
    'color-info': p.info600,
    'color-info-hover': p.info700,
    'color-info-dark': p.info700,
    'color-info-bg': p.info50,
    'color-info-fg': p.info700,
    'color-info-border': p.info200,

    // ── Tooltip ───────────────────────────────────────────────────────────────
    'color-tooltip': p.neutral900,
    'color-tooltip-fg': p.neutral0,

    // ── Overlay ───────────────────────────────────────────────────────────────
    // Stored as rgb() to preserve the alpha channel when injected as a CSS var.
    'color-overlay': 'rgb(0 0 0 / 0.5)',

    // ── Shadows ───────────────────────────────────────────────────────────────
    // Storing the shadow color as a semantic token (rather than hardcoding it
    // inside spacing.ts) means the brutalism hard-offset shadows automatically
    // adapt to dark mode. spacing.ts references this via var(--ch-color-shadow).
    // Light mode: near-black at 75% opacity on white/light surfaces.
    'color-shadow': 'rgba(0, 0, 0, 0.75)',

    // ── Chart Palette ─────────────────────────────────────────────────────────
    // 8 perceptually distinct series. Series 4 = orange (not warning amber) to
    // avoid collision with series 6 (secondary amber brand color).
    'color-chart-1': p.primary900, // navy    — series 1
    'color-chart-2': p.info600, // blue    — series 2
    'color-chart-3': p.success600, // green   — series 3
    'color-chart-4': p.chartOrange, // orange  — series 4
    'color-chart-5': p.danger600, // red     — series 5
    'color-chart-6': p.secondary600, // amber   — series 6
    'color-chart-7': p.chartTeal, // teal    — series 7
    'color-chart-8': p.chartPink, // pink    — series 8
  }
}

// ─── Dark Mode Semantic Factory ───────────────────────────────────────────────
/**
 * Generates the full set of semantic color tokens for DARK MODE from a palette.
 *
 * Dark mode strategy:
 * - Page and surface backgrounds use deep navy tints (primary950/900/800) instead
 *   of generic neutral black. The brand color IS the dark background — this creates
 *   a cohesive, immersive feel rather than a generic "lights off" effect.
 * - Primary interactive color shifts from primary900 to primary300 (7.8:1 on dark bg).
 * - Secondary amber shifts from secondary600 to secondary400 for more vibrancy
 *   on dark surfaces (12.1:1 on primary950 — WCAG AAA ✓).
 * - secondary-fg stays primary950 (navy) — amber always needs dark text regardless
 *   of light/dark mode.
 * - Status bg/border tokens invert: dark fill + light text replaces light fill + dark text.
 * - Focus ring uses secondary400 (amber) — highly visible against dark navy surfaces.
 *
 * Dark elevation scale (most elevated → least elevated):
 *   primary800 (raised/overlay) → primary900 (surface/subtle bg) → primary950 (page bg)
 */
export function createDarkSemanticColors(p: Palette) {
  return {
    // ── Brand / Primary (lightened for dark backgrounds) ─────────────────────
    'color-primary': p.primary300, // 7.8:1 on primary950 ✓
    'color-primary-hover': p.primary200,
    'color-primary-active': p.primary100,
    'color-primary-dark': p.primary400,
    'color-primary-subtle': p.primary800, // dark navy as subtle hover bg
    'color-primary-muted': p.primary700,
    'color-primary-fg': p.primary950, // near-black on light primary300

    // ── Brand / Secondary (brighter amber for dark backgrounds) ──────────────
    // Amber still needs dark navy text — brighter stop, same fg rule applies.
    'color-secondary': p.secondary400, // 12.1:1 on primary950 ✓
    'color-secondary-hover': p.secondary300,
    'color-secondary-active': p.secondary200,
    'color-secondary-dark': p.secondary500,
    'color-secondary-subtle': p.secondary950, // very dark amber tint as subtle bg
    'color-secondary-muted': p.secondary900,
    'color-secondary-fg': p.primary950, // ⚠ navy on amber — required for contrast

    // ── Page & Surface Backgrounds (navy-tinted, not neutral black) ───────────
    // Using brand-tinted darks makes the dark theme feel intentional and premium
    // rather than generic. The page bg IS the brand's darkest shade.
    // Elevation scale: primary950 (deepest) → primary900 → primary800 (most raised)
    'color-bg': p.primary950, // #000148 — deep navy page bg
    'color-bg-subtle': p.primary900, // #00026d — slightly elevated sections
    'color-bg-muted': p.primary800, // #0c1084 — muted/disabled sections
    'color-surface': p.primary900, // #00026d — card/panel surfaces
    'color-surface-raised': p.primary800, // #0c1084 — elevated cards/dropdowns
    'color-surface-overlay': p.primary800, // #0c1084 — modals/popovers

    // ── Borders ───────────────────────────────────────────────────────────────
    'color-border': p.primary700, // subtle navy border
    'color-border-strong': p.primary300, // lighter navy — softer than pure white
    'color-border-focus': p.secondary400, // amber focus ring — highly visible on dark

    // ── Typography ────────────────────────────────────────────────────────────
    'color-text': p.neutral50,
    'color-text-muted': p.neutral400,
    'color-text-subtle': p.neutral500,
    'color-text-disabled': p.neutral600,
    'color-text-inverse': p.neutral900,
    'color-text-on-primary': p.primary950, // dark text on light primary300

    // ── Status: Success ───────────────────────────────────────────────────────
    'color-success': p.success500,
    'color-success-hover': p.success600,
    'color-success-dark': p.success600,
    'color-success-bg': p.success700, // dark fill, not light fill
    'color-success-fg': p.success100, // light text on dark success bg
    'color-success-border': p.success700,

    // ── Status: Warning ───────────────────────────────────────────────────────
    'color-warning': p.warning500,
    'color-warning-hover': p.warning600,
    'color-warning-dark': p.warning600,
    'color-warning-bg': p.warning700,
    'color-warning-fg': p.warning100,
    'color-warning-border': p.warning700,

    // ── Status: Danger ────────────────────────────────────────────────────────
    'color-danger': p.danger500,
    'color-danger-hover': p.danger600,
    'color-danger-dark': p.danger600,
    'color-danger-bg': p.danger700,
    'color-danger-fg': p.danger100,
    'color-danger-border': p.danger700,

    // ── Status: Info ──────────────────────────────────────────────────────────
    'color-info': p.info500,
    'color-info-hover': p.info600,
    'color-info-dark': p.info600,
    'color-info-bg': p.info700,
    'color-info-fg': p.info100,
    'color-info-border': p.info700,

    // ── Tooltip ───────────────────────────────────────────────────────────────
    'color-tooltip': p.neutral100, // light bg in dark mode
    'color-tooltip-fg': p.neutral900,

    // ── Overlay ───────────────────────────────────────────────────────────────
    'color-overlay': 'rgb(0 0 0 / 0.65)', // more opaque in dark mode

    // ── Shadows ───────────────────────────────────────────────────────────────
    // Inverted for dark navy surfaces: near-white with low opacity preserves
    // the brutalism hard-edge aesthetic while remaining visible on dark backgrounds.
    // rgba(0,0,0,0.75) on #000148 is effectively invisible — this fixes that.
    'color-shadow': 'rgba(255, 255, 255, 0.25)',

    // ── Chart Palette (adjusted for dark backgrounds) ─────────────────────────
    'color-chart-1': p.primary300, // lighter navy — readable on dark bg
    'color-chart-2': p.info500,
    'color-chart-3': p.success500,
    'color-chart-4': p.chartOrange, // orange — unchanged, reads well on dark
    'color-chart-5': p.danger500,
    'color-chart-6': p.secondary400, // brighter amber on dark
    'color-chart-7': p.chartTeal,
    'color-chart-8': p.chartPink,
  }
}

// ─── Default Instances (backward-compatible exports) ──────────────────────────
/**
 * The default light-mode semantic color map, built from the base palette.
 * Components reference these via CSS custom properties (--ch-color-*).
 *
 * Prefer `defaultTheme.light` in new code — the `Theme` object keeps
 * light and dark co-located and is the currency of `useTheme().applyTheme()`.
 */
export const semanticColors = createSemanticColors(palette)

/**
 * The default dark-mode semantic color map, built from the base palette.
 * Prefer `defaultTheme.dark` in new code.
 */
export const darkSemanticColors = createDarkSemanticColors(palette)

// ─── Theme Definition ─────────────────────────────────────────────────────────
/**
 * A complete, portable theme: a palette plus its derived light and dark semantic
 * token maps. Created by `defineTheme()` and consumed by `useTheme().applyTheme()`.
 *
 * Keeping light and dark co-located on the Theme object means:
 *   - Dark mode switching is always theme-aware — no hardcoded fallback colors.
 *   - Themes can be stored, versioned, and swapped atomically.
 *   - User overrides (via `applyOverrides`) layer on top without touching the theme.
 */
export interface Theme {
  /** Human-readable identifier, used for debugging and DevTools labelling. */
  name: string
  /** The resolved primitive palette this theme was derived from. */
  palette: Palette
  /** Semantic token map for light mode. */
  light: ReturnType<typeof createSemanticColors>
  /** Semantic token map for dark mode. */
  dark: ReturnType<typeof createDarkSemanticColors>
}

/**
 * Creates a `Theme` by merging palette overrides with the default palette,
 * then running both semantic factories over the result.
 *
 * Only pass the values you want to change — everything else inherits from the
 * default palette. The factories automatically derive all semantic tokens
 * (including contrast-correct secondary-fg values) from the merged palette.
 *
 * @param overrides - Partial palette to merge over `palette` defaults.
 * @param name      - Human-readable theme name (default: `'custom'`).
 *
 * @example Minimal brand swap — only primary colors changed
 * export const roseTheme = defineTheme({
 *   primary50:  '#fff1f2', primary100: '#ffe4e6',
 *   primary200: '#fecdd3', primary300: '#fda4af',
 *   primary700: '#be123c', primary800: '#9f1239',
 *   primary900: '#881337', primary950: '#4c0519',
 * }, 'rose')
 *
 * @example Per-tenant runtime theme (single color, full theme derived automatically)
 * const { applyTheme } = useTheme()
 * const tenantTheme = defineTheme({ primary900: tenant.brandColor }, tenant.name)
 * applyTheme(tenantTheme)
 *
 * @example Swap both brand colors entirely
 * export const forestTheme = defineTheme({
 *   primary900: '#14532d', primary300: '#86efac',  // green primary
 *   secondary600: '#b45309', secondary400: '#fbbf24', // earthy secondary
 * }, 'forest')
 */
export function defineTheme(overrides: Partial<Palette> = {}, name = 'custom'): Theme {
  const merged = { ...palette, ...overrides } as Palette
  return {
    name,
    palette: merged,
    light: createSemanticColors(merged),
    dark: createDarkSemanticColors(merged),
  }
}

/** The default theme, built from the base palette. */
export const defaultTheme = defineTheme({}, 'default')

// ─── Types ────────────────────────────────────────────────────────────────────
/**
 * Union type of all valid semantic color token names (light and dark share the same keys).
 * Useful for strongly-typing any function that accepts a color token key.
 *
 * @example
 * function applyColor(token: SemanticColor) { ... }
 */
export type SemanticColor = keyof ReturnType<typeof createSemanticColors>
