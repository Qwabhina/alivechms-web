<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { groupService } from '@/services/group.service'
import { useToast } from '@/design-system'
import type { GroupDetail, GroupUpdateInput } from '@/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const groupId = Number(route.params.id)

const loading = ref(false)
const saving = ref(false)
const group = ref<GroupDetail | null>(null)
const form = ref<GroupUpdateInput>({
  group_id: groupId,
  group_name: '',
  description: '',
  meeting_day: '',
  meeting_time: '',
  meeting_location: '',
})

async function loadGroup() {
  loading.value = true
  try {
    const response = await groupService.get(groupId)
    if (response.status === 'success' && response.data) {
      group.value = response.data
      form.value = {
        group_id: groupId,
        group_name: response.data.GroupName,
        description: response.data.GroupDescription,
        meeting_day: response.data.MeetingDay,
        meeting_time: response.data.MeetingTime,
        meeting_location: '',
      }
    }
  } catch {
    toast.error('Failed to load group')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  if (!form.value.group_name) {
    toast.error('Group name is required')
    return
  }

  saving.value = true
  try {
    const response = await groupService.update(groupId, form.value)
    if (response.status === 'success') {
      toast.success('Group updated successfully')
      router.push('/groups')
    }
  } catch {
    toast.error('Failed to update group')
  } finally {
    saving.value = false
  }
}

function handleCancel() {
  router.push('/groups')
}

onMounted(loadGroup)
</script>

<template>
  <div class="group-edit">
    <ChCard>
      <template #header>
        <div class="page-header">
          <ChBreadcrumb :items="[{ label: 'Groups', to: '/groups' }, { label: group?.GroupName || 'Edit Group' }]" />
          <h1 class="page-title">Edit Group</h1>
        </div>
      </template>

      <div v-if="loading" class="loading-state"><ChSpinner size="lg" /><span>Loading group...</span></div>

      <form v-else @submit.prevent="handleSubmit">
        <div class="form-section">
          <h2 class="section-title">Basic Information</h2>
          <ChFormField label="Group Name *" required>
            <ChInput v-model="form.group_name" placeholder="Enter group name" required />
          </ChFormField>
          <ChFormField label="Description">
            <ChTextarea v-model="form.description" placeholder="Enter group description" :rows="3" />
          </ChFormField>
        </div>

        <div class="form-section">
          <h2 class="section-title">Meeting Details</h2>
          <div class="form-row">
            <ChFormField label="Meeting Day">
              <ChSelect v-model="form.meeting_day" :options="[{ value: '', label: 'Select day' }, { value: 'Sunday', label: 'Sunday' }, { value: 'Monday', label: 'Monday' }, { value: 'Tuesday', label: 'Tuesday' }, { value: 'Wednesday', label: 'Wednesday' }, { value: 'Thursday', label: 'Thursday' }, { value: 'Friday', label: 'Friday' }, { value: 'Saturday', label: 'Saturday' }]" />
            </ChFormField>
            <ChFormField label="Meeting Time">
              <ChInput v-model="form.meeting_time" type="time" />
            </ChFormField>
          </div>
          <ChFormField label="Meeting Location">
            <ChInput v-model="form.meeting_location" placeholder="Enter meeting location" />
          </ChFormField>
        </div>

        <div class="form-actions">
          <ChButton variant="secondary" @click="handleCancel">Cancel</ChButton>
          <ChButton type="submit" variant="primary" :loading="saving" left-icon="save">Save Changes</ChButton>
        </div>
      </form>
    </ChCard>
  </div>
</template>

<style scoped>
.group-edit { padding: var(--ch-space-6); max-width: 800px; margin: 0 auto; }
.page-header { padding: var(--ch-space-6); }
.page-title { font-size: var(--ch-font-size-xl); font-weight: var(--ch-font-weight-semibold); margin: var(--ch-space-2) 0 0; }
.loading-state { display: flex; flex-direction: column; align-items: center; gap: var(--ch-space-4); padding: var(--ch-space-12); color: var(--ch-color-text-secondary); }
.form-section { padding: var(--ch-space-6); }
.section-title { font-size: var(--ch-font-size-lg); font-weight: var(--ch-font-weight-semibold); margin-bottom: var(--ch-space-4); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--ch-space-4); }
.form-actions { display: flex; justify-content: flex-end; gap: var(--ch-space-3); padding: var(--ch-space-6); border-top: 1px solid var(--ch-color-border); }
</style>
