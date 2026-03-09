<template>
    <div class="card mb-0">
        <div class="flex justify-content-between mb-3">
            <div>
                <span class="block text-500 font-medium mb-3">{{ title }}</span>
                <div class="text-900 font-medium text-xl">{{ value }}</div>
            </div>
            <div class="flex align-items-center justify-content-center border-round"
                 :style="{ width: '2.5rem', height: '2.5rem', backgroundColor: `var(--${color}-100)`, color: `var(--${color}-500)` }">
                <i :class="icon" class="text-xl"></i>
            </div>
        </div>
        <span v-if="trend !== undefined" :class="trend >= 0 ? 'text-green-500' : 'text-red-500'" class="font-medium">
            <i :class="trend >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" class="text-xs"></i>
            {{ Math.abs(trend) }}%
            <span class="text-500 font-normal ml-1">{{ subtext }}</span>
        </span>
        <span v-else class="text-500">{{ subtext }}</span>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    value: string | number;
    icon: string;
    color: string;
    trend?: number;
    subtext?: string;
}>();
</script>
