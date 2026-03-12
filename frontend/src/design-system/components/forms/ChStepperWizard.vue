<script setup lang="ts">
/**
 * @component ChStepperWizard
 * @path /frontend/src/design-system/components/forms/ChStepperWizard.vue
 * @description The outer container for a multi-step wizard form.
 * Renders the step progress header and navigation footer.
 * Place ChStepperStep children inside the default slot.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Member registration wizard
 * <script setup>
 * const wizard = useStepperWizard([
 *   { id: 'personal', label: 'Personal Info', validate: () => v$.personal.$validate() },
 *   { id: 'family',   label: 'Family Details' },
 *   { id: 'roles',    label: 'Groups & Roles' },
 *   { id: 'confirm',  label: 'Confirm',       optional: false },
 * ])
 *
 * async function onFinish() {
 *   const ok = await wizard.next()
 *   if (ok) await api.members.create(form)
 * }
 * <\/script>
 *
 * <ChStepperWizard :wizard="wizard" @finish="onFinish" @cancel="router.back()">
 *   <ChStepperStep step-id="personal" :wizard="wizard">
 *     <PersonalInfoForm v-model="form.personal" />
 *   </ChStepperStep>
 *   <ChStepperStep step-id="family" :wizard="wizard">
 *     <FamilyForm v-model="form.family" />
 *   </ChStepperStep>
 *   <ChStepperStep step-id="roles" :wizard="wizard">
 *     <GroupRolesForm v-model="form.roles" />
 *   </ChStepperStep>
 *   <ChStepperStep step-id="confirm" :wizard="wizard">
 *     <ConfirmationSummary :form="form" />
 *   </ChStepperStep>
 * </ChStepperWizard>
 */

import { computed } from 'vue'
import ChButton from '../core/ChButton.vue'
import ChProgress from '../cues/ChProgress.vue'
import type { WizardInstance } from '../../composables/useStepperWizard'

interface Props {
  /** The wizard instance from useStepperWizard() */
  wizard:       WizardInstance
  /** Label for the final step's "Next" button. Default: 'Finish' */
  finishLabel?: string
  /** Label for the "Back" button. Default: 'Back' */
  backLabel?:   string
  /** Label for the "Next" button. Default: 'Next' */
  nextLabel?:   string
  /** Whether to show the progress bar below the step indicators */
  showProgress?: boolean
  /** Whether clicking a completed step header navigates to it */
  clickableSteps?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  finishLabel:    'Finish',
  backLabel:      'Back',
  nextLabel:      'Next',
  showProgress:   true,
  clickableSteps: true,
})

const emit = defineEmits<{
  /** Emitted when the user clicks Finish on the last step (after validation) */
  finish: []
  /** Emitted when the user clicks the Cancel button (if shown via #cancel slot) */
  cancel: []
}>()

const { wizard } = props

async function handleNext() {
  if (wizard.isLastStep.value) {
    const ok = await wizard.next()
    if (ok) emit('finish')
  } else {
    await wizard.next()
  }
}

function handleBack() {
  wizard.back()
}

function handleStepClick(idx: number) {
  if (props.clickableSteps) wizard.goTo(idx)
}

/** Status for each step header indicator */
function stepStatus(idx: number): 'complete' | 'active' | 'upcoming' {
  if (wizard.completed.value.has(idx)) return 'complete'
  if (idx === wizard.currentIdx.value)  return 'active'
  return 'upcoming'
}
</script>

<template>
  <div class="ch-stepper">

    <!-- ── Step header ── -->
    <div class="ch-stepper__header" role="list" aria-label="Form steps">
      <div
        v-for="(step, idx) in wizard.steps"
        :key="step.id"
        class="ch-stepper__step"
        :class="{
          'ch-stepper__step--active':   stepStatus(idx) === 'active',
          'ch-stepper__step--complete': stepStatus(idx) === 'complete',
          'ch-stepper__step--upcoming': stepStatus(idx) === 'upcoming',
          'ch-stepper__step--clickable':clickableSteps && wizard.isReachable(idx),
        }"
        role="listitem"
        :aria-current="idx === wizard.currentIdx.value ? 'step' : undefined"
        @click="handleStepClick(idx)"
      >
        <!-- Step circle indicator -->
        <div class="ch-stepper__indicator">
          <!-- Completed: checkmark -->
          <template v-if="stepStatus(idx) === 'complete'">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
              <path d="M2.5 7l3 3 6-5.5"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </template>
          <!-- Active or upcoming: step number -->
          <template v-else>
            <span class="ch-stepper__num">{{ idx + 1 }}</span>
          </template>
        </div>

        <!-- Connector line between steps -->
        <div v-if="idx < wizard.steps.length - 1" class="ch-stepper__connector" aria-hidden="true">
          <div
            class="ch-stepper__connector-fill"
            :class="{ 'ch-stepper__connector-fill--done': stepStatus(idx) === 'complete' }"
          />
        </div>

        <!-- Step label -->
        <div class="ch-stepper__label-wrap">
          <span class="ch-stepper__label">{{ step.label }}</span>
          <span v-if="step.sublabel" class="ch-stepper__sublabel">{{ step.sublabel }}</span>
          <span v-if="step.optional" class="ch-stepper__optional">Optional</span>
        </div>
      </div>
    </div>

    <!-- Optional progress bar -->
    <ChProgress
      v-if="showProgress"
      :value="wizard.progress.value"
      size="xs"
      variant="primary"
      class="ch-stepper__progress"
    />

    <!-- ── Step content ── -->
    <div class="ch-stepper__body">
      <!-- Per-step validation error -->
      <Transition name="ch-fade">
        <div
          v-if="wizard.errors.value.get(wizard.currentIdx.value)"
          class="ch-stepper__error"
          role="alert"
        >
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
            <circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.2"/>
            <path d="M7 4v3M7 9.5v.25" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
          </svg>
          {{ wizard.errors.value.get(wizard.currentIdx.value) }}
        </div>
      </Transition>

      <!-- Slot: ChStepperStep components go here -->
      <slot />
    </div>

    <!-- ── Footer navigation ── -->
    <div class="ch-stepper__footer">
      <!-- Left: Cancel slot or empty spacer -->
      <div class="ch-stepper__footer-left">
        <slot name="cancel">
          <!-- No cancel button by default — provide via #cancel slot if needed -->
        </slot>
      </div>

      <!-- Right: Back + Next/Finish -->
      <div class="ch-stepper__footer-right">
        <ChButton
          variant="ghost"
          :disabled="wizard.isFirstStep.value"
          @click="handleBack"
        >
          {{ backLabel }}
        </ChButton>

        <ChButton
          variant="primary"
          :loading="wizard.isValidating.value"
          @click="handleNext"
        >
          {{ wizard.isLastStep.value ? finishLabel : nextLabel }}
        </ChButton>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-stepper {
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-6);
  width:          100%;
}

/* ─── Step header ─────────────────────────────────────────────────────────── */
.ch-stepper__header {
  display:  flex;
  position: relative;
}

.ch-stepper__step {
  display:        flex;
  flex-direction: column;
  align-items:    center;
  flex:           1;
  position:       relative;
  gap:            var(--ch-space-2);
}

.ch-stepper__step--clickable { cursor: pointer; }
.ch-stepper__step--upcoming   { opacity: 0.45; }

/* ─── Circle indicator ────────────────────────────────────────────────────── */
.ch-stepper__indicator {
  width:           32px;
  height:          32px;
  border-radius:   var(--ch-radius-full);
  display:         flex;
  align-items:     center;
  justify-content: center;
  border:          2px solid var(--ch-color-border);
  background:      var(--ch-color-surface);
  color:           var(--ch-color-text-subtle);
  font-size:       var(--ch-text-sm);
  font-weight:     var(--ch-font-semibold);
  transition:
    background-color var(--ch-duration-normal) var(--ch-ease-out),
    border-color     var(--ch-duration-normal) var(--ch-ease-out),
    color            var(--ch-duration-normal) var(--ch-ease-out);
  z-index:         1;
  flex-shrink:     0;
}

.ch-stepper__step--active .ch-stepper__indicator {
  border-color:     var(--ch-color-primary);
  color:            var(--ch-color-primary);
  background:       var(--ch-color-primary-subtle);
  box-shadow:       0 0 0 4px var(--ch-color-primary-muted);
}

.ch-stepper__step--complete .ch-stepper__indicator {
  border-color:     var(--ch-color-primary);
  background:       var(--ch-color-primary);
  color:            white;
}

.ch-stepper__num { line-height: 1; }

/* ─── Connector line ──────────────────────────────────────────────────────── */
.ch-stepper__connector {
  position:   absolute;
  top:        16px; /* center of 32px indicator */
  left:       calc(50% + 20px);
  right:      calc(-50% + 20px);
  height:     2px;
  background: var(--ch-color-border);
  overflow:   hidden;
}

.ch-stepper__connector-fill {
  height:     100%;
  width:      0%;
  background: var(--ch-color-primary);
  transition: width var(--ch-duration-slow) var(--ch-ease-out);
}
.ch-stepper__connector-fill--done { width: 100%; }

/* ─── Labels ──────────────────────────────────────────────────────────────── */
.ch-stepper__label-wrap {
  display:        flex;
  flex-direction: column;
  align-items:    center;
  gap:            2px;
  text-align:     center;
}

.ch-stepper__label {
  font-size:   var(--ch-text-xs);
  font-weight: var(--ch-font-semibold);
  color:       var(--ch-color-text-muted);
  white-space: nowrap;
  line-height: var(--ch-leading-snug);
}

.ch-stepper__step--active .ch-stepper__label   { color: var(--ch-color-primary); }
.ch-stepper__step--complete .ch-stepper__label { color: var(--ch-color-text); }

.ch-stepper__sublabel {
  font-size: var(--ch-text-xs);
  color:     var(--ch-color-text-subtle);
}

.ch-stepper__optional {
  font-size:     0.625rem;
  color:         var(--ch-color-text-subtle);
  background:    var(--ch-color-bg-muted);
  border:        1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-full);
  padding:       1px var(--ch-space-1_5);
}

/* ─── Progress bar ────────────────────────────────────────────────────────── */
.ch-stepper__progress { margin-top: calc(-1 * var(--ch-space-3)); }

/* ─── Body ────────────────────────────────────────────────────────────────── */
.ch-stepper__body {
  display:        flex;
  flex-direction: column;
  gap:            var(--ch-space-4);
}

/* ─── Step error ──────────────────────────────────────────────────────────── */
.ch-stepper__error {
  display:       flex;
  align-items:   center;
  gap:           var(--ch-space-2);
  padding:       var(--ch-space-3) var(--ch-space-4);
  background:    var(--ch-color-danger-bg);
  border:        1px solid var(--ch-color-danger);
  border-radius: var(--ch-radius-lg);
  color:         var(--ch-color-danger-fg);
  font-size:     var(--ch-text-sm);
  font-weight:   var(--ch-font-medium);
}

/* ─── Footer ──────────────────────────────────────────────────────────────── */
.ch-stepper__footer {
  display:         flex;
  align-items:     center;
  justify-content: space-between;
  padding-top:     var(--ch-space-4);
  border-top:      1px solid var(--ch-color-border);
}

.ch-stepper__footer-right {
  display:     flex;
  align-items: center;
  gap:         var(--ch-space-2);
}

/* ─── Fade transition for error banner ────────────────────────────────────── */
.ch-fade-enter-active, .ch-fade-leave-active { transition: opacity var(--ch-duration-fast) var(--ch-ease-out); }
.ch-fade-enter-from,   .ch-fade-leave-to     { opacity: 0; }
</style>
