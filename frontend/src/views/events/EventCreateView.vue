<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { eventService } from '@/services/event.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { EventCreateInput, RecurringType, EventTemplate } from '@/types'

const router = useRouter()
const toast = useToast()

const saving = ref(false)
const form = ref<EventCreateInput>({
  event_title: '',
  event_description: '',
  event_date: '',
  start_time: '',
  end_time: '',
  location: '',
  event_type: '',
  max_attendees: undefined,
  event_status: 'draft',
  recurring_type: 'none',
  recurring_end_date: '',
  template_id: undefined,
})

// Validation
const errors = ref<Record<string, string>>({})

// Recurring options
const recurringOptions: { value: RecurringType; label: string }[] = [
  { value: 'none', label: 'Does not repeat' },
  { value: 'daily', label: 'Daily' },
  { value: 'weekly', label: 'Weekly' },
  { value: 'biweekly', label: 'Every 2 weeks' },
  { value: 'monthly', label: 'Monthly' },
  { value: 'yearly', label: 'Yearly' },
]

// Event types
const eventTypes = [
  { value: 'Sunday Service', label: 'Sunday Service' },
  { value: 'Bible Study', label: 'Bible Study' },
  { value: 'Prayer Meeting', label: 'Prayer Meeting' },
  { value: 'Youth Service', label: 'Youth Service' },
  { value: 'Children Service', label: 'Children Service' },
  { value: 'Choir Practice', label: 'Choir Practice' },
  { value: 'Outreach', label: 'Outreach' },
  { value: 'Conference', label: 'Conference' },
  { value: 'Workshop', label: 'Workshop' },
  { value: 'Other', label: 'Other' },
]

// Templates
const templates = ref<EventTemplate[]>([])
// const loadingTemplates = ref(false)

const showDatePicker = ref(false)
const showTimePicker = ref(false)

const isFormValid = computed(() => {
  return form.value.event_title && form.value.event_date
})

function validateForm() {
  errors.value = {}
  
  if (!form.value.event_title?.trim()) {
    errors.value.event_title = 'Event title is required'
  }
  
  if (!form.value.event_date) {
    errors.value.event_date = 'Event date is required'
  }
  
  if (form.value.start_time && form.value.end_time) {
    if (form.value.start_time >= form.value.end_time) {
      errors.value.end_time = 'End time must be after start time'
    }
  }
  
  if (form.value.recurring_type !== 'none' && !form.value.recurring_end_date) {
    errors.value.recurring_end_date = 'End date is required for recurring events'
  }
  
  return Object.keys(errors.value).length === 0
}

async function handleSubmit() {
  if (!validateForm()) {
    toast.error('Please fix the form errors')
    return
  }

  saving.value = true
  try {
    const response = await eventService.create(form.value)
    if (response.status === 'success') {
      toast.success('Event created successfully')
      router.push('/events')
    }
  } catch {
    toast.error('Failed to create event')
  } finally {
    saving.value = false
  }
}

function handleCancel() {
  router.push('/events')
}

function applyTemplate(template: EventTemplate) {
  form.value.event_type = template.EventType
  form.value.template_id = template.TemplateID
  if (template.DefaultMaxAttendees) {
    form.value.max_attendees = template.DefaultMaxAttendees
  }
  if (template.DefaultLocation) {
    form.value.location = template.DefaultLocation
  }
}

function clearError(field: string) {
  delete errors.value[field]
}
</script>

<template>
  <div class="event-create">
    <ChPageHeader title="Create Event">
      <template #leading>
        <ChBreadcrumb :items="[{ label: 'Events', to: '/events' }, { label: 'Create Event' }]" />
      </template>
    </ChPageHeader>

    <ChCard>

      <form @submit.prevent="handleSubmit">
        <!-- Template Selection -->
        <div class="form-section">
          <h2 class="section-title">Quick Start (Optional)</h2>
          <p class="section-description">Select a template to pre-fill common event settings</p>
          <div class="template-grid">
            <ChCard
              v-for="template in templates"
              :key="template.TemplateID"
              class="template-card"
              :class="{ selected: form.template_id === template.TemplateID }"
              @click="applyTemplate(template)"
            >
              <h3 class="template-name">{{ template.TemplateName }}</h3>
              <p class="template-type">{{ template.EventType }}</p>
            </ChCard>
          </div>
        </div>

        <div class="form-section">
          <h2 class="section-title">Basic Information</h2>
          <ChFormField label="Event Title *" required :error="errors.event_title">
            <ChInput 
              v-model="form.event_title" 
              placeholder="Enter event title" 
              required 
              @input="clearError('event_title')"
            />
          </ChFormField>
          <ChFormField label="Event Type">
            <ChSelect 
              v-model="form.event_type" 
              :options="eventTypes"
              placeholder="Select event type"
            />
          </ChFormField>
          <ChFormField label="Description">
            <ChTextarea v-model="form.event_description" placeholder="Enter event description" :rows="4" />
          </ChFormField>
        </div>

        <div class="form-section">
          <h2 class="section-title">Date & Time</h2>
          <div class="form-row">
            <ChFormField label="Event Date *" required :error="errors.event_date">
              <div class="date-picker-wrapper">
                <ChInput 
                  v-model="form.event_date" 
                  type="date" 
                  required
                  @input="clearError('event_date')"
                />
                <ChButton variant="ghost" left-icon="calendar" @click="showDatePicker = !showDatePicker" />
              </div>
            </ChFormField>
            <ChFormField label="Status">
              <ChSelect 
                v-model="form.event_status" 
                :options="[
                  { value: 'draft', label: 'Draft' },
                  { value: 'published', label: 'Published' },
                ]"
              />
            </ChFormField>
          </div>
          <div class="form-row">
            <ChFormField label="Start Time">
              <div class="time-picker-wrapper">
                <ChInput v-model="form.start_time" type="time" />
                <ChButton variant="ghost" left-icon="clock" @click="showTimePicker = !showTimePicker" />
              </div>
            </ChFormField>
            <ChFormField label="End Time" :error="errors.end_time">
              <ChInput v-model="form.end_time" type="time" @input="clearError('end_time')" />
            </ChFormField>
          </div>
        </div>

        <!-- Recurring Events -->
        <div class="form-section">
          <h2 class="section-title">Recurring Event</h2>
          <ChFormField label="Repeat">
            <ChSelect 
              v-model="form.recurring_type" 
              :options="recurringOptions"
            />
          </ChFormField>
          <ChFormField v-if="form.recurring_type !== 'none'" label="Repeat Until" :error="errors.recurring_end_date">
            <ChInput 
              v-model="form.recurring_end_date" 
              type="date"
              @input="clearError('recurring_end_date')"
            />
          </ChFormField>
        </div>

        <div class="form-section">
          <h2 class="section-title">Location & Capacity</h2>
          <ChFormField label="Location">
            <ChInput v-model="form.location" placeholder="Enter event location" />
          </ChFormField>
          <ChFormField label="Max Attendees">
            <ChInput v-model="form.max_attendees" type="number" placeholder="Maximum number of attendees" min="1" />
          </ChFormField>
        </div>

        <div class="form-actions">
          <ChButton variant="secondary" @click="handleCancel">Cancel</ChButton>
          <ChButton type="submit" variant="primary" :loading="saving" :disabled="!isFormValid" left-icon="save">Create Event</ChButton>
        </div>
      </form>
    </ChCard>
  </div>
</template>

<style scoped>
.event-create { padding: var(--ch-space-6); max-width: 900px; margin: 0 auto; }
.page-header { padding: var(--ch-space-6); }
.page-title { font-size: var(--ch-font-size-xl); font-weight: var(--ch-font-weight-semibold); margin: var(--ch-space-2) 0 0; }
.form-section { padding: var(--ch-space-6); border-bottom: 1px solid var(--ch-color-border-subtle); }
.form-section:last-of-type { border-bottom: none; }
.section-title { font-size: var(--ch-font-size-lg); font-weight: var(--ch-font-weight-semibold); margin-bottom: var(--ch-space-2); }
.section-description { font-size: var(--ch-font-size-sm); color: var(--ch-color-text-secondary); margin-bottom: var(--ch-space-4); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--ch-space-4); }
.form-actions { display: flex; justify-content: flex-end; gap: var(--ch-space-3); padding: var(--ch-space-6); border-top: 1px solid var(--ch-color-border); }

/* Template Grid */
.template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--ch-space-3); margin-bottom: var(--ch-space-4); }
.template-card { cursor: pointer; transition: all 0.2s ease; border: 2px solid transparent; }
.template-card:hover { border-color: var(--ch-color-primary); }
.template-card.selected { border-color: var(--ch-color-primary); background-color: var(--ch-color-primary-subtle); }
.template-name { font-size: var(--ch-font-size-base); font-weight: var(--ch-font-weight-medium); margin: 0 0 var(--ch-space-1); }
.template-type { font-size: var(--ch-font-size-sm); color: var(--ch-color-text-secondary); margin: 0; }

/* Date/Time Pickers */
.date-picker-wrapper, .time-picker-wrapper { display: flex; gap: var(--ch-space-2); }
.date-picker-wrapper .ch-input, .time-picker-wrapper .ch-input { flex: 1; }
</style>
