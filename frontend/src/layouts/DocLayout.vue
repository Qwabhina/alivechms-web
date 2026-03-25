<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ChSidebar, ChSidebarItem, ChTopbar, ChToastContainer } from '@/design-system'

const route = useRoute()
const router = useRouter()

const isSidebarOpen = ref(true)

const sidebarLinks = [
  { id: 'foundation', label: 'Foundation', to: '/docs/foundation' },
  { id: 'core', label: 'Core Components', to: '/docs/core' },
  { id: 'forms', label: 'Forms & Flows', to: '/docs/forms' },
  { id: 'data', label: 'Data Display', to: '/docs/data' },
  { id: 'navigation', label: 'Navigation', to: '/docs/navigation' },
  { id: 'feedback', label: 'UI Cues', to: '/docs/feedback' },
]

const user = {
  name: 'Developer Mode',
  email: 'engineer@alivechms',
  avatar: 'https://i.pravatar.cc/150?img=11'
}

function navigate(to: string) {
  router.push(to)
}
</script>

<template>
  <div class="doc-layout">
    <ChSidebar :collapsed="!isSidebarOpen" class="doc-sidebar">
      <template #logo>
        <div class="doc-logo" v-if="isSidebarOpen">AliveCHMS</div>
        <div class="doc-logo" v-else>DS</div>
      </template>

      <ChSidebarItem
        v-for="link in sidebarLinks"
        :key="link.id"
        :item="link"
        :current-route="route.path"
        :collapsed="!isSidebarOpen"
        @click="navigate(link.to)"
      >
        <template #icon>
          <div class="nav-dot"></div>
        </template>
      </ChSidebarItem>
    </ChSidebar>

    <div class="doc-main">
      <ChTopbar
        :user="user"
        :notification-count="0"
        @toggle-sidebar="isSidebarOpen = !isSidebarOpen"
      >
        <template #search>
          <div class="doc-topbar-title">Design System Docs</div>
        </template>
      </ChTopbar>

      <main class="doc-content">
        <RouterView />
      </main>
    </div>
    
    <!-- Required for rendering toasts -->
    <ChToastContainer />
  </div>
</template>

<style scoped>
.doc-layout {
  display: flex;
  height: 100vh;
  width: 100vw;
  overflow: hidden;
  background-color: var(--ch-color-bg-subtle);
}

.doc-logo {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-bold);
  padding: var(--ch-space-4);
  color: var(--ch-color-text);
  white-space: nowrap;
  letter-spacing: -0.02em;
}

.nav-dot {
  width: 8px;
  height: 8px;
  background: currentColor;
}

.doc-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100vh;
}

.doc-topbar-title {
  font-family: var(--ch-font-display);
  font-weight: var(--ch-font-semibold);
  font-size: var(--ch-text-lg);
  letter-spacing: -0.01em;
}

.doc-content {
  flex: 1;
  overflow-y: auto;
  padding: var(--ch-space-12);
}

/* Page content default styles for children views */
:global(.doc-page) {
  max-width: 1024px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-12);
}

:global(.page-header) {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
}

:global(.page-title) {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-4xl);
  font-weight: var(--ch-font-bold);
  letter-spacing: -0.02em;
  margin: 0;
}

:global(.page-desc) {
  font-size: var(--ch-text-lg);
  color: var(--ch-color-text-muted);
  max-width: 60ch;
  line-height: var(--ch-leading-relaxed);
}

:global(.doc-section) {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
}

:global(.doc-section-title) {
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-semibold);
  padding-bottom: var(--ch-space-4);
  border-bottom: 2px solid var(--ch-color-border-strong);
  margin: 0;
}

:global(.demo-grid) {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--ch-space-6);
}

:global(.demo-block) {
  padding: var(--ch-space-6);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

:global(.demo-title) {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-bold);
  text-transform: uppercase;
  color: var(--ch-color-text-subtle);
  letter-spacing: 0.05em;
  margin-bottom: var(--ch-space-2);
}
</style>
