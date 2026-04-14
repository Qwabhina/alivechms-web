<script setup lang="ts">
/**
 * LoginView — Authentication entry point.
 * Built entirely on design system components:
 *   ChCard, ChFormField, ChInput, ChButton, ChAlert
 * 
 * Re-designed to be heavily inspired by the V1 aesthetic and structure.
 */
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { usePublicSettingsStore } from '@/stores/publicSettings.store'
import { LogIn, Eye, EyeOff, Church, User, Lock, ShieldCheck } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const publicSettings = usePublicSettingsStore()

/* ── Load public settings on mount ───────────────────────────────────── */
onMounted(() => {
  publicSettings.loadSettings()
})

/* ── Form state ──────────────────────────────────────────────────────── */
const userid = ref('')
const passkey = ref('')
const remember = ref(false)
const showPassword = ref(false)
const isSubmitting = ref(false)
const loginError = ref<string | null>(null)
const isShaking = ref(false)

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
    // V1 Shake animation logic on failure
    isShaking.value = true
    setTimeout(() => { isShaking.value = false }, 500)

    loginError.value = auth.error ?? 'Login failed. Please check your credentials and try again.'
    passkey.value = '' // Clear password for security (like V1)
  }

  isSubmitting.value = false
}
</script>

<template>
  <div class="login-page">
    <div class="login-shell">

      <!-- Login card — structure inspired by V1 login -->
      <ChCard shadow="lg" :bordered="false" padding="lg" :class="{ 'ch-animate-shake': isShaking }">

        <!-- Branding header inside the card -->
        <div class="logo-container">
          <div class="logo-icon-wrapper">
            <!-- Show church logo if available, fallback to icon -->
            <img
              v-if="publicSettings.churchLogo"
              :src="publicSettings.churchLogo"
              :alt="publicSettings.churchName || 'Church Logo'"
              class="logo-image"
            />
            <Church v-else :size="32" class="logo-icon" />
          </div>
          <h1 class="brand-title">{{ publicSettings.churchName }}</h1>
          <p class="brand-subtitle">{{ publicSettings.churchMotto }}</p>
        </div>

        <!-- Error alert -->
        <ChAlert v-if="loginError" variant="danger" :dismissible="true" class="login-alert"
          @dismiss="loginError = null">
          {{ loginError }}
        </ChAlert>

        <form class="login-form" @submit.prevent="handleLogin" novalidate>

          <!-- Username field -->
          <ChFormField label="Username" input-id="login-userid" :required="true">
            <ChInput id="login-userid" v-model="userid" type="text" placeholder="Enter your username"
              autocomplete="username" size="lg">
              <template #leading>
                <User :size="18" />
              </template>
            </ChInput>
          </ChFormField>

          <!-- Password field -->
          <ChFormField label="Password" input-id="login-passkey" :required="true">
            <ChInput id="login-passkey" v-model="passkey" :type="showPassword ? 'text' : 'password'"
              placeholder="Enter your password" autocomplete="current-password" size="lg">
              <template #leading>
                <Lock :size="18" />
              </template>
              <template #trailing>
                <button type="button" class="pwd-toggle" :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  title="Toggle password visibility" @click="showPassword = !showPassword">
                  <Eye v-if="!showPassword" :size="16" />
                  <EyeOff v-else :size="16" />
                </button>
              </template>
            </ChInput>
          </ChFormField>

          <!-- Remember me -->
          <label class="remember-label">
            <input v-model="remember" type="checkbox" class="remember-checkbox" id="remember" />
            <span>Remember me</span>
          </label>

          <!-- Submit -->
          <ChButton type="submit" variant="primary" size="lg" :full-width="true" :loading="isSubmitting"
            :disabled="!isValid">
            <template #icon>
              <LogIn :size="18" />
            </template>
            {{ isSubmitting ? 'Logging in...' : 'Login' }}
          </ChButton>
        </form>

        <!-- Footer inside the card -->
        <div class="footer-text">
          <small class="footer-content">
            <ShieldCheck :size="14" class="footer-icon" />
            Secure Login &middot; &copy; {{ new Date().getFullYear() }} {{ publicSettings.churchName }}
          </small>
        </div>
      </ChCard>

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
  background-color: var(--ch-color-primary);
    padding: var(--ch-space-8);
    font-family: var(--ch-font-sans);
}

.login-shell {
  width: 100%;
  max-width: 440px;
}

/* ── Branding ─────────────────────────────────────────────────────────── */
.logo-container {
  text-align: center;
  margin-bottom: var(--ch-space-8);
}

.logo-icon-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 64px;
    height: 64px;
    background-color: var(--ch-color-primary-subtle);
    border-radius: var(--ch-radius-lg);
  color: var(--ch-color-primary);
  margin-bottom: var(--ch-space-3);
  overflow: hidden;
}

.logo-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: var(--ch-space-1);
}

.brand-title {
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
  font-family: var(--ch-font-display);
  margin: 0;
}

.brand-subtitle {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: var(--ch-space-1) 0 0;
}

/* ── Alert ────────────────────────────────────────────────────────────── */
.login-alert {
  margin-bottom: var(--ch-space-4);
}

/* ── Form ─────────────────────────────────────────────────────────────── */
.login-form {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-5);
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
.footer-text {
  text-align: center;
  margin-top: var(--ch-space-6);
    padding-top: var(--ch-space-6);
    border-top: 1px solid var(--ch-color-border-strong);
    color: var(--ch-color-text-muted);
  }
  
  .footer-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--ch-space-1_5);
    font-size: var(--ch-text-xs);
}
</style>
