<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { groupService } from '@/services/group.service'
import { memberService } from '@/services/member.service'
import { useToast, confirm } from '@/design-system'
import type { GroupDetail } from '@/types'
import type { GroupMember } from '@/types/operations'
import { ArrowLeft, Edit, Trash2, UserPlus, Users, Crown, Check, MoreVertical } from '@lucide/vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const groupId = Number(route.params.id)

const loading = ref(false)
const group = ref<GroupDetail | null>(null)
const deleteModalOpen = ref(false)

// Member Management State
const members = ref<GroupMember[]>([])
const addMemberModalOpen = ref(false)
const memberSearchQuery = ref('')
const memberSearchResults = ref<Array<{ MbrID: number; FullName: string; MbrEmailAddress?: string }>>([])
const selectedMemberToAdd = ref<number | null>(null)
const isAddingMember = ref(false)

async function loadGroup() {
  loading.value = true
  try {
    const response = await groupService.get(groupId)
    if (response.status === 'success' && response.data) {
      group.value = response.data
      // Load members if available
      if (response.data.members) {
        members.value = response.data.members
      }
    }
  } catch {
    toast.error('Failed to load group details')
  } finally {
    loading.value = false
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
      // Filter out members already in group
      const existingIds = new Set(members.value.map(m => m.MemberID))
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
    await groupService.addMember(groupId, selectedMemberToAdd.value)
    toast.success('Member added to group')
    addMemberModalOpen.value = false
    resetAddMemberForm()
    loadGroup()
  } catch {
    toast.error('Failed to add member to group')
  } finally {
    isAddingMember.value = false
  }
}

async function handleRemoveMember(memberId: number) {
  const confirmed = await confirm({
    title: 'Remove Member',
    message: 'Are you sure you want to remove this member from the group?',
    confirmLabel: 'Remove',
    cancelLabel: 'Cancel',
  })
  if (!confirmed) return
  try {
    await groupService.removeMember(groupId, memberId)
    toast.success('Member removed from group')
    loadGroup()
  } catch {
    toast.error('Failed to remove member')
  }
}

function resetAddMemberForm() {
  memberSearchQuery.value = ''
  memberSearchResults.value = []
  selectedMemberToAdd.value = null
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

function navigateToEdit() {
  router.push(`/groups/${groupId}/edit`)
}

function confirmDelete() {
  deleteModalOpen.value = true
}

async function handleDelete() {
  try {
    await groupService.delete(groupId)
    toast.success('Group deleted successfully')
    router.push('/groups')
  } catch {
    toast.error('Failed to delete group')
  } finally {
    deleteModalOpen.value = false
  }
}

onMounted(loadGroup)
</script>

<template>
  <div class="group-detail">
    <div v-if="loading" class="loading-state"><ChSpinner size="lg" /><span>Loading group details...</span></div>

    <template v-else-if="group">
      <!-- Header -->
      <div class="page-header">
        <ChPageHeader
          :title="group.GroupName as string"
          :subtitle="(group.TypeName as string) || 'Group Profile'"
        >
          <template #actions>
            <ChButton variant="outline" @click="router.push('/groups')">
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
        <!-- Group Information -->
        <ChCard shadow="sm">
          <template #header>
            <div class="card-header">
              <Users :size="20" />
              <h2 class="section-title">Group Information</h2>
            </div>
          </template>
          <ChDataList
            :items="[
              { label: 'Leader', value: group.LeaderName || '—' },
              { label: 'Co-Leader', value: group.CoLeaderName || '—' },
              { label: 'Meeting Day', value: group.MeetingDay || '—' },
              { label: 'Meeting Time', value: group.MeetingTime || '—' },
              { label: 'Meeting Location', value: group.MeetingLocation || '—' },
              { label: 'Total Members', value: String(group.TotalMembers || 0) },
            ]"
          />
        </ChCard>

        <!-- Description -->
        <ChCard shadow="sm">
          <template #header>
            <h2 class="section-title">Description</h2>
          </template>
          <p class="description-text">{{ group.Description || 'No description available.' }}</p>
        </ChCard>

        <!-- Group Members -->
        <ChCard shadow="sm" class="members-card">
          <template #header>
            <div class="members-header">
              <div class="card-header">
                <Users :size="20" />
                <h2 class="section-title">
                  Group Members
                  <ChBadge variant="secondary" size="sm">{{ members.length }}</ChBadge>
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
              v-for="member in members"
              :key="member.MemberID"
              class="member-item"
            >
              <div class="member-info" @click="router.push(`/members/${member.MemberID}`)">
                <ChAvatar :name="member.FullName" size="md" />
                <div class="member-details">
                  <span class="member-name">{{ member.FullName }}</span>
                  <span class="member-role">{{ member.Role || 'Member' }}</span>
                </div>
              </div>
              <div class="member-actions">
                <span v-if="member.Role === 'Leader'" class="leader-badge">
                  <Crown :size="12" />
                  Leader
                </span>
                <ChDropdown position="bottom-end">
                  <template #trigger>
                    <button class="action-btn" @click.stop>
                      <MoreVertical :size="16" />
                    </button>
                  </template>
                  <ChDropdownItem
                    label="View Profile"
                    @click="router.push(`/members/${member.MemberID}`)"
                  />
                  <ChDropdownDivider />
                  <ChDropdownItem
                    label="Remove from Group"
                    variant="danger"
                    @click="handleRemoveMember(member.MemberID)"
                  />
                </ChDropdown>
              </div>
            </div>

            <div v-if="members.length === 0" class="empty-members">
              <ChEmptyState
                icon="users"
                title="No members yet"
                description="This group doesn't have any members. Click 'Add Member' to get started."
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

      <!-- Delete Confirmation Modal -->
      <ChModal v-model:open="deleteModalOpen" title="Delete Group" size="sm">
        <p class="modal-body">
          Are you sure you want to delete <strong>{{ group?.GroupName }}</strong>?
          This will remove all member associations. This action cannot be undone.
        </p>
        <template #footer>
          <ChButton variant="ghost" @click="deleteModalOpen = false">Cancel</ChButton>
          <ChButton variant="danger" @click="handleDelete">Delete Group</ChButton>
        </template>
      </ChModal>

      <!-- Add Member Modal -->
      <ChModal v-model:open="addMemberModalOpen" title="Add Member to Group" size="md">
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
    </template>

    <!-- Not Found -->
    <div v-else class="empty-state">
      <ChEmptyState
        icon="search"
        title="Group not found"
        description="The group you're looking for doesn't exist or has been removed."
      >
        <ChButton variant="primary" @click="router.push('/groups')">
          Back to Groups
        </ChButton>
      </ChEmptyState>
    </div>
  </div>
</template>

<style scoped>
.group-detail {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  padding-bottom: var(--ch-space-8);
}

.page-header {
  margin-bottom: var(--ch-space-2);
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
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

.description-text {
  padding: var(--ch-space-4);
  color: var(--ch-color-text);
  line-height: var(--ch-leading-relaxed);
  margin: 0;
}

.members-card {
  grid-column: 1 / -1;
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

.member-role {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.member-actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.leader-badge {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-warning);
  font-weight: var(--ch-font-medium);
}

.leader-badge svg {
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

.empty-state {
  padding: var(--ch-space-12) 0;
}
.info-row { display: flex; justify-content: space-between; align-items: center; padding: var(--ch-space-2) 0; border-bottom: 1px solid var(--ch-color-border-subtle); }
.info-row:last-child { border-bottom: none; }
.info-label { font-weight: var(--ch-font-weight-medium); color: var(--ch-color-text-secondary); }
.info-value { color: var(--ch-color-text); }
.description-text { padding: var(--ch-space-4); color: var(--ch-color-text); line-height: 1.6; }
.modal-actions { display: flex; gap: var(--ch-space-3); justify-content: flex-end; }
</style>
