<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { familyService } from '@/services/family.service'
import { useToast } from '@/design-system'
import type { FamilyDetail } from '@/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const familyId = Number(route.params.id)

// State
const loading = ref(false)
const family = ref<FamilyDetail | null>(null)
const deleteModalOpen = ref(false)

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
      <ChCard class="header-card">
        <template #header>
          <div class="family-header">
            <div class="header-content">
              <ChBreadcrumb
                :items="[
                  { label: 'Families', to: '/families' },
                  { label: family.FamilyName },
                ]"
              />
              <div class="family-title">
                <ChAvatar
                  :name="family.FamilyName"
                  size="xl"
                />
                <div class="title-content">
                  <h1 class="family-name">{{ family.FamilyName }}</h1>
                  <p v-if="family.FamilyHeadName" class="family-head">
                    Family Head: {{ family.FamilyHeadName }}
                  </p>
                </div>
              </div>
            </div>
            <div class="header-actions">
              <ChButton
                variant="secondary"
                left-icon="edit"
                @click="navigateToEdit"
              >
                Edit
              </ChButton>
              <ChButton
                variant="danger"
                left-icon="trash"
                @click="confirmDelete"
              >
                Delete
              </ChButton>
            </div>
          </div>
        </template>
      </ChCard>

      <div class="detail-grid">
        <!-- Family Information -->
        <ChCard>
          <template #header>
            <h2 class="section-title">Family Information</h2>
          </template>

          <div class="info-section">
            <div class="info-row">
              <span class="info-label">Address</span>
              <span class="info-value">
                {{ family.Address || '—' }}
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">City</span>
              <span class="info-value">
                {{ family.City || '—' }}
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">Region</span>
              <span class="info-value">
                {{ family.Region || '—' }}
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">Country</span>
              <span class="info-value">
                {{ family.Country || '—' }}
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">Home Phone</span>
              <span class="info-value">
                {{ family.HomePhone || '—' }}
              </span>
            </div>
          </div>
        </ChCard>

        <!-- Family Members -->
        <ChCard>
          <template #header>
            <div class="section-header">
              <h2 class="section-title">
                Family Members
                <ChBadge :value="family.members?.length || 0" variant="secondary" />
              </h2>
            </div>
          </template>

          <div class="members-list">
            <div
              v-for="member in family.members"
              :key="member.MemberID"
              class="member-item"
              @click="navigateToMember(member.MemberID)"
            >
              <div class="member-info">
                <ChAvatar
                  :name="member.FullName"
                  size="md"
                />
                <div class="member-details">
                  <span class="member-name">{{ member.FullName }}</span>
                  <span class="member-relationship">{{ member.Relationship }}</span>
                </div>
              </div>
              <div class="member-status">
                <ChBadge
                  :value="member.MembershipStatus"
                  :variant="member.MembershipStatus === 'Active' ? 'success' : 'secondary'"
                />
                <span v-if="member.IsFamilyHead" class="head-indicator">
                  <ChIcon name="crown" size="sm" />
                  Head
                </span>
              </div>
            </div>

            <div v-if="!family.members?.length" class="empty-members">
              <ChEmptyState
                icon="users"
                title="No members yet"
                description="This family doesn't have any members associated with it."
              />
            </div>
          </div>
        </ChCard>
      </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <ChModal
      :open="deleteModalOpen"
      :title="'Delete Family'"
      @close="deleteModalOpen = false"
    >
      <p>
        Are you sure you want to delete <strong>{{ family?.FamilyName }}</strong>?
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
.family-detail {
  padding: var(--ch-space-6);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-4);
  padding: var(--ch-space-12);
  color: var(--ch-color-text-secondary);
}

.header-card {
  margin-bottom: var(--ch-space-6);
}

.family-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: var(--ch-space-6);
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

.family-title {
  display: flex;
  align-items: center;
  gap: var(--ch-space-4);
}

.title-content {
  display: flex;
  flex-direction: column;
}

.family-name {
  font-size: var(--ch-font-size-2xl);
  font-weight: var(--ch-font-weight-bold);
  color: var(--ch-color-text);
  margin: 0;
}

.family-head {
  font-size: var(--ch-font-size-sm);
  color: var(--ch-color-text-secondary);
  margin: var(--ch-space-1) 0 0;
}

.header-actions {
  display: flex;
  gap: var(--ch-space-2);
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

.section-title {
  font-size: var(--ch-font-size-lg);
  font-weight: var(--ch-font-weight-semibold);
  color: var(--ch-color-text);
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  margin: 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-section {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
  padding: var(--ch-space-4);
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--ch-space-2) 0;
  border-bottom: 1px solid var(--ch-color-border-subtle);
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: var(--ch-font-weight-medium);
  color: var(--ch-color-text-secondary);
}

.info-value {
  color: var(--ch-color-text);
}

.members-list {
  display: flex;
  flex-direction: column;
}

.member-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--ch-space-4);
  border-bottom: 1px solid var(--ch-color-border-subtle);
  cursor: pointer;
  transition: background-color 0.2s;
}

.member-item:hover {
  background-color: var(--ch-color-bg-hover);
}

.member-item:last-child {
  border-bottom: none;
}

.member-info {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.member-details {
  display: flex;
  flex-direction: column;
}

.member-name {
  font-weight: var(--ch-font-weight-medium);
  color: var(--ch-color-text);
}

.member-relationship {
  font-size: var(--ch-font-size-xs);
  color: var(--ch-color-text-secondary);
}

.member-status {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.head-indicator {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
  font-size: var(--ch-font-size-xs);
  color: var(--ch-color-warning);
}

.empty-members {
  padding: var(--ch-space-8);
}

.modal-actions {
  display: flex;
  gap: var(--ch-space-3);
  justify-content: flex-end;
}
</style>
