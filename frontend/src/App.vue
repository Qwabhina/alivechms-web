<template>
  <Toast />
  <component :is="currentLayout">
    <router-view />
  </component>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import DefaultLayout from './layouts/DefaultLayout.vue';
import AuthLayout from './layouts/AuthLayout.vue';

const route = useRoute();
const toast = useToast();

const currentLayout = computed(() => {
  const layoutName = route.meta.layout as string || 'default';
  return layoutName === 'auth' ? AuthLayout : DefaultLayout;
});

const handleToast = (event: Event) => {
  const customEvent = event as CustomEvent;
  toast.add(customEvent.detail);
};

onMounted(() => {
  document.addEventListener('show-toast', handleToast);
});

onUnmounted(() => {
  document.removeEventListener('show-toast', handleToast);
});
</script>

<style>
/* Global styles are in styles/theme.css */
</style>
