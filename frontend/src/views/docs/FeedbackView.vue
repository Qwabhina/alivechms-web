<script setup lang="ts">
import { ref } from 'vue'
import { ChSpinner, ChSkeleton, ChProgress, ChAlert, ChButton, useToast } from '@/design-system'

const progress = ref(65)
const toast = useToast()

const showSuccess = () => toast.success('Operation completed successfully')
const showError = () => toast.error('A critical error occurred while saving')
const showInfo = () => toast.info('A new software update is available.')
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">UI Cues & Feedback</h1>
      <p class="page-desc">Components that communicate system status: Toast notifications, stark alerts, loading spinners, and sharp progress bars.</p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Alerts & Messaging</h2>
      <div class="demo-grid" style="grid-template-columns: 1fr">
        <div class="demo-block" style="gap: 1rem">
          <ChAlert variant="info" title="Information">
            Please make sure your email address is verified before changing the password.
          </ChAlert>
          <ChAlert variant="success" title="Success">
            Your profile has been updated successfully.
          </ChAlert>
          <ChAlert variant="warning" title="Warning">
            Your subscription will expire in 3 days. Please renew to avoid interruption.
          </ChAlert>
          <ChAlert variant="danger" title="System Error">
            Unable to connect to the database. Trying again in 30 seconds.
          </ChAlert>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Toast Notifications</h2>
      <div class="demo-block">
        <p style="margin-top: 0">The toast system uses mechanical blocks sliding into view from the bottom-right.</p>
        <div style="display: flex; gap: 1rem; margin-top: 1rem">
          <ChButton variant="outline" size="sm" @click="showSuccess">Show Success Toast</ChButton>
          <ChButton variant="danger" size="sm" @click="showError">Show Error Toast</ChButton>
          <ChButton variant="outline" size="sm" @click="showInfo">Show Info Toast</ChButton>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Progress & Loading</h2>
      <div class="demo-grid">
        <div class="demo-block">
          <div class="demo-title">Progress Bar</div>
          <p>Zero radius tracker matching the grid structure.</p>
          <ChProgress :value="progress" style="margin-bottom: 2rem" />
          <ChButton size="sm" variant="outline" @click="progress = (progress + 15) % 100">Advance Progress</ChButton>
        </div>
        <div class="demo-block">
          <div class="demo-title">Spinner</div>
          <div style="display: flex; gap: 1rem; align-items: center">
            <ChSpinner size="sm" />
            <ChSpinner size="md" color="primary" />
            <ChSpinner size="lg" color="danger" />
          </div>
        </div>
        <div class="demo-block">
          <div class="demo-title">Skeleton Loader</div>
          <div style="display: flex; flex-direction: column; gap: 0.5rem">
            <ChSkeleton width="80%" height="24px" />
            <ChSkeleton width="100%" height="16px" />
            <ChSkeleton width="60%" height="16px" />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
