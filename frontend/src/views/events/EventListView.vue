<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { eventService } from '@/services/event.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { ChurchEvent, EventListFilters } from '@/types'

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

const hasFilters = computed(() => {
  return Object.values(selectedFilters.value).some(v => v !== undefined && v !== '')
})

const columns = [
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

function handlePageChange(page: number) {
  loadEvents(page)
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
  selectedFilters.value = {}
  loadEvents(1)
}

function formatDate(dateStr?: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString()
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
          <ChButton variant="secondary" left-icon="filter" :class="{ active: hasFilters }" @click="handleSearch">Search</ChButton>
        </div>
        <ChButton v-if="hasFilters || searchQuery" variant="ghost" left-icon="x" @click="clearFilters">Clear</ChButton>
      </div>

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
        <template #cell-EventName="{ row }">
          <div class="event-title-cell">
            <ChAvatar :name="row.EventName" size="sm" />
            <span class="event-name">{{ row.EventName }}</span>
          </div>
        </template>

        <template #cell-EventDate="{ row }">
          {{ formatDate(row.EventDate) }}
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
}

.search-bar {
  display: flex;
  gap: var(--ch-space-3);
  flex: 1;
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
</style>
