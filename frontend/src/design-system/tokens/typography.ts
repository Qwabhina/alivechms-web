/**
 * @file typography.ts
 * @path /frontend/src/design-system/tokens/typography.ts
 * @description Typography design tokens for the design system.
 *
 * Covers:
 * - Font family stacks (with web-safe fallbacks)
 * - A fluid type scale (rem-based, 16px root)
 * - Font weight constants
 * - Line height scale
 * - Letter spacing scale
 *
 * All values become CSS custom properties prefixed with `--ch-`
 * via the token injection system in `tokens/index.ts`.
 *
 * Font loading:
 * You need to load the actual font files separately.
 * Recommended approach — add to your `index.html`:
 *
 * @example
 * <link rel="preconnect" href="https://fonts.googleapis.com" />
 * <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:wght@400;600&family=JetBrains+Mono&display=swap" rel="stylesheet" />
 */

/**
 * All typography-related design tokens.
 * `as const` ensures TypeScript infers exact literal types for each value.
 */
export const typography = {
  // ── Font Family Stacks ──
  // Each value is a CSS font stack: preferred font first, then fallbacks.
  // If the preferred font isn't loaded, the browser falls back gracefully.

  /**
   * `font-sans` — used for all UI text: labels, body copy, buttons, inputs.
   * Plus Jakarta Sans is geometric, modern, and highly legible at small sizes.
   * Inter is the web-safe fallback if Jakarta isn't loaded.
   */
  'font-sans':    '"Plus Jakarta Sans", "Inter", ui-sans-serif, system-ui, sans-serif',

  /**
   * `font-display` — used for headings (h1–h6) and decorative large type.
   * Lora is a serif with warmth and elegance — fitting for a church context.
   * Georgia is the web-safe fallback.
   */
  'font-display': '"Lora", "Georgia", ui-serif, serif',

  /**
   * `font-mono` — used for code, IDs, technical strings.
   * JetBrains Mono has excellent readability and distinguishable characters.
   */
  'font-mono':    '"JetBrains Mono", "Cascadia Code", ui-monospace, monospace',

  // ── Type Scale ──
  // All sizes are in `rem` units. At a 16px root font size:
  //   0.75rem = 12px, 1rem = 16px, 1.5rem = 24px, etc.
  //
  // Using rem (not px) is important: it respects the user's browser
  // font-size preference, which is a key accessibility feature.

  'text-xs':   '0.75rem',    // 12px — captions, timestamps, fine print
  'text-sm':   '0.875rem',   // 14px — secondary text, table cells, labels
  'text-base': '1rem',       // 16px — default body text size
  'text-md':   '1.0625rem',  // 17px — slightly larger body (comfortable reading)
  'text-lg':   '1.125rem',   // 18px — slightly emphasized body copy
  'text-xl':   '1.25rem',    // 20px — sub-headings, card titles
  'text-2xl':  '1.5rem',     // 24px — section headings
  'text-3xl':  '1.875rem',   // 30px — page headings
  'text-4xl':  '2.25rem',    // 36px — hero text, display headings

  // ── Font Weights ──
  // Named to match CSS keyword equivalents for clarity.
  // These map to numeric values the browser uses internally.

  'font-normal':   '400', // Regular — body text, most UI copy
  'font-medium':   '500', // Slightly heavier — labels, button text, nav items
  'font-semibold': '600', // Bold-ish — headings, emphasized text
  'font-bold':     '700', // Full bold — strong emphasis, hero headings

  // ── Line Heights ──
  // Controls vertical spacing between lines within a block of text.
  // These are unitless multipliers of the current font size.
  // e.g. `font-size: 16px` + `line-height: 1.5` = 24px line height

  'leading-none':    '1',     // No extra line spacing — display text, icons
  'leading-tight':   '1.25',  // Tight — headings, large display type
  'leading-snug':    '1.375', // Slightly tighter than normal — sub-headings
  'leading-normal':  '1.5',   // Default comfortable reading line height
  'leading-relaxed': '1.625', // More spacious — long-form body copy
  'leading-loose':   '2',     // Very open — specific decorative uses

  // ── Letter Spacing (Tracking) ──
  // Controls horizontal spacing between characters.
  // Negative tracking tightens display headings for a polished look.
  // Wider tracking is used for uppercase labels and caps-style text.

  'tracking-tight':   '-0.025em', // Tightened — large headings, display type
  'tracking-normal':  '0em',      // No adjustment — default for body text
  'tracking-wide':    '0.025em',  // Slightly open — readable at small sizes
  'tracking-wider':   '0.05em',   // Wide — uppercase labels, badge text
  'tracking-widest':  '0.1em',    // Very wide — all-caps decorative labels
} as const

/**
 * Union type of all valid typography token names.
 *
 * @example
 * function setFont(token: TypographyToken, el: HTMLElement) {
 *   el.style.fontFamily = `var(--ch-${token})`
 * }
 */
export type TypographyToken = keyof typeof typography
