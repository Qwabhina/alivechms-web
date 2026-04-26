<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { groupService } from '@/services/group.service'
import { useToast, confirm, ChPageHeader } from '@/design-system'
import type { Group } from '@/types'
import {
  Search,
  Plus,
  LayoutGrid,
  List,
  PanelRight,
  Filter,
  X,
  ChevronLeft,
  ChevronRight,
  User,
  Calendar,
  MapPin,
  MoreVertical,
  Check,
  Crown,
  Users2,
} from 'lucide-vue-next'

// ─── Types ────────────────────────────────────────────────────────────────────

type ViewMode = 'list' | 'grid' | 'detail'

// ─── Setup ────────────────────────────────────────────────────────────────────

const router = useRouter()
const toast = useToast()

// ─── State ────────────────────────────────────────────────────────────────────

const groups = ref<Group[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(24)
const isLoading = ref(false)
const search = ref('')
const typeFilter = ref('')
const sortBy = ref('GroupName')
const sortDir = ref<'ASC' | 'DESC'>('ASC')
const viewMode = ref<ViewMode>('grid')
const selectedGroups = ref<Set<number>>(new Set())
const detailGroup = ref<Group | null>(null)
const showDetailPanel = ref(false)
const showFilters = ref(false)

// ─── Computed ─────────────────────────────────────────────────────────────────

const sortOptions = [
  { value: 'GroupName', label: 'Group Name' },
  { value: 'TotalMembers', label: 'Member Count' },
  { value: 'TypeName', label: 'Group Type' },
]

const totalPages = computed(() => Math.ceil(total.value / pageSize.value))

const hasActiveFilters = computed(() =>
  search.value !== '' || typeFilter.value !== ''
)

const allSelected = computed(() =>
  groups.value.length > 0 && selectedGroups.value.size === groups.value.length
)

// ─── Data Loading ─────────────────────────────────────────────────────────────

async function loadGroups() {
  isLoading.value = true
  try {
    const filters = {
      search: search.value || undefined,
      type: typeFilter.value || undefined,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    }
    const res = await groupService.list(page.value, pageSize.value, filters)

    // Handle response structure
    const paginatedData = res?.data
    if (!paginatedData) {
      groups.value = []
      total.value = 0
      return
    }

    const groupArray = Array.isArray(paginatedData.data)
      ? paginatedData.data
      : Array.isArray(paginatedData)
        ? paginatedData
        : []

    groups.value = groupArray
    total.value = paginatedData.pagination?.total ?? groupArray.length ?? 0
    selectedGroups.value.clear()
  } catch (err) {
    console.error('Failed to load groups:', err)
    toast.error('Failed to load groups.')
    groups.value = []
    total.value = 0
  } finally {
    isLoading.value = false
  }
}

// ─── Actions ──────────────────────────────────────────────────────────────────

function handleSearch() {
  page.value = 1
  loadGroups()
}

function handleSort() {
  loadGroups()
}

function clearFilters() {
  search.value = ''
  typeFilter.value = ''
  page.value = 1
  loadGroups()
}

function goToPage(newPage: number) {
  if (newPage >= 1 && newPage <= totalPages.value) {
    page.value = newPage
    loadGroups()
  }
}

function toggleViewMode(mode: ViewMode) {
  viewMode.value = mode
  if (mode !== 'detail') {
    showDetailPanel.value = false
    detailGroup.value = null
  }
}

function openGroupDetail(group: Group) {
  if (viewMode.value === 'detail') {
    detailGroup.value = group
    showDetailPanel.value = true
  } else {
    router.push(`/groups/${group.GroupID}`)
  }
}

function closeDetailPanel() {
  showDetailPanel.value = false
  detailGroup.value = null
}

function toggleSelection(groupId: number) {
  if (selectedGroups.value.has(groupId)) {
    selectedGroups.value.delete(groupId)
  } else {
    selectedGroups.value.add(groupId)
  }
}

async function deleteSelected() {
  const confirmed = await confirm({
    title: 'Delete Selected Groups',
    message: `Are you sure you want to delete ${selectedGroups.value.size} group(s)? This action cannot be undone.`,
    confirmLabel: 'Delete',
    cancelLabel: 'Cancel',
  })

  if (!confirmed) return

  try {
    const ids = Array.from(selectedGroups.value)
    await Promise.all(ids.map(id => groupService.delete(id)))
    toast.success(`${ids.length} group(s) deleted.`)
    selectedGroups.value.clear()
    loadGroups()
  } catch {
    toast.error('Failed to delete some groups.')
  }
}

async function exportSelected() {
  toast.info(`Exporting ${selectedGroups.value.size} group(s)...`)
}

// ─── Watchers ─────────────────────────────────────────────────────────────────

let searchTimer: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    loadGroups()
  }, 400)
})

watch([typeFilter, page], () => loadGroups())

// ─── Init ─────────────────────────────────────────────────────────────────────

onMounted(() => {
  loadGroups()
})
</script>

<template>
  <div class="directory-view">
    <ChPageHeader title="Groups Directory" subtitle="Manage church groups and ministries.">
      <template #actions>
        <ChButton variant="primary" @click="router.push('/groups/create')">
          <template #icon><Plus :size="18" /></template>
          Create Group
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ─── Toolbar ─────────────────────────────────────────────────────────── -->
    <div class="toolbar">
      <div class="toolbar__search">
        <ChInput
          v-model="search"
          placeholder="Search groups by name..."
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
        @change="handleSort"
      />

      <ChButton
        variant="ghost"
        size="sm"
        :class="{ 'sort-dir-btn--asc': sortDir === 'ASC' }"
        @click="sortDir = sortDir === 'ASC' ? 'DESC' : 'ASC'; handleSort()"
      >
        {{ sortDir === 'ASC' ? '↑' : '↓' }}
      </ChButton>

      <div class="toolbar__divider"></div>

      <div class="view-toggle">
        <button
          v-for="mode in ['grid', 'list', 'detail'] as ViewMode[]"
          :key="mode"
          :class="['view-toggle__btn', { 'view-toggle__btn--active': viewMode === mode }]"
          @click="toggleViewMode(mode)"
          :title="`${mode.charAt(0).toUpperCase() + mode.slice(1)} View`"
        >
          <LayoutGrid v-if="mode === 'grid'" :size="16" />
          <List v-if="mode === 'list'" :size="16" />
          <PanelRight v-if="mode === 'detail'" :size="16" />
        </button>
      </div>
    </div>

    <!-- ─── Filter Bar ──────────────────────────────────────────────────────── -->
    <Transition name="slide-down">
      <div v-if="showFilters" class="filter-bar">
        <ChSelect
          v-model="typeFilter"
          :options="[
            { value: '', label: 'All Types' },
            { value: 'ministry', label: 'Ministry' },
            { value: 'fellowship', label: 'Fellowship' },
            { value: 'department', label: 'Department' },
          ]"
          placeholder="Filter by type"
          size="md"
        />
        <ChButton variant="ghost" size="sm" @click="clearFilters">
          <template #icon><X :size="14" /></template>
          Clear All
        </ChButton>
      </div>
    </Transition>

    <!-- ─── Select All Bar ──────────────────────────────────────────────────── -->
    <div class="select-all-bar">
      <div :class="['checkbox', { 'checkbox--checked': allSelected }]" @click="groups.forEach(g => allSelected ? selectedGroups.delete(g.GroupID) : selectedGroups.add(g.GroupID))">
        <Check v-if="allSelected" :size="12" />
      </div>
      <span class="select-all-label" @click="groups.forEach(g => allSelected ? selectedGroups.delete(g.GroupID) : selectedGroups.add(g.GroupID))">
        {{ allSelected ? 'Deselect All' : 'Select All' }}
      </span>
    </div>

    <!-- ─── Bulk Actions Bar ────────────────────────────────────────────────── -->
    <Transition name="slide-down">
      <div v-if="selectedGroups.size > 0" class="bulk-actions">
        <div class="bulk-actions__info">
          <Check :size="16" />
          <span>{{ selectedGroups.size }} selected</span>
        </div>
        <div class="bulk-actions__buttons">
          <ChButton variant="ghost" size="sm" @click="exportSelected">
            Export
          </ChButton>
          <ChButton variant="danger" size="sm" @click="deleteSelected">
            <template #icon><X :size="14" /></template>
            Delete
          </ChButton>
        </div>
      </div>
    </Transition>

    <!-- ─── Content Area ────────────────────────────────────────────────────── -->
    <div class="content-wrapper" :class="{ 'content-wrapper--with-panel': showDetailPanel }">
      <!-- GRID VIEW -->
      <div v-if="viewMode === 'grid'" class="grid-view">
        <div v-if="isLoading" class="loading-grid">
          <ChSkeleton v-for="i in 12" :key="i" class="group-card-skeleton" />
        </div>
        <div v-else-if="groups.length === 0" class="empty-state">
          <ChEmptyState
            icon="users"
            title="No groups found"
            :description="hasActiveFilters ? 'Try adjusting your filters.' : 'Get started by creating your first group.'"
          >
            <ChButton v-if="!hasActiveFilters" variant="primary" @click="router.push('/groups/create')">
              Create Group
            </ChButton>
            <ChButton v-else variant="ghost" @click="clearFilters">
              Clear Filters
            </ChButton>
          </ChEmptyState>
        </div>
        <div v-else class="groups-grid">
          <div
            v-for="group in groups"
            :key="group.GroupID"
            :class="['group-card', { 'group-card--selected': selectedGroups.has(group.GroupID) }]"
            @click="openGroupDetail(group)"
          >
            <div class="group-card__checkbox" @click.stop="toggleSelection(group.GroupID)">
              <div :class="['checkbox', { 'checkbox--checked': selectedGroups.has(group.GroupID) }]">
                <Check v-if="selectedGroups.has(group.GroupID)" :size="12" />
              </div>
            </div>
            <div class="group-card__header">
              <div class="group-card__avatar">
                <Users2 :size="24" />
              </div>
              <div class="group-card__member-count">
                <User :size="12" />
                <span>{{ group.TotalMembers || 0 }}</span>
              </div>
            </div>
            <div class="group-card__info">
              <h3 class="group-card__name">{{ group.GroupName }}</h3>
              <p v-if="group.TypeName" class="group-card__type">{{ group.TypeName }}</p>
              <div class="group-card__meta">
                <span v-if="group.LeaderName" class="meta-item">
                  <Crown :size="12" />
                  {{ group.LeaderName }}
                </span>
                <span v-if="group.MeetingDay" class="meta-item">
                  <Calendar :size="12" />
                  {{ group.MeetingDay }}
                </span>
                <span v-if="group.MeetingLocation" class="meta-item">
                  <MapPin :size="12" />
                  {{ group.MeetingLocation }}
                </span>
              </div>
            </div>
            <div class="group-card__actions" @click.stop>
              <ChDropdown position="bottom-end">
                <template #trigger>
                  <button class="action-btn">
                    <MoreVertical :size="16" />
                  </button>
                </template>
                <ChDropdownItem label="View Group" @click="router.push(`/groups/${group.GroupID}`)" />
                <ChDropdownItem label="Edit" @click="router.push(`/groups/${group.GroupID}/edit`)" />
                <ChDropdownDivider />
                <ChDropdownItem label="Delete" variant="danger" @click="toggleSelection(group.GroupID)" />
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
            { key: 'GroupName', label: 'Group Name', sortable: true, type: 'slot' },
            { key: 'TotalMembers', label: 'Members', width: '100px', type: 'slot' },
            { key: 'TypeName', label: 'Type' },
            { key: 'LeaderName', label: 'Leader' },
            { key: 'MeetingDay', label: 'Meeting Day' },
            { key: 'actions', label: '', type: 'slot', exportable: false, align: 'right' },
          ]"
          :rows="groups as Record<string, unknown>[]"
          :total="total"
          :page-size="pageSize"
          v-model:page="page"
          :loading="isLoading"
          row-key="GroupID"
          :hoverable="true"
          :clickable="true"
          :exportable="true"
          title="Group Directory"
          @sort="handleSort"
          @row-click="(row) => openGroupDetail(row as unknown as Group)"
        >
          <template #cell-select="{ row }">
            <div :class="['checkbox', { 'checkbox--checked': selectedGroups.has((row as any).GroupID) }]" @click.stop="toggleSelection((row as any).GroupID)">
              <Check v-if="selectedGroups.has((row as any).GroupID)" :size="12" />
            </div>
          </template>
          <template #cell-GroupName="{ row }">
            <div class="group-name-cell">
              <div class="group-avatar-small">
                <Users2 :size="16" />
              </div>
              <div class="group-info">
                <span class="group-name__primary">{{ (row as any).GroupName }}</span>
                <span v-if="(row as any).MeetingLocation" class="group-name__secondary">
                  {{ (row as any).MeetingLocation }}
                </span>
              </div>
            </div>
          </template>
          <template #cell-TotalMembers="{ row }">
            <ChBadge :variant="(row as any).TotalMembers > 0 ? 'primary' : 'default'" size="sm">
              {{ (row as any).TotalMembers || 0 }} members
            </ChBadge>
          </template>
          <template #cell-actions="{ row }">
            <div class="row-actions">
              <ChButton size="sm" variant="ghost" @click.stop="router.push(`/groups/${(row as any).GroupID}`)">
                View
              </ChButton>
              <ChButton size="sm" variant="ghost" @click.stop="router.push(`/groups/${(row as any).GroupID}/edit`)">
                Edit
              </ChButton>
            </div>
          </template>
        </ChTable>
      </div>

      <!-- DETAIL VIEW -->
      <div v-else class="detail-view">
        <div class="detail-view__list">
          <ChTable
            :columns="[
              { key: 'select', label: '', type: 'slot', width: '40px', exportable: false },
              { key: 'GroupName', label: 'Group', sortable: true, type: 'slot' },
              { key: 'TotalMembers', label: 'Members', type: 'slot' },
              { key: 'LeaderName', label: 'Leader' },
            ]"
            :rows="groups as Record<string, unknown>[]"
            :total="total"
            :page-size="pageSize"
            v-model:page="page"
            :loading="isLoading"
            row-key="GroupID"
            :hoverable="true"
            :clickable="true"
            title="Select a group to preview"
            @sort="handleSort"
            @row-click="(row) => openGroupDetail(row as unknown as Group)"
          >
            <template #cell-select="{ row }">
              <div :class="['checkbox', { 'checkbox--checked': selectedGroups.has((row as any).GroupID) }]" @click.stop="toggleSelection((row as any).GroupID)">
                <Check v-if="selectedGroups.has((row as any).GroupID)" :size="12" />
              </div>
            </template>
            <template #cell-GroupName="{ row }">
              <div class="group-name-cell">
                <div class="group-avatar-small">
                  <Users2 :size="16" />
                </div>
                <div class="group-info">
                  <span class="group-name__primary">{{ (row as any).GroupName }}</span>
                  <span v-if="(row as any).TypeName" class="group-name__secondary">
                    {{ (row as any).TypeName }}
                  </span>
                </div>
              </div>
            </template>
            <template #cell-TotalMembers="{ row }">
              <ChBadge :variant="(row as any).TotalMembers > 0 ? 'primary' : 'default'" size="sm">
                {{ (row as any).TotalMembers || 0 }}
              </ChBadge>
            </template>
          </ChTable>
        </div>
      </div>

      <!-- ─── Detail Side Panel ─────────────────────────────────────────────── -->
      <Transition name="slide-left">
        <div v-if="showDetailPanel && detailGroup" class="detail-panel">
          <div class="detail-panel__header">
            <ChButton variant="ghost" size="sm" @click="closeDetailPanel">
              <template #icon><X :size="16" /></template>
            </ChButton>
          </div>
          <div class="detail-panel__content">
            <div class="detail-profile">
              <div class="detail-profile__avatar">
                <Users2 :size="32" />
              </div>
              <h2 class="detail-profile__name">{{ detailGroup.GroupName }}</h2>
              <p v-if="detailGroup.TypeName" class="detail-profile__type">{{ detailGroup.TypeName }}</p>
              <ChBadge :variant="(detailGroup.TotalMembers || 0) > 0 ? 'primary' : 'default'">
                {{ detailGroup.TotalMembers || 0 }} members
              </ChBadge>
            </div>

            <ChDivider />

            <div class="detail-section">
              <h4>Leadership</h4>
              <ChDataList
                :items="[
                  { label: 'Leader', value: detailGroup.LeaderName || '—' },
                  { label: 'Co-Leader', value: detailGroup.CoLeaderName || '—' },
                ]"
              />
            </div>

            <ChDivider />

            <div class="detail-section">
              <h4>Meeting Information</h4>
              <ChDataList
                :items="[
                  { label: 'Day', value: detailGroup.MeetingDay || '—' },
                  { label: 'Time', value: detailGroup.MeetingTime || '—' },
                  { label: 'Location', value: detailGroup.MeetingLocation || '—' },
                ]"
              />
            </div>

            <div class="detail-actions">
              <ChButton variant="primary" block @click="router.push(`/groups/${detailGroup.GroupID}`)">
                View Full Profile
              </ChButton>
              <ChButton variant="outline" block @click="router.push(`/groups/${detailGroup.GroupID}/edit`)">
                Edit Group
              </ChButton>
            </div>
          </div>
        </div>
      </Transition>
    </div>

    <!-- ─── Pagination ──────────────────────────────────────────────────────── -->
    <div v-if="totalPages > 1" class="pagination">
      <div class="pagination__info">
        Showing {{ (page - 1) * pageSize + 1 }} - {{ Math.min(page * pageSize, total) }} of {{ total }} groups
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
  gap: var(--ch-space-3);
  padding: var(--ch-space-3);
  background: var(--ch-color-bg-subtle);
  border-radius: var(--ch-radius-xl);
  border: 1px solid var(--ch-color-border-strong);
  flex-wrap: wrap;
}

/* ─── Select All Bar ───────────────────────────────────────────────────────── */
.select-all-bar {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  padding: var(--ch-space-2) 0;
}

.select-all-label {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-subtle);
  cursor: pointer;
  user-select: none;
}

.select-all-label:hover {
  color: var(--ch-color-text);
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
  display: flex;
  gap: var(--ch-space-4);
  min-height: 400px;
}

.content-wrapper--with-panel {
  display: grid;
  grid-template-columns: 1fr 380px;
}

/* ─── Grid View ────────────────────────────────────────────────────────────── */
.grid-view {
  flex: 1;
}

.loading-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--ch-space-4);
}

.group-card-skeleton {
  height: 160px;
  border-radius: var(--ch-radius-xl);
}

.groups-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--ch-space-4);
}

.group-card {
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

.group-card:hover {
  border-color: var(--ch-color-primary-muted);
  box-shadow: var(--ch-shadow-md);
  transform: translateY(-1px);
}

.group-card--selected {
  border-color: var(--ch-color-primary);
  background: var(--ch-color-primary-subtle);
}

.group-card__checkbox {
  position: absolute;
  top: var(--ch-space-3);
  left: var(--ch-space-3);
  z-index: 1;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.group-card:hover .group-card__checkbox,
.group-card--selected .group-card__checkbox {
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

.group-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-left: var(--ch-space-6);
}

.group-card__avatar {
  width: 48px;
  height: 48px;
  border-radius: var(--ch-radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--ch-color-primary-muted), var(--ch-color-primary));
  color: white;
}

.group-card__member-count {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
  background: var(--ch-color-bg-subtle);
  padding: var(--ch-space-1) var(--ch-space-2);
  border-radius: var(--ch-radius-md);
}

.group-card__info {
  flex: 1;
  min-width: 0;
}

.group-card__name {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0 0 var(--ch-space-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.group-card__type {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
  margin: 0 0 var(--ch-space-2);
}

.group-card__meta {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1);
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

.group-card__actions {
  position: absolute;
  top: var(--ch-space-3);
  right: var(--ch-space-3);
  opacity: 0;
  transition: opacity 0.15s ease;
}

.group-card:hover .group-card__actions {
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
  flex: 1;
}

.group-name-cell {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.group-avatar-small {
  width: 32px;
  height: 32px;
  border-radius: var(--ch-radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--ch-color-primary-muted), var(--ch-color-primary));
  color: white;
}

.group-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.group-name__primary {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.group-name__secondary {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.row-actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
}

/* ─── Detail View & Panel ──────────────────────────────────────────────────── */
.detail-view {
  flex: 1;
}

.detail-view__list {
  height: 100%;
}

.detail-panel {
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-xl);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 200px);
  position: sticky;
  top: var(--ch-space-4);
}

.detail-panel__header {
  display: flex;
  justify-content: flex-end;
  padding: var(--ch-space-2);
  border-bottom: 1px solid var(--ch-color-border-strong);
}

.detail-panel__content {
  flex: 1;
  overflow-y: auto;
  padding: var(--ch-space-4);
}

.detail-profile {
  text-align: center;
  padding: var(--ch-space-4) 0;
}

.detail-profile__avatar {
  width: 80px;
  height: 80px;
  border-radius: var(--ch-radius-xl);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto var(--ch-space-4);
  background: linear-gradient(135deg, var(--ch-color-primary-muted), var(--ch-color-primary));
  color: white;
}

.detail-profile__name {
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
  margin: 0 0 var(--ch-space-1);
}

.detail-profile__type {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-subtle);
  margin: 0 0 var(--ch-space-3);
}

.detail-section {
  padding: var(--ch-space-3) 0;
}

.detail-section h4 {
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-semibold);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--ch-color-text-muted);
  margin: 0 0 var(--ch-space-3);
}

.detail-actions {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
  padding-top: var(--ch-space-4);
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

.slide-left-enter-active,
.slide-left-leave-active {
  transition: all 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
  opacity: 0;
  transform: translateX(20px);
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
  .content-wrapper--with-panel {
    grid-template-columns: 1fr;
  }

  .detail-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    max-width: 380px;
    z-index: 100;
    border-radius: 0;
    max-height: none;
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

  .groups-grid {
    grid-template-columns: 1fr;
  }

  .pagination {
    flex-direction: column;
    align-items: center;
  }

  .group-card__actions {
    opacity: 1;
  }
}
</style>
