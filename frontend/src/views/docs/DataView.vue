<script setup lang="ts">
/**
 * DataView.vue - Data Display Components Documentation
 * 
 * Comprehensive demonstration of all data display components in the
 * AliveCHMS brutalist-lite design system.
 * 
 * Components covered:
 * - ChTable: Full-featured data tables with sorting, pagination, selection, export
 * - ChStatCard: Key metrics with trends and contextual icons
 * - ChDataList: Structured label/value pairs for record details
 * - ChChart: Chart.js integration with Chart.js for data visualization
 * - ChPagination: Standalone pagination controls
 * 
 * @requires lucide-vue-next for icons
 */

import { ref } from 'vue'
import {
  ChTable,
  ChStatCard,
  ChDataList,
  ChChart,
  ChPagination,
  ChButton,
  ChCard,
  ChInput,
  ChSpinner,
} from '@/design-system'

// Define DataListItem locally since it's not exported from design-system
interface DataListItem {
  label: string
  value: string | number | null | undefined
  type?: 'text' | 'badge' | 'slot'
  variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
  slotName?: string
  fullWidth?: boolean
}

import {
  // General icons
  Users,
  Calendar,
  TrendingDown,
  Eye,
  Edit,
  Trash2,
  Download,
  Search,
  Filter,
  RefreshCw,
  Plus,
  Printer,
  FileText,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
  LayoutGrid,
  List,
  UserCheck,
  UserX,
  Heart,
  Baby,
  Banknote,
  PieChart,
  BarChart3,
  LineChart,
  Code,
} from 'lucide-vue-next'

// =============================================================================
// SECTION 1: ChTable - Full-Featured Data Tables
// =============================================================================

// ─── Member Directory Table ───────────────────────────────────────────────────

interface Member {
  id: number
  name: string
  email: string
  phone: string
  membershipType: string
  status: 'active' | 'inactive' | 'pending'
  joinDate: string
  group: string
  avatar?: string
}

const members = ref<Member[]>([
  { id: 1, name: 'Pastor John Mensah', email: 'pastor.john@gracefellowship.gh', phone: '+233 24 123 4567', membershipType: 'Elder', status: 'active', joinDate: '2018-03-15', group: 'Pastoral Team' },
  { id: 2, name: 'Grace Adjei', email: 'grace.adjei@email.com', phone: '+233 24 234 5678', membershipType: 'Member', status: 'active', joinDate: '2020-06-22', group: "Women's Fellowship" },
  { id: 3, name: 'Michael Owusu', email: 'michael.owusu@email.com', phone: '+233 24 345 6789', membershipType: 'Deacon', status: 'active', joinDate: '2015-09-10', group: 'Deacon Board' },
  { id: 4, name: 'Ama Boateng', email: 'ama.boateng@email.com', phone: '+233 24 456 7890', membershipType: 'Member', status: 'active', joinDate: '2021-01-08', group: 'Choir' },
  { id: 5, name: 'Kwame Asante', email: 'kwame.asante@email.com', phone: '+233 24 567 8901', membershipType: 'Member', status: 'pending', joinDate: '2024-11-15', group: 'Youth Ministry' },
  { id: 6, name: 'Abena Serwaa', email: 'abena.serwaa@email.com', phone: '+233 24 678 9012', membershipType: 'Member', status: 'inactive', joinDate: '2019-04-20', group: "Women's Fellowship" },
  { id: 7, name: 'Yaw Darko', email: 'yaw.darko@email.com', phone: '+233 24 789 0123', membershipType: 'Member', status: 'active', joinDate: '2022-08-12', group: 'Ushering Team' },
  { id: 8, name: 'Akua Mensah', email: 'akua.mensah@email.com', phone: '+233 24 890 1234', membershipType: 'Member', status: 'active', joinDate: '2023-02-28', group: "Children's Ministry" },
  { id: 9, name: 'Kofi Annan', email: 'kofi.annan@email.com', phone: '+233 24 901 2345', membershipType: 'Elder', status: 'active', joinDate: '2010-05-01', group: 'Elder Board' },
  { id: 10, name: 'Efua Powell', email: 'efua.powell@email.com', phone: '+233 24 012 3456', membershipType: 'Member', status: 'active', joinDate: '2024-01-10', group: 'Media Team' },
  { id: 11, name: 'Samuel Addo', email: 'samuel.addo@email.com', phone: '+233 24 123 4568', membershipType: 'Member', status: 'active', joinDate: '2022-11-05', group: 'Worship Team' },
  { id: 12, name: 'Akosua Nyame', email: 'akosua.nyame@email.com', phone: '+233 24 234 5679', membershipType: 'Member', status: 'pending', joinDate: '2024-12-01', group: 'Youth Ministry' },
])

// Member table columns definition
const memberColumns = [
  { key: 'name', label: 'Name', sortable: true, type: 'text' as const },
  { key: 'email', label: 'Email', sortable: true, type: 'text' as const },
  { key: 'phone', label: 'Phone', sortable: false, type: 'text' as const },
  {
    key: 'membershipType', label: 'Type', sortable: true, type: 'badge' as const, badgeVariant: (v: unknown) => {
      const val = v as string
      if (val === 'Elder') return 'primary' as const
      if (val === 'Deacon') return 'success' as const
      return 'default' as const
    }
  },
  {
    key: 'status', label: 'Status', sortable: true, type: 'badge' as const, badgeVariant: (v: unknown) => {
      const val = v as string
      if (val === 'active') return 'success' as const
      if (val === 'pending') return 'warning' as const
      return 'default' as const
    }
  },
  { key: 'group', label: 'Group', sortable: true, type: 'text' as const },
  { key: 'actions', label: '', sortable: false, type: 'slot' as const, exportable: false, width: '140px' },
]

const memberPage = ref(1)
const memberPageSize = ref(5)
const memberTotal = ref(members.value.length)

// ─── Contribution Records Table ──────────────────────────────────────────────

interface Contribution {
  id: number
  receiptNo: string
  memberName: string
  date: string
  type: 'Tithe' | 'Offering' | 'Building Fund' | 'Missions' | 'Thanksgiving'
  amount: number
  paymentMethod: 'Cash' | 'Mobile Money' | 'Bank Transfer'
  status: 'received' | 'pending' | 'refunded'
}

const contributions = ref<Contribution[]>([
  { id: 1, receiptNo: 'CR-2024-001', memberName: 'Pastor John Mensah', date: '2024-12-01', type: 'Tithe', amount: 5000, paymentMethod: 'Bank Transfer', status: 'received' },
  { id: 2, receiptNo: 'CR-2024-002', memberName: 'Grace Adjei', date: '2024-12-01', type: 'Tithe', amount: 2000, paymentMethod: 'Mobile Money', status: 'received' },
  { id: 3, receiptNo: 'CR-2024-003', memberName: 'Michael Owusu', date: '2024-12-01', type: 'Offering', amount: 500, paymentMethod: 'Cash', status: 'received' },
  { id: 4, receiptNo: 'CR-2024-004', memberName: 'Ama Boateng', date: '2024-12-01', type: 'Building Fund', amount: 10000, paymentMethod: 'Bank Transfer', status: 'received' },
  { id: 5, receiptNo: 'CR-2024-005', memberName: 'Anonymous', date: '2024-12-02', type: 'Thanksgiving', amount: 2500, paymentMethod: 'Cash', status: 'received' },
  { id: 6, receiptNo: 'CR-2024-006', memberName: 'Kwame Asante', date: '2024-12-02', type: 'Missions', amount: 1500, paymentMethod: 'Mobile Money', status: 'pending' },
  { id: 7, receiptNo: 'CR-2024-007', memberName: 'Abena Serwaa', date: '2024-12-03', type: 'Tithe', amount: 3000, paymentMethod: 'Bank Transfer', status: 'received' },
  { id: 8, receiptNo: 'CR-2024-008', memberName: 'Kofi Annan', date: '2024-12-03', type: 'Offering', amount: 750, paymentMethod: 'Cash', status: 'received' },
])

const contributionColumns = [
  { key: 'receiptNo', label: 'Receipt No.', sortable: true, type: 'text' as const },
  { key: 'memberName', label: 'Member', sortable: true, type: 'text' as const },
  { key: 'date', label: 'Date', sortable: true, type: 'text' as const },
  {
    key: 'type', label: 'Type', sortable: true, type: 'badge' as const, badgeVariant: (v: unknown) => {
      const val = v as string
      if (val === 'Tithe') return 'primary' as const
      if (val === 'Building Fund') return 'success' as const
      if (val === 'Missions') return 'info' as const
      return 'default' as const
    }
  },
  { key: 'amount', label: 'Amount', sortable: true, type: 'text' as const, align: 'right' as const },
  { key: 'paymentMethod', label: 'Method', sortable: false, type: 'badge' as const },
  {
    key: 'status', label: 'Status', sortable: true, type: 'badge' as const, badgeVariant: (v: unknown) => {
      const val = v as string
      if (val === 'received') return 'success' as const
      if (val === 'pending') return 'warning' as const
      return 'danger' as const
    }
  },
]

// ─── Event Attendance Table ───────────────────────────────────────────────────

interface Attendance {
  id: number
  eventName: string
  date: string
  expected: number
  actual: number
  percentage: number
  recordedBy: string
}

const attendanceRecords = ref<Attendance[]>([
  { id: 1, eventName: 'Sunday Worship - Week 1', date: '2024-12-01', expected: 300, actual: 285, percentage: 95, recordedBy: 'David Kwaku' },
  { id: 2, eventName: 'Sunday Worship - Week 2', date: '2024-12-08', expected: 300, actual: 292, percentage: 97, recordedBy: 'David Kwaku' },
  { id: 3, eventName: 'Wednesday Bible Study', date: '2024-12-04', expected: 120, actual: 98, percentage: 82, recordedBy: 'Sarah Adjei' },
  { id: 4, eventName: 'Friday Prayer Meeting', date: '2024-12-06', expected: 80, actual: 72, percentage: 90, recordedBy: 'Samuel Mensah' },
  { id: 5, eventName: 'Youth Service', date: '2024-12-07', expected: 150, actual: 142, percentage: 95, recordedBy: 'Kojo Asante' },
  { id: 6, eventName: "Children's Church", date: '2024-12-01', expected: 80, actual: 76, percentage: 95, recordedBy: 'Akua Serwaa' },
])

const attendanceColumns = [
  { key: 'eventName', label: 'Event', sortable: true, type: 'text' as const },
  { key: 'date', label: 'Date', sortable: true, type: 'text' as const },
  { key: 'expected', label: 'Expected', sortable: true, type: 'text' as const, align: 'center' as const },
  { key: 'actual', label: 'Actual', sortable: true, type: 'text' as const, align: 'center' as const },
  {
    key: 'percentage', label: 'Attendance %', sortable: true, type: 'badge' as const, badgeVariant: (v: unknown) => {
      const val = v as number
      if (val >= 90) return 'success' as const
      if (val >= 70) return 'warning' as const
      return 'danger' as const
    }
  },
  { key: 'recordedBy', label: 'Recorded By', sortable: false, type: 'text' as const },
]

// ─── Table Actions ────────────────────────────────────────────────────────────

function handleMemberSort(key: string, direction: 'asc' | 'desc' | null) {
  console.log('Sort:', key, direction)
}

function handleMemberRowClick(member: Member) {
  console.log('View member:', member)
}

function editMember(member: Member) {
  console.log('Edit member:', member)
}

function deleteMember(member: Member) {
  console.log('Delete member:', member)
}

// Simulate server-side loading
const isLoadingData = ref(false)
async function simulateServerLoad() {
  isLoadingData.value = true
  await new Promise(resolve => setTimeout(resolve, 1500))
  isLoadingData.value = false
}

// Empty state data
const emptyTableData = ref<Member[]>([])
const emptyTableColumns = [
  { key: 'name', label: 'Name', sortable: true, type: 'text' as const },
  { key: 'email', label: 'Email', sortable: true, type: 'text' as const },
]

// =============================================================================
// SECTION 2: ChStatCard - Key Metrics Display
// =============================================================================

// ─── Dashboard Stats ─────────────────────────────────────────────────────────

const dashboardStats = ref([
  {
    id: 1,
    label: 'Total Members',
    value: '1,248',
    trend: 12.4,
    trendLabel: 'vs last month',
    variant: 'primary' as const,
    icon: Users,
    loading: false,
  },
  {
    id: 2,
    label: 'Weekly Attendance',
    value: '292',
    trend: 8.2,
    trendLabel: 'vs last Sunday',
    variant: 'success' as const,
    icon: UserCheck,
    loading: false,
  },
  {
    id: 3,
    label: 'Monthly Contributions',
    value: 'GH₵ 84,500',
    trend: 15.7,
    trendLabel: 'vs last month',
    variant: 'warning' as const,
    icon: Banknote,
    loading: false,
  },
  {
    id: 4,
    label: 'New Conversions',
    value: '23',
    trend: -2.1,
    trendLabel: 'vs last month',
    variant: 'info' as const,
    icon: Heart,
    loading: false,
  },
])

// Stat card variants
const statCardVariants = ['default', 'primary', 'success', 'warning', 'danger', 'info'] as const
const variantLabels = ['Default', 'Primary', 'Success', 'Warning', 'Danger', 'Info']

// Loading state demo
const statsLoading = ref(false)
function toggleStatsLoading() {
  statsLoading.value = !statsLoading.value
}

// =============================================================================
// SECTION 3: ChDataList - Structured Data Display
// =============================================================================

// ─── Member Profile DataList ─────────────────────────────────────────────────

const memberProfileItems = ref<DataListItem[]>([
  { label: 'Full Name', value: 'Pastor John Mensah' },
  { label: 'Email Address', value: 'pastor.john@gracefellowship.gh' },
  { label: 'Phone Number', value: '+233 24 123 4567' },
  { label: 'Date of Birth', value: '15 March 1975' },
  { label: 'Membership Type', value: 'Elder', type: 'badge', variant: 'primary' },
  { label: 'Status', value: 'Active', type: 'badge', variant: 'success' },
  { label: 'Join Date', value: '15 March 2018' },
  { label: 'Group', value: "Pastoral Team" },
  { label: 'Address', value: 'Plot 24, East Legon, Accra', fullWidth: true },
])

// ─── Event Info DataList ─────────────────────────────────────────────────────

const eventInfoItems = ref<DataListItem[]>([
  { label: 'Event Name', value: 'Christmas Carol Service 2024' },
  { label: 'Date', value: '22 December 2024' },
  { label: 'Time', value: '6:00 PM - 9:00 PM' },
  { label: 'Location', value: 'Main Sanctuary', fullWidth: true },
  { label: 'Organizer', value: 'Elder Grace Adjei' },
  { label: 'Expected Attendance', value: '500' },
  { label: 'Status', value: 'Confirmed', type: 'badge', variant: 'success' },
])

// ─── Contribution Receipt DataList ───────────────────────────────────────────

const receiptItems = ref<DataListItem[]>([
  { label: 'Receipt Number', value: 'CR-2024-001' },
  { label: 'Date', value: '01 December 2024' },
  { label: 'Member', value: 'Pastor John Mensah' },
  { label: 'Type', value: 'Tithe', type: 'badge', variant: 'primary' },
  { label: 'Amount', value: 'GH₵ 5,000.00' },
  { label: 'Payment Method', value: 'Bank Transfer' },
  { label: 'Reference', value: 'BANK-TRF-20241201-001' },
  { label: 'Status', value: 'Received', type: 'badge', variant: 'success' },
  { label: 'Notes', value: 'Monthly tithe for December 2024', fullWidth: true },
])

// ─── Family Summary DataList ──────────────────────────────────────────────────

const familyItems = ref<DataListItem[]>([
  { label: 'Family Name', value: 'Mensah Family' },
  { label: 'Head of Family', value: 'Papa Kwame Mensah' },
  { label: 'Contact', value: '+233 24 000 0000' },
  { label: 'Address', value: 'House No. 15, Teshie-Nungua', fullWidth: true },
  { label: 'Members', value: '5' },
  { label: 'Active Members', value: '4', type: 'badge', variant: 'success' },
  { label: 'Children (Under 18)', value: '2' },
  { label: 'Family Group', value: 'East Assembly' },
])

// DataList layout modes
const dataListLayout = ref<'horizontal' | 'vertical'>('horizontal')

// Empty state data
const emptyDataListItems = ref<DataListItem[]>([])

// =============================================================================
// SECTION 4: ChChart - Data Visualization
// =============================================================================

// ─── Attendance Trends (Line Chart) ──────────────────────────────────────────

const attendanceChartData = ref({
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  datasets: [
    {
      label: 'Sunday Worship',
      data: [245, 260, 238, 275, 289, 292, 285, 298, 310, 305, 298, 315],
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      fill: true,
      tension: 0.4,
    },
    {
      label: 'Bible Study',
      data: [95, 102, 98, 110, 108, 115, 112, 118, 105, 120, 122, 125],
      borderColor: 'rgb(16, 185, 129)',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      fill: true,
      tension: 0.4,
    },
  ],
})

// ─── Contributions by Category (Bar Chart) ───────────────────────────────────

const contributionsChartData = ref({
  labels: ['Tithe', 'Offering', 'Building Fund', 'Missions', 'Thanksgiving', 'Youth', 'Children'],
  datasets: [
    {
      label: 'This Month',
      data: [45000, 12000, 25000, 8000, 5000, 3500, 2800],
      backgroundColor: 'rgba(59, 130, 246, 0.8)',
    },
    {
      label: 'Last Month',
      data: [42000, 11000, 30000, 7500, 4500, 3200, 2500],
      backgroundColor: 'rgba(209, 213, 219, 0.8)',
    },
  ],
})

// ─── Membership Distribution (Doughnut Chart) ─────────────────────────────────

const membershipChartData = ref({
  labels: ['Regular Members', 'Youth', 'Children', 'Elders', 'Deacons'],
  datasets: [
    {
      data: [580, 220, 180, 45, 28],
      backgroundColor: [
        'rgba(59, 130, 246, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(245, 158, 11, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(236, 72, 153, 0.8)',
      ],
      borderWidth: 2,
      borderColor: '#ffffff',
    },
  ],
})

// ─── Growth Over Time (Area Chart) ────────────────────────────────────────────

const growthChartData = ref({
  labels: ['2020', '2021', '2022', '2023', '2024'],
  datasets: [
    {
      label: 'Total Members',
      data: [650, 780, 920, 1080, 1248],
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.3)',
      fill: true,
      tension: 0.4,
    },
    {
      label: 'Active Members',
      data: [580, 720, 850, 980, 1150],
      borderColor: 'rgb(16, 185, 129)',
      backgroundColor: 'rgba(16, 185, 129, 0.3)',
      fill: true,
      tension: 0.4,
    },
  ],
})

// ─── Weekly Giving Pattern (Bar Chart) ───────────────────────────────────────

const weeklyGivingData = ref({
  labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
  datasets: [
    {
      label: 'Tithe',
      data: [12000, 11500, 12500, 11800, 13000],
      backgroundColor: 'rgba(59, 130, 246, 0.8)',
    },
    {
      label: 'Offering',
      data: [3500, 3200, 3800, 3400, 4100],
      backgroundColor: 'rgba(16, 185, 129, 0.8)',
    },
    {
      label: 'Special',
      data: [5000, 2000, 8000, 3000, 12000],
      backgroundColor: 'rgba(245, 158, 11, 0.8)',
    },
  ],
})

// ─── Ministry Breakdown (Pie Chart) ───────────────────────────────────────────

const ministryChartData = ref({
  labels: ["Children's Ministry", 'Youth Ministry', "Women's Fellowship", "Men's Fellowship", 'Worship Team', 'Other'],
  datasets: [
    {
      data: [25, 22, 18, 12, 15, 8],
      backgroundColor: [
        'rgba(245, 158, 11, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(236, 72, 153, 0.8)',
        'rgba(59, 130, 246, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(156, 163, 175, 0.8)',
      ],
    },
  ],
})

// Chart loading state
const chartLoading = ref(false)

// Chart options
const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
    },
    title: {
      display: false,
    },
  },
}

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
    },
  },
}

const doughnutChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right' as const,
    },
  },
}

// =============================================================================
// SECTION 5: ChPagination - Standalone Pagination
// =============================================================================

// ─── Basic Pagination ─────────────────────────────────────────────────────────

const basicPage = ref(1)
const basicTotal = ref(248)
const basicPageSize = ref(10)

// ─── With Page Size Selector ─────────────────────────────────────────────────

const pageSizePage = ref(1)
const pageSizeTotal = ref(500)
const pageSizeOptions = [10, 25, 50, 100]
const selectedPageSize = ref(25)

// ─── Simple Mode (Prev/Next Only) ───────────────────────────────────────────

const simplePage = ref(1)
const simpleTotal = ref(50)
const simplePageSize = ref(10)

// ─── Compact Mode ────────────────────────────────────────────────────────────

const compactPage = ref(1)
const compactTotal = ref(100)

// ─── With Jump to Page ────────────────────────────────────────────────────────

const jumpPage = ref(1)
const jumpTotal = ref(500)
const jumpPageSize = ref(20)
const jumpInput = ref('')

// =============================================================================
// SECTION 6: Code Examples
// =============================================================================

// ChTable code example
const tableCodeExample = `
// Basic Table
<ChTable
  :columns="columns"
  :rows="members"
  :total="totalCount"
  :page="page"
  :page-size="10"
  :selectable="true"
  :exportable="true"
  title="Member Directory"
  @sort="onSort"
>
  <template #cell-actions="{ row }">
    <ChButton size="sm" variant="ghost" @click="edit(row)">
      <Edit :size="14" />
    </ChButton>
  </template>
</ChTable>

// Column Definition
const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'status', label: 'Status', type: 'badge', 
    badgeVariant: v => v === 'Active' ? 'success' : 'default' },
  { key: 'actions', label: '', type: 'slot', exportable: false },
]
`

// ChStatCard code example
const statCardCodeExample = `
// Basic Stat Card
<ChStatCard
  label="Total Members"
  value="1,248"
  :trend="12.4"
  trend-label="vs last month"
  variant="primary"
>
  <template #icon>
    <Users :size="24" />
  </template>
</ChStatCard>

// Stat Card Grid
<div class="stats-grid">
  <ChStatCard v-for="stat in stats" 
    :key="stat.id"
    :label="stat.label"
    :value="stat.value"
    :trend="stat.trend"
    :variant="stat.variant"
  />
</div>
`

// ChDataList code example
const dataListCodeExample = `
// Basic DataList
<ChDataList
  :items="memberProfileItems"
  layout="horizontal"
  empty-text="No information available"
/>

// With Custom Value Slot
<ChDataList :items="profileItems">
  <template #value-avatar="{ item }">
    <ChAvatar :src="item.value" :name="member.name" size="sm" />
  </template>
</ChDataList>
`

// ChChart code example
const chartCodeExample = `
// Line Chart
<ChChart
  type="line"
  :data="attendanceData"
  :options="chartOptions"
  height="300"
/>

// Bar Chart
<ChChart
  type="bar"
  :data="contributionsData"
  height="350"
/>

// Doughnut Chart
<ChChart
  type="doughnut"
  :data="membershipData"
  :options="{ plugins: { legend: { position: 'right' } } }"
  height="300"
/>
`

// ChPagination code example
const paginationCodeExample = `
// Basic Pagination
<ChPagination
  :page="currentPage"
  :total="totalItems"
  :page-size="20"
  @update:page="currentPage = $event"
/>

// With v-model
<ChPagination
  v-model:page="page"
  :total="500"
  :page-size="25"
/>

// Compact Mode
<ChPagination
  v-model:page="page"
  :total="total"
  compact
/>
`

// =============================================================================
// SECTION 7: Interactive Features
// =============================================================================

const showCodeExample = ref<string | null>(null)

function toggleCode(example: string) {
  showCodeExample.value = showCodeExample.value === example ? null : example
}
</script>

<template>
  <div class="docs-container">
    <!-- Page Header -->
    <header class="docs-header">
      <div class="header-content">
        <h1 class="docs-title">Data Display Components</h1>
        <p class="docs-subtitle">
          Comprehensive components for displaying, organizing, and visualizing data
          in your church management system.
        </p>
      </div>
      <div class="header-actions">
        <ChButton variant="outline" size="sm" @click="simulateServerLoad">
          <RefreshCw :size="14" :class="{ 'animate-spin': isLoadingData }" />
          Reload Data
        </ChButton>
      </div>
    </header>

    <!-- =======================================================================
    SECTION 1: ChTable - Full-Featured Data Tables
    ======================================================================== -->
    <section class="docs-section">
      <div class="section-header">
        <div class="section-info">
          <h2 class="section-title">
            <LayoutGrid :size="20" class="section-icon" />
            ChTable
          </h2>
          <p class="section-description">
            A full-featured, accessible data table with sorting, row selection,
            pagination, loading states, empty states, and built-in export functionality.
          </p>
        </div>
        <ChButton variant="ghost" size="sm" @click="toggleCode('table')">
          <Code :size="14" />
          {{ showCodeExample === 'table' ? 'Hide' : 'Show' }} Code
        </ChButton>
      </div>

      <!-- Basic Table Demo -->
      <ChCard>
        <template #header>
          <div class="card-header-content">
            <h3 class="card-title">Member Directory</h3>
            <span class="badge-count">{{ members.length }} members</span>
          </div>
        </template>

        <!-- Toolbar -->
        <div class="table-toolbar">
          <div class="toolbar-left">
            <ChInput placeholder="Search members..." size="sm" class="search-input">
              <template #prefix>
                <Search :size="14" />
              </template>
            </ChInput>
          </div>
          <div class="toolbar-right">
            <ChButton variant="outline" size="sm">
              <Filter :size="14" />
              Filter
            </ChButton>
            <ChButton variant="outline" size="sm">
              <Download :size="14" />
              Export
            </ChButton>
            <ChButton variant="primary" size="sm">
              <Plus :size="14" />
              Add Member
            </ChButton>
          </div>
        </div>

        <!-- Table -->
        <ChTable :columns="memberColumns" :rows="members" :total="memberTotal" v-model:page="memberPage"
          :page-size="memberPageSize" :selectable="true" :exportable="true" title="Member Directory" :hoverable="true"
          :clickable="true" @sort="handleMemberSort" @row-click="handleMemberRowClick">
          <!-- Custom Actions Column -->
          <template #cell-actions="{ row }">
            <div class="action-buttons">
              <ChButton variant="ghost" size="sm" @click.stop="editMember(row)">
                <Eye :size="14" />
              </ChButton>
              <ChButton variant="ghost" size="sm" @click.stop="editMember(row)">
                <Edit :size="14" />
              </ChButton>
              <ChButton variant="ghost" size="sm" @click.stop="deleteMember(row)">
                <Trash2 :size="14" />
              </ChButton>
            </div>
          </template>
        </ChTable>
      </ChCard>

      <!-- Code Example -->
      <div v-if="showCodeExample === 'table'" class="code-block">
        <pre><code>{{ tableCodeExample }}</code></pre>
      </div>

      <!-- ChTable Variations -->
      <div class="subsection">
        <h4 class="subsection-title">Table Variations</h4>

        <!-- Contribution Records Table -->
        <ChCard class="variation-card">
          <template #header>
            <h5 class="card-subtitle">Contribution Records</h5>
          </template>
          <ChTable :columns="contributionColumns" :rows="contributions" :total="contributions.length" :page-size="5"
            :exportable="true" title="Contribution Records" :hoverable="true" />
        </ChCard>

        <!-- Attendance Records Table -->
        <ChCard class="variation-card">
          <template #header>
            <h5 class="card-subtitle">Event Attendance</h5>
          </template>
          <ChTable :columns="attendanceColumns" :rows="attendanceRecords" :total="attendanceRecords.length"
            :page-size="4" :hoverable="true" />
        </ChCard>
      </div>

      <!-- Loading State -->
      <ChCard class="variation-card">
        <template #header>
          <h5 class="card-subtitle">Loading State</h5>
        </template>
        <ChTable :columns="memberColumns" :rows="[]" :loading="isLoadingData" :skeleton-rows="5" :total="100"
          :page-size="5" />
      </ChCard>

      <!-- Empty State -->
      <ChCard class="variation-card">
        <template #header>
          <h5 class="card-subtitle">Empty State</h5>
        </template>
        <ChTable :columns="emptyTableColumns" :rows="emptyTableData"
          empty-message="No members found matching your criteria." />
      </ChCard>
    </section>

    <!-- =======================================================================
    SECTION 2: ChStatCard - Key Metrics Display
    ======================================================================== -->
    <section class="docs-section">
      <div class="section-header">
        <div class="section-info">
          <h2 class="section-title">
            <BarChart3 :size="20" class="section-icon" />
            ChStatCard
          </h2>
          <p class="section-description">
            Compact cards for displaying key metrics with optional trend indicators,
            icons, and color variants. Perfect for dashboard summaries.
          </p>
        </div>
        <ChButton variant="ghost" size="sm" @click="toggleCode('statCard')">
          <Code :size="14" />
          {{ showCodeExample === 'statCard' ? 'Hide' : 'Show' }} Code
        </ChButton>
      </div>

      <!-- Dashboard Stats Grid -->
      <div class="stats-grid">
        <ChStatCard
v-for="stat in dashboardStats" :key="stat.id" :label="stat.label" :value="stat.value"
          :trend="stat.trend" :trend-label="stat.trendLabel" :variant="stat.variant" :loading="statsLoading">
          <template #icon>
            <component :is="stat.icon" :size="24" />
          </template>
        </ChStatCard>
      </div>

      <!-- Loading Toggle -->
      <div class="demo-controls">
        <ChButton variant="outline" size="sm" @click="toggleStatsLoading">
          {{ statsLoading ? 'Show' : 'Hide' }} Loading State
        </ChButton>
      </div>

      <!-- Code Example -->
      <div v-if="showCodeExample === 'statCard'" class="code-block">
        <pre><code>{{ statCardCodeExample }}</code></pre>
      </div>

      <!-- StatCard Variations -->
      <div class="subsection">
        <h4 class="subsection-title">Color Variants</h4>
        <div class="stats-grid stats-grid-3">
          <ChStatCard v-for="(variant, index) in statCardVariants" :key="variant"
            :label="variantLabels[index] + ' Card'" :value="variant === 'default' ? '—' : '1,248'" :variant="variant">
            <template #icon>
              <Users :size="20" />
            </template>
          </ChStatCard>
        </div>
      </div>

      <!-- Without Trend -->
      <div class="subsection">
        <h4 class="subsection-title">Without Trend Indicator</h4>
        <div class="stats-grid stats-grid-4">
          <ChStatCard label="Members Under 18" value="185" variant="primary">
            <template #icon>
              <Baby :size="20" />
            </template>
          </ChStatCard>
          <ChStatCard label="Baptisms This Year" value="34" variant="success">
            <template #icon>
              <Heart :size="20" />
            </template>
          </ChStatCard>
          <ChStatCard label="Active Groups" value="12" variant="info">
            <template #icon>
              <Users :size="20" />
            </template>
          </ChStatCard>
          <ChStatCard label="Upcoming Events" value="5" variant="warning">
            <template #icon>
              <Calendar :size="20" />
            </template>
          </ChStatCard>
        </div>
      </div>

      <!-- With Negative Trend -->
      <div class="subsection">
        <h4 class="subsection-title">With Negative Trend</h4>
        <div class="stats-grid stats-grid-2">
          <ChStatCard label="Inactive Members" value="38" :trend="-5.2" trend-label="vs last month" variant="danger">
            <template #icon>
              <UserX :size="20" />
            </template>
          </ChStatCard>
          <ChStatCard label="Absentee Rate" value="3.2%" :trend="0.8" trend-label="vs last week" variant="warning">
            <template #icon>
              <TrendingDown :size="20" />
            </template>
          </ChStatCard>
        </div>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 3: ChDataList - Structured Data Display
    ======================================================================== -->
    <section class="docs-section">
      <div class="section-header">
        <div class="section-info">
          <h2 class="section-title">
            <List :size="20" class="section-icon" />
            ChDataList
          </h2>
          <p class="section-description">
            A structured label/value list for displaying record details like
            member profiles, event information, or contribution receipts.
          </p>
        </div>
        <ChButton variant="ghost" size="sm" @click="toggleCode('dataList')">
          <Code :size="14" />
          {{ showCodeExample === 'dataList' ? 'Hide' : 'Show' }} Code
        </ChButton>
      </div>

      <!-- Layout Toggle -->
      <div class="demo-controls">
        <ChButton :variant="dataListLayout === 'horizontal' ? 'primary' : 'outline'" size="sm"
          @click="dataListLayout = 'horizontal'">
          Horizontal
        </ChButton>
        <ChButton :variant="dataListLayout === 'vertical' ? 'primary' : 'outline'" size="sm"
          @click="dataListLayout = 'vertical'">
          Vertical
        </ChButton>
      </div>

      <!-- Member Profile -->
      <div class="data-list-showcase">
        <ChCard>
          <template #header>
            <div class="card-header-content">
              <h5 class="card-subtitle">Member Profile</h5>
              <ChButton variant="ghost" size="sm">
                <Edit :size="14" />
              </ChButton>
            </div>
          </template>
          <ChDataList :items="memberProfileItems" :layout="dataListLayout" />
        </ChCard>

        <!-- Event Info -->
        <ChCard>
          <template #header>
            <h5 class="card-subtitle">Event Information</h5>
          </template>
          <ChDataList :items="eventInfoItems" :layout="dataListLayout" />
        </ChCard>
      </div>

      <!-- Contribution Receipt -->
      <ChCard>
        <template #header>
          <div class="card-header-content">
            <h5 class="card-subtitle">Contribution Receipt</h5>
            <ChButton variant="ghost" size="sm">
              <Printer :size="14" />
            </ChButton>
          </div>
        </template>
        <ChDataList :items="receiptItems" :layout="dataListLayout" />
      </ChCard>

      <!-- Code Example -->
      <div v-if="showCodeExample === 'dataList'" class="code-block">
        <pre><code>{{ dataListCodeExample }}</code></pre>
      </div>

      <!-- Family Summary -->
      <div class="subsection">
        <h4 class="subsection-title">Family Summary</h4>
        <ChCard>
          <template #header>
            <h5 class="card-subtitle">Family Details</h5>
          </template>
          <ChDataList :items="familyItems" :layout="dataListLayout" />
        </ChCard>
      </div>

      <!-- Empty State -->
      <div class="subsection">
        <h4 class="subsection-title">Empty State</h4>
        <ChCard>
          <template #header>
            <h5 class="card-subtitle">No Data Available</h5>
          </template>
          <ChDataList :items="emptyDataListItems" :layout="dataListLayout" empty-text="No information available" />
        </ChCard>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 4: ChChart - Data Visualization
    ======================================================================== -->
    <section class="docs-section">
      <div class="section-header">
        <div class="section-info">
          <h2 class="section-title">
            <PieChart :size="20" class="section-icon" />
            ChChart
          </h2>
          <p class="section-description">
            A Vue 3 wrapper around Chart.js for creating interactive charts.
            Supports line, bar, doughnut, pie, and area charts with theme integration.
          </p>
        </div>
        <ChButton variant="ghost" size="sm" @click="toggleCode('chart')">
          <Code :size="14" />
          {{ showCodeExample === 'chart' ? 'Hide' : 'Show' }} Code
        </ChButton>
      </div>

      <!-- Chart Grid -->
      <div class="charts-grid charts-grid-2">
        <!-- Line Chart - Attendance Trends -->
        <ChCard>
          <template #header>
            <div class="card-header-content">
              <h5 class="card-subtitle">Attendance Trends</h5>
              <span class="card-badge">Line Chart</span>
            </div>
          </template>
          <div class="chart-container">
            <ChChart type="line" :data="attendanceChartData" :options="lineChartOptions" :height="280" />
          </div>
        </ChCard>

        <!-- Bar Chart - Contributions -->
        <ChCard>
          <template #header>
            <div class="card-header-content">
              <h5 class="card-subtitle">Contributions by Category</h5>
              <span class="card-badge">Bar Chart</span>
            </div>
          </template>
          <div class="chart-container">
            <ChChart type="bar" :data="contributionsChartData" :options="barChartOptions" :height="280" />
          </div>
        </ChCard>
      </div>

      <!-- Doughnut and Pie Charts -->
      <div class="charts-grid charts-grid-3">
        <!-- Doughnut Chart - Membership Distribution -->
        <ChCard>
          <template #header>
            <div class="card-header-content">
              <h5 class="card-subtitle">Membership Distribution</h5>
              <span class="card-badge">Doughnut</span>
            </div>
          </template>
          <div class="chart-container chart-container-sm">
            <ChChart type="doughnut" :data="membershipChartData" :options="doughnutChartOptions" :height="240" />
          </div>
        </ChCard>

        <!-- Area Chart - Growth -->
        <ChCard>
          <template #header>
            <div class="card-header-content">
              <h5 class="card-subtitle">Church Growth</h5>
              <span class="card-badge">Area Chart</span>
            </div>
          </template>
          <div class="chart-container chart-container-sm">
            <ChChart type="line" :data="growthChartData" :height="240" />
          </div>
        </ChCard>

        <!-- Pie Chart - Ministry Breakdown -->
        <ChCard>
          <template #header>
            <div class="card-header-content">
              <h5 class="card-subtitle">Ministry Breakdown</h5>
              <span class="card-badge">Pie Chart</span>
            </div>
          </template>
          <div class="chart-container chart-container-sm">
            <ChChart type="pie" :data="ministryChartData" :height="240" />
          </div>
        </ChCard>
      </div>

      <!-- Weekly Giving Pattern -->
      <ChCard>
        <template #header>
          <div class="card-header-content">
            <h5 class="card-subtitle">Weekly Giving Pattern</h5>
            <span class="card-badge">Stacked Bar Chart</span>
          </div>
        </template>
        <div class="chart-container">
          <ChChart type="bar" :data="weeklyGivingData" :height="280" />
        </div>
      </ChCard>

      <!-- Code Example -->
      <div v-if="showCodeExample === 'chart'" class="code-block">
        <pre><code>{{ chartCodeExample }}</code></pre>
      </div>

      <!-- Loading and Empty States -->
      <div class="subsection">
        <h4 class="subsection-title">States</h4>
        <div class="charts-grid charts-grid-2">
          <!-- Loading State -->
          <ChCard>
            <template #header>
              <h5 class="card-subtitle">Loading State</h5>
            </template>
            <div class="chart-container chart-loading">
              <ChSpinner v-if="chartLoading" size="lg" />
            </div>
          </ChCard>

          <!-- Empty State -->
          <ChCard>
            <template #header>
              <h5 class="card-subtitle">Empty State</h5>
            </template>
            <div class="chart-container chart-empty">
              <div class="empty-state-content">
                <LineChart :size="48" class="empty-icon" />
                <p>No data to display</p>
              </div>
            </div>
          </ChCard>
        </div>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 5: ChPagination - Standalone Pagination
    ======================================================================== -->
    <section class="docs-section">
      <div class="section-header">
        <div class="section-info">
          <h2 class="section-title">
            <ChevronRight :size="20" class="section-icon" />
            ChPagination
          </h2>
          <p class="section-description">
            Standalone pagination control for navigating through paged data.
            Supports various configurations including page size selection and compact mode.
          </p>
        </div>
        <ChButton variant="ghost" size="sm" @click="toggleCode('pagination')">
          <Code :size="14" />
          {{ showCodeExample === 'pagination' ? 'Hide' : 'Show' }} Code
        </ChButton>
      </div>

      <!-- Basic Pagination -->
      <ChCard>
        <template #header>
          <h5 class="card-subtitle">Basic Pagination</h5>
        </template>
        <div class="pagination-demo">
          <ChPagination v-model:page="basicPage" :total="basicTotal" :page-size="basicPageSize" />
        </div>
        <p class="demo-note">
          Showing {{ ((basicPage - 1) * basicPageSize) + 1 }}–{{ Math.min(basicPage * basicPageSize, basicTotal) }} of
          {{ basicTotal }} members
        </p>
      </ChCard>

      <!-- Code Example -->
      <div v-if="showCodeExample === 'pagination'" class="code-block">
        <pre><code>{{ paginationCodeExample }}</code></pre>
      </div>

      <!-- With Page Size Selector -->
      <ChCard>
        <template #header>
          <h5 class="card-subtitle">With Page Size Selector</h5>
        </template>
        <div class="pagination-demo pagination-with-size">
          <div class="page-size-selector">
            <span class="size-label">Show</span>
            <ChButton v-for="size in pageSizeOptions" :key="size"
              :variant="selectedPageSize === size ? 'primary' : 'outline'" size="sm" @click="selectedPageSize = size">
              {{ size }}
            </ChButton>
            <span class="size-label">per page</span>
          </div>
          <ChPagination v-model:page="pageSizePage" :total="pageSizeTotal" :page-size="selectedPageSize" />
        </div>
      </ChCard>

      <!-- Compact Mode -->
      <ChCard>
        <template #header>
          <h5 class="card-subtitle">Compact Mode</h5>
        </template>
        <div class="pagination-demo">
          <ChPagination v-model:page="compactPage" :total="compactTotal" compact />
        </div>
      </ChCard>

      <!-- Simple Mode (Prev/Next Only) -->
      <ChCard>
        <template #header>
          <h5 class="card-subtitle">Simple Mode (Prev/Next Only)</h5>
        </template>
        <div class="pagination-demo">
          <div class="simple-pagination">
            <ChButton variant="outline" size="sm" :disabled="simplePage === 1" @click="simplePage--">
              <ChevronLeft :size="14" />
              Previous
            </ChButton>
            <span class="simple-info">
              Page {{ simplePage }} of {{ Math.ceil(simpleTotal / simplePageSize) }}
            </span>
            <ChButton variant="outline" size="sm" :disabled="simplePage >= Math.ceil(simpleTotal / simplePageSize)"
              @click="simplePage++">
              Next
              <ChevronRight :size="14" />
            </ChButton>
          </div>
        </div>
      </ChCard>

      <!-- With Jump to Page -->
      <ChCard>
        <template #header>
          <h5 class="card-subtitle">With Jump to Page</h5>
        </template>
        <div class="pagination-demo">
          <div class="jump-pagination">
            <ChButton variant="outline" size="sm" :disabled="jumpPage <= 1" @click="jumpPage = 1">
              <ChevronsLeft :size="14" />
            </ChButton>
            <ChButton variant="outline" size="sm" :disabled="jumpPage <= 1" @click="jumpPage--">
              <ChevronLeft :size="14" />
            </ChButton>

            <ChPagination v-model:page="jumpPage" :total="jumpTotal" :page-size="jumpPageSize" />

            <ChButton variant="outline" size="sm" :disabled="jumpPage >= Math.ceil(jumpTotal / jumpPageSize)"
              @click="jumpPage++">
              <ChevronRight :size="14" />
            </ChButton>
            <ChButton variant="outline" size="sm" :disabled="jumpPage >= Math.ceil(jumpTotal / jumpPageSize)"
              @click="jumpPage = Math.ceil(jumpTotal / jumpPageSize)">
              <ChevronsRight :size="14" />
            </ChButton>

            <div class="jump-input-wrapper">
              <span class="jump-label">Go to</span>
              <ChInput v-model="jumpInput" type="number" size="sm" class="jump-input" :min="1"
                :max="Math.ceil(jumpTotal / jumpPageSize)" @keyup.enter="jumpPage = parseInt(jumpInput) || 1" />
            </div>
          </div>
        </div>
      </ChCard>
    </section>

    <!-- =======================================================================
    SECTION 6: Component Reference
    ======================================================================== -->
    <section class="docs-section">
      <div class="section-header">
        <div class="section-info">
          <h2 class="section-title">
            <FileText :size="20" class="section-icon" />
            Component Reference
          </h2>
          <p class="section-description">
            Quick reference for component props and usage patterns.
          </p>
        </div>
      </div>

      <!-- Props Reference Table -->
      <ChCard>
        <template #header>
          <h5 class="card-subtitle">ChTable Props</h5>
        </template>
        <div class="props-table">
          <table>
            <thead>
              <tr>
                <th>Prop</th>
                <th>Type</th>
                <th>Default</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>columns</code></td>
                <td><code>ColumnDef[]</code></td>
                <td>Required</td>
                <td>Column definitions with keys, labels, sortable, type</td>
              </tr>
              <tr>
                <td><code>rows</code></td>
                <td><code>T[]</code></td>
                <td>Required</td>
                <td>Data rows to display</td>
              </tr>
              <tr>
                <td><code>total</code></td>
                <td><code>number</code></td>
                <td>—</td>
                <td>Total records for pagination</td>
              </tr>
              <tr>
                <td><code>page</code></td>
                <td><code>number</code></td>
                <td>1</td>
                <td>Current page (1-indexed)</td>
              </tr>
              <tr>
                <td><code>pageSize</code></td>
                <td><code>number</code></td>
                <td>10</td>
                <td>Rows per page</td>
              </tr>
              <tr>
                <td><code>selectable</code></td>
                <td><code>boolean</code></td>
                <td>false</td>
                <td>Enable row selection checkboxes</td>
              </tr>
              <tr>
                <td><code>exportable</code></td>
                <td><code>boolean</code></td>
                <td>false</td>
                <td>Enable export toolbar</td>
              </tr>
              <tr>
                <td><code>loading</code></td>
                <td><code>boolean</code></td>
                <td>false</td>
                <td>Show loading skeleton</td>
              </tr>
            </tbody>
          </table>
        </div>
      </ChCard>

      <ChCard>
        <template #header>
          <h5 class="card-subtitle">ChStatCard Props</h5>
        </template>
        <div class="props-table">
          <table>
            <thead>
              <tr>
                <th>Prop</th>
                <th>Type</th>
                <th>Default</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>value</code></td>
                <td><code>string | number</code></td>
                <td>Required</td>
                <td>The metric value to display</td>
              </tr>
              <tr>
                <td><code>label</code></td>
                <td><code>string</code></td>
                <td>Required</td>
                <td>Descriptive label for the metric</td>
              </tr>
              <tr>
                <td><code>trend</code></td>
                <td><code>number</code></td>
                <td>—</td>
                <td>Percentage change vs previous period</td>
              </tr>
              <tr>
                <td><code>trendLabel</code></td>
                <td><code>string</code></td>
                <td>—</td>
                <td>Context label for trend comparison</td>
              </tr>
              <tr>
                <td><code>variant</code></td>
                <td><code>Variant</code></td>
                <td>'primary'</td>
                <td>Color accent variant</td>
              </tr>
              <tr>
                <td><code>loading</code></td>
                <td><code>boolean</code></td>
                <td>false</td>
                <td>Show loading skeleton</td>
              </tr>
            </tbody>
          </table>
        </div>
      </ChCard>

      <ChCard>
        <template #header>
          <h5 class="card-subtitle">ChChart Props</h5>
        </template>
        <div class="props-table">
          <table>
            <thead>
              <tr>
                <th>Prop</th>
                <th>Type</th>
                <th>Default</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>type</code></td>
                <td><code>'line' | 'bar' | 'doughnut' | 'pie'</code></td>
                <td>Required</td>
                <td>Chart type</td>
              </tr>
              <tr>
                <td><code>data</code></td>
                <td><code>ChartData</code></td>
                <td>Required</td>
                <td>Chart.js data object with labels and datasets</td>
              </tr>
              <tr>
                <td><code>options</code></td>
                <td><code>ChartOptions</code></td>
                <td>—</td>
                <td>Chart.js options override</td>
              </tr>
              <tr>
                <td><code>height</code></td>
                <td><code>string | number</code></td>
                <td>300</td>
                <td>Chart height in pixels</td>
              </tr>
            </tbody>
          </table>
        </div>
      </ChCard>

      <ChCard>
        <template #header>
          <h5 class="card-subtitle">ChPagination Props</h5>
        </template>
        <div class="props-table">
          <table>
            <thead>
              <tr>
                <th>Prop</th>
                <th>Type</th>
                <th>Default</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>page</code></td>
                <td><code>number</code></td>
                <td>Required</td>
                <td>Current page (1-indexed)</td>
              </tr>
              <tr>
                <td><code>total</code></td>
                <td><code>number</code></td>
                <td>Required</td>
                <td>Total number of items</td>
              </tr>
              <tr>
                <td><code>pageSize</code></td>
                <td><code>number</code></td>
                <td>10</td>
                <td>Items per page</td>
              </tr>
              <tr>
                <td><code>compact</code></td>
                <td><code>boolean</code></td>
                <td>false</td>
                <td>Show simplified pagination</td>
              </tr>
            </tbody>
          </table>
        </div>
      </ChCard>
    </section>
  </div>
</template>

<style scoped>
/* ==========================================================================
   Base Layout
   ========================================================================== */

.docs-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.docs-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 3rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid var(--ch-color-border, #e5e7eb);
}

.header-content {
  flex: 1;
}

.docs-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--ch-color-primary, #1e40af);
  margin: 0 0 0.5rem 0;
  line-height: 1.2;
}

.docs-subtitle {
  font-size: 1.125rem;
  color: var(--ch-color-muted, #6b7280);
  margin: 0;
  max-width: 600px;
  line-height: 1.6;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
}

/* ==========================================================================
   Sections
   ========================================================================== */

.docs-section {
  margin-bottom: 4rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.section-info {
  flex: 1;
}

.section-title {
  font-size: 1.75rem;
  font-weight: 600;
  color: var(--ch-color-text, #111827);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-icon {
  color: var(--ch-color-primary, #3b82f6);
}

.section-description {
  font-size: 1rem;
  color: var(--ch-color-muted, #6b7280);
  margin: 0;
  max-width: 700px;
  line-height: 1.6;
}

.subsection {
  margin-top: 2rem;
}

.subsection-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--ch-color-text, #111827);
  margin: 0 0 1rem 0;
}

/* ==========================================================================
   Card Variations
   ========================================================================== */

.variation-card {
  margin-bottom: 1.5rem;
}

.card-header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--ch-color-text, #111827);
  margin: 0;
}

.card-subtitle {
  font-size: 1rem;
  font-weight: 600;
  color: var(--ch-color-text, #111827);
  margin: 0;
}

.badge-count {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--ch-color-muted, #6b7280);
  background: var(--ch-color-subtle, #f3f4f6);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
}

.card-badge {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--ch-color-primary, #3b82f6);
  background: rgba(59, 130, 246, 0.1);
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
}

/* ==========================================================================
   Table Styles
   ========================================================================== */

.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.toolbar-left {
  flex: 1;
  min-width: 200px;
}

.toolbar-right {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.search-input {
  max-width: 300px;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

/* ==========================================================================
   Stats Grid
   ========================================================================== */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.stats-grid-3 {
  grid-template-columns: repeat(3, 1fr);
}

.stats-grid-2 {
  grid-template-columns: repeat(2, 1fr);
}

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {

  .stats-grid,
  .stats-grid-3,
  .stats-grid-2 {
    grid-template-columns: 1fr;
  }
}

/* ==========================================================================
   DataList Showcase
   ========================================================================== */

.data-list-showcase {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
  .data-list-showcase {
    grid-template-columns: 1fr;
  }
}

/* ==========================================================================
   Chart Styles
   ========================================================================== */

.charts-grid {
  display: grid;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.charts-grid-2 {
  grid-template-columns: repeat(2, 1fr);
}

.charts-grid-3 {
  grid-template-columns: repeat(3, 1fr);
}

@media (max-width: 1024px) {

  .charts-grid-2,
  .charts-grid-3 {
    grid-template-columns: 1fr;
  }
}

.chart-container {
  height: 280px;
  position: relative;
}

.chart-container-sm {
  height: 240px;
}

.chart-loading,
.chart-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ch-color-subtle, #f9fafb);
  border-radius: 8px;
}

.empty-state-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  color: var(--ch-color-muted, #9ca3af);
}

.empty-icon {
  opacity: 0.5;
}

/* ==========================================================================
   Pagination Styles
   ========================================================================== */

.pagination-demo {
  display: flex;
  justify-content: center;
  padding: 1rem 0;
}

.pagination-with-size {
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.page-size-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.size-label {
  font-size: 0.875rem;
  color: var(--ch-color-muted, #6b7280);
}

.simple-pagination {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.simple-info {
  font-size: 0.875rem;
  color: var(--ch-color-muted, #6b7280);
}

.jump-pagination {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.jump-input-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: 1rem;
}

.jump-label {
  font-size: 0.875rem;
  color: var(--ch-color-muted, #6b7280);
}

.jump-input {
  width: 70px;
}

.demo-note {
  font-size: 0.875rem;
  color: var(--ch-color-muted, #6b7280);
  text-align: center;
  margin: 0;
  padding-bottom: 1rem;
}

/* ==========================================================================
   Demo Controls
   ========================================================================== */

.demo-controls {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

/* ==========================================================================
   Code Blocks
   ========================================================================== */

.code-block {
  background: var(--ch-color-surface, #1f2937);
  border-radius: 8px;
  padding: 1rem;
  margin: 1.5rem 0;
  overflow-x: auto;
}

.code-block pre {
  margin: 0;
}

.code-block code {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.8125rem;
  color: #e5e7eb;
  line-height: 1.6;
  white-space: pre;
}

/* ==========================================================================
   Props Tables
   ========================================================================== */

.props-table {
  overflow-x: auto;
}

.props-table table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.props-table th,
.props-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--ch-color-border, #e5e7eb);
}

.props-table th {
  font-weight: 600;
  color: var(--ch-color-text, #111827);
  background: var(--ch-color-subtle, #f9fafb);
}

.props-table td {
  color: var(--ch-color-muted, #6b7280);
}

.props-table code {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.8125rem;
  color: var(--ch-color-primary, #3b82f6);
  background: rgba(59, 130, 246, 0.1);
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
}

/* ==========================================================================
   Animations
   ========================================================================== */

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}
</style>