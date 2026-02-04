<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import * as z from 'zod'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { Alerts } from '@/utils/alerts'
import { Lock, User, Eye, EyeOff, Loader2, ShieldCheck } from 'lucide-vue-next'

import { Button } from '@/components/ui/button'
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'

const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const showPassword = ref(false)
const isShaking = ref(false)

// Update document title when settings load
import { watchEffect } from 'vue'
watchEffect(() => {
  if (settingsStore.settings.church_name) {
    document.title = `${settingsStore.settings.church_name} - Login`
  }
})

const formSchema = toTypedSchema(z.object({
// ... (omitting schema for brevity)
}))

const form = useForm({
// ...
})

const onSubmit = form.handleSubmit(async (values) => {
  Alerts.loading('Logging in...', 'Please wait while we verify your credentials')
  try {
    await authStore.login(values.username, values.password, values.remember)
    Alerts.closeLoading()
    Alerts.success('Login successful! Redirecting...')
    setTimeout(() => {
      router.push({ name: 'home' })
    }, 1000)
  } catch (error: any) {
    Alerts.closeLoading()
    isShaking.value = true
    setTimeout(() => { isShaking.value = false }, 500)
    Alerts.handleApiError(error, 'Login failed. Please check your credentials.')
    form.setFieldValue('password', '')
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#00026d] p-4 font-inter">
    <div 
      class="w-full max-w-[440px] login-container animate-in fade-in duration-500"
      :class="{ 'animate-shake': isShaking }"
    >
      <Card class="border-none shadow-[0_10px_40px_rgba(0,0,0,0.3)] rounded-2xl overflow-hidden">
        <CardHeader class="pt-10 pb-2 flex flex-col items-center text-center px-8">
          <div class="mb-4">
            <img 
               v-if="settingsStore.settings.church_logo"
               :src="settingsStore.churchLogoUrl" 
               :alt="settingsStore.settings.church_name"
               class="max-w-[100px] h-auto"
            />
            <ShieldCheck v-else class="w-16 h-16 text-[#00028a]" />
          </div>
          <CardTitle class="text-2xl font-bold tracking-tight text-[#00028a]">
             {{ settingsStore.settings.church_name }}
          </CardTitle>
          <CardDescription class="text-sm font-medium mt-1">
            {{ settingsStore.settings.church_motto || 'Church Management System' }}
          </CardDescription>
        </CardHeader>
        <CardContent class="px-8 pb-10">
          <form @submit="onSubmit" class="space-y-4">
          <FormField v-slot="{ componentField }" name="username">
            <FormItem>
              <FormLabel>Username</FormLabel>
              <FormControl>
                <div class="relative">
                  <User class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                  <Input 
                    placeholder="Enter your username" 
                    class="pl-10" 
                    v-bind="componentField" 
                    autocomplete="username"
autofocus
                  />
                </div>
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="password">
            <FormItem>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <div class="relative">
                  <Lock class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                  <Input 
                    :type="showPassword ? 'text' : 'password'" 
                    placeholder="Enter your password" 
                    class="pl-10 h-10 pr-10" 
                    v-bind="componentField" 
                    autocomplete="current-password"
                  />
                  <button 
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-3 top-3 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <Eye v-if="!showPassword" class="h-4 w-4" />
                    <EyeOff v-else class="h-4 w-4" />
                  </button>
                </div>
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ value, handleChange }" name="remember">
            <FormItem class="flex flex-row items-start space-x-3 space-y-0 py-2">
              <FormControl>
                <Checkbox
                  :checked="value"
                  @update:checked="handleChange"
                />
              </FormControl>
              <div class="space-y-1 leading-none">
                <FormLabel>Remember me</FormLabel>
              </div>
            </FormItem>
          </FormField>

          <Button type="submit" class="w-full bg-[#00028a] hover:bg-[#00026d] text-white py-6" :disabled="authStore.loading">
            <Loader2 v-if="authStore.loading" class="mr-2 h-4 w-4 animate-spin" />
            <span v-else class="text-base font-semibold">Login to Dashboard</span>
          </Button>
        </form>
      </CardContent>
      <CardFooter class="flex flex-col items-center gap-2 text-sm text-gray-500 bg-gray-50 border-t px-8 py-6">
        <div class="flex items-center gap-1 justify-center">
          <ShieldCheck class="w-4 h-4" />
          <span>Secure Login</span>
          <span class="mx-1">&middot;</span>
          <span>&copy; {{ new Date().getFullYear() }} AliveCHMS</span>
        </div>
      </CardFooter>
    </Card>
    </div>
  </div>
</template>
