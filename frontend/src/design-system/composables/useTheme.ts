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

// ─── Module-level singleton ───────────────────────────────────────────────────
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

// ─── Composable ──────────────────────────────────────────────────────────────
/**
 * Returns theme control functions and the current overrides state.
 * Can be called from any Vue component or other composable.
 */
export function useTheme() {
  // ─── Dark Mode Detection ────────────────────────────────────────────────────
  const isDarkMode = ref(false)

  // Check initial dark mode preference (system setting or saved preference)
  function checkDarkMode() {
    // Check if user has a saved preference
    const savedTheme = localStorage.getItem('ch-theme')
    if (savedTheme === 'dark') {
      isDarkMode.value = true
    } else if (savedTheme === 'light') {
      isDarkMode.value = false
    } else {
      // Fallback to system preference
      isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
    }
  }

  // Toggle dark mode
  function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value
    localStorage.setItem('ch-theme', isDarkMode.value ? 'dark' : 'light')
    applyDarkMode(isDarkMode.value)
  }

  // Apply dark mode styles
  function applyDarkMode(enabled: boolean) {
    if (enabled) {
      applyOverrides(darkThemeOverrides)
      document.documentElement.classList.add('dark')
    } else {
      // Remove dark mode overrides by resetting to defaults
      resetTheme()
      document.documentElement.classList.remove('dark')
    }
  }

  // Initialize dark mode
  checkDarkMode()
  applyDarkMode(isDarkMode.value)

  // Listen for system preference changes
  const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  const handleSystemThemeChange = (e: MediaQueryListEvent) => {
    // Only apply system preference if user hasn't explicitly set a theme
    if (!localStorage.getItem('ch-theme')) {
      isDarkMode.value = e.matches
      applyDarkMode(e.matches)
    }
  }
  mediaQuery.addEventListener('change', handleSystemThemeChange)

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
     * Dark mode properties and methods
     */
    isDarkMode: readonly(isDarkMode),
    toggleDarkMode,
    applyDarkMode,
  }
}
