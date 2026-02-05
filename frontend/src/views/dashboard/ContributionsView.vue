<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useContributionsStore } from '@/stores/contributions'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { useToast } from '@/components/ui/toast/use-toast'
import {
   getCoreRowModel,
   useVueTable,
   createColumnHelper,
   type ColumnDef
} from '@tanstack/vue-table'

// UI Components
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
   Table,
   TableBody,
   TableCell,
   TableHead,
   TableHeader,
   TableRow
} from '@/components/ui/table'
import {
   DropdownMenu,
   DropdownMenuContent,
   DropdownMenuItem,
   DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import { Skeleton } from '@/components/ui/skeleton'

// Icons
import {
   Coins,
   Plus,
   Tags,
   RefreshCw,
   Eye,
   Receipt,
   Pencil,
   Trash2,
   RotateCcw,
   Search,
   X,
   ChevronLeft,
   ChevronRight,
   MoreHorizontal,
   Trophy,
   TrendingUp,
   PieChart,
   Calendar,
   CalendarDays,
   CalendarCheck,
   Calculator,
   UserCheck,
   Users,
   CalendarMinus,
   Banknote
} from 'lucide-vue-next'

// Modals
import AddContributionModal from '@/components/dashboard/AddContributionModal.vue'
import ViewContributionModal from '@/components/dashboard/ViewContributionModal.vue'
import ViewReceiptModal from '@/components/dashboard/ViewReceiptModal.vue'
import ViewStatementModal from '@/components/dashboard/ViewStatementModal.vue'
import ManageTypesModal from '@/components/dashboard/ManageContributionTypesModal.vue'

// Chart
import VueApexCharts from 'vue3-apexcharts'

// Types
import type { Contribution } from '@/stores/contributions'

const store = useContributionsStore()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const toast = useToast()

// Modal states
const showAddModal = ref(false)
const showViewModal = ref(false)
const showReceiptModal = ref(false)
const showStatementModal = ref(false)
const showTypesModal = ref(false)
const editingContribution = ref<Contribution | null>(null)
const viewingContributionId = ref<number | null>(null)
const receiptContributionId = ref<number | null>(null)
const statementMemberId = ref<number | null>(null)

// Initialize
onMounted(async () => {
   // Get currency symbol from settings
   await settingsStore.fetchSettings()
   store.setCurrencySymbol(settingsStore.settings.currency_symbol || 'GH₵')

   await store.fetchDropdowns()
   await store.fetchContributions()
   await store.fetchStats()
})

// Watch fiscal year changes
watch(() => store.selectedFiscalYearId, () => {
   store.fetchContributions()
   store.fetchStats()
})

// Format currency
function formatCurrency(amount: number | string | null | undefined): string {
   if (amount === null || amount === undefined) return '-'
   const num = typeof amount === 'string' ? parseFloat(amount) : amount
   if (isNaN(num)) return '-'
   return `${store.currencySymbol} ${num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`
}

function formatCurrencyShort(amount: number): string {
   if (amount >= 1000000) return `${store.currencySymbol}${(amount / 1000000).toFixed(1)}M`
   if (amount >= 1000) return `${store.currencySymbol}${(amount / 1000).toFixed(1)}K`
   return `${store.currencySymbol}${amount}`
}

function formatDate(dateStr: string): string {
   if (!dateStr) return '-'
   return new Date(dateStr).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
   })
}

// Stats cards data
const statsRow1 = computed(() => {
   const s = store.stats
   const fyStatus = s?.fiscal_year?.status
   const statusBadge = fyStatus === 'Closed'

   return [
      {
         title: 'Total',
         value: formatCurrency(s?.total_amount || 0),
         subtitle: `${(s?.total_count || 0).toLocaleString()} contributions`,
         icon: Banknote,
         color: 'bg-blue-500',
         bgColor: 'bg-blue-50',
         closed: statusBadge
      },
      {
         title: 'This Month',
         value: formatCurrency(s?.month_total || 0),
         subtitle: `${(s?.month_growth || 0) >= 0 ? '+' : ''}${s?.month_growth || 0}% vs last month`,
         subtitleColor: (s?.month_growth || 0) >= 0 ? 'text-green-600' : 'text-red-600',
         icon: CalendarCheck,
         color: 'bg-green-500',
         bgColor: 'bg-green-50'
      },
      {
         title: 'This Week',
         value: formatCurrency(s?.week_total || 0),
         subtitle: `${(s?.week_count || 0).toLocaleString()} contributions`,
         icon: CalendarDays,
         color: 'bg-cyan-500',
         bgColor: 'bg-cyan-50'
      },
      {
         title: 'Today',
         value: formatCurrency(s?.today_total || 0),
         subtitle: `${(s?.today_count || 0).toLocaleString()} contributions`,
         icon: Calendar,
         color: 'bg-amber-500',
         bgColor: 'bg-amber-50'
      }
   ]
})

const statsRow2 = computed(() => {
   const s = store.stats
   return [
      {
         title: 'Average Contribution',
         value: formatCurrency(s?.average_amount || 0),
         subtitle: 'Per transaction',
         icon: Calculator,
         color: 'bg-gray-500',
         bgColor: 'bg-gray-50'
      },
      {
         title: 'Avg Per Contributor',
         value: formatCurrency(s?.average_per_contributor || 0),
         subtitle: 'Per member',
         icon: UserCheck,
         color: 'bg-slate-600',
         bgColor: 'bg-slate-50'
      },
      {
         title: 'Unique Contributors',
         value: (s?.unique_contributors || 0).toLocaleString(),
         subtitle: 'Active givers',
         icon: Users,
         color: 'bg-blue-500',
         bgColor: 'bg-blue-50'
      },
      {
         title: 'Last Month',
         value: formatCurrency(s?.last_month_total || 0),
         subtitle: 'Previous month total',
         icon: CalendarMinus,
         color: 'bg-gray-500',
         bgColor: 'bg-gray-50'
      }
   ]
})

// Charts
const byTypeChartOptions = computed(() => ({
   chart: {
      type: 'donut' as const,
      height: 200
   },
   labels: store.stats?.by_type?.map(t => t.ContributionTypeName) || [],
   colors: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d'],
   legend: {
      position: 'bottom' as const,
      fontSize: '11px'
   },
   tooltip: {
      y: {
         formatter: (val: number) => formatCurrency(val)
      }
   },
   dataLabels: {
      enabled: false
   },
   responsive: [{
      breakpoint: 480,
      options: {
         chart: { width: 200 },
         legend: { position: 'bottom' as const }
      }
   }]
}))

const byTypeChartSeries = computed(() =>
   store.stats?.by_type?.map(t => parseFloat(String(t.total))) || []
)

const monthlyTrendChartOptions = computed(() => ({
   chart: {
      type: 'area' as const,
      height: 200,
      toolbar: { show: false },
      sparkline: { enabled: false }
   },
   stroke: {
      curve: 'smooth' as const,
      width: 2
   },
   fill: {
      type: 'gradient',
      gradient: {
         shadeIntensity: 1,
         opacityFrom: 0.4,
         opacityTo: 0.1
      }
   },
   colors: ['#0d6efd'],
   xaxis: {
      categories: store.stats?.monthly_trend?.map(m => m.month_label) || [],
      labels: { style: { fontSize: '10px' } }
   },
   yaxis: {
      labels: {
         formatter: (val: number) => formatCurrencyShort(val)
      }
   },
   tooltip: {
      y: {
         formatter: (val: number) => formatCurrency(val)
      }
   },
   dataLabels: { enabled: false }
}))

const monthlyTrendChartSeries = computed(() => [{
   name: 'Contributions',
   data: store.stats?.monthly_trend?.map(m => parseFloat(String(m.total))) || []
}])

// Table columns
const columnHelper = createColumnHelper<Contribution>()

const columns: ColumnDef<Contribution, any>[] = [
   columnHelper.accessor('MbrFirstName', {
      header: 'Member',
      cell: (info) => {
         const row = info.row.original
         return {
            firstName: row.MbrFirstName || '',
            lastName: row.MbrFamilyName || '',
            initials: (row.MbrFirstName?.[0] || '') + (row.MbrFamilyName?.[0] || '')
         }
      }
   }),
   columnHelper.accessor('ContributionAmount', {
      header: 'Amount',
      cell: (info) => formatCurrency(info.getValue())
   }),
   columnHelper.accessor('ContributionDate', {
      header: 'Date',
      cell: (info) => formatDate(info.getValue())
   }),
   columnHelper.accessor('ContributionTypeName', {
      header: 'Type',
      cell: (info) => info.getValue() || '-'
   }),
   columnHelper.accessor('PaymentMethodName', {
      header: 'Payment',
      cell: (info) => info.getValue() || '-'
   }),
   columnHelper.accessor('ContributionID', {
      header: 'Actions',
      enableSorting: false
   })
]

const table = useVueTable({
   get data() { return store.contributions },
   columns,
   getCoreRowModel: getCoreRowModel()
})

// Actions
function openAddModal() {
   editingContribution.value = null
   showAddModal.value = true
}

async function openEditModal(contribution: Contribution) {
   editingContribution.value = contribution
   showAddModal.value = true
}

function openViewModal(id: number) {
   viewingContributionId.value = id
   showViewModal.value = true
}

function openReceiptModal(id: number) {
   receiptContributionId.value = id
   showReceiptModal.value = true
}

function openStatementModal(memberId: number) {
   statementMemberId.value = memberId
   showStatementModal.value = true
}

async function handleDelete(id: number) {
   if (!confirm('Are you sure you want to delete this contribution? You can restore it later.')) return

   try {
      await store.deleteContribution(id)
      toast.toast({ title: 'Success', description: 'Contribution deleted successfully' })
   } catch (error: any) {
      toast.toast({ title: 'Error', description: error.message || 'Failed to delete contribution', variant: 'destructive' })
   }
}

async function handleRestore(id: number) {
   if (!confirm('Are you sure you want to restore this contribution?')) return

   try {
      await store.restoreContribution(id)
      toast.toast({ title: 'Success', description: 'Contribution restored successfully' })
   } catch (error: any) {
      toast.toast({ title: 'Error', description: error.message || 'Failed to restore contribution', variant: 'destructive' })
   }
}

function handleRefresh() {
   store.fetchContributions()
   store.fetchStats()
}

function handleSaved() {
   showAddModal.value = false
   editingContribution.value = null
}

// Pagination
function goToPage(page: number) {
   store.fetchContributions(page)
}
</script>

<template>
   <div class="p-6 space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
         <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
               <Coins class="w-7 h-7 text-[#00028a]" />
               Contributions
            </h1>
            <nav class="text-sm text-muted-foreground mt-1">
               <span class="hover:text-foreground cursor-pointer">Dashboard</span>
               <span class="mx-2">/</span>
               <span>Contributions</span>
            </nav>
         </div>

         <div class="flex flex-wrap items-center gap-2">
            <!-- Fiscal Year Selector -->
            <div class="flex items-center gap-2">
               <Label class="text-sm text-muted-foreground whitespace-nowrap">Fiscal Year:</Label>
               <Select :model-value="String(store.selectedFiscalYearId || '')"
                  @update:model-value="(val) => store.setFiscalYear(val ? parseInt(String(val)) : null)">
                  <SelectTrigger class="w-[180px]">
                     <SelectValue placeholder="Select Year" />
                  </SelectTrigger>
                  <SelectContent>
                     <SelectItem v-for="fy in store.fiscalYears" :key="fy.FiscalYearID"
                        :value="String(fy.FiscalYearID)">
                        {{ fy.FiscalYearName }}
                        <span v-if="fy.Status === 'Active'" class="text-green-600 ml-1">(Active)</span>
                        <span v-else-if="fy.Status === 'Closed'" class="text-muted-foreground ml-1">(Closed)</span>
                     </SelectItem>
                  </SelectContent>
               </Select>
            </div>

            <Button variant="outline" @click="showTypesModal = true">
               <Tags class="w-4 h-4 mr-2" />
               Types
            </Button>

            <Button @click="openAddModal">
               <Plus class="w-4 h-4 mr-2" />
               Record Contribution
            </Button>
         </div>
      </div>

      <!-- Stats Cards Row 1 -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
         <Card v-for="(card, i) in statsRow1" :key="i" :class="[card.bgColor, 'border-none']">
            <CardContent class="p-4">
               <div class="flex justify-between items-start">
                  <div>
                     <p class="text-sm text-muted-foreground flex items-center gap-2">
                        {{ card.title }}
                        <Badge v-if="card.closed" variant="secondary" class="text-xs">Closed</Badge>
                     </p>
                     <h3 class="text-2xl font-bold mt-1">
                        <Skeleton v-if="store.statsLoading" class="h-8 w-28" />
                        <span v-else>{{ card.value }}</span>
                     </h3>
                     <p :class="['text-xs mt-1', card.subtitleColor || 'text-muted-foreground']">
                        {{ card.subtitle }}
                     </p>
                  </div>
                  <div :class="[card.color, 'w-10 h-10 rounded-full flex items-center justify-center text-white']">
                     <component :is="card.icon" class="w-5 h-5" />
                  </div>
               </div>
            </CardContent>
         </Card>
      </div>

      <!-- Stats Cards Row 2 -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
         <Card v-for="(card, i) in statsRow2" :key="i" :class="[card.bgColor, 'border-none']">
            <CardContent class="p-4">
               <div class="flex justify-between items-start">
                  <div>
                     <p class="text-sm text-muted-foreground">{{ card.title }}</p>
                     <h3 class="text-2xl font-bold mt-1">
                        <Skeleton v-if="store.statsLoading" class="h-8 w-28" />
                        <span v-else>{{ card.value }}</span>
                     </h3>
                     <p class="text-xs text-muted-foreground mt-1">{{ card.subtitle }}</p>
                  </div>
                  <div :class="[card.color, 'w-10 h-10 rounded-full flex items-center justify-center text-white']">
                     <component :is="card.icon" class="w-5 h-5" />
                  </div>
               </div>
            </CardContent>
         </Card>
      </div>

      <!-- Charts and Top Contributors Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
         <!-- By Type Chart -->
         <Card>
            <CardHeader class="pb-2">
               <CardTitle class="text-base flex items-center gap-2">
                  <PieChart class="w-4 h-4" />
                  By Type
               </CardTitle>
            </CardHeader>
            <CardContent>
               <VueApexCharts v-if="byTypeChartSeries.length > 0" type="donut" height="200"
                  :options="byTypeChartOptions" :series="byTypeChartSeries" />
               <div v-else class="h-[200px] flex items-center justify-center text-muted-foreground">
                  No data available
               </div>
            </CardContent>
         </Card>

         <!-- Monthly Trend Chart -->
         <Card>
            <CardHeader class="pb-2">
               <CardTitle class="text-base flex items-center gap-2">
                  <TrendingUp class="w-4 h-4" />
                  Monthly Trend
               </CardTitle>
            </CardHeader>
            <CardContent>
               <VueApexCharts
                  v-if="monthlyTrendChartSeries.length > 0 && monthlyTrendChartSeries[0]?.data && monthlyTrendChartSeries[0].data.length > 0"
                  type="area" height="200" :options="monthlyTrendChartOptions" :series="monthlyTrendChartSeries" />
               <div v-else class="h-[200px] flex items-center justify-center text-muted-foreground">
                  No data available
               </div>
            </CardContent>
         </Card>

         <!-- Top Contributors -->
         <Card>
            <CardHeader class="pb-2">
               <CardTitle class="text-base flex items-center gap-2">
                  <Trophy class="w-4 h-4 text-amber-500" />
                  Top Contributors
               </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
               <Table>
                  <TableHeader>
                     <TableRow class="bg-muted/50">
                        <TableHead class="w-10"></TableHead>
                        <TableHead>Member</TableHead>
                        <TableHead class="text-right">Total</TableHead>
                     </TableRow>
                  </TableHeader>
                  <TableBody>
                     <template v-if="store.stats?.top_contributors?.length">
                        <TableRow v-for="(c, i) in store.stats.top_contributors.slice(0, 5)" :key="c.MbrID"
                           class="cursor-pointer hover:bg-muted/50" @click="openStatementModal(c.MbrID)">
                           <TableCell class="text-center">
                              <Trophy v-if="i === 0" class="w-4 h-4 text-amber-500 mx-auto" />
                              <Trophy v-else-if="i === 1" class="w-4 h-4 text-gray-400 mx-auto" />
                              <Trophy v-else-if="i === 2" class="w-4 h-4 text-orange-700 mx-auto" />
                              <span v-else class="text-muted-foreground text-sm">{{ i + 1 }}</span>
                           </TableCell>
                           <TableCell>
                              <div class="font-medium">{{ c.MbrFirstName }} {{ c.MbrFamilyName }}</div>
                              <div class="text-xs text-muted-foreground">{{ c.contribution_count }} contributions</div>
                           </TableCell>
                           <TableCell class="text-right font-semibold text-green-600">
                              {{ formatCurrency(c.total) }}
                           </TableCell>
                        </TableRow>
                     </template>
                     <TableRow v-else>
                        <TableCell colspan="3" class="text-center text-muted-foreground py-4">
                           No data available
                        </TableCell>
                     </TableRow>
                  </TableBody>
               </Table>
            </CardContent>
         </Card>
      </div>

      <!-- Filters Row -->
      <Card>
         <CardContent class="py-3">
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
               <div>
                  <Label class="text-sm mb-1 block">Contribution Type</Label>
                  <Select v-model="store.filters.contribution_type_id">
                     <SelectTrigger>
                        <SelectValue placeholder="All Types" />
                     </SelectTrigger>
                     <SelectContent>
                        <SelectItem value="all">All Types</SelectItem>
                        <SelectItem v-for="t in store.contributionTypes" :key="t.ContributionTypeID"
                           :value="String(t.ContributionTypeID)">
                           {{ t.ContributionTypeName }}
                        </SelectItem>
                     </SelectContent>
                  </Select>
               </div>

               <div>
                  <Label class="text-sm mb-1 block">Start Date</Label>
                  <Input type="date" v-model="store.filters.start_date" />
               </div>

               <div>
                  <Label class="text-sm mb-1 block">End Date</Label>
                  <Input type="date" v-model="store.filters.end_date" />
               </div>

               <div class="flex items-center gap-2 pt-5">
                  <Checkbox id="showDeleted" :checked="store.filters.include_deleted"
                     @update:checked="(val: boolean) => store.filters.include_deleted = val" />
                  <Label for="showDeleted" class="text-sm cursor-pointer">Show Deleted</Label>
               </div>

               <Button @click="store.applyFilters()" class="w-full">
                  <Search class="w-4 h-4 mr-2" />
                  Filter
               </Button>

               <Button variant="outline" @click="store.clearFilters()" class="w-full">
                  <X class="w-4 h-4 mr-2" />
                  Clear
               </Button>
            </div>
         </CardContent>
      </Card>

      <!-- Contributions Table -->
      <Card>
         <CardHeader class="border-b">
            <div class="flex items-center justify-between">
               <CardTitle class="text-lg flex items-center gap-2">
                  All Contributions
                  <Badge variant="secondary">{{ store.pagination.total }}</Badge>
               </CardTitle>
               <Button variant="outline" size="sm" @click="handleRefresh">
                  <RefreshCw class="w-4 h-4 mr-2" />
                  Refresh
               </Button>
            </div>
         </CardHeader>
         <CardContent class="p-0">
            <Table>
               <TableHeader>
                  <TableRow>
                     <TableHead>Member</TableHead>
                     <TableHead>Amount</TableHead>
                     <TableHead>Date</TableHead>
                     <TableHead>Type</TableHead>
                     <TableHead>Payment</TableHead>
                     <TableHead class="w-[150px]">Actions</TableHead>
                  </TableRow>
               </TableHeader>
               <TableBody>
                  <template v-if="store.loading">
                     <TableRow v-for="i in 5" :key="i">
                        <TableCell v-for="j in 6" :key="j">
                           <Skeleton class="h-6 w-full" />
                        </TableCell>
                     </TableRow>
                  </template>
                  <template v-else-if="store.contributions.length === 0">
                     <TableRow>
                        <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                           No contributions found
                        </TableCell>
                     </TableRow>
                  </template>
                  <template v-else>
                     <TableRow v-for="row in store.contributions" :key="row.ContributionID"
                        :class="{ 'bg-red-50': row.Deleted === 1 }">
                        <TableCell>
                           <div class="flex items-center gap-3">
                              <Avatar class="h-9 w-9 bg-blue-100 text-blue-600">
                                 <AvatarFallback class="text-sm font-medium">
                                    {{ (row.MbrFirstName?.[0] || '') + (row.MbrFamilyName?.[0] || '') }}
                                 </AvatarFallback>
                              </Avatar>
                              <div class="font-medium">{{ row.MbrFirstName }} {{ row.MbrFamilyName }}</div>
                           </div>
                        </TableCell>
                        <TableCell class="font-semibold text-green-600">
                           {{ formatCurrency(row.ContributionAmount) }}
                        </TableCell>
                        <TableCell>{{ formatDate(row.ContributionDate) }}</TableCell>
                        <TableCell>
                           <Badge variant="secondary">{{ row.ContributionTypeName || '-' }}</Badge>
                        </TableCell>
                       <TableCell>{{ row.PaymentMethodName || '-' }}</TableCell>
                        <TableCell>
                           <template v-if="row.Deleted === 1">
                              <div class="flex items-center gap-2">
                                 <Button size="sm" variant="outline" class="text-green-600"
                                    @click="handleRestore(row.ContributionID)">
                                    <RotateCcw class="w-4 h-4 mr-1" />
                                    Restore
                                 </Button>
                                 <Badge variant="destructive">Deleted</Badge>
                              </div>
                           </template>
                           <template v-else>
                              <div class="flex items-center gap-1">
                                 <Button size="icon" variant="ghost" @click="openViewModal(row.ContributionID)"
                                    title="View">
                                    <Eye class="w-4 h-4" />
                                 </Button>
                                 <Button size="icon" variant="ghost" @click="openReceiptModal(row.ContributionID)"
                                    title="Receipt">
                                    <Receipt class="w-4 h-4 text-green-600" />
                                 </Button>
                                 <Button size="icon" variant="ghost" @click="openEditModal(row)" title="Edit">
                                    <Pencil class="w-4 h-4 text-amber-600" />
                                 </Button>
                                 <Button size="icon" variant="ghost" @click="handleDelete(row.ContributionID)"
                                    title="Delete">
                                    <Trash2 class="w-4 h-4 text-red-600" />
                                 </Button>
                              </div>
                           </template>
                        </TableCell>
                     </TableRow>
                  </template>
               </TableBody>
            </Table>

            <!-- Pagination -->
            <div v-if="store.pagination.pages > 1" class="flex items-center justify-between p-4 border-t">
               <div class="text-sm text-muted-foreground">
                  Showing page {{ store.pagination.page }} of {{ store.pagination.pages }}
                  ({{ store.pagination.total }} total)
               </div>
               <div class="flex items-center gap-2">
                  <Button variant="outline" size="sm" :disabled="store.pagination.page <= 1"
                     @click="goToPage(store.pagination.page - 1)">
                     <ChevronLeft class="w-4 h-4" />
                  </Button>
                  <Button variant="outline" size="sm" :disabled="store.pagination.page >= store.pagination.pages"
                     @click="goToPage(store.pagination.page + 1)">
                     <ChevronRight class="w-4 h-4" />
                  </Button>
               </div>
            </div>
         </CardContent>
      </Card>

      <!-- Modals -->
      <AddContributionModal :open="showAddModal" :contribution="editingContribution" @close="showAddModal = false"
         @saved="handleSaved" />

      <ViewContributionModal :open="showViewModal" :contribution-id="viewingContributionId"
         @close="showViewModal = false" @edit="(c) => { showViewModal = false; openEditModal(c) }"
         @receipt="(id) => { showViewModal = false; openReceiptModal(id) }" />

      <ViewReceiptModal :open="showReceiptModal" :contribution-id="receiptContributionId"
         @close="showReceiptModal = false" />

      <ViewStatementModal :open="showStatementModal" :member-id="statementMemberId"
         @close="showStatementModal = false" />

      <ManageTypesModal :open="showTypesModal" @close="showTypesModal = false" />
   </div>
</template>
