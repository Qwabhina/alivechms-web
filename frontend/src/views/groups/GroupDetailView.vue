<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { groupService } from '@/services/group.service'
import { useToast } from '@/design-system'
import type { GroupDetail } from '@/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const groupId = Number(route.params.id)

const loading = ref(false)
const group = ref<GroupDetail | null>(null)
const deleteModalOpen = ref(false)

async function loadGroup() {
  loading.value = true
  try {
    const response = await groupService.get(groupId)
    if (response.status === 'success' && response.data) {
      group.value = response.data
    }
  } catch {
    toast.error('Failed to load group details')
  } finally {
    loading.value = false
  }
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
      <ChCard class="header-card">
        <template #header>
          <div class="group-header">
            <div class="header-content">
              <ChBreadcrumb :items="[{ label: 'Groups', to: '/groups' }, { label: group.GroupName }]" />
              <div class="group-title">
                <ChAvatar :name="group.GroupName" size="xl" />
                <div class="title-content">
                  <h1 class="group-name">{{ group.GroupName }}</h1>
                  <p v-if="group.TypeName" class="group-type">{{ group.TypeName }}</p>
                </div>
              </div>
            </div>
            <div class="header-actions">
              <ChButton variant="secondary" left-icon="edit" @click="navigateToEdit">Edit</ChButton>
              <ChButton variant="danger" left-icon="trash" @click="confirmDelete">Delete</ChButton>
            </div>
          </div>
        </template>
      </ChCard>

      <div class="detail-grid">
        <ChCard>
          <template #header><h2 class="section-title">Group Information</h2></template>
          <div class="info-section">
            <div class="info-row"><span class="info-label">Leader</span><span class="info-value">{{ group.LeaderName || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Co-Leader</span><span class="info-value">{{ group.CoLeaderName || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Meeting Day</span><span class="info-value">{{ group.MeetingDay || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Meeting Time</span><span class="info-value">{{ group.MeetingTime || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Meeting Location</span><span class="info-value">{{ group.MeetingLocation || '—' }}</span></div>
            <div class="info-row"><span class="info-label">Total Members</span><span class="info-value">{{ group.TotalMembers || 0 }}</span></div>
          </div>
        </ChCard>

        <ChCard>
          <template #header><h2 class="section-title">Description</h2></template>
          <p class="description-text">{{ group.Description || 'No description available.' }}</p>
        </ChCard>
      </div>
    </template>

    <ChModal :open="deleteModalOpen" :title="'Delete Group'" @close="deleteModalOpen = false">
      <p>Are you sure you want to delete <strong>{{ group?.GroupName }}</strong>? This action cannot be undone.</p>
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
.group-detail { padding: var(--ch-space-6); }
.loading-state { display: flex; flex-direction: column; align-items: center; gap: var(--ch-space-4); padding: var(--ch-space-12); color: var(--ch-color-text-secondary); }
.header-card { margin-bottom: var(--ch-space-6); }
.group-header { display: flex; justify-content: space-between; align-items: flex-start; padding: var(--ch-space-6); }
.header-content { display: flex; flex-direction: column; gap: var(--ch-space-4); }
.group-title { display: flex; align-items: center; gap: var(--ch-space-4); }
.title-content { display: flex; flex-direction: column; }
.group-name { font-size: var(--ch-font-size-2xl); font-weight: var(--ch-font-weight-bold); margin: 0; }
.group-type { font-size: var(--ch-font-size-sm); color: var(--ch-color-text-secondary); margin: var(--ch-space-1) 0 0; }
.header-actions { display: flex; gap: var(--ch-space-2); }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--ch-space-6); }
@media (max-width: 1024px) { .detail-grid { grid-template-columns: 1fr; } }
.section-title { font-size: var(--ch-font-size-lg); font-weight: var(--ch-font-weight-semibold); margin: 0; }
.info-section { display: flex; flex-direction: column; gap: var(--ch-space-4); padding: var(--ch-space-4); }
.info-row { display: flex; justify-content: space-between; align-items: center; padding: var(--ch-space-2) 0; border-bottom: 1px solid var(--ch-color-border-subtle); }
.info-row:last-child { border-bottom: none; }
.info-label { font-weight: var(--ch-font-weight-medium); color: var(--ch-color-text-secondary); }
.info-value { color: var(--ch-color-text); }
.description-text { padding: var(--ch-space-4); color: var(--ch-color-text); line-height: 1.6; }
.modal-actions { display: flex; gap: var(--ch-space-3); justify-content: flex-end; }
</style>
