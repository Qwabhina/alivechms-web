<script setup lang="ts">
import { ref } from 'vue'
import { ChTable, ChStatCard, ChDataList } from '@/design-system'

const tableData = ref([
  { id: 1, name: 'Alice', status: 'Active' },
  { id: 2, name: 'Bob', status: 'Pending' }
])

const tableColumns = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Name' },
  { key: 'status', label: 'Status' }
]

const listData = [
  { id: 1, label: 'Server 1', value: 'Online', avatar: 'https://i.pravatar.cc/150?u=1' },
  { id: 2, label: 'Server 2', value: 'Offline', avatar: 'https://i.pravatar.cc/150?u=2' }
]
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Data Display</h1>
      <p class="page-desc">High density tables, lists, and statistical cards optimized for deep metric interfaces.</p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Stat Cards</h2>
      <div class="demo-grid">
        <ChStatCard
          label="Total Revenue"
          :value="45231.89"
          :trend="12.5"
          trend-label="+12.5% vs last month"
          icon="currency-dollar"
        />
        <ChStatCard
          label="Active Users"
          :value="1204"
          :trend="-5.2"
          trend-label="-5.2% vs last month"
          icon="users"
        />
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Data Table</h2>
      <div class="demo-block">
        <ChTable
          :rows="tableData"
          :columns="tableColumns"
          row-key="id"
        />
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Data List</h2>
      <div class="demo-block">
        <ChDataList :items="listData" />
      </div>
    </section>
  </div>
</template>
