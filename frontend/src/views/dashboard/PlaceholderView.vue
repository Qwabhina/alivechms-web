<script setup lang="ts">
import { useRoute } from 'vue-router'
import { Hammer, Construction, ArrowLeft } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const pageTitle = (route.name as string)?.replace(/-/g, ' ').toUpperCase() || 'Page Under Construction'
</script>

<template>
 <div
    class="h-[calc(100vh-160px)] flex flex-col items-center justify-center text-center p-6 bg-white rounded-2xl shadow-sm border border-slate-100">
    <div class="h-20 w-20 bg-amber-50 rounded-full flex items-center justify-center mb-6 animate-pulse">
      <Construction class="h-10 w-10 text-amber-500" />
    </div>

    <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ pageTitle }}</h1>
    <p class="text-slate-500 max-w-md mb-8">
      We're meticulously migrating the legacy features to this new interface.
      The <strong>{{ pageTitle.toLowerCase() }}</strong> module is currently being built for parity.
    </p>

    <div class="flex gap-4">
      <Button variant="outline" @click="router.back()">
        <ArrowLeft class="w-4 h-4 mr-2" />
        Go Back
      </Button>
      <Button class="bg-[#00028a] hover:bg-[#00026d]" @click="router.push({ name: 'home' })">
        Return Home
      </Button>
    </div>

    <!-- Meticulous Checkmarks -->
    <div class="mt-12 grid grid-cols-2 gap-x-8 gap-y-2 text-left">
      <div v-for="item in ['UI Parity', 'Logic Migration', 'Data Integrity', 'Speed Optimization']" :key="item"
        class="flex items-center gap-2 text-xs font-semibold text-slate-400">
        <div class="h-4 w-4 rounded-full border border-slate-200 flex items-center justify-center">
          <div class="h-2 w-2 rounded-full bg-slate-200"></div>
        </div>
        {{ item }}
      </div>
    </div>
  </div>
</template>
