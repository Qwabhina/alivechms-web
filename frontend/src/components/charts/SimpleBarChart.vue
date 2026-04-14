<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ labels: string[]; values: number[]; height?: number }>()

const max = computed(() => Math.max(...(props.values ?? [0]), 1))

function barWidth(i: number) {
  const v = (props.values && props.values[i]) ?? 0
  return ((v / max.value) * 100) + '%'
}
</script>

<template>
  <div class="simple-bar-chart" :style="{ height: (props.height || 120) + 'px' }">
    <div v-for="(label, i) in props.labels" :key="label" class="row">
      <div class="label">{{ label }}</div>
      <div class="bar-wrap">
        <div class="bar" :style="{ width: barWidth(i) }"></div>
      </div>
      <div class="value">{{ (props.values && props.values[i]) ?? 0 }}</div>
    </div>
  </div>
</template>

<style scoped>
.simple-bar-chart { display:flex; flex-direction:column; gap:8px }
.row { display:flex; align-items:center; gap:8px }
.label { width:120px; color:var(--ch-color-text-muted); font-size:0.9rem }
.bar-wrap { flex:1; background:var(--ch-color-bg); height:14px; border-radius:6px; overflow:hidden }
.bar { height:100%; background:var(--ch-color-primary); border-radius:6px }
.value { width:40px; text-align:right; color:var(--ch-color-text) }
</style>
