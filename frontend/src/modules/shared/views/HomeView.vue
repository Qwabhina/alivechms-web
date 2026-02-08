<template>
  <div class="home-view">
    <h1>AliveChMS</h1>
    <p>Frontend Application - Phase 1 Setup Complete</p>
    <div class="status">
      <h2>✅ Completed:</h2>
      <ul>
        <li>Vue 3 with TypeScript</li>
        <li>Pinia (State Management)</li>
        <li>Vue Router</li>
        <li>PrimeVue UI Components</li>
        <li>Axios APIClient with interceptors</li>
        <li>Authentication Store</li>
        <li>Theme & Styling System</li>
        <li>Layouts (Default & Auth)</li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.home-view {
  padding: var(--space-2xl);
  text-align: center;
}

h1 {
  color: var(--color-primary);
  font-size: var(--font-size-3xl);
  margin-bottom: var(--space-lg);
}

.status {
  max-width: 600px;
  margin: var(--space-2xl) auto;
  padding: var(--space-xl);
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
}

.status h2 {
  color: var(--color-success);
  margin-bottom: var(--space-md);
}

.status ul {
  text-align: left;
  list-style-position: inside;
}

.status li {
  padding: var(--space-sm) 0;
  color: var(--color-text);
}
</style>
