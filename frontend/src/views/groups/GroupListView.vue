<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { groupService } from '@/services/group.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { Group, GroupListFilters } from '@/types'

const router = useRouter()
const toast = useToast()

// State
const groups = ref<Group[]>([])
const loading = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const itemsPerPage = 10
const searchQuery = ref('')
const selectedFilters = ref<GroupListFilters>({})
const deleteModalOpen = ref(false)
const groupToDelete = ref<Group | null>(null)

// Computed
const hasFilters = computed(() => {
  return Object.values(selectedFilters.value).some(v => v !== undefined && v !== '')
})

const columns = [
  {
    key: 'GroupName',
    label: 'Group Name',
    sortable: true,
    type: 'slot' as const,
  },
  {
    key: 'LeaderName',
    label: 'Leader',
    type: 'slot' as const,
  },
  {
    key: 'CoLeaderName',
    label: 'Co-Leader',
    type: 'slot' as const,
  },
  {
    key: 'MeetingInfo',
    label: 'Meeting',
    type: 'slot' as const,
  },
  {
    key: 'actions',
    label: '',
    type: 'slot' as const,
    width: '120px',
    exportable: false,
    align: 'right' as const,
  },
]

// Methods
async function loadGroups(page: number = 1) {
  loading.value = true
  try {
    const filters: GroupListFilters = {
      search: searchQuery.value || undefined,
      ...selectedFilters.value,
    }

    const response = await groupService.list(page, itemsPerPage, filters)

    if (response.status === 'success' && response.data) {
      groups.value = response.data.data
      totalPages.value = response.data.pagination.total_pages
      totalItems.value = response.data.pagination.total
      currentPage.value = page
    }
  } catch {
    toast.error('Failed to load groups')
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  loadGroups(1)
}

function handlePageChange(page: number) {
  loadGroups(page)
}

function navigateToCreate() {
  router.push('/groups/create')
}

function navigateToEdit(group: Group) {
  router.push(`/groups/${group.GroupID}/edit`)
}

function navigateToView(group: Group) {
  router.push(`/groups/${group.GroupID}`)
}

function confirmDelete(group: Group) {
  groupToDelete.value = group
  deleteModalOpen.value = true
}

async function handleDelete() {
  if (!groupToDelete.value) return

  try {
    await groupService.delete(groupToDelete.value.GroupID)
    toast.success('Group deleted successfully')
    loadGroups(currentPage.value)
  } catch {
    toast.error('Failed to delete group')
  } finally {
    deleteModalOpen.value = false
    groupToDelete.value = null
  }
}

function clearFilters() {
  searchQuery.value = ''
  selectedFilters.value = {}
  loadGroups(1)
}

// Initialize
onMounted(() => {
  loadGroups()
})
</script>

<template>
  <div class="group-list">
    <ChPageHeader title="Groups" subtitle="Manage church groups and ministries">
      <template #actions>
        <ChButton
          variant="primary"
          left-icon="plus"
          @click="navigateToCreate"
        >
          Create Group
        </ChButton>
      </template>
    </ChPageHeader>

    <ChCard>

      <!-- Filters -->
      <div class="filters-section">
        <div class="search-bar">
          <ChInput
            v-model="searchQuery"
            placeholder="Search groups..."
            left-icon="search"
            @keyup.enter="handleSearch"
          />
          <ChButton
            variant="secondary"
            left-icon="filter"
            :class="{ active: hasFilters }"
            @click="handleSearch"
          >
            Search
          </ChButton>
        </div>
        <ChButton
          v-if="hasFilters || searchQuery"
          variant="ghost"
          left-icon="x"
          @click="clearFilters"
        >
          Clear
        </ChButton>
      </div>

      <!-- Table -->
      <ChTable
        :columns="columns"
        :rows="groups"
        :total="totalItems"
        :page-size="itemsPerPage"
        v-model:page="currentPage"
        :loading="loading"
        row-key="GroupID"
        :empty-message="'No groups found'"
        :empty-description="'Create your first group to get started'"
      >
        <template #cell-GroupName="{ row }">
          <div class="group-name-cell">
            <ChAvatar
              :name="row.GroupName"
              size="sm"
            />
            <div class="group-info">
              <span class="group-name">{{ row.GroupName }}</span>
              <span v-if="row.TypeName" class="group-type">{{ row.TypeName }}</span>
            </div>
          </div>
        </template>

        <template #cell-LeaderName="{ row }">
          <div class="leader-cell">
            <span v-if="row.LeaderName">{{ row.LeaderName }}</span>
            <span v-else class="no-data">—</span>
          </div>
        </template>

        <template #cell-CoLeaderName="{ row }">
          <div class="leader-cell">
            <span v-if="row.CoLeaderName">{{ row.CoLeaderName }}</span>
            <span v-else class="no-data">—</span>
          </div>
        </template>

        <template #cell-MeetingInfo="{ row }">
          <div class="meeting-cell">
            <span v-if="row.MeetingDay">{{ row.MeetingDay }}</span>
            <span v-if="row.MeetingTime" class="meeting-time">at {{ row.MeetingTime }}</span>
            <span v-if="!row.MeetingDay && !row.MeetingTime" class="no-data">—</span>
          </div>
        </template>

        <template #cell-actions="{ row }">
          <div class="action-buttons">
            <ChButton
              variant="ghost"
              size="sm"
              left-icon="eye"
              @click="navigateToView(row)"
            />
            <ChButton
              variant="ghost"
              size="sm"
              left-icon="edit"
              @click="navigateToEdit(row)"
            />
            <ChButton
              variant="ghost"
              size="sm"
              left-icon="trash"
              @click="confirmDelete(row)"
            />
          </div>
        </template>
      </ChTable>
    </ChCard>

    <!-- Delete Confirmation Modal -->
    <ChModal
      :open="deleteModalOpen"
      :title="'Delete Group'"
      @close="deleteModalOpen = false"
    >
      <p>
        Are you sure you want to delete <strong>{{ groupToDelete?.GroupName }}</strong>?
        This action cannot be undone.
      </p>
      <template #footer>
        <div class="modal-actions">
          <ChButton
            variant="secondary"
            @click="deleteModalOpen = false"
          >
            Cancel
          </ChButton>
          <ChButton
            variant="danger"
            @click="handleDelete"
          >
            Delete
          </ChButton>
        </div>
      </template>
    </ChModal>
  </div>
</template>

<style scoped>
.group-list {
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

.group-name-cell {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.group-info {
  display: flex;
  flex-direction: column;
}

.group-name {
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.group-type {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.leader-cell {
  color: var(--ch-color-text);
}

.meeting-cell {
  display: flex;
  gap: var(--ch-space-1);
  color: var(--ch-color-text);
}

.meeting-time {
  color: var(--ch-color-text-muted);
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
