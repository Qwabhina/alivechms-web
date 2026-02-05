<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useBudgetsStore } from '@/stores/budgets'
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
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { 
  Loader2, 
  PieChart, 
  Calendar, 
  Building,
  User,
  ShieldCheck,
  AlertCircle,
  ArrowUpCircle,
  ArrowDownCircle,
  FileText
} from 'lucide-vue-next'
import dayjs from 'dayjs'

const props = defineProps<{
  open: boolean
  id: number | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const store = useBudgetsStore()
const auth = useAuthStore()
const { toast } = useToast()

const loading = ref(false)
const budget = ref<any>(null)

watch(() => props.id, async (newId) => {
  if (newId && props.open) {
    await fetchDetails(newId)
  }
})

async function fetchDetails(id: number) {
  loading.value = true
  try {
    budget.value = await store.fetchBudgetById(id)
  } catch (error) {
    toast({ title: 'Error', description: 'Failed to fetch budget details', variant: 'destructive' })
    emit('update:open', false)
  } finally {
    loading.value = false
  }
}

const incomeItems = computed(() => budget.value?.items?.filter((i: any) => i.CategoryType === 'Income') || [])
const expenseItems = computed(() => budget.value?.items?.filter((i: any) => i.CategoryType === 'Expense') || [])

function getStatusBadge(status: string) {
  const map: Record<string, { variant: "default" | "destructive" | "outline" | "secondary" | null | undefined, class: string }> = {
    'Approved': { variant: 'default', class: 'bg-green-500' },
    'Submitted': { variant: 'outline', class: 'text-blue-600 border-blue-200 bg-blue-50' },
    'Rejected': { variant: 'destructive', class: '' },
    'Draft': { variant: 'secondary', class: 'opacity-70' }
  }
  return map[status] || { variant: 'outline', class: '' }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2 text-xl">
          <PieChart class="w-5 h-5 text-primary" />
          Budget Breakdown
        </DialogTitle>
        <DialogDescription>
          Detailed allocation of resources for this budget period.
        </DialogDescription>
      </DialogHeader>

      <div v-if="loading" class="flex justify-center py-12">
        <Loader2 class="w-8 h-8 animate-spin text-muted-foreground" />
      </div>

      <div v-else-if="budget" class="space-y-6">
        <!-- Header Card -->
        <div class="p-5 bg-muted/30 rounded-xl border space-y-4">
           <div class="flex flex-col md:flex-row justify-between items-start gap-4">
              <div>
                 <h3 class="text-2xl font-black text-primary">{{ budget.BudgetTitle }}</h3>
                 <div class="flex items-center gap-3 mt-1 text-sm text-muted-foreground">
                    <span class="flex items-center gap-1.5 font-medium"><Calendar class="w-3.5 h-3.5" /> {{ budget.FiscalYearName }}</span>
                    <span class="flex items-center gap-1.5 font-medium"><Building class="w-3.5 h-3.5" /> {{ budget.BranchName }}</span>
                 </div>
              </div>
              <Badge :variant="getStatusBadge(budget.BudgetStatus).variant" :class="`text-sm py-1 px-3 ${getStatusBadge(budget.BudgetStatus).class}`">
                 {{ budget.BudgetStatus }}
              </Badge>
           </div>
           
           <div v-if="budget.BudgetSummary" class="text-sm p-3 bg-white/50 rounded-lg italic text-muted-foreground border-l-4 border-primary/20">
              "{{ budget.BudgetSummary }}"
           </div>

           <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
              <div class="p-3 bg-white rounded-lg border shadow-sm">
                 <p class="text-[10px] uppercase font-bold text-muted-foreground mb-1">Total Allocated</p>
                 <p class="text-xl font-black">{{ auth.currencySymbol }}{{ budget.TotalAmount.toLocaleString() }}</p>
              </div>
              <div class="p-3 bg-white rounded-lg border shadow-sm">
                 <p class="text-[10px] uppercase font-bold text-muted-foreground mb-1">Line Items</p>
                 <p class="text-xl font-black">{{ budget.items?.length || 0 }}</p>
              </div>
              <div class="p-3 bg-white rounded-lg border shadow-sm col-span-2">
                 <p class="text-[10px] uppercase font-bold text-muted-foreground mb-1">Created By</p>
                 <p class="text-sm font-semibold flex items-center gap-2 mt-1">
                    <User class="w-4 h-4 text-primary" />
                    {{ budget.CreatorFirstName }} {{ budget.CreatorFamilyName }}
                 </p>
              </div>
           </div>
        </div>

        <!-- Budget Items -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
           <!-- Income Items -->
           <div class="space-y-3">
              <h4 class="text-sm font-bold flex items-center gap-2 text-green-600">
                 <ArrowUpCircle class="w-4 h-4" />
                 Income Projections
              </h4>
              <div class="border rounded-lg overflow-hidden">
                 <Table>
                    <TableHeader class="bg-green-50/50">
                       <TableRow>
                          <TableHead class="h-9 px-3 text-xs">Item</TableHead>
                          <TableHead class="h-9 px-3 text-xs text-right">Amount</TableHead>
                       </TableRow>
                    </TableHeader>
                    <TableBody>
                       <TableRow v-if="incomeItems.length === 0">
                          <TableCell colspan="2" class="h-16 text-center text-xs text-muted-foreground italic">No income projections.</TableCell>
                       </TableRow>
                       <TableRow v-for="item in incomeItems" :key="item.ItemID" class="h-9">
                          <TableCell class="px-3 py-2 text-xs font-medium">{{ item.ItemName }}</TableCell>
                          <TableCell class="px-3 py-2 text-xs text-right font-bold">{{ auth.currencySymbol }}{{ item.Amount.toLocaleString() }}</TableCell>
                       </TableRow>
                    </TableBody>
                 </Table>
              </div>
           </div>

           <!-- Expense Items -->
           <div class="space-y-3">
              <h4 class="text-sm font-bold flex items-center gap-2 text-red-600">
                 <ArrowDownCircle class="w-4 h-4" />
                 Expense Allocations
              </h4>
              <div class="border rounded-lg overflow-hidden">
                 <Table>
                    <TableHeader class="bg-red-50/50">
                       <TableRow>
                          <TableHead class="h-9 px-3 text-xs">Item</TableHead>
                          <TableHead class="h-9 px-3 text-xs text-right">Amount</TableHead>
                       </TableRow>
                    </TableHeader>
                    <TableBody>
                       <TableRow v-if="expenseItems.length === 0">
                          <TableCell colspan="2" class="h-16 text-center text-xs text-muted-foreground italic">No expense allocations.</TableCell>
                       </TableRow>
                       <TableRow v-for="item in expenseItems" :key="item.ItemID" class="h-9">
                          <TableCell class="px-3 py-2 text-xs font-medium">{{ item.ItemName }}</TableCell>
                          <TableCell class="px-3 py-2 text-xs text-right font-bold">{{ auth.currencySymbol }}{{ item.Amount.toLocaleString() }}</TableCell>
                       </TableRow>
                    </TableBody>
                 </Table>
              </div>
           </div>
        </div>

        <!-- Metadata -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 text-[10px] text-muted-foreground border-t pt-4 px-1">
           <div class="flex items-center gap-2">
              <Clock class="w-3 h-3" />
              Created on {{ dayjs(budget.CreatedAt).format('MMMM D, YYYY [at] h:mm A') }}
           </div>
           <div v-if="budget.BudgetStatus === 'Approved'" class="flex items-center gap-2 text-green-600 font-bold">
              <ShieldCheck class="w-3 h-3" />
              Approved by {{ budget.ApproverFirstName }} {{ budget.ApproverFamilyName }}
           </div>
        </div>
      </div>

      <DialogFooter class="gap-2">
        <Button variant="outline" @click="emit('update:open', false)">Close</Button>
        <Button v-if="budget?.BudgetStatus === 'Approved'" variant="outline" class="gap-2 font-bold">
           <Printer class="w-4 h-4" /> Print PDF
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
