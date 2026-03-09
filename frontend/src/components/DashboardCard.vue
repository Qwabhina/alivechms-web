<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
   title: string;
   icon: string;
   color: string;
   value: string | number;
   description?: string;
   trend?: number;
}>();

const iconClass = computed(() => `pi ${props.icon}`);
</script>

<template>
   <div class="card mb-0">
      <div class="flex justify-content-between mb-3">
         <div>
            <span class="block text-500 font-medium mb-3">{{ title }}</span>
            <div class="text-900 font-medium text-xl">{{ value }}</div>
         </div>
         <div class="flex align-items-center justify-content-center border-round"
            :style="{ width: '2.5rem', height: '2.5rem', backgroundColor: `var(--${color}-100)`, color: `var(--${color}-500)` }">
            <i :class="iconClass" class="text-xl"></i>
         </div>
      </div>
      <span v-if="trend !== undefined" class="text-green-500 font-medium">
         {{ trend > 0 ? '+' : '' }}{{ trend }}%
      </span>
      <span v-if="description" class="text-500">{{ description }}</span>
   </div>
</template>
