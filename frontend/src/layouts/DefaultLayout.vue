<template>
  <div class="default-layout">
    <AppHeader />
    <div class="layout-content">
      <AppSidebar />
      <main class="main-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import AppHeader from '@/components/layout/AppHeader.vue';
import AppSidebar from '@/components/layout/AppSidebar.vue';
</script>

<style scoped>
.default-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.layout-content {
  display: flex;
  flex: 1;
}

.main-content {
  flex: 1;
  padding: var(--space-xl);
  overflow-y: auto;
}

@media (max-width: 768px) {
  .layout-content {
    flex-direction: column;
  }
  
  .main-content {
    padding: var(--space-md);
  }
}
</style>
