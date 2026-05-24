<script setup lang="ts">
/**
 * ExpenseCreateView — Full implementation.
 * Provides a form to record a new expense.
 */
import { ref, reactive, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { expenseService } from '@/services/expense.service'
import { lookupService } from '@/services/finance.service'
import type { FinanceLookupData } from '@/services/finance.service'
import { useToast } from '@/design-system'
import type { ExpenseCreateInput, ExpenseCategory } from '@/types/finance'
import { ArrowLeft, DollarSign } from '@lucide/vue'

const router = useRouter()
const toast = useToast()

// ── State ─────────────────────────────────────────────────────────────────────

const lookupData = ref<FinanceLookupData | null>(null)
const categories = ref<ExpenseCategory[]>([])
const isLoading = ref(true)
const isSubmitting = ref(false)

// ── Form ──────────────────────────────────────────────────────────────────────

const form = reactive<ExpenseCreateInput>({
  category_id: 0,
  fiscal_year_id: undefined,
  amount: 0,
  expense_date: new Date().toISOString().slice(0, 10),
  title: '',
  purpose: '',
  branch_id: undefined,
})

/** Date picker binding — kept in sync with form.expense_date */
const expenseDate = ref<Date>(new Date())

watch(expenseDate, (d) => {
  form.expense_date = d ? d.toISOString().slice(0, 10) : ''
})

// ── Submit ────────────────────────────────────────────────────────────────────

async function handleSubmit() {
  if (!form.category_id || !form.amount || !form.title) {
    toast.warning('Please fill in all required fields.')
    return
  }

  isSubmitting.value = true
  try {
    const payload: ExpenseCreateInput = {
      category_id: form.category_id,
      amount: Number(form.amount),
      expense_date: form.expense_date,
      title: form.title,
      purpose: form.purpose || undefined,
      fiscal_year_id: form.fiscal_year_id || undefined,
      branch_id: form.branch_id || undefined,
    }
    await expenseService.create(payload)
    toast.success('Expense recorded successfully.')
    router.push('/finance/expenses')
  } catch (err: unknown) {
    const msg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
      'Failed to record expense.'
    toast.error(msg)
  } finally {
    isSubmitting.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(async () => {
  isLoading.value = true
  try {
    // Load lookup data and categories in parallel
    const [lookupRes, categoriesRes] = await Promise.all([
      lookupService.getAll(),
      expenseService.listCategories(),
    ])

    lookupData.value = lookupRes?.data?.data as FinanceLookupData

    // Pre-select the active fiscal year
    const currentFY = lookupData.value?.fiscal_years?.find((fy) => fy.Status === 'Active')
    if (currentFY) form.fiscal_year_id = currentFY.id

    // Populate categories
    categories.value = categoriesRes?.data || []
  } catch {
    toast.error('Failed to load form data.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="view">
    <ChPageHeader title="Record Expense">
      <template #leading>
        <ChButton variant="ghost" size="sm" @click="router.push('/finance/expenses')">
          <template #icon><ArrowLeft :size="16" /></template>
          Expenses
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="isLoading" class="loading-wrap">
      <ChSpinner />
    </div>

    <!-- ── Form Card ──────────────────────────────────────────────────────── -->
    <ChCard v-else shadow="sm">
      <form @submit.prevent="handleSubmit">
        <!-- ── Section: Expense Details ─────────────────────────────── -->
        <div class="form-section">
          <h2 class="form-section__title">Expense Details</h2>

          <div class="form-grid">
            <!-- Title -->
            <ChFormField
              label="Title"
              input-id="title-input"
              :required="true"
            >
              <ChInput
                id="title-input"
                v-model="form.title"
                placeholder="e.g. Office Supplies, Vehicle Maintenance"
              />
            </ChFormField>

            <!-- Category -->
            <ChFormField
              label="Category"
              input-id="category-select"
              :required="true"
            >
              <ChSelect
                id="category-select"
                v-model="form.category_id"
                :options="
                  categories.map((c) => ({
                    value: c.CategoryID,
                    label: c.CategoryName,
                  }))
                "
                placeholder="Select category…"
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

            <!-- Expense Date -->
            <ChFormField label="Expense Date" :required="true">
              <ChDatePicker
                v-model="expenseDate"
                placeholder="Select date"
                display-format="dd/mm/yyyy"
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

            <!-- Purpose -->
            <ChFormField
              label="Purpose / Description"
              input-id="purpose-input"
              class="form-grid__full"
            >
              <ChTextarea
                id="purpose-input"
                v-model="form.purpose"
                placeholder="Additional information about this expense..."
                :rows="3"
                resize="vertical"
              />
            </ChFormField>
          </div>
        </div>

        <ChDivider />

        <!-- ── Footer Actions ─────────────────────────────────────────────── -->
        <div class="form-footer">
          <ChButton type="button" variant="ghost" @click="router.push('/finance/expenses')">
            Cancel
          </ChButton>
          <ChButton type="submit" variant="primary" :loading="isSubmitting">
            <template #icon><DollarSign :size="16" /></template>
            Record Expense
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
}

.loading-wrap {
  display: flex;
  justify-content: center;
  padding: var(--ch-space-12) 0;
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
}
</style>
