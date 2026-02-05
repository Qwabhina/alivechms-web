<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useBudgetsStore } from '@/stores/budgets'
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
import { 
  Loader2, 
  Trash2, 
  Plus, 
  PieChart,
  ArrowUpCircle,
  ArrowDownCircle
} from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'success'): void
}>()

const store = useBudgetsStore()
const lookups = useLookupsStore()
const auth = useAuthStore()
const { toast } = useToast()

const loading = ref(false)

interface BudgetItemForm {
  category: string
  amount: string
  type: 'Income' | 'Expense'
  subcategory_id: string
}

const form = ref({
  fiscal_year_id: '',
  branch_id: '',
  title: '',
  description: '',
  items: [
    { category: '', amount: '', type: 'Expense', subcategory_id: '1' }
  ] as BudgetItemForm[]
})

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    if (lookups.fiscalYears.length > 0) {
      const active = lookups.fiscalYears.find(fy => fy.Status === 'Active')
      form.value.fiscal_year_id = active ? active.id.toString() : ''
    }
    if (lookups.branches.length > 0) {
       form.value.branch_id = lookups.branches[0].id.toString()
    }
  }
})

const totalAmount = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (Number(item.amount) || 0), 0)
})

function addItem() {
  form.value.items.push({
    category: '',
    amount: '',
    type: 'Expense',
    subcategory_id: '1'
  })
}

function removeItem(index: number) {
  if (form.value.items.length > 1) {
    form.value.items.splice(index, 1)
  }
}

async function handleSubmit() {
  if (!form.value.fiscal_year_id || !form.value.branch_id || !form.value.title) {
    toast({ title: 'Validation Error', description: 'Please fill in basic info.', variant: 'destructive' })
    return
  }

  const validItems = form.value.items.filter(item => item.category && Number(item.amount) > 0)
  if (validItems.length === 0) {
    toast({ title: 'Validation Error', description: 'At least one valid item is required.', variant: 'destructive' })
    return
  }

  loading.value = true
  try {
    await store.createBudget({
      ...form.value,
      fiscal_year_id: Number(form.value.fiscal_year_id),
      branch_id: Number(form.value.branch_id),
      items: validItems.map(item => ({
        ...item,
        amount: Number(item.amount),
        subcategory_id: Number(item.subcategory_id)
      }))
    })
    
    toast({ title: 'Success', description: 'Budget created successfully.' })
    emit('success')
    closeModal()
  } catch (error: any) {
    toast({ 
      title: 'Error', 
      description: error.response?.data?.message || 'Failed to create budget.', 
      variant: 'destructive' 
    })
  } finally {
    loading.value = false
  }
}

function closeModal() {
  emit('update:open', false)
  form.value = {
    fiscal_year_id: form.value.fiscal_year_id,
    branch_id: form.value.branch_id,
    title: '',
    description: '',
    items: [{ category: '', amount: '', type: 'Expense', subcategory_id: '1' }]
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <PieChart class="w-5 h-5 text-primary" />
          Create New Budget
        </DialogTitle>
        <DialogDescription>
          Define financial targets and allocations for a specific fiscal year and branch.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-6 py-4">
        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
           <div class="space-y-4">
              <div class="grid gap-2">
                <Label for="title">Budget Title *</Label>
                <Input id="title" v-model="form.title" placeholder="e.g. Annual Operations 2026" required />
              </div>
              <div class="grid gap-2">
                <Label for="description">Summary/Purpose</Label>
                <Textarea id="description" v-model="form.description" placeholder="Brief overview of this budget plan..." rows="2" />
              </div>
           </div>
           <div class="space-y-4">
              <div class="grid gap-2">
                <Label for="fy">Fiscal Year *</Label>
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
              <div class="grid gap-2">
                <Label for="branch">Branch *</Label>
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
           </div>
        </div>

        <div class="space-y-3">
           <div class="flex items-center justify-between">
              <h4 class="text-sm font-bold flex items-center gap-2">
                 Budget Items 
                 <Badge variant="outline">{{ form.items.length }}</Badge>
              </h4>
              <Button type="button" variant="outline" size="sm" @click="addItem">
                 <Plus class="w-4 h-4 mr-2" /> Add Item
              </Button>
           </div>

           <div class="space-y-3">
              <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-3 p-3 bg-muted/30 rounded-lg border items-end group">
                 <div class="col-span-12 md:col-span-4 grid gap-2">
                    <Label class="text-[10px] uppercase font-bold text-muted-foreground">Category/Item Name *</Label>
                    <Input v-model="item.category" placeholder="e.g. Utility Bills" required />
                 </div>
                 <div class="col-span-6 md:col-span-3 grid gap-2">
                    <Label class="text-[10px] uppercase font-bold text-muted-foreground">Type</Label>
                    <Select v-model="item.type">
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="Expense">
                           <div class="flex items-center gap-2 text-red-600">
                              <ArrowDownCircle class="w-3 h-3" /> Expense
                           </div>
                        </SelectItem>
                        <SelectItem value="Income">
                           <div class="flex items-center gap-2 text-green-600">
                              <ArrowUpCircle class="w-3 h-3" /> Income
                           </div>
                        </SelectItem>
                      </SelectContent>
                    </Select>
                 </div>
                 <div class="col-span-6 md:col-span-4 grid gap-2">
                    <Label class="text-[10px] uppercase font-bold text-muted-foreground">Estimated Amount *</Label>
                    <div class="relative">
                       <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">{{ auth.currencySymbol }}</span>
                       <Input v-model="item.amount" type="number" step="0.01" class="pl-8" placeholder="0.00" required />
                    </div>
                 </div>
                 <div class="col-span-12 md:col-span-1 flex justify-end">
                    <Button type="button" variant="ghost" size="icon" class="text-destructive opacity-0 group-hover:opacity-100 transition-opacity" @click="removeItem(index)" :disabled="form.items.length === 1">
                       <Trash2 class="w-4 h-4" />
                    </Button>
                 </div>
              </div>
           </div>
        </div>

        <DialogFooter class="flex flex-col sm:flex-row items-center gap-4 sm:justify-between pt-4 border-t">
           <div class="text-sm font-bold flex items-center gap-2">
              <span class="text-muted-foreground">Total Budgeted:</span>
              <span class="text-xl text-primary">{{ auth.currencySymbol }}{{ totalAmount.toLocaleString() }}</span>
           </div>
           <div class="flex items-center gap-2">
              <Button type="button" variant="outline" @click="closeModal" :disabled="loading">Cancel</Button>
              <Button type="submit" :disabled="loading">
                <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
                Create Budget Plan
              </Button>
           </div>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
