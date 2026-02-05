<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { usePledgesStore } from '@/stores/pledges'
import { useLookupsStore } from '@/stores/lookups'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/components/ui/toast/use-toast'
import api from '@/services/api'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { Loader2, Check, ChevronsUpDown, Bookmark } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import dayjs from 'dayjs'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'success'): void
}>()

const store = usePledgesStore()
const lookups = useLookupsStore()
const auth = useAuthStore()
const { toast } = useToast()

const loading = ref(false)
const members = ref<any[]>([])
const membersLoading = ref(false)

const form = ref({
  member_id: '',
  pledge_type_id: '',
  amount: '',
  pledge_date: dayjs().format('YYYY-MM-DD'),
  due_date: '',
  fiscal_year_id: '',
  description: ''
})

// Member selection state
const memberOpen = ref(false)
const memberSearch = ref('')

const filteredMembers = computed(() => {
  const search = memberSearch.value.toLowerCase()
  if (!search) return members.value.slice(0, 50)
  return members.value.filter(m =>
    `${m.MbrFirstName} ${m.MbrFamilyName}`.toLowerCase().includes(search)
  ).slice(0, 50)
})

const selectedMember = computed(() =>
  members.value.find(m => String(m.MbrID) === form.value.member_id)
)

async function fetchMembers() {
  membersLoading.value = true
  try {
    const response = await api.get('member/all')
    members.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch members:', error)
  } finally {
    membersLoading.value = false
  }
}

watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    if (members.value.length === 0) await fetchMembers()
    if (lookups.fiscalYears.length > 0) {
      const active = lookups.fiscalYears.find(fy => fy.Status === 'Active')
      form.value.fiscal_year_id = active ? active.id.toString() : ''
    }
  }
})

async function handleSubmit() {
  if (!form.value.member_id || !form.value.pledge_type_id || !form.value.amount) {
    toast({ title: 'Validation Error', description: 'Please fill in all required fields.', variant: 'destructive' })
    return
  }

  loading.value = true
  try {
    await store.createPledge({
      ...form.value,
      member_id: Number(form.value.member_id),
      pledge_type_id: Number(form.value.pledge_type_id),
      amount: Number(form.value.amount),
      fiscal_year_id: form.value.fiscal_year_id ? Number(form.value.fiscal_year_id) : null
    })
    
    toast({ title: 'Success', description: 'Pledge recorded successfully.' })
    emit('success')
    closeModal()
  } catch (error: any) {
    toast({ 
      title: 'Error', 
      description: error.response?.data?.message || 'Failed to record pledge.', 
      variant: 'destructive' 
    })
  } finally {
    loading.value = false
  }
}

function closeModal() {
  emit('update:open', false)
  form.value = {
    member_id: '',
    pledge_type_id: '',
    amount: '',
    pledge_date: dayjs().format('YYYY-MM-DD'),
    due_date: '',
    fiscal_year_id: form.value.fiscal_year_id, // Keep FY
    description: ''
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Bookmark class="w-5 h-5 text-primary" />
          New Pledge
        </DialogTitle>
        <DialogDescription>
          Record a new financial commitment from a member.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4 py-4">
        <!-- Member Selection -->
        <div class="grid gap-2">
          <Label>Member *</Label>
          <Popover v-model:open="memberOpen">
            <PopoverTrigger as-child>
              <Button variant="outline" role="combobox" :aria-expanded="memberOpen" class="w-full justify-between font-normal">
                <span v-if="selectedMember">
                  {{ selectedMember.MbrFirstName }} {{ selectedMember.MbrFamilyName }}
                </span>
                <span v-else class="text-muted-foreground">Select member...</span>
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[400px] p-0">
              <Command>
                <CommandInput v-model="memberSearch" placeholder="Search members..." />
                <CommandList>
                  <CommandEmpty v-if="!membersLoading">No member found.</CommandEmpty>
                  <div v-if="membersLoading" class="p-4 text-center text-sm text-muted-foreground">
                     <Loader2 class="w-4 h-4 mr-2 animate-spin inline" /> Loading members...
                  </div>
                  <CommandGroup>
                    <CommandItem v-for="member in filteredMembers" :key="member.MbrID"
                      :value="`${member.MbrFirstName} ${member.MbrFamilyName}`"
                      @select="() => { form.member_id = String(member.MbrID); memberOpen = false }">
                      <Check
                        :class="cn('mr-2 h-4 w-4', form.member_id === String(member.MbrID) ? 'opacity-100' : 'opacity-0')" />
                      {{ member.MbrFirstName }} {{ member.MbrFamilyName }}
                      <span class="ml-auto text-[10px] text-muted-foreground uppercase">ID: {{ member.MbrID }}</span>
                    </CommandItem>
                  </CommandGroup>
                </CommandList>
              </Command>
            </PopoverContent>
          </Popover>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="grid gap-2">
            <Label for="amount">Pledge Amount *</Label>
            <div class="relative">
               <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">{{ auth.currencySymbol }}</span>
               <Input id="amount" type="number" step="0.01" v-model="form.amount" class="pl-12" placeholder="0.00" required />
            </div>
          </div>
          <div class="grid gap-2">
            <Label for="type">Pledge Type *</Label>
            <Select v-model="form.pledge_type_id">
              <SelectTrigger id="type">
                <SelectValue placeholder="Select type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="type in lookups.pledgeTypes" :key="type.id" :value="type.id.toString()">
                  {{ type.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="grid gap-2">
            <Label for="date">Pledge Date *</Label>
            <Input id="date" type="date" v-model="form.pledge_date" required />
          </div>
          <div class="grid gap-2">
            <Label for="due_date">Due Date</Label>
            <Input id="due_date" type="date" v-model="form.due_date" />
          </div>
        </div>

        <div class="grid gap-2">
          <Label for="fy">Fiscal Year</Label>
          <Select v-model="form.fiscal_year_id">
            <SelectTrigger id="fy">
              <SelectValue placeholder="Select year" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="fy in lookups.fiscalYears" :key="fy.id" :value="fy.id.toString()">
                {{ fy.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="grid gap-2">
          <Label for="description">Description</Label>
          <Textarea id="description" v-model="form.description" placeholder="Optional notes about this pledge..." rows="2" />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="closeModal" :disabled="loading">Cancel</Button>
          <Button type="submit" :disabled="loading">
            <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
            Record Pledge
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
