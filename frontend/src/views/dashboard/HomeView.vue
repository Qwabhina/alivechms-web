<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { 
  Users, 
  UserPlus, 
  Calendar, 
  TrendingUp, 
  ArrowUpRight, 
  ArrowDownRight,
  Receipt,
  Clock,
  CheckCircle2,
  AlertCircle,
  Activity,
  CalendarDays
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import api from '@/services/api'
import { Alerts } from '@/utils/alerts'
import VueApexCharts from 'vue3-apexcharts'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

const authStore = useAuthStore()
const router = useRouter()
const loading = ref(true)
const dashboardData = ref<any>(null)

const fetchDashboardData = async () => {
  loading.value = true
  try {
    const response = await api.get('dashboard/overview')
    dashboardData.value = response.data.data
  } catch (error) {
    Alerts.handleApiError(error, 'Failed to load dashboard data')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})

// Stats Calculation
const stats = computed(() => {
  if (!dashboardData.value) return []
  
  const m = dashboardData.value.membership || {}
  const f = dashboardData.value.finance || {}
  const p = dashboardData.value.pending_approvals || {}
  
  return [
    { 
      name: 'Active Members', 
      value: m.total || '0', 
      change: `+${m.new_this_month || 0}`, 
      label: 'this month',
      icon: Users, 
      trend: 'up',
      color: 'text-blue-600',
      bg: 'bg-blue-50'
    },
    { 
      name: 'Total Income', 
      value: `GH₵ ${parseFloat(f.income || 0).toLocaleString()}`, 
      change: 'Fiscal Year', 
      label: 'current',
      icon: TrendingUp, 
      trend: 'up',
      color: 'text-green-600',
      bg: 'bg-green-50'
    },
    { 
      name: 'Total Expenses', 
      value: `GH₵ ${parseFloat(f.expenses || 0).toLocaleString()}`, 
      change: 'Fiscal Year', 
      label: 'current',
      icon: Receipt, 
      trend: 'down',
      color: 'text-red-600',
      bg: 'bg-red-50'
    },
    { 
      name: 'Pending Tasks', 
      value: (p.budgets || 0) + (p.expenses || 0), 
      change: `${p.budgets || 0} Budgets`, 
      label: 'awaiting approval',
      icon: Clock, 
      trend: 'up',
      color: 'text-orange-600',
      bg: 'bg-orange-50'
    },
    { 
      name: 'Net Balance', 
      value: `GH₵ ${(parseFloat(f.income || 0) - parseFloat(f.expenses || 0)).toLocaleString()}`, 
      change: 'Calculated', 
      label: 'current surplus/deficit',
      icon: Activity, 
      trend: (parseFloat(f.income || 0) - parseFloat(f.expenses || 0)) >= 0 ? 'up' : 'down',
      color: (parseFloat(f.income || 0) - parseFloat(f.expenses || 0)) >= 0 ? 'text-emerald-600' : 'text-rose-600',
      bg: (parseFloat(f.income || 0) - parseFloat(f.expenses || 0)) >= 0 ? 'bg-emerald-50' : 'bg-rose-50'
    },
  ]
})

// Attendance Chart Options
const attendanceChartOptions = computed(() => ({
  chart: {
    id: 'attendance-chart',
    toolbar: { show: false },
    fontFamily: 'Inter, sans-serif'
  },
  xaxis: {
    categories: (Array.isArray(dashboardData.value?.attendance_last_4_sundays) ? dashboardData.value.attendance_last_4_sundays : []).map((d: any) => dayjs(d.date).format('MMM DD')),
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: {
    labels: {
      formatter: (val: number) => val.toFixed(0)
    }
  },
  stroke: {
    curve: 'smooth' as const,
    width: 3
  },
  colors: ['#00028a'],
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.45,
      opacityTo: 0.05,
      stops: [20, 100]
    }
  },
  dataLabels: { enabled: false },
  grid: {
    borderColor: '#f1f5f9',
    strokeDashArray: 4
  }
}))

const attendanceChartSeries = computed(() => [{
  name: 'Attendance',
  data: (Array.isArray(dashboardData.value?.attendance_last_4_sundays) ? dashboardData.value.attendance_last_4_sundays : []).map((d: any) => parseInt(d.present) || 0)
}])

// Finance Chart Options
const financeChartOptions = computed(() => ({
  chart: {
    id: 'finance-chart',
    fontFamily: 'Inter, sans-serif'
  },
  labels: ['Income', 'Expenses'],
  colors: ['#10b981', '#ef4444'],
  legend: {
    position: 'bottom' as const
  },
  plotOptions: {
    pie: {
      donut: {
        size: '75%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Net Balance',
            formatter: (w: any) => {
              const income = parseFloat(dashboardData.value?.finance?.income || 0)
              const expenses = parseFloat(dashboardData.value?.finance?.expenses || 0)
              return `GH₵ ${(income - expenses).toLocaleString()}`
            }
          }
        }
      }
    }
  },
  dataLabels: { enabled: false }
}))

const financeChartSeries = computed(() => [
  parseFloat(dashboardData.value?.finance?.income || 0),
  parseFloat(dashboardData.value?.finance?.expenses || 0)
])

const getActivityIcon = (type: string) => {
  switch (type) {
    case 'Member Registered': return UserPlus
    case 'Contribution': return TrendingUp
    case 'Expense': return Receipt
    case 'Event Created': return Calendar
    default: return Activity
  }
}

const getActivityColor = (type: string) => {
  switch (type) {
    case 'Member Registered': return 'text-green-500 bg-green-50'
    case 'Contribution': return 'text-blue-500 bg-blue-50'
    case 'Expense': return 'text-red-500 bg-red-50'
    case 'Event Created': return 'text-purple-500 bg-purple-50'
    default: return 'text-gray-500 bg-gray-50'
  }
}
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-[#00028a]">Dashboard Overview</h1>
        <p class="text-muted-foreground">Welcome back, {{ authStore.user?.username }}. Here's the latest for your congregation.</p>
      </div>
      <Button @click="fetchDashboardData" :disabled="loading" variant="outline" class="w-fit">
        <Activity class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
        Refresh Data
      </Button>
    </div>

    <!-- Stats Grid -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
      <template v-if="loading">
        <Card v-for="i in 4" :key="i" class="border-none shadow-sm animate-pulse h-32"></Card>
      </template>
      <template v-else>
        <Card v-for="stat in stats" :key="stat.name" class="border-none shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300">
          <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle class="text-sm font-medium text-muted-foreground">{{ stat.name }}</CardTitle>
            <div :class="['h-10 w-10 rounded-xl flex items-center justify-center transition-colors', stat.bg, stat.color]">
              <component :is="stat.icon" class="h-5 w-5" />
            </div>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stat.value }}</div>
            <div class="flex items-center mt-1">
              <span class="text-xs font-semibold" :class="stat.trend === 'up' ? 'text-green-600' : 'text-red-600'">
                {{ stat.change }}
              </span>
              <span class="text-xs text-muted-foreground ml-2">{{ stat.label }}</span>
            </div>
          </CardContent>
        </Card>
      </template>
    </div>

    <!-- Main Content Area -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-12">
      <!-- Attendance Chart -->
      <Card class="lg:col-span-8 border-none shadow-sm h-fit">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Attendance Trend</CardTitle>
              <CardDescription>Member presence across the last 4 Sundays.</CardDescription>
            </div>
            <div class="flex items-center gap-2">
               <Badge variant="secondary" class="bg-blue-50 text-blue-700 hover:bg-blue-50">Live</Badge>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="h-[300px] flex items-center justify-center">
             <Clock class="w-8 h-8 text-gray-200 animate-spin" />
          </div>
          <VueApexCharts
            v-else
            type="area"
            height="300"
            :options="attendanceChartOptions"
            :series="attendanceChartSeries"
          />
        </CardContent>
      </Card>

      <!-- Finance Overview -->
      <Card class="lg:col-span-4 border-none shadow-sm">
        <CardHeader>
          <CardTitle>Financial Overview</CardTitle>
          <CardDescription>Fiscal year income vs expenses.</CardDescription>
        </CardHeader>
        <CardContent>
           <div v-if="loading" class="h-[250px] flex items-center justify-center">
              <Clock class="w-8 h-8 text-gray-200 animate-spin" />
           </div>
           <template v-else>
             <VueApexCharts
               type="donut"
               height="250"
               :options="financeChartOptions"
               :series="financeChartSeries"
             />
             <div class="mt-6 space-y-3">
               <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                 <span class="text-sm font-medium text-green-700">Total Income</span>
                 <strong class="text-green-700">GH₵ {{ parseFloat(dashboardData?.finance?.income || 0).toLocaleString() }}</strong>
               </div>
               <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                 <span class="text-sm font-medium text-red-700">Total Expenses</span>
                 <strong class="text-red-700">GH₵ {{ parseFloat(dashboardData?.finance?.expenses || 0).toLocaleString() }}</strong>
               </div>
             </div>
           </template>
        </CardContent>
      </Card>

      <!-- Recent Activity -->
      <Card class="lg:col-span-7 border-none shadow-sm">
        <CardHeader>
           <div class="flex items-center justify-between">
              <div>
                 <CardTitle>Recent Activity</CardTitle>
                 <CardDescription>Latest system logs and transaction history.</CardDescription>
              </div>
              <Badge v-if="dashboardData?.recent_activity?.length" class="bg-blue-100 text-blue-700 hover:bg-blue-100">
                {{ dashboardData.recent_activity.length }} total
              </Badge>
           </div>
        </CardHeader>
        <CardContent>
           <div v-if="loading" class="space-y-4">
              <div v-for="i in 5" :key="i" class="h-12 bg-gray-50 animate-pulse rounded-lg"></div>
           </div>
           <div v-else-if="!dashboardData?.recent_activity?.length" class="text-center py-10">
              <Activity class="w-12 h-12 text-gray-200 mx-auto mb-2" />
              <p class="text-gray-400">No recent activity found.</p>
           </div>
           <div v-else class="space-y-6">
              <div v-for="(activity, index) in dashboardData.recent_activity" :key="index" class="flex gap-4">
                 <div :class="['h-10 w-10 shrink-0 rounded-full flex items-center justify-center', getActivityColor(activity.type)]">
                    <component :is="getActivityIcon(activity.type)" class="h-5 w-5" />
                 </div>
                 <div class="flex-1 space-y-1">
                    <div class="flex items-center justify-between">
                       <p class="text-sm font-semibold">{{ activity.type }}</p>
                       <span class="text-xs text-muted-foreground">{{ dayjs(activity.timestamp).fromNow() }}</span>
                    </div>
                    <p class="text-xs text-muted-foreground line-clamp-2">{{ activity.description }}</p>
                 </div>
              </div>
           </div>
        </CardContent>
      </Card>

      <!-- Upcoming Events -->
      <Card class="lg:col-span-5 border-none shadow-sm">
        <CardHeader>
           <CardTitle>Upcoming Events</CardTitle>
           <CardDescription>Calendar highlights for the coming week.</CardDescription>
        </CardHeader>
        <CardContent>
           <div v-if="loading" class="space-y-4">
              <div v-for="i in 3" :key="i" class="h-20 bg-gray-50 animate-pulse rounded-lg"></div>
           </div>
           <div v-else-if="!dashboardData?.upcoming_events?.length" class="text-center py-10">
              <CalendarDays class="w-12 h-12 text-gray-200 mx-auto mb-2" />
              <p class="text-gray-400">No scheduled events.</p>
           </div>
           <div v-else class="space-y-4">
              <div v-for="(event, index) in dashboardData.upcoming_events" :key="index" class="p-4 rounded-xl border border-gray-100 flex items-center gap-4 hover:border-[#00028a]/20 transition-colors group">
                 <div class="h-12 w-12 rounded-lg bg-[#00028a]/5 text-[#00028a] flex flex-col items-center justify-center transition-colors group-hover:bg-[#00028a] group-hover:text-white">
                    <span class="text-[10px] font-bold uppercase">{{ dayjs(event.EventDate).format('MMM') }}</span>
                    <span class="text-lg font-bold leading-none">{{ dayjs(event.EventDate).format('DD') }}</span>
                 </div>
                 <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold truncate">{{ event.EventTitle }}</p>
                    <div class="flex items-center gap-2 mt-1">
                       <span class="text-xs text-muted-foreground flex items-center">
                          <Clock class="w-3 h-3 mr-1" />
                          {{ event.StartTime || 'TBA' }}
                       </span>
                       <span v-if="event.Location" class="text-xs text-muted-foreground flex items-center truncate">
                          <AlertCircle class="w-3 h-3 mr-1" />
                          {{ event.Location }}
                       </span>
                    </div>
                 </div>
                 <Button variant="ghost" size="icon" class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                    <ArrowUpRight class="w-4 h-4" />
                 </Button>
              </div>
              <Button variant="outline" class="w-full mt-2" @click="router.push('/events')">
                 View Full Calendar
              </Button>
           </div>
        </CardContent>
      </Card>
      
      <!-- Pending Approvals (Conditional) -->
      <template v-if="!loading && (dashboardData?.pending_approvals?.budgets > 0 || dashboardData?.pending_approvals?.expenses > 0)">
        <Card class="lg:col-span-12 border-none shadow-sm bg-orange-50/50 border-l-4 border-l-orange-500">
           <CardHeader class="pb-2">
              <div class="flex items-center gap-2">
                 <AlertCircle class="w-5 h-5 text-orange-600" />
                 <CardTitle class="text-orange-900">Pending Approvals Action Required</CardTitle>
              </div>
           </CardHeader>
           <CardContent>
              <div class="grid md:grid-cols-2 gap-4">
                 <div v-if="dashboardData.pending_approvals.budgets > 0" class="bg-white p-4 rounded-lg flex items-center justify-between shadow-sm">
                    <div>
                        <p class="font-bold text-orange-900">{{ dashboardData.pending_approvals.budgets }} Budgets</p>
                        <p class="text-xs text-orange-600">Awaiting financial review</p>
                    </div>
                    <Button size="sm" class="bg-orange-600 hover:bg-orange-700" @click="router.push('/budgets?filter=pending')">Review</Button>
                 </div>
                 <div v-if="dashboardData.pending_approvals.expenses > 0" class="bg-white p-4 rounded-lg flex items-center justify-between shadow-sm">
                    <div>
                        <p class="font-bold text-orange-900">{{ dashboardData.pending_approvals.expenses }} Expenses</p>
                        <p class="text-xs text-orange-600">Awaiting payout authorization</p>
                    </div>
                    <Button size="sm" class="bg-orange-600 hover:bg-orange-700" @click="router.push('/expenses?filter=pending')">Review</Button>
                 </div>
              </div>
           </CardContent>
        </Card>
      </template>
    </div>
  </div>
</template>
