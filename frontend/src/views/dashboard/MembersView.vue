<script setup lang="ts">
import { ref, onMounted, computed, watch, h } from 'vue'
import { 
  Users, 
  Search, 
  Plus, 
  RefreshCw, 
  MoreHorizontal, 
  Eye, 
  Pencil, 
  Trash2, 
  Filter,
  Download,
  FilterX,
  ChevronLeft,
  ChevronRight,
  PieChart,
  BarChart3,
  Printer,
  FileText as FileTextIcon
} from 'lucide-vue-next'
import { 
  Card, 
  CardContent, 
  CardHeader, 
  CardTitle, 
  CardDescription 
} from '@/components/ui/card'
import {
  useVueTable,
  getCoreRowModel,
  FlexRender,
  type ColumnDef
} from '@tanstack/vue-table'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
  DropdownMenuSeparator
} from '@/components/ui/dropdown-menu'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Skeleton } from '@/components/ui/skeleton'
import AddMemberModal from '@/components/dashboard/AddMemberModal.vue'
import ViewMemberModal from '@/components/dashboard/ViewMemberModal.vue'
import VueApexCharts from 'vue3-apexcharts'
import { useMembersStore } from '@/stores/members'
import { useSettingsStore } from '@/stores/settings'
import { Alerts } from '@/utils/alerts'
import api, { resolveUrl } from '@/services/api'
import dayjs from 'dayjs'
import * as XLSX from 'xlsx'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const membersStore = useMembersStore()
const settingsStore = useSettingsStore()

const searchQuery = ref('')
const statusFilter = ref('all')
const branchFilter = ref('all')
const isAddModalOpen = ref(false)
const isViewModalOpen = ref(false)
const selectedMemberId = ref<number | null>(null)
const selectedMemberData = ref<any | null>(null)

// Debounced search
let searchTimeout: any = null
watch(searchQuery, (val) => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    membersStore.setFilters({ search: val === '' ? '' : val })
  }, 500)
})

onMounted(() => {
  membersStore.fetchMembers(1)
  membersStore.fetchStats()
  membersStore.fetchLookupData()
})

const handleRefresh = () => {
  membersStore.fetchMembers(membersStore.pagination.page)
  membersStore.fetchStats()
}

const handleStatusChange = (val: any) => {
  const value = val === null ? 'all' : val
  statusFilter.value = value
  membersStore.setFilters({ status: value === 'all' ? '' : value })
}

const handleBranchChange = (val: any) => {
  const value = val === null ? 'all' : val
  branchFilter.value = value
  membersStore.setFilters({ branch_id: value === 'all' ? '' : value })
}

const resetFilters = () => {
  searchQuery.value = ''
  statusFilter.value = 'all'
  branchFilter.value = 'all'
  membersStore.setFilters({ search: '', status: '', branch_id: '' })
}

const handleViewMember = (id: number) => {
  selectedMemberId.value = id
  isViewModalOpen.value = true
}

const handleExportCSV = () => {
  if (!membersStore.members.length) return

  const data = membersStore.members.map(m => ({
    'ID': m.MbrUniqueID,
    'Name': `${m.MbrFirstName} ${m.MbrFamilyName}`,
    'Email': m.MbrEmailAddress,
    'Gender': m.MbrGender,
    'Phone': m.PrimaryPhone || 'N/A',
    'Branch': m.BranchName,
    'Status': m.MembershipStatusName,
    'Joined': dayjs(m.MbrRegistrationDate).format('YYYY-MM-DD')
  }))

  const worksheet = XLSX.utils.json_to_sheet(data)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Members')
  XLSX.writeFile(workbook, `church_members_${dayjs().format('YYYY-MM-DD')}.xlsx`)

  Alerts.success('Excel export downloaded')
}

const handleExportPDF = () => {
  if (!membersStore.members.length) return

  const doc = new jsPDF()
  const tableData = membersStore.members.map(m => [
    m.MbrUniqueID,
    `${m.MbrFirstName} ${m.MbrFamilyName}`,
    m.MbrGender,
    m.BranchName || 'N/A',
    m.MembershipStatusName,
    dayjs(m.MbrRegistrationDate).format('YYYY-MM-DD')
  ])

  doc.setFontSize(18)
  doc.setTextColor(0, 2, 138) // #00028a
  doc.text('Church Member Directory', 14, 22)

  doc.setFontSize(10)
  doc.setTextColor(100)
  doc.text(`Generated on ${dayjs().format('MMMM DD, YYYY HH:mm')}`, 14, 30)

  autoTable(doc, {
    head: [['ID', 'Name', 'Gender', 'Branch', 'Status', 'Joined']],
    body: tableData,
    startY: 35,
    styles: { fontSize: 8, cellPadding: 3 },
    headStyles: { fillColor: [0, 2, 138], textColor: 255 },
    alternateRowStyles: { fillColor: [245, 247, 250] }
  })

  doc.save(`church_members_${dayjs().format('YYYY-MM-DD')}.pdf`)
  Alerts.success('PDF export downloaded')
}

const handlePrintTable = () => {
  const printWindow = window.open('', '_blank')
  if (!printWindow) return

  const tableHtml = `
    <table style="width: 100%; border-collapse: collapse; font-family: sans-serif;">
      <thead>
        <tr style="background: #00028a; color: white;">
          <th style="padding: 10px; text-align: left;">ID</th>
          <th style="padding: 10px; text-align: left;">Name</th>
          <th style="padding: 10px; text-align: left;">Branch</th>
          <th style="padding: 10px; text-align: left;">Status</th>
          <th style="padding: 10px; text-align: left;">Joined</th>
        </tr>
      </thead>
      <tbody>
        ${membersStore.members.map(m => `
          <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;">${m.MbrUniqueID}</td>
            <td style="padding: 10px;">${m.MbrFirstName} ${m.MbrFamilyName}</td>
            <td style="padding: 10px;">${m.BranchName || 'N/A'}</td>
            <td style="padding: 10px;">${m.MembershipStatusName}</td>
            <td style="padding: 10px;">${dayjs(m.MbrRegistrationDate).format('YYYY-MM-DD')}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  `

  printWindow.document.write(`
    <html>
      <head>
        <title>Member List Printout</title>
        <style>
          body { padding: 40px; }
          h1 { color: #00028a; }
          .footer { margin-top: 20px; font-size: 12px; color: #666; }
          @media print {
            body { padding: 0; }
          }
        </style>
      </head>
      <body>
        <h1>Member Directory</h1>
        <p>Total Members: ${membersStore.pagination.total}</p>
        ${tableHtml}
        <div class="footer">Printed on ${dayjs().format('MMMM DD, YYYY HH:mm')}</div>
        <script>
          window.onload = () => { window.print(); setTimeout(() => window.close(), 500); };
        <\/script>
      </body>
    </html>
  `)
  printWindow.document.close()
}

const columns: ColumnDef<any>[] = [
  {
    accessorKey: 'MbrFirstName',
    header: 'Member',
    cell: ({ row }) => {
      const m = row.original
      return h('div', { class: 'flex items-center gap-3' }, [
        h('div', { class: 'h-9 w-9 rounded-full overflow-hidden border bg-slate-50' }, [
          h('img', {
            src: resolveUrl(m.MbrProfilePicture) || `https://api.dicebear.com/7.x/initials/svg?seed=${m.MbrFirstName}`,
            class: 'h-full w-full object-cover'
          })
        ]),
        h('div', { class: 'flex flex-col' }, [
          h('span', { class: 'font-semibold text-slate-900' }, `${m.MbrFirstName} ${m.MbrFamilyName}`),
          h('span', { class: 'text-xs text-muted-foreground truncate max-w-[150px]' }, m.MbrEmailAddress)
        ])
      ])
    }
  },
  {
    accessorKey: 'MbrUniqueID',
    header: 'Unique ID',
    cell: ({ row }) => h('code', {
      class: 'text-xs bg-slate-100 px-1.5 py-0.5 rounded text-slate-600 font-mono'
    }, row.original.MbrUniqueID)
  },
  {
    accessorKey: 'BranchName',
    header: 'Branch',
    cell: ({ row }) => row.original.BranchName || 'N/A'
  },
  {
    accessorKey: 'MbrRegistrationDate',
    header: 'Joined',
    cell: ({ row }) => dayjs(row.original.MbrRegistrationDate).format('YYYY-MM-DD')
  },
  {
    accessorKey: 'MembershipStatusName',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.original.MembershipStatusName
      const badgeClass = getStatusBadge(status)
      return h('span', {
        class: `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${badgeClass}`
      }, status)
    }
  }
]

const table = useVueTable({
  get data() { return membersStore.members },
  columns,
  getCoreRowModel: getCoreRowModel(),
})

// Charts Data
const genderChartOptions = computed(() => ({
  chart: { type: 'donut' as const, fontFamily: 'Inter, sans-serif' },
  labels: (Array.isArray(membersStore.stats?.gender_distribution) ? membersStore.stats.gender_distribution : []).map((d: any) => d.gender),
  colors: ['#00028a', '#e5a100', '#10b981'],
  legend: { position: 'bottom' as const },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: { size: '70%' }
    }
  }
}))

const genderChartSeries = computed(() => 
  (Array.isArray(membersStore.stats?.gender_distribution) ? membersStore.stats.gender_distribution : []).map((d: any) => parseInt(d.count))
)

const ageChartOptions = computed(() => ({
  chart: { type: 'bar' as const, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
  xaxis: {
    categories: (Array.isArray(membersStore.stats?.age_distribution) ? membersStore.stats.age_distribution : []).map((d: any) => d.group),
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  colors: ['#00028a'],
  dataLabels: { enabled: false },
  grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
  plotOptions: {
    bar: { borderRadius: 4, columnWidth: '60%' }
  }
}))

const ageChartSeries = computed(() => [{
  name: 'Count',
  data: (Array.isArray(membersStore.stats?.age_distribution) ? membersStore.stats.age_distribution : []).map((d: any) => parseInt(d.count))
}])

const getStatusBadge = (status: string) => {
  switch (status.toLowerCase()) {
    case 'active': return 'bg-green-100 text-green-700'
    case 'inactive': return 'bg-gray-100 text-gray-700'
    case 'deceased': return 'bg-red-100 text-red-700'
    case 'transferred': return 'bg-blue-100 text-blue-700'
    default: return 'bg-slate-100 text-slate-700'
  }
}
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-[#00028a]">Members Directory</h1>
        <p class="text-muted-foreground">Manage and track your church congregation records.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button variant="outline" size="sm" @click="handleRefresh">
          <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': membersStore.loading }" />
          Refresh
        </Button>
        <Button class="bg-[#00028a] hover:bg-[#00026d]" @click="isAddModalOpen = true">
          <Plus class="w-4 h-4 mr-2" />
          Add Member
        </Button>
      </div>
    </div>

    <!-- Modals -->
   <AddMemberModal :open="isAddModalOpen" :member-data="selectedMemberData" @close="() => {
      isAddModalOpen = false
      selectedMemberData = null
    }" />
    <ViewMemberModal :open="isViewModalOpen" :member-id="selectedMemberId" @close="isViewModalOpen = false"
@edit="(m: any) => {
      isViewModalOpen = false;
      selectedMemberData = m;
      isAddModalOpen = true;
    }" />

    <!-- Stats Section -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-12">
      <Card class="lg:col-span-4 border-none shadow-sm h-fit">
        <CardHeader class="pb-2">
          <CardTitle class="text-sm font-semibold flex items-center gap-2">
            <PieChart class="w-4 h-4 text-[#00028a]" />
            Gender Distribution
          </CardTitle>
        </CardHeader>
        <CardContent>
           <div v-if="!membersStore.stats" class="h-[200px] flex items-center justify-center">
              <Skeleton class="h-full w-full rounded-lg" />
           </div>
           <VueApexCharts
             v-else
             type="donut"
             height="200"
             :options="genderChartOptions"
             :series="genderChartSeries"
           />
        </CardContent>
      </Card>

      <Card class="lg:col-span-8 border-none shadow-sm h-fit">
        <CardHeader class="pb-2">
          <CardTitle class="text-sm font-semibold flex items-center gap-2">
            <BarChart3 class="w-4 h-4 text-[#00028a]" />
            Age Distribution
          </CardTitle>
        </CardHeader>
        <CardContent>
           <div v-if="!membersStore.stats" class="h-[200px] flex items-center justify-center">
              <Skeleton class="h-full w-full rounded-lg" />
           </div>
           <VueApexCharts
             v-else
             type="bar"
             height="200"
             :options="ageChartOptions"
             :series="ageChartSeries"
           />
        </CardContent>
      </Card>
    </div>

    <!-- Members Table Section -->
    <Card class="border-none shadow-sm overflow-hidden">
      <CardHeader class="border-b bg-white">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-2">
            <CardTitle>Member Directory</CardTitle>
            <Badge variant="secondary" class="bg-[#00028a]/5 text-[#00028a]">
              {{ membersStore.pagination.total }} Total
            </Badge>
          </div>
          <div class="flex items-center gap-2">
           <Button variant="outline" size="sm" @click="handlePrintTable" class="text-xs">
              <Printer class="w-3 h-4 mr-1.5" /> Print
            </Button>
            <Button variant="outline" size="sm" @click="handleExportPDF" class="text-xs">
              <FileTextIcon class="w-3 h-4 mr-1.5" /> PDF
            </Button>
            <Button variant="outline" size="sm" @click="handleExportCSV" class="text-xs">
              <Download class="w-3 h-4 mr-1.5" /> CSV
            </Button>
          </div>
        </div>
      </CardHeader>
      
      <!-- Filters -->
      <div class="p-4 bg-gray-50/50 border-b flex flex-wrap gap-4 items-center">
        <div class="relative w-full max-w-sm">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <Input 
            v-model="searchQuery"
            placeholder="Search by name, email or ID..." 
            class="pl-10 h-10 bg-white"
          />
        </div>
        
        <Select v-model="statusFilter" @update:model-value="handleStatusChange">
          <SelectTrigger class="w-[180px] h-10 bg-white">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Statuses</SelectItem>
            <SelectItem 
              v-for="status in membersStore.lookupData?.membership_statuses" 
              :key="status.id" 
              :value="status.name"
            >
              {{ status.name }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Select v-model="branchFilter" @update:model-value="handleBranchChange">
          <SelectTrigger class="w-[180px] h-10 bg-white">
            <SelectValue placeholder="Branch" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Branches</SelectItem>
            <SelectItem 
              v-for="branch in membersStore.lookupData?.branches" 
              :key="branch.id" 
              :value="branch.id.toString()"
            >
              {{ branch.name }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Button variant="ghost" size="icon" @click="resetFilters" class="text-orange-600 hover:text-orange-700 hover:bg-orange-50" title="Reset Filters">
          <FilterX class="w-5 h-5" />
        </Button>
      </div>

      <CardContent class="p-0">
        <div class="relative overflow-x-auto">
          <Table>
            <TableHeader class="bg-gray-50/50">
             <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <TableHead v-for="header in headerGroup.headers" :key="header.id" class="font-bold text-[#00028a]">
                  <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                    :props="header.getContext()" />
                </TableHead>
                <TableHead class="w-12"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <template v-if="membersStore.loading">
                <TableRow v-for="i in 5" :key="i">
                 <TableCell v-for="j in 6" :key="j">
                    <Skeleton class="h-8 w-full" />
                  </TableCell>
                </TableRow>
              </template>
             <template v-else-if="!table.getRowModel().rows.length">
                <TableRow>
                 <TableCell colspan="6" class="h-32 text-center text-muted-foreground">
                    No members found matching your criteria.
                  </TableCell>
                </TableRow>
              </template>
              <template v-else>
               <TableRow v-for="row in table.getRowModel().rows" :key="row.id"
                  class="group hover:bg-[#00028a]/5 transition-colors">

                  <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                  </TableCell>

                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="icon" class="hover:bg-white shadow-none">
                          <MoreHorizontal class="w-4 h-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" class="w-40">
                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                       <DropdownMenuItem @click="handleViewMember(row.original.MbrID)">
                          <Eye class="w-4 h-4 mr-2" /> View Profile
                        </DropdownMenuItem>
                       <DropdownMenuItem @click="() => {
                          selectedMemberData = row.original
                          isAddModalOpen = true
                        }">
                          <Pencil class="w-4 h-4 mr-2" /> Edit Member
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                       <DropdownMenuItem @click="membersStore.deleteMember(row.original.MbrID)"
                          class="text-red-600 focus:text-red-600">
                          <Trash2 class="w-4 h-4 mr-2" /> Delete
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </template>
            </TableBody>
          </Table>
        </div>
      </CardContent>

      <!-- Pagination -->
      <div class="p-4 border-t flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <p class="text-sm text-muted-foreground">
          Showing <strong>{{ membersStore.members.length }}</strong> of <strong>{{ membersStore.pagination.total }}</strong> members
        </p>
        <div class="flex items-center gap-2">
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="membersStore.pagination.page === 1 || membersStore.loading"
            @click="membersStore.fetchMembers(membersStore.pagination.page - 1)"
          >
            <ChevronLeft class="w-4 h-4 mr-1" />
            Previous
          </Button>
          <div class="flex items-center gap-1 mx-2">
             <span class="text-sm font-medium">Page {{ membersStore.pagination.page }}</span>
             <span class="text-sm text-muted-foreground">of {{ Math.ceil(membersStore.pagination.total / membersStore.pagination.limit) }}</span>
          </div>
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="membersStore.pagination.page >= Math.ceil(membersStore.pagination.total / membersStore.pagination.limit) || membersStore.loading"
            @click="membersStore.fetchMembers(membersStore.pagination.page + 1)"
          >
            Next
            <ChevronRight class="w-4 h-4 ml-1" />
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<style scoped>
/* Any custom styles for the members table */
</style>
