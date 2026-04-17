<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { familyService } from '@/services/family.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { Family, FamilyListFilters } from '@/types'

const router = useRouter()
const toast = useToast()

// State
const families = ref<Family[]>([])
const loading = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const itemsPerPage = 10
const searchQuery = ref('')
const selectedFilters = ref<FamilyListFilters>({})
const deleteModalOpen = ref(false)
const familyToDelete = ref<Family | null>(null)

// Computed
const hasFilters = computed(() => {
  return Object.values(selectedFilters.value).some(v => v !== undefined && v !== '')
})

const columns = [
  {
    key: 'FamilyName',
    label: 'Family Name',
    sortable: true,
    type: 'slot' as const,
  },
  {
    key: 'MemberCount',
    label: 'Members',
    width: '100px',
    type: 'slot' as const,
  },
  {
    key: 'City',
    label: 'Location',
    type: 'slot' as const,
  },
  {
    key: 'HomePhone',
    label: 'Contact',
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
async function loadFamilies(page: number = 1) {
  loading.value = true
  try {
    const filters: FamilyListFilters = {
      search: searchQuery.value || undefined,
      ...selectedFilters.value,
    }

    const response = await familyService.list(page, itemsPerPage, filters)

    if (response.status === 'success' && response.data) {
      families.value = response.data.data
      totalPages.value = response.data.pagination.total_pages
      totalItems.value = response.data.pagination.total
      currentPage.value = page
    }
  } catch {
    toast.error('Failed to load families')
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  loadFamilies(1)
}

function handlePageChange(page: number) {
  loadFamilies(page)
}

function navigateToCreate() {
  router.push('/families/create')
}

function navigateToEdit(family: Family) {
  router.push(`/families/${family.FamilyID}/edit`)
}

function navigateToView(family: Family) {
  router.push(`/families/${family.FamilyID}`)
}

function confirmDelete(family: Family) {
  familyToDelete.value = family
  deleteModalOpen.value = true
}

async function handleDelete() {
  if (!familyToDelete.value) return

  try {
    await familyService.delete(familyToDelete.value.FamilyID)
    toast.success('Family deleted successfully')
    loadFamilies(currentPage.value)
  } catch {
    toast.error('Failed to delete family')
  } finally {
    deleteModalOpen.value = false
    familyToDelete.value = null
  }
}

function clearFilters() {
  searchQuery.value = ''
  selectedFilters.value = {}
  loadFamilies(1)
}

// Initialize
onMounted(() => {
  loadFamilies()
})
</script>

<template>
  <div class="family-list">
    <ChPageHeader title="Families" subtitle="Manage church families and their members">
      <template #actions>
        <ChButton
          variant="primary"
          left-icon="plus"
          @click="navigateToCreate"
        >
          Create Family
        </ChButton>
      </template>
    </ChPageHeader>

    <ChCard>

      <!-- Filters -->
      <div class="filters-section">
        <div class="search-bar">
          <ChInput
            v-model="searchQuery"
            placeholder="Search families..."
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
        :rows="families"
        :total="totalItems"
        :page-size="itemsPerPage"
        v-model:page="currentPage"
        :loading="loading"
        row-key="FamilyID"
        :empty-message="'No families found'"
        :empty-description="'Create your first family to get started'"
      >
        <template #cell-FamilyName="{ row }">
          <div class="family-name-cell">
            <ChAvatar
              :name="row.FamilyName"
              size="sm"
            />
            <div class="family-info">
              <span class="family-name">{{ row.FamilyName }}</span>
              <span v-if="row.FamilyHeadName" class="family-head">
                Head: {{ row.FamilyHeadName }}
              </span>
            </div>
          </div>
        </template>

        <template #cell-MemberCount="{ row }">
          <ChBadge
            :value="row.MemberCount || 0"
            variant="secondary"
          />
        </template>

        <template #cell-City="{ row }">
          <div class="location-cell">
            <span v-if="row.City">{{ row.City }}</span>
            <span v-if="row.Region" class="region">, {{ row.Region }}</span>
            <span v-if="!row.City && !row.Region" class="no-data">—</span>
          </div>
        </template>

        <template #cell-HomePhone="{ row }">
          <span v-if="row.HomePhone">{{ row.HomePhone }}</span>
          <span v-else class="no-data">—</span>
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
      :title="'Delete Family'"
      @close="deleteModalOpen = false"
    >
      <p>
        Are you sure you want to delete <strong>{{ familyToDelete?.FamilyName }}</strong>?
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
.family-list {
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

.family-name-cell {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.family-info {
  display: flex;
  flex-direction: column;
}

.family-name {
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.family-head {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.location-cell {
  display: flex;
  color: var(--ch-color-text);
}

.region {
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
