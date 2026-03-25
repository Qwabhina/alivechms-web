<script setup lang="ts">
import { ref, reactive } from 'vue'
import {
  ChSelect,
  ChCheckbox,
  ChRadio,
  ChSwitch,
  ChSlider,
  ChFileUpload,
  ChDatePicker,
  ChModal,
  ChButton,
  ChStepperWizard,
  ChStepperStep,
  ChTimeline,
  ChTimelineItem,
  useStepperWizard
} from '@/design-system'

const multiSelect = ref([])
const checkboxGroup = ref(['opt1'])
const switchValue = ref(true)
const sliderValue = ref(50)
const files = ref([])
const dateValue = ref(null)

const isModalOpen = ref(false)

const stepper = useStepperWizard([
  { id: 'step1', label: 'Details' },
  { id: 'step2', label: 'Review' }
])
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Forms & Flows</h1>
      <p class="page-desc">Advanced data entry components and multi-step workflows. Featuring extreme focus contrast and flattened bounding boxes.</p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Form Controls</h2>
      <div class="demo-grid">
        <div class="demo-block">
          <div class="demo-title">Select</div>
          <ChSelect
            v-model="multiSelect"
            multiple
            placeholder="Choose options"
            :options="[{value: '1', label: 'Option 1'}, {value: '2', label: 'Option 2'}]"
          />
        </div>
        <div class="demo-block">
          <div class="demo-title">Checkbox & Radio</div>
          <div style="display: flex; flex-direction: column; gap: 1rem">
            <ChCheckbox v-model="checkboxGroup" :options="[{value: 'opt1', label: 'Checked'}, {value: 'opt2', label: 'Unchecked'}]" />
            <ChRadio model-value="1" value="1" label="Selected Radio" />
          </div>
        </div>
        <div class="demo-block">
          <div class="demo-title">Switch</div>
          <ChSwitch v-model="switchValue" label="Feature Enabled" />
        </div>
        <div class="demo-block">
          <div class="demo-title">Slider</div>
          <ChSlider v-model="sliderValue" :min="0" :max="100" />
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Complex Inputs</h2>
      <div class="demo-grid">
        <div class="demo-block">
          <div class="demo-title">File Upload</div>
          <ChFileUpload v-model="files" multiple accept="image/*" />
        </div>
        <div class="demo-block">
          <div class="demo-title">Date Picker</div>
          <ChDatePicker v-model="dateValue" placeholder="Select a date" />
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Modal</h2>
      <div class="demo-block">
        <ChButton @click="isModalOpen = true">Open Modal Overlay</ChButton>
        <ChModal v-model:open="isModalOpen" title="Brutalist Modal" subtitle="Demonstrating mechanical z-depth">
          <p>The modal features a stark absolute offset shadow and zero border radius.</p>
          <template #footer>
            <ChButton variant="ghost" @click="isModalOpen = false">Close</ChButton>
            <ChButton variant="primary" @click="isModalOpen = false">Confirm</ChButton>
          </template>
        </ChModal>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Stepper Wizard & Timeline</h2>
      <div class="demo-grid" style="grid-template-columns: 1fr">
        <div class="demo-block">
          <div class="demo-title">Stepper Wizard</div>
          <ChStepperWizard :wizard="stepper" @finish="stepper.goTo(0)">
            <ChStepperStep step-id="step1" :wizard="stepper">
              <p>Step 1 Content</p>
            </ChStepperStep>
            <ChStepperStep step-id="step2" :wizard="stepper">
              <p>Step 2 Content</p>
            </ChStepperStep>
          </ChStepperWizard>
        </div>
        
        <div class="demo-block">
          <div class="demo-title">Timeline</div>
          <ChTimeline>
            <ChTimelineItem title="Task Started" timestamp="2024-01-01 10:00" variant="primary">
              Initialized brutalist redesign.
            </ChTimelineItem>
            <ChTimelineItem title="Phase 1 Complete" timestamp="2024-01-02 14:00" variant="success">
              Finished colors and spacing tokens.
            </ChTimelineItem>
          </ChTimeline>
        </div>
      </div>
    </section>
  </div>
</template>
