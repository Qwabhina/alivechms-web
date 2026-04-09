/**
 * @file useStepperWizard.ts
 * @path /frontend/src/design-system/composables/useStepperWizard.ts
 * @description Composable that manages step state for multi-step wizard forms.
 * Handles navigation, per-step validation gating, and completion tracking.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Member registration wizard
 * const wizard = useStepperWizard([
 *   { id: 'personal', label: 'Personal Info',
 *     validate: () => validatePersonalForm() },
 *   { id: 'family',   label: 'Family Details' },
 *   { id: 'groups',   label: 'Groups & Roles' },
 *   { id: 'confirm',  label: 'Confirm' },
 * ])
 *
 * In template — pass wizard to ChStepperWizard
 * <ChStepperWizard :wizard="wizard">
 *   <ChStepperStep step-id="personal" :wizard="wizard">
 *     <PersonalInfoForm v-model="form.personal" />
 *   </ChStepperStep>
 *   ...
 * </ChStepperWizard>
 */

import { ref, computed, readonly } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface WizardStep {
  /** Unique identifier for this step — used by ChStepperStep to match */
  id:        string
  /** Display label shown in the step header */
  label:     string
  /** Optional sub-label shown below the main label */
  sublabel?: string
  /**
   * Optional async validation function called when the user clicks "Next".
   * Return true (or resolve to true) to allow proceeding.
   * Return false (or a string error message) to block navigation.
   * If omitted, the step is always passable.
   */
  validate?: () => boolean | string | Promise<boolean | string>
  /** Marks the step as optional — shows an "Optional" badge in the header */
  optional?: boolean
}



// ─── Composable ───────────────────────────────────────────────────────────────

export function useStepperWizard(steps: WizardStep[]) {
  if (steps.length === 0) throw new Error('[useStepperWizard] steps array must not be empty')

  /** Index of the currently active step (0-based) */
  const currentIdx   = ref(0)

  /** Set of step indices the user has successfully completed */
  const completed    = ref(new Set<number>())

  /** Per-step validation error messages */
  const errors       = ref(new Map<number, string>())

  /** True while an async validate() fn is running */
  const isValidating = ref(false)

  // ── Computed ────────────────────────────────────────────────────────────────

  const currentStep = computed((): WizardStep => {
    const step = steps[currentIdx.value]
    if (!step) throw new Error(`Step index ${currentIdx.value} out of bounds`)
    return step
  })
  const isFirstStep  = computed(() => currentIdx.value === 0)
  const isLastStep   = computed(() => currentIdx.value === steps.length - 1)
  const isComplete   = computed(() => completed.value.size === steps.length)

  /** Progress percentage (0–100) based on completed steps */
  const progress     = computed(() =>
    Math.round((completed.value.size / steps.length) * 100)
  )

  // ── Navigation ──────────────────────────────────────────────────────────────

  /**
   * Runs the current step's validate() if it has one, then advances.
   * Blocks if validation fails and stores the error message on the step.
   */
  async function next(): Promise<boolean> {
    const step = steps[currentIdx.value]
    if (!step) return false // Should never happen due to validation checks

    errors.value.delete(currentIdx.value)

    if (step.validate) {
      isValidating.value = true
      try {
        const result = await step.validate()
        if (result !== true) {
          const msg = typeof result === 'string' ? result : 'Please complete this step before continuing.'
          errors.value.set(currentIdx.value, msg)
          return false
        }
      } catch {
        errors.value.set(currentIdx.value, 'Validation failed. Please try again.')
        return false
      } finally {
        isValidating.value = false
      }
    }

    // Mark step as completed and advance
    completed.value.add(currentIdx.value)
    if (!isLastStep.value) {
      currentIdx.value++
    }
    return true
  }

  /** Goes back one step. Never validates — going back is always allowed. */
  function back() {
    if (!isFirstStep.value) currentIdx.value--
  }

  /**
   * Jumps directly to a step by index.
   * Only allowed if the target step has been completed or is adjacent.
   * Prevents skipping ahead past unvisited steps.
   */
  function goTo(idx: number) {
    if (idx < 0 || idx >= steps.length) return
    // Allow jumping back freely, or jumping to the next uncompleted step
    if (idx <= currentIdx.value || completed.value.has(idx - 1) || idx === 0) {
      currentIdx.value = idx
    }
  }

  /** Resets the wizard to the first step, clearing all state */
  function reset() {
    currentIdx.value = 0
    completed.value  = new Set()
    errors.value     = new Map()
  }

  /** Returns whether a given step index is reachable (for header click navigation) */
  function isReachable(idx: number): boolean {
    if (idx === 0) return true
    if (idx <= currentIdx.value) return true
    return completed.value.has(idx - 1)
  }

  return {
    // Config
    steps,

    // State
    currentIdx,
    currentStep,
    completed:   readonly(completed),
    errors:      readonly(errors),
    isValidating,

    // Computed
    isFirstStep,
    isLastStep,
    isComplete,
    progress,

    // Actions
    next,
    back,
    goTo,
    reset,
    isReachable,
  }
}

export type WizardInstance = ReturnType<typeof useStepperWizard>
