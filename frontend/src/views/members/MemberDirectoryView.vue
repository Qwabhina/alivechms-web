<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { memberService } from '@/services/member.service'
import {
  useToast,
  ChPageHeader,
  confirm,
} from '@/design-system'
import type { Member, MemberFilters, MemberLookupData } from '@/types/member'
import {
  UserPlus,
  Search,
  Trash2,
  LayoutGrid,
  List,
  PanelRight,
  Filter,
  X,
  ChevronLeft,
  ChevronRight,
  Mail,
  Phone,
  MapPin,
  MoreVertical,
  Check,
} from 'lucide-vue-next'
import { normalizeProfileImage } from '@/utils/image'

// ─── Types ────────────────────────────────────────────────────────────────────

type ViewMode = 'list' | 'grid' | 'detail'

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function statusVariant(status: unknown): 'success' | 'default' | 'warning' | 'danger' | 'info' {
  const map: Record<string, 'success' | 'default' | 'warning' | 'danger' | 'info'> = {
    Active: 'success',
    Inactive: 'default',
    Pending: 'warning',
    Suspended: 'danger',
    Visitor: 'info',
  }
  return map[String(status)] ?? 'default'
}

function getInitials(firstName: string, familyName: string): string {
  return `${firstName.charAt(0)}${familyName.charAt(0)}`.toUpperCase()
}

// ─── Setup ────────────────────────────────────────────────────────────────────

const router = useRouter()
const toast = useToast()

// ─── State ────────────────────────────────────────────────────────────────────

const members = ref<Member[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(24)
const isLoading = ref(false)
const search = ref('')
const statusFilter = ref<string | number | null>(null)
const branchFilter = ref<number | null>(null)
const sortBy = ref('MbrRegistrationDate')
const sortDir = ref<'ASC' | 'DESC'>('DESC')
const lookupData = ref<MemberLookupData | null>(null)
const viewMode = ref<ViewMode>('grid')
const selectedMembers = ref<Set<number>>(new Set())
const detailMember = ref<Member | null>(null)
const showDetailPanel = ref(false)
const showFilters = ref(false)

// ─── Computed ───────────────────────────────────────────────────────────────────

const statusOptions = computed(() => [
  { value: '', label: 'All Statuses' },
  ...(lookupData.value?.membership_statuses.map((s) => ({ value: s.id, label: s.name })) ?? []),
])

const branchOptions = computed(() => [
  { value: '', label: 'All Branches' },
  ...(lookupData.value?.branches.map((b) => ({ value: b.id, label: b.name })) ?? []),
])

const sortOptions = [
  { value: 'MbrRegistrationDate', label: 'Date Joined' },
  { value: 'MbrFirstName', label: 'First Name' },
  { value: 'MbrFamilyName', label: 'Family Name' },
  { value: 'MembershipStatusName', label: 'Status' },
  { value: 'BranchName', label: 'Branch' },
]

const totalPages = computed(() => Math.ceil(total.value / pageSize.value))

const hasActiveFilters = computed(() =>
  (statusFilter.value !== null && String(statusFilter.value) !== '') ||
  (branchFilter.value !== null && String(branchFilter.value) !== '') ||
  search.value !== ''
)

const allSelected = computed(() =>
  members.value.length > 0 && selectedMembers.value.size === members.value.length
)


// ─── Data Loading ─────────────────────────────────────────────────────────────

async function loadMembers() {
  isLoading.value = true
  try {
    const filters: MemberFilters = {
      search: search.value || undefined,
      status: statusFilter.value ? String(statusFilter.value) : undefined,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    }
    const res = await memberService.getAll(page.value, pageSize.value, filters)
    // Handle both nested and flat response structures
    const paginatedData = res?.data
    if (!paginatedData) {
      members.value = []
      total.value = 0
      return
    }
    // Support both res.data.data (PaginatedResponse) and direct array
    const memberArray = Array.isArray(paginatedData.data)
      ? paginatedData.data
      : Array.isArray(paginatedData)
        ? paginatedData
        : []
    members.value = memberArray
    total.value = paginatedData.pagination?.total ?? memberArray.length ?? 0
    selectedMembers.value.clear()
  } catch (err) {
    console.error('Failed to load members:', err)
    toast.error('Failed to load members.')
    members.value = []
    total.value = 0
  } finally {
    isLoading.value = false
  }
}

async function loadLookupData() {
  try {
    const res = await memberService.getLookupData()
    if (res?.data) lookupData.value = res.data
  } catch {
    /* silent */
  }
}

// ─── Actions ──────────────────────────────────────────────────────────────────

function handleSearch() {
  page.value = 1
  loadMembers()
}

function handleFilterChange() {
  page.value = 1
  loadMembers()
}

function handleSort() {
  loadMembers()
}

function clearFilters() {
  search.value = ''
  statusFilter.value = null
  branchFilter.value = null
  page.value = 1
  loadMembers()
}

function goToPage(newPage: number) {
  if (newPage >= 1 && newPage <= totalPages.value) {
    page.value = newPage
    loadMembers()
  }
}

function toggleViewMode(mode: ViewMode) {
  viewMode.value = mode
  if (mode !== 'detail') {
    showDetailPanel.value = false
    detailMember.value = null
  }
}

function openMemberDetail(member: Member) {
  if (viewMode.value === 'detail') {
    detailMember.value = member
    showDetailPanel.value = true
  } else {
    router.push(`/members/${member.MbrID}`)
  }
}

function closeDetailPanel() {
  showDetailPanel.value = false
  detailMember.value = null
}

function toggleSelection(memberId: number) {
  if (selectedMembers.value.has(memberId)) {
    selectedMembers.value.delete(memberId)
  } else {
    selectedMembers.value.add(memberId)
  }
}


async function deleteSelected() {
  const confirmed = await confirm({
    title: 'Delete Selected Members',
    message: `Are you sure you want to delete ${selectedMembers.value.size} member(s)? This action cannot be undone.`,
    confirmLabel: 'Delete',
    cancelLabel: 'Cancel',
  })

  if (!confirmed) return

  try {
    const ids = Array.from(selectedMembers.value)
    await Promise.all(ids.map(id => memberService.delete(id)))
    toast.success(`${ids.length} member(s) deleted.`)
    selectedMembers.value.clear()
    loadMembers()
  } catch {
    toast.error('Failed to delete some members.')
  }
}

async function exportSelected() {
  toast.info(`Exporting ${selectedMembers.value.size} member(s)...`)
  // TODO: Implement export functionality
}

// ─── Watchers ─────────────────────────────────────────────────────────────────

let searchTimer: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    loadMembers()
  }, 400)
})

watch([statusFilter, branchFilter, page], () => loadMembers())

// ─── Init ─────────────────────────────────────────────────────────────────────

onMounted(() => {
  loadLookupData()
  loadMembers()
})
</script>

<template>
  <div class="directory-view">
    <ChPageHeader title="Members Directory" subtitle="Browse, search, and manage your church membership.">
      <template #actions>
        <ChButton variant="primary" @click="router.push('/members/create')">
          <template #icon><UserPlus :size="18" /></template>
          Add Member
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ─── Toolbar ─────────────────────────────────────────────────────────── -->
    <div class="toolbar">
      <!-- Search -->
      <div class="toolbar__search">
        <ChInput
          v-model="search"
          placeholder="Search by name, email, or ID..."
          size="md"
          clearable
          @clear="search = ''; handleSearch()"
        >
          <template #leading>
            <Search :size="16" />
          </template>
        </ChInput>
      </div>

      <!-- Filters Toggle -->
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

      <!-- Sort -->
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

      <!-- View Mode Toggle -->
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
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="All Statuses"
          size="md"
          clearable
          @update:modelValue="handleFilterChange"
        />
        <ChSelect
          v-model="branchFilter"
          :options="branchOptions"
          placeholder="All Branches"
          size="md"
          clearable
          @update:modelValue="handleFilterChange"
        />
        <ChButton variant="ghost" size="sm" @click="clearFilters">
          <template #icon><X :size="14" /></template>
          Clear All
        </ChButton>
      </div>
    </Transition>

    <!-- ─── Select All Bar ──────────────────────────────────────────────────── -->
    <div class="select-all-bar">
      <div :class="['checkbox', { 'checkbox--checked': allSelected } ]" @click="members.forEach(m => allSelected ? selectedMembers.delete(m.MbrID) : selectedMembers.add(m.MbrID))">
        <Check v-if="allSelected" :size="12" />
      </div>
      <span class="select-all-label" @click="members.forEach(m => allSelected ? selectedMembers.delete(m.MbrID) : selectedMembers.add(m.MbrID))">
        {{ allSelected ? 'Deselect All' : 'Select All' }}
      </span>
    </div>

    <!-- ─── Bulk Actions Bar ────────────────────────────────────────────────── -->
    <Transition name="slide-down">
      <div v-if="selectedMembers.size > 0" class="bulk-actions">
        <div class="bulk-actions__info">
          <Check :size="16" />
          <span>{{ selectedMembers.size }} selected</span>
        </div>
        <div class="bulk-actions__buttons">
          <ChButton variant="ghost" size="sm" @click="exportSelected">
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
    <div class="content-wrapper" :class="{ 'content-wrapper--with-panel': showDetailPanel }">
      <!-- GRID VIEW -->
      <div v-if="viewMode === 'grid'" class="grid-view">
        <div v-if="isLoading" class="loading-grid">
          <ChSkeleton v-for="i in 12" :key="i" class="member-card-skeleton" />
        </div>
        <div v-else-if="members.length === 0" class="empty-state">
          <ChEmptyState
            icon="users"
            title="No members found"
            :description="hasActiveFilters ? 'Try adjusting your filters.' : 'Get started by adding your first member.'"
          >
            <ChButton v-if="!hasActiveFilters" variant="primary" @click="router.push('/members/create')">
              Add Member
            </ChButton>
            <ChButton v-else variant="ghost" @click="clearFilters">
              Clear Filters
            </ChButton>
          </ChEmptyState>
        </div>
        <div v-else class="members-grid">
          <div
            v-for="member in members"
            :key="member.MbrID"
            :class="['member-card', { 'member-card--selected': selectedMembers.has(member.MbrID) }]"
            @click="openMemberDetail(member)"
          >
            <div class="member-card__checkbox" @click.stop="toggleSelection(member.MbrID)">
              <div :class="['checkbox', { 'checkbox--checked': selectedMembers.has(member.MbrID) }]">
                <Check v-if="selectedMembers.has(member.MbrID)" :size="12" />
              </div>
            </div>
            <div class="member-card__avatar">
              <img
                v-if="member.MbrProfilePicture"
                :src="normalizeProfileImage(member.MbrProfilePicture)"
                :alt="`${member.MbrFirstName} ${member.MbrFamilyName}`"
              />
              <div v-else class="avatar-placeholder">
                {{ getInitials(member.MbrFirstName, member.MbrFamilyName) }}
              </div>
            </div>
            <div class="member-card__info">
              <h3 class="member-card__name">
                {{ member.MbrFirstName }} {{ member.MbrFamilyName }}
              </h3>
              <p class="member-card__email">{{ member.MbrEmailAddress }}</p>
              <div class="member-card__meta">
                <ChBadge v-if="member.MembershipStatusName" :variant="statusVariant(member.MembershipStatusName)" size="sm">
                  {{ member.MembershipStatusName }}
                </ChBadge>
                <span v-if="member.BranchName" class="meta-item">{{ member.BranchName }}</span>
              </div>
            </div>
            <div class="member-card__actions" @click.stop>
              <ChDropdown position="bottom-end">
                <template #trigger>
                  <button class="action-btn">
                    <MoreVertical :size="16" />
                  </button>
                </template>
                <ChDropdownItem label="View Profile" @click="router.push(`/members/${member.MbrID}`)" />
                <ChDropdownItem label="Edit" @click="router.push(`/members/${member.MbrID}/edit`)" />
                <ChDropdownDivider />
                <ChDropdownItem label="Delete" variant="danger" @click="toggleSelection(member.MbrID)" />
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
            { key: 'avatar', label: '', type: 'slot', width: '48px', exportable: false },
            { key: 'name', label: 'Member', sortable: true, type: 'slot' },
            { key: 'MembershipStatusName', label: 'Status', type: 'badge', badgeVariant: statusVariant },
            { key: 'BranchName', label: 'Branch' },
            { key: 'MbrRegistrationDate', label: 'Joined', sortable: true, type: 'slot' },
            { key: 'actions', label: '', type: 'slot', exportable: false, align: 'right' },
          ]"
          :rows="members as Record<string, unknown>[]"
          :total="total"
          :page-size="pageSize"
          v-model:page="page"
          :loading="isLoading"
          row-key="MbrID"
          :hoverable="true"
          :clickable="true"
          :exportable="true"
          title="Member Directory"
          @sort="handleSort"
          @row-click="(row) => openMemberDetail(row as unknown as Member)"
        >
          <template #cell-select="{ row }">
            <div :class="['checkbox', { 'checkbox--checked': selectedMembers.has((row as any).MbrID) }]" @click.stop="toggleSelection((row as any).MbrID)">
              <Check v-if="selectedMembers.has((row as any).MbrID)" :size="12" />
            </div>
          </template>
          <template #cell-avatar="{ row }">
            <ChAvatar
              :name="`${(row as any).MbrFirstName} ${(row as any).MbrFamilyName}`"
              :src="normalizeProfileImage((row as any).MbrProfilePicture)"
              size="sm"
            />
          </template>
          <template #cell-name="{ row }">
            <div class="member-name">
              <span class="member-name__primary">
                {{ (row as any).MbrFirstName }} {{ (row as any).MbrFamilyName }}
              </span>
              <span class="member-name__secondary">{{ (row as any).MbrEmailAddress }}</span>
            </div>
          </template>
          <template #cell-MbrRegistrationDate="{ row }">
            {{ formatDate((row as any).MbrRegistrationDate) }}
          </template>
          <template #cell-actions="{ row }">
            <div class="row-actions">
              <ChButton size="sm" variant="ghost" @click.stop="router.push(`/members/${(row as any).MbrID}`)">
                View
              </ChButton>
              <ChButton size="sm" variant="ghost" @click.stop="router.push(`/members/${(row as any).MbrID}/edit`)">
                Edit
              </ChButton>
            </div>
          </template>
        </ChTable>
      </div>

      <!-- DETAIL VIEW (List with side panel) -->
      <div v-else class="detail-view">
        <div class="detail-view__list">
          <ChTable
            :columns="[
              { key: 'select', label: '', type: 'slot', width: '40px', exportable: false },
              { key: 'avatar', label: '', type: 'slot', width: '48px', exportable: false },
              { key: 'name', label: 'Member', sortable: true, type: 'slot' },
              { key: 'MembershipStatusName', label: 'Status', type: 'badge', badgeVariant: statusVariant },
              { key: 'BranchName', label: 'Branch' },
            ]"
            :rows="members as Record<string, unknown>[]"
            :total="total"
            :page-size="pageSize"
            v-model:page="page"
            :loading="isLoading"
            row-key="MbrID"
            :hoverable="true"
            :clickable="true"
            title="Select a member to preview"
            @sort="handleSort"
            @row-click="(row) => openMemberDetail(row as unknown as Member)"
          >
            <template #cell-select="{ row }">
              <div :class="['checkbox', { 'checkbox--checked': selectedMembers.has((row as any).MbrID) }]" @click.stop="toggleSelection((row as any).MbrID)">
                <Check v-if="selectedMembers.has((row as any).MbrID)" :size="12" />
              </div>
            </template>
            <template #cell-avatar="{ row }">
              <ChAvatar
                :name="`${(row as any).MbrFirstName} ${(row as any).MbrFamilyName}`"
                :src="normalizeProfileImage((row as any).MbrProfilePicture)"
                size="sm"
              />
            </template>
            <template #cell-name="{ row }">
              <div class="member-name">
                <span class="member-name__primary">
                  {{ (row as any).MbrFirstName }} {{ (row as any).MbrFamilyName }}
                </span>
                <span class="member-name__secondary">{{ (row as any).MbrEmailAddress }}</span>
              </div>
            </template>
          </ChTable>
        </div>
      </div>

      <!-- ─── Detail Side Panel ─────────────────────────────────────────────── -->
      <Transition name="slide-left">
        <div v-if="showDetailPanel && detailMember" class="detail-panel">
          <div class="detail-panel__header">
            <ChButton variant="ghost" size="sm" @click="closeDetailPanel">
              <template #icon><X :size="16" /></template>
            </ChButton>
          </div>
          <div class="detail-panel__content">
            <div class="detail-profile">
              <div class="detail-profile__avatar">
                <img
                  v-if="detailMember.MbrProfilePicture"
                  :src="normalizeProfileImage(detailMember.MbrProfilePicture)"
                  :alt="`${detailMember.MbrFirstName} ${detailMember.MbrFamilyName}`"
                />
                <div v-else class="detail-avatar-placeholder">
                  {{ getInitials(detailMember.MbrFirstName, detailMember.MbrFamilyName) }}
                </div>
              </div>
              <h2 class="detail-profile__name">
                {{ detailMember.MbrFirstName }} {{ detailMember.MbrFamilyName }}
              </h2>
              <p class="detail-profile__id">{{ detailMember.MbrUniqueID ?? `ID #${detailMember.MbrID}` }}</p>
              <div class="detail-profile__badges">
                <ChBadge v-if="detailMember.MembershipStatusName" :variant="statusVariant(detailMember.MembershipStatusName)">
                  {{ detailMember.MembershipStatusName }}
                </ChBadge>
                <ChBadge v-if="detailMember.BranchName" variant="default">
                  {{ detailMember.BranchName }}
                </ChBadge>
              </div>
            </div>

            <ChDivider />

            <div class="detail-section">
              <h4>Contact Information</h4>
              <div class="detail-contact">
                <div v-if="detailMember.MbrEmailAddress" class="detail-contact__item">
                  <Mail :size="14" />
                  <span>{{ detailMember.MbrEmailAddress }}</span>
                </div>
                <div v-if="detailMember.PrimaryPhone" class="detail-contact__item">
                  <Phone :size="14" />
                  <span>{{ detailMember.PrimaryPhone }}</span>
                </div>
                <div v-if="detailMember.MbrResidentialAddress" class="detail-contact__item">
                  <MapPin :size="14" />
                  <span>{{ detailMember.MbrResidentialAddress }}</span>
                </div>
              </div>
            </div>

            <ChDivider />

            <div class="detail-section">
              <h4>Membership Details</h4>
              <ChDataList
                :items="[
                  { label: 'Date Joined', value: formatDate(detailMember.MbrRegistrationDate) },
                  { label: 'Gender', value: detailMember.MbrGender ?? '—' },
                  { label: 'Date of Birth', value: formatDate(detailMember.MbrDateOfBirth) },
                  { label: 'Occupation', value: detailMember.MbrOccupation ?? '—' },
                ]"
              />
            </div>

            <div class="detail-actions">
              <ChButton variant="primary" block @click="router.push(`/members/${detailMember.MbrID}`)">
                View Full Profile
              </ChButton>
              <ChButton variant="outline" block @click="router.push(`/members/${detailMember.MbrID}/edit`)">
                Edit Member
              </ChButton>
            </div>
          </div>
        </div>
      </Transition>
    </div>

    <!-- ─── Pagination ──────────────────────────────────────────────────────── -->
    <div v-if="totalPages > 1" class="pagination">
      <div class="pagination__info">
        Showing {{ (page - 1) * pageSize + 1 }} - {{ Math.min(page * pageSize, total) }} of {{ total }} members
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

.member-card-skeleton {
  height: 140px;
  border-radius: var(--ch-radius-xl);
}

.members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--ch-space-4);
}

.member-card {
  display: flex;
  align-items: flex-start;
  gap: var(--ch-space-3);
  padding: var(--ch-space-4);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-xl);
  cursor: pointer;
  transition: all 0.15s ease;
  position: relative;
}

.member-card:hover {
  border-color: var(--ch-color-primary-muted);
  box-shadow: var(--ch-shadow-md);
  transform: translateY(-1px);
}

.member-card--selected {
  border-color: var(--ch-color-primary);
  background: var(--ch-color-primary-subtle);
}

.member-card__checkbox {
  position: absolute;
  top: var(--ch-space-3);
  left: var(--ch-space-3);
  z-index: 1;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.member-card:hover .member-card__checkbox,
.member-card--selected .member-card__checkbox {
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

.member-card__avatar {
  width: 48px;
  height: 48px;
  border-radius: var(--ch-radius-lg);
  overflow: hidden;
  flex-shrink: 0;
  background: var(--ch-color-bg-subtle);
  margin-left: var(--ch-space-6);
}

.member-card__avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--ch-color-primary-muted), var(--ch-color-primary));
  color: white;
  font-weight: var(--ch-font-semibold);
  font-size: var(--ch-text-lg);
}

.member-card__info {
  flex: 1;
  min-width: 0;
}

.member-card__name {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0 0 var(--ch-space-1);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.member-card__email {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  margin: 0 0 var(--ch-space-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.member-card__meta {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  flex-wrap: wrap;
}

.meta-item {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
}

.member-card__actions {
  opacity: 0;
  transition: opacity 0.15s ease;
}

.member-card:hover .member-card__actions {
  opacity: 1;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  color: var(--ch-color-text-subtle);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
}

.action-btn:hover {
  background: var(--ch-color-bg-subtle);
  color: var(--ch-color-text);
}

/* ─── List View ─────────────────────────────────────────────────────────────── */
.list-view {
  flex: 1;
}

.member-name {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.member-name__primary {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.member-name__secondary {
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
  overflow: hidden;
  margin: 0 auto var(--ch-space-4);
  background: var(--ch-color-bg-subtle);
}

.detail-profile__avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.detail-avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--ch-color-primary-muted), var(--ch-color-primary));
  color: white;
  font-weight: var(--ch-font-semibold);
  font-size: var(--ch-text-2xl);
}

.detail-profile__name {
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
  margin: 0 0 var(--ch-space-1);
}

.detail-profile__id {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-subtle);
  margin: 0 0 var(--ch-space-3);
}

.detail-profile__badges {
  display: flex;
  justify-content: center;
  gap: var(--ch-space-2);
  flex-wrap: wrap;
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

.detail-contact {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3);
}

.detail-contact__item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
}

.detail-contact__item svg {
  color: var(--ch-color-text-subtle);
  flex-shrink: 0;
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

  .members-grid {
    grid-template-columns: 1fr;
  }

  .pagination {
    flex-direction: column;
    align-items: center;
  }

  .member-card__actions {
    opacity: 1;
  }
}
</style>
