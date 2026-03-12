<script setup lang="ts">
/**
 * @component ChStepperStep
 * @path /frontend/src/design-system/components/forms/ChStepperStep.vue
 * @description A wrapper for a single step's content inside ChStepperWizard.
 * Only renders when its step-id matches the wizard's current step.
 *
 * ─── How it works ────────────────────────────────────────────────────────────
 * Each ChStepperStep checks if its `stepId` matches `wizard.currentStep.id`.
 * When it does, it renders its slot content with a slide-in transition.
 * Steps are kept in the DOM when inactive (v-show, not v-if) to preserve
 * form state (filled fields) when the user navigates back.
 *
 * @example
 * <ChStepperStep step-id="personal" :wizard="wizard">
 *   <PersonalInfoForm v-model="form.personal" />
 * </ChStepperStep>
 */

import { computed } from 'vue'
import type { WizardInstance } from '../../composables/useStepperWizard'

interface Props {
  /** Must match the `id` field in the corresponding WizardStep config */
  stepId: string
  /** The wizard instance from useStepperWizard() */
  wizard: WizardInstance
}

const props = defineProps<Props>()

const isActive = computed(() => props.wizard.currentStep.value?.id === props.stepId)
</script>

<template>
  <!--
    v-show (not v-if) keeps the step in the DOM when inactive.
    This preserves form input state when the user navigates back —
    otherwise Vue would destroy and recreate the form component,
    losing any values the user had already filled in.
  -->
  <div
    v-show="isActive"
    class="ch-stepper-step"
    :aria-hidden="!isActive"
    role="tabpanel"
  >
    <slot />
  </div>
</template>

<style scoped>
.ch-stepper-step {
  width: 100%;
}
</style>
