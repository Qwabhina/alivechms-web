<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { groupService } from '@/services/group.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { GroupCreateInput } from '@/types'

const router = useRouter()
const toast = useToast()

const saving = ref(false)
const form = ref<GroupCreateInput>({
  group_name: '',
  group_type_id: undefined,
  description: '',
  leader_id: undefined,
  co_leader_id: undefined,
  meeting_day: '',
  meeting_time: '',
  meeting_location: '',
  max_members: undefined,
})

async function handleSubmit() {
  if (!form.value.group_name) {
    toast.error('Group name is required')
    return
  }

  saving.value = true
  try {
    const response = await groupService.create(form.value)
    if (response.status === 'success') {
      toast.success('Group created successfully')
      router.push('/groups')
    }
  } catch {
    toast.error('Failed to create group')
  } finally {
    saving.value = false
  }
}

function handleCancel() {
  router.push('/groups')
}
</script>

<template>
  <div class="group-create">
    <ChPageHeader title="Create Group">
      <template #leading>
        <ChBreadcrumb
          :items="[
            { label: 'Groups', to: '/groups' },
            { label: 'Create Group' },
          ]"
        />
      </template>
    </ChPageHeader>

    <ChCard>

      <form @submit.prevent="handleSubmit">
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
              <ChSelect v-model="form.meeting_day" :options="[
                { value: '', label: 'Select day' },
                { value: 'Sunday', label: 'Sunday' },
                { value: 'Monday', label: 'Monday' },
                { value: 'Tuesday', label: 'Tuesday' },
                { value: 'Wednesday', label: 'Wednesday' },
                { value: 'Thursday', label: 'Thursday' },
                { value: 'Friday', label: 'Friday' },
                { value: 'Saturday', label: 'Saturday' },
              ]" />
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
          <ChButton type="submit" variant="primary" :loading="saving" left-icon="save">Create Group</ChButton>
        </div>
      </form>
    </ChCard>
  </div>
</template>

<style scoped>
.group-create { padding: var(--ch-space-6); max-width: 800px; margin: 0 auto; }
.page-header { padding: var(--ch-space-6); }
.page-title { font-size: var(--ch-font-size-xl); font-weight: var(--ch-font-weight-semibold); margin: var(--ch-space-2) 0 0; }
.form-section { padding: var(--ch-space-6); }
.section-title { font-size: var(--ch-font-size-lg); font-weight: var(--ch-font-weight-semibold); margin-bottom: var(--ch-space-4); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--ch-space-4); }
.form-actions { display: flex; justify-content: flex-end; gap: var(--ch-space-3); padding: var(--ch-space-6); border-top: 1px solid var(--ch-color-border); }
</style>
