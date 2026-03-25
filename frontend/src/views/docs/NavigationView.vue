<script setup lang="ts">
import { ChTabs } from '@/design-system'
import { ref } from 'vue'

const activeTab = ref('tab1')
const verticalTab = ref('vtab1')
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Navigation</h1>
      <p class="page-desc">The navigational components including tabs, structural sidebars, and topbars built with the zero-radius aesthetic.</p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Tabs</h2>
      <div class="demo-grid">
        <div class="demo-block">
          <div class="demo-title">Horizontal Tabs</div>
          <ChTabs
            v-model="activeTab"
            :tabs="[
              {value: 'tab1', label: 'Details'},
              {value: 'tab2', label: 'Security'},
              {value: 'tab3', label: 'Billing'}
            ]"
          />
          <div style="padding: 1rem; margin-top: 1rem; border: 1px dashed var(--ch-color-border-strong)">
            Content for {{ activeTab }}
          </div>
        </div>

        <div class="demo-block">
          <div class="demo-title">Vertical Tabs</div>
          <ChTabs
            v-model="verticalTab"
            vertical
            :tabs="[
              {value: 'vtab1', label: 'General'},
              {value: 'vtab2', label: 'Notifications'},
              {value: 'vtab3', label: 'Advanced'}
            ]"
          />
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Layout Navigation</h2>
      <div class="demo-block">
        <p>The <b>Sidebar</b> and <b>Topbar</b> components are actively used to render this very documentation frame. They utilize heavy monolithic borders instead of soft drop shadows.</p>
        <p style="margin-top: 1rem; color: var(--ch-color-text-subtle)">Check `src/layouts/DocLayout.vue` for integration examples.</p>
      </div>
    </section>
  </div>
</template>
