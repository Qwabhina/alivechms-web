<script setup lang="ts">
/**
 * FormPatternsView.vue - Advanced Form Flows
 * 
 * Documents complex interactions for data entry and wizards.
 */

import { ref } from 'vue'
import { ChCard, ChButton, ChInput } from '@/design-system'
import { 
  CheckCircle2, 
  ArrowRight, 
  ArrowLeft,
  AlertCircle,
  Clock
} from 'lucide-vue-next'

const currentStep = ref(1)
const steps = [
  { id: 1, label: 'Member Type', status: 'complete' },
  { id: 2, label: 'Basic Info', status: 'active' },
  { id: 3, label: 'Ministry Assignment', status: 'pending' },
  { id: 4, label: 'Confirmation', status: 'pending' }
]

const nextStep = () => { if (currentStep.value < 4) currentStep.value++ }
const prevStep = () => { if (currentStep.value > 1) currentStep.value-- }
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Form Patterns</h1>
      <p class="page-desc">
        Standardized approaches for handling complex data entry, multi-step 
        processes, and validation feedback.
      </p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Multi-Step Wizards</h2>
      <p>
        For complex tasks like new member onboarding, break the process into 
        logical steps to prevent user fatigue.
      </p>

      <ChCard padding="none">
        <div class="wizard-container">
          <!-- Wizard Sidebar -->
          <div class="wizard-sidebar">
            <div 
              v-for="step in steps" 
              :key="step.id" 
              class="step-item"
              :class="{ 
                'active': currentStep === step.id,
                'completed': currentStep > step.id
              }"
            >
              <div class="step-icon">
                <CheckCircle2 v-if="currentStep > step.id" :size="18" />
                <span v-else>{{ step.id }}</span>
              </div>
              <div class="step-label">{{ step.label }}</div>
            </div>
          </div>

          <!-- Wizard Content -->
          <div class="wizard-main">
            <div class="wizard-header">
              <h3 class="step-title">Step {{ currentStep }}: {{ steps[currentStep-1]?.label }}</h3>
              <p class="step-desc">Please fill out the required information below to proceed.</p>
            </div>

            <div class="wizard-content">
              <div v-if="currentStep === 1" class="step-form">
                <div class="selection-grid">
                  <div class="selection-card active">
                    <h4 class="card-title">Regular Member</h4>
                    <p>Standard membership with full voting rights.</p>
                  </div>
                  <div class="selection-card">
                    <h4 class="card-title">Associate</h4>
                    <p>Non-voting membership for temporary attendees.</p>
                  </div>
                </div>
              </div>

              <div v-if="currentStep === 2" class="step-form">
                <div class="input-stack">
                  <ChInput label="First Name" placeholder="e.g. Samuel" />
                  <ChInput label="Last Name" placeholder="e.g. Addo" />
                  <ChInput label="Email Address" type="email" placeholder="samuel@example.com" />
                </div>
              </div>

              <div v-else-if="currentStep > 2" class="placeholder-step">
                <Clock :size="48" />
                <p>Advanced form logic demonstration...</p>
              </div>
            </div>

            <div class="wizard-footer">
              <ChButton variant="secondary" :disabled="currentStep === 1" @click="prevStep">
                <template #icon><ArrowLeft :size="16" /></template>
                Back
              </ChButton>
              <ChButton variant="primary" @click="nextStep">
                {{ currentStep === 4 ? 'Finish' : 'Next Step' }}
                <template #trailingIcon><ArrowRight :size="16" /></template>
              </ChButton>
            </div>
          </div>
        </div>
      </ChCard>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Validation Patterns</h2>
      <div class="card-grid card-grid--2">
        <ChCard padding="lg">
          <div class="pattern-header">
            <div class="header-icon success">
              <CheckCircle2 :size="20" />
            </div>
            <h3>Inline Success</h3>
          </div>
          <p>
             provide immediate positive feedback when a field meets requirements, 
             especially for unique checks like usernames.
          </p>
          <ChInput label="Username" model-value="grace_mensah" :success="true" />
        </ChCard>

        <ChCard padding="lg">
          <div class="pattern-header">
            <div class="header-icon danger">
              <AlertCircle :size="20" />
            </div>
            <h3>Critical Error</h3>
          </div>
          <p>
            Clear, high-contrast error messages with icons. Never rely on 
            color alone to communicate errors.
          </p>
          <ChInput 
            label="Email" 
            model-value="invalid-email" 
            error="Please enter a valid email address." 
          />
        </ChCard>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Density Variations</h2>
      <div class="demo-block">
        <div class="density-demo">
          <div class="density-group">
            <h4 class="group-label">Standard Density</h4>
            <div class="input-stack">
              <ChInput label="Name" placeholder="Standard spacing" />
              <ChInput label="Role" placeholder="Standard spacing" />
            </div>
          </div>
          <div class="density-divider"></div>
          <div class="density-group compact">
            <h4 class="group-label">Compact Density</h4>
            <div class="input-stack">
              <ChInput label="Name" size="sm" placeholder="Tight spacing" />
              <ChInput label="Role" size="sm" placeholder="Tight spacing" />
            </div>
          </div>
        </div>
        <p class="demo-description">
          Use compact density for data-intensive administrative screens, and 
          standard density for public-facing or mobile-first forms.
        </p>
      </div>
    </section>
  </div>
</template>

<style scoped>
.wizard-container {
  display: grid;
  grid-template-columns: 240px 1fr;
  min-height: 400px;
}

@media (max-width: 768px) {
  .wizard-container {
    grid-template-columns: 1fr;
  }
  .wizard-sidebar {
    display: flex;
    overflow-x: auto;
    border-right: none !important;
    border-bottom: 1px solid var(--ch-color-border-strong);
    padding: var(--ch-space-4) !important;
  }
}

.wizard-sidebar {
  background: var(--ch-color-bg-subtle);
  border-right: 1px solid var(--ch-color-border-strong);
  padding: var(--ch-space-6);
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

.step-item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  opacity: 0.5;
  transition: all 0.2s ease;
}

.step-item.active { opacity: 1; font-weight: var(--ch-font-bold); }
.step-item.completed { opacity: 0.8; color: var(--ch-color-success); }

.step-icon {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid currentColor;
  border-radius: 50%;
  font-size: var(--ch-text-xs);
}

.step-item.active .step-icon {
  background: var(--ch-color-primary);
  border-color: var(--ch-color-primary);
  color: white;
}

.wizard-main {
  display: flex;
  flex-direction: column;
  padding: var(--ch-space-8);
}

.wizard-header { margin-bottom: var(--ch-space-8); }
.step-title { font-family: var(--ch-font-display); font-size: var(--ch-text-2xl); margin: 0; }
.step-desc { color: var(--ch-color-text-muted); margin-top: 4px; }

.wizard-content { flex: 1; }

.selection-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-4);
}

.selection-card {
  padding: var(--ch-space-6);
  border: 1px solid var(--ch-color-border-strong);
  cursor: pointer;
  transition: all 0.2s ease;
}

.selection-card.active {
  border-color: var(--ch-color-primary);
  background: var(--ch-color-bg-subtle);
  box-shadow: var(--ch-shadow-sm);
}

.input-stack {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
  max-width: 400px;
}

.placeholder-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: var(--ch-color-text-subtle);
}

.wizard-footer {
  margin-top: var(--ch-space-8);
  padding-top: var(--ch-space-6);
  border-top: 1px solid var(--ch-color-border);
  display: flex;
  justify-content: space-between;
}

.pattern-header {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  margin-bottom: var(--ch-space-4);
}

.header-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border-strong);
}

.header-icon.success { color: var(--ch-color-success); border-color: var(--ch-color-success); }
.header-icon.danger { color: var(--ch-color-danger); border-color: var(--ch-color-danger); }

.density-demo {
  display: grid;
  grid-template-columns: 1fr 1px 1fr;
  gap: var(--ch-space-12);
  padding: var(--ch-space-6);
}

.density-divider { background: var(--ch-color-border); }

.group-label { margin-bottom: var(--ch-space-6); font-weight: var(--ch-font-bold); }
</style>
