<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { 
  Plus, 
  Search, 
  Download, 
  Printer, 
  MoreVertical, 
  Eye, 
  Trash2, 
  CheckCircle2, 
  Clock,
  XCircle,
  Building,
  Calendar,
  PieChart,
  Send,
  ShieldCheck,
  AlertCircle
} from 'lucide-vue-next'
import { useBudgetsStore } from '@/stores/budgets'
import { useLookupsStore } from '@/stores/lookups'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/components/ui/toast/use-toast'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardFooter
} from '@/components/ui/card'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Alerts } from '@/utils/alerts'
import dayjs from 'dayjs'

const store = useBudgetsStore()
const lookups = useLookupsStore()
const auth = useAuthStore()
const { toast } = useToast()

// Filters state
const filters = ref({
  search: '',
  status: 'all',
  fiscal_year_id: 'all',
  branch_id: 'all',
  page: 1,
  limit: 10
})

// Modals state
const isAddModalOpen = ref(false)
const isViewModalOpen = ref(false)
const selectedBudgetId = ref<number | null>(null)

// Load data
onMounted(async () => {
  await Promise.all([
    store.fetchBudgets(getCleanFilters()),
    lookups.fetchLookups()
  ])
})

function getCleanFilters() {
  const f: any = { ...filters.value }
  Object.keys(f).forEach(key => {
    if (f[key] === 'all' || f[key] === '') delete f[key]
  })
  return f
}

async function refreshData() {
  await store.fetchBudgets(getCleanFilters())
}

watch(filters, () => {
  refreshData()
}, { deep: true })

// Helpers
function getStatusBadge(status: string) {
  const map: Record<string, { variant: "default" | "destructive" | "outline" | "secondary", class: string }> = {
    'Approved': { variant: 'default', class: 'bg-green-500 hover:bg-green-600' },
    'Submitted': { variant: 'outline', class: 'text-blue-600 border-blue-200 bg-blue-50' },
    'Rejected': { variant: 'destructive', class: '' },
    'Draft': { variant: 'secondary', class: 'opacity-70' }
  }
  return map[status] || { variant: 'outline', class: '' }
}

// Actions
function viewDetails(id: number) {
  selectedBudgetId.value = id
  isViewModalOpen.value = true
}

async function submitBudget(id: number) {
  const confirmed = await Alerts.confirm({
    title: 'Submit Budget',
    text: 'Are you sure you want to submit this budget for approval? You won\'t be able to edit it anymore.',
    icon: 'question'
  })

  if (confirmed) {
    try {
      await store.submitBudget(id)
      toast({ title: 'Submitted', description: 'Budget sent for approval' })
      refreshData()
    } catch (e: any) {
      toast({ title: 'Error', description: e.response?.data?.message || 'Failed to submit budget', variant: 'destructive' })
    }
  }
}

async function handleReview(id: number, action: 'approve' | 'reject') {
  const text = action === 'approve' ? 'Approve this budget?' : 'Reject this budget?'
  const confirmed = await Alerts.confirm({
     title: action.toUpperCase(),
     text: text,
     icon: action === 'approve' ? 'success' : 'warning'
  })

  if (confirmed) {
     try {
        await store.reviewBudget(id, action)
        toast({ title: 'Success', description: `Budget has been ${action}d` })
        refreshData()
     } catch (e: any) {
        toast({ title: 'Error', description: 'Action failed', variant: 'destructive' })
     }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Budget Registry</h1>
        <p class="text-muted-foreground">Monitor church financial planning and expense allocations.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button variant="outline" class="hidden md:flex">
          <Download class="w-4 h-4 mr-2" />
          Export
        </Button>
        <Button @click="isAddModalOpen = true">
          <Plus class="w-4 h-4 mr-2" />
          Create Budget
        </Button>
      </div>
    </div>

    <!-- Filters -->
    <Card>
      <CardContent class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <Input v-model="filters.search" placeholder="Search budgets..." class="pl-10" />
          </div>

          <Select v-model="filters.status">
            <SelectTrigger>
              <SelectValue placeholder="All Statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Statuses</SelectItem>
              <SelectItem value="Draft">Draft</SelectItem>
              <SelectItem value="Submitted">Submitted</SelectItem>
              <SelectItem value="Approved">Approved</SelectItem>
              <SelectItem value="Rejected">Rejected</SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="filters.fiscal_year_id">
            <SelectTrigger>
              <SelectValue placeholder="Fiscal Year" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Fiscal Years</SelectItem>
              <SelectItem v-for="fy in lookups.fiscalYears" :key="fy.id" :value="fy.id.toString()">
                {{ fy.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="filters.branch_id">
            <SelectTrigger>
              <SelectValue placeholder="Branch" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Branches</SelectItem>
              <SelectItem v-for="branch in lookups.branches" :key="branch.id" :value="branch.id.toString()">
                {{ branch.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CardContent>
    </Card>

    <!-- Table -->
    <Card>
      <CardContent class="p-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Budget Title</TableHead>
              <TableHead>Fiscal Year</TableHead>
              <TableHead>Branch</TableHead>
              <TableHead class="text-right">Total Amount</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Created</TableHead>
              <TableHead class="w-[50px]"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="store.loading" class="h-24">
              <TableCell colspan="7" class="text-center text-muted-foreground">
                <Loader2 class="w-4 h-4 mr-2 animate-spin inline" /> Loading budgets...
              </TableCell>
            </TableRow>
            <TableRow v-else-if="store.budgets.length === 0" class="h-24">
              <TableCell colspan="7" class="text-center text-muted-foreground">
                No budget plans found.
              </TableCell>
            </TableRow>
            <TableRow v-for="budget in store.budgets" :key="budget.BudgetID" class="group hover:bg-muted/50 transition-colors">
              <TableCell>
                <div class="flex flex-col">
                  <span class="font-semibold text-sm">{{ budget.BudgetTitle }}</span>
                  <span v-if="budget.BudgetSummary" class="text-[10px] text-muted-foreground truncate max-w-[200px]">
                     {{ budget.BudgetSummary }}
                  </span>
                </div>
              </TableCell>
              <TableCell class="text-sm">
                {{ budget.FiscalYearName }}
              </TableCell>
              <TableCell class="text-sm">
                <Badge variant="outline" class="font-normal">{{ budget.BranchName }}</Badge>
              </TableCell>
              <TableCell class="text-right font-bold">
                {{ auth.currencySymbol }}{{ budget.TotalAmount.toLocaleString() }}
              </TableCell>
              <TableCell>
                <Badge :variant="getStatusBadge(budget.BudgetStatus).variant" :class="getStatusBadge(budget.BudgetStatus).class">
                  {{ budget.BudgetStatus }}
                </Badge>
              </TableCell>
              <TableCell class="text-xs text-muted-foreground">
                {{ dayjs(budget.CreatedAt).format('MMM D, YYYY') }}
              </TableCell>
              <TableCell>
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                      <MoreVertical class="w-4 h-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuLabel>Budget Actions</DropdownMenuLabel>
                    <DropdownMenuItem @click="viewDetails(budget.BudgetID)">
                      <Eye class="w-4 h-4 mr-2" /> View Breakdown
                    </DropdownMenuItem>
                    
                    <template v-if="budget.BudgetStatus === 'Draft'">
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="submitBudget(budget.BudgetID)" class="text-blue-600">
                        <Send class="w-4 h-4 mr-2" /> Submit for Approval
                      </DropdownMenuItem>
                    </template>

                    <template v-if="budget.BudgetStatus === 'Submitted'">
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="handleReview(budget.BudgetID, 'approve')" class="text-green-600">
                        <ShieldCheck class="w-4 h-4 mr-2" /> Approve
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="handleReview(budget.BudgetID, 'reject')" class="text-destructive">
                        <XCircle class="w-4 h-4 mr-2" /> Reject
                      </DropdownMenuItem>
                    </template>

                    <DropdownMenuSeparator />
                    <DropdownMenuItem class="text-destructive">
                      <Trash2 class="w-4 h-4 mr-2" /> Delete
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
      <CardFooter class="flex items-center justify-between p-4 border-t">
        <div class="text-sm text-muted-foreground">
          Showing {{ store.budgets.length }} of {{ store.pagination.total }} budgets
        </div>
        <div class="flex items-center gap-2">
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="filters.page === 1" 
            @click="filters.page--"
          >
            Previous
          </Button>
          <div class="text-sm font-medium">Page {{ filters.page }} of {{ store.pagination.pages }}</div>
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="filters.page >= store.pagination.pages" 
            @click="filters.page++"
          >
            Next
          </Button>
        </div>
      </CardFooter>
    </Card>

    <!-- Modals -->
    <AddBudgetModal v-model:open="isAddModalOpen" @success="refreshData" />
    <ViewBudgetModal v-model:open="isViewModalOpen" :id="selectedBudgetId" />
  </div>
</template>
