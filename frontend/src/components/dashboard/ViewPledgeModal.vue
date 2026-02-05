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
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { 
  Loader2, 
  User, 
  Calendar, 
  Bookmark, 
  History,
  CheckCircle2,
  Clock,
  HandCoins,
  Building,
  AlertCircle
} from 'lucide-vue-next'
import dayjs from 'dayjs'

const props = defineProps<{
  open: boolean
  id: number | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const store = usePledgesStore()
const auth = useAuthStore()
const { toast } = useToast()

const loading = ref(false)
const pledge = ref<any>(null)

watch(() => props.id, async (newId) => {
  if (newId && props.open) {
    await fetchDetails(newId)
  }
})

async function fetchDetails(id: number) {
  loading.value = true
  try {
    pledge.value = await store.fetchPledgeById(id)
  } catch (error) {
    toast({ title: 'Error', description: 'Failed to fetch pledge details', variant: 'destructive' })
    emit('update:open', false)
  } finally {
    loading.value = false
  }
}

function getStatusBadge(status: string) {
  const map: Record<string, { variant: "default" | "destructive" | "outline" | "secondary" | null | undefined, class: string }> = {
    'Fulfilled': { variant: 'default', class: 'bg-green-500' },
    'Active': { variant: 'outline', class: 'text-blue-600 border-blue-200 bg-blue-50' },
    'Cancelled': { variant: 'secondary', class: 'opacity-70' }
  }
  return map[status] || { variant: 'outline', class: '' }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2 text-xl">
          <Bookmark class="w-5 h-5 text-primary" />
          Pledge Details
        </DialogTitle>
        <DialogDescription>
          Comprehensive view of pledge status, member info, and payment history.
        </DialogDescription>
      </DialogHeader>

      <div v-if="loading" class="flex justify-center py-12">
        <Loader2 class="w-8 h-8 animate-spin text-muted-foreground" />
      </div>

      <div v-else-if="pledge" class="space-y-6">
        <!-- Overview Card -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="col-span-1 md:col-span-2 space-y-4">
             <div class="p-4 bg-muted/30 rounded-lg border">
                <div class="flex justify-between items-start mb-4">
                   <div>
                      <h3 class="font-bold text-lg text-primary">{{ pledge.MbrFirstName }} {{ pledge.MbrFamilyName }}</h3>
                      <p class="text-xs text-muted-foreground">Member ID: {{ pledge.MbrID }}</p>
                   </div>
                   <Badge :variant="getStatusBadge(pledge.Status).variant" :class="getStatusBadge(pledge.Status).class">
                      {{ pledge.Status }}
                   </Badge>
                </div>
                
                <div class="space-y-2">
                   <div class="flex justify-between text-xs font-medium">
                      <span>Fulfillment Progress</span>
                      <span>{{ Math.round(((pledge.total_paid || 0) / pledge.PledgeAmount) * 100) }}%</span>
                   </div>
                   <Progress :model-value="((pledge.total_paid || 0) / pledge.PledgeAmount) * 100" class="h-2" />
                   <div class="flex justify-between text-[10px] text-muted-foreground">
                      <span>Paid: {{ auth.currencySymbol }}{{ pledge.total_paid.toLocaleString() }}</span>
                      <span>Target: {{ auth.currencySymbol }}{{ pledge.PledgeAmount.toLocaleString() }}</span>
                   </div>
                </div>
             </div>

             <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center gap-3">
                   <Calendar class="w-4 h-4 text-muted-foreground" />
                   <div>
                      <p class="text-[10px] text-muted-foreground font-bold uppercase">Pledge Date</p>
                      <p class="font-semibold">{{ dayjs(pledge.PledgeDate).format('MMM D, YYYY') }}</p>
                   </div>
                </div>
                <div class="flex items-center gap-3">
                   <Clock class="w-4 h-4 text-muted-foreground" />
                   <div>
                      <p class="text-[10px] text-muted-foreground font-bold uppercase">Due Date</p>
                      <p class="font-semibold">{{ pledge.DueDate ? dayjs(pledge.DueDate).format('MMM D, YYYY') : 'None' }}</p>
                   </div>
                </div>
             </div>
          </div>

          <div class="space-y-4">
             <div class="p-4 bg-red-50 rounded-lg border border-red-100 flex flex-col items-center justify-center text-center h-full">
                <p class="text-[10px] text-red-600 font-bold uppercase mb-1">Outstanding Balance</p>
                <h4 class="text-2xl font-black text-red-700">{{ auth.currencySymbol }}{{ pledge.balance.toLocaleString() }}</h4>
                <div class="mt-2 flex items-center gap-1 text-[10px] text-red-500">
                   <AlertCircle class="w-3 h-3" />
                   <span>Please follow up</span>
                </div>
             </div>
          </div>
        </div>

        <!-- Payment History -->
        <div class="space-y-3">
           <div class="flex items-center gap-2">
              <History class="w-4 h-4 text-primary" />
              <h4 class="text-sm font-bold">Payment History</h4>
           </div>
           
           <div class="border rounded-lg overflow-hidden">
              <Table>
                 <TableHeader class="bg-muted/50">
                    <TableRow>
                       <TableHead class="h-9 px-3 text-xs">Date</TableHead>
                       <TableHead class="h-9 px-3 text-xs text-right">Amount</TableHead>
                       <TableHead class="h-9 px-3 text-xs">Recorded By</TableHead>
                    </TableRow>
                 </TableHeader>
                 <TableBody>
                    <TableRow v-if="!pledge.payments || pledge.payments.length === 0">
                       <TableCell colspan="3" class="h-16 text-center text-xs text-muted-foreground">
                          No payments recorded yet.
                       </TableCell>
                    </TableRow>
                    <TableRow v-for="pay in pledge.payments" :key="pay.PaymentID" class="h-9">
                       <TableCell class="px-3 py-2 text-xs">
                          {{ dayjs(pay.PaymentDate).format('MMM D, YYYY') }}
                       </TableCell>
                       <TableCell class="px-3 py-2 text-xs text-right font-bold text-green-600">
                          {{ auth.currencySymbol }}{{ pay.PaymentAmount.toLocaleString() }}
                       </TableCell>
                       <TableCell class="px-3 py-2 text-xs text-muted-foreground">
                          {{ pay.RecorderFirstName }} {{ pay.RecorderFamilyName }}
                       </TableCell>
                    </TableRow>
                 </TableBody>
              </Table>
           </div>
        </div>

        <div v-if="pledge.Description" class="p-3 bg-slate-50 rounded border text-xs italic text-slate-600">
           <strong>Notes:</strong> {{ pledge.Description }}
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="emit('update:open', false)">Close</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
