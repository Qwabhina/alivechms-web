<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { eventService } from '@/services/event.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { EventCreateInput } from '@/types'

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
})

async function handleSubmit() {
  if (!form.value.event_title || !form.value.event_date) {
    toast.error('Event title and date are required')
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
        <div class="form-section">
          <h2 class="section-title">Basic Information</h2>
          <ChFormField label="Event Title *" required>
            <ChInput v-model="form.event_title" placeholder="Enter event title" required />
          </ChFormField>
          <ChFormField label="Event Type">
            <ChInput v-model="form.event_type" placeholder="e.g., Sunday Service, Bible Study" />
          </ChFormField>
          <ChFormField label="Description">
            <ChTextarea v-model="form.event_description" placeholder="Enter event description" :rows="3" />
          </ChFormField>
        </div>

        <div class="form-section">
          <h2 class="section-title">Date & Time</h2>
          <ChFormField label="Event Date *" required>
            <ChInput v-model="form.event_date" type="date" required />
          </ChFormField>
          <div class="form-row">
            <ChFormField label="Start Time">
              <ChInput v-model="form.start_time" type="time" />
            </ChFormField>
            <ChFormField label="End Time">
              <ChInput v-model="form.end_time" type="time" />
            </ChFormField>
          </div>
        </div>

        <div class="form-section">
          <h2 class="section-title">Location & Capacity</h2>
          <ChFormField label="Location">
            <ChInput v-model="form.location" placeholder="Enter event location" />
          </ChFormField>
          <ChFormField label="Max Attendees">
            <ChInput v-model="form.max_attendees" type="number" placeholder="Maximum number of attendees" />
          </ChFormField>
        </div>

        <div class="form-actions">
          <ChButton variant="secondary" @click="handleCancel">Cancel</ChButton>
          <ChButton type="submit" variant="primary" :loading="saving" left-icon="save">Create Event</ChButton>
        </div>
      </form>
    </ChCard>
  </div>
</template>

<style scoped>
.event-create { padding: var(--ch-space-6); max-width: 800px; margin: 0 auto; }
.page-header { padding: var(--ch-space-6); }
.page-title { font-size: var(--ch-font-size-xl); font-weight: var(--ch-font-weight-semibold); margin: var(--ch-space-2) 0 0; }
.form-section { padding: var(--ch-space-6); }
.section-title { font-size: var(--ch-font-size-lg); font-weight: var(--ch-font-weight-semibold); margin-bottom: var(--ch-space-4); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--ch-space-4); }
.form-actions { display: flex; justify-content: flex-end; gap: var(--ch-space-3); padding: var(--ch-space-6); border-top: 1px solid var(--ch-color-border); }
</style>
