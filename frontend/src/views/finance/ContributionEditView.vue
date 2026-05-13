<script setup lang="ts">
/**
 * ContributionEditView — Edit an existing contribution.
 */
import { ref, reactive, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { contributionService, lookupService } from '@/services/finance.service'
import type { FinanceLookupData } from '@/services/finance.service'
import { memberService } from '@/services/member.service'
import { useToast } from '@/design-system'
import type { Contribution, ContributionUpdate } from '@/types/finance'
import { ArrowLeft, Save } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const contributionId = Number(route.params.id)

// ── State ─────────────────────────────────────────────────────────────────────

const loading = ref(true)
const isSubmitting = ref(false)
const contribution = ref<Contribution | null>(null)
const lookupData = ref<FinanceLookupData | null>(null)
const memberOptions = ref<Array<{ value: number; label: string }>>([])

// ── Form ──────────────────────────────────────────────────────────────────────

const form = reactive<ContributionUpdate>({
  member_id: 0,
  contribution_type_id: 0,
  amount: 0,
  date: '',
  payment_method_id: undefined,
  fiscal_year_id: undefined,
  description: '',
  branch_id: undefined,
})

/** Date picker binding */
const contributionDate = ref<Date>(new Date())

watch(contributionDate, (d) => {
  form.date = d ? d.toISOString().slice(0, 10) : ''
})

// ── Data loaders ──────────────────────────────────────────────────────────────

async function loadData() {
  loading.value = true
  try {
    const [contribRes, lookupRes, membersRes] = await Promise.all([
      contributionService.getById(contributionId),
      lookupService.getAll(),
      memberService.getAll(1, 100),
    ])

    // Load contribution data
    if (contribRes?.data?.data) {
      contribution.value = contribRes.data.data
      // Populate form
      form.member_id = contribRes.data.data.MbrID
      form.contribution_type_id = contribRes.data.data.ContributionTypeID
      form.amount = contribRes.data.data.ContributionAmount
      form.date = contribRes.data.data.ContributionDate
      form.payment_method_id = contribRes.data.data.PaymentMethodID || undefined
      form.fiscal_year_id = contribRes.data.data.FiscalYearID || undefined
      form.description = contribRes.data.data.PaymentReference || contribRes.data.data.Notes || ''
      form.branch_id = contribRes.data.data.BranchID || undefined
      
      // Set date picker
      contributionDate.value = new Date(contribRes.data.data.ContributionDate)
    } else {
      toast.error('Contribution not found')
      router.push('/finance/contributions')
      return
    }

    // Load lookup data
    if (lookupRes?.data?.data) {
      lookupData.value = lookupRes.data.data as FinanceLookupData
    }

    // Load member options
    const mems = membersRes?.data?.data ?? []
    memberOptions.value = mems.map((m) => ({
      value: m.MbrID,
      label: `${m.MbrFirstName} ${m.MbrFamilyName}`,
    }))
  } catch {
    toast.error('Failed to load form data')
    router.push('/finance/contributions')
  } finally {
    loading.value = false
  }
}

// ── Submit ────────────────────────────────────────────────────────────────────

async function handleSubmit() {
  if (!form.member_id || !form.contribution_type_id || !form.amount) {
    toast.warning('Please fill in all required fields.')
    return
  }

  isSubmitting.value = true
  try {
    const payload: ContributionUpdate = {
      member_id: form.member_id,
      contribution_type_id: form.contribution_type_id,
      amount: Number(form.amount),
      date: form.date,
      payment_method_id: form.payment_method_id || undefined,
      fiscal_year_id: form.fiscal_year_id || undefined,
      description: form.description || undefined,
      branch_id: form.branch_id,
    }
    
    await contributionService.update(contributionId, payload)
    toast.success('Contribution updated successfully.')
    router.push(`/finance/contributions/${contributionId}`)
  } catch (err: unknown) {
    const msg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
      'Failed to update contribution.'
    toast.error(msg)
  } finally {
    isSubmitting.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="view">
    <ChPageHeader title="Edit Contribution">
      <template #leading>
        <ChButton variant="ghost" size="sm" @click="router.push(`/finance/contributions/${contributionId}`)">
          <template #icon><ArrowLeft :size="16" /></template>
          Back to Contribution
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="loading" class="loading-wrap">
      <ChSpinner size="lg" />
      <span>Loading contribution data...</span>
    </div>

    <!-- ── Form Card ──────────────────────────────────────────────────────── -->
    <ChCard v-else shadow="sm">
      <form @submit.prevent="handleSubmit">
        <!-- ── Section: Contribution Details ─────────────────────────────── -->
        <div class="form-section">
          <h2 class="form-section__title">Contribution Details</h2>

          <div class="form-grid">
            <!-- Member -->
            <ChFormField
              label="Member"
              input-id="member-select"
              :required="true"
              class="form-grid__full"
            >
              <ChSelect
                id="member-select"
                v-model="form.member_id"
                :options="memberOptions"
                placeholder="Select a member…"
                :searchable="true"
                empty-message="No members found."
              />
            </ChFormField>

            <!-- Contribution Type -->
            <ChFormField label="Contribution Type" input-id="type-select" :required="true">
              <ChSelect
                id="type-select"
                v-model="form.contribution_type_id"
                :options="
                  (lookupData?.contribution_types ?? []).map((t) => ({
                    value: t.id,
                    label: t.name,
                  }))
                "
                placeholder="Select type…"
              />
            </ChFormField>

            <!-- Amount -->
            <ChFormField label="Amount (GH₵)" input-id="amount-input" :required="true">
              <ChInput
                id="amount-input"
                v-model="form.amount"
                type="number"
                step="0.01"
                placeholder="0.00"
              />
            </ChFormField>

            <!-- Contribution Date -->
            <ChFormField label="Contribution Date" :required="true">
              <ChDatePicker
                v-model="contributionDate"
                placeholder="Select date"
                display-format="dd/mm/yyyy"
              />
            </ChFormField>
          </div>
        </div>

        <ChDivider />

        <!-- ── Section: Payment Details ──────────────────────────────────── -->
        <div class="form-section">
          <h2 class="form-section__title">Payment Details</h2>

          <div class="form-grid">
            <!-- Payment Method -->
            <ChFormField label="Payment Method" input-id="method-select">
              <ChSelect
                id="method-select"
                v-model="form.payment_method_id"
                :options="
                  (lookupData?.payment_methods ?? []).map((m) => ({
                    value: m.id,
                    label: m.name,
                  }))
                "
                placeholder="Select method…"
              />
            </ChFormField>

            <!-- Fiscal Year -->
            <ChFormField label="Fiscal Year" input-id="fiscal-select">
              <ChSelect
                id="fiscal-select"
                v-model="form.fiscal_year_id"
                :options="
                  (lookupData?.fiscal_years ?? [])
                    .filter((fy) => fy.Status === 'Active' || fy.Status === 'Planned')
                    .map((fy) => ({ value: fy.id, label: fy.name }))
                "
                placeholder="Select fiscal year…"
              />
            </ChFormField>

            <!-- Branch -->
            <ChFormField label="Branch" input-id="branch-select">
              <ChSelect
                id="branch-select"
                v-model="form.branch_id"
                :options="lookupData?.branches?.map((b) => ({ value: b.id, label: b.name })) ?? []"
                placeholder="Select branch (optional)"
              />
            </ChFormField>
          </div>
        </div>

        <ChDivider />

        <!-- ── Section: Description ─────────────────────────────────────────────── -->
        <div class="form-section">
          <h2 class="form-section__title">Description / Notes</h2>

          <ChFormField
            label="Description"
            input-id="description-input"
            class="form-grid__full"
          >
            <ChTextarea
              id="description-input"
              v-model="form.description"
              placeholder="Additional information about this contribution..."
              :rows="3"
            />
          </ChFormField>
        </div>

        <!-- ── Footer Actions ─────────────────────────────────────────────── -->
        <div class="form-footer">
          <ChButton type="button" variant="ghost" @click="router.push(`/finance/contributions/${contributionId}`)">
            Cancel
          </ChButton>
          <ChButton type="submit" variant="primary" :loading="isSubmitting">
            <template #icon><Save :size="16" /></template>
            Save Changes
          </ChButton>
        </div>
      </form>
    </ChCard>
  </div>
</template>

<style scoped>
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-5);
  max-width: 860px;
  margin: 0 auto;
  padding-bottom: var(--ch-space-8);
}

.loading-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-4);
  padding: var(--ch-space-12) 0;
  color: var(--ch-color-text-muted);
}

.form-section {
  padding: var(--ch-space-5) 0;
}

.form-section:first-child {
  padding-top: 0;
}

.form-section__title {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin: 0 0 var(--ch-space-4);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-4);
}

.form-grid__full {
  grid-column: 1 / -1;
}

@media (max-width: 600px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

.form-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--ch-space-3);
  padding-top: var(--ch-space-5);
  border-top: 1px solid var(--ch-color-border);
}
</style>
