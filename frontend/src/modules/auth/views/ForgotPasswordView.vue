<template>
   <div class="forgot-password-view">
      <div class="auth-card">
         <div class="header">
            <i class="pi pi-key icon"></i>
            <h2>Forgot Password?</h2>
            <p class="subtitle">Enter your email address and we'll send you a link to reset your password.</p>
         </div>

         <form v-if="!emailSent" @submit.prevent="handleSubmit" class="form">
            <div class="form-group">
               <label for="email" class="form-label">Email Address</label>
               <input id="email" v-model="email" type="email" class="form-input" placeholder="your.email@example.com"
                  required autocomplete="email" :disabled="isLoading" />
            </div>

            <button type="submit" class="btn-primary" :disabled="isLoading">
               <span v-if="!isLoading">Send Reset Link</span>
               <span v-else class="loading-text">
                  <i class="pi pi-spinner pi-spin"></i> Sending...
               </span>
            </button>

            <div v-if="errorMessage" class="error-message">
               {{ errorMessage }}
            </div>
         </form>

         <div v-else class="success-message">
            <i class="pi pi-check-circle success-icon"></i>
            <h3>Email Sent!</h3>
            <p>If an account exists with {{ email }}, you will receive a password reset link shortly.</p>
         </div>

         <div class="footer">
            <router-link to="/login" class="link">
               <i class="pi pi-arrow-left"></i> Back to Login
            </router-link>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import apiClient from '@/api/apiClient';

const email = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const emailSent = ref(false);

async function handleSubmit() {
   errorMessage.value = '';
   isLoading.value = true;

   try {
      await apiClient.post('/auth/forgot-password', { email: email.value });
      emailSent.value = true;
   } catch (error: any) {
      errorMessage.value = error.response?.data?.message || 'Failed to send reset link. Please try again.';
   } finally {
      isLoading.value = false;
   }
}
</script>

<style scoped>
.forgot-password-view {
   min-height: 100vh;
   display: flex;
   align-items: center;
   justify-content: center;
   background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
   padding: var(--space-lg);
}

.auth-card {
   background: var(--color-surface);
   border-radius: var(--radius-lg);
   box-shadow: var(--shadow-xl);
   padding: var(--space-2xl);
   width: 100%;
   max-width: 420px;
   animation: slideInUp var(--transition-normal);
}

.header {
   text-align: center;
   margin-bottom: var(--space-xl);
}

.icon {
   font-size: 3rem;
   color: var(--color-primary);
   margin-bottom: var(--space-md);
}

.header h2 {
   color: var(--color-text);
   margin-bottom: var(--space-sm);
}

.subtitle {
   color: var(--color-text-muted);
   font-size: var(--font-size-sm);
}

.form {
   display: flex;
   flex-direction: column;
   gap: var(--space-lg);
}

.form-group {
   display: flex;
   flex-direction: column;
   gap: var(--space-sm);
}

.form-label {
   font-weight: 500;
   color: var(--color-text);
   font-size: var(--font-size-sm);
}

.form-input {
   padding: var(--space-sm) var(--space-md);
   border: 1px solid var(--color-border);
   border-radius: var(--radius-md);
   font-size: var(--font-size-base);
   transition: all var(--transition-fast);
}

.form-input:focus {
   outline: none;
   border-color: var(--color-primary);
   box-shadow: 0 0 0 3px rgba(0, 2, 138, 0.1);
}

.form-input:disabled {
   opacity: 0.6;
   cursor: not-allowed;
}

.btn-primary {
   padding: var(--space-md) var(--space-lg);
   background: var(--color-primary);
   color: white;
   border: none;
   border-radius: var(--radius-md);
   font-size: var(--font-size-base);
   font-weight: 600;
   cursor: pointer;
   transition: all var(--transition-fast);
}

.btn-primary:hover:not(:disabled) {
   background: var(--color-primary-light);
   transform: translateY(-1px);
   box-shadow: var(--shadow-md);
}

.btn-primary:disabled {
   opacity: 0.6;
   cursor: not-allowed;
}

.loading-text {
   display: flex;
   align-items: center;
   justify-content: center;
   gap: var(--space-sm);
}

.error-message {
   padding: var(--space-sm) var(--space-md);
   background: rgba(239, 68, 68, 0.1);
   border: 1px solid var(--color-error);
   border-radius: var(--radius-md);
   color: var(--color-error);
   font-size: var(--font-size-sm);
   text-align: center;
}

.success-message {
   text-align: center;
   padding: var(--space-xl) 0;
}

.success-icon {
   font-size: 4rem;
   color: var(--color-success);
   margin-bottom: var(--space-lg);
}

.success-message h3 {
   color: var(--color-text);
   margin-bottom: var(--space-md);
}

.success-message p {
   color: var(--color-text-muted);
   font-size: var(--font-size-sm);
}

.footer {
   margin-top: var(--space-xl);
   text-align: center;
}

.link {
   color: var(--color-primary);
   font-size: var(--font-size-sm);
   text-decoration: none;
   transition: color var(--transition-fast);
   display: inline-flex;
   align-items: center;
   gap: var(--space-xs);
}

.link:hover {
   color: var(--color-primary-light);
   text-decoration: underline;
}
</style>
