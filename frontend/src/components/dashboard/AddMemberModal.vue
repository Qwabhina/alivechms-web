<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { toTypedSchema } from '@vee-validate/zod'
import { 
  Dialog, 
  DialogContent, 
  DialogDescription, 
  DialogFooter, 
  DialogHeader, 
  DialogTitle 
} from '@/components/ui/dialog'
import { Card, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Switch } from '@/components/ui/switch'
import { useMembersStore } from '@/stores/members'
import { Alerts } from '@/utils/alerts'
import api from '@/services/api'
import { UserPlus, Camera, Trash2, ArrowRight, ArrowLeft, CheckCircle2, RefreshCw } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits(['close', 'success'])

const membersStore = useMembersStore()
const currentStep = ref(0)
const isSubmitting = ref(false)
const profilePreview = ref<string | null>(null)
const selectedFile = ref<File | null>(null)

const steps = [
  { title: 'Personal', description: 'Basic information' },
  { title: 'Contact', description: 'Address & Family' },
  { title: 'Account', description: 'System Access' }
]

const phoneNumbers = ref([{ number: '', type_id: '1' }])

const addPhone = () => {
  phoneNumbers.value.push({ number: '', type_id: '1' })
}

const removePhone = (index: number) => {
  if (phoneNumbers.value.length > 1) {
    phoneNumbers.value.splice(index, 1)
  }
}

const { handleSubmit, values, setFieldValue, validate } = useForm({
  initialValues: {
    first_name: '',
    family_name: '',
    other_names: '',
    gender: '',
    date_of_birth: '',
    marital_status_id: '',
    occupation: '',
    education_level_id: '',
    email_address: '',
    address: '',
    family_id: '',
    branch_id: '',
    enable_login: false,
    username: '',
    password: '',
    role_id: '',
  }
})

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
    profilePreview.value = URL.createObjectURL(target.files[0])
  }
}

const removePhoto = () => {
  selectedFile.value = null
  profilePreview.value = null
}

const nextStep = async () => {
  const result = await validate()
  if (result.valid) {
    if (currentStep.value < 2) {
      currentStep.value++
    } else {
      submitMember()
    }
  }
}

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const submitMember = handleSubmit(async (formValues) => {
  isSubmitting.value = true
  try {
    const formData = new FormData()
    
    // Append all fields
    Object.entries(formValues).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        formData.append(key, value.toString())
      }
    })

    // Append phone numbers (filter out empty)
    const validPhones = phoneNumbers.value.filter(p => p.number.trim() !== '')
    validPhones.forEach((p, index) => {
      formData.append(`phone_numbers[${index}][number]`, p.number)
      formData.append(`phone_numbers[${index}][type_id]`, p.type_id)
    })

    if (selectedFile.value) {
      formData.append('profile_picture', selectedFile.value)
    }

    await api.post('member/create', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    Alerts.success('Member registration successful!')
    emit('success')
    emit('close')
    membersStore.fetchMembers(1)
    membersStore.fetchStats()
  } catch (error) {
    Alerts.handleApiError(error, 'Failed to register member')
  } finally {
    isSubmitting.value = false
  }
})

const triggerFileUpload = () => {
  (window as any).document.getElementById('profile-upload')?.click()
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => !val && emit('close')">
    <DialogContent class="max-w-2xl p-0 overflow-hidden border-none shadow-2xl">
      <Card class="border-none shadow-none">
        <CardHeader class="bg-[#00028a]/5 border-b">
          <div>
            <DialogTitle class="text-xl text-[#00028a] flex items-center gap-2">
              <UserPlus class="w-5 h-5" />
              Add New Member
            </DialogTitle>
            <DialogDescription class="mt-1">
              Step {{ currentStep + 1 }} of 3: {{ steps[currentStep]?.description }}
            </DialogDescription>
          </div>
          
          <!-- Stepper Indicator -->
          <div class="flex items-center gap-2 mt-6">
            <div v-for="(step, index) in steps" :key="index" class="flex-1 flex items-center gap-2">
              <div 
                class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                :class="index <= currentStep ? 'bg-[#00028a] text-white' : 'bg-gray-200 text-gray-500'"
              >
                {{ index + 1 }}
              </div>
              <div v-if="index < 2" class="flex-1 h-1 bg-gray-200">
                <div 
                  class="h-full bg-[#00028a] transition-all duration-300" 
                  :style="{ width: index < currentStep ? '100%' : '0%' }"
                ></div>
              </div>
            </div>
          </div>
        </CardHeader>

        <div class="p-6 max-h-[60vh] overflow-y-auto">
          <!-- Step 0: Personal -->
          <div v-show="currentStep === 0" class="space-y-6">
            <div class="flex flex-col items-center gap-4 py-4">
              <div class="relative group">
                <div class="h-24 w-24 rounded-full border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                  <img v-if="profilePreview" :src="profilePreview" class="h-full w-full object-cover" />
                  <Camera v-else class="w-8 h-8 text-gray-300" />
                </div>
                <input type="file" @change="handleFileChange" accept="image/*" class="hidden" id="profile-upload" />
                <Button 
                  v-if="profilePreview" 
                  type="button" 
                  variant="destructive" 
                  size="icon" 
                  class="absolute -top-2 -right-2 h-6 w-6 rounded-full"
                  @click="removePhoto"
                >
                  <Trash2 class="w-3 h-3" />
                </Button>
                <Button 
                  v-else
                  type="button"
                  size="sm"
                  variant="outline"
                  class="mt-2"
                  @click="triggerFileUpload"
                >
                  Upload Photo
                </Button>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
               <div class="space-y-2">
                  <Label>First Name <span class="text-red-500">*</span></Label>
                  <Input :model-value="values.first_name" @update:model-value="v => setFieldValue('first_name', v as string)" placeholder="John" />
               </div>
               <div class="space-y-2">
                  <Label>Family Name <span class="text-red-500">*</span></Label>
                  <Input :model-value="values.family_name" @update:model-value="v => setFieldValue('family_name', v as string)" placeholder="Doe" />
               </div>
               <div class="space-y-2">
                  <Label>Other Names</Label>
                  <Input :model-value="values.other_names" @update:model-value="v => setFieldValue('other_names', v as string)" />
               </div>
               <div class="space-y-2">
                  <Label>Gender <span class="text-red-500">*</span></Label>
                  <Select :model-value="values.gender" @update:model-value="v => setFieldValue('gender', v as string)">
                    <SelectTrigger><SelectValue placeholder="Select gender" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Male">Male</SelectItem>
                      <SelectItem value="Female">Female</SelectItem>
                      <SelectItem value="Other">Other</SelectItem>
                    </SelectContent>
                  </Select>
               </div>
               <div class="space-y-2">
                  <Label>Date of Birth</Label>
                  <Input type="date" :model-value="values.date_of_birth" @update:model-value="v => setFieldValue('date_of_birth', v as string)" />
               </div>
               <div class="space-y-2">
                  <Label>Marital Status</Label>
                  <Select :model-value="values.marital_status_id" @update:model-value="v => setFieldValue('marital_status_id', v as string)">
                    <SelectTrigger><SelectValue placeholder="Select status" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="s in membersStore.lookupData?.marital_statuses" 
                        :key="s.id" 
                        :value="s.id.toString()"
                      >
                        {{ s.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
               </div>
             <div class="space-y-2">
                <Label>Occupation</Label>
                <Input :model-value="values.occupation"
                  @update:model-value="v => setFieldValue('occupation', v as string)"
                  placeholder="e.g. Teacher, Merchant" />
              </div>
              <div class="space-y-2">
                <Label>Education Level</Label>
                <Select :model-value="values.education_level_id"
                  @update:model-value="v => setFieldValue('education_level_id', v as string)">
                  <SelectTrigger>
                    <SelectValue placeholder="Select education" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="e in membersStore.lookupData?.education_levels" :key="e.id"
                      :value="e.id.toString()">
                      {{ e.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          <!-- Step 1: Contact -->
          <div v-show="currentStep === 1" class="space-y-6">
             <div class="grid gap-4">
             <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <Label class="text-sm font-semibold">Phone Numbers <span class="text-red-500">*</span></Label>
                  <Button type="button" variant="ghost" size="sm" class="h-8 text-[#00028a]" @click="addPhone">
                    + Add Phone
                  </Button>
                </div>
                <div v-for="(phone, index) in phoneNumbers" :key="index"
                  class="flex items-end gap-2 animate-in fade-in slide-in-from-left-2">
                  <div class="flex-1 space-y-1.5">
                    <Input v-model="phone.number" placeholder="024 000 0000" />
                  </div>
                  <div class="w-32 space-y-1.5">
                    <Select v-model="phone.type_id">
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="t in membersStore.lookupData?.phone_types" :key="t.id"
                          :value="t.id.toString()">
                          {{ t.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <Button v-if="phoneNumbers.length > 1" type="button" variant="ghost" size="icon"
                    class="h-10 w-10 text-red-500 hover:bg-red-50" @click="removePhone(index)">
                    <Trash2 class="w-4 h-4" />
                  </Button>
                </div>
              </div>
                <div class="space-y-2">
                  <Label>Email Address <span class="text-red-500">*</span></Label>
                  <Input :model-value="values.email_address" @update:model-value="v => setFieldValue('email_address', v as string)" type="email" placeholder="john.doe@example.com" />
                </div>
                <div class="space-y-2">
                  <Label>Residential Address</Label>
                  <Input :model-value="values.address" @update:model-value="v => setFieldValue('address', v as string)" placeholder="Street, City, Region" />
                </div>
                <div class="space-y-2">
                  <Label>Branch <span class="text-red-500">*</span></Label>
                  <Select :model-value="values.branch_id" @update:model-value="v => setFieldValue('branch_id', v as string)">
                    <SelectTrigger><SelectValue placeholder="Select branch" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="b in membersStore.lookupData?.branches" 
                        :key="b.id" 
                        :value="b.id.toString()"
                      >
                        {{ b.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>Family (Optional)</Label>
                  <Select :model-value="values.family_id" @update:model-value="v => setFieldValue('family_id', v as string)">
                    <SelectTrigger><SelectValue placeholder="Select family" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">No Family Assignment</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
             </div>
          </div>

          <!-- Step 2: Account -->
          <div v-show="currentStep === 2" class="space-y-6">
             <div class="flex flex-row items-center justify-between rounded-lg border p-4 shadow-sm bg-slate-50/50">
                <div class="space-y-0.5">
                  <Label class="text-base">Enable System Login</Label>
                  <p class="text-sm text-muted-foreground">Allow this member to log into the church management system.</p>
                </div>
                <Switch
                  :checked="values.enable_login"
                  @update:checked="(v: boolean) => setFieldValue('enable_login', v)"
                />
             </div>

             <div v-if="values.enable_login" class="grid gap-4 animate-in slide-in-from-top-2 duration-300">
                <div class="space-y-2">
                  <Label>Username <span class="text-red-500">*</span></Label>
                  <Input :model-value="values.username" @update:model-value="v => setFieldValue('username', v as string)" autocomplete="username" />
                </div>
                <div class="space-y-2">
                  <Label>Password <span class="text-red-500">*</span></Label>
                  <Input :model-value="values.password" @update:model-value="v => setFieldValue('password', v as string)" type="password" autocomplete="new-password" />
                </div>
                <div class="space-y-2">
                  <Label>System Role <span class="text-red-500">*</span></Label>
                  <Select :model-value="values.role_id" @update:model-value="v => setFieldValue('role_id', v as string)">
                    <SelectTrigger><SelectValue placeholder="Select role" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="r in membersStore.roles" 
                        :key="r.RoleID" 
                        :value="r.RoleID.toString()"
                      >
                        {{ r.RoleName }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
             </div>

             <div v-else class="text-center py-6 text-muted-foreground border-2 border-dashed rounded-lg bg-gray-50">
                <p>System login is disabled for this person.</p>
                <p class="text-sm">They will be registered as a member only.</p>
             </div>
          </div>
        </div>

        <CardFooter class="bg-gray-50/50 border-t flex justify-between p-6">
          <Button variant="outline" @click="prevStep" :disabled="currentStep === 0 || isSubmitting">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Previous
          </Button>
          <Button @click="nextStep" :disabled="isSubmitting" class="bg-[#00028a] hover:bg-[#00026d]">
            <template v-if="isSubmitting">
              <RefreshCw class="w-4 h-4 mr-2 animate-spin" />
              Saving...
            </template>
            <template v-else-if="currentStep < 2">
              Next
              <ArrowRight class="w-4 h-4 ml-2" />
            </template>
            <template v-else>
              <CheckCircle2 class="w-4 h-4 mr-2" />
              Complete Registration
            </template>
          </Button>
        </CardFooter>
      </Card>
    </DialogContent>
  </Dialog>
</template>
