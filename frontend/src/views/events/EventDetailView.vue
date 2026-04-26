<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { eventService } from '@/services/event.service'
import { useToast } from '@/design-system'
import type { EventDetail, EventRegistration, EventVolunteer, EventResource, EventStatus } from '@/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const eventId = Number(route.params.id)

const loading = ref(false)
const event = ref<EventDetail | null>(null)
const deleteModalOpen = ref(false)

// Tab state
const activeTab = ref('info')

// Attendee management
const showAddAttendeeModal = ref(false)
const newAttendeeEmail = ref('')
const attendees = ref<EventRegistration[]>([])
const loadingAttendees = ref(false)

// Volunteer management
const showAddVolunteerModal = ref(false)
const volunteers = ref<EventVolunteer[]>([])
const loadingVolunteers = ref(false)

// Check-in
const checkInSearch = ref('')
const checkInResults = ref<Array<{ MemberID: number; MemberName: string; Email?: string }>>([])
const loadingCheckIn = ref(false)

// Event status
const statusOptions: { value: EventStatus; label: string }[] = [
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'completed', label: 'Completed' },
]

// Resources
const resources = ref<EventResource[]>([])
const loadingResources = ref(false)

const tabs = [
  { value: 'info', label: 'Information' },
  { value: 'attendees', label: 'Attendees' },
  { value: 'volunteers', label: 'Volunteers' },
  { value: 'checkin', label: 'Check-In' },
  { value: 'resources', label: 'Resources' },
]

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

async function loadAttendees() {
  loadingAttendees.value = true
  try {
    const response = await eventService.getRegistrations(eventId)
    if (response.status === 'success' && response.data) {
      attendees.value = response.data
    }
  } catch {
    toast.error('Failed to load attendees')
  } finally {
    loadingAttendees.value = false
  }
}

async function loadVolunteers() {
  loadingVolunteers.value = true
  try {
    const response = await eventService.getVolunteers(eventId)
    if (response.status === 'success' && response.data) {
      volunteers.value = response.data
    }
  } catch {
    toast.error('Failed to load volunteers')
  } finally {
    loadingVolunteers.value = false
  }
}

async function loadResources() {
  loadingResources.value = true
  try {
    const response = await eventService.getResources(eventId)
    if (response.status === 'success' && response.data) {
      resources.value = response.data
    }
  } catch {
    toast.error('Failed to load resources')
  } finally {
    loadingResources.value = false
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

async function handleStatusChange(newStatus: unknown) {
  if (!event.value) return
  const validStatus = newStatus as EventStatus
  if (!validStatus) return
  try {
    await eventService.updateStatus(eventId, validStatus)
    ;(event.value as any).Status = validStatus
    toast.success('Event status updated')
  } catch {
    toast.error('Failed to update status')
  }
}

async function addAttendee() {
  if (!newAttendeeEmail.value) return
  try {
    await eventService.registerAttendee(eventId, { email: newAttendeeEmail.value })
    toast.success('Attendee added successfully')
    showAddAttendeeModal.value = false
    newAttendeeEmail.value = ''
    loadAttendees()
  } catch {
    toast.error('Failed to add attendee')
  }
}

async function removeAttendee(registrationId: number) {
  try {
    await eventService.unregisterAttendee(eventId, registrationId)
    toast.success('Attendee removed successfully')
    loadAttendees()
  } catch {
    toast.error('Failed to remove attendee')
  }
}

async function addVolunteer() {
  if (!event.value) return
  try {
    await eventService.assignVolunteer(eventId, { member_id: 1, role: 'Helper' })
    toast.success('Volunteer assigned successfully')
    showAddVolunteerModal.value = false
    loadVolunteers()
  } catch {
    toast.error('Failed to assign volunteer')
  }
}

async function loadCheckIn() {
  if (!checkInSearch.value) return
  loadingCheckIn.value = true
  try {
    const response = await eventService.getCheckInList(eventId)
    if (response.status === 'success' && response.data) {
      checkInResults.value = response.data
        .filter(c => c.MemberName?.toLowerCase().includes(checkInSearch.value.toLowerCase()))
        .map(c => ({ MemberID: c.MemberID, MemberName: c.MemberName, Email: c.CheckedInBy?.toString() }))
    }
  } catch {
    toast.error('Failed to search attendees')
  } finally {
    loadingCheckIn.value = false
  }
}

async function checkInMember(memberId: number) {
  try {
    await eventService.checkIn(eventId, memberId)
    toast.success('Member checked in successfully')
    // Refresh the check-in list if we have an active search
    if (checkInSearch.value) {
      loadCheckIn()
    }
  } catch {
    toast.error('Failed to check in member')
  }
}

function downloadResource(resource: EventResource) {
  const url = resource.Url || resource.FilePath
  if (url) {
    window.open(url, '_blank')
  }
}

function formatDate(dateStr?: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

function getStatusColor(status?: string): 'success' | 'warning' | 'danger' | 'info' | 'default' {
  switch (status) {
    case 'published': return 'success'
    case 'draft': return 'warning'
    case 'cancelled': return 'danger'
    case 'completed': return 'info'
    default: return 'default'
  }
}

async function onTabChange(tabValue: string | string[]) {
  const tabId = Array.isArray(tabValue) ? tabValue[0] : tabValue
  if (typeof tabId === 'string') {
    activeTab.value = tabId
    if (tabId === 'attendees') loadAttendees()
    if (tabId === 'volunteers') loadVolunteers()
    if (tabId === 'checkin') loadCheckIn()
    if (tabId === 'resources') loadResources()
  }
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
              <ChSelect
                :model-value="(event as { Status?: string }).Status || 'draft'"
                :options="statusOptions"
                @update:model-value="handleStatusChange"
                style="width: 150px"
              />
              <ChButton variant="secondary" left-icon="edit" @click="navigateToEdit">Edit</ChButton>
              <ChButton variant="danger" left-icon="trash" @click="confirmDelete">Delete</ChButton>
            </div>
          </div>
        </template>
      </ChCard>

      <!-- Tabs -->
      <ChTabs v-model="activeTab" :tabs="tabs" @change="onTabChange" />

      <!-- Info Tab -->
      <div v-if="activeTab === 'info'" class="detail-grid">
        <ChCard>
          <template #header><h2 class="section-title">Event Information</h2></template>
          <div class="info-section">
            <div class="info-row"><span class="info-label">Date</span><span class="info-value">{{ formatDate(event.EventDate) }}</span></div>
            <div class="info-row"><span class="info-label">Time</span><span class="info-value">{{ event.StartTime || '—' }} - {{ event.EndTime || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Location</span><span class="info-value">{{ event.Location || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Max Attendees</span><span class="info-value">{{ event.MaxAttendees || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Status</span><span class="info-value"><ChBadge :variant="(event as any).Status ? getStatusColor((event as any).Status) : 'default'">{{ (event as any).Status || 'draft' }}</ChBadge></span></div>
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

      <!-- Attendees Tab -->
      <div v-else-if="activeTab === 'attendees'" class="tab-content">
        <ChCard>
          <template #header>
            <div class="tab-header">
              <h2 class="section-title">Registered Attendees</h2>
              <ChButton variant="primary" left-icon="plus" @click="showAddAttendeeModal = true">Add Attendee</ChButton>
            </div>
          </template>
          <div v-if="loadingAttendees" class="loading-state"><ChSpinner /><span>Loading attendees...</span></div>
          <div v-else-if="attendees.length === 0" class="empty-state">
            <p>No attendees registered yet.</p>
          </div>
          <ChTable v-else :columns="attendeeColumns" :rows="attendees" row-key="RegistrationID">
            <template #cell-Status="{ row }">
              <ChBadge :variant="row.Status === 'registered' ? 'success' : row.Status === 'waitlisted' ? 'warning' : 'danger'">
                {{ row.Status }}
              </ChBadge>
            </template>
            <template #cell-actions="{ row }">
              <ChButton variant="ghost" size="sm" left-icon="trash" @click="removeAttendee(row.RegistrationID)" />
            </template>
          </ChTable>
        </ChCard>
      </div>

      <!-- Volunteers Tab -->
      <div v-else-if="activeTab === 'volunteers'" class="tab-content">
        <ChCard>
          <template #header>
            <div class="tab-header">
              <h2 class="section-title">Event Volunteers</h2>
              <ChButton variant="primary" left-icon="plus" @click="showAddVolunteerModal = true">Assign Volunteer</ChButton>
            </div>
          </template>
          <div v-if="loadingVolunteers" class="loading-state"><ChSpinner /><span>Loading volunteers...</span></div>
          <div v-else-if="volunteers.length === 0" class="empty-state">
            <p>No volunteers assigned yet.</p>
          </div>
          <ChTable v-else :columns="volunteerColumns" :rows="volunteers" row-key="VolunteerID">
            <template #cell-Status="{ row }">
              <ChBadge :variant="row.Status === 'confirmed' ? 'success' : 'warning'">
                {{ row.Status }}
              </ChBadge>
            </template>
          </ChTable>
        </ChCard>
      </div>

      <!-- Check-In Tab -->
      <div v-else-if="activeTab === 'checkin'" class="tab-content">
        <ChCard>
          <template #header><h2 class="section-title">Attendance Check-In</h2></template>
          <div class="checkin-section">
            <ChInput v-model="checkInSearch" placeholder="Search member by name or ID..." left-icon="search" />
            <ChButton variant="primary" @click="loadCheckIn">Search</ChButton>
          </div>
          <div v-if="loadingCheckIn" class="loading-state"><ChSpinner /><span>Searching...</span></div>
          <div v-else-if="checkInResults.length > 0" class="checkin-results">
            <div v-for="member in checkInResults" :key="member.MemberID" class="checkin-item">
              <div class="member-info">
                <ChAvatar :name="member.MemberName" size="sm" />
                <span>{{ member.MemberName }}</span>
              </div>
              <ChButton variant="primary" size="sm" @click="checkInMember(member.MemberID)">Check In</ChButton>
            </div>
          </div>
        </ChCard>
      </div>

      <!-- Resources Tab -->
      <div v-else-if="activeTab === 'resources'" class="tab-content">
        <ChCard>
          <template #header><h2 class="section-title">Event Resources & Materials</h2></template>
          <div v-if="loadingResources" class="loading-state"><ChSpinner /><span>Loading resources...</span></div>
          <div v-else-if="resources.length === 0" class="empty-state">
            <p>No resources uploaded yet.</p>
          </div>
          <div v-else class="resources-list">
            <div v-for="resource in resources" :key="resource.ResourceID" class="resource-item">
              <ChIcon :name="resource.ResourceType === 'link' ? 'link' : 'file'" />
              <span class="resource-name">{{ resource.ResourceName }}</span>
              <ChButton variant="ghost" size="sm" left-icon="download" @click="downloadResource(resource)">Download</ChButton>
            </div>
          </div>
        </ChCard>
      </div>
    </template>

    <!-- Add Attendee Modal -->
    <ChModal :open="showAddAttendeeModal" title="Add Attendee" @close="showAddAttendeeModal = false">
      <ChFormField label="Email Address">
        <ChInput v-model="newAttendeeEmail" type="email" placeholder="Enter attendee email" />
      </ChFormField>
      <template #footer>
        <ChButton variant="secondary" @click="showAddAttendeeModal = false">Cancel</ChButton>
        <ChButton variant="primary" @click="addAttendee">Add</ChButton>
      </template>
    </ChModal>

    <!-- Add Volunteer Modal -->
    <ChModal :open="showAddVolunteerModal" title="Assign Volunteer" @close="showAddVolunteerModal = false">
      <p>Select a member to assign as volunteer for this event.</p>
      <template #footer>
        <ChButton variant="secondary" @click="showAddVolunteerModal = false">Cancel</ChButton>
        <ChButton variant="primary" @click="addVolunteer">Assign</ChButton>
      </template>
    </ChModal>

    <!-- Delete Confirmation Modal -->
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

<script lang="ts">
const attendeeColumns = [
  { key: 'MemberName', label: 'Name' },
  { key: 'Email', label: 'Email' },
  { key: 'RegisteredAt', label: 'Registered', type: 'slot' as const },
  { key: 'Status', label: 'Status', type: 'slot' as const },
  { key: 'actions', label: '', width: '60px', type: 'slot' as const, exportable: false },
]

const volunteerColumns = [
  { key: 'MemberName', label: 'Name' },
  { key: 'Role', label: 'Role' },
  { key: 'AssignedAt', label: 'Assigned', type: 'slot' as const },
  { key: 'Status', label: 'Status', type: 'slot' as const },
]
</script>

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
.header-actions { display: flex; gap: var(--ch-space-2); align-items: center; }
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

/* Tab Content Styles */
.tab-content { margin-top: var(--ch-space-6); }
.tab-header { display: flex; justify-content: space-between; align-items: center; }
.empty-state { padding: var(--ch-space-8); text-align: center; color: var(--ch-color-text-secondary); }

/* Check-In Styles */
.checkin-section { display: flex; gap: var(--ch-space-3); margin-bottom: var(--ch-space-4); }
.checkin-results { display: flex; flex-direction: column; gap: var(--ch-space-2); margin-top: var(--ch-space-4); }
.checkin-item { display: flex; justify-content: space-between; align-items: center; padding: var(--ch-space-3); background-color: var(--ch-color-bg-subtle); border-radius: var(--ch-radius-md); }
.member-info { display: flex; align-items: center; gap: var(--ch-space-3); }

/* Resources Styles */
.resources-list { display: flex; flex-direction: column; gap: var(--ch-space-2); }
.resource-item { display: flex; align-items: center; gap: var(--ch-space-3); padding: var(--ch-space-3); background-color: var(--ch-color-bg-subtle); border-radius: var(--ch-radius-md); }
.resource-name { flex: 1; }
</style>
