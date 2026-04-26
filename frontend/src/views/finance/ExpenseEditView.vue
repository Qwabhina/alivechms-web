<script setup lang="ts">
/**
 * ExpenseEditView — Edit an existing expense.
 */
import { ref, reactive, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { expenseService } from '@/services/expense.service'
import { lookupService } from '@/services/finance.service'
import type { FinanceLookupData } from '@/services/finance.service'
import { useToast } from '@/design-system'
import type { ExpenseDetail, ExpenseUpdateInput, ExpenseCategory } from '@/types/finance'
import { ArrowLeft, Save } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const expenseId = Number(route.params.id)

// ── State ─────────────────────────────────────────────────────────────────────

const loading = ref(true)
const isSubmitting = ref(false)
const expense = ref<ExpenseDetail | null>(null)
const lookupData = ref<FinanceLookupData | null>(null)
const categories = ref<ExpenseCategory[]>([])

// ── Form ──────────────────────────────────────────────────────────────────────

const form = reactive<ExpenseUpdateInput>({
  expense_id: expenseId,
  expense_category_id: 0,
  fiscal_year_id: undefined,
  amount: 0,
  expense_date: '',
  description: '',
  branch_id: undefined,
})

/** Date picker binding */
const expenseDate = ref<Date>(new Date())

watch(expenseDate, (d) => {
  form.expense_date = d ? d.toISOString().slice(0, 10) : ''
})

// ── Data loaders ──────────────────────────────────────────────────────────────

async function loadData() {
  loading.value = true
  try {
    const [expenseRes, lookupRes, categoriesRes] = await Promise.all([
      expenseService.get(expenseId),
      lookupService.getAll(),
      expenseService.listCategories(),
    ])

    // Load expense data
    if (expenseRes?.data) {
      expense.value = expenseRes.data
      // Populate form
      form.expense_category_id = expenseRes.data.ExpenseCategoryID
      form.amount = expenseRes.data.Amount
      form.expense_date = expenseRes.data.ExpenseDate
      form.description = expenseRes.data.Description || ''
      form.fiscal_year_id = expenseRes.data.FiscalYearID || undefined
      
      // Set date picker
      expenseDate.value = new Date(expenseRes.data.ExpenseDate)
    } else {
      toast.error('Expense not found')
      router.push('/finance/expenses')
      return
    }

    // Load lookup data
    if (lookupRes?.data?.data) {
      lookupData.value = lookupRes.data.data as FinanceLookupData
    }

    // Load categories
    categories.value = categoriesRes?.data || []
  } catch {
    toast.error('Failed to load form data')
    router.push('/finance/expenses')
  } finally {
    loading.value = false
  }
}

// ── Submit ────────────────────────────────────────────────────────────────────

async function handleSubmit() {
  if (!form.expense_category_id || !form.amount || !form.description) {
    toast.warning('Please fill in all required fields.')
    return
  }

  isSubmitting.value = true
  try {
    const payload: ExpenseUpdateInput = {
      expense_id: expenseId,
      expense_category_id: form.expense_category_id,
      amount: Number(form.amount),
      expense_date: form.expense_date,
      description: form.description,
      fiscal_year_id: form.fiscal_year_id || undefined,
      branch_id: form.branch_id || undefined,
    }
    
    await expenseService.update(expenseId, payload)
    toast.success('Expense updated successfully.')
    router.push(`/finance/expenses/${expenseId}`)
  } catch (err: unknown) {
    const msg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
      'Failed to update expense.'
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
    <ChPageHeader title="Edit Expense">
      <template #leading>
        <ChButton variant="ghost" size="sm" @click="router.push(`/finance/expenses/${expenseId}`)">
          <template #icon><ArrowLeft :size="16" /></template>
          Back to Expense
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="loading" class="loading-wrap">
      <ChSpinner size="lg" />
      <span>Loading expense data...</span>
    </div>

    <!-- ── Form Card ──────────────────────────────────────────────────────── -->
    <ChCard v-else shadow="sm">
      <form @submit.prevent="handleSubmit">
        <!-- ── Section: Expense Details ─────────────────────────────── -->
        <div class="form-section">
          <h2 class="form-section__title">Expense Details</h2>

          <div class="form-grid">
            <!-- Category -->
            <ChFormField
              label="Category"
              input-id="category-select"
              :required="true"
            >
              <ChSelect
                id="category-select"
                v-model="form.expense_category_id"
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

            <!-- Description -->
            <ChFormField
              label="Description"
              input-id="description-input"
              :required="true"
              class="form-grid__full"
            >
              <ChTextarea
                id="description-input"
                v-model="form.description"
                placeholder="Describe the expense..."
                :rows="3"
                resize="vertical"
              />
            </ChFormField>
          </div>
        </div>

        <ChDivider />

        <!-- ── Footer Actions ─────────────────────────────────────────────── -->
        <div class="form-footer">
          <ChButton type="button" variant="ghost" @click="router.push(`/finance/expenses/${expenseId}`)">
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
}
</style>
