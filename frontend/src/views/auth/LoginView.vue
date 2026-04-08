<script setup lang="ts">
/**
 * LoginView — Authentication entry point.
 * Built entirely on design system components:
 *   ChCard, ChFormField, ChInput, ChButton, ChAlert
 */
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { LogIn, Eye, EyeOff, Church } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

/* ── Form state ──────────────────────────────────────────────────────── */
const userid = ref('')
const passkey = ref('')
const remember = ref(false)
const showPassword = ref(false)
const isSubmitting = ref(false)
const loginError = ref<string | null>(null)

/* ── Validation ──────────────────────────────────────────────────────── */
const isValid = computed(() => userid.value.trim().length > 0 && passkey.value.length >= 1)

/* ── Submit ──────────────────────────────────────────────────────────── */
async function handleLogin() {
  if (!isValid.value || isSubmitting.value) return
  isSubmitting.value = true
  loginError.value = null

  const success = await auth.login(userid.value.trim(), passkey.value, remember.value)

  if (success) {
    const redirect = (route.query.redirect as string) || '/dashboard'
    router.push(redirect)
  } else {
    loginError.value = auth.error ?? 'Invalid credentials. Please try again.'
  }

  isSubmitting.value = false
}
</script>

<template>
  <div class="login-page">
    <div class="login-shell">

      <!-- Branding header -->
      <div class="login-brand">
        <div class="login-brand__icon">
          <Church :size="28" />
        </div>
        <h1 class="login-brand__name">AliveChMS</h1>
        <p class="login-brand__tagline">Church Management System</p>
      </div>

      <!-- Login card — uses ChCard from the design system -->
      <ChCard shadow="lg" :bordered="true" padding="lg">
        <template #header>
          <span class="login-card__title">Sign in to your account</span>
        </template>

        <!-- Error alert — uses ChAlert from the design system -->
        <ChAlert v-if="loginError" variant="danger" :dismissible="true" class="login-alert"
          @dismiss="loginError = null">
          {{ loginError }}
        </ChAlert>

        <form class="login-form" @submit.prevent="handleLogin" novalidate>

          <!-- Username field — uses ChFormField + ChInput -->
          <ChFormField label="Username or Email" input-id="login-userid" :required="true">
            <ChInput id="login-userid" v-model="userid" type="text" placeholder="Enter your username or email"
              autocomplete="username" size="lg" />
          </ChFormField>

          <!-- Password field — uses ChFormField + ChInput with trailing toggle -->
          <ChFormField label="Password" input-id="login-passkey" :required="true">
            <ChInput id="login-passkey" v-model="passkey" :type="showPassword ? 'text' : 'password'"
              placeholder="Enter your password" autocomplete="current-password" size="lg">
              <template #trailing>
                <button type="button" class="pwd-toggle" :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  @click="showPassword = !showPassword">
                  <Eye v-if="!showPassword" :size="16" />
                  <EyeOff v-else :size="16" />
                </button>
              </template>
            </ChInput>
          </ChFormField>

          <!-- Remember me -->
          <label class="remember-label">
            <input v-model="remember" type="checkbox" class="remember-checkbox" />
            <span>Remember me for 30 days</span>
          </label>

          <!-- Submit — uses ChButton -->
          <ChButton type="submit" variant="primary" size="lg" :full-width="true" :loading="isSubmitting"
            :disabled="!isValid">
            <template #icon>
              <LogIn :size="18" />
            </template>
            {{ isSubmitting ? 'Signing in…' : 'Sign in' }}
          </ChButton>
        </form>
      </ChCard>

      <p class="login-footer">
        &copy; {{ new Date().getFullYear() }} AliveChMS &middot; All rights reserved
      </p>
    </div>
  </div>
</template>

<style scoped>
/* ── Page shell ──────────────────────────────────────────────────────── */
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ch-color-surface);
  padding: var(--ch-space-6);
}

.login-shell {
  width: 100%;
  max-width: 440px;
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
}

/* ── Branding ─────────────────────────────────────────────────────────── */
.login-brand {
  text-align: center;
}

.login-brand__icon {
  width: 60px;
  height: 60px;
  margin: 0 auto var(--ch-space-4);
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: var(--ch-radius-xl, 16px);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ch-color-primary);
}

.login-brand__name {
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-primary);
  font-family: var(--ch-font-display);
  margin: 0;
}

.login-brand__tagline {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-primary);
  margin: var(--ch-space-1) 0 0;
}

/* ── Card header title ────────────────────────────────────────────────── */
.login-card__title {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

/* ── Alert ────────────────────────────────────────────────────────────── */
.login-alert {
  margin-bottom: var(--ch-space-4);
}

/* ── Form ─────────────────────────────────────────────────────────────── */
.login-form {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

/* ── Password toggle ─────────────────────────────────────────────────── */
.pwd-toggle {
  display: flex;
  align-items: center;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--ch-color-text-muted);
  padding: 0;
  transition: color var(--ch-duration-fast) var(--ch-ease-out);
}

.pwd-toggle:hover {
  color: var(--ch-color-text);
}

/* ── Remember label ──────────────────────────────────────────────────── */
.remember-label {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  cursor: pointer;
  user-select: none;
}

.remember-checkbox {
  accent-color: var(--ch-color-primary);
  width: 16px;
  height: 16px;
  cursor: pointer;
}

/* ── Footer ──────────────────────────────────────────────────────────── */
.login-footer {
  text-align: center;
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  margin: 0;
}
</style>
