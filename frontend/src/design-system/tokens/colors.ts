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
 *    entirely if your church's branding changes.
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
  primary50:  '#f8fafc', // Very subtle tint
  primary100: '#f1f5f9', // Light background
  primary200: '#e2e8f0', // Borders
  primary300: '#cbd5e1', // Muted text
  primary400: '#94a3b8', // Icons
  primary500: '#64748b', // Mid-point
  primary600: '#475569', // Secondary actions
  primary700: '#334155', // Hover states
  primary800: '#1e293b', // Active states
  primary900: '#0f172a', // ★ Main brand color (Deep Slate)
  primary950: '#020617', // Near-black for extreme contrast

  // ── Neutral grays (Stark and clean) ──
  neutral0:   '#ffffff', // Pure white
  neutral50:  '#fafafa', // Off-white
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
  success50:  '#ecfdf5',
  success100: '#d1fae5',
  success500: '#10b981',
  success600: '#059669', // ★ Primary success
  success700: '#047857',

  // ── Warning (Deep Amber) ──
  warning50:  '#fffbeb',
  warning100: '#fef3c7',
  warning500: '#f59e0b',
  warning600: '#d97706', // ★ Primary warning
  warning700: '#b45309',

  // ── Danger (Sharp Crimson) ──
  danger50:   '#fef2f2',
  danger100:  '#fee2e2',
  danger500:  '#ef4444',
  danger600:  '#dc2626', // ★ Primary danger
  danger700:  '#b91c1c',

  // ── Info (Electric Blue) ──
  info50:     '#eff6ff',
  info100:    '#dbeafe',
  info500:    '#3b82f6',
  info600:    '#2563eb', // ★ Primary info
  info700:    '#1d4ed8',
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
  // ── Brand / Primary ──
  'color-primary':        palette.primary900, // Very dark slate/black
  'color-primary-hover':  palette.primary800,
  'color-primary-active': palette.primary950,
  'color-primary-subtle': palette.neutral100, // Light gray for crisp contrast
  'color-primary-muted':  palette.neutral200,
  'color-primary-fg':     palette.neutral0,   // White text on dark primary

  // ── Page & Surface Backgrounds ──
  'color-bg':             palette.neutral50,  // Slightly off-white for page background
  'color-bg-subtle':      palette.neutral100,
  'color-bg-muted':       palette.neutral200,
  'color-surface':        palette.neutral0,   // Pure white for cards to pop
  'color-surface-raised': palette.neutral0,
  'color-surface-overlay':palette.neutral0,

  // ── Borders ──
  'color-border':         palette.neutral200,
  'color-border-strong':  palette.neutral900, // High contrast borders
  'color-border-focus':   palette.primary900, // Black/slate focus ring

  // ── Typography ──
  'color-text':           palette.neutral900, // Near black
  'color-text-muted':     palette.neutral500,
  'color-text-subtle':    palette.neutral400,
  'color-text-disabled':  palette.neutral300,
  'color-text-inverse':   palette.neutral0,
  'color-text-on-primary':palette.neutral0,

  // ── Status: Success ──
  'color-success':        palette.success600,
  'color-success-bg':     palette.success50,
  'color-success-fg':     palette.success700,

  // ── Status: Warning ──
  'color-warning':        palette.warning600,
  'color-warning-bg':     palette.warning50,
  'color-warning-fg':     palette.warning700,

  // ── Status: Danger ──
  'color-danger':         palette.danger600,
  'color-danger-bg':      palette.danger50,
  'color-danger-fg':      palette.danger700,

  // ── Status: Info ──
  'color-info':           palette.info600,
  'color-info-bg':        palette.info50,
  'color-info-fg':        palette.info700,
} as const

// ─── Dark Mode Semantic Colors ────────────────────────────────────────────────
export const darkSemanticColors = {
  // ── Brand / Primary ──
  'color-primary':        palette.neutral0,   // White in dark mode
  'color-primary-hover':  palette.neutral200,
  'color-primary-active': palette.neutral300,
  'color-primary-subtle': palette.neutral800,
  'color-primary-muted':  palette.neutral700,
  'color-primary-fg':     palette.neutral900, // Dark text on white primary

  // ── Page & Surface Backgrounds ──
  'color-bg':             palette.neutral950,
  'color-bg-subtle':      palette.neutral900,
  'color-bg-muted':       palette.neutral800,
  'color-surface':        palette.neutral900,
  'color-surface-raised': palette.neutral800,
  'color-surface-overlay':palette.neutral800,

  // ── Borders ──
  'color-border':         palette.neutral800,
  'color-border-strong':  palette.neutral0,   // White borders in dark mode
  'color-border-focus':   palette.neutral0,

  // ── Typography ──
  'color-text':           palette.neutral0,
  'color-text-muted':     palette.neutral400,
  'color-text-subtle':    palette.neutral500,
  'color-text-disabled':  palette.neutral600,
  'color-text-inverse':   palette.neutral900,
  'color-text-on-primary':palette.neutral900,

  // ── Status: Success ──
  'color-success':        palette.success500,
  'color-success-bg':     palette.success700,
  'color-success-fg':     palette.success100,

  // ── Status: Warning ──
  'color-warning':        palette.warning500,
  'color-warning-bg':     palette.warning700,
  'color-warning-fg':     palette.warning100,

  // ── Status: Danger ──
  'color-danger':         palette.danger500,
  'color-danger-bg':      palette.danger700,
  'color-danger-fg':      palette.danger100,

  // ── Status: Info ──
  'color-info':           palette.info500,
  'color-info-bg':        palette.info700,
  'color-info-fg':        palette.info100,
} as const

/**
 * Union type of all valid semantic color token names.
 * Useful for strongly-typing any function that accepts a color token key.
 *
 * @example
 * function applyColor(token: SemanticColor) { ... }
 */
export type SemanticColor = keyof typeof semanticColors
