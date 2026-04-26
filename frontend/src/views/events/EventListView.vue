<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { eventService } from '@/services/event.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { ChurchEvent, EventListFilters, EventViewMode, EventStatus } from '@/types'

const router = useRouter()
const toast = useToast()

const events = ref<ChurchEvent[]>([])
const loading = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const itemsPerPage = 10
const searchQuery = ref('')
const selectedFilters = ref<EventListFilters>({})
const deleteModalOpen = ref(false)
const eventToDelete = ref<ChurchEvent | null>(null)

// View mode
const viewMode = ref<EventViewMode>('list')
const viewModes: { value: EventViewMode; label: string; icon: string }[] = [
  { value: 'list', label: 'List', icon: 'list' },
  { value: 'grid', label: 'Grid', icon: 'grid' },
  { value: 'calendar', label: 'Calendar', icon: 'calendar' },
]

// Advanced filters
const showFilters = ref(false)
const filterDateFrom = ref('')
const filterDateTo = ref('')
const filterEventType = ref('')
const filterEventStatus = ref<EventStatus | ''>('')
const filterUpcoming = ref<string>('')

// Bulk selection
const selectedEvents = ref<Set<number>>(new Set())

// Event types for filter dropdown
const eventTypes = ['Sunday Service', 'Bible Study', 'Prayer Meeting', 'Youth Service', 'Children Service', 'Choir Practice', 'Outreach', 'Conference', 'Workshop', 'Other']

const hasFilters = computed(() => {
  return Object.values(selectedFilters.value).some(v => v !== undefined && v !== '' && v !== null) ||
    filterDateFrom.value || filterDateTo.value || filterEventType.value || filterEventStatus.value !== '' || filterUpcoming.value !== ''
})

const columns = [
  {
    key: 'checkbox',
    label: '',
    width: '40px',
    type: 'slot' as const,
    exportable: false,
  },
  {
    key: 'EventName',
    label: 'Event Title',
    sortable: true,
    type: 'slot' as const,
  },
  {
    key: 'EventDate',
    label: 'Date',
    sortable: true,
    type: 'slot' as const,
  },
  {
    key: 'EventType',
    label: 'Type',
  },
  {
    key: 'Status',
    label: 'Status',
    type: 'slot' as const,
  },
  {
    key: 'Location',
    label: 'Location',
    type: 'slot' as const,
  },
  {
    key: 'actions',
    label: '',
    width: '120px',
    type: 'slot' as const,
    exportable: false,
    align: 'right' as const,
  },
]

async function loadEvents(page: number = 1) {
  loading.value = true
  try {
    const filters: EventListFilters = {
      search: searchQuery.value || undefined,
      start_date: filterDateFrom.value || undefined,
      end_date: filterDateTo.value || undefined,
      event_type: filterEventType.value || undefined,
      event_status: filterEventStatus.value || undefined,
      upcoming_only: filterUpcoming.value === 'upcoming' ? true : undefined,
      past_only: filterUpcoming.value === 'past' ? true : undefined,
      ...selectedFilters.value,
    }
    const response = await eventService.list(page, itemsPerPage, filters)
    if (response.status === 'success' && response.data) {
      events.value = response.data.data
      totalPages.value = response.data.pagination.total_pages
      totalItems.value = response.data.pagination.total
      currentPage.value = page
    }
  } catch {
    toast.error('Failed to load events')
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  loadEvents(1)
}


function navigateToCreate() {
  router.push('/events/create')
}

function navigateToEdit(event: ChurchEvent) {
  router.push(`/events/${event.EventID}/edit`)
}

function navigateToView(event: ChurchEvent) {
  router.push(`/events/${event.EventID}`)
}

function confirmDelete(event: ChurchEvent) {
  eventToDelete.value = event
  deleteModalOpen.value = true
}

async function handleDelete() {
  if (!eventToDelete.value) return
  try {
    await eventService.delete(eventToDelete.value.EventID)
    toast.success('Event deleted successfully')
    loadEvents(currentPage.value)
  } catch {
    toast.error('Failed to delete event')
  } finally {
    deleteModalOpen.value = false
    eventToDelete.value = null
  }
}

function clearFilters() {
  searchQuery.value = ''
  filterDateFrom.value = ''
  filterDateTo.value = ''
  filterEventType.value = ''
  filterEventStatus.value = ''
  filterUpcoming.value = ''
  selectedFilters.value = {}
  loadEvents(1)
}

function toggleEventSelection(eventId: number) {
  if (selectedEvents.value.has(eventId)) {
    selectedEvents.value.delete(eventId)
  } else {
    selectedEvents.value.add(eventId)
  }
}


async function handleBulkDelete() {
  if (selectedEvents.value.size === 0) return
  try {
    for (const eventId of selectedEvents.value) {
      await eventService.delete(eventId)
    }
    toast.success(`${selectedEvents.value.size} events deleted successfully`)
    selectedEvents.value.clear()
    loadEvents(currentPage.value)
  } catch {
    toast.error('Failed to delete selected events')
  }
}

function formatDate(dateStr?: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString()
}

function getStatusColor(status?: string) {
  switch (status) {
    case 'published': return 'success'
    case 'draft': return 'warning'
    case 'cancelled': return 'danger'
    case 'completed': return 'info'
    default: return 'default'
  }
}

onMounted(() => {
  loadEvents()
})
</script>

<template>
  <div class="event-list">
    <ChPageHeader title="Events" subtitle="Manage church events and services">
      <template #actions>
        <ChButton variant="primary" left-icon="plus" @click="navigateToCreate">Create Event</ChButton>
      </template>
    </ChPageHeader>

    <ChCard>

      <div class="filters-section">
        <div class="search-bar">
          <ChInput v-model="searchQuery" placeholder="Search events..." left-icon="search" @keyup.enter="handleSearch" />
          <ChButton variant="secondary" left-icon="filter" :class="{ active: hasFilters }" @click="showFilters = !showFilters">Filters</ChButton>
        </div>
        <div class="view-toggle">
          <ChButton
            v-for="mode in viewModes"
            :key="mode.value"
            variant="ghost"
            :class="{ active: viewMode === mode.value }"
            :left-icon="mode.icon"
            @click="viewMode = mode.value"
          />
        </div>
        <ChButton v-if="hasFilters || searchQuery" variant="ghost" left-icon="x" @click="clearFilters">Clear</ChButton>
      </div>

      <!-- Advanced Filters Panel -->
      <div v-if="showFilters" class="filters-panel">
        <div class="filter-row">
          <ChFormField label="Date From">
            <ChInput v-model="filterDateFrom" type="date" />
          </ChFormField>
          <ChFormField label="Date To">
            <ChInput v-model="filterDateTo" type="date" />
          </ChFormField>
          <ChFormField label="Event Type">
            <ChSelect v-model="filterEventType" :options="eventTypes.map(t => ({ value: t, label: t }))" placeholder="All types" />
          </ChFormField>
          <ChFormField label="Status">
            <ChSelect v-model="filterEventStatus" :options="[
              { value: '', label: 'All statuses' },
              { value: 'draft', label: 'Draft' },
              { value: 'published', label: 'Published' },
              { value: 'cancelled', label: 'Cancelled' },
              { value: 'completed', label: 'Completed' },
            ]" />
          </ChFormField>
          <ChFormField label="Events">
            <ChSelect v-model="filterUpcoming" :options="[
              { value: '', label: 'All events' },
              { value: 'upcoming', label: 'Upcoming' },
              { value: 'past', label: 'Past' },
            ]" />
          </ChFormField>
          <ChButton variant="primary" @click="handleSearch">Apply</ChButton>
        </div>
      </div>

      <!-- Bulk Actions -->
      <div v-if="selectedEvents.size > 0" class="bulk-actions">
        <span class="selected-count">{{ selectedEvents.size }} selected</span>
        <ChButton variant="danger" size="sm" left-icon="trash" @click="handleBulkDelete">Delete Selected</ChButton>
      </div>

      <!-- List View -->
      <template v-if="viewMode === 'list'">
        <ChTable
          :columns="columns"
          :rows="events"
          :total="totalItems"
          :page-size="itemsPerPage"
          v-model:page="currentPage"
          :loading="loading"
          row-key="EventID"
          :empty-message="'No events found'"
          :empty-description="'Create your first event to get started'"
        >
          <template #cell-checkbox="{ row }">
            <ChCheckbox
              :model-value="selectedEvents.has(row.EventID)"
              @update:model-value="toggleEventSelection(row.EventID)"
            />
          </template>

          <template #cell-EventName="{ row }">
            <div class="event-title-cell">
              <ChAvatar :name="row.EventName" size="sm" />
              <span class="event-name">{{ row.EventName }}</span>
            </div>
          </template>

          <template #cell-EventDate="{ row }">
            {{ formatDate(row.EventDate) }}
          </template>

          <template #cell-Status="{ row }">
            <ChBadge :variant="getStatusColor((row as unknown as { Status?: string }).Status)">
              {{ (row as unknown as { Status?: string }).Status || 'draft' }}
            </ChBadge>
          </template>

          <template #cell-Location="{ row }">
            <span v-if="row.Location">{{ row.Location }}</span>
            <span v-else class="no-data">—</span>
          </template>

          <template #cell-actions="{ row }">
            <div class="action-buttons">
              <ChButton variant="ghost" size="sm" left-icon="eye" @click="navigateToView(row)" />
              <ChButton variant="ghost" size="sm" left-icon="edit" @click="navigateToEdit(row)" />
              <ChButton variant="ghost" size="sm" left-icon="trash" @click="confirmDelete(row)" />
            </div>
          </template>
        </ChTable>
      </template>

      <!-- Grid View -->
      <template v-else-if="viewMode === 'grid'">
        <div class="events-grid">
          <ChCard v-for="event in events" :key="event.EventID" class="event-card" @click="navigateToView(event)">
            <div class="card-header">
              <ChBadge :variant="getStatusColor((event as unknown as { Status?: string }).Status)">{{ (event as unknown as { Status?: string }).Status || 'draft' }}</ChBadge>
              <ChButton variant="ghost" size="sm" left-icon="more-vertical" @click.stop />
            </div>
            <h3 class="card-title">{{ event.EventName }}</h3>
            <p class="card-type">{{ event.EventType }}</p>
            <div class="card-details">
              <div class="detail-item">
                <ChIcon name="calendar" />
                <span>{{ formatDate(event.EventDate) }}</span>
              </div>
              <div v-if="event.Location" class="detail-item">
                <ChIcon name="map-pin" />
                <span>{{ event.Location }}</span>
              </div>
            </div>
            <div class="card-actions">
              <ChButton variant="ghost" size="sm" left-icon="edit" @click.stop="navigateToEdit(event)">Edit</ChButton>
              <ChButton variant="ghost" size="sm" left-icon="trash" @click.stop="confirmDelete(event)">Delete</ChButton>
            </div>
          </ChCard>
        </div>
      </template>

      <!-- Calendar View -->
      <template v-else-if="viewMode === 'calendar'">
        <div class="calendar-view">
          <div class="calendar-header">
            <ChButton variant="ghost" left-icon="chevron-left" @click="loadEvents(currentPage - 1)" :disabled="currentPage <= 1">Previous</ChButton>
            <span class="calendar-title">Events Calendar</span>
            <ChButton variant="ghost" left-icon="chevron-right" @click="loadEvents(currentPage + 1)" :disabled="currentPage >= totalPages">Next</ChButton>
          </div>
          <div class="calendar-grid">
            <div v-for="event in events" :key="event.EventID" class="calendar-event" @click="navigateToView(event)">
              <div class="event-date-badge">{{ formatDate(event.EventDate) }}</div>
              <div class="event-info">
                <span class="event-title">{{ event.EventName }}</span>
                <span class="event-type">{{ event.EventType }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </ChCard>

    <ChModal :open="deleteModalOpen" :title="'Delete Event'" @close="deleteModalOpen = false">
      <p>Are you sure you want to delete <strong>{{ eventToDelete?.EventName }}</strong>? This action cannot be undone.</p>
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
.event-list {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
}

.filters-section {
  display: flex;
  gap: var(--ch-space-3);
  padding: var(--ch-space-4) var(--ch-space-6);
  border-bottom: 1px solid var(--ch-color-border);
  align-items: center;
}

.search-bar {
  display: flex;
  gap: var(--ch-space-3);
  flex: 1;
}

.view-toggle {
  display: flex;
  gap: var(--ch-space-1);
  padding: 0 var(--ch-space-2);
  border-left: 1px solid var(--ch-color-border);
  border-right: 1px solid var(--ch-color-border);
}

.view-toggle .ch-button.active {
  background-color: var(--ch-color-primary-subtle);
  color: var(--ch-color-primary);
}

.filters-panel {
  padding: var(--ch-space-4) var(--ch-space-6);
  background-color: var(--ch-color-bg-subtle);
  border-bottom: 1px solid var(--ch-color-border);
}

.filter-row {
  display: flex;
  gap: var(--ch-space-4);
  align-items: flex-end;
  flex-wrap: wrap;
}

.bulk-actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-4);
  padding: var(--ch-space-3) var(--ch-space-6);
  background-color: var(--ch-color-primary-subtle);
  border-bottom: 1px solid var(--ch-color-border);
}

.selected-count {
  font-weight: var(--ch-font-weight-medium);
  color: var(--ch-color-primary);
}

.event-title-cell {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.event-name {
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.no-data {
  color: var(--ch-color-text-subtle);
}

.action-buttons {
  display: flex;
  gap: var(--ch-space-1);
}

.modal-actions {
  display: flex;
  gap: var(--ch-space-3);
  justify-content: flex-end;
}

/* Grid View Styles */
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--ch-space-4);
  padding: var(--ch-space-4);
}

.event-card {
  cursor: pointer;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.event-card:hover {
  box-shadow: var(--ch-shadow-lg);
  transform: translateY(-2px);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--ch-space-3);
}

.card-title {
  font-size: var(--ch-font-size-lg);
  font-weight: var(--ch-font-weight-semibold);
  margin: 0 0 var(--ch-space-1);
}

.card-type {
  font-size: var(--ch-font-size-sm);
  color: var(--ch-color-text-secondary);
  margin: 0 0 var(--ch-space-3);
}

.card-details {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
  margin-bottom: var(--ch-space-3);
}

.detail-item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  font-size: var(--ch-font-size-sm);
  color: var(--ch-color-text-secondary);
}

.card-actions {
  display: flex;
  gap: var(--ch-space-2);
  padding-top: var(--ch-space-3);
  border-top: 1px solid var(--ch-color-border);
}

/* Calendar View Styles */
.calendar-view {
  padding: var(--ch-space-4);
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--ch-space-4);
}

.calendar-title {
  font-size: var(--ch-font-size-lg);
  font-weight: var(--ch-font-weight-semibold);
}

.calendar-grid {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
}

.calendar-event {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  padding: var(--ch-space-3);
  background-color: var(--ch-color-bg-subtle);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.calendar-event:hover {
  background-color: var(--ch-color-primary-subtle);
}

.event-date-badge {
  font-size: var(--ch-font-size-sm);
  font-weight: var(--ch-font-weight-medium);
  color: var(--ch-color-primary);
  white-space: nowrap;
}

.event-info {
  display: flex;
  flex-direction: column;
}

.event-info .event-title {
  font-weight: var(--ch-font-weight-medium);
}

.event-info .event-type {
  font-size: var(--ch-font-size-sm);
  color: var(--ch-color-text-secondary);
}
</style>
