<script setup lang="ts">
/**
 * AuthLayout — Minimal centered layout for login/registration pages.
 */
</script>

<template>
  <div class="auth-layout">
    <slot />
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
