<script setup lang="ts">
import { ref, watch } from 'vue'
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogDescription 
} from '@/components/ui/dialog'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Skeleton } from '@/components/ui/skeleton'
import { Button } from '@/components/ui/button'
import { 
  User, 
  Phone, 
  Mail, 
  MapPin, 
  Briefcase, 
  GraduationCap, 
  Calendar, 
  Users, 
  Trash2, 
  Pencil,
  FileText
} from 'lucide-vue-next'
import api, { resolveUrl } from '@/services/api'
import { Alerts } from '@/utils/alerts'
import dayjs from 'dayjs'

const props = defineProps<{
  open: boolean
  memberId: number | null
}>()

const emit = defineEmits(['close', 'edit'])

const member = ref<any>(null)
const loading = ref(false)

const fetchMemberDetails = async () => {
  if (!props.memberId) return
  loading.value = true
  try {
    const response = await api.get(`member/get/${props.memberId}`)
    member.value = response.data.data
  } catch (error) {
    Alerts.handleApiError(error, 'Failed to load member details')
    emit('close')
  } finally {
    loading.value = false
  }
}

watch(() => props.open, (newVal) => {
  if (newVal && props.memberId) {
    fetchMemberDetails()
  } else {
    member.value = null
  }
})

const getStatusBadge = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'active': return 'bg-emerald-100 text-emerald-700'
    case 'inactive': return 'bg-slate-100 text-slate-700'
    case 'deceased': return 'bg-rose-100 text-rose-700'
    default: return 'bg-blue-100 text-blue-700'
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => !val && emit('close')">
    <DialogContent class="max-w-3xl p-0 overflow-hidden border-none shadow-2xl">
      <template v-if="loading">
        <div class="p-8 space-y-4">
          <div class="flex items-center gap-4">
            <Skeleton class="h-20 w-20 rounded-full" />
            <div class="space-y-2">
              <Skeleton class="h-6 w-48" />
              <Skeleton class="h-4 w-32" />
            </div>
          </div>
          <Skeleton class="h-40 w-full" />
        </div>
      </template>
      
      <template v-else-if="member">
        <!-- Header / Profile Section -->
        <div class="bg-[#00028a]/5 px-8 pt-8 pb-6 border-b">
          <div class="flex flex-col md:flex-row gap-6 items-center md:items-start text-center md:text-left">
            <Avatar class="h-24 w-24 border-4 border-white shadow-sm">
              <AvatarImage :src="resolveUrl(member.MbrProfilePicture) || `https://api.dicebear.com/7.x/initials/svg?seed=${member.MbrFirstName}`" />
              <AvatarFallback>{{ member.MbrFirstName.substring(0, 1) }}</AvatarFallback>
            </Avatar>
            
            <div class="flex-1 space-y-2">
              <div class="flex flex-col md:flex-row md:items-center gap-3">
                <h2 class="text-2xl font-bold text-[#00028a]">
                  {{ member.MbrFirstName }} {{ member.MbrOtherNames }} {{ member.MbrFamilyName }}
                </h2>
                <Badge :class="getStatusBadge(member.MembershipStatusName)">
                  {{ member.MembershipStatusName }}
                </Badge>
              </div>
              <div class="flex flex-wrap justify-center md:justify-start gap-x-4 gap-y-2 text-sm text-muted-foreground">
                <span class="flex items-center gap-1.5">
                  <Mail class="w-4 h-4" /> {{ member.MbrEmailAddress }}
                </span>
                <span class="flex items-center gap-1.5">
                  <Calendar class="w-4 h-4" /> Registered {{ dayjs(member.MbrRegistrationDate).format('MMM DD, YYYY') }}
                </span>
                <span class="flex items-center gap-1.5">
                  <User class="w-4 h-4" /> ID: <code>{{ member.MbrUniqueID }}</code>
                </span>
              </div>
            </div>
            
            <div class="flex gap-2">
              <Button size="sm" variant="outline" @click="emit('edit', member)">
                <Pencil class="w-4 h-4 mr-2" /> Edit
              </Button>
            </div>
          </div>
        </div>

        <Tabs default-value="personal" class="w-full">
          <TabsList class="w-full justify-start px-8 h-12 bg-transparent border-b rounded-none gap-6">
            <TabsTrigger value="personal" class="bg-transparent border-b-2 border-transparent data-[state=active]:border-[#00028a] data-[state=active]:text-[#00028a] rounded-none px-0 h-12 shadow-none transition-none">
              Personal Info
            </TabsTrigger>
            <TabsTrigger value="contact" class="bg-transparent border-b-2 border-transparent data-[state=active]:border-[#00028a] data-[state=active]:text-[#00028a] rounded-none px-0 h-12 shadow-none transition-none">
              Contact & Family
            </TabsTrigger>
            <TabsTrigger value="history" class="bg-transparent border-b-2 border-transparent data-[state=active]:border-[#00028a] data-[state=active]:text-[#00028a] rounded-none px-0 h-12 shadow-none transition-none">
              History
            </TabsTrigger>
          </TabsList>
          
          <div class="p-8 max-h-[50vh] overflow-y-auto">
            <TabsContent value="personal" class="mt-0 space-y-6">
              <div class="grid md:grid-cols-2 gap-8">
                <div class="space-y-4">
                  <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Basic Details</h3>
                  <div class="space-y-3">
                    <div class="flex flex-col">
                      <span class="text-xs text-muted-foreground">Gender</span>
                      <span class="font-medium">{{ member.MbrGender }}</span>
                    </div>
                    <div class="flex flex-col">
                      <span class="text-xs text-muted-foreground">Date of Birth</span>
                      <span class="font-medium">{{ member.MbrDateOfBirth ? dayjs(member.MbrDateOfBirth).format('MMMM DD, YYYY') : 'N/A' }}</span>
                    </div>
                    <div class="flex flex-col">
                      <span class="text-xs text-muted-foreground">Marital Status</span>
                      <span class="font-medium">{{ member.MaritalStatusName || 'Not Specified' }}</span>
                    </div>
                  </div>
                </div>
                
                <div class="space-y-4">
                  <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Professional & Education</h3>
                  <div class="space-y-3">
                    <div class="flex flex-col">
                      <span class="text-xs text-muted-foreground flex items-center gap-1.5"><Briefcase class="w-3 h-3" /> Occupation</span>
                      <span class="font-medium">{{ member.MbrOccupation || 'N/A' }}</span>
                    </div>
                    <div class="flex flex-col">
                      <span class="text-xs text-muted-foreground flex items-center gap-1.5"><GraduationCap class="w-3 h-3" /> Education Level</span>
                      <span class="font-medium">{{ member.EducationLevelName || 'N/A' }}</span>
                    </div>
                    <div class="flex flex-col">
                      <span class="text-xs text-muted-foreground flex items-center gap-1.5"><Users class="w-3 h-3" /> Branch</span>
                      <span class="font-medium">{{ member.BranchName || 'N/A' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </TabsContent>
            
            <TabsContent value="contact" class="mt-0 space-y-6">
              <div class="space-y-6">
                <div class="space-y-4">
                  <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Communication</h3>
                  <div class="grid md:grid-cols-2 gap-4">
                    <div v-for="phone in member.phones" :key="phone.PhoneID" class="p-3 border rounded-lg flex items-center justify-between">
                      <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded bg-blue-50 text-blue-600 flex items-center justify-center">
                          <Phone class="w-4 h-4" />
                        </div>
                        <div class="flex flex-col">
                          <span class="font-medium">{{ phone.PhoneNumber }}</span>
                          <span class="text-[10px] uppercase font-bold text-muted-foreground">{{ phone.TypeName }}</span>
                        </div>
                      </div>
                      <Badge v-if="phone.IsPrimary" variant="secondary" class="bg-blue-100 text-blue-700 text-[10px]">Primary</Badge>
                    </div>
                  </div>
                </div>

                <div class="space-y-4">
                  <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Location</h3>
                  <div class="p-4 bg-slate-50 rounded-xl flex items-start gap-3">
                    <MapPin class="w-5 h-5 text-slate-400 mt-0.5" />
                    <div>
                      <p class="font-medium">{{ member.MbrResidentialAddress || 'No address provided' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </TabsContent>

            <TabsContent value="history" class="mt-0 py-8 text-center text-muted-foreground">
              <FileText class="w-12 h-12 mx-auto mb-2 text-slate-200" />
              <p>Activity history for this member will appear here.</p>
            </TabsContent>
          </div>
        </Tabs>
      </template>
    </DialogContent>
  </Dialog>
</template>
