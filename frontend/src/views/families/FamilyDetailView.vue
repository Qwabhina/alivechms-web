<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { familyService } from '@/services/family.service'
import { memberService } from '@/services/member.service'
import { useToast, confirm } from '@/design-system'
import type { FamilyDetail, FamilyMember } from '@/types'
import { ArrowLeft, Edit, Trash2, UserPlus, Crown, Home, User, Check, MoreVertical } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const familyId = Number(route.params.id)

// State
const loading = ref(false)
const family = ref<FamilyDetail | null>(null)
const deleteModalOpen = ref(false)

// Add Member Modal State
const addMemberModalOpen = ref(false)
const memberSearchQuery = ref('')
const memberSearchResults = ref<Array<{ MbrID: number; FullName: string; MbrEmailAddress?: string }>>([])
const selectedMemberToAdd = ref<number | null>(null)
const relationship = ref('Member')
const isAddingMember = ref(false)

// Methods
async function loadFamily() {
  loading.value = true
  try {
    const response = await familyService.get(familyId)
    if (response.status === 'success' && response.data) {
      family.value = response.data
    }
  } catch {
    toast.error('Failed to load family details')
  } finally {
    loading.value = false
  }
}

function navigateToEdit() {
  router.push(`/families/${familyId}/edit`)
}

function navigateToMember(memberId: number) {
  router.push(`/members/${memberId}`)
}

function confirmDelete() {
  deleteModalOpen.value = true
}

async function handleDelete() {
  try {
    await familyService.delete(familyId)
    toast.success('Family deleted successfully')
    router.push('/families')
  } catch {
    toast.error('Failed to delete family')
  } finally {
    deleteModalOpen.value = false
  }
}

// Member Management Methods
async function searchMembers(query: string) {
  if (query.length < 2) {
    memberSearchResults.value = []
    return
  }
  try {
    const response = await memberService.search(query)
    if (response.status === 'success' && response.data) {
      // Filter out members already in family
      const existingIds = new Set(family.value?.members?.map(m => m.MemberID) || [])
      memberSearchResults.value = response.data.filter(m => !existingIds.has(m.MbrID))
    }
  } catch {
    memberSearchResults.value = []
  }
}

async function handleAddMember() {
  if (!selectedMemberToAdd.value) return
  isAddingMember.value = true
  try {
    await familyService.addMember(familyId, selectedMemberToAdd.value, relationship.value)
    toast.success('Member added to family')
    addMemberModalOpen.value = false
    resetAddMemberForm()
    loadFamily()
  } catch {
    toast.error('Failed to add member to family')
  } finally {
    isAddingMember.value = false
  }
}

async function handleRemoveMember(memberId: number) {
  const confirmed = await confirm({
    title: 'Remove Member',
    message: 'Are you sure you want to remove this member from the family?',
    confirmLabel: 'Remove',
    cancelLabel: 'Cancel',
  })
  if (!confirmed) return
  try {
    await familyService.removeMember(familyId, memberId)
    toast.success('Member removed from family')
    loadFamily()
  } catch {
    toast.error('Failed to remove member')
  }
}

async function handleSetFamilyHead(memberId: number) {
  try {
    await familyService.setFamilyHead(familyId, memberId)
    toast.success('Family head updated')
    loadFamily()
  } catch {
    toast.error('Failed to set family head')
  }
}

function resetAddMemberForm() {
  memberSearchQuery.value = ''
  memberSearchResults.value = []
  selectedMemberToAdd.value = null
  relationship.value = 'Member'
}

function openAddMemberModal() {
  resetAddMemberForm()
  addMemberModalOpen.value = true
}

function selectMemberForAdd(member: { MbrID: number; FullName: string }) {
  selectedMemberToAdd.value = member.MbrID
  memberSearchQuery.value = member.FullName
  memberSearchResults.value = []
}

// Initialize
onMounted(() => {
  loadFamily()
})
</script>

<template>
  <div class="family-detail">
    <div v-if="loading" class="loading-state">
      <ChSpinner size="lg" />
      <span>Loading family details...</span>
    </div>

    <template v-else-if="family">
      <!-- Header -->
      <div class="page-header">
        <ChPageHeader
          :title="family.FamilyName"
          :subtitle="family.FamilyHeadName ? `Family Head: ${family.FamilyHeadName}` : 'Family Profile'"
        >
          <template #actions>
            <ChButton variant="outline" @click="router.push('/families')">
              <template #icon><ArrowLeft :size="18" /></template>
              Back
            </ChButton>
            <ChButton variant="outline" @click="navigateToEdit">
              <template #icon><Edit :size="18" /></template>
              Edit
            </ChButton>
            <ChButton variant="danger" @click="confirmDelete">
              <template #icon><Trash2 :size="18" /></template>
              Delete
            </ChButton>
          </template>
        </ChPageHeader>
      </div>

      <div class="detail-grid">
        <!-- Family Information -->
        <ChCard shadow="sm">
          <template #header>
            <div class="card-header">
              <Home :size="20" />
              <h2 class="section-title">Family Information</h2>
            </div>
          </template>

          <ChDataList
            :items="[
              { label: 'Address', value: family.Address || '—' },
              { label: 'City', value: family.City || '—' },
              { label: 'Region', value: family.Region || '—' },
              { label: 'Country', value: family.Country || '—' },
              { label: 'Home Phone', value: family.HomePhone || '—' },
            ]"
          />
        </ChCard>

        <!-- Family Members -->
        <ChCard shadow="sm">
          <template #header>
            <div class="members-header">
              <div class="card-header">
                <User :size="20" />
                <h2 class="section-title">
                  Family Members
                  <ChBadge variant="secondary" size="sm">{{ family.members?.length || 0 }}</ChBadge>
                </h2>
              </div>
              <ChButton variant="primary" size="sm" @click="openAddMemberModal">
                <template #icon><UserPlus :size="16" /></template>
                Add Member
              </ChButton>
            </div>
          </template>

          <div class="members-list">
            <div
              v-for="member in family.members"
              :key="member.MemberID"
              class="member-item"
            >
              <div class="member-info" @click="navigateToMember(member.MemberID)">
                <ChAvatar :name="member.FullName" size="md" />
                <div class="member-details">
                  <span class="member-name">{{ member.FullName }}</span>
                  <span class="member-relationship">{{ member.Relationship }}</span>
                </div>
              </div>
              <div class="member-actions">
                <ChBadge
                  :variant="member.MembershipStatus === 'Active' ? 'success' : 'default'"
                  size="sm"
                >
                  {{ member.MembershipStatus }}
                </ChBadge>
                <span v-if="member.IsFamilyHead" class="head-badge">
                  <Crown :size="12" />
                  Head
                </span>
                <ChDropdown position="bottom-end">
                  <template #trigger>
                    <button class="action-btn" @click.stop>
                      <MoreVertical :size="16" />
                    </button>
                  </template>
                  <ChDropdownItem
                    label="View Profile"
                    @click="navigateToMember(member.MemberID)"
                  />
                  <ChDropdownItem
                    v-if="!member.IsFamilyHead"
                    label="Set as Family Head"
                    @click="handleSetFamilyHead(member.MemberID)"
                  />
                  <ChDropdownDivider />
                  <ChDropdownItem
                    label="Remove from Family"
                    variant="danger"
                    @click="handleRemoveMember(member.MemberID)"
                  />
                </ChDropdown>
              </div>
            </div>

            <div v-if="!family.members?.length" class="empty-members">
              <ChEmptyState
                icon="users"
                title="No members yet"
                description="This family doesn't have any members. Click 'Add Member' to get started."
              >
                <ChButton variant="primary" size="sm" @click="openAddMemberModal">
                  <template #icon><UserPlus :size="16" /></template>
                  Add First Member
                </ChButton>
              </ChEmptyState>
            </div>
          </div>
        </ChCard>
      </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <ChModal v-model:open="deleteModalOpen" title="Delete Family" size="sm">
      <p class="modal-body">
        Are you sure you want to delete <strong>{{ family?.FamilyName }}</strong>?
        This will remove all member associations. This action cannot be undone.
      </p>
      <template #footer>
        <ChButton variant="ghost" @click="deleteModalOpen = false">Cancel</ChButton>
        <ChButton variant="danger" @click="handleDelete">Delete Family</ChButton>
      </template>
    </ChModal>

    <!-- Add Member Modal -->
    <ChModal v-model:open="addMemberModalOpen" title="Add Member to Family" size="md">
      <div class="add-member-form">
        <ChFormField label="Search Member" required>
          <ChInput
            v-model="memberSearchQuery"
            placeholder="Type at least 2 characters to search..."
            @input="(e: Event) => searchMembers((e.target as HTMLInputElement).value)"
          />
          <div v-if="memberSearchResults.length > 0" class="search-results">
            <div
              v-for="member in memberSearchResults"
              :key="member.MbrID"
              :class="['search-result-item', { 'search-result-item--selected': selectedMemberToAdd === member.MbrID }]"
              @click="selectMemberForAdd(member)"
            >
              <div class="result-info">
                <span class="result-name">{{ member.FullName }}</span>
                <span v-if="member.MbrEmailAddress" class="result-email">{{ member.MbrEmailAddress }}</span>
              </div>
              <Check v-if="selectedMemberToAdd === member.MbrID" :size="16" class="result-check" />
            </div>
          </div>
        </ChFormField>

        <ChFormField label="Relationship" required>
          <ChSelect
            v-model="relationship"
            :options="[
              { value: 'Spouse', label: 'Spouse' },
              { value: 'Child', label: 'Child' },
              { value: 'Parent', label: 'Parent' },
              { value: 'Sibling', label: 'Sibling' },
              { value: 'Other', label: 'Other' },
            ]"
          />
        </ChFormField>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="addMemberModalOpen = false">Cancel</ChButton>
        <ChButton
          variant="primary"
          :loading="isAddingMember"
          :disabled="!selectedMemberToAdd"
          @click="handleAddMember"
        >
          Add Member
        </ChButton>
      </template>
    </ChModal>
  </div>
</template>

<style scoped>
.family-detail {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  padding-bottom: var(--ch-space-8);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-4);
  padding: var(--ch-space-12);
  color: var(--ch-color-text-muted);
}

.page-header {
  margin-bottom: var(--ch-space-2);
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: var(--ch-space-6);
}

@media (max-width: 1024px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

.card-header {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  color: var(--ch-color-text);
}

.section-title {
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  margin: 0;
}

.members-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.members-list {
  display: flex;
  flex-direction: column;
}

.member-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--ch-space-3) var(--ch-space-4);
  border-bottom: 1px solid var(--ch-color-border-subtle);
  transition: background-color 0.15s ease;
}

.member-item:hover {
  background-color: var(--ch-color-bg-subtle);
}

.member-item:last-child {
  border-bottom: none;
}

.member-info {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  cursor: pointer;
  flex: 1;
}

.member-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.member-name {
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.member-relationship {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.member-actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.head-badge {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-warning);
  font-weight: var(--ch-font-medium);
}

.head-badge svg {
  color: var(--ch-color-warning);
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
  transition: all 0.15s ease;
}

.action-btn:hover {
  background: var(--ch-color-bg-subtle);
  color: var(--ch-color-text);
}

.empty-members {
  padding: var(--ch-space-8);
}

.modal-body {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  line-height: var(--ch-leading-relaxed);
}

/* Add Member Modal Styles */
.add-member-form {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

.search-results {
  margin-top: var(--ch-space-2);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-lg);
  max-height: 200px;
  overflow-y: auto;
  background: var(--ch-color-surface);
}

.search-result-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--ch-space-3) var(--ch-space-4);
  cursor: pointer;
  border-bottom: 1px solid var(--ch-color-border-subtle);
  transition: background-color 0.15s ease;
}

.search-result-item:last-child {
  border-bottom: none;
}

.search-result-item:hover,
.search-result-item--selected {
  background-color: var(--ch-color-bg-subtle);
}

.result-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.result-name {
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.result-email {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.result-check {
  color: var(--ch-color-primary);
}
</style>
