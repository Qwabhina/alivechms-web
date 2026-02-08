<template>
   <div class="reset-password-view">
      <div class="auth-card">
         <div class="header">
            <i class="pi pi-lock icon"></i>
            <h2>Reset Password</h2>
            <p class="subtitle">Enter your new password below.</p>
         </div>

         <form v-if="!resetSuccess" @submit.prevent="handleSubmit" class="form">
            <div class="form-group">
               <label for="password" class="form-label">New Password</label>
               <input id="password" v-model="formData.password" type="password" class="form-input"
                  placeholder="Enter new password" required autocomplete="new-password" :disabled="isLoading" />
               <small v-if="formData.password" class="password-strength" :class="passwordStrengthClass">
                  {{ passwordStrengthText }}
               </small>
            </div>

            <div class="form-group">
               <label for="confirmPassword" class="form-label">Confirm Password</label>
               <input id="confirmPassword" v-model="formData.confirmPassword" type="password" class="form-input"
                  placeholder="Confirm new password" required autocomplete="new-password" :disabled="isLoading" />
            </div>

            <button type="submit" class="btn-primary" :disabled="isLoading || !isFormValid">
               <span v-if="!isLoading">Reset Password</span>
               <span v-else class="loading-text">
                  <i class="pi pi-spinner pi-spin"></i> Resetting...
               </span>
            </button>

            <div v-if="errorMessage" class="error-message">
               {{ errorMessage }}
            </div>
         </form>

         <div v-else class="success-message">
            <i class="pi pi-check-circle success-icon"></i>
            <h3>Password Reset Successful!</h3>
            <p>Your password has been reset. You can now log in with your new password.</p>
            <router-link to="/login" class="btn-primary">
               Go to Login
            </router-link>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/apiClient';

const route = useRoute();
const token = route.params.token as string;

const formData = ref({
   password: '',
   confirmPassword: '',
});

const isLoading = ref(false);
const errorMessage = ref('');
const resetSuccess = ref(false);

const passwordStrength = computed(() => {
   const pwd = formData.value.password;
   if (pwd.length < 6) return 0;
   if (pwd.length < 8) return 1;
   if (pwd.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/) && pwd.length >= 8) return 3;
   if (pwd.match(/^(?=.*[a-z])(?=.*[A-Z])/) || pwd.match(/^(?=.*\d)/)) return 2;
   return 1;
});

const passwordStrengthClass = computed(() => {
   const strength = passwordStrength.value;
   if (strength === 0) return 'weak';
   if (strength === 1) return 'weak';
   if (strength === 2) return 'medium';
   return 'strong';
});

const passwordStrengthText = computed(() => {
   const strength = passwordStrength.value;
   if (strength === 0) return 'Too short';
   if (strength === 1) return 'Weak';
   if (strength === 2) return 'Medium';
   return 'Strong';
});

const isFormValid = computed(() => {
   return (
      formData.value.password.length >= 6 &&
      formData.value.password === formData.value.confirmPassword
   );
});

async function handleSubmit() {
   if (!isFormValid.value) {
      errorMessage.value = 'Passwords must match and be at least 6 characters.';
      return;
   }

   errorMessage.value = '';
   isLoading.value = true;

   try {
      await apiClient.post('/auth/reset-password', {
         token,
         password: formData.value.password,
      });
      resetSuccess.value = true;
   } catch (error: any) {
      errorMessage.value = error.response?.data?.message || 'Failed to reset password. Please try again or request a new reset link.';
   } finally {
      isLoading.value = false;
   }
}
</script>

<style scoped>
.reset-password-view {
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

.password-strength {
   font-size: var(--font-size-xs);
   font-weight: 600;
   margin-top: var(--space-xs);
}

.password-strength.weak {
   color: var(--color-error);
}

.password-strength.medium {
   color: var(--color-warning);
}

.password-strength.strong {
   color: var(--color-success);
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
   text-decoration: none;
   display: inline-block;
   text-align: center;
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
   margin-bottom: var(--space-xl);
}
</style>
