<template>
   <div class="card h-full">
      <div class="flex justify-content-between align-items-center mb-3">
         <span class="text-900 font-medium text-xl">Financial Overview</span>
         <div class="flex gap-2">
            <!-- Time range selectors could go here -->
         </div>
      </div>
      <Chart type="bar" :data="chartData" :options="chartOptions" class="h-20rem" />
   </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Chart from 'primevue/chart';
// import { useLayout } from '@/layout/composables/layout';

const props = defineProps<{
   data: Array<{
      month_label: string;
      total: number;
      count: number;
   }>;
}>();

// const { isDarkTheme } = useLayout();

const chartData = computed(() => {
   const documentStyle = getComputedStyle(document.documentElement);

   return {
      labels: props.data.map(d => d.month_label),
      datasets: [
         {
            label: 'Total Giving',
            backgroundColor: documentStyle.getPropertyValue('--primary-500'),
            borderColor: documentStyle.getPropertyValue('--primary-500'),
            data: props.data.map(d => d.total),
            yAxisID: 'y'
         },
         {
            label: 'Transaction Count',
            backgroundColor: documentStyle.getPropertyValue('--primary-200'),
            borderColor: documentStyle.getPropertyValue('--primary-200'),
            data: props.data.map(d => d.count),
            yAxisID: 'y1'
         }
      ]
   };
});

const chartOptions = computed(() => {
   const documentStyle = getComputedStyle(document.documentElement);
   const textColor = documentStyle.getPropertyValue('--text-color');
   const textColorSecondary = documentStyle.getPropertyValue('--text-color-secondary');
   const surfaceBorder = documentStyle.getPropertyValue('--surface-border');

   return {
      maintainAspectRatio: false,
      aspectRatio: 0.8,
      plugins: {
         legend: {
            labels: {
               color: textColor
            }
         },
         tooltips: {
            mode: 'index',
            intersect: false
         }
      },
      scales: {
         x: {
            ticks: {
               color: textColorSecondary,
               font: {
                  weight: 500
               }
            },
            grid: {
               display: false,
               drawBorder: false
            }
         },
         y: {
            type: 'linear',
            display: true,
            position: 'left',
            ticks: {
               color: textColorSecondary,
               callback: function (value: any) {
                  return '$' + value;
               }
            },
            grid: {
               color: surfaceBorder,
               drawBorder: false
            }
         },
         y1: {
            type: 'linear',
            display: true,
            position: 'right',
            ticks: {
               color: textColorSecondary
            },
            grid: {
               drawOnChartArea: false
            }
         }
      }
   };
});
</script>
