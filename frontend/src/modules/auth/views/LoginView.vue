<template>
   <div class="login-view">
      <div class="login-card">
         <div class="logo-container">
            <img v-if="systemLogo" :src="systemLogo" alt="Logo" class="logo" />
            <h1 v-else class="app-name">AliveChMS</h1>
           <p class="subtitle">Church Management System</p>
         </div>

         <form @submit.prevent="handleLogin" class="login-form">
            <div class="form-group">
              <label for="userid" class="form-label">Username</label>
               <div class="input-wrapper">
                  <i class="pi pi-user input-icon-left"></i>
                  <input id="userid" v-model="credentials.userid" type="text" autofocus class="form-input with-icon"
                     placeholder="Enter your username" required autocomplete="username" :disabled="isLoading" />
               </div>
           </div>

            <div class="form-group">
              <label for="passkey" class="form-label">Password</label>
               <div class="input-wrapper">
                  <i class="pi pi-lock input-icon-left"></i>
                  <input id="passkey" v-model="credentials.passkey" :type="showPassword ? 'text' : 'password'"
                     class="form-input with-icon with-action" placeholder="Enter your password" required
                     autocomplete="current-password" :disabled="isLoading" />
                  <button type="button" class="action-icon" @click="showPassword = !showPassword" tabindex="-1">
                     <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                  </button>
               </div>
            </div>

            <div class="form-group checkbox-group">
               <label class="checkbox-label">
                 <input v-model="credentials.remember" type="checkbox" class="checkbox-input" :disabled="isLoading" />
                  <span>Remember me</span>
               </label>
            </div>

          <div v-if="errorMessage" class="error-message">
               <i class="pi pi-exclamation-circle"></i>
               <span>{{ errorMessage }}</span>
            </div>
            <button type="submit" class="btn-primary btn-login" :disabled="isLoading">
              <span v-if="!isLoading">
                  <i class="pi pi-sign-in mr-2"></i> Login
               </span>
               <span v-else class="loading-text">
                  <i class="pi pi-spinner pi-spin"></i> Signing in...
               </span>
           </button>
         </form>

       <div class="login-links">
            <router-link to="/forgot-password" class="link">
               Forgot Password?
            </router-link>
         </div>
        <div class="login-footer">
            <small>
               <i class="pi pi-shield text-primary"></i>
               Secure Login &middot; &copy; {{ new Date().getFullYear() }} AliveChMS
            </small>
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
   userid: '',
   passkey: '',
   remember: false,
});

const showPassword = ref(false);
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
      if (error.response?.data?.message) {
         errorMessage.value = error.response.data.message;
      } else if (error.response?.status === 401) {
         errorMessage.value = 'Invalid username or password.';
      } else {
         errorMessage.value = 'Login failed. Please check your network.';
      }
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
   background: var(--color-primary);
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
   margin-bottom: var(--space-md);
}

.app-name {
   color: var(--color-primary);
   font-size: var(--font-size-3xl);
   font-weight: 700;
   margin: 0;
   line-height: 1.2;
}

.subtitle {
   color: var(--color-text-muted);
   font-size: var(--font-size-sm);
   margin: var(--space-xs) 0 0;
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
   font-weight: 600;
   color: var(--color-text);
   font-size: var(--font-size-sm);
}

.input-wrapper {
   position: relative;
   display: flex;
   align-items: center;
}

.input-icon-left {
   position: absolute;
   left: 12px;
   color: var(--color-text-muted);
   pointer-events: none;
}
.form-input {
   width: 100%;
      padding: 10px var(--space-md);
   border: 1px solid var(--color-border);
   border-radius: var(--radius-md);
   font-size: var(--font-size-base);
   transition: all var(--transition-fast);
   background: var(--color-surface);
   color: var(--color-text);
}

.form-input.with-icon {
   padding-left: 36px;
}

.form-input.with-action {
   padding-right: 40px;
}
.form-input:focus {
   outline: none;
   border-color: var(--color-primary);
   box-shadow: 0 0 0 3px rgba(0, 2, 138, 0.1);
}

.form-input:disabled {
   background: #f1f5f9;
   cursor: not-allowed;
}

.action-icon {
   position: absolute;
   right: 0;
   top: 0;
   bottom: 0;
   width: 40px;
   display: flex;
   align-items: center;
   justify-content: center;
   background: transparent;
   border: none;
   color: var(--color-text-muted);
   cursor: pointer;
   transition: color var(--transition-fast);
}

.action-icon:hover {
   color: var(--color-text);
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
   user-select: none;
}

.checkbox-input {
   width: 16px;
      height: 16px;
   cursor: pointer;
   accent-color: var(--color-primary);
}

.btn-primary {
   padding: 12px;
   background: var(--color-primary);
   color: white;
   border: none;
   border-radius: var(--radius-md);
   font-size: var(--font-size-base);
   font-weight: 600;
   cursor: pointer;
   transition: all var(--transition-fast);
   display: flex;
      align-items: center;
      justify-content: center;
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
   opacity: 0.7;
   cursor: not-allowed;
}

.loading-text {
   display: flex;
   align-items: center;
   gap: var(--space-sm);
}

.error-message {
   padding: var(--space-sm) var(--space-md);
   background: #fef2f2;
      border: 1px solid #fee2e2;
   border-radius: var(--radius-md);
   color: var(--color-error);
   font-size: var(--font-size-sm);
   display: flex;
      align-items: center;
      gap: var(--space-sm);
      animation: fadeIn var(--transition-fast);
   }
   
   .login-links {
      margin-top: var(--space-lg);
   text-align: center;
}

.login-footer {
   margin-top: var(--space-xl);
   text-align: center;
   padding-top: var(--space-md);
      border-top: 1px solid var(--color-border);
      color: var(--color-text-muted);
      font-size: var(--font-size-xs);
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

.mr-2 {
   margin-right: 0.5rem;
}

.text-primary {
   color: var(--color-primary);
}
</style>
