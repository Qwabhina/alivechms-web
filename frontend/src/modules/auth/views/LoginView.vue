<template>
   <div class="login-view">
      <div class="login-card">
         <div class="logo-container">
            <img v-if="systemLogo" :src="systemLogo" alt="Logo" class="logo" />
            <h1 v-else class="app-name">AliveChMS</h1>
         </div>

         <h2 class="login-title">Sign In</h2>

         <form @submit.prevent="handleLogin" class="login-form">
            <div class="form-group">
               <label for="username" class="form-label">Username</label>
               <input id="username" v-model="credentials.username" type="text" class="form-input"
                  placeholder="Enter your username" required autocomplete="username" :disabled="isLoading" />
            </div>

            <div class="form-group">
               <label for="password" class="form-label">Password</label>
               <input id="password" v-model="credentials.password" type="password" class="form-input"
                  placeholder="Enter your password" required autocomplete="current-password" :disabled="isLoading" />
            </div>

            <div class="form-group checkbox-group">
               <label class="checkbox-label">
                  <input v-model="credentials.rememberMe" type="checkbox" class="checkbox-input"
                     :disabled="isLoading" />
                  <span>Remember Me</span>
               </label>
            </div>

            <button type="submit" class="btn-primary btn-login" :disabled="isLoading">
               <span v-if="!isLoading">Sign In</span>
               <span v-else class="loading-text">
                  <i class="pi pi-spinner pi-spin"></i> Signing in...
               </span>
            </button>

            <div v-if="errorMessage" class="error-message">
               {{ errorMessage }}
            </div>
         </form>

         <div class="login-footer">
            <router-link to="/forgot-password" class="link">
               Forgot Password?
            </router-link>
         </div>
      </div>
   </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const credentials = ref({
   username: '',
   password: '',
   rememberMe: false,
});

const isLoading = ref(false);
const errorMessage = ref('');

// TODO: Fetch logo from system settings
const systemLogo = computed(() => '');

async function handleLogin() {
   errorMessage.value = '';
   isLoading.value = true;

   try {
      await authStore.login(credentials.value);

      // Redirect to intended page or dashboard
      const redirectPath = (route.query.redirect as string) || '/';
      router.push(redirectPath);
   } catch (error: any) {
      errorMessage.value = error.response?.data?.message || 'Login failed. Please check your credentials.';
   } finally {
      isLoading.value = false;
   }
}
</script>

<style scoped>
.login-view {
   min-height: 100vh;
   display: flex;
   align-items: center;
   justify-content: center;
   background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
   padding: var(--space-lg);
}

.login-card {
   background: var(--color-surface);
   border-radius: var(--radius-lg);
   box-shadow: var(--shadow-xl);
   padding: var(--space-2xl);
   width: 100%;
   max-width: 420px;
   animation: slideInUp var(--transition-normal);
}

.logo-container {
   text-align: center;
   margin-bottom: var(--space-xl);
}

.logo {
   max-width: 120px;
   height: auto;
}

.app-name {
   color: var(--color-primary);
   font-size: var(--font-size-3xl);
   font-weight: 700;
   margin: 0;
}

.login-title {
   text-align: center;
   color: var(--color-text);
   font-size: var(--font-size-2xl);
   font-weight: 600;
   margin-bottom: var(--space-xl);
}

.login-form {
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
   background: var(--color-surface);
   color: var(--color-text);
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

.checkbox-group {
   flex-direction: row;
   align-items: center;
}

.checkbox-label {
   display: flex;
   align-items: center;
   gap: var(--space-sm);
   cursor: pointer;
   font-size: var(--font-size-sm);
   color: var(--color-text);
}

.checkbox-input {
   width: 18px;
   height: 18px;
   cursor: pointer;
   accent-color: var(--color-primary);
}

.checkbox-input:disabled {
   cursor: not-allowed;
}

.btn-primary {
   padding: var(--space-sm) var(--space-lg);
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

.btn-primary:active:not(:disabled) {
   transform: translateY(0);
}

.btn-primary:disabled {
   opacity: 0.6;
   cursor: not-allowed;
}

.btn-login {
   margin-top: var(--space-md);
   padding: var(--space-md) var(--space-lg);
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

.login-footer {
   margin-top: var(--space-lg);
   text-align: center;
}

.link {
   color: var(--color-primary);
   font-size: var(--font-size-sm);
   text-decoration: none;
   transition: color var(--transition-fast);
}

.link:hover {
   color: var(--color-primary-light);
   text-decoration: underline;
}

@media (max-width: 480px) {
   .login-card {
      padding: var(--space-xl);
   }

   .login-title {
      font-size: var(--font-size-xl);
   }
}
</style>
