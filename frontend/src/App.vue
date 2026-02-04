<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner'
</script>

<template>
  <Toaster position="top-right" :expand="false" richColors :close-button="true" closeButtonPosition="top-right" />
  <RouterView />
</template>

