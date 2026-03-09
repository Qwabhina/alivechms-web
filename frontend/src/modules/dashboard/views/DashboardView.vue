<template>
  <div class="grid">
      <!-- Welcome Section -->
      <div class="col-12 lg:col-6 xl:col-3">
         <StatWidget title="Total Giving" :value="formatCurrency(stats.financial?.total_amount || 0)"
            icon="pi pi-dollar" color="blue" :trend="stats.financial?.month_growth" subtext="since last month" />
      </div>
     <div class="col-12 lg:col-6 xl:col-3">
         <StatWidget title="Active Members" :value="activeMembersCount" icon="pi pi-users" color="orange"
            :subtext="`${stats.membership?.new_this_month || 0} new this month`" />
      </div>
     <div class="col-12 lg:col-6 xl:col-3">
         <StatWidget title="Avg. Contrib." :value="formatCurrency(stats.financial?.average_amount || 0)"
            icon="pi pi-chart-bar" color="cyan" subtext="per transaction" />
      </div>
     <div class="col-12 lg:col-6 xl:col-3">
         <StatWidget title="Unique Givers" :value="stats.financial?.unique_contributors || 0" icon="pi pi-wallet"
            color="purple" subtext="this fiscal year" />
     </div>

    <!-- Main Charts Area -->
      <div class="col-12 xl:col-6">
         <FinancialChart :data="stats.financial?.monthly_trend || []" />
      </div>

    <div class="col-12 xl:col-6">
         <div class="card h-full">
            <div class="flex justify-content-between align-items-center mb-3">
               <span class="text-900 font-medium text-xl">Recent Activity</span>
            </div>
           <ul class="p-0 m-0 list-none">
               <li v-for="activity in stats.recent_activity" :key="activity.id"
                  class="flex align-items-center py-3 border-bottom-1 surface-border">
                  <div
                     class="w-3rem h-3rem flex align-items-center justify-content-center bg-blue-100 border-circle mr-3 flex-shrink-0">
                     <i :class="activity.icon" class="text-xl text-blue-500"></i>
                  </div>
                 <span class="text-900 line-height-3">{{ activity.text }}</span>
                  <span class="ml-auto text-500">{{ activity.time }}</span>
               </li>
            </ul>
         </div>
      </div>

    <!-- Demographics Row -->
      <div class="col-12">
         <DemographicsChart :genderData="stats.membership?.gender_counts || []"
            :ageData="stats.membership?.age_groups || []" />
      </div>
   </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { dashboardService, type DashboardData } from '@/services/dashboardService';
import StatWidget from '@/modules/dashboard/components/StatWidget.vue';
import FinancialChart from '@/modules/dashboard/components/FinancialChart.vue';
import DemographicsChart from '@/modules/dashboard/components/DemographicsChart.vue';

const stats = ref<Partial<DashboardData>>({});

const activeMembersCount = computed(() => {
   const activeStatus = stats.value.membership?.status_counts.find(s => s.MbrMembershipStatus === 'Active');
   return activeStatus?.count || 0;
});

const formatCurrency = (value: number) => {
   return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};

onMounted(async () => {
   try {
      // Attempt to fetch real data
      stats.value = await dashboardService.getDashboardData();
   } catch (e) {
      console.warn('Backend not ready, using mock data for demonstration');
      stats.value = dashboardService.getMockData();
   }
});
</script>

