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
 * @example
 * main.ts
 * import { createApp }    from 'vue'
 * import { injectCSSVars } from '@/design-system'
 * import '@/design-system/styles/base.css'
 * import App from './App.vue'
 *
 * Initialize CSS vars BEFORE mounting the app.
 * This ensures all --ch-* variables exist before any component renders.
 * injectCSSVars()
 *
 * createApp(App).mount('#app')
 *
 * ─── Styles to import manually in main.ts ────────────────────────────────────
 * This CSS file cannot be auto-imported — it must be explicitly imported
 * in your app's entry point (main.ts) after calling injectCSSVars():
 *
 *   import '@/design-system/styles/base.css' → global reset + element defaults + animations
 *
 * ─── Note on styles/tokens.css ───────────────────────────────────────────────
 * The original plan included a `styles/tokens.css` file for static CSS custom
 * property declarations. This was intentionally replaced by the JS-based
 * `injectCSSVars()` approach (see tokens/index.ts) because it provides:
 *   - Runtime override support (per-church branding, dark mode)
 *   - TypeScript type safety on token names
 *   - A single source of truth (tokens are defined once in TS, not duplicated in CSS)
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
 * - `palette`         — raw color values
 * - `semanticColors`  — intent-mapped color tokens
 * - `typography`      — font, size, weight, spacing tokens
 * - `spacing`         — space scale
 * - `radius`          — border-radius scale
 * - `shadows`         — elevation shadows
 * - `transitions`     — duration + easing tokens
 * - `zIndex`          — stacking layer tokens
 * - `generateCSSVars` — utility to get all vars as a plain object
 * - `injectCSSVars`   — utility to write vars to a DOM element
 * - `generateStyleTag`— utility to generate a CSS string (for SSR)
 * - All TypeScript token types
 */
export * from './tokens'

// ─── Core Components ──────────────────────────────────────────────────────────
/**
 * Fundamental building blocks used across every part of the UI.
 * These should be globally registered in most apps — they're that universal.
 */
export { ChButton }   from './components/core/ChButton.vue'
export { ChInput }    from './components/core/ChInput.vue'
export { ChTextarea } = from './components/core/ChTextarea.vue'
export { ChCard }     = from './components/core/ChCard.vue'
export { ChBadge }    = from './components/core/ChBadge.vue'
export { ChAvatar }   = from './components/core/ChAvatar.vue'
export { ChDivider }  = from './components/core/ChDivider.vue'
export { ChTooltip }  = from './components/core/ChTooltip.vue'
export { ChPopover }  = from './components/core/ChPopover.vue'
export { ChDropdown } = from './components/core/ChDropdown.vue'
export { ChDropdownItem } = from './components/core/ChDropdownItem.vue'
export { ChDropdownDivider } = from './components/core/ChDropdownDivider.vue'
export { ChIcon }     = from './components/core/ChIcon.vue'

// Core component types
export type { TooltipPlacement }           = from './components/core/ChTooltip.vue'
export type { PopoverTrigger, PopoverPlacement } = from './components/core/ChPopover.vue'
export type { DropdownItem as ChDropdownItemType, DropdownItemVariant } = from './components/core/ChDropdown.vue'
export type { IconSize, IconColor, IconName } = from './components/core/ChIcon.vue'

// ─── Composables ────────────────────
/**
 * Vue composables that provide reactive logic.
 * Each is a function you call inside a `<script setup>` or `setup()`.
 */
export { useTheme }       = from './composables/useTheme'
export { useTableExport, type ExportFormat } = from './composables/useTableExport'
export { useToast }       = from './composables/useToast'
export { useModal }       = from './composables/useModal'
export { useStepperWizard } = from './composables/useStepperWizard'
export { useValidation, useForm, validators, type ValidationRule, type ValidatorFn, type UseValidationOptions } = from './composables/useValidation'
export { useLocalStorage, useSessionStorage, type UseLocalStorageOptions, type StorageInfo } = from './composables/useLocalStorage'
export { darkSemanticColors } = from './tokens/colors'

// ─── Utility Functions ─────────────────────────────────────────────────────────
/**
 * General utilities for string, number, date, array, DOM, validation, color, and function operations.
 * Also includes enhanced date utilities (date-fns-like) via `utils/date`.
 */
export * from './utils'
export { useGrouping } = from './utils'
export * as dateUtils = from './utils/date'
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
export { ChSidebar }             = from './components/navigation/ChSidebar.vue'
export { ChSidebarItem }         = from './components/navigation/ChSidebarItem.vue'
export { ChTopbar }              = from './components/navigation/ChTopbar.vue'
export { ChTabs }                = from './components/navigation/ChTabs.vue'
export { ChBreadcrumb } = from './components/navigation/ChBreadcrumb.vue'
export { ChBreadcrumbItem } = from './components/navigation/ChBreadcrumbItem.vue'
export { ChCommandPalette } = from './components/navigation/ChCommandPalette.vue'

// Navigation types — export so consuming code can build nav arrays type-safely
export type { NavItem } = from './components/navigation/ChSidebar.vue'
export type { Tab }                         = from './components/navigation/ChTabs.vue'
export { TopbarUser }                  = from './components/navigation/ChTopbar.vue'
export type { Command, CommandGroup } = from './components/navigation/ChCommandPalette.vue'

// ── Data Display ──────────────────────────────────────────────────────────────
export { ChTable }             = from './components/data/ChTable.vue'
export { ChTableExportDialog } = from './components/data/ChTableExportDialog.vue'
export { ChStatCard }          = from './components/data/ChStatCard.vue'
export { ChDataList }          = from './components/data/ChDataList.vue'
export { ChChart }             = from './components/data/ChChart.vue'
export { ChPagination }        = from './components/data/ChPagination.vue'
export { ChAccordion } = from './components/data/ChAccordion.vue'
export { ChAccordionItem } = from './components/data/ChAccordionItem.vue'
export { ChCarousel } = from './components/data/ChCarousel.vue'
export { ChEmptyState } = from './components/data/ChEmptyState.vue'

// Data Display types
export type { CarouselSlide } = from './components/data/ChCarousel.vue'
export type { EmptyStateIcon } = from './components/data/ChEmptyState.vue'

// ── Forms & Modals ─────────────────────────────────────────────────────────────
export { ChFormField }     = from './components/forms/ChFormField.vue'
export { ChSelect }        = from './components/forms/ChSelect.vue'
export { ChCheckbox }      = from './components/forms/ChCheckbox.vue'
export { ChRadio }         = from './components/forms/ChRadio.vue'
export { ChSwitch } = from './components/forms/ChSwitch.vue'
export { ChSlider } = from './components/forms/ChSlider.vue'
export { ChFileUpload } = = from './components/forms/ChFileUpload.vue'
export { ChDatePicker }    = from './components/forms/ChDatePicker.vue'
export { ChModal }         = from './components/forms/ChModal.vue'
export { ChStepperWizard } = from './components/forms/ChStepperWizard.vue'
export { ChStepperStep }   = from './components/forms/ChStepperStep.vue'
export { ChTimeline }      = from './components/forms/ChTimeline.vue'
export { ChTimelineItem }  = from './components/forms/ChTimelineItem.vue'

// ── UI Cues (async state, loading, feedback) ──────────────────────────────────
export { ChSpinner }        = from './components/cues/ChSpinner.vue'
export { ChSkeleton }       = from './components/cues/ChSkeleton.vue'
export { ChProgress }       = from './components/cues/ChProgress.vue'
export { ChToast }          = from './components/cues/ChToast.vue'
export { ChToastContainer } = from './components/cues/ChToastContainer.vue'
export { ChAlert } = from './components/cues/ChAlert.vue'