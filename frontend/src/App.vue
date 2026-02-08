<template>
  <component :is="currentLayout">
    <router-view />
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DefaultLayout from './layouts/DefaultLayout.vue';
import AuthLayout from './layouts/AuthLayout.vue';

const route = useRoute();

const currentLayout = computed(() => {
  const layoutName = route.meta.layout as string || 'default';
  return layoutName === 'auth' ? AuthLayout : DefaultLayout;
});
</script>

<style>
/* Global styles are in styles/theme.css */
</style>
