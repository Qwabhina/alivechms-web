<template>
   <div class="unauthorized-view">
      <div class="content">
         <div class="icon-container">
            <i class="pi pi-ban"></i>
         </div>

         <h1>Access Denied</h1>
         <p class="message">
            You don't have permission to access this resource.
         </p>

         <div class="actions">
            <button @click="goBack" class="btn-secondary">
               <i class="pi pi-arrow-left"></i> Go Back
            </button>
            <router-link to="/" class="btn-primary">
               <i class="pi pi-home"></i> Go to Dashboard
            </router-link>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';

const router = useRouter();

function goBack() {
   router.back();
}
</script>

<style scoped>
.unauthorized-view {
   min-height: calc(100vh - 64px);
   display: flex;
   align-items: center;
   justify-content: center;
   padding: var(--space-2xl);
}

.content {
   text-align: center;
   max-width: 500px;
}

.icon-container {
   margin-bottom: var(--space-xl);
}

.icon-container i {
   font-size: 6rem;
   color: var(--color-error);
}

h1 {
   color: var(--color-text);
   font-size: var(--font-size-3xl);
   margin-bottom: var(--space-md);
}

.message {
   color: var(--color-text-muted);
   font-size: var(--font-size-lg);
   margin-bottom: var(--space-2xl);
}

.actions {
   display: flex;
   gap: var(--space-md);
   justify-content: center;
   flex-wrap: wrap;
}

.btn-primary,
.btn-secondary {
   padding: var(--space-sm) var(--space-xl);
   border-radius: var(--radius-md);
   font-size: var(--font-size-base);
   font-weight: 600;
   cursor: pointer;
   transition: all var(--transition-fast);
   text-decoration: none;
   display: inline-flex;
   align-items: center;
   gap: var(--space-sm);
   border: none;
}

.btn-primary {
   background: var(--color-primary);
   color: white;
}

.btn-primary:hover {
   background: var(--color-primary-light);
   transform: translateY(-1px);
   box-shadow: var(--shadow-md);
}

.btn-secondary {
   background: var(--color-bg);
   color: var(--color-text);
   border: 1px solid var(--color-border);
}

.btn-secondary:hover {
   background: var(--color-surface);
   transform: translateY(-1px);
   box-shadow: var(--shadow-sm);
}
</style>
