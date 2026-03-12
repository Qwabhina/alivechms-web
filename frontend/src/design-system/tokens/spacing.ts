/**
 * @file spacing.ts
 * @path /frontend/src/design-system/tokens/spacing.ts
 * @description Spatial design tokens: spacing, border radius, shadows,
 * transitions, and z-index layers.
 *
 * ─── Why a spacing scale? ───────────────────────────────────────────────────
 * Without a scale, developers make arbitrary spacing decisions (17px here,
 * 23px there) that create visual inconsistency across the UI. A shared scale
 * forces every space in the system to come from the same set of steps,
 * making the layout feel harmonious and predictable.
 *
 * ─── The base unit ──────────────────────────────────────────────────────────
 * This scale uses a **4px base grid**. Every step is a multiple of 4:
 *   space-1 = 4px, space-2 = 8px, space-4 = 16px, etc.
 * Why 4px? It divides cleanly by 1, 2, and 4, and aligns with most
 * device pixel ratios (1x, 1.5x, 2x, 3x screens).
 */

// ─── Spacing Scale ────────────────────────────────────────────────────────────
/**
 * A 4px-base spacing scale for padding, margin, gap, and positioning.
 *
 * Naming: `space-{step}` where step loosely maps to multiples of 4px.
 * Underscores in names like `space-0_5` represent the decimal `0.5`
 * (since CSS custom property names can't contain dots).
 *
 * In components, these become:
 *   `padding: var(--ch-space-4)`  → 16px
 *   `gap: var(--ch-space-2)`      → 8px
 */
export const spacing = {
  'space-0':   '0px',       // No space — explicit zero reset
  'space-px':  '1px',       // Single pixel — hairline borders, offsets
  'space-0_5': '0.125rem',  //  2px — micro adjustments
  'space-1':   '0.25rem',   //  4px — icon padding, tight stacking
  'space-1_5': '0.375rem',  //  6px — sm badge padding
  'space-2':   '0.5rem',    //  8px — compact padding (sm inputs, icon gaps)
  'space-2_5': '0.625rem',  // 10px — slightly more than compact
  'space-3':   '0.75rem',   // 12px — sm component padding
  'space-3_5': '0.875rem',  // 14px — between sm and md
  'space-4':   '1rem',      // 16px — ★ default base padding (md inputs, card padding)
  'space-5':   '1.25rem',   // 20px — generous padding (card body)
  'space-6':   '1.5rem',    // 24px — section padding
  'space-7':   '1.75rem',   // 28px — larger section padding
  'space-8':   '2rem',      // 32px — lg card padding, major sections
  'space-10':  '2.5rem',    // 40px — hero sections, page-level padding
  'space-12':  '3rem',      // 48px — generous vertical rhythm
  'space-14':  '3.5rem',    // 56px — large layout spacing
  'space-16':  '4rem',      // 64px — major section separation
  'space-20':  '5rem',      // 80px — page-level section gaps
  'space-24':  '6rem',      // 96px — hero/landing spacing
} as const

// ─── Border Radius ────────────────────────────────────────────────────────────
/**
 * Controls the roundness of corners on components.
 * Consistent use of this scale prevents a mix of sharp and rounded
 * elements that would look visually inconsistent.
 *
 * Usage patterns:
 *   - Inputs, buttons, badges → `radius-lg` or `radius-full`
 *   - Cards, panels           → `radius-xl`
 *   - Modals, large surfaces  → `radius-2xl`
 *   - Avatars, icons          → `radius-full`
 */
export const radius = {
  'radius-none': '0px',      // Sharp corners — tables, code blocks
  'radius-sm':   '0.25rem',  //  4px — very slight rounding (checkboxes)
  'radius-md':   '0.375rem', //  6px — subtle rounding
  'radius-lg':   '0.5rem',   //  8px — ★ default for inputs, buttons
  'radius-xl':   '0.75rem',  // 12px — cards, panels
  'radius-2xl':  '1rem',     // 16px — large cards, modals
  'radius-3xl':  '1.5rem',   // 24px — extra-rounded containers
  'radius-full': '9999px',   // Fully circular — avatars, pills, dots
} as const

// ─── Shadows (Elevation System) ───────────────────────────────────────────────
/**
 * Box shadows that represent the elevation of a surface above the page.
 * Higher elevation = larger, softer shadow.
 *
 * Elevation map:
 *   none  → flat (no depth, e.g. table rows)
 *   xs    → barely lifted (e.g. badge, chip)
 *   sm    → default card
 *   md    → hovered card, dropdown
 *   lg    → modal backdrop, sticky nav
 *   xl    → side drawer, command palette
 *   2xl   → floating action elements
 *   inner → inset well (e.g. pressed input, inset panel)
 */
export const shadows = {
  'shadow-none':  'none',
  'shadow-xs':    '0 1px 2px 0 rgb(0 0 0 / 0.05)',
  'shadow-sm':    '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
  'shadow-md':    '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
  'shadow-lg':    '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
  'shadow-xl':    '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
  'shadow-2xl':   '0 25px 50px -12px rgb(0 0 0 / 0.25)',
  'shadow-inner': 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)',
} as const

// ─── Transitions ──────────────────────────────────────────────────────────────
/**
 * Duration and easing tokens for CSS transitions and animations.
 *
 * ─── Durations ───────────────────────────────────────────────────────────────
 * Use shorter durations for small, frequent interactions (hover, toggle),
 * and longer ones for larger layout changes (modals, page transitions).
 *
 * ─── Easings ─────────────────────────────────────────────────────────────────
 * CSS `cubic-bezier(x1, y1, x2, y2)` defines the acceleration curve:
 *   - `ease-in`     → starts slow, ends fast (entering animations look weighted)
 *   - `ease-out`    → starts fast, ends slow (★ most natural for UI transitions)
 *   - `ease-in-out` → symmetric — slow start and end (good for back/forth)
 *   - `ease-spring` → overshoots slightly, then settles (playful, physical feel)
 *   - `ease-bounce` → more aggressive overshoot (use sparingly — notifications)
 *   - `ease-smooth` → very gentle ease-out (subtle background changes)
 */
export const transitions = {
  // Durations
  'duration-instant':  '50ms',  // Near-instant feedback (toggle switches)
  'duration-fast':     '100ms', // ★ Default hover state transitions
  'duration-normal':   '150ms', // ★ Default component transitions (open/close)
  'duration-moderate': '200ms', // Slightly slower — menus, tooltips
  'duration-slow':     '300ms', // Modal open/close, page transitions
  'duration-slower':   '400ms', // Complex multi-part animations
  'duration-slowest':  '500ms', // Rare — large layout shifts

  // Easings — cubic-bezier values
  'ease-linear':  'linear',                        // Constant speed — progress bars
  'ease-in':      'cubic-bezier(0.4, 0, 1, 1)',    // Accelerates into end
  'ease-out':     'cubic-bezier(0, 0, 0.2, 1)',    // ★ Decelerates to rest (most UI)
  'ease-in-out':  'cubic-bezier(0.4, 0, 0.2, 1)', // Symmetric — sliders, scrubbing
  'ease-spring':  'cubic-bezier(0.34, 1.56, 0.64, 1)', // Slight overshoot — modals
  'ease-bounce':  'cubic-bezier(0.68, -0.55, 0.265, 1.55)', // Playful — badges
  'ease-smooth':  'cubic-bezier(0.25, 0.46, 0.45, 0.94)',   // Very gentle ease-out
} as const

// ─── Z-index Scale ────────────────────────────────────────────────────────────
/**
 * Named z-index layers to prevent the "z-index wars" problem where
 * developers randomly choose high values (z-index: 9999) that conflict.
 *
 * The layers are ordered from lowest to highest:
 *   base → raised → dropdown → sticky → overlay → modal → toast → tooltip
 *
 * Each layer is spaced 100 apart, leaving room to insert something
 * between layers (e.g. z-index: 150 fits between sticky and overlay).
 */
export const zIndex = {
  'z-base':     '0',   // Normal document flow
  'z-raised':   '10',  // Slightly elevated (e.g. a focused input)
  'z-dropdown': '100', // Dropdowns, select menus, date pickers
  'z-sticky':   '200', // Sticky headers, fixed sidebars
  'z-overlay':  '300', // Backdrop/scrim behind modals
  'z-modal':    '400', // Modal dialogs, drawers
  'z-toast':    '500', // Toast notifications (above modals)
  'z-tooltip':  '600', // Tooltips (always on top)
} as const

// ─── Exported Types ───────────────────────────────────────────────────────────
// These union types are used to strongly-type props and utility functions
// that accept token names as arguments.

/** Union of all spacing token names */
export type SpacingToken    = keyof typeof spacing

/** Union of all border-radius token names */
export type RadiusToken     = keyof typeof radius

/** Union of all shadow token names */
export type ShadowToken     = keyof typeof shadows

/** Union of all transition token names */
export type TransitionToken = keyof typeof transitions

/** Union of all z-index token names */
export type ZIndexToken     = keyof typeof zIndex
