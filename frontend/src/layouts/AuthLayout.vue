<template>
  <div class="auth-layout" :style="{ backgroundColor: 'var(--color-primary)' }">
    <div class="auth-content">
      <router-view />
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-lg);
}

.auth-content {
  width: 100%;
  max-width: 420px;
}
</style>
