<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSettingsStore } from '@/stores/settings'
import { useToast } from '@/components/ui/toast/use-toast'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardFooter
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger
} from '@/components/ui/tabs'
import { Switch } from '@/components/ui/switch'
import { Label } from '@/components/ui/label'
import { Loader2, Save, Upload, RefreshCw } from 'lucide-vue-next'

// Basic Setting Interface
interface SettingItem {
  id: number
  key: string
  value: any
  type: string
  category: string
  description: string
  is_editable?: boolean
  updated_at?: string
}

const store = useSettingsStore()
const { toast } = useToast()

const loading = ref(false)
const saving = ref(false)
const rawSettings = ref<SettingItem[]>([])

// Legacy Order
const CATEGORY_ORDER = [
  'General',
  'Regional',
  'Email',
  'SMS',
  'System',
  'Backup',
  'Notifications',
  'Financial'
]

// Group settings by category
const categories = computed(() => {
  const groups: Record<string, SettingItem[]> = {}

  // Defensive check
  if (!Array.isArray(rawSettings.value)) {
    return []
  }

  rawSettings.value.forEach(s => {
    const cat = s.category || 'General'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(s)
  })

  // Sort by defined order, putting unknown categories at the end
  return Object.keys(groups).sort((a, b) => {
    const indexA = CATEGORY_ORDER.indexOf(a)
    const indexB = CATEGORY_ORDER.indexOf(b)

    if (indexA === -1 && indexB === -1) return a.localeCompare(b)
    if (indexA === -1) return 1
    if (indexB === -1) return -1
    return indexA - indexB
  })
})

const route = useRoute()
const router = useRouter()

const currentTab = ref(route.params.category as string || 'General')

// Settings grouped by active category
const activeSettings = computed(() => {
  // Normalize tab name to match category
  const active = currentTab.value.charAt(0).toUpperCase() + currentTab.value.slice(1)
  return rawSettings.value.filter(s => (s.category || 'General') === active)
})

watch(() => route.params.category, (newCategory) => {
  if (newCategory) {
    currentTab.value = (newCategory as string).charAt(0).toUpperCase() + (newCategory as string).slice(1)
  }
})

watch(currentTab, (newTab) => {
  router.replace({ name: 'settings', params: { category: newTab.toLowerCase() } })
})

onMounted(async () => {
  await loadSettings()
  // Set initial tab if categories exist and current is invalid
  // If route param was empty/default, logic above sets 'General'
})

async function loadSettings() {
  loading.value = true
  try {
    rawSettings.value = await store.fetchAllSettings()

    // If no settings functionality, initialize defaults
    if (rawSettings.value.length === 0) {
      try {
        await store.initializeDefaults()
        rawSettings.value = await store.fetchAllSettings()
        toast({ title: 'Initialized', description: 'Default system settings have been generated.' })
      } catch (e) {
        console.error('Auto-init failed', e)
      }
    }
  } catch (error) {
    toast({ title: 'Error', description: 'Failed to load settings', variant: 'destructive' })
  } finally {
    loading.value = false
  }
}

async function saveAll() {
  saving.value = true
  try {
    // Only save editable settings
    const payload = rawSettings.value
      .filter(s => s.is_editable !== false)
      .map(s => ({
        key: s.key,
        value: s.value,
        type: s.type,
        category: s.category,
        description: s.description
      }))

    await store.updateSettings(payload)
    toast({ title: 'Saved', description: 'System settings updated successfully' })
  } catch (error) {
    toast({ title: 'Error', description: 'Failed to save settings', variant: 'destructive' })
  } finally {
    saving.value = false
  }
}

// Logo Upload
const logoInput = ref<HTMLInputElement | null>(null)
const uploadingLogo = ref(false)

async function handleLogoUpload(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  uploadingLogo.value = true
  try {
    await store.uploadLogo(file)
    toast({ title: 'Success', description: 'Logo uploaded successfully' })
    // Refresh to get new path
    await loadSettings()
  } catch (error) {
    toast({ title: 'Error', description: 'Logo upload failed', variant: 'destructive' })
  } finally {
    uploadingLogo.value = false
    if (logoInput.value) logoInput.value.value = ''
  }
}

function triggerLogoUpload() {
  logoInput.value?.click()
}
</script>

<template>
 <div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">System Settings</h1>
        <p class="text-muted-foreground">Manage application configuration and preferences.</p>
      </div>

      <div class="flex items-center gap-2">
        <Button variant="outline" @click="loadSettings" :disabled="loading || saving">
          <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
          Refresh
        </Button>
        <Button @click="saveAll" :disabled="loading || saving">
          <Save class="w-4 h-4 mr-2" />
          {{ saving ? 'Saving...' : 'Save Changes' }}
        </Button>
      </div>
    </div>

    <div v-if="loading && rawSettings.length === 0" class="flex justify-center py-12">
      <Loader2 class="w-8 h-8 animate-spin text-muted-foreground" />
    </div>

    <Tabs v-else v-model="currentTab" class="w-full">
      <TabsList class="flex w-full overflow-x-auto justify-start mb-4 h-auto p-1 bg-muted/50 rounded-lg">
        <TabsTrigger v-for="cat in categories" :key="cat" :value="cat" class="px-4 py-2 min-w-[100px]">
          {{ cat }}
        </TabsTrigger>
      </TabsList>

      <TabsContent v-for="cat in categories" :key="cat" :value="cat">
        <Card>
          <CardHeader>
            <CardTitle>{{ cat }} Settings</CardTitle>
            <CardDescription>Configure {{ cat.toLowerCase() }} settings for the application.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">

            <!-- Special Handling: Church Logo in General Tab -->
            <div v-if="cat === 'General'" class="flex items-center gap-6 border-b pb-6">
              <div class="h-24 w-24 rounded-lg border bg-muted flex items-center justify-center overflow-hidden">
                <img v-if="store.churchLogoUrl" :src="store.churchLogoUrl" alt="Church Logo"
                  class="w-full h-full object-contain" />
                <span v-else class="text-xs text-muted-foreground">No Logo</span>
              </div>
              <div>
                <h3 class="text-sm font-medium mb-1">Church Logo</h3>
                <p class="text-xs text-muted-foreground mb-3">Upload a square image (PNG, JPG) for reports and header.
                </p>
                <input type="file" ref="logoInput" class="hidden" accept="image/*" @change="handleLogoUpload" />
                <Button variant="outline" size="sm" @click="triggerLogoUpload" :disabled="uploadingLogo">
                  <Upload class="w-3 h-3 mr-2" />
                  {{ uploadingLogo ? 'Uploading...' : 'Upload New Logo' }}
                </Button>
              </div>
            </div>

            <!-- Dynamic Form Fields -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <div v-for="setting in activeSettings" :key="setting.id" class="space-y-2">
                <div class="flex justify-between items-center">
                  <Label :for="setting.key" :class="{ 'opacity-70': setting.is_editable === false }">
                    {{ setting.description || setting.key }}
                  </Label>
                  <span v-if="setting.is_editable === false"
                    class="text-[10px] text-muted-foreground uppercase tracking-wider border px-1 rounded">Locked</span>
                </div>

                <!-- Boolean Toggle -->
                <div v-if="setting.type === 'boolean'" class="flex items-center space-x-2">
                  <Switch :id="setting.key" :checked="!!setting.value"
                    @update:checked="(val: boolean) => setting.value = val" :disabled="setting.is_editable === false" />
                  <span class="text-sm text-muted-foreground">{{ setting.value ? 'Enabled' : 'Disabled' }}</span>
                </div>

                <!-- Number Input -->
                <Input v-else-if="setting.type === 'number'" :id="setting.key" type="number"
                  v-model.number="setting.value" :disabled="setting.is_editable === false" />

                <!-- String Input -->
                <Input v-else :id="setting.key" type="text" v-model="setting.value"
                  :disabled="setting.is_editable === false" />
              </div>
            </div>
          </CardContent>
          <CardFooter class="border-t px-6 py-4 flex justify-end">
            <Button @click="saveAll" :disabled="saving">
              <Save class="w-4 h-4 mr-2" />
              Save {{ cat }} Settings
            </Button>
          </CardFooter>
        </Card>
      </TabsContent>
    </Tabs>
  </div>
</template>
