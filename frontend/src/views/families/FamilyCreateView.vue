<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { familyService } from '@/services/family.service'
import { memberService } from '@/services/member.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { FamilyCreateInput } from '@/types'

const router = useRouter()
const toast = useToast()

// State
const saving = ref(false)
const form = ref<FamilyCreateInput>({
  family_name: '',
  family_head_id: undefined,
  address: '',
  city: '',
  region: '',
  country: '',
  home_phone: '',
})
const memberSearch = ref('')
const memberResults = ref<Array<{ MbrID: number; FullName: string }>>([])
const selectedHeadName = ref('')

// Methods
async function searchMembers(query: string) {
  if (query.length < 2) {
    memberResults.value = []
    return
  }

  try {
    const response = await memberService.search(query)
    if (response.status === 'success') {
      memberResults.value = response.data || []
    }
  } catch {
    memberResults.value = []
  }
}

function selectMemberHead(member: { MbrID: number; FullName: string }) {
  form.value.family_head_id = member.MbrID
  selectedHeadName.value = member.FullName
  memberSearch.value = ''
  memberResults.value = []
}

function clearFamilyHead() {
  form.value.family_head_id = undefined
  selectedHeadName.value = ''
}

async function handleSubmit() {
  if (!form.value.family_name) {
    toast.error('Family name is required')
    return
  }

  saving.value = true
  try {
    const response = await familyService.create(form.value)

    if (response.status === 'success') {
      toast.success('Family created successfully')
      router.push('/families')
    }
  } catch {
    toast.error('Failed to create family')
  } finally {
    saving.value = false
  }
}

function handleCancel() {
  router.push('/families')
}
</script>

<template>
  <div class="family-create">
    <ChPageHeader title="Create Family">
      <template #leading>
        <ChBreadcrumb
          :items="[
            { label: 'Families', to: '/families' },
            { label: 'Create Family' },
          ]"
        />
      </template>
    </ChPageHeader>

    <ChCard>

      <form @submit.prevent="handleSubmit">
        <div class="form-section">
          <h2 class="section-title">Basic Information</h2>

          <ChFormField label="Family Name *" required>
            <ChInput
              v-model="form.family_name"
              placeholder="e.g., The Johnson Family"
              required
            />
          </ChFormField>

          <ChFormField label="Family Head">
            <div v-if="selectedHeadName" class="selected-head">
              <ChBadge :value="selectedHeadName" variant="primary" />
              <ChButton
                variant="ghost"
                size="sm"
                left-icon="x"
                @click="clearFamilyHead"
              />
            </div>
            <div v-else class="member-search">
              <ChInput
                v-model="memberSearch"
                placeholder="Search for a member..."
                left-icon="search"
                @input="searchMembers($event.target.value)"
              />
              <div v-if="memberResults.length > 0" class="search-results">
                <div
                  v-for="member in memberResults"
                  :key="member.MbrID"
                  class="search-result-item"
                  @click="selectMemberHead(member)"
                >
                  <span class="member-name">{{ member.FullName }}</span>
                </div>
              </div>
            </div>
          </ChFormField>
        </div>

        <ChDivider />

        <div class="form-section">
          <h2 class="section-title">Address Information</h2>

          <ChFormField label="Address">
            <ChTextarea
              v-model="form.address"
              placeholder="Enter street address"
              :rows="3"
            />
          </ChFormField>

          <div class="form-row">
            <ChFormField label="City">
              <ChInput
                v-model="form.city"
                placeholder="City"
              />
            </ChFormField>

            <ChFormField label="Region/State">
              <ChInput
                v-model="form.region"
                placeholder="Region or State"
              />
            </ChFormField>
          </div>

          <ChFormField label="Country">
            <ChInput
              v-model="form.country"
              placeholder="Country"
            />
          </ChFormField>

          <ChFormField label="Home Phone">
            <ChInput
              v-model="form.home_phone"
              placeholder="Home phone number"
              type="tel"
            />
          </ChFormField>
        </div>

        <div class="form-actions">
          <ChButton
            variant="secondary"
            @click="handleCancel"
          >
            Cancel
          </ChButton>
          <ChButton
            type="submit"
            variant="primary"
            :loading="saving"
            left-icon="save"
          >
            Create Family
          </ChButton>
        </div>
      </form>
    </ChCard>
  </div>
</template>

<style scoped>
.family-create {
  padding: var(--ch-space-6);
  max-width: 800px;
  margin: 0 auto;
}

.page-header {
  padding: var(--ch-space-6);
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
}

.page-title {
  font-size: var(--ch-font-size-xl);
  font-weight: var(--ch-font-weight-semibold);
  color: var(--ch-color-text);
  margin: 0;
}

.form-section {
  padding: var(--ch-space-6);
}

.section-title {
  font-size: var(--ch-font-size-lg);
  font-weight: var(--ch-font-weight-semibold);
  color: var(--ch-color-text);
  margin-bottom: var(--ch-space-4);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-4);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--ch-space-3);
  padding: var(--ch-space-6);
  border-top: 1px solid var(--ch-color-border);
}

.selected-head {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.member-search {
  position: relative;
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--ch-color-bg);
  border: 1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-md);
  box-shadow: var(--ch-shadow-lg);
  z-index: 10;
  max-height: 200px;
  overflow-y: auto;
}

.search-result-item {
  padding: var(--ch-space-3) var(--ch-space-4);
  cursor: pointer;
  transition: background-color 0.2s;
}

.search-result-item:hover {
  background-color: var(--ch-color-bg-hover);
}

.member-name {
  font-weight: var(--ch-font-weight-medium);
}
</style>
