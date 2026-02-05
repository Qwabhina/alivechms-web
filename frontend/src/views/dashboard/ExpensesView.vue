<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import {
   Plus,
   Search,
   Filter,
   Download,
   Printer,
   MoreVertical,
   Eye,
   Edit2,
   Trash2,
   FileCheck,
   FileX,
   Clock,
   CheckCircle2,
   XCircle,
   TrendingDown,
   Calendar,
   Building,
   Tag,
   Receipt
} from 'lucide-vue-next'
import { useExpensesStore } from '@/stores/expenses'
import { useLookupsStore } from '@/stores/lookups'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/components/ui/toast/use-toast'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
   Card,
   CardContent,
   CardDescription,
   CardHeader,
   CardTitle,
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
import AddExpenseModal from '@/components/dashboard/AddExpenseModal.vue'
import ViewExpenseModal from '@/components/dashboard/ViewExpenseModal.vue'

const store = useExpensesStore()
const lookups = useLookupsStore()
const auth = useAuthStore()
const { toast } = useToast()

// Filters state
const filters = ref({
   search: '',
   category_id: 'all',
   branch_id: 'all',
   status: 'all',
   fiscal_year_id: 'all',
   page: 1,
   limit: 10
})

// Modals state
const isAddModalOpen = ref(false)
const isViewModalOpen = ref(false)
const selectedExpenseId = ref<number | null>(null)

// Load data
onMounted(async () => {
   await Promise.all([
      store.fetchExpenses(getCleanFilters()),
      store.fetchStats(),
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
   await store.fetchExpenses(getCleanFilters())
   await store.fetchStats(filters.value.fiscal_year_id === 'all' ? undefined : Number(filters.value.fiscal_year_id))
}

watch(filters, () => {
   refreshData()
}, { deep: true })

// Table Helper
function getStatusBadge(status: string) {
   const map: Record<string, { variant: "default" | "destructive" | "outline" | "secondary", class: string }> = {
      'Approved': { variant: 'default', class: 'bg-green-500 hover:bg-green-600' },
      'Pending Approval': { variant: 'outline', class: 'text-amber-600 border-amber-200 bg-amber-50' },
      'Declined': { variant: 'destructive', class: '' },
      'Cancelled': { variant: 'secondary', class: 'opacity-70' }
   }
   return map[status] || { variant: 'outline', class: '' }
}

function getStatusIcon(status: string) {
   switch (status) {
      case 'Approved': return CheckCircle2
      case 'Pending Approval': return Clock
      case 'Declined': return XCircle
      case 'Cancelled': return FileX
      default: return Clock
   }
}

// Actions
function viewDetails(id: number) {
   selectedExpenseId.value = id
   isViewModalOpen.value = true
}

async function handleReview(id: number, action: 'approve' | 'reject') {
   const confirmed = await Alerts.confirm({
      title: `${action.charAt(0).toUpperCase() + action.slice(1)} Expense`,
      text: `Are you sure you want to ${action} this expense request?`,
      icon: action === 'approve' ? 'success' : 'warning',
      confirmButtonText: action.charAt(0).toUpperCase() + action.slice(1)
   })

   if (confirmed) {
      try {
         await store.reviewExpense(id, action)
         toast({ title: 'Success', description: `Expense ${action}d successfully` })
         refreshData()
      } catch (e: any) {
         toast({ title: 'Error', description: e.message || `Failed to ${action} expense`, variant: 'destructive' })
      }
   }
}

async function deleteExpense(id: number) {
   const confirmed = await Alerts.confirm({
      title: 'Delete Expense',
      text: 'Are you sure you want to delete this expense request? This cannot be undone.',
      icon: 'warning',
      confirmButtonText: 'Delete',
      confirmButtonColor: '#d33'
   })

   if (confirmed) {
      try {
         await store.deleteExpense(id)
         toast({ title: 'Deleted', description: 'Expense record removed' })
         refreshData()
      } catch (e: any) {
         toast({ title: 'Error', description: 'Failed to delete expense', variant: 'destructive' })
      }
   }
}

const statsCards = computed(() => {
   if (!store.stats) return []
   return [
      {
         title: 'Total Expenses',
         value: store.stats.total_amount,
         count: store.stats.total_count,
         icon: TrendingDown,
         color: 'text-red-600',
         bg: 'bg-red-50'
      },
      {
         title: 'Approved',
         value: store.stats.approved_total,
         count: store.stats.approved_count,
         icon: CheckCircle2,
         color: 'text-green-600',
         bg: 'bg-green-50'
      },
      {
         title: 'Pending',
         value: store.stats.pending_total,
         count: store.stats.pending_count,
         icon: Clock,
         color: 'text-amber-600',
         bg: 'bg-amber-50'
      },
      {
         title: 'This Month',
         value: store.stats.month_total,
         growth: store.stats.month_growth,
         icon: Calendar,
         color: 'text-blue-600',
         bg: 'bg-blue-50'
      }
   ]
})
</script>

<template>
   <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
         <div>
            <h1 class="text-3xl font-bold tracking-tight">Expense Management</h1>
            <p class="text-muted-foreground">Track church spending and manage approval workflows.</p>
         </div>
         <div class="flex items-center gap-2">
            <Button variant="outline" class="hidden md:flex">
               <Download class="w-4 h-4 mr-2" />
               Export
            </Button>
            <Button variant="outline" class="hidden md:flex">
               <Printer class="w-4 h-4 mr-2" />
               Print
            </Button>
            <Button @click="isAddModalOpen = true">
               <Plus class="w-4 h-4 mr-2" />
               New Expense
            </Button>
         </div>
      </div>

      <!-- Stats -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
         <Card v-for="stat in statsCards" :key="stat.title">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
               <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
               <div :class="`p-2 rounded-full ${stat.bg}`">
                  <component :is="stat.icon" :class="`w-4 h-4 ${stat.color}`" />
               </div>
            </CardHeader>
            <CardContent>
               <div class="text-2xl font-bold">{{ auth.currencySymbol }}{{ stat.value.toLocaleString() }}</div>
               <div class="flex items-center mt-1">
                  <span v-if="stat.count !== undefined" class="text-xs text-muted-foreground">
                     {{ stat.count }} transactions
                  </span>
                  <span v-if="stat.growth !== undefined"
                     :class="`text-xs font-medium ${stat.growth >= 0 ? 'text-red-600' : 'text-green-600'}`">
                     {{ stat.growth >= 0 ? '+' : '' }}{{ stat.growth }}% from last month
                  </span>
               </div>
            </CardContent>
         </Card>
      </div>

      <!-- Filters -->
      <Card>
         <CardContent class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4">
               <div class="relative">
                  <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                  <Input v-model="filters.search" placeholder="Search title or purpose..." class="pl-10" />
               </div>

               <Select v-model="filters.category_id">
                  <SelectTrigger>
                     <SelectValue placeholder="All Categories" />
                  </SelectTrigger>
                  <SelectContent>
                     <SelectItem value="all">All Categories</SelectItem>
                     <SelectItem v-for="cat in lookups.expenseCategories" :key="cat.id" :value="cat.id.toString()">
                        {{ cat.name }}
                     </SelectItem>
                  </SelectContent>
               </Select>

               <Select v-model="filters.status">
                  <SelectTrigger>
                     <SelectValue placeholder="All Statuses" />
                  </SelectTrigger>
                  <SelectContent>
                     <SelectItem value="all">All Statuses</SelectItem>
                     <SelectItem value="Pending Approval">Pending Approval</SelectItem>
                     <SelectItem value="Approved">Approved</SelectItem>
                     <SelectItem value="Declined">Declined</SelectItem>
                     <SelectItem value="Cancelled">Cancelled</SelectItem>
                  </SelectContent>
               </Select>

               <Select v-model="filters.branch_id">
                  <SelectTrigger>
                     <SelectValue placeholder="All Branches" />
                  </SelectTrigger>
                  <SelectContent>
                     <SelectItem value="all">All Branches</SelectItem>
                     <SelectItem v-for="branch in lookups.branches" :key="branch.id" :value="branch.id.toString()">
                        {{ branch.name }}
                     </SelectItem>
                  </SelectContent>
               </Select>

               <Select v-model="filters.fiscal_year_id">
                  <SelectTrigger>
                     <SelectValue placeholder="Current Fiscal Year" />
                  </SelectTrigger>
                  <SelectContent>
                     <SelectItem value="all">All Fiscal Years</SelectItem>
                     <SelectItem v-for="fy in lookups.fiscalYears" :key="fy.id" :value="fy.id.toString()">
                        {{ fy.name }}
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
                     <TableHead>Date</TableHead>
                     <TableHead>Expense Detail</TableHead>
                     <TableHead>Category</TableHead>
                     <TableHead>Branch</TableHead>
                     <TableHead class="text-right">Amount</TableHead>
                     <TableHead>Status</TableHead>
                     <TableHead class="w-[50px]"></TableHead>
                  </TableRow>
               </TableHeader>
               <TableBody>
                  <TableRow v-if="store.loading" class="h-24">
                     <TableCell colspan="7" class="text-center">
                        <div class="flex items-center justify-center gap-2 text-muted-foreground">
                           <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin">
                           </div>
                           Loading expenses...
                        </div>
                     </TableCell>
                  </TableRow>
                  <TableRow v-else-if="store.expenses.length === 0" class="h-24">
                     <TableCell colspan="7" class="text-center text-muted-foreground">
                        No expense records found.
                     </TableCell>
                  </TableRow>
                  <TableRow v-for="expense in store.expenses" :key="expense.ExpenseID"
                     class="group hover:bg-muted/50 transition-colors">
                     <TableCell class="font-medium">
                        {{ dayjs(expense.ExpenseDate).format('MMM D, YYYY') }}
                     </TableCell>
                     <TableCell>
                        <div class="flex flex-col">
                           <span class="font-semibold text-sm">{{ expense.ExpenseTitle }}</span>
                           <span v-if="expense.ExpensePurpose"
                              class="text-xs text-muted-foreground truncate max-w-[200px]">
                              {{ expense.ExpensePurpose }}
                           </span>
                        </div>
                     </TableCell>
                     <TableCell>
                        <Badge variant="secondary" class="font-normal">{{ expense.CategoryName }}</Badge>
                     </TableCell>
                     <TableCell>
                        <div class="flex items-center gap-1.5 text-xs">
                           <Building class="w-3 h-3 text-muted-foreground" />
                           {{ expense.BranchName }}
                        </div>
                     </TableCell>
                     <TableCell class="text-right font-bold text-red-600">
                        {{ auth.currencySymbol }}{{ expense.ExpenseAmount.toLocaleString() }}
                     </TableCell>
                     <TableCell>
                        <Badge :variant="getStatusBadge(expense.ExpenseStatus).variant"
                           :class="getStatusBadge(expense.ExpenseStatus).class">
                           <component :is="getStatusIcon(expense.ExpenseStatus)" class="w-3 h-3 mr-1" />
                           {{ expense.ExpenseStatus }}
                        </Badge>
                     </TableCell>
                     <TableCell>
                        <DropdownMenu>
                           <DropdownMenuTrigger as-child>
                              <Button variant="ghost" size="icon" class="h-8 w-8">
                                 <MoreVertical class="w-4 h-4" />
                              </Button>
                           </DropdownMenuTrigger>
                           <DropdownMenuContent align="end">
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuItem @click="viewDetails(expense.ExpenseID)">
                                 <Eye class="w-4 h-4 mr-2" /> View Details
                              </DropdownMenuItem>

                              <template v-if="expense.ExpenseStatus === 'Pending Approval'">
                                 <DropdownMenuSeparator />
                                 <DropdownMenuItem @click="handleReview(expense.ExpenseID, 'approve')"
                                    class="text-green-600">
                                    <FileCheck class="w-4 h-4 mr-2" /> Approve
                                 </DropdownMenuItem>
                                 <DropdownMenuItem @click="handleReview(expense.ExpenseID, 'reject')"
                                    class="text-red-600">
                                    <XCircle class="w-4 h-4 mr-2" /> Decline
                                 </DropdownMenuItem>
                                 <DropdownMenuSeparator />
                                 <DropdownMenuItem @click="deleteExpense(expense.ExpenseID)" class="text-destructive">
                                    <Trash2 class="w-4 h-4 mr-2" /> Delete
                                 </DropdownMenuItem>
                              </template>
                           </DropdownMenuContent>
                        </DropdownMenu>
                     </TableCell>
                  </TableRow>
               </TableBody>
            </Table>
         </CardContent>
         <CardFooter class="flex items-center justify-between p-4 border-t">
            <div class="text-sm text-muted-foreground">
               Showing {{ store.expenses.length }} of {{ store.pagination.total }} expenses
            </div>
            <div class="flex items-center gap-2">
               <Button variant="outline" size="sm" :disabled="filters.page === 1" @click="filters.page--">
                  Previous
               </Button>
               <div class="text-sm font-medium">Page {{ filters.page }} of {{ store.pagination.pages }}</div>
               <Button variant="outline" size="sm" :disabled="filters.page >= store.pagination.pages"
                  @click="filters.page++">
                  Next
               </Button>
            </div>
         </CardFooter>
      </Card>

      <!-- Modals -->
      <AddExpenseModal v-model:open="isAddModalOpen" @success="refreshData" />
      <ViewExpenseModal v-model:open="isViewModalOpen" :id="selectedExpenseId" @action="refreshData" />
   </div>
</template>

<style scoped>
/* Custom status classes if needed */
</style>
