<script setup lang="ts">
/**
 * ContributionCreateView — Full implementation.
 * Provides a form to record a new financial contribution for a member.
 */
import { useRouter } from 'vue-router'
import { contributionService, lookupService } from '@/services/finance.service'
import type { FinanceLookupData } from '@/services/finance.service'
import { memberService } from '@/services/member.service'
import { useToast } from '@/design-system'
import type { ContributionCreate } from '@/types/finance'
import { ArrowLeft, DollarSign } from 'lucide-vue-next'

const router = useRouter()
const toast = useToast()

// ── State ─────────────────────────────────────────────────────────────────────

const lookupData = ref<FinanceLookupData | null>(null)
const isLoading = ref(true)
const isSubmitting = ref(false)

// ── Member options ────────────────────────────────────────────────────────────

const memberOptions = ref<Array<{ value: number; label: string }>>([])

// ── Form ──────────────────────────────────────────────────────────────────────

const form = reactive<ContributionCreate>({
  member_id: 0,
  contribution_type_id: 0,
  amount: 0,
  contribution_date: new Date().toISOString().slice(0, 10),
  payment_method_id: undefined,
  fiscal_year_id: undefined,
  receipt_number: '',
  notes: '',
})

/** Date picker binding — kept in sync with form.contribution_date */
const contributionDate = ref<Date>(new Date())

watch(contributionDate, (d) => {
  form.contribution_date = d ? d.toISOString().slice(0, 10) : ''
})

// ── Submit ────────────────────────────────────────────────────────────────────

async function handleSubmit() {
  if (!form.member_id || !form.contribution_type_id || !form.amount) {
    toast.warning('Please fill in all required fields.')
    return
  }

  isSubmitting.value = true
  try {
    const payload: ContributionCreate = {
      member_id: form.member_id,
      contribution_type_id: form.contribution_type_id,
      amount: Number(form.amount),
      contribution_date: form.contribution_date,
      payment_method_id: form.payment_method_id || undefined,
      fiscal_year_id: form.fiscal_year_id || undefined,
      receipt_number: form.receipt_number || undefined,
      notes: form.notes || undefined,
    }
    await contributionService.create(payload)
    toast.success('Contribution recorded successfully.')
    router.push('/finance/contributions')
  } catch (err: unknown) {
    const msg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
      'Failed to record contribution.'
    toast.error(msg)
  } finally {
    isSubmitting.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(async () => {
  isLoading.value = true
  try {
    // Load lookup data and members in parallel
    const [lookupRes, membersRes] = await Promise.all([
      lookupService.getAll(),
      memberService.getAll(1, 100),
    ])

    lookupData.value = lookupRes?.data?.data as FinanceLookupData

    // Pre-select the active fiscal year
    const currentFY = lookupData.value?.fiscal_years?.find((fy) => fy.Status === 'Active')
    if (currentFY) form.fiscal_year_id = currentFY.id

    // Populate member select options
    const mems = membersRes?.data?.data ?? []
    memberOptions.value = mems.map((m) => ({
      value: m.MbrID,
      label: `${m.MbrFirstName} ${m.MbrFamilyName}`,
    }))
  } catch {
    toast.error('Failed to load form data.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="view">
    <!-- ── Header ──────────────────────────────────────────────────────────── -->
    <div class="view-header">
      <ChButton variant="ghost" size="sm" @click="router.push('/finance/contributions')">
        <template #icon><ArrowLeft :size="16" /></template>
        Contributions
      </ChButton>
      <h1 class="view-title">Record Contribution</h1>
    </div>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="isLoading" class="loading-wrap">
      <ChSpinner />
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
                v-model="(form as any).member_id"
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
                v-model="(form as any).contribution_type_id"
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
                v-model="(form as any).amount"
                type="number"
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
                v-model="(form as any).payment_method_id"
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
                v-model="(form as any).fiscal_year_id"
                :options="
                  (lookupData?.fiscal_years ?? [])
                    .filter((fy) => fy.Status === 'Active' || fy.Status === 'Planned')
                    .map((fy) => ({ value: fy.id, label: fy.name }))
                "
                placeholder="Select fiscal year…"
              />
            </ChFormField>

            <!-- Receipt Number -->
            <ChFormField
              label="Receipt Number"
              input-id="receipt-input"
              hint="Optional — leave blank to auto-generate."
              class="form-grid__full"
            >
              <ChInput
                id="receipt-input"
                v-model="form.receipt_number"
                placeholder="e.g. RCP-2024-001"
              />
            </ChFormField>
          </div>
        </div>

        <ChDivider />

        <!-- ── Section: Notes ─────────────────────────────────────────────── -->
        <div class="form-section">
          <h2 class="form-section__title">Notes</h2>

          <ChFormField
            label="Additional Notes"
            input-id="notes-input"
            hint="Any context about this contribution (e.g. special occasion, pledge reference)."
          >
            <ChTextarea
              id="notes-input"
              v-model="form.notes"
              placeholder="Add any relevant notes here…"
              :rows="3"
              resize="vertical"
            />
          </ChFormField>
        </div>

        <!-- ── Footer Actions ─────────────────────────────────────────────── -->
        <div class="form-footer">
          <ChButton type="button" variant="ghost" @click="router.push('/finance/contributions')">
            Cancel
          </ChButton>
          <ChButton type="submit" variant="primary" :loading="isSubmitting">
            <template #icon><DollarSign :size="16" /></template>
            Record Contribution
          </ChButton>
        </div>
      </form>
    </ChCard>
  </div>
</template>

<style scoped>
/* ─── Page shell ──────────────────────────────────────────────────────────── */
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-5);
  max-width: 720px;
}

/* ─── Header ──────────────────────────────────────────────────────────────── */
.view-header {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.view-title {
  font-size: var(--ch-text-xl);
  font-weight: var(--ch-font-bold);
  font-family: var(--ch-font-display);
  color: var(--ch-color-text);
  margin: 0;
}

/* ─── Loading ─────────────────────────────────────────────────────────────── */
.loading-wrap {
  display: flex;
  justify-content: center;
  padding: var(--ch-space-12) 0;
}

/* ─── Form section ────────────────────────────────────────────────────────── */
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

/* ─── Two-column grid ─────────────────────────────────────────────────────── */
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

/* ─── Footer actions ──────────────────────────────────────────────────────── */
.form-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--ch-space-3);
  padding-top: var(--ch-space-5);
  border-top: 1px solid var(--ch-color-border);
}
</style>
