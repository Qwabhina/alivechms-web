/**
 * @file index.ts
 * @path /frontend/src/design-system/index.ts
 * @description Master export file for the design system.
 *
 * This is the single entry point for consuming the design system in your app.
 * Import everything from here — not from individual files — so that:
 *   1. Import paths stay stable even if internal files are moved/renamed
 *   2. Tree-shaking works correctly (bundlers trace from this entry point)
 *   3. You have one place to see everything the design system exports
 *
 * ─── Usage in a Vue component ────────────────────────────────────────────────
 * @example
 * import { ChButton, ChCard, ChBadge } from '@/design-system'
 *
 * ─── Usage in main.ts (app entry point) ──────────────────────────────────────
 * Token initialization is automatic — `useTheme` runs at module load time and
 * writes all CSS vars (respecting the user's saved dark mode preference).
 * You only need to import the global styles:
 *
 * @example
 * main.ts
 * import { createApp } from 'vue'
 * import '@/design-system/styles/base.css'
 * import App from './App.vue'
 *
 * createApp(App).mount('#app')
 *
 * For startup brand overrides, use useTheme — overrides set this way survive
 * dark mode toggles and applyTheme() calls automatically:
 * import { useTheme } from '@/design-system'
 * const { applyOverrides } = useTheme()
 * applyOverrides({ '--ch-color-primary': appConfig.brandColor })
 *
 * ─── Styles to import manually in main.ts ────────────────────────────────────
 * This CSS file cannot be auto-imported — it must be explicitly imported
 * in your app's entry point (main.ts):
 *
 *   import '@/design-system/styles/base.css' → global reset + element defaults + animations
 *
 * ─── Note on styles/tokens.css ───────────────────────────────────────────────
 * The original plan included a `styles/tokens.css` file for static CSS custom
 * property declarations. This was intentionally replaced by the JS-based
 * theming system (see `useTheme.ts` and `tokens/index.ts`) because it provides:
 *   - Automatic initialization — no setup code needed in main.ts
 *   - Dark mode awareness built in (reads localStorage + OS preference)
 *   - Named theme support via `defineTheme()` and `applyTheme()`
 *   - Runtime override support (per-church branding, post-login)
 *   - TypeScript type safety on token names
 *   - A single source of truth (tokens defined once in TS, not duplicated in CSS)
 * If you need a static CSS file (e.g. for email templates), use `generateStyleTag()`
 * from tokens/index.ts to generate the CSS string at build time.
 * Add this to `vite.config.ts` so you can use `@/design-system` as the import path:
 * @example
 * vite.config.ts
 * import path from 'path'
 * export default defineConfig({
 *   resolve: {
 *     alias: { '@': path.resolve(__dirname, './src') }
 *   }
 * })
 */

/**
 * ─── Token System ─────────────────────────────────────────────────────────────
 * Re-exports everything from the token system:
 * - `palette`                  — raw primitive color values
 * - `semanticColors`           — default light-mode semantic token map
 * - `darkSemanticColors`       — default dark-mode semantic token map
 * - `defaultTheme`             — built-in Theme object (light + dark co-located)
 * - `defineTheme`              — create a custom Theme from palette overrides
 * - `createSemanticColors`     — factory: build a light semantic map from any palette
 * - `createDarkSemanticColors` — factory: build a dark semantic map from any palette
 * - `typography`               — font, size, weight, line-height, tracking tokens
 * - `spacing`                  — space scale (4px base grid)
 * - `radius`                   — border-radius scale
 * - `shadows`                  — elevation shadows (dark-mode aware via color-shadow)
 * - `transitions`              — duration + easing tokens
 * - `zIndex`                   — stacking layer tokens
 * - `generateCSSVars`          — utility: all vars as a plain object
 * - `injectCSSVars`            — utility: apply specific overrides to a DOM element
 * - `generateStyleTag`         — utility: generate a full CSS string for SSR
 * - Types: `Theme`, `Palette`, `SemanticColor`, `SpacingToken`, and more
 */
export * from './tokens'

// ─── Core Components ──────────────────────────────────────────────────────────
/**
 * Fundamental building blocks used across every part of the UI.
 * These should be globally registered in most apps — they're that universal.
 */
export { default as ChButton } from './components/core/ChButton.vue'
export { default as ChInput } from './components/core/ChInput.vue'
export { default as ChTextarea } from './components/core/ChTextarea.vue'
export { default as ChCard } from './components/core/ChCard.vue'
export { default as ChPageHeader } from './components/core/ChPageHeader.vue'
export { default as ChBadge } from './components/core/ChBadge.vue'
export { default as ChAvatar } from './components/core/ChAvatar.vue'
export { default as ChDivider } from './components/core/ChDivider.vue'
export { default as ChTooltip } from './components/core/ChTooltip.vue'
export { default as ChPopover } from './components/core/ChPopover.vue'
export { default as ChDropdown } from './components/core/ChDropdown.vue'
export { default as ChDropdownItem } from './components/core/ChDropdownItem.vue'
export { default as ChDropdownDivider } from './components/core/ChDropdownDivider.vue'

// Core component types
export type { TooltipPlacement } from './components/core/ChTooltip.vue'
export type { PopoverTrigger, PopoverPlacement } from './components/core/ChPopover.vue'
export type {
  DropdownItem as ChDropdownItemType,
  DropdownItemVariant,
} from './components/core/ChDropdown.vue'

// ─── Composables ────────────────────
/**
 * Vue composables that provide reactive logic.
 * Each is a function you call inside a `<script setup>` or `setup()`.
 */
export { useTheme } from './composables/useTheme'
export { useTableExport, type ExportFormat } from './composables/useTableExport'
export { useToast } from './composables/useToast'
export { useModal } from './composables/useModal'
export { useStepperWizard } from './composables/useStepperWizard'
export { confirm, confirmModal, type ConfirmOptions } from './composables/useConfirm'
export {
  useValidation,
  useForm,
  validators,
  type ValidationRule,
  type ValidatorFn,
  type UseValidationOptions,
} from './composables/useValidation'
export {
  useLocalStorage,
  useSessionStorage,
  type UseLocalStorageOptions,
  type StorageInfo,
} from './composables/useLocalStorage'
// ─── Utility Functions ─────────────────────────────────────────────────────────
/**
 * General utilities for string, number, date, array, DOM, validation, color, and function operations.
 * Also includes enhanced date utilities (date-fns-like) via `utils/date`.
 */
export * from './utils'
export { groupBy as useGrouping } from './utils'
export * as dateUtils from './utils/date'
// Re-export commonly used date utils at top level for convenience
export {
  parse,
  format,
  formatISO,
  formatTime,
  formatRelative,
  formatDistance,
  isToday,
  isYesterday,
  isTomorrow,
  isSameDay,
  isSameMonth,
  isSameYear,
  isPast,
  isFuture,
  isAfter,
  isBefore,
  isWithinRange,
  addDays,
  addMonths,
  addYears,
  addHours,
  addMinutes,
  addSeconds,
  subDays,
  subMonths,
  subYears,
  startOfDay,
  endOfDay,
  startOfMonth,
  endOfMonth,
  startOfYear,
  endOfYear,
  startOfWeek,
  endOfWeek,
  differenceInMilliseconds,
  differenceInSeconds,
  differenceInMinutes,
  differenceInHours,
  differenceInDays,
  differenceInMonths,
  differenceInYears,
  getDate,
  getDay,
  getMonth,
  getYear,
  getHours,
  getMinutes,
  getSeconds,
  getMilliseconds,
  setDate,
  setDay,
  setMonth,
  setYear,
  setHours,
  setMinutes,
  setSeconds,
  now,
  timestamp,
  isLeapYear,
  getDaysInMonth,
  getDaysInYear,
  clamp,
  closestTo,
  areIntervalsOverlapping,
} from './utils/date'
// ── Navigation ────────────────────────────────────────────────────────────────
export { default as ChSidebar } from './components/navigation/ChSidebar.vue'
export { default as ChSidebarItem } from './components/navigation/ChSidebarItem.vue'
export { default as ChTopbar } from './components/navigation/ChTopbar.vue'
export { default as ChTabs } from './components/navigation/ChTabs.vue'
export { default as ChBreadcrumb } from './components/navigation/ChBreadcrumb.vue'
export { default as ChBreadcrumbItem } from './components/navigation/ChBreadcrumbItem.vue'
export { default as ChCommandPalette } from './components/navigation/ChCommandPalette.vue'

export type { NavItem } from './components/navigation/ChSidebar.vue'
export type { TopbarUser } from './components/navigation/ChTopbar.vue'
export type { Command, CommandGroup } from './components/navigation/ChCommandPalette.vue'

// ── Data Display ──────────────────────────────────────────────────────────────
export { default as ChTable } from './components/data/ChTable.vue'
export { default as ChTableExportDialog } from './components/data/ChTableExportDialog.vue'
export { default as ChStatCard } from './components/data/ChStatCard.vue'
export { default as ChDataList } from './components/data/ChDataList.vue'
export { default as ChChart } from './components/data/ChChart.vue'
export { default as ChPagination } from './components/data/ChPagination.vue'
export { default as ChAccordion } from './components/data/ChAccordion.vue'
export { default as ChAccordionItem } from './components/data/ChAccordionItem.vue'
export { default as ChCarousel } from './components/data/ChCarousel.vue'
export { default as ChEmptyState } from './components/data/ChEmptyState.vue'

// Data Display types
export type { CarouselSlide } from './components/data/ChCarousel.vue'
export type { EmptyStateIcon } from './components/data/ChEmptyState.vue'

// ── Forms & Modals ─────────────────────────────────────────────────────────────
export { default as ChFormField } from './components/forms/ChFormField.vue'
export { default as ChSelect } from './components/forms/ChSelect.vue'
export { default as ChCheckbox } from './components/forms/ChCheckbox.vue'
export { default as ChRadio } from './components/forms/ChRadio.vue'
export { default as ChSwitch } from './components/forms/ChSwitch.vue'
export { default as ChSlider } from './components/forms/ChSlider.vue'
export { default as ChFileUpload } from './components/forms/ChFileUpload.vue'
export { default as ChDatePicker } from './components/forms/ChDatePicker.vue'
export { default as ChModal } from './components/forms/ChModal.vue'
export { default as ChStepperWizard } from './components/forms/ChStepperWizard.vue'
export { default as ChStepperStep } from './components/forms/ChStepperStep.vue'
export { default as ChTimeline } from './components/forms/ChTimeline.vue'
export { default as ChTimelineItem } from './components/forms/ChTimelineItem.vue'

// ── UI Cues (async state, loading, feedback) ──────────────────────────────────
export { default as ChSpinner } from './components/cues/ChSpinner.vue'
export { default as ChSkeleton } from './components/cues/ChSkeleton.vue'
export { default as ChProgress } from './components/cues/ChProgress.vue'
export { default as ChToast } from './components/cues/ChToast.vue'
export { default as ChToastContainer } from './components/cues/ChToastContainer.vue'
export { default as ChAlert } from './components/cues/ChAlert.vue'

// Temporary compatibility export: some generated auto-imports or legacy code
// reference `userid` from the design-system. Export a harmless stub to avoid
// rollup warnings while the root cause is investigated and auto-imports are
// regenerated. Remove this once all auto-import declarations are clean.
export const userid: unknown = undefined
