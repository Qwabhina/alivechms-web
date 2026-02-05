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
  Bookmark,
  HandCoins,
  TrendingUp,
  AlertCircle
} from 'lucide-vue-next'
import { usePledgesStore } from '@/stores/pledges'
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
import { Progress } from '@/components/ui/progress'
import { Alerts } from '@/utils/alerts'
import dayjs from 'dayjs'

const store = usePledgesStore()
const lookups = useLookupsStore()
const auth = useAuthStore()
const { toast } = useToast()

// Filters state
const filters = ref({
  search: '',
  pledge_type_id: 'all',
  status: 'all',
  fiscal_year_id: 'all',
  page: 1,
  limit: 10
})

// Modals state
const isAddModalOpen = ref(false)
const isViewModalOpen = ref(false)
const isPaymentModalOpen = ref(false)
const selectedPledgeId = ref<number | null>(null)

// Load data
onMounted(async () => {
  await Promise.all([
    store.fetchPledges(getCleanFilters()),
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
  await store.fetchPledges(getCleanFilters())
  await store.fetchStats(filters.value.fiscal_year_id === 'all' ? undefined : Number(filters.value.fiscal_year_id))
}

watch(filters, () => {
  refreshData()
}, { deep: true })

// Helpers
function getStatusBadge(status: string) {
  const map: Record<string, { variant: "default" | "destructive" | "outline" | "secondary", class: string }> = {
    'Fulfilled': { variant: 'default', class: 'bg-green-500 hover:bg-green-600' },
    'Active': { variant: 'outline', class: 'text-blue-600 border-blue-200 bg-blue-50' },
    'Cancelled': { variant: 'secondary', class: 'opacity-70' }
  }
  return map[status] || { variant: 'outline', class: '' }
}

function getProgressColor(percent: number) {
  if (percent >= 100) return 'bg-green-500'
  if (percent >= 50) return 'bg-blue-500'
  return 'bg-amber-500'
}

// Actions
function viewDetails(id: number) {
  selectedPledgeId.value = id
  isViewModalOpen.value = true
}

function recordPayment(id: number) {
  selectedPledgeId.value = id
  isPaymentModalOpen.value = true
}

async function deletePledge(id: number) {
  const confirmed = await Alerts.confirm({
    title: 'Delete Pledge',
    text: 'Are you sure you want to delete this pledge? This cannot be undone.',
    icon: 'warning',
    confirmButtonText: 'Delete',
    confirmButtonColor: '#d33'
  })

  if (confirmed) {
    try {
      await store.deletePledge(id)
      toast({ title: 'Deleted', description: 'Pledge record removed' })
      refreshData()
    } catch (e) {
      toast({ title: 'Error', description: 'Failed to delete pledge', variant: 'destructive' })
    }
  }
}

const statsCards = computed(() => {
  if (!store.stats) return []
  return [
    {
      title: 'Total Pledged',
      value: store.stats.total_amount,
      count: store.stats.total_count,
      icon: Bookmark,
      color: 'text-blue-600',
      bg: 'bg-blue-50'
    },
    {
      title: 'Total Paid',
      value: store.stats.payments_total,
      icon: HandCoins,
      color: 'text-green-600',
      bg: 'bg-green-50'
    },
    {
      title: 'Outstanding',
      value: store.stats.outstanding_amount,
      icon: AlertCircle,
      color: 'text-amber-600',
      bg: 'bg-amber-50'
    },
    {
      title: 'Active Pledges',
      value: store.stats.active_amount,
      icon: Clock,
      color: 'text-purple-600',
      bg: 'bg-purple-50'
    }
  ]
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Pledge Management</h1>
        <p class="text-muted-foreground">Manage member pledges and track fulfillment progress.</p>
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
          New Pledge
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
          <div v-if="stat.count !== undefined" class="text-xs text-muted-foreground mt-1">
             {{ stat.count }} pledges total
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
            <Input v-model="filters.search" placeholder="Search member name..." class="pl-10" />
          </div>

          <Select v-model="filters.pledge_type_id">
            <SelectTrigger>
              <SelectValue placeholder="All Types" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Types</SelectItem>
              <SelectItem v-for="type in lookups.pledgeTypes" :key="type.id" :value="type.id.toString()">
                {{ type.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="filters.status">
            <SelectTrigger>
              <SelectValue placeholder="All Statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Statuses</SelectItem>
              <SelectItem value="Active">Active</SelectItem>
              <SelectItem value="Fulfilled">Fulfilled</SelectItem>
              <SelectItem value="Cancelled">Cancelled</SelectItem>
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
              <TableHead>Member</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Date</TableHead>
              <TableHead class="text-right">Amount</TableHead>
              <TableHead class="w-[200px]">Progress</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="w-[50px]"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="store.loading" class="h-24">
              <TableCell colspan="7" class="text-center">
                <div class="flex items-center justify-center gap-2 text-muted-foreground">
                  <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                  Loading pledges...
                </div>
              </TableCell>
            </TableRow>
            <TableRow v-else-if="store.pledges.length === 0" class="h-24">
              <TableCell colspan="7" class="text-center text-muted-foreground">
                No pledge records found.
              </TableCell>
            </TableRow>
            <TableRow v-for="pledge in store.pledges" :key="pledge.PledgeID" class="group hover:bg-muted/50 transition-colors">
              <TableCell>
                <div class="flex flex-col">
                  <span class="font-semibold text-sm">{{ pledge.MbrFirstName }} {{ pledge.MbrFamilyName }}</span>
                  <span class="text-[10px] text-muted-foreground uppercase tracking-widest">Member ID: {{ pledge.MbrID }}</span>
                </div>
              </TableCell>
              <TableCell>
                <Badge variant="outline" class="font-normal">{{ pledge.PledgeTypeName }}</Badge>
              </TableCell>
              <TableCell class="text-sm">
                {{ dayjs(pledge.PledgeDate).format('MMM D, YYYY') }}
              </TableCell>
              <TableCell class="text-right font-bold">
                 <div class="flex flex-col items-end">
                    <span>{{ auth.currencySymbol }}{{ pledge.PledgeAmount.toLocaleString() }}</span>
                    <span class="text-[10px] text-muted-foreground">Paid: {{ auth.currencySymbol }}{{ (pledge.total_paid || 0).toLocaleString() }}</span>
                 </div>
              </TableCell>
              <TableCell>
                <div class="space-y-1.5">
                  <div class="flex justify-between text-[10px]">
                    <span class="font-medium text-muted-foreground">{{ Math.round(((pledge.total_paid || 0) / pledge.PledgeAmount) * 100) }}%</span>
                    <span class="text-muted-foreground">Bal: {{ auth.currencySymbol }}{{ (pledge.balance || 0).toLocaleString() }}</span>
                  </div>
                  <Progress :model-value="((pledge.total_paid || 0) / pledge.PledgeAmount) * 100" class="h-1.5" />
                </div>
              </TableCell>
              <TableCell>
                <Badge :variant="getStatusBadge(pledge.Status).variant" :class="getStatusBadge(pledge.Status).class">
                  {{ pledge.Status }}
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
                    <DropdownMenuItem @click="viewDetails(pledge.PledgeID)">
                      <Eye class="w-4 h-4 mr-2" /> View Details
                    </DropdownMenuItem>
                    
                    <template v-if="pledge.Status === 'Active'">
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="recordPayment(pledge.PledgeID)" class="text-green-600">
                        <HandCoins class="w-4 h-4 mr-2" /> Record Payment
                      </DropdownMenuItem>
                    </template>
                    
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="deletePledge(pledge.PledgeID)" class="text-destructive">
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
          Showing {{ store.pledges.length }} of {{ store.pagination.total }} pledges
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
    <AddPledgeModal v-model:open="isAddModalOpen" @success="refreshData" />
    <ViewPledgeModal v-model:open="isViewModalOpen" :id="selectedPledgeId" />
    <RecordPaymentModal v-model:open="isPaymentModalOpen" :pledgeId="selectedPledgeId" @success="refreshData" />
  </div>
</template>
