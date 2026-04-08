/**
 * @file colors.ts
 * @path /frontend/src/design-system/tokens/colors.ts
 * @description Design token definitions for the color system.
 *
 * The color system is organized in two layers:
 *
 * 1. **Primitive palette** (`palette`)
 *    Raw, named color values — no meaning attached yet.
 *    Think of these as your "paint swatches". You can swap these
 *    entirely if your brand changes.
 *
 * 2. **Semantic mappings** (`semanticColors`)
 *    These map *intent* to a primitive value. A semantic token answers
 *    the question "what is this color FOR?" rather than "what color IS it?".
 *    e.g. `color-primary` means "the main action color" — it just happens
 *    to resolve to a specific hex right now.
 *
 * The two-layer approach means:
 *   - To retheme the whole system → change `semanticColors` mappings
 *   - To add a new shade → add it to `palette`
 *   - Components only ever reference semantic tokens, never raw hex values
 *
 * ─── Secondary color ─────────────────────────────────────────────────────────
 * The system supports a secondary brand color out of the box. Use it for:
 *   - Secondary CTA buttons (`variant="secondary"` once added to ChButton)
 *   - Accent UI elements and highlights
 *   - Chart data series 6
 * Override at runtime:
 *   injectCSSVars({ '--ch-color-secondary': '#7c3aed' })
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
 * `color-chart-1` through `color-chart-8` provide a themed 8-color palette
 * for multi-dataset charts. All pull from semantic/primitive tokens so they
 * stay in harmony with the active theme. Override individually for custom
 * data visualization branding.
 */

// ─── Primitive Palette ────────────────────────────────────────────────────────
/**
 * Raw color values with no semantic meaning.
 * Named by hue + lightness step (50 = lightest, 950 = darkest).
 *
 * `as const` freezes the object so TypeScript infers the exact string
 * literal types (e.g. `'#fafafa'`) rather than just `string`.
 * This lets us get type-safe autocompletion when referencing palette values.
 */
export const palette = {
  // ── Primary brand color (Deep Slate/Black for a sharp, premium feel) ──
  primary50: '#f8fafc', // Very subtle tint
  primary100: '#f1f5f9', // Light background
  primary200: '#e2e8f0', // Borders
  primary300: '#cbd5e1', // Muted text
  primary400: '#94a3b8', // Icons
  primary500: '#64748b', // Mid-point
  primary600: '#475569', // Secondary actions
  primary700: '#334155', // ← border tint for color-primary-dark
  primary800: '#1e293b', // ← hover state
  primary900: '#0f172a', // ★ Main brand color (Deep Slate)
  primary950: '#020617', // Near-black for extreme contrast

  // ── Secondary brand color (Violet — vibrant complementary accent) ──
  // Used for secondary CTAs, accents, and chart series 6.
  // Override with: injectCSSVars({ '--ch-color-secondary': yourColor })
  secondary50: '#f5f3ff',
  secondary100: '#ede9fe',
  secondary200: '#ddd6fe',
  secondary300: '#c4b5fd',
  secondary400: '#a78bfa',
  secondary500: '#8b5cf6',
  secondary600: '#7c3aed', // ★ Main secondary color
  secondary700: '#6d28d9', // ← border tint + hover
  secondary800: '#5b21b6',
  secondary900: '#4c1d95',
  secondary950: '#2e1065',

  // ── Neutral grays (Stark and clean) ──
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

  // ── Success (Rich Emerald) ──
  success50: '#ecfdf5',
  success100: '#d1fae5',
  success200: '#a7f3d0', // ← subtle border for status containers
  success500: '#10b981',
  success600: '#059669', // ★ Primary success
  success700: '#047857', // ← dark border tint + fg text on light bg

  // ── Warning (Deep Amber) ──
  warning50: '#fffbeb',
  warning100: '#fef3c7',
  warning200: '#fde68a', // ← subtle border for status containers
  warning500: '#f59e0b',
  warning600: '#d97706', // ★ Primary warning
  warning700: '#b45309', // ← dark border tint + fg text on light bg

  // ── Danger (Sharp Crimson) ──
  danger50: '#fef2f2',
  danger100: '#fee2e2',
  danger200: '#fecaca', // ← subtle border for status containers
  danger500: '#ef4444',
  danger600: '#dc2626', // ★ Primary danger
  danger700: '#b91c1c', // ← dark border tint + fg text on light bg

  // ── Info (Electric Blue) ──
  info50: '#eff6ff',
  info100: '#dbeafe',
  info200: '#bfdbfe', // ← subtle border for status containers
  info500: '#3b82f6',
  info600: '#2563eb', // ★ Primary info
  info700: '#1d4ed8', // ← dark border tint + fg text on light bg

  // ── Extended chart hues (beyond the 5 semantic status colors) ──
  // These live in the primitive palette so chart tokens can reference them
  // without depending on anything outside this file.
  chartViolet: '#8b5cf6', // violet — chart series 7
  chartPink: '#ec4899', // pink   — chart series 8
} as const

// ─── Semantic Color Mappings ──────────────────────────────────────────────────
/**
 * Maps semantic intent to primitive palette values.
 *
 * These become CSS custom properties on `:root` via the token injection system.
 * The naming convention is:
 *   `color-{role}-{modifier?}`
 */
export const semanticColors = {
  // ── Brand / Primary ──────────────────────────────────────────────────────────
  'color-primary': palette.primary900, // Very dark slate/black
  'color-primary-hover': palette.primary800,
  'color-primary-active': palette.primary950,
  'color-primary-dark': palette.primary700, // Darker border tint (e.g. button borders)
  'color-primary-subtle': palette.neutral100, // Light gray for crisp contrast
  'color-primary-muted': palette.neutral200,
  'color-primary-fg': palette.neutral0, // White text on dark primary

  // ── Brand / Secondary ─────────────────────────────────────────────────────────
  // A distinct accent color for secondary CTAs, highlights, and chart accents.
  // Defaults to violet but is fully overridable at runtime via injectCSSVars.
  'color-secondary': palette.secondary600,
  'color-secondary-hover': palette.secondary700,
  'color-secondary-active': palette.secondary800,
  'color-secondary-dark': palette.secondary700, // Darker border tint
  'color-secondary-subtle': palette.secondary50, // Very light tint
  'color-secondary-muted': palette.secondary200, // Light tint
  'color-secondary-fg': palette.neutral0, // White text on violet secondary

  // ── Page & Surface Backgrounds ────────────────────────────────────────────────
  'color-bg': palette.neutral50, // Slightly off-white for page background
  'color-bg-subtle': palette.neutral100,
  'color-bg-muted': palette.neutral200,
  'color-surface': palette.neutral0, // Pure white for cards to pop
  'color-surface-raised': palette.neutral0,
  'color-surface-overlay': palette.neutral0,

  // ── Borders ───────────────────────────────────────────────────────────────────
  'color-border': palette.neutral200,
  'color-border-strong': palette.neutral900, // High contrast borders
  'color-border-focus': palette.primary900, // Brand-colored focus ring

  // ── Typography ────────────────────────────────────────────────────────────────
  'color-text': palette.neutral900, // Near black
  'color-text-muted': palette.neutral500,
  'color-text-subtle': palette.neutral400,
  'color-text-disabled': palette.neutral300,
  'color-text-inverse': palette.neutral0, // White — for text placed on dark fills
  'color-text-on-primary': palette.neutral0,

  // ── Status: Success ───────────────────────────────────────────────────────────
  'color-success': palette.success600,
  'color-success-hover': palette.success700, // Darker fill for hover states
  'color-success-dark': palette.success700, // Dark border tint (e.g. button borders)
  'color-success-bg': palette.success50, // Light bg for badges / alerts
  'color-success-fg': palette.success700, // Text on success-bg (dark green on light)
  'color-success-border': palette.success200, // Subtle border for success containers

  // ── Status: Warning ───────────────────────────────────────────────────────────
  'color-warning': palette.warning600,
  'color-warning-hover': palette.warning700,
  'color-warning-dark': palette.warning700,
  'color-warning-bg': palette.warning50,
  'color-warning-fg': palette.warning700,
  'color-warning-border': palette.warning200,

  // ── Status: Danger ────────────────────────────────────────────────────────────
  'color-danger': palette.danger600,
  'color-danger-hover': palette.danger700,
  'color-danger-dark': palette.danger700, // Dark border tint (was missing — now fixed)
  'color-danger-bg': palette.danger50,
  'color-danger-fg': palette.danger700, // Text on danger-bg (dark red on light red)
  'color-danger-border': palette.danger200, // Subtle border for danger containers

  // ── Status: Info ──────────────────────────────────────────────────────────────
  'color-info': palette.info600,
  'color-info-hover': palette.info700,
  'color-info-dark': palette.info700,
  'color-info-bg': palette.info50,
  'color-info-fg': palette.info700,
  'color-info-border': palette.info200,

  // ── Tooltip ───────────────────────────────────────────────────────────────────
  'color-tooltip': palette.neutral900, // Near-black background
  'color-tooltip-fg': palette.neutral0, // White text

  // ── Overlay (modal / drawer / popover backdrops) ──────────────────────────────
  // All components that need a scrim (ChModal, ChSidebar, ChCommandPalette, etc.)
  // should reference this token rather than hardcoding rgb() values.
  // The raw rgb() value preserves the alpha channel when injected as a CSS var.
  'color-overlay': 'rgb(0 0 0 / 0.5)',

  // ── Chart Palette ─────────────────────────────────────────────────────────────
  // 8-color palette sourced from semantic and primitive tokens.
  // ChChart reads these at mount time so they always reflect the active theme.
  // Series 1–5 map to semantic colors; 6 = secondary; 7–8 = extended hues.
  // Override any series: injectCSSVars({ '--ch-color-chart-3': '#custom' })
  'color-chart-1': palette.primary900, // series 1 — primary brand
  'color-chart-2': palette.info600, // series 2 — blue
  'color-chart-3': palette.success600, // series 3 — green
  'color-chart-4': palette.warning600, // series 4 — amber
  'color-chart-5': palette.danger600, // series 5 — red
  'color-chart-6': palette.secondary600, // series 6 — violet (secondary brand)
  'color-chart-7': palette.chartViolet, // series 7 — violet (light)
  'color-chart-8': palette.chartPink, // series 8 — pink
} as const

// ─── Dark Mode Semantic Colors ────────────────────────────────────────────────
export const darkSemanticColors = {
  // ── Brand / Primary ──
  'color-primary': palette.neutral0, // White in dark mode
  'color-primary-hover': palette.neutral200,
  'color-primary-active': palette.neutral300,
  'color-primary-dark': palette.neutral400, // Softer dark border tint
  'color-primary-subtle': palette.neutral800,
  'color-primary-muted': palette.neutral700,
  'color-primary-fg': palette.neutral900, // Dark text on white primary

  // ── Brand / Secondary ──
  'color-secondary': palette.secondary400, // Lighter hue for dark bg
  'color-secondary-hover': palette.secondary300,
  'color-secondary-active': palette.secondary200,
  'color-secondary-dark': palette.secondary500,
  'color-secondary-subtle': palette.secondary950,
  'color-secondary-muted': palette.secondary900,
  'color-secondary-fg': palette.neutral900, // Dark text on light secondary

  // ── Page & Surface Backgrounds ──
  'color-bg': palette.neutral950,
  'color-bg-subtle': palette.neutral900,
  'color-bg-muted': palette.neutral800,
  'color-surface': palette.neutral900,
  'color-surface-raised': palette.neutral800,
  'color-surface-overlay': palette.neutral800,

  // ── Borders ──
  'color-border': palette.neutral800,
  'color-border-strong': palette.neutral300, // Softer than pure white (was neutral0)
  'color-border-focus': palette.neutral0,

  // ── Typography ──
  'color-text': palette.neutral0,
  'color-text-muted': palette.neutral400,
  'color-text-subtle': palette.neutral500,
  'color-text-disabled': palette.neutral600,
  'color-text-inverse': palette.neutral900,
  'color-text-on-primary': palette.neutral900,

  // ── Status: Success ──
  'color-success': palette.success500,
  'color-success-hover': palette.success600,
  'color-success-dark': palette.success600,
  'color-success-bg': palette.success700, // Dark bg for badges in dark mode
  'color-success-fg': palette.success100, // Light text on dark success bg
  'color-success-border': palette.success700,

  // ── Status: Warning ──
  'color-warning': palette.warning500,
  'color-warning-hover': palette.warning600,
  'color-warning-dark': palette.warning600,
  'color-warning-bg': palette.warning700,
  'color-warning-fg': palette.warning100,
  'color-warning-border': palette.warning700,

  // ── Status: Danger ──
  'color-danger': palette.danger500,
  'color-danger-hover': palette.danger600,
  'color-danger-dark': palette.danger600,
  'color-danger-bg': palette.danger700,
  'color-danger-fg': palette.danger100,
  'color-danger-border': palette.danger700,

  // ── Status: Info ──
  'color-info': palette.info500,
  'color-info-hover': palette.info600,
  'color-info-dark': palette.info600,
  'color-info-bg': palette.info700,
  'color-info-fg': palette.info100,
  'color-info-border': palette.info700,

  // ── Tooltip ──
  'color-tooltip': palette.neutral100, // Light background in dark mode
  'color-tooltip-fg': palette.neutral900,

  // ── Overlay ──
  'color-overlay': 'rgb(0 0 0 / 0.65)', // Slightly more opaque in dark mode

  // ── Chart palette (adjusted for dark backgrounds) ──
  'color-chart-1': palette.neutral0, // White for primary series in dark mode
  'color-chart-2': palette.info500,
  'color-chart-3': palette.success500,
  'color-chart-4': palette.warning500,
  'color-chart-5': palette.danger500,
  'color-chart-6': palette.secondary400,
  'color-chart-7': palette.secondary500,
  'color-chart-8': palette.chartPink,
} as const

/**
 * Union type of all valid semantic color token names.
 * Useful for strongly-typing any function that accepts a color token key.
 *
 * @example
 * function applyColor(token: SemanticColor) { ... }
 */
export type SemanticColor = keyof typeof semanticColors
