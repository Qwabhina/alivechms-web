<script setup lang="ts">
import { useSlots } from 'vue'
import type { PropType } from 'vue'

const slots = useSlots()
const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  titleTag: {
    type: String as PropType<'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6'>,
    default: 'h1',
  },
})
</script>

<template>
  <div class="ch-page-header">
    <div v-if="slots.leading" class="ch-page-header__leading">
      <slot name="leading" />
    </div>

    <div class="ch-page-header__title-group">
      <div v-if="slots.icon" class="ch-page-header__icon">
        <slot name="icon" />
      </div>
      <div class="ch-page-header__text">
        <component :is="props.titleTag" class="ch-page-header__title">
          {{ props.title }}
        </component>
        <p v-if="props.subtitle" class="ch-page-header__subtitle">{{ props.subtitle }}</p>
      </div>
    </div>

    <div v-if="slots.actions" class="ch-page-header__actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<style scoped>
.ch-page-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--ch-space-4);
  margin-bottom: var(--ch-space-6);
}

.ch-page-header__leading {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  min-width: 0;
}

.ch-page-header__title-group {
  display: flex;
  align-items: flex-start;
  gap: var(--ch-space-3);
  flex: 1 1 0;
  min-width: 0;
}

.ch-page-header__text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.ch-page-header__title {
  margin: 0;
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
  line-height: var(--ch-leading-tight);
}

.ch-page-header__subtitle {
  margin: var(--ch-space-1) 0 0;
  color: var(--ch-color-text-muted);
  font-size: var(--ch-text-sm);
}

.ch-page-header__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-3);
  align-items: center;
  justify-items: middle;
  justify-content: flex-end;
}
</style>
