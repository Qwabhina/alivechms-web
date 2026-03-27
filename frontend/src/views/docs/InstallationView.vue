<script setup lang="ts">
/**
 * InstallationView.vue - Technical Setup Guide
 * 
 * Instructions for installing and configuring the design system.
 */

import { ChCard, ChButton, ChDivider } from '@/design-system'
import { 
  Terminal, 
  Code2, 
  Info,
  CheckCircle2,
  Copy
} from 'lucide-vue-next'

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text)
  // In a real app, I'd trigger a toast here
}
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Installation</h1>
      <p class="page-desc">
        Everything you need to get the AliveCHMS design system up and running 
        in your project.
      </p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Initial Setup</h2>
      <p>
        The design system relies on CSS variables for theming. You must inject 
        these variables into your application entry point (usually <code>main.ts</code>).
      </p>

      <div class="step-list">
        <!-- Step 1 -->
        <div class="setup-step">
          <div class="step-number">01</div>
          <div class="step-content">
            <h4>Import and Inject CSS Variables</h4>
            <p>
              Call <code>injectCSSVars()</code> before mounting your app to ensure 
              all tokens are available.
            </p>
            <div class="code-block-wrapper">
              <div class="code-header">
                <Code2 :size="16" />
                <span>main.ts</span>
              </div>
              <code>import { createApp } from 'vue'
import { injectCSSVars } from '@alivechms/design-system'
import '@alivechms/design-system/styles/base.css'
import App from './App.vue'

// Initialize CSS variables
injectCSSVars()

createApp(App).mount('#app')</code>
            </div>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="setup-step">
          <div class="step-number">02</div>
          <div class="step-content">
            <h4>Import Animations (Optional)</h4>
            <p>
              If you plan to use the built-in animations, import the animation styles.
            </p>
            <div class="code-block-wrapper">
              <pre><code>&lt;script  setup lang="ts"&gt; import '@alivechms/design-system/styles/animations.css' &lt;/script&gt;</code></pre>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Basic Usage</h2>
      <p>Now you can start using components in your Vue templates:</p>

      <ChCard>
        <div class="usage-demo">
          <div class="demo-preview">
            <ChButton variant="primary">Hello AliveCHMS</ChButton>
          </div>
          <div class="demo-code">
            <pre><code>&lt;script setup lang="ts"&gt;
import { ChButton } from '@alivechms/design-system'
&lt;/script&gt;

&lt;template&gt;
  &lt;ChButton variant="primary"&gt;
    Hello AliveCHMS
  &lt;/ChButton&gt;
&lt;/template&gt;</code></pre>
          </div>
        </div>
      </ChCard>
    </section>

    <section class="doc-section">
      <div class="info-note">
        <Info :size="20" class="note-icon" />
        <div class="note-content">
          <h4>Vite Alias Setup</h4>
          <p>
            If you are developing inside the AliveCHMS monorepo, you can use the 
            <code>@/design-system</code> alias instead of the package name.
          </p>
        </div>
      </div>
    </section>

    <ChDivider />

    <section class="doc-section next-steps">
      <h2 class="doc-section-title">Next Steps</h2>
      <div class="next-grid">
        <RouterLink to="/docs/foundation" class="next-link">
          <div class="link-icon">
            <CheckCircle2 :size="24" />
          </div>
          <div class="link-text">
            <strong>Understand Foundations</strong>
            <span>Learn about colors, spacing, and shadows.</span>
          </div>
        </RouterLink>
        <RouterLink to="/docs/core" class="next-link">
          <div class="link-icon">
            <Code2 :size="24" />
          </div>
          <div class="link-text">
            <strong>Browse Components</strong>
            <span>Check out the library of available components.</span>
          </div>
        </RouterLink>
      </div>
    </section>
  </div>
</template>

<style scoped>
.code-terminal {
  background: #1e293b;
  border: 1px solid var(--ch-color-border-strong);
  margin-top: var(--ch-space-4);
}

.terminal-header {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  padding: var(--ch-space-2) var(--ch-space-4);
  background: #0f172a;
  color: #94a3b8;
  font-size: var(--ch-text-xs);
  border-bottom: 1px solid #334155;
}

.copy-btn {
  margin-left: auto;
  background: transparent;
  border: none;
  color: inherit;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
}

.copy-btn:hover {
  color: white;
}

.terminal-body {
  padding: var(--ch-space-6);
  color: #38bdf8;
  font-family: var(--ch-font-mono);
}

.setup-step {
  display: flex;
  gap: var(--ch-space-6);
  margin-bottom: var(--ch-space-8);
}

.step-number {
  width: 40px;
  height: 40px;
  background: var(--ch-color-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: var(--ch-font-bold);
  flex-shrink: 0;
}

.step-content h4 {
  font-weight: var(--ch-font-bold);
  font-size: var(--ch-text-lg);
  margin-bottom: var(--ch-space-2);
}

.code-block-wrapper {
  background: #f8fafc;
  border: 1px solid var(--ch-color-border);
  margin-top: var(--ch-space-4);
}

.dark .code-block-wrapper {
  background: #0f172a;
  border-color: #334155;
}

.code-header {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  padding: var(--ch-space-2) var(--ch-space-4);
  background: #f1f5f9;
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  border-bottom: 1px solid var(--ch-color-border);
}

.dark .code-header {
  background: #1e293b;
  border-bottom-color: #334155;
  color: #94a3b8;
}

pre {
  padding: var(--ch-space-4);
  margin: 0;
  overflow-x: auto;
  font-family: var(--ch-font-mono);
  font-size: var(--ch-text-sm);
  line-height: 1.5;
}

.usage-demo {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-6);
}

@media (max-width: 768px) {
  .usage-demo {
    grid-template-columns: 1fr;
  }
}

.demo-preview {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ch-color-bg-subtle);
  border: 1px dashed var(--ch-color-border-strong);
  padding: var(--ch-space-8);
}

.info-note {
  display: flex;
  gap: var(--ch-space-4);
  padding: var(--ch-space-4);
  background: #eff6ff;
  border-left: 4px solid #3b82f6;
  color: #1e40af;
}

.dark .info-note {
  background: #1e3a8a;
  border-left-color: #60a5fa;
  color: #dbeafe;
}

.note-icon {
  flex-shrink: 0;
}

.note-content h4 {
  font-weight: var(--ch-font-bold);
  margin-bottom: var(--ch-space-1);
}

.note-content p {
  margin: 0;
  font-size: var(--ch-text-sm);
}

.next-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-6);
}

@media (max-width: 640px) {
  .next-grid {
    grid-template-columns: 1fr;
  }
}

.next-link {
  display: flex;
  align-items: center;
  gap: var(--ch-space-4);
  padding: var(--ch-space-6);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
}

.next-link:hover {
  transform: translateY(-4px);
  box-shadow: var(--ch-shadow-sm);
  border-color: var(--ch-color-primary);
}

.link-icon {
  color: var(--ch-color-primary);
}

.link-text {
  display: flex;
  flex-direction: column;
}

.link-text strong {
  font-size: var(--ch-text-lg);
  margin-bottom: 2px;
}

.link-text span {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}
</style>
