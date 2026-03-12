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
 * literal types (e.g. `'#eef2ff'`) rather than just `string`.
 * This lets us get type-safe autocompletion when referencing palette values.
 */
export const palette = {
  // ── Primary brand color ──
  // Default: indigo/blue. Replace these 11 shades with your church's brand hue.
  // Tip: tools like https://uicolors.app can generate a full scale from one hex.
  primary50:  '#eef2ff', // Near-white tint — used for subtle backgrounds
  primary100: '#e0e7ff', // Very light — used for hover states on light surfaces
  primary200: '#c7d2fe', // Light — used for muted indicators
  primary300: '#a5b4fc', // Medium-light — decorative accents
  primary400: '#818cf8', // Medium — secondary interactive elements
  primary500: '#6366f1', // Mid-point — focus rings, borders
  primary600: '#4f46e5', // ★ Main brand color — buttons, links, active states
  primary700: '#4338ca', // Hover state for primary
  primary800: '#3730a3', // Active/pressed state for primary
  primary900: '#312e81', // Dark — high-contrast text on light backgrounds
  primary950: '#1e1b4b', // Near-black — extreme contrast situations

  // ── Neutral grays ──
  // Used for text, backgrounds, borders, and surfaces.
  // These should remain gray regardless of brand color changes.
  neutral0:   '#ffffff', // Pure white — primary surface color
  neutral50:  '#f8fafc', // Off-white — subtle background areas
  neutral100: '#f1f5f9', // Light gray — muted backgrounds, disabled states
  neutral200: '#e2e8f0', // Border gray — default border color
  neutral300: '#cbd5e1', // Medium-light border — strong borders, dividers
  neutral400: '#94a3b8', // Muted gray — placeholder text, subtle icons
  neutral500: '#64748b', // Mid gray — secondary text, metadata
  neutral600: '#475569', // Medium-dark — body text in some contexts
  neutral700: '#334155', // Dark gray — headings on light backgrounds
  neutral800: '#1e293b', // Very dark — primary text on light backgrounds
  neutral900: '#0f172a', // Near-black — highest contrast text
  neutral950: '#020617', // Deepest shade — used sparingly

  // ── Success (green) ──
  success50:  '#f0fdf4', // Background for success alerts/banners
  success100: '#dcfce7', // Lighter success background
  success500: '#22c55e', // Icon/indicator color
  success600: '#16a34a', // ★ Primary success color — badges, alerts
  success700: '#15803d', // Foreground text on success backgrounds

  // ── Warning (amber) ──
  warning50:  '#fffbeb', // Background for warning alerts/banners
  warning100: '#fef3c7', // Lighter warning background
  warning500: '#f59e0b', // Icon/indicator color
  warning600: '#d97706', // ★ Primary warning color
  warning700: '#b45309', // Foreground text on warning backgrounds

  // ── Danger (rose/red) ──
  danger50:   '#fff1f2', // Background for error alerts/banners
  danger100:  '#ffe4e6', // Lighter danger background
  danger500:  '#f43f5e', // Icon/indicator color
  danger600:  '#e11d48', // ★ Primary danger color — destructive actions, errors
  danger700:  '#be123c', // Foreground text on danger backgrounds

  // ── Info (blue) ──
  info50:     '#eff6ff', // Background for info alerts/banners
  info100:    '#dbeafe', // Lighter info background
  info500:    '#3b82f6', // Icon/indicator color
  info600:    '#2563eb', // ★ Primary info color
  info700:    '#1d4ed8', // Foreground text on info backgrounds
} as const

// ─── Semantic Color Mappings ──────────────────────────────────────────────────
/**
 * Maps semantic intent to primitive palette values.
 *
 * These become CSS custom properties on `:root` via the token injection system.
 * The naming convention is:
 *   `color-{role}-{modifier?}`
 *
 * Where `role` is the *purpose* (primary, bg, text, border, success, etc.)
 * and `modifier` is an optional variant (hover, active, subtle, muted, fg, etc.)
 *
 * Components reference these — never raw hex values or palette keys.
 * This is what makes the entire system themeable from one place.
 */
export const semanticColors = {
  // ── Brand / Primary ──
  // The main interactive color — used on buttons, links, active indicators, etc.
  'color-primary':        palette.primary600, // Default state
  'color-primary-hover':  palette.primary700, // Mouse hover state
  'color-primary-active': palette.primary800, // Click / pressed state
  'color-primary-subtle': palette.primary50,  // Very light tint for backgrounds
  'color-primary-muted':  palette.primary100, // Light tint — focus rings, tag backgrounds
  'color-primary-fg':     palette.neutral0,   // Text/icon drawn ON TOP of primary color

  // ── Page & Surface Backgrounds ──
  // "bg" = the overall page/canvas color
  // "surface" = elevated containers like cards and modals
  'color-bg':             palette.neutral0,   // Page background
  'color-bg-subtle':      palette.neutral50,  // Slightly off-white sections
  'color-bg-muted':       palette.neutral100, // Muted areas: sidebars, table rows
  'color-surface':        palette.neutral0,   // Cards, panels — same as bg in light mode
  'color-surface-raised': palette.neutral0,   // Modals, dropdowns — slightly elevated
  'color-surface-overlay':palette.neutral0,   // Tooltip/popover backgrounds

  // ── Borders ──
  'color-border':         palette.neutral200, // Default border — inputs, cards, dividers
  'color-border-strong':  palette.neutral300, // Emphasized borders — hover, separators
  'color-border-focus':   palette.primary500, // Focus ring color (keyboard navigation)

  // ── Typography ──
  'color-text':           palette.neutral900, // Primary body text
  'color-text-muted':     palette.neutral500, // Secondary text, metadata, labels
  'color-text-subtle':    palette.neutral400, // Placeholder, disabled labels, hints
  'color-text-disabled':  palette.neutral300, // Disabled input text
  'color-text-inverse':   palette.neutral0,   // White text (on dark backgrounds)
  'color-text-on-primary':palette.neutral0,   // Text drawn on primary-colored backgrounds

  // ── Status: Success ──
  'color-success':        palette.success600, // Icon/border color for success states
  'color-success-bg':     palette.success50,  // Background of success alerts
  'color-success-fg':     palette.success700, // Text color inside success alerts

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

/**
 * Union type of all valid semantic color token names.
 * Useful for strongly-typing any function that accepts a color token key.
 *
 * @example
 * function applyColor(token: SemanticColor) { ... }
 */
export type SemanticColor = keyof typeof semanticColors
