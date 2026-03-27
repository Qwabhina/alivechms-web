<script setup lang="ts">
/**
 * IntroductionView.vue - Design System Overview
 * 
 * This view provides the high-level context, goals, and core values
 * of the AliveCHMS brutalist-lite design system.
 */

import { ChCard, ChBadge, ChButton } from '@/design-system'
import {
  ArrowRight,
  Zap,
  Shield,
  Layout,
  Type,
  Palette,
  ExternalLink
} from 'lucide-vue-next'
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Introduction</h1>
      <p class="page-desc">
        AliveCHMS Design System is a premium, "brutalist-lite" UI framework built for
        modern church management. It prioritizes clarity, performance, and a
        distinctive architectural aesthetic.
      </p>
    </header>

    <section class="doc-section">
      <div class="hero-card">
        <div class="hero-content">
          <h2 class="hero-title">A Unified Visual Language</h2>
          <p class="hero-text">
            Moving beyond the generic "SaaS-blue" aesthetic, AliveCHMS adopts a bold,
            high-contrast design system that feels both professional and state-of-the-art.
            By combining stark geometry with editorial typography, we create an
            interface that is as functional as it is beautiful.
          </p>
          <div class="hero-actions">
            <RouterLink to="/docs/foundation" v-slot="{ href, navigate }">
              <ChButton variant="primary" size="lg" :href="href" @click="navigate">
                Explore Foundations
                <template #trailingIcon>
                  <ArrowRight :size="18" />
                </template>
              </ChButton>
            </RouterLink>
            <ChButton variant="outline" size="lg">
              View on GitHub
              <template #trailingIcon>
                <ExternalLink :size="18" />
              </template>
            </ChButton>
          </div>
        </div>
        <div class="hero-visual">
          <div class="visual-block block-1"></div>
          <div class="visual-block block-2"></div>
          <div class="visual-block block-3"></div>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Core Pillars</h2>
      <div class="card-grid card-grid--3">
        <ChCard padding="lg">
          <div class="pillar-icon zap">
            <Zap :size="24" />
          </div>
          <h3 class="pillar-title">Performance First</h3>
          <p class="pillar-text">
            Built with Vue 3 and Vite, the system is optimized for lightning-fast
            interactions and minimal bundle size.
          </p>
        </ChCard>

        <ChCard padding="lg">
          <div class="pillar-icon shield">
            <Shield :size="24" />
          </div>
          <h3 class="pillar-title">Type Safe</h3>
          <p class="pillar-text">
            Written entirely in TypeScript with strict prop validation and
            comprehensive token definitions.
          </p>
        </ChCard>

        <ChCard padding="lg">
          <div class="pillar-icon layout">
            <Layout :size="24" />
          </div>
          <h3 class="pillar-title">Modular Architecture</h3>
          <p class="pillar-text">
            Components are loosely coupled and highly composable, allowing for
            rapid development and easy maintenance.
          </p>
        </ChCard>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Why "Brutalist-Lite"?</h2>
      <div class="demo-block">
        <div class="philosophy-grid">
          <div class="philosophy-item">
            <div class="item-header">
              <Type :size="20" />
              <h4>Editorial Typography</h4>
            </div>
            <p>
              We use <strong>Instrument Serif</strong> for headings to bring an
              editorial, premium feel to the data-heavy interface.
            </p>
          </div>
          <div class="philosophy-item">
            <div class="item-header">
              <Palette :size="20" />
              <h4>Slate Palette</h4>
            </div>
            <p>
              High-contrast monochromatic slates replace soft indigos, ensuring
              accessibility and a sophisticated "printed" look.
            </p>
          </div>
          <div class="philosophy-item">
            <div class="item-header">
              <Layout :size="20" />
              <h4>Sharp Geometry</h4>
            </div>
            <p>
              Global <code>0px</code> border-radius creates a rigid, architectural
              presence that stands out from competitors.
            </p>
          </div>
          <div class="philosophy-item">
            <div class="item-header">
              <Zap :size="20" />
              <h4>Block Shadows</h4>
            </div>
            <p>
              Shadows are harsh <code>4px 4px 0px</code> offsets rather than
              blurry gradients, providing tactile feedback.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Project Roadmap</h2>
      <ChCard>
        <div class="roadmap-list">
          <div class="roadmap-item done">
            <div class="roadmap-status">
              <ChBadge variant="success" size="sm" :dot="true">Completed</ChBadge>
            </div>
            <div class="roadmap-content">
              <h4>Design Token System</h4>
              <p>Foundational colors, typography, spacing, and shadows.</p>
            </div>
          </div>
          <div class="roadmap-item active">
            <div class="roadmap-status">
              <ChBadge variant="info" size="sm" :dot="true">In Progress</ChBadge>
            </div>
            <div class="roadmap-content">
              <h4>Core Components</h4>
              <p>Buttons, Cards, Inputs, and basic layout building blocks.</p>
            </div>
          </div>
          <div class="roadmap-item pending">
            <div class="roadmap-status">
              <ChBadge variant="default" size="sm" :dot="true">Planned</ChBadge>
            </div>
            <div class="roadmap-content">
              <h4>Complex Patterns</h4>
              <p>Data tables, advanced form wizards, and interactive dashboards.</p>
            </div>
          </div>
        </div>
      </ChCard>
    </section>
  </div>
</template>

<style scoped>
.hero-card {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: var(--ch-space-8);
  padding: var(--ch-space-8);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  box-shadow: var(--ch-shadow);
  overflow: hidden;
}

@media (max-width: 1024px) {
  .hero-card {
    grid-template-columns: 1fr;
  }

  .hero-visual {
    display: none;
  }
}

.hero-title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-4xl);
  font-weight: var(--ch-font-bold);
  margin-bottom: var(--ch-space-4);
  color: var(--ch-color-text);
}

.hero-text {
  font-size: var(--ch-text-lg);
  line-height: var(--ch-leading-relaxed);
  color: var(--ch-color-text-muted);
  margin-bottom: var(--ch-space-8);
  max-width: 600px;
}

.hero-actions {
  display: flex;
  gap: var(--ch-space-4);
  flex-wrap: wrap;
}

.hero-visual {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.visual-block {
  border: 2px solid var(--ch-color-border-strong);
  background: var(--ch-color-bg-subtle);
  position: absolute;
}

.block-1 {
  width: 120px;
  height: 120px;
  top: 10%;
  right: 10%;
  background: var(--ch-color-primary);
  z-index: 1;
}

.block-2 {
  width: 160px;
  height: 160px;
  bottom: 10%;
  left: 10%;
  box-shadow: 8px 8px 0px var(--ch-color-border-strong);
}

.block-3 {
  width: 100px;
  height: 100px;
  top: 40%;
  left: 40%;
  border-style: dashed;
}

.pillar-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--ch-space-4);
  border: 1px solid var(--ch-color-border-strong);
}

.pillar-icon.zap {
  background: #fef3c7;
  color: #d97706;
}

.pillar-icon.shield {
  background: #dcfce7;
  color: #16a34a;
}

.pillar-icon.layout {
  background: #dbeafe;
  color: #2563eb;
}

.pillar-title {
  font-size: var(--ch-text-xl);
  font-weight: var(--ch-font-bold);
  margin-bottom: var(--ch-space-2);
}

.pillar-text {
  color: var(--ch-color-text-muted);
  line-height: var(--ch-leading-normal);
}

.philosophy-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--ch-space-8);
}

@media (max-width: 768px) {
  .philosophy-grid {
    grid-template-columns: 1fr;
  }
}

.item-header {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  margin-bottom: var(--ch-space-2);
  color: var(--ch-color-primary);
}

.item-header h4 {
  font-weight: var(--ch-font-bold);
  font-size: var(--ch-text-lg);
  color: var(--ch-color-text);
  margin: 0;
}

.roadmap-list {
  display: flex;
  flex-direction: column;
}

.roadmap-item {
  display: flex;
  padding: var(--ch-space-4);
  gap: var(--ch-space-6);
  border-bottom: 1px solid var(--ch-color-border);
}

.roadmap-item:last-child {
  border-bottom: none;
}

.roadmap-status {
  width: 100px;
  flex-shrink: 0;
}

.roadmap-content h4 {
  font-weight: var(--ch-font-semibold);
  margin-bottom: var(--ch-space-1);
}

.roadmap-content p {
  color: var(--ch-color-text-muted);
  font-size: var(--ch-text-sm);
}

.roadmap-item.done h4 {
  text-decoration: line-through;
  opacity: 0.7;
}
</style>
