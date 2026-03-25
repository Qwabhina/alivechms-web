/**
 * @file useTheme.ts
 * @path /frontend/src/design-system/composables/useTheme.ts
 * @description Composable for runtime theme customization.
 *
 * This composable is the programmatic API for the design token system.
 * While `injectCSSVars()` in `tokens/index.ts` sets the initial theme at
 * app startup, `useTheme` allows you to change theme values AFTER the app
 * is running — without reloading the page.
 *
 * ─── Use cases ───────────────────────────────────────────────────────────────
 * 1. **Per-church branding**: Each church using the system has its own brand
 *    color. When they log in, load their `primaryColor` from the database and
 *    call `applyOverrides({ '--ch-color-primary': church.primaryColor })`.
 *
 * 2. **Dark mode toggle**: Define a dark theme override map and apply it
 *    when the user switches to dark mode.
 *
 * 3. **Accessibility modes**: High contrast mode, large text mode, etc.
 *
 * 4. **Tenant customization**: If this SaaS serves multiple organizations,
 *    each can have a scoped theme on their section of the app.
 *
 * ─── How it works ────────────────────────────────────────────────────────────
 * CSS custom properties on `document.documentElement` (`:root`) cascade
 * to every element on the page. By changing them at runtime with
 * `element.style.setProperty('--ch-color-primary', '#e11d48')`, every
 * component that uses `var(--ch-color-primary)` in its CSS instantly
 * re-renders with the new color — no Vue re-render cycle needed.
 *
 * ─── Singleton pattern ───────────────────────────────────────────────────────
 * `_overrides` is declared OUTSIDE the composable function. This makes it
 * a module-level singleton — all components calling `useTheme()` share the
 * same `_overrides` state. This is intentional: the current theme is global
 * state, not per-component state.
 *
 * @example Per-church branding on login
 * const { applyOverrides } = useTheme()
 *
 * onMounted(async () => {
 *   const church = await fetchChurchSettings(churchId)
 *   applyOverrides({
 *     '--ch-color-primary':       church.brandColor,
 *     '--ch-color-primary-hover': church.brandColorDark,
 *   })
 * })
 *
 * @example Dark mode toggle
 * const { applyOverrides, resetTheme } = useTheme()
 *
 * function toggleDarkMode(enabled: boolean) {
 *   if (enabled) {
 *     applyOverrides({
 *       '--ch-color-bg':      '#0f172a',
 *       '--ch-color-surface': '#1e293b',
 *       '--ch-color-text':    '#f8fafc',
 *       '--ch-color-border':  '#334155',
 *     })
 *   } else {
 *     resetTheme()
 *   }
 * }
 */

import { ref, readonly, watch } from 'vue'
import { generateCSSVars, type ThemeOverrides } from '../tokens'
import { darkSemanticColors } from '../tokens/colors'

// Convert dark semantic colors to CSS custom properties with --ch- prefix
const darkThemeOverrides: ThemeOverrides = Object.entries(darkSemanticColors).reduce(
  (acc, [key, value]) => {
    acc[`--ch-${key}`] = value
    return acc
  },
  {} as ThemeOverrides
)

// ─── Module-level singletons ──────────────────────────────────────────────────
/**
 * The current set of active overrides.
 * Stored as a `ref` so computed properties or watchers can react to changes.
 *
 * Declared outside the composable function so it's shared across ALL
 * component instances that call `useTheme()` — it's truly global state.
 *
 * We export it as `readonly` via the composable to prevent direct mutation
 * (consumers should only change it through the provided functions).
 */
const _overrides = ref<ThemeOverrides>({})

/**
 * Dark mode state — module-level singleton like _overrides.
 *
 * Previously this lived inside useTheme(), which meant every component that
 * called useTheme() created its own isDarkMode ref, ran checkDarkMode(),
 * applyDarkMode(), and added a NEW media query listener. This caused:
 *   1. Listener accumulation (memory leak)
 *   2. Redundant localStorage reads on every useTheme() call
 *   3. Redundant DOM writes (classList, style.setProperty) on every call
 *
 * Moving to module scope means all of this runs exactly once, when the
 * module is first imported. All useTheme() consumers share this ref.
 */
const _isDarkMode = ref(false)

// ─── Module-level dark mode functions ─────────────────────────────────────────
// Forward-declared so _checkDarkMode and the media listener can reference
// applyOverrides/resetTheme. These are defined after useTheme but called at
// module init, which is fine because JS hoists function declarations.

/**
 * Reads the saved theme preference or falls back to the system preference.
 * Runs once at module load time.
 */
function _checkDarkMode() {
  const savedTheme = localStorage.getItem('ch-theme')
  if (savedTheme === 'dark') {
    _isDarkMode.value = true
  } else if (savedTheme === 'light') {
    _isDarkMode.value = false
  } else {
    _isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
}

/**
 * Applies or removes dark mode overrides.
 * Called at module init and when the system preference changes.
 *
 * NOTE: This function directly sets CSS vars on :root rather than calling
 * the composable's applyOverrides/resetTheme, because those are defined
 * inside useTheme() and aren't available at module scope. Instead we use
 * the same generateCSSVars + setProperty mechanism directly.
 */
function _applyDarkMode(enabled: boolean) {
  const root = document.documentElement
  if (enabled) {
    // Merge dark overrides into _overrides and apply all vars
    _overrides.value = { ..._overrides.value, ...darkThemeOverrides }
    const vars = generateCSSVars(_overrides.value)
    for (const [prop, value] of Object.entries(vars)) {
      root.style.setProperty(prop, value)
    }
    root.classList.add('dark')
  } else {
    // Reset to defaults
    const defaultVars = generateCSSVars({})
    for (const prop of Object.keys(defaultVars)) {
      root.style.removeProperty(prop)
    }
    _overrides.value = {}
    for (const [prop, value] of Object.entries(defaultVars)) {
      root.style.setProperty(prop, value)
    }
    root.classList.remove('dark')
  }
}

// ─── Module-level initialization ──────────────────────────────────────────────
// Runs exactly once when the module is first imported. This is the fix for
// the memory leak: previously each useTheme() call added a new listener.
_checkDarkMode()
_applyDarkMode(_isDarkMode.value)

const _mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
_mediaQuery.addEventListener('change', (e: MediaQueryListEvent) => {
  // Only respond to system preference if user hasn't explicitly set a theme
  if (!localStorage.getItem('ch-theme')) {
    _isDarkMode.value = e.matches
    _applyDarkMode(e.matches)
  }
})

// ─── Composable ──────────────────────────────────────────────────────────────
/**
 * Returns theme control functions and the current overrides state.
 * Can be called from any Vue component or other composable.
 *
 * All state is module-level singleton — calling this from multiple components
 * does NOT create duplicate listeners or redundant DOM operations.
 */
export function useTheme() {

  // ─── applyOverrides ────────────────────────────────────────────────────────
  /**
   * Applies a partial theme override. Merges with any previously applied overrides.
   *
   * The merge behavior means you can apply overrides in multiple calls without
   * losing earlier ones. For example:
   *   applyOverrides({ '--ch-color-primary': '#e11d48' })  // sets primary color
   *   applyOverrides({ '--ch-font-sans': '"Nunito"' })     // adds font override
   *   // Both overrides are now active
   *
   * @param overrides - Partial map of `--ch-*` property names to new values
   * @param target    - DOM element to apply overrides to (default: `<html>` = `:root`)
   *
   * @example
   * const { applyOverrides } = useTheme()
   * applyOverrides({ '--ch-color-primary': '#e11d48' })
   */
  function applyOverrides(
    overrides: ThemeOverrides,
    target: HTMLElement = document.documentElement
  ): void {
    // Merge new overrides into the existing overrides object.
    // `{ ..._overrides.value, ...overrides }` creates a NEW object with all
    // current keys plus any new/changed keys from `overrides`.
    // We must create a new object (not mutate in-place) to trigger Vue's
    // reactivity system to detect the change.
    _overrides.value = { ..._overrides.value, ...overrides }

    // Generate the FULL var map (all defaults + all overrides) and
    // apply every property. This ensures the complete state is always
    // reflected on the element, even after partial updates.
    const vars = generateCSSVars(_overrides.value)

    for (const [prop, value] of Object.entries(vars)) {
      // `style.setProperty` is the only API that works for CSS custom properties.
      // `el.style['--ch-color-primary'] = '...'` does NOT work.
      target.style.setProperty(prop, value)
    }
  }

  // ─── setVar ───────────────────────────────────────────────────────────────
  /**
   * Overrides a single CSS variable directly.
   * A convenience function for simple one-property changes.
   *
   * @param varName - The full CSS custom property name (e.g. '--ch-color-primary')
   * @param value   - The new value string
   * @param target  - DOM element (default: `<html>`)
   *
   * @example
   * const { setVar } = useTheme()
   * setVar('--ch-color-primary', '#7c3aed')
   */
  function setVar(
    varName: string,
    value: string,
    target: HTMLElement = document.documentElement
  ): void {
    // Update the singleton overrides map for tracking
    _overrides.value[varName] = value

    // Directly set the single property (more efficient than regenerating all vars)
    target.style.setProperty(varName, value)
  }

  // ─── resetTheme ───────────────────────────────────────────────────────────
  /**
   * Removes ALL runtime overrides and reverts to the token default values.
   *
   * How it reverts: calling `style.removeProperty` on the element removes
   * the inline style. The CSS then cascades up to find the value — but since
   * `injectCSSVars()` was called at startup, the defaults are set as inline
   * styles on `<html>`. To restore those defaults, we re-set them all here.
   *
   * @param target - DOM element to reset (default: `<html>`)
   *
   * @example
   * const { resetTheme } = useTheme()
   * resetTheme() // removes all overrides, restores token defaults
   */
  function resetTheme(target: HTMLElement = document.documentElement): void {
    // Generate defaults (no overrides) and re-apply them to restore the baseline
    const defaultVars = generateCSSVars({})

    for (const prop of Object.keys(defaultVars)) {
      // Remove the inline style for this property...
      target.style.removeProperty(prop)
    }

    // ...then reset the in-memory override tracker to empty
    _overrides.value = {}

    // Re-apply the defaults (since we removed all inline styles above)
    for (const [prop, value] of Object.entries(defaultVars)) {
      target.style.setProperty(prop, value)
    }
  }

  // ─── removeOverride ───────────────────────────────────────────────────────
  /**
   * Removes a single override, reverting ONLY that variable to its token default.
   * Useful when you want to reset one color without resetting the whole theme.
   *
   * @param varName - The `--ch-*` property name to revert
   * @param target  - DOM element (default: `<html>`)
   *
   * @example
   * const { removeOverride } = useTheme()
   * // Previously: applyOverrides({ '--ch-color-primary': '#e11d48' })
   * removeOverride('--ch-color-primary') // reverts to indigo
   */
  function removeOverride(
    varName: string,
    target: HTMLElement = document.documentElement
  ): void {
    // Destructure assignment to remove one key from the overrides object.
    // `const { [varName]: _, ...rest }` creates a new object `rest` that
    // contains everything EXCEPT the key `varName`. `_` is the discarded value.
    const { [varName]: _, ...rest } = _overrides.value
    _overrides.value = rest as ThemeOverrides

    // Re-generate vars WITHOUT the removed override, then re-apply just this var.
    // This restores the token default value for this specific property.
    const vars = generateCSSVars(_overrides.value)
    target.style.setProperty(varName, vars[varName] ?? '')
  }

  // ─── getVar ───────────────────────────────────────────────────────────────
  /**
   * Reads the current computed value of any CSS variable on an element.
   * This reads the LIVE browser-computed value (accounting for inheritance,
   * cascading, etc.) — not just what's in `_overrides`.
   *
   * @param varName - The CSS custom property name to read
   * @param target  - DOM element to read from (default: `<html>`)
   * @returns The current string value, trimmed of whitespace
   *
   * @example
   * const { getVar } = useTheme()
   * const primaryColor = getVar('--ch-color-primary') // '#4f46e5'
   */
  function getVar(
    varName: string,
    target: HTMLElement = document.documentElement
  ): string {
    // `getComputedStyle` returns the COMPUTED style (after cascade + inheritance).
    // `getPropertyValue` retrieves a CSS custom property's value.
    // `.trim()` removes any leading/trailing whitespace the browser may add.
    return getComputedStyle(target).getPropertyValue(varName).trim()
  }

  // ─── Dark mode toggle (composable-level wrapper) ─────────────────────────
  /**
   * Toggles dark mode on/off. Writes the preference to localStorage and
   * applies the visual change. Delegates to module-level singletons.
   */
  function toggleDarkMode() {
    _isDarkMode.value = !_isDarkMode.value
    localStorage.setItem('ch-theme', _isDarkMode.value ? 'dark' : 'light')
    _applyDarkMode(_isDarkMode.value)
  }

  // ─── Return public API ────────────────────────────────────────────────────
  return {
    applyOverrides,
    setVar,
    resetTheme,
    removeOverride,
    getVar,

    /**
     * The currently active overrides, as a readonly reactive ref.
     * Use this in a component to reactively display the current theme state,
     * e.g. in a theme editor UI.
     *
     * `readonly()` wraps the ref so consumers can read it reactively
     * but cannot directly mutate `_overrides.value` from outside this composable.
     */
    currentOverrides: readonly(_overrides),

    /**
     * Dark mode properties and methods.
     * isDarkMode and toggleDarkMode reference module-level singletons,
     * so they work identically regardless of which component calls useTheme().
     */
    isDarkMode: readonly(_isDarkMode),
    toggleDarkMode,
    applyDarkMode: _applyDarkMode,
  }
}
