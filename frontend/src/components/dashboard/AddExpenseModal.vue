<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useExpensesStore } from '@/stores/expenses'
import { useLookupsStore } from '@/stores/lookups'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/components/ui/toast/use-toast'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Loader2 } from 'lucide-vue-next'
import dayjs from 'dayjs'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'success'): void
}>()

const store = useExpensesStore()
const lookups = useLookupsStore()
const auth = useAuthStore()
const { toast } = useToast()

const loading = ref(false)
const form = ref({
  title: '',
  amount: '',
  expense_date: dayjs().format('YYYY-MM-DD'),
  category_id: '',
  branch_id: '',
  fiscal_year_id: '',
  purpose: ''
})

// Initialize defaults from lookups
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    if (lookups.fiscalYears.length > 0) {
      const active = lookups.fiscalYears.find(fy => fy.Status === 'Active')
      form.value.fiscal_year_id = active ? active.id.toString() : ''
    }
  }
})

async function handleSubmit() {
  if (!form.value.title || !form.value.amount || !form.value.category_id) {
    toast({ title: 'Validation Error', description: 'Please fill in all required fields.', variant: 'destructive' })
    return
  }

  loading.value = true
  try {
    await store.createExpense({
      ...form.value,
      amount: Number(form.value.amount),
      category_id: Number(form.value.category_id),
      branch_id: form.value.branch_id ? Number(form.value.branch_id) : null,
      fiscal_year_id: form.value.fiscal_year_id ? Number(form.value.fiscal_year_id) : null
    })
    
    toast({ title: 'Success', description: 'Expense request submitted successfully.' })
    emit('success')
    closeModal()
  } catch (error: any) {
    toast({ 
      title: 'Error', 
      description: error.response?.data?.message || 'Failed to submit expense request.', 
      variant: 'destructive' 
    })
  } finally {
    loading.value = false
  }
}

function closeModal() {
  emit('update:open', false)
  // Reset form
  form.value = {
    title: '',
    amount: '',
    expense_date: dayjs().format('YYYY-MM-DD'),
    category_id: '',
    branch_id: '',
    fiscal_year_id: '',
    purpose: ''
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>New Expense Request</DialogTitle>
        <DialogDescription>
          Submit a new expense for approval. All fields marked with * are required.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4 py-4">
        <div class="grid gap-2">
          <Label for="title">Expense Title *</Label>
          <Input id="title" v-model="form.title" placeholder="e.g., Office Supplies" required />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="grid gap-2">
            <Label for="amount">Amount ({{ auth.currencySymbol }}) *</Label>
            <Input id="amount" type="number" step="0.01" v-model="form.amount" placeholder="0.00" required />
          </div>
          <div class="grid gap-2">
            <Label for="date">Expense Date *</Label>
            <Input id="date" type="date" v-model="form.expense_date" required />
          </div>
        </div>

        <div class="grid gap-2">
          <Label for="category">Category *</Label>
          <Select v-model="form.category_id">
            <SelectTrigger id="category">
              <SelectValue placeholder="Select category" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="cat in lookups.expenseCategories" :key="cat.id" :value="cat.id.toString()">
                {{ cat.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="grid gap-2">
            <Label for="branch">Branch</Label>
            <Select v-model="form.branch_id">
              <SelectTrigger id="branch">
                <SelectValue placeholder="Select branch" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="branch in lookups.branches" :key="branch.id" :value="branch.id.toString()">
                  {{ branch.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="grid gap-2">
            <Label for="fy">Fiscal Year</Label>
            <Select v-model="form.fiscal_year_id">
              <SelectTrigger id="fy">
                <SelectValue placeholder="Select year" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="fy in lookups.fiscalYears" :key="fy.id" :value="fy.id.toString()">
                  {{ fy.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="grid gap-2">
          <Label for="purpose">Purpose / Description</Label>
          <Textarea id="purpose" v-model="form.purpose" placeholder="Provide more details about this expense..." />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="closeModal" :disabled="loading">Cancel</Button>
          <Button type="submit" :disabled="loading">
            <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
            Submit Request
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
