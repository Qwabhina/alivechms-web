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
 * ─── Layered override model ───────────────────────────────────────────────────
 * All CSS variable state is managed in two distinct layers, applied in priority:
 *
 *   1. **Theme vars** (`_themeVars`) — derived from the active `Theme` object's
 *      light or dark semantic map. Updated atomically when `applyTheme()` is
 *      called or dark mode is toggled.
 *
 *   2. **User overrides** (`_userOverrides`) — set explicitly via `applyOverrides()`
 *      or `setVar()`. These survive theme switches and dark mode toggles.
 *      Think: per-church brand colors applied after login.
 *
 * Final applied CSS = theme vars merged with user overrides (user wins).
 *
 * This replaces the previous `brandSafeTokens` allowlist approach, which was
 * fragile (hardcoded keys) and didn't scale to arbitrary theme structures.
 *
 * ─── Use cases ───────────────────────────────────────────────────────────────
 * 1. **Per-church branding**: Load `church.primaryColor` after login and call
 *    `applyOverrides({ '--ch-color-primary': church.primaryColor })`.
 *    The override survives dark mode toggles and theme switches.
 *
 * 2. **Named themes**: Pre-build theme objects with `defineTheme()` and switch
 *    between them with `applyTheme()`. Dark mode automatically uses the active
 *    theme's dark semantic map.
 *
 * 3. **Dark mode toggle**: Call `toggleDarkMode()`. The active theme's `.dark`
 *    semantic map is applied automatically — no manual color mapping needed.
 *
 * 4. **Tenant customization**: Combine `applyTheme(tenantTheme)` for structure
 *    and `applyOverrides({ '--ch-color-primary': tenant.brandColor })` for
 *    per-church accent colors on top.
 *
 * ─── How it works ────────────────────────────────────────────────────────────
 * CSS custom properties on `document.documentElement` (`:root`) cascade to every
 * element on the page. `element.style.setProperty('--ch-color-primary', '#00026d')`
 * makes every component using `var(--ch-color-primary)` update instantly —
 * no Vue re-render cycle needed.
 *
 * ─── Singleton pattern ────────────────────────────────────────────────────────
 * All module-level `_` vars are true singletons. All `useTheme()` callers share
 * the same state. This is intentional: the current theme is global state.
 * The composable exists to expose a clean API — not to hold per-component state.
 *
 * @example Per-church branding on login
 * const { applyOverrides } = useTheme()
 * onMounted(async () => {
 *   const church = await fetchChurchSettings(churchId)
 *   applyOverrides({ '--ch-color-primary': church.brandColor })
 * })
 *
 * @example Switching named themes
 * import { roseTheme } from '@/design-system/tokens/themes'
 * const { applyTheme } = useTheme()
 * applyTheme(roseTheme)
 *
 * @example Dark mode toggle
 * const { toggleDarkMode, isDarkMode } = useTheme()
 * // isDarkMode is reactive — bind it to a toggle switch
 */

import { ref, readonly, computed } from 'vue'
import { generateCSSVars, type ThemeOverrides } from '../tokens'
import { defaultTheme, type Theme } from '../tokens/colors'

// ─── Module-level singletons ──────────────────────────────────────────────────

/**
 * CSS vars derived from the active theme's light or dark semantic map.
 * Keys are `--ch-*` CSS property names. Swapped atomically on `applyTheme()`
 * and dark mode toggle. Never mutated by user overrides.
 */
const _themeVars = ref<ThemeOverrides>({})

/**
 * Explicit overrides set by the consuming application (e.g. per-church colors).
 * These persist across `applyTheme()` calls and dark mode toggles.
 * User overrides always win over theme vars.
 */
const _userOverrides = ref<ThemeOverrides>({})

/** The currently active theme. */
const _activeTheme = ref<Theme>(defaultTheme)

/** Whether dark mode is currently active. */
const _isDarkMode = ref(false)

// ─── Internal helpers ─────────────────────────────────────────────────────────

/**
 * Converts a semantic color map (e.g. `theme.light`) to a ThemeOverrides record
 * with `--ch-*` prefixed CSS custom property keys.
 */
function _semanticsToVars(semantics: Record<string, string>): ThemeOverrides {
  return Object.fromEntries(Object.entries(semantics).map(([key, value]) => [`--ch-${key}`, value]))
}

/**
 * Computes the final merged ThemeOverrides: theme vars + user overrides.
 * User overrides take precedence (spread last).
 */
function _computeFinalVars(): ThemeOverrides {
  return { ..._themeVars.value, ..._userOverrides.value }
}

/**
 * The single write point for all DOM changes. Every other function ultimately
 * calls this. Writes the merged final vars and syncs the `dark` class.
 *
 * @param target - DOM element to write to (default: `<html>` = `:root`)
 */
function _commit(target: HTMLElement = document.documentElement): void {
  const vars = generateCSSVars(_computeFinalVars())
  for (const [prop, value] of Object.entries(vars)) {
    target.style.setProperty(prop, value)
  }
  target.classList.toggle('dark', _isDarkMode.value)
}

// ─── Module-level dark mode setup ─────────────────────────────────────────────

/**
 * Reads the saved theme preference or falls back to the OS preference.
 * Runs exactly once at module load time.
 */
function _checkDarkMode(): void {
  const saved = localStorage.getItem('ch-theme')
  if (saved === 'dark') {
    _isDarkMode.value = true
  } else if (saved === 'light') {
    _isDarkMode.value = false
  } else {
    _isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
}

/**
 * Applies or removes dark mode by switching the active theme's semantic map.
 * User overrides are preserved — they're layered on top in `_commit()`.
 */
function _applyDarkMode(enabled: boolean): void {
  _isDarkMode.value = enabled
  const semantics = enabled ? _activeTheme.value.dark : _activeTheme.value.light
  _themeVars.value = _semanticsToVars(semantics)
  _commit()
}

// ─── Module-level initialization (runs once on first import) ──────────────────
_checkDarkMode()

_themeVars.value = _semanticsToVars(_isDarkMode.value ? defaultTheme.dark : defaultTheme.light)
_commit()

// Respond to OS-level preference changes, but only when the user hasn't
// pinned an explicit preference via localStorage.
window
  .matchMedia('(prefers-color-scheme: dark)')
  .addEventListener('change', (e: MediaQueryListEvent) => {
    if (!localStorage.getItem('ch-theme')) {
      _applyDarkMode(e.matches)
    }
  })

// ─── Composable ──────────────────────────────────────────────────────────────
/**
 * Returns theme control functions and reactive state.
 * Can be called from any Vue component or other composable.
 *
 * All state is module-level — calling this from multiple components does NOT
 * create duplicate listeners or redundant DOM writes.
 */
export function useTheme() {
  // ─── applyTheme ───────────────────────────────────────────────────────────
  /**
   * Switches to a new `Theme` (created by `defineTheme()`).
   *
   * Atomically replaces all theme-derived CSS vars with the new theme's
   * light or dark semantic map (depending on current dark mode state).
   * Any active `_userOverrides` are preserved and re-applied on top.
   *
   * @param theme  - A `Theme` object from `defineTheme()`
   * @param target - DOM element (default: `<html>`)
   *
   * @example Switch to a pre-built theme
   * import { roseTheme } from '@/design-system/tokens/themes'
   * applyTheme(roseTheme)
   *
   * @example Derive a runtime theme from a single color
   * applyTheme(defineTheme({ primary900: tenant.brandColor }, tenant.name))
   */
  function applyTheme(theme: Theme, target: HTMLElement = document.documentElement): void {
    _activeTheme.value = theme
    const semantics = _isDarkMode.value ? theme.dark : theme.light
    _themeVars.value = _semanticsToVars(semantics)
    _commit(target)
  }

  // ─── applyOverrides ────────────────────────────────────────────────────────
  /**
   * Applies user-level overrides. Merges with any existing user overrides.
   * These persist across `applyTheme()` calls and dark mode toggles.
   *
   * Merge behavior: calling twice combines both sets of overrides.
   * To fully replace overrides, call `resetTheme()` first.
   *
   * @param overrides - Map of `--ch-*` CSS property names to values
   * @param target    - DOM element (default: `<html>`)
   *
   * @example
   * applyOverrides({ '--ch-color-primary': church.brandColor })
   */
  function applyOverrides(
    overrides: ThemeOverrides,
    target: HTMLElement = document.documentElement,
  ): void {
    _userOverrides.value = { ..._userOverrides.value, ...overrides }
    _commit(target)
  }

  // ─── setVar ───────────────────────────────────────────────────────────────
  /**
   * Overrides a single CSS variable. Convenience wrapper around `applyOverrides`.
   *
   * @param varName - Full CSS custom property name (e.g. `'--ch-color-primary'`)
   * @param value   - New value string
   * @param target  - DOM element (default: `<html>`)
   *
   * @example
   * setVar('--ch-color-primary', '#e11d48')
   */
  function setVar(
    varName: string,
    value: string,
    target: HTMLElement = document.documentElement,
  ): void {
    _userOverrides.value = { ..._userOverrides.value, [varName]: value }
    _commit(target)
  }

  // ─── resetTheme ───────────────────────────────────────────────────────────
  /**
   * Clears all user overrides, reverting to the active theme's values.
   *
   * The active theme itself is NOT changed. To also reset the theme, call
   * `applyTheme(defaultTheme)` afterward.
   *
   * @param target - DOM element (default: `<html>`)
   *
   * @example
   * resetTheme()              // removes user overrides, keeps active theme
   * applyTheme(defaultTheme)  // additionally resets to the default theme
   */
  function resetTheme(target: HTMLElement = document.documentElement): void {
    _userOverrides.value = {}
    _commit(target)
  }

  // ─── removeOverride ───────────────────────────────────────────────────────
  /**
   * Removes a single user override, reverting ONLY that variable to the active
   * theme's current value. Everything else is untouched.
   *
   * @param varName - The `--ch-*` property to revert
   * @param target  - DOM element (default: `<html>`)
   *
   * @example
   * removeOverride('--ch-color-primary') // reverts to theme's primary color
   */
  function removeOverride(varName: string, target: HTMLElement = document.documentElement): void {
    const { [varName]: _removed, ...rest } = _userOverrides.value as Record<string, string>
    _userOverrides.value = rest as ThemeOverrides
    _commit(target)
  }

  // ─── getVar ───────────────────────────────────────────────────────────────
  /**
   * Reads the current computed value of any CSS variable from the DOM.
   * Returns the LIVE browser-computed value — accounts for cascade and inheritance.
   *
   * @param varName - CSS custom property to read
   * @param target  - DOM element to read from (default: `<html>`)
   *
   * @example
   * const primary = getVar('--ch-color-primary') // '#00026d'
   */
  function getVar(varName: string, target: HTMLElement = document.documentElement): string {
    return getComputedStyle(target).getPropertyValue(varName).trim()
  }

  // ─── toggleDarkMode ───────────────────────────────────────────────────────
  /**
   * Toggles dark mode on/off. Persists the preference to localStorage.
   *
   * Uses the ACTIVE THEME's `.dark` or `.light` semantic map — so calling
   * `applyTheme(roseTheme)` then `toggleDarkMode()` gives you rose dark mode,
   * not the default dark mode.
   */
  function toggleDarkMode(): void {
    const next = !_isDarkMode.value
    localStorage.setItem('ch-theme', next ? 'dark' : 'light')
    _applyDarkMode(next)
  }

  // ─── Return public API ────────────────────────────────────────────────────
  return {
    // ── Theme switching ──────────────────────────────────────────────────────
    applyTheme,

    // ── User overrides ───────────────────────────────────────────────────────
    applyOverrides,
    setVar,
    resetTheme,
    removeOverride,
    getVar,

    // ── Dark mode ────────────────────────────────────────────────────────────
    isDarkMode: readonly(_isDarkMode),
    toggleDarkMode,
    /** Direct setter for dark mode — use `toggleDarkMode` for the common case. */
    applyDarkMode: _applyDarkMode,

    // ── State inspection ─────────────────────────────────────────────────────
    /**
     * The currently active theme. Reactive — bind to a display component or
     * use in a computed to derive theme-aware values.
     */
    activeTheme: readonly(_activeTheme),

    /**
     * Only the user-level overrides currently in effect.
     * Does NOT include theme-derived vars — only what the app explicitly set.
     * Useful for a theme editor UI that shows customizations separately from defaults.
     */
    userOverrides: readonly(_userOverrides),

    /**
     * All active CSS vars: theme vars + user overrides fully merged.
     * Equivalent to what's currently written to the DOM. Useful for serializing,
     * snapshotting, or debugging the full computed theme state.
     */
    computedVars: computed(() => _computeFinalVars()),
  }
}
