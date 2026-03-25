<script setup lang="ts">
import { ref } from 'vue'
import { ChButton, ChCard, ChAvatar, ChBadge, ChDivider, ChInput, ChTextarea } from '@/design-system'

const inputValue = ref('')
const textareaValue = ref('')
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Core Components</h1>
      <p class="page-desc">Essential building blocks including Buttons, Cards, Inputs, and Badges. All adhering to the new sharp, offset geometry.</p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Buttons</h2>
      <div class="demo-grid">
        <div class="demo-block">
          <div class="demo-title">Variants</div>
          <div style="display: flex; gap: 1rem; flex-wrap: wrap">
            <ChButton variant="primary">Primary</ChButton>
            <ChButton variant="secondary">Secondary</ChButton>
            <ChButton variant="outline">Outline</ChButton>
            <ChButton variant="ghost">Ghost</ChButton>
            <ChButton variant="danger">Danger</ChButton>
          </div>
        </div>
        <div class="demo-block">
          <div class="demo-title">States</div>
          <div style="display: flex; gap: 1rem; flex-wrap: wrap">
            <ChButton disabled>Disabled</ChButton>
            <ChButton loading>Loading</ChButton>
          </div>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Cards</h2>
      <div class="demo-grid">
        <ChCard>
          <template #header><h3 style="margin: 0; font-family: var(--ch-font-display); font-size: 1.5rem">Standard Card</h3></template>
          <p style="margin: 0; color: var(--ch-color-text-muted)">Cards have stark 0px radii and heavy borders to match the mechanical aesthetic.</p>
          <template #footer>
            <ChButton size="sm" variant="outline">Learn More</ChButton>
          </template>
        </ChCard>
        
        <ChCard hoverable>
          <template #header><h3 style="margin: 0; font-family: var(--ch-font-display); font-size: 1.5rem">Hoverable Card</h3></template>
          <p style="margin: 0; color: var(--ch-color-text-muted)">Hovering triggers the brutalist offset shadow translation mechanism.</p>
        </ChCard>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Avatar & Badge</h2>
      <div class="demo-grid">
        <div class="demo-block">
          <div class="demo-title">Square Avatars</div>
          <div style="display: flex; gap: 1rem; align-items: center">
            <ChAvatar size="sm" src="https://i.pravatar.cc/150?img=1" />
            <ChAvatar size="md" src="https://i.pravatar.cc/150?img=2" status="online" />
            <ChAvatar size="lg" src="https://i.pravatar.cc/150?img=3" status="busy" />
            <ChAvatar size="xl">JD</ChAvatar>
          </div>
        </div>
        <div class="demo-block">
          <div class="demo-title">Sharp Badges</div>
          <div style="display: flex; gap: 0.5rem; flex-wrap: wrap">
            <ChBadge variant="default">Default</ChBadge>
            <ChBadge variant="primary">Primary</ChBadge>
            <ChBadge variant="success">Success</ChBadge>
            <ChBadge variant="warning">Warning</ChBadge>
            <ChBadge variant="danger">Danger</ChBadge>
          </div>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Inputs</h2>
      <div class="demo-grid">
        <div class="demo-block">
          <div class="demo-title">Text Input</div>
          <ChInput v-model="inputValue" label="Email Address" placeholder="Enter your email" />
          <div style="margin-top: 1rem">
            <ChInput model-value="Error specific" label="Username" error="This username is taken" />
          </div>
        </div>
        <div class="demo-block">
          <div class="demo-title">Textarea</div>
          <ChTextarea v-model="textareaValue" label="Biography" placeholder="Tell us about yourself" :rows="4" show-count :maxlength="200" />
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Divider</h2>
      <div class="demo-block">
        <p style="margin: 0">Content Above</p>
        <ChDivider />
        <p style="margin: 0">Content Below</p>
      </div>
    </section>

  </div>
</template>
