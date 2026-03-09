<template>
    <div class="grid">
        <div class="col-12 xl:col-6">
            <div class="card flex flex-column align-items-center h-full">
                <h5 class="w-full text-left">Gender Distribution</h5>
                <Chart type="doughnut" :data="genderChatData" :options="chartOptions" class="w-full md:w-20rem" />
            </div>
        </div>
        <div class="col-12 xl:col-6">
             <div class="card flex flex-column align-items-center h-full">
                <h5 class="w-full text-left">Age Demographics</h5>
                <Chart type="pie" :data="ageChartData" :options="chartOptions" class="w-full md:w-20rem" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps<{
    genderData: Array<{ MbrGender: string; count: number }>;
    ageData: Array<{ age_group: string; count: number }>;
}>();

const chartOptions = computed(() => {
    const documentStyle = getComputedStyle(document.documentElement);
    const textColor = documentStyle.getPropertyValue('--text-color');

    return {
        plugins: {
            legend: {
                labels: {
                    usePointStyle: true,
                    color: textColor
                }
            }
        }
    };
});

const genderChatData = computed(() => {
    const documentStyle = getComputedStyle(document.documentElement);
    
    return {
        labels: props.genderData.map(d => d.MbrGender || 'Unknown'),
        datasets: [
            {
                data: props.genderData.map(d => d.count),
                backgroundColor: [
                    documentStyle.getPropertyValue('--blue-500'), 
                    documentStyle.getPropertyValue('--pink-500'), 
                    documentStyle.getPropertyValue('--gray-500')
                ],
                hoverBackgroundColor: [
                    documentStyle.getPropertyValue('--blue-400'), 
                    documentStyle.getPropertyValue('--pink-400'), 
                    documentStyle.getPropertyValue('--gray-400')
                ]
            }
        ]
    };
});

const ageChartData = computed(() => {
    const documentStyle = getComputedStyle(document.documentElement);
    const colors = [
        '--cyan-500', '--teal-500', '--green-500', '--lime-500', '--yellow-500', '--orange-500'
    ];

    return {
        labels: props.ageData.map(d => d.age_group),
        datasets: [
            {
                data: props.ageData.map(d => d.count),
                backgroundColor: colors.map(c => documentStyle.getPropertyValue(c)),
                hoverBackgroundColor: colors.map(c => documentStyle.getPropertyValue(c.replace('500', '400')))
            }
        ]
    };
});
</script>
