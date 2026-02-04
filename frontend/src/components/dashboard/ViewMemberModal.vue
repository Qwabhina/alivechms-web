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
  FileText,
  Printer
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
    const response = await api.get(`member/view/${props.memberId}`)
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

const handlePrint = () => {
  if (!member.value) return

  const printWindow = window.open('', '_blank')
  if (!printWindow) return

  const photoHtml = member.value.MbrProfilePicture
    ? `<img src="${resolveUrl(member.value.MbrProfilePicture)}" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid white;box-shadow:0 4px 6px rgba(0,0,0,0.1);">`
    : `<div style="width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg, #00028a 0%, #00026d 100%);color:white;display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:bold;border:4px solid white;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        ${member.value.MbrFirstName[0]}${member.value.MbrFamilyName[0]}
      </div>`

  const fullName = `${member.value.MbrFirstName} ${member.value.MbrOtherNames || ''} ${member.value.MbrFamilyName}`.trim()

  printWindow.document.write(`
    <html>
      <head>
        <title>Member Profile - ${fullName}</title>
        <style>
          body { font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; margin: 0; padding: 40px; }
          .header { text-align: center; padding: 40px 20px; background: #f8fafc; border-radius: 16px; margin-bottom: 40px; }
          .photo { margin-bottom: 20px; }
          .name { font-size: 28px; font-weight: 800; color: #00028a; margin: 0; }
          .id { color: #64748b; font-size: 14px; margin-top: 4px; }
          .section { margin-bottom: 32px; }
          .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; }
          .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
          .item { display: flex; flex-direction: column; }
          .label { font-size: 11px; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px; }
          .value { font-size: 14px; font-weight: 500; color: #1e293b; }
          @media print {
            body { padding: 0; }
            .header { background: #f8fafc !important; -webkit-print-color-adjust: exact; }
          }
        </style>
      </head>
      <body>
        <div class="header">
          <div class="photo">${photoHtml}</div>
          <h1 class="name">${fullName}</h1>
          <div class="id">Member ID: ${member.value.MbrUniqueID}</div>
        </div>

        <div class="section">
          <div class="section-title">Personal Information</div>
          <div class="grid">
            <div class="item"><span class="label">Gender</span><span class="value">${member.value.MbrGender}</span></div>
            <div class="item"><span class="label">Date of Birth</span><span class="value">${member.value.MbrDateOfBirth ? dayjs(member.value.MbrDateOfBirth).format('MMMM DD, YYYY') : 'Not provided'}</span></div>
            <div class="item"><span class="label">Marital Status</span><span class="value">${member.value.MaritalStatusName || 'Not provided'}</span></div>
            <div class="item"><span class="label">Occupation</span><span class="value">${member.value.MbrOccupation || 'Not provided'}</span></div>
          </div>
        </div>

        <div class="section">
          <div class="section-title">Contact & Church Details</div>
          <div class="grid">
            <div class="item"><span class="label">Email</span><span class="value">${member.value.MbrEmailAddress}</span></div>
            <div class="item"><span class="label">Phone(s)</span><span class="value">${member.value.phones?.map((p: any) => p.PhoneNumber).join(', ') || 'Not provided'}</span></div>
            <div class="item"><span class="label">Family</span><span class="value">${member.value.FamilyName || 'None assigned'}</span></div>
            <div class="item"><span class="label">Branch</span><span class="value">${member.value.BranchName}</span></div>
            <div class="item" style="grid-column: span 2;"><span class="label">Residential Address</span><span class="value">${member.value.MbrResidentialAddress || 'Not provided'}</span></div>
          </div>
        </div>

        <div style="text-align: center; margin-top: 60px; font-size: 10px; color: #94a3b8;">
          Printed on ${dayjs().format('MMMM DD, YYYY HH:mm')}
        </div>

        <script>
          window.onload = () => {
            window.print();
            setTimeout(() => window.close(), 500);
          };
        <\/script>
      <\/body>
    <\/html>
  `)
  printWindow.document.close()
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
       <div class="relative overflow-hidden bg-[#00028a] text-white p-6 md:p-8">
          <!-- Background Decoration -->
          <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-[#e5a100]/10 rounded-full blur-3xl"></div>
          <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-48 h-48 bg-[#00028a]/20 rounded-full blur-2xl"></div>

          <div class="relative flex flex-col md:flex-row gap-6 md:gap-8 items-center md:items-start">
            <div class="relative group">
              <Avatar
                class="h-24 w-24 md:h-32 md:w-32 border-4 border-white/20 shadow-2xl transition-transform duration-500 group-hover:scale-105">
                <AvatarImage
                  :src="resolveUrl(member.MbrProfilePicture) || `https://api.dicebear.com/7.x/initials/svg?seed=${member.MbrFirstName}`" />
                <AvatarFallback class="bg-white/10 text-white text-3xl font-bold">
                  {{ member.MbrFirstName.substring(0, 1) }}{{ member.MbrFamilyName.substring(0, 1) }}
                </AvatarFallback>
              </Avatar>
             <div class="absolute -bottom-2 -right-2">
                <Badge :class="getStatusBadge(member.MembershipStatusName)"
                  class="h-7 px-3 text-xs font-bold border-2 border-white shadow-md">
                  {{ member.MembershipStatusName }}
                </Badge>
              </div>
            </div>
            
<div class="flex-1 space-y-4 text-center md:text-left">
              <div>
                <h2 class="text-xl md:text-3xl font-extrabold tracking-tight leading-tight">
                  {{ member.MbrFirstName }} {{ member.MbrOtherNames }} {{ member.MbrFamilyName }}
                </h2>
               <p
                  class="text-blue-100/80 font-medium flex items-center justify-center md:justify-start gap-2 mt-1 text-sm md:text-base">
                  <User class="w-4 h-4 opacity-70" /> {{ member.MbrUniqueID }}
                </p>
              </div>
             <div class="flex flex-wrap justify-center md:justify-start gap-3">
                <div
                  class="bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-full flex items-center gap-2 text-xs font-medium border border-white/10">
                  <Mail class="w-3.5 h-3.5 opacity-70" /> {{ member.MbrEmailAddress }}
                </div>
                <div
                  class="bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-full flex items-center gap-2 text-xs font-medium border border-white/10">
                  <Calendar class="w-3.5 h-3.5 opacity-70" /> {{
                    dayjs(member.MbrRegistrationDate).format('MMM DD, YYYY')
                  }}
                </div>
              </div>
            </div>
            
<div class="flex flex-wrap justify-center items-center gap-2 w-full md:w-auto">
              <Button v-if="member.MbrEmailAddress" size="sm" variant="outline"
                class="bg-white/10 border-white/20 text-white hover:bg-white hover:text-[#00028a]" as-child>
                <a :href="`mailto:${member.MbrEmailAddress}`">
                  <Mail class="w-4 h-4 mr-2" /> Email
                </a>
              </Button>
              <Button v-if="member.phones?.find((p: any) => p.IsPrimary || p.is_primary)" size="sm" variant="outline"
                class="bg-white/10 border-white/20 text-white hover:bg-white hover:text-[#00028a]" as-child>
                <a :href="`tel:${member.phones.find((p: any) => p.IsPrimary || p.is_primary).PhoneNumber}`">
                  <Phone class="w-4 h-4 mr-2" /> Call
                </a>
              </Button>
              <Button size="sm" variant="outline" @click="handlePrint"
                class="bg-white/10 border-white/20 text-white hover:bg-white hover:text-[#00028a]">
                <Printer class="w-4 h-4 mr-2" /> Print
              </Button>
              <Button size="sm" @click="emit('edit', member)"
                class="bg-[#e5a100] hover:bg-[#c98e00] text-white border-none">
                <Pencil class="w-4 h-4 mr-2" /> Edit
              </Button>
            </div>
          </div>
        </div>

        <Tabs default-value="personal" class="w-full">
         <TabsList
            class="w-full justify-start px-4 md:px-8 h-12 bg-transparent border-b rounded-none gap-4 md:gap-6 overflow-x-auto no-scrollbar">
            <TabsTrigger value="personal"
              class="bg-transparent border-b-2 border-transparent data-[state=active]:border-[#00028a] data-[state=active]:text-[#00028a] rounded-none px-0 h-12 shadow-none transition-none text-xs md:text-sm whitespace-nowrap">
              Personal Info
            </TabsTrigger>
           <TabsTrigger value="contact"
              class="bg-transparent border-b-2 border-transparent data-[state=active]:border-[#00028a] data-[state=active]:text-[#00028a] rounded-none px-0 h-12 shadow-none transition-none text-xs md:text-sm whitespace-nowrap">
              Contact & Family
            </TabsTrigger>
           <TabsTrigger value="history"
              class="bg-transparent border-b-2 border-transparent data-[state=active]:border-[#00028a] data-[state=active]:text-[#00028a] rounded-none px-0 h-12 shadow-none transition-none text-xs md:text-sm whitespace-nowrap">
              History
            </TabsTrigger>
          </TabsList>
          
<div class="p-6 md:p-8 max-h-[50vh] overflow-y-auto">
            <TabsContent value="personal" class="mt-0 outline-none">
              <div class="grid md:grid-cols-2 gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 space-y-6">
                  <div class="flex items-center gap-2 pb-2 border-b border-slate-200/50">
                    <User class="w-5 h-5 text-[#00028a]" />
                    <h3 class="text-sm font-bold tracking-tight text-slate-800">Identity Details</h3>
                  </div>
                 <div class="grid grid-cols-2 gap-y-6">
                    <div class="space-y-1">
                      <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Gender</p>
                      <p class="font-semibold text-slate-700">{{ member.MbrGender }}</p>
                    </div>
                   <div class="space-y-1">
                      <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Marital Status</p>
                      <p class="font-semibold text-slate-700">{{ member.MaritalStatusName || 'Not Specified' }}</p>
                    </div>
                    <div class="space-y-1 col-span-2">
                      <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Date of Birth</p>
                      <p class="font-semibold text-slate-700">{{ member.MbrDateOfBirth ?
                        dayjs(member.MbrDateOfBirth).format('MMMM DD, YYYY') : 'Not Provided' }}</p>
                    </div>
                  </div>
                </div>
                
<div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 space-y-6">
                  <div class="flex items-center gap-2 pb-2 border-b border-slate-200/50">
                    <Briefcase class="w-5 h-5 text-[#e5a100]" />
                    <h3 class="text-sm font-bold tracking-tight text-slate-800">Professional Profile</h3>
                  </div>
                 <div class="space-y-6">
                    <div class="space-y-1">
                      <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Current Occupation</p>
                      <p class="font-semibold text-slate-700">{{ member.MbrOccupation || 'Not Specified' }}</p>
                    </div>
                   <div class="space-y-1">
                      <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Education Level</p>
                      <p class="font-semibold text-slate-700">{{ member.EducationLevelName || 'Not Specified' }}</p>
                    </div>
                    <div class="space-y-1">
                      <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Assigned Branch</p>
                      <p class="font-semibold text-[#00028a]">{{ member.BranchName || 'N/A' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </TabsContent>
            
<TabsContent value="contact" class="mt-0 outline-none">
              <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="space-y-4">
                 <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                      <Phone class="w-4 h-4" /> Contact Numbers
                    </h3>
                  </div>
                  <div class="grid md:grid-cols-2 gap-4">
                   <div v-for="phone in member.phones" :key="phone.PhoneID"
                      class="group p-4 bg-white border border-slate-100 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 hover:border-[#00028a]/20">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                          <div
                            class="h-10 w-10 rounded-xl bg-[#00028a]/5 text-[#00028a] flex items-center justify-center group-hover:bg-[#00028a] group-hover:text-white transition-colors duration-300">
                            <Phone class="w-5 h-5" />
                          </div>
                          <div class="flex flex-col">
                           <span class="text-lg font-bold text-slate-700 tracking-tight">{{ phone.PhoneNumber }}</span>
                            <span class="text-[10px] uppercase font-bold text-slate-400">{{ phone.TypeName }}</span>
                          </div>
                       </div>
                       <Badge v-if="phone.IsPrimary"
                          class="bg-[#e5a100]/10 text-[#e5a100] border-[#e5a100]/20 text-[10px] font-bold">Primary
                        </Badge>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="space-y-4">
                 <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <MapPin class="w-4 h-4" /> Residential Location
                  </h3>
                  <div
                    class="p-6 bg-gradient-to-br from-slate-50 to-white rounded-2xl border border-slate-100 flex items-start gap-4 shadow-sm">
                    <div
                      class="h-10 w-10 rounded-xl bg-[#e5a100]/5 text-[#e5a100] flex items-center justify-center shrink-0">
                      <MapPin class="w-5 h-5" />
                    </div>
                    <div class="pt-1">
                      <p class="text-slate-700 font-semibold leading-relaxed text-lg">
                        {{ member.MbrResidentialAddress || 'No primary address recorded' }}
                      </p>
                      <p class="text-xs text-slate-400 font-medium mt-1">Confirmed Residential Location</p>
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
