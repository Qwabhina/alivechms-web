<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>

<template>
  <RouterView />
</template>

<style>
/* Import Inter font for premium typography */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

/* Global resets — design system tokens are injected via injectCSSVars() */
*,
*::before,
*::after {
  box-sizing: border-box;
}

body {
  margin: 0;
  padding: 0;
  font-family: 'Inter', var(--ch-font-family, system-ui, -apple-system, sans-serif);
  background-color: var(--ch-color-bg-subtle, #f5f6fa);
  color: var(--ch-color-text, #111827);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

a {
  color: inherit;
  text-decoration: none;
}

h1, h2, h3, h4, h5, h6 {
  margin: 0;
}

button {
  font-family: inherit;
}
</style>
