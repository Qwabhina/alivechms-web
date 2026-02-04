<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useContributionsStore, type Contribution } from '@/stores/contributions'
import { useToast } from '@/components/ui/toast/use-toast'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter
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
  SelectValue
} from '@/components/ui/select'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList
} from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger
} from '@/components/ui/popover'
import { Coins, Check, ChevronsUpDown, Loader2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{
  open: boolean
  contribution: Contribution | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
}>()

const store = useContributionsStore()
const { toast } = useToast()

const isEditMode = computed(() => !!props.contribution)
const loading = ref(false)

// Form state
const form = ref({
  member_id: '',
  amount: '',
  date: new Date().toISOString().split('T')[0],
  contribution_type_id: '',
  payment_option_id: '',
  fiscal_year_id: '',
  description: ''
})

// Member combobox state
const memberOpen = ref(false)
const memberSearch = ref('')

const filteredMembers = computed(() => {
  const search = memberSearch.value.toLowerCase()
  if (!search) return store.members.slice(0, 50)
  return store.members.filter(m =>
    `${m.MbrFirstName} ${m.MbrFamilyName}`.toLowerCase().includes(search)
  ).slice(0, 50)
})

const selectedMember = computed(() =>
  store.members.find(m => String(m.MbrID) === form.value.member_id)
)

// Reset form when modal opens
watch(() => props.open, async (open) => {
  if (open) {
    if (props.contribution) {
      // Edit mode - load contribution data
      form.value = {
        member_id: String(props.contribution.MbrID),
        amount: String(props.contribution.ContributionAmount),
        date: props.contribution.ContributionDate,
        contribution_type_id: String(props.contribution.ContributionTypeID),
        payment_option_id: String(props.contribution.PaymentOptionID),
        fiscal_year_id: String(props.contribution.FiscalYearID),
        description: props.contribution.Notes || ''
      }
    } else {
      // Create mode - reset form
      const activeFY = store.fiscalYears.find(fy => fy.Status === 'Active')
      form.value = {
        member_id: '',
        amount: '',
        date: new Date().toISOString().split('T')[0],
        contribution_type_id: '',
        payment_option_id: '',
        fiscal_year_id: activeFY ? String(activeFY.FiscalYearID) : '',
        description: ''
      }
    }
    memberSearch.value = ''
  }
})

async function handleSubmit() {
  // Validation
  if (!form.value.member_id || !form.value.amount || !form.value.date ||
    !form.value.contribution_type_id || !form.value.payment_option_id ||
    !form.value.fiscal_year_id) {
    toast({ description: 'Please fill all required fields', variant: 'default' })
    return
  }

  loading.value = true
  try {
    const payload = {
      member_id: parseInt(form.value.member_id),
      amount: parseFloat(form.value.amount),
      date: form.value.date,
      contribution_type_id: parseInt(form.value.contribution_type_id),
      payment_option_id: parseInt(form.value.payment_option_id),
      fiscal_year_id: parseInt(form.value.fiscal_year_id),
      description: form.value.description.trim() || undefined
    }

    if (isEditMode.value && props.contribution) {
      await store.updateContribution(props.contribution.ContributionID, payload)
      toast({ title: 'Success', description: 'Contribution updated successfully' })
    } else {
      await store.createContribution(payload)
      toast({ title: 'Success', description: 'Contribution recorded successfully' })
    }

    emit('saved')
  } catch (error: any) {
    toast({ title: 'Error', description: error.message || 'Failed to save contribution', variant: 'destructive' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => !val && emit('close')">
    <DialogContent class="max-w-lg">
      <DialogHeader class="bg-blue-50/50 -m-6 mb-0 p-6 border-b">
        <DialogTitle class="flex items-center gap-2">
          <Coins class="w-5 h-5 text-blue-600" />
          {{ isEditMode ? 'Edit Contribution' : 'Record Contribution' }}
        </DialogTitle>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4 pt-4">
        <!-- Member Selection -->
        <div class="space-y-2">
          <Label>Member <span class="text-red-500">*</span></Label>
          <Popover v-model:open="memberOpen">
            <PopoverTrigger as-child>
              <Button variant="outline" role="combobox" :aria-expanded="memberOpen" class="w-full justify-between">
                <span v-if="selectedMember">
                  {{ selectedMember.MbrFirstName }} {{ selectedMember.MbrFamilyName }}
                </span>
                <span v-else class="text-muted-foreground">Select Member</span>
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[400px] p-0">
              <Command>
                <CommandInput v-model="memberSearch" placeholder="Search members..." />
                <CommandList>
                  <CommandEmpty>No member found.</CommandEmpty>
                  <CommandGroup>
                    <CommandItem v-for="member in filteredMembers" :key="member.MbrID"
                      :value="`${member.MbrFirstName} ${member.MbrFamilyName}`"
                      @select="() => { form.member_id = String(member.MbrID); memberOpen = false }">
                      <Check
                        :class="cn('mr-2 h-4 w-4', form.member_id === String(member.MbrID) ? 'opacity-100' : 'opacity-0')" />
                      {{ member.MbrFirstName }} {{ member.MbrFamilyName }}
                    </CommandItem>
                  </CommandGroup>
                </CommandList>
              </Command>
            </PopoverContent>
          </Popover>
        </div>

        <!-- Amount and Date Row -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label>Amount <span class="text-red-500">*</span></Label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">
                {{ store.currencySymbol }}
              </span>
              <Input v-model="form.amount" type="number" step="0.01" min="0.01" class="pl-12" placeholder="0.00"
                required />
            </div>
          </div>

          <div class="space-y-2">
            <Label>Date <span class="text-red-500">*</span></Label>
            <Input v-model="form.date" type="date" required />
          </div>
        </div>

        <!-- Type and Payment Method Row -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label>Contribution Type <span class="text-red-500">*</span></Label>
            <Select v-model="form.contribution_type_id">
              <SelectTrigger>
                <SelectValue placeholder="Select Type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="type in store.contributionTypes" :key="type.ContributionTypeID"
                  :value="String(type.ContributionTypeID)">
                  {{ type.ContributionTypeName }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label>Payment Method <span class="text-red-500">*</span></Label>
            <Select v-model="form.payment_option_id">
              <SelectTrigger>
                <SelectValue placeholder="Select Method" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="opt in store.paymentOptions" :key="opt.PaymentOptionID"
                  :value="String(opt.PaymentOptionID)">
                  {{ opt.PaymentOptionName }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <!-- Fiscal Year -->
        <div class="space-y-2">
          <Label>Fiscal Year <span class="text-red-500">*</span></Label>
          <Select v-model="form.fiscal_year_id">
            <SelectTrigger>
              <SelectValue placeholder="Select Fiscal Year" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="fy in store.fiscalYears" :key="fy.FiscalYearID" :value="String(fy.FiscalYearID)">
                {{ fy.FiscalYearName }}
                <span v-if="fy.Status === 'Active'" class="text-green-600 ml-1">(Active)</span>
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Description -->
        <div class="space-y-2">
          <Label>Description</Label>
          <Textarea v-model="form.description" placeholder="Optional notes..." rows="2" />
        </div>

        <DialogFooter class="gap-2 pt-4">
          <Button type="button" variant="outline" @click="emit('close')">
            Cancel
          </Button>
          <Button type="submit" :disabled="loading">
            <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
            {{ isEditMode ? 'Update Contribution' : 'Save Contribution' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
