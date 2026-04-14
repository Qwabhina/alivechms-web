<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { eventService } from '@/services/event.service'
import { useToast } from '@/design-system'
import type { EventDetail } from '@/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const eventId = Number(route.params.id)

const loading = ref(false)
const event = ref<EventDetail | null>(null)
const deleteModalOpen = ref(false)

async function loadEvent() {
  loading.value = true
  try {
    const response = await eventService.get(eventId)
    if (response.status === 'success' && response.data) {
      event.value = response.data
    }
  } catch {
    toast.error('Failed to load event details')
  } finally {
    loading.value = false
  }
}

function navigateToEdit() {
  router.push(`/events/${eventId}/edit`)
}

function confirmDelete() {
  deleteModalOpen.value = true
}

async function handleDelete() {
  try {
    await eventService.delete(eventId)
    toast.success('Event deleted successfully')
    router.push('/events')
  } catch {
    toast.error('Failed to delete event')
  } finally {
    deleteModalOpen.value = false
  }
}

function formatDate(dateStr?: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

onMounted(loadEvent)
</script>

<template>
  <div class="event-detail">
    <div v-if="loading" class="loading-state"><ChSpinner size="lg" /><span>Loading event details...</span></div>

    <template v-else-if="event">
      <ChCard class="header-card">
        <template #header>
          <div class="event-header">
            <div class="header-content">
              <ChBreadcrumb :items="[{ label: 'Events', to: '/events' }, { label: event.EventTitle }]" />
              <div class="event-title">
                <ChAvatar :name="event.EventTitle" size="xl" />
                <div class="title-content">
                  <h1 class="event-name">{{ event.EventTitle }}</h1>
                  <p v-if="event.EventType" class="event-type">{{ event.EventType }}</p>
                </div>
              </div>
            </div>
            <div class="header-actions">
              <ChButton variant="secondary" left-icon="edit" @click="navigateToEdit">Edit</ChButton>
              <ChButton variant="danger" left-icon="trash" @click="confirmDelete">Delete</ChButton>
            </div>
          </div>
        </template>
      </ChCard>

      <div class="detail-grid">
        <ChCard>
          <template #header><h2 class="section-title">Event Information</h2></template>
          <div class="info-section">
            <div class="info-row"><span class="info-label">Date</span><span class="info-value">{{ formatDate(event.EventDate) }}</span></div>
            <div class="info-row"><span class="info-label">Time</span><span class="info-value">{{ event.StartTime || '—' }} - {{ event.EndTime || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Location</span><span class="info-value">{{ event.Location || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Max Attendees</span><span class="info-value">{{ event.MaxAttendees || '—' }}</span></div>
          </div>
        </ChCard>

        <ChCard>
          <template #header><h2 class="section-title">Description</h2></template>
          <p class="description-text">{{ event.EventDescription || 'No description available.' }}</p>
        </ChCard>

        <ChCard>
          <template #header><h2 class="section-title">Attendance</h2></template>
          <div class="attendance-stats">
            <div class="stat-item"><span class="stat-label">Expected</span><span class="stat-value">{{ event.expectedAttendees || 0 }}</span></div>
            <div class="stat-item"><span class="stat-label">Attended</span><span class="stat-value">{{ event.totalAttendees || 0 }}</span></div>
          </div>
        </ChCard>
      </div>
    </template>

    <ChModal :open="deleteModalOpen" :title="'Delete Event'" @close="deleteModalOpen = false">
      <p>Are you sure you want to delete <strong>{{ event?.EventTitle }}</strong>? This action cannot be undone.</p>
      <template #footer>
        <div class="modal-actions">
          <ChButton variant="secondary" @click="deleteModalOpen = false">Cancel</ChButton>
          <ChButton variant="danger" @click="handleDelete">Delete</ChButton>
        </div>
      </template>
    </ChModal>
  </div>
</template>

<style scoped>
.event-detail { padding: var(--ch-space-6); }
.loading-state { display: flex; flex-direction: column; align-items: center; gap: var(--ch-space-4); padding: var(--ch-space-12); color: var(--ch-color-text-secondary); }
.header-card { margin-bottom: var(--ch-space-6); }
.event-header { display: flex; justify-content: space-between; align-items: flex-start; padding: var(--ch-space-6); }
.header-content { display: flex; flex-direction: column; gap: var(--ch-space-4); }
.event-title { display: flex; align-items: center; gap: var(--ch-space-4); }
.title-content { display: flex; flex-direction: column; }
.event-name { font-size: var(--ch-font-size-2xl); font-weight: var(--ch-font-weight-bold); margin: 0; }
.event-type { font-size: var(--ch-font-size-sm); color: var(--ch-color-text-secondary); margin: var(--ch-space-1) 0 0; }
.header-actions { display: flex; gap: var(--ch-space-2); }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--ch-space-6); }
@media (max-width: 1024px) { .detail-grid { grid-template-columns: 1fr; } }
.section-title { font-size: var(--ch-font-size-lg); font-weight: var(--ch-font-weight-semibold); margin: 0; }
.info-section { display: flex; flex-direction: column; gap: var(--ch-space-4); padding: var(--ch-space-4); }
.info-row { display: flex; justify-content: space-between; align-items: center; padding: var(--ch-space-2) 0; border-bottom: 1px solid var(--ch-color-border-subtle); }
.info-row:last-child { border-bottom: none; }
.info-label { font-weight: var(--ch-font-weight-medium); color: var(--ch-color-text-secondary); }
.info-value { color: var(--ch-color-text); }
.description-text { padding: var(--ch-space-4); color: var(--ch-color-text); line-height: 1.6; }
.attendance-stats { display: flex; gap: var(--ch-space-8); padding: var(--ch-space-4); }
.stat-item { display: flex; flex-direction: column; align-items: center; }
.stat-label { font-size: var(--ch-font-size-sm); color: var(--ch-color-text-secondary); }
.stat-value { font-size: var(--ch-font-size-2xl); font-weight: var(--ch-font-weight-bold); color: var(--ch-color-text); }
.modal-actions { display: flex; gap: var(--ch-space-3); justify-content: flex-end; }
</style>
