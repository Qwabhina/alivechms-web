<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { eventService } from '@/services/event.service'
import { useToast, confirm } from '@/design-system'
import type { ChurchEvent, EventListFilters } from '@/types'
import {
  Calendar,
  Search,
  Plus,
  LayoutGrid,
  List,
  Filter,
  X,
  ChevronLeft,
  ChevronRight,
  CalendarDays,
  MapPin,
  Users,
  Clock,
  Check,
  MoreVertical,
  Trash2,
  Download,
} from '@lucide/vue'

// ─── Types ────────────────────────────────────────────────────────────────────

type ViewMode = 'list' | 'grid' | 'calendar'
type EventStatus = 'upcoming' | 'past' | 'all'

// ─── Setup ────────────────────────────────────────────────────────────────────

const router = useRouter()
const toast = useToast()

// ─── State ────────────────────────────────────────────────────────────────────

const events = ref<ChurchEvent[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(24)
const isLoading = ref(false)
const search = ref('')
const viewMode = ref<ViewMode>('grid')
const statusFilter = ref<EventStatus>('all')
const typeFilter = ref('')
const dateRangeStart = ref('')
const dateRangeEnd = ref('')
const sortBy = ref('EventDate')
const sortDir = ref<'ASC' | 'DESC'>('ASC')
const selectedEvents = ref<Set<number>>(new Set())
const showFilters = ref(false)
const currentMonth = ref(new Date())

// ─── Calendar State ───────────────────────────────────────────────────────────

const calendarEvents = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const daysInMonth = lastDay.getDate()
  const startingDayOfWeek = firstDay.getDay()

  const days = []

  // Previous month days
  for (let i = startingDayOfWeek - 1; i >= 0; i--) {
    const prevDate = new Date(year, month, -i)
    days.push({ date: prevDate, events: [], isCurrentMonth: false })
  }

  // Current month days
  for (let i = 1; i <= daysInMonth; i++) {
    const date = new Date(year, month, i)
    const dayEvents = events.value.filter(e => {
      if (!e.EventDate) return false
      const eventDate = new Date(e.EventDate)
      return eventDate.toDateString() === date.toDateString()
    })
    days.push({ date, events: dayEvents, isCurrentMonth: true })
  }

  // Next month days to fill grid
  const remainingDays = 42 - days.length
  for (let i = 1; i <= remainingDays; i++) {
    const nextDate = new Date(year, month + 1, i)
    days.push({ date: nextDate, events: [], isCurrentMonth: false })
  }

  return days
})

const calendarMonthYear = computed(() => {
  return currentMonth.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})

// ─── Computed ─────────────────────────────────────────────────────────────────

const sortOptions = [
  { value: 'EventDate', label: 'Event Date' },
  { value: 'EventName', label: 'Event Name' },
  { value: 'EventType', label: 'Event Type' },
]

const typeOptions = [
  { value: '', label: 'All Types' },
  { value: 'Sunday Service', label: 'Sunday Service' },
  { value: 'Bible Study', label: 'Bible Study' },
  { value: 'Prayer Meeting', label: 'Prayer Meeting' },
  { value: 'Youth Event', label: 'Youth Event' },
  { value: 'Fellowship', label: 'Fellowship' },
  { value: 'Conference', label: 'Conference' },
  { value: 'Workshop', label: 'Workshop' },
  { value: 'Other', label: 'Other' },
]

const totalPages = computed(() => Math.ceil(total.value / pageSize.value))

const hasActiveFilters = computed(() =>
  search.value !== '' ||
  statusFilter.value !== 'all' ||
  typeFilter.value !== '' ||
  dateRangeStart.value !== '' ||
  dateRangeEnd.value !== ''
)

// Bulk selection toggle (used in template via click handler)
function toggleSelectAll() {
  if (selectedEvents.value.size === events.value.length) {
    selectedEvents.value.clear()
  } else {
    events.value.forEach(e => selectedEvents.value.add(e.EventID))
  }
}

const filteredEvents = computed(() => {
  let result = events.value

  // Status filter
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  if (statusFilter.value === 'upcoming') {
    result = result.filter(e => e.EventDate && new Date(e.EventDate) >= today)
  } else if (statusFilter.value === 'past') {
    result = result.filter(e => e.EventDate && new Date(e.EventDate) < today)
  }

  return result
})

// ─── Data Loading ─────────────────────────────────────────────────────────────

async function loadEvents() {
  isLoading.value = true
  try {
    const filters: EventListFilters = {
      search: search.value || undefined,
      event_type: typeFilter.value || undefined,
      start_date: dateRangeStart.value || undefined,
      end_date: dateRangeEnd.value || undefined,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    }

    const res = await eventService.list(page.value, pageSize.value, filters)

    if (res?.status === 'success' && res.data) {
      events.value = res.data.data || []
      total.value = res.data.pagination?.total || 0
    } else {
      events.value = []
      total.value = 0
    }
    selectedEvents.value.clear()
  } catch (err) {
    console.error('Failed to load events:', err)
    toast.error('Failed to load events.')
    events.value = []
    total.value = 0
  } finally {
    isLoading.value = false
  }
}

// ─── Actions ──────────────────────────────────────────────────────────────────

function handleSearch() {
  page.value = 1
  loadEvents()
}

function clearFilters() {
  search.value = ''
  statusFilter.value = 'all'
  typeFilter.value = ''
  dateRangeStart.value = ''
  dateRangeEnd.value = ''
  page.value = 1
  loadEvents()
}

function goToPage(newPage: number) {
  if (newPage >= 1 && newPage <= totalPages.value) {
    page.value = newPage
    loadEvents()
  }
}

function toggleViewMode(mode: ViewMode) {
  viewMode.value = mode
}

function navigateToEvent(eventId: number) {
  router.push(`/events/${eventId}`)
}

function navigateToCreate() {
  router.push('/events/create')
}

function toggleSelection(eventId: number) {
  if (selectedEvents.value.has(eventId)) {
    selectedEvents.value.delete(eventId)
  } else {
    selectedEvents.value.add(eventId)
  }
}

async function deleteSelected() {
  const confirmed = await confirm({
    title: 'Delete Selected Events',
    message: `Are you sure you want to delete ${selectedEvents.value.size} event(s)? This action cannot be undone.`,
    confirmLabel: 'Delete',
    cancelLabel: 'Cancel',
  })

  if (!confirmed) return

  try {
    const ids = Array.from(selectedEvents.value)
    await Promise.all(ids.map(id => eventService.delete(id)))
    toast.success(`${ids.length} event(s) deleted.`)
    selectedEvents.value.clear()
    loadEvents()
  } catch {
    toast.error('Failed to delete some events.')
  }
}

function exportSelected() {
  const selected = events.value.filter(e => selectedEvents.value.has(e.EventID))
  const csvContent = [
    ['Event Name', 'Date', 'Type', 'Location'].join(','),
    ...selected.map(e => [
      `"${e.EventName}"`,
      e.EventDate,
      e.EventType || '',
      e.Location || ''
    ].join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `events-export-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  window.URL.revokeObjectURL(url)

  toast.success(`${selected.length} events exported.`)
}

// Calendar navigation
function prevMonth() {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1)
}

function nextMonth() {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1)
}

function goToToday() {
  currentMonth.value = new Date()
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDate(dateStr?: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric'
  })
}

function formatTime(timeStr?: string | null) {
  if (!timeStr) return ''
  return timeStr
}

function isToday(date: Date) {
  const today = new Date()
  return date.toDateString() === today.toDateString()
}

function getEventStatus(event: ChurchEvent): 'upcoming' | 'past' | 'today' {
  if (!event.EventDate) return 'upcoming'
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const eventDate = new Date(event.EventDate)
  eventDate.setHours(0, 0, 0, 0)

  if (eventDate.getTime() === today.getTime()) return 'today'
  if (eventDate > today) return 'upcoming'
  return 'past'
}

// ─── Watchers ─────────────────────────────────────────────────────────────────

let searchTimer: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    loadEvents()
  }, 400)
})

watch([statusFilter, typeFilter, dateRangeStart, dateRangeEnd], () => {
  page.value = 1
  loadEvents()
})

// ─── Init ─────────────────────────────────────────────────────────────────────

onMounted(() => {
  loadEvents()
})
</script>

<template>
  <div class="directory-view">
    <ChPageHeader title="Events Directory" subtitle="Manage church events, services, and gatherings.">
      <template #actions>
        <ChButton variant="primary" @click="navigateToCreate">
          <template #icon><Plus :size="18" /></template>
          Create Event
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ─── Toolbar ─────────────────────────────────────────────────────────── -->
    <div class="toolbar">
      <div class="toolbar__search">
        <ChInput
          v-model="search"
          placeholder="Search events by name..."
          size="md"
          clearable
          @clear="search = ''; handleSearch()"
        >
          <template #leading>
            <Search :size="16" />
          </template>
        </ChInput>
      </div>

      <ChButton
        variant="ghost"
        size="md"
        :class="['filter-toggle', { 'filter-toggle--active': showFilters }]"
        @click="showFilters = !showFilters"
      >
        <template #icon><Filter :size="16" /></template>
        Filters
        <span v-if="hasActiveFilters" class="filter-badge"></span>
      </ChButton>

      <ChSelect
        v-model="sortBy"
        :options="sortOptions"
        size="md"
        @change="loadEvents"
      />

      <ChButton
        variant="ghost"
        size="sm"
        :class="{ 'sort-dir-btn--asc': sortDir === 'ASC' }"
        @click="sortDir = sortDir === 'ASC' ? 'DESC' : 'ASC'; loadEvents()"
      >
        {{ sortDir === 'ASC' ? '↑' : '↓' }}
      </ChButton>

      <div class="toolbar__divider"></div>

      <div class="view-toggle">
        <button
          v-for="mode in ['grid', 'list', 'calendar'] as ViewMode[]"
          :key="mode"
          :class="['view-toggle__btn', { 'view-toggle__btn--active': viewMode === mode }]"
          @click="toggleViewMode(mode)"
          :title="`${mode.charAt(0).toUpperCase() + mode.slice(1)} View`"
        >
          <LayoutGrid v-if="mode === 'grid'" :size="16" />
          <List v-if="mode === 'list'" :size="16" />
          <CalendarDays v-if="mode === 'calendar'" :size="16" />
        </button>
      </div>
    </div>

    <!-- ─── Filter Bar ──────────────────────────────────────────────────────── -->
    <Transition name="slide-down">
      <div v-if="showFilters" class="filter-bar">
        <div class="filter-group">
          <label class="filter-label">Status</label>
          <div class="status-tabs">
            <button
              v-for="status in ['all', 'upcoming', 'past'] as EventStatus[]"
              :key="status"
              :class="['status-tab', { 'status-tab--active': statusFilter === status }]"
              @click="statusFilter = status"
            >
              {{ status.charAt(0).toUpperCase() + status.slice(1) }}
            </button>
          </div>
        </div>

        <div class="filter-group">
          <label class="filter-label">Event Type</label>
          <ChSelect
            v-model="typeFilter"
            :options="typeOptions"
            placeholder="All Types"
            size="md"
          />
        </div>

        <div class="filter-group">
          <label class="filter-label">Date Range</label>
          <div class="date-range">
            <ChInput v-model="dateRangeStart" type="date" size="sm" placeholder="From" />
            <span class="date-separator">to</span>
            <ChInput v-model="dateRangeEnd" type="date" size="sm" placeholder="To" />
          </div>
        </div>

        <ChButton variant="ghost" size="sm" @click="clearFilters">
          <template #icon><X :size="14" /></template>
          Clear All
        </ChButton>
      </div>
    </Transition>

    <!-- ─── Bulk Actions Bar ────────────────────────────────────────────────── -->
    <Transition name="slide-down">
      <div v-if="selectedEvents.size > 0" class="bulk-actions">
        <div class="bulk-actions__info">
          <Check :size="16" />
          <span>{{ selectedEvents.size }} selected</span>
        </div>
        <div class="bulk-actions__buttons">
          <ChButton variant="ghost" size="sm" @click="exportSelected">
            <template #icon><Download :size="14" /></template>
            Export
          </ChButton>
          <ChButton variant="danger" size="sm" @click="deleteSelected">
            <template #icon><Trash2 :size="14" /></template>
            Delete
          </ChButton>
        </div>
      </div>
    </Transition>

    <!-- ─── Content Area ────────────────────────────────────────────────────── -->
    <div class="content-wrapper">
      <!-- GRID VIEW -->
      <div v-if="viewMode === 'grid'" class="grid-view">
        <div v-if="isLoading" class="loading-grid">
          <ChSkeleton v-for="i in 12" :key="i" class="event-card-skeleton" />
        </div>
        <div v-else-if="filteredEvents.length === 0" class="empty-state">
          <ChEmptyState
            icon="calendar"
            title="No events found"
            :description="hasActiveFilters ? 'Try adjusting your filters.' : 'Get started by creating your first event.'"
          >
            <ChButton v-if="!hasActiveFilters" variant="primary" @click="navigateToCreate">
              Create Event
            </ChButton>
            <ChButton v-else variant="ghost" @click="clearFilters">
              Clear Filters
            </ChButton>
          </ChEmptyState>
        </div>
        <div v-else class="events-grid">
          <div
            v-for="event in filteredEvents"
            :key="event.EventID"
            :class="['event-card', `event-card--${getEventStatus(event)}`, { 'event-card--selected': selectedEvents.has(event.EventID) }]"
            @click="navigateToEvent(event.EventID)"
          >
            <div class="event-card__checkbox" @click.stop="toggleSelection(event.EventID)">
              <div :class="['checkbox', { 'checkbox--checked': selectedEvents.has(event.EventID) }]">
                <Check v-if="selectedEvents.has(event.EventID)" :size="12" />
              </div>
            </div>

            <div class="event-card__header">
              <div class="event-card__date">
                <span class="date-month">{{ event.EventDate ? new Date(event.EventDate).toLocaleDateString('en-US', { month: 'short' }) : '' }}</span>
                <span class="date-day">{{ event.EventDate ? new Date(event.EventDate).getDate() : '' }}</span>
              </div>
              <ChBadge
                :variant="getEventStatus(event) === 'today' ? 'warning' : getEventStatus(event) === 'upcoming' ? 'primary' : 'default'"
                size="sm"
              >
                {{ getEventStatus(event) === 'today' ? 'Today' : getEventStatus(event) === 'upcoming' ? 'Upcoming' : 'Past' }}
              </ChBadge>
            </div>

            <div class="event-card__content">
              <h3 class="event-card__title">{{ event.EventName }}</h3>
              <p v-if="event.EventType" class="event-card__type">{{ event.EventType }}</p>

              <div class="event-card__meta">
                <span v-if="event.EventTime" class="meta-item">
                  <Clock :size="12" />
                  {{ formatTime(event.EventTime) }}
                </span>
                <span v-if="event.Location" class="meta-item">
                  <MapPin :size="12" />
                  {{ event.Location }}
                </span>
                <span v-if="(event as any).totalAttendees" class="meta-item">
                  <Users :size="12" />
                  {{ (event as any).totalAttendees }} attending
                </span>
              </div>
            </div>

            <div class="event-card__actions" @click.stop>
              <ChDropdown position="bottom-end">
                <template #trigger>
                  <button class="action-btn">
                    <MoreVertical :size="16" />
                  </button>
                </template>
                <ChDropdownItem label="View Event" @click="navigateToEvent(event.EventID)" />
                <ChDropdownItem label="Edit" @click="router.push(`/events/${event.EventID}/edit`)" />
                <ChDropdownDivider />
                <ChDropdownItem label="Delete" variant="danger" @click="$event.stopPropagation(); selectedEvents.add(event.EventID); deleteSelected()" />
              </ChDropdown>
            </div>
          </div>
        </div>
      </div>

      <!-- LIST VIEW -->
      <div v-else-if="viewMode === 'list'" class="list-view">
        <ChTable
          :columns="[
            { key: 'select', label: '', type: 'slot', width: '40px', exportable: false },
            { key: 'EventName', label: 'Event', sortable: true, type: 'slot' },
            { key: 'EventDate', label: 'Date', sortable: true, type: 'slot' },
            { key: 'EventType', label: 'Type' },
            { key: 'Location', label: 'Location' },
            { key: 'Status', label: 'Status', type: 'slot' },
            { key: 'actions', label: '', type: 'slot', exportable: false, align: 'right' },
          ]"
          :rows="filteredEvents as Record<string, unknown>[]"
          :total="total"
          :page-size="pageSize"
          v-model:page="page"
          :loading="isLoading"
          row-key="EventID"
          :hoverable="true"
          :clickable="true"
          :exportable="true"
          title="Event Directory"
          @sort="loadEvents"
          @row-click="(row) => navigateToEvent((row as unknown as ChurchEvent).EventID)"
        >
          <template #cell-select="{ row }">
            <div
              :class="['checkbox', { 'checkbox--checked': selectedEvents.has((row as unknown as ChurchEvent).EventID) }]"
              @click.stop="toggleSelection((row as unknown as ChurchEvent).EventID)"
            >
              <Check v-if="selectedEvents.has((row as unknown as ChurchEvent).EventID)" :size="12" />
            </div>
          </template>

          <template #cell-EventName="{ row }">
            <div class="event-name-cell">
              <div class="event-avatar">
                <Calendar :size="16" />
              </div>
              <div class="event-info">
                <span class="event-name__primary">{{ (row as unknown as ChurchEvent).EventName }}</span>
                <span v-if="(row as unknown as ChurchEvent).EventDescription" class="event-name__secondary">
                  {{ ((row as unknown as ChurchEvent).EventDescription || '').slice(0, 50) }}...
                </span>
              </div>
            </div>
          </template>

          <template #cell-EventDate="{ row }">
            <div class="date-cell">
              <span class="date-primary">{{ formatDate((row as unknown as ChurchEvent).EventDate) }}</span>
              <span v-if="(row as unknown as ChurchEvent).EventTime" class="date-secondary">
                {{ formatTime((row as unknown as ChurchEvent).EventTime) }}
              </span>
            </div>
          </template>

          <template #cell-Status="{ row }">
            <ChBadge
              :variant="getEventStatus(row as unknown as ChurchEvent) === 'today' ? 'warning' : getEventStatus(row as unknown as ChurchEvent) === 'upcoming' ? 'primary' : 'default'"
              size="sm"
            >
              {{ getEventStatus(row as unknown as ChurchEvent) === 'today' ? 'Today' : getEventStatus(row as unknown as ChurchEvent) === 'upcoming' ? 'Upcoming' : 'Past' }}
            </ChBadge>
          </template>

          <template #cell-actions="{ row }">
            <div class="row-actions">
              <ChButton size="sm" variant="ghost" @click.stop="navigateToEvent((row as unknown as ChurchEvent).EventID)">
                View
              </ChButton>
              <ChButton size="sm" variant="ghost" @click.stop="router.push(`/events/${(row as unknown as ChurchEvent).EventID}/edit`)">
                Edit
              </ChButton>
            </div>
          </template>
        </ChTable>
      </div>

      <!-- CALENDAR VIEW -->
      <div v-else class="calendar-view">
        <div class="calendar-header">
          <div class="calendar-nav">
            <ChButton variant="ghost" size="sm" @click="prevMonth">
              <template #icon><ChevronLeft :size="16" /></template>
            </ChButton>
            <h2 class="calendar-title">{{ calendarMonthYear }}</h2>
            <ChButton variant="ghost" size="sm" @click="nextMonth">
              <template #icon><ChevronRight :size="16" /></template>
            </ChButton>
          </div>
          <ChButton variant="outline" size="sm" @click="goToToday">Today</ChButton>
        </div>

        <div class="calendar-grid">
          <div class="calendar-weekdays">
            <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="weekday">
              {{ day }}
            </div>
          </div>
          <div class="calendar-days">
            <div
              v-for="day in calendarEvents"
              :key="day.date.toISOString()"
              :class="['calendar-day', { 'calendar-day--today': isToday(day.date), 'calendar-day--other-month': !day.isCurrentMonth }]"
            >
              <div class="day-number">{{ day.date.getDate() }}</div>
              <div class="day-events">
                <div
                  v-for="event in day.events.slice(0, 3)"
                  :key="event.EventID"
                  :class="['calendar-event', `calendar-event--${getEventStatus(event)}`]"
                  @click="navigateToEvent(event.EventID)"
                >
                  <span class="event-dot"></span>
                  <span class="event-title">{{ event.EventName }}</span>
                </div>
                <div v-if="day.events.length > 3" class="more-events">
                  +{{ day.events.length - 3 }} more
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── Pagination ──────────────────────────────────────────────────────── -->
    <div v-if="viewMode !== 'calendar' && totalPages > 1" class="pagination">
      <div class="pagination__info">
        Showing {{ (page - 1) * pageSize + 1 }} - {{ Math.min(page * pageSize, total) }} of {{ total }} events
      </div>
      <div class="pagination__controls">
        <ChButton
          variant="ghost"
          size="sm"
          :disabled="page === 1"
          @click="goToPage(page - 1)"
        >
          <template #icon><ChevronLeft :size="16" /></template>
          Previous
        </ChButton>
        <div class="page-numbers">
          <button
            v-for="p in totalPages <= 7 ? totalPages : 7"
            :key="p"
            :class="['page-number', { 'page-number--active': p === page }]"
            @click="goToPage(p)"
          >
            {{ p }}
          </button>
        </div>
        <ChButton
          variant="ghost"
          size="sm"
          :disabled="page === totalPages"
          @click="goToPage(page + 1)"
        >
          Next
          <template #icon><ChevronRight :size="16" /></template>
        </ChButton>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ─── Layout ───────────────────────────────────────────────────────────────── */
.directory-view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
  padding-bottom: var(--ch-space-8);
}

/* ─── Toolbar ───────────────────────────────────────────────────────────────── */
.toolbar {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  flex-wrap: wrap;
  padding: var(--ch-space-3) 0;
}

.toolbar__search {
  flex: 1;
  min-width: 240px;
  max-width: 400px;
}

.toolbar__divider {
  width: 1px;
  height: 24px;
  background: var(--ch-color-border-strong);
}

.filter-toggle {
  position: relative;
}

.filter-toggle--active {
  background: var(--ch-color-bg-subtle);
  color: var(--ch-color-primary);
}

.filter-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 8px;
  height: 8px;
  background: var(--ch-color-primary);
  border-radius: 50%;
}

.sort-dir-btn--asc {
  color: var(--ch-color-primary);
}

/* ─── View Toggle ──────────────────────────────────────────────────────────── */
.view-toggle {
  display: flex;
  align-items: center;
  gap: 2px;
  background: var(--ch-color-bg-subtle);
  padding: 2px;
  border-radius: var(--ch-radius-lg);
  border: 1px solid var(--ch-color-border-strong);
}

.view-toggle__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: var(--ch-color-text-subtle);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  transition: all 0.15s ease;
}

.view-toggle__btn:hover {
  color: var(--ch-color-text);
  background: var(--ch-color-surface);
}

.view-toggle__btn--active {
  background: var(--ch-color-surface);
  color: var(--ch-color-primary);
  box-shadow: var(--ch-shadow-sm);
}

/* ─── Filter Bar ───────────────────────────────────────────────────────────── */
.filter-bar {
  display: flex;
  align-items: center;
  gap: var(--ch-space-4);
  padding: var(--ch-space-4);
  background: var(--ch-color-bg-subtle);
  border-radius: var(--ch-radius-xl);
  border: 1px solid var(--ch-color-border-strong);
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1);
}

.filter-label {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  font-weight: var(--ch-font-medium);
}

.status-tabs {
  display: flex;
  gap: 2px;
  background: var(--ch-color-surface);
  padding: 2px;
  border-radius: var(--ch-radius-lg);
  border: 1px solid var(--ch-color-border-strong);
}

.status-tab {
  padding: var(--ch-space-2) var(--ch-space-3);
  border: none;
  background: transparent;
  color: var(--ch-color-text-subtle);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  font-size: var(--ch-text-sm);
  transition: all 0.15s ease;
}

.status-tab:hover {
  color: var(--ch-color-text);
}

.status-tab--active {
  background: var(--ch-color-primary);
  color: white;
}

.date-range {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.date-separator {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}

/* ─── Bulk Actions ─────────────────────────────────────────────────────────── */
.bulk-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--ch-space-3) var(--ch-space-4);
  background: var(--ch-color-primary-subtle);
  border: 1px solid var(--ch-color-primary-muted);
  border-radius: var(--ch-radius-lg);
  animation: slideDown 0.2s ease;
}

.bulk-actions__info {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  color: var(--ch-color-primary);
  font-weight: var(--ch-font-medium);
  font-size: var(--ch-text-sm);
}

.bulk-actions__buttons {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

/* ─── Content Wrapper ──────────────────────────────────────────────────────── */
.content-wrapper {
  min-height: 400px;
}

/* ─── Grid View ────────────────────────────────────────────────────────────── */
.grid-view {
  width: 100%;
}

.loading-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--ch-space-4);
}

.event-card-skeleton {
  height: 180px;
  border-radius: var(--ch-radius-xl);
}

.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--ch-space-4);
}

.event-card {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3);
  padding: var(--ch-space-4);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-xl);
  cursor: pointer;
  transition: all 0.15s ease;
  position: relative;
}

.event-card:hover {
  border-color: var(--ch-color-primary-muted);
  box-shadow: var(--ch-shadow-md);
  transform: translateY(-1px);
}

.event-card--selected {
  border-color: var(--ch-color-primary);
  background: var(--ch-color-primary-subtle);
}

.event-card--today {
  border-color: var(--ch-color-warning);
}

.event-card__checkbox {
  position: absolute;
  top: var(--ch-space-3);
  left: var(--ch-space-3);
  z-index: 1;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.event-card:hover .event-card__checkbox,
.event-card--selected .event-card__checkbox {
  opacity: 1;
}

.checkbox {
  width: 18px;
  height: 18px;
  border: 2px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ch-color-surface);
  cursor: pointer;
  transition: all 0.15s ease;
}

.checkbox--checked {
  background: var(--ch-color-primary);
  border-color: var(--ch-color-primary);
  color: white;
}

.event-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-left: var(--ch-space-6);
}

.event-card__date {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: var(--ch-color-bg-subtle);
  padding: var(--ch-space-2) var(--ch-space-3);
  border-radius: var(--ch-radius-lg);
  min-width: 50px;
}

.date-month {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
  font-weight: var(--ch-font-medium);
}

.date-day {
  font-size: var(--ch-text-xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
}

.event-card__content {
  flex: 1;
  min-width: 0;
}

.event-card__title {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0 0 var(--ch-space-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.event-card__type {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
  margin: 0 0 var(--ch-space-2);
}

.event-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-2);
}

.meta-item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
}

.meta-item svg {
  flex-shrink: 0;
}

.event-card__actions {
  position: absolute;
  top: var(--ch-space-3);
  right: var(--ch-space-3);
  opacity: 0;
  transition: opacity 0.15s ease;
}

.event-card:hover .event-card__actions {
  opacity: 1;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: none;
  background: var(--ch-color-surface);
  color: var(--ch-color-text-subtle);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  box-shadow: var(--ch-shadow-sm);
}

.action-btn:hover {
  background: var(--ch-color-bg-subtle);
  color: var(--ch-color-text);
}

/* ─── List View ─────────────────────────────────────────────────────────────── */
.list-view {
  width: 100%;
}

.event-name-cell {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.event-avatar {
  width: 32px;
  height: 32px;
  border-radius: var(--ch-radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--ch-color-primary-muted), var(--ch-color-primary));
  color: white;
}

.event-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.event-name__primary {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.event-name__secondary {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.date-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.date-primary {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
}

.date-secondary {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.row-actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
}

/* ─── Calendar View ─────────────────────────────────────────────────────────── */
.calendar-view {
  width: 100%;
}

.calendar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--ch-space-4);
  padding: var(--ch-space-2) 0;
}

.calendar-nav {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.calendar-title {
  font-size: var(--ch-text-xl);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0;
  min-width: 200px;
  text-align: center;
}

.calendar-grid {
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-xl);
  overflow: hidden;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: var(--ch-color-bg-subtle);
  border-bottom: 1px solid var(--ch-color-border-strong);
}

.weekday {
  padding: var(--ch-space-3);
  text-align: center;
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  grid-template-rows: repeat(6, 1fr);
  min-height: 500px;
}

.calendar-day {
  border-right: 1px solid var(--ch-color-border-subtle);
  border-bottom: 1px solid var(--ch-color-border-subtle);
  padding: var(--ch-space-2);
  min-height: 80px;
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1);
}

.calendar-day:nth-child(7n) {
  border-right: none;
}

.calendar-day:nth-last-child(-n+7) {
  border-bottom: none;
}

.calendar-day--today {
  background: var(--ch-color-primary-subtle);
}

.calendar-day--other-month {
  background: var(--ch-color-bg-subtle);
  color: var(--ch-color-text-muted);
}

.day-number {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--ch-radius-md);
}

.calendar-day--today .day-number {
  background: var(--ch-color-primary);
  color: white;
}

.day-events {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  overflow: hidden;
}

.calendar-event {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
  padding: var(--ch-space-1) var(--ch-space-2);
  background: var(--ch-color-primary-subtle);
  border-radius: var(--ch-radius-sm);
  cursor: pointer;
  font-size: var(--ch-text-xs);
  transition: all 0.15s ease;
  overflow: hidden;
}

.calendar-event:hover {
  background: var(--ch-color-primary-muted);
}

.calendar-event--today {
  background: var(--ch-color-warning-subtle);
}

.calendar-event--past {
  background: var(--ch-color-bg-subtle);
  opacity: 0.7;
}

.event-dot {
  width: 6px;
  height: 6px;
  background: var(--ch-color-primary);
  border-radius: 50%;
  flex-shrink: 0;
}

.calendar-event--today .event-dot {
  background: var(--ch-color-warning);
}

.calendar-event--past .event-dot {
  background: var(--ch-color-text-muted);
}

.event-title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.more-events {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  padding: var(--ch-space-1) var(--ch-space-2);
}

/* ─── Empty State ──────────────────────────────────────────────────────────── */
.empty-state {
  padding: var(--ch-space-12) 0;
}

/* ─── Pagination ─────────────────────────────────────────────────────────── */
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--ch-space-4) 0;
  border-top: 1px solid var(--ch-color-border-strong);
  margin-top: var(--ch-space-4);
  flex-wrap: wrap;
  gap: var(--ch-space-3);
}

.pagination__info {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}

.pagination__controls {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.page-numbers {
  display: flex;
  align-items: center;
  gap: 2px;
}

.page-number {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: var(--ch-color-text);
  font-size: var(--ch-text-sm);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  transition: all 0.15s ease;
}

.page-number:hover {
  background: var(--ch-color-bg-subtle);
}

.page-number--active {
  background: var(--ch-color-primary);
  color: white;
}

/* ─── Transitions ──────────────────────────────────────────────────────────── */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.2s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ─── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .events-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .calendar-title {
    font-size: var(--ch-text-lg);
    min-width: 150px;
  }
}

@media (max-width: 640px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar__search {
    max-width: none;
  }

  .events-grid {
    grid-template-columns: 1fr;
  }

  .pagination {
    flex-direction: column;
    align-items: center;
  }

  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .event-card__actions {
    opacity: 1;
  }

  .calendar-days {
    min-height: 400px;
  }

  .calendar-day {
    min-height: 60px;
    padding: var(--ch-space-1);
  }

  .calendar-event {
    font-size: 10px;
  }
}
</style>
