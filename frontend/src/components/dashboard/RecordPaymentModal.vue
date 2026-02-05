<script setup lang="ts">
import { ref, watch } from 'vue'
import { usePledgesStore } from '@/stores/pledges'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/components/ui/toast/use-toast'
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
import { Loader2, HandCoins } from 'lucide-vue-next'
import dayjs from 'dayjs'

const props = defineProps<{
  open: boolean
  pledgeId: number | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'success'): void
}>()

const store = usePledgesStore()
const auth = useAuthStore()
const { toast } = useToast()

const loading = ref(false)
const pledge = ref<any>(null)
const form = ref({
  amount: '',
  payment_date: dayjs().format('YYYY-MM-DD')
})

watch(() => props.pledgeId, async (newId) => {
  if (newId && props.open) {
    try {
      pledge.value = await store.fetchPledgeById(newId)
      // Suggest remaining balance as default amount
      form.value.amount = pledge.value.balance.toString()
    } catch (e) {
      toast({ title: 'Error', description: 'Failed to fetch pledge info', variant: 'destructive' })
      emit('update:open', false)
    }
  }
})

async function handleSubmit() {
  if (!props.pledgeId || !form.value.amount) return

  loading.value = true
  try {
    await store.recordPayment(props.pledgeId, {
      amount: Number(form.value.amount),
      payment_date: form.value.payment_date
    })
    
    toast({ title: 'Success', description: 'Payment recorded successfully.' })
    emit('success')
    closeModal()
  } catch (error: any) {
    toast({ 
      title: 'Error', 
      description: error.response?.data?.message || 'Failed to record payment.', 
      variant: 'destructive' 
    })
  } finally {
    loading.value = false
  }
}

function closeModal() {
  emit('update:open', false)
  form.value = {
    amount: '',
    payment_date: dayjs().format('YYYY-MM-DD')
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <HandCoins class="w-5 h-5 text-green-600" />
          Record Pledge Payment
        </DialogTitle>
        <DialogDescription v-if="pledge">
          Recording payment for <strong>{{ pledge.MbrFirstName }} {{ pledge.MbrFamilyName }}</strong>'s 
          {{ pledge.PledgeTypeName }}.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4 py-4 text-sm" v-if="pledge">
        <div class="grid grid-cols-2 gap-4 p-3 bg-muted/50 rounded-lg border border-dashed">
           <div>
              <p class="text-[10px] text-muted-foreground uppercase font-bold">Pledge Amount</p>
              <p class="font-bold">{{ auth.currencySymbol }}{{ pledge.PledgeAmount.toLocaleString() }}</p>
           </div>
           <div>
              <p class="text-[10px] text-muted-foreground uppercase font-bold text-right">Remaining Balance</p>
              <p class="font-bold text-right text-red-600">{{ auth.currencySymbol }}{{ pledge.balance.toLocaleString() }}</p>
           </div>
        </div>

        <div class="grid gap-2">
          <Label for="amount">Payment Amount *</Label>
          <div class="relative">
             <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">{{ auth.currencySymbol }}</span>
             <Input id="amount" type="number" step="0.01" v-model="form.amount" class="pl-10" required />
          </div>
        </div>

        <div class="grid gap-2">
          <Label for="date">Payment Date *</Label>
          <Input id="date" type="date" v-model="form.payment_date" required />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="closeModal" :disabled="loading">Cancel</Button>
          <Button type="submit" :disabled="loading" class="bg-green-600 hover:bg-green-700">
            <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
            Record Payment
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
