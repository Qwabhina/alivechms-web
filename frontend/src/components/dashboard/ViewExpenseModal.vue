<script setup lang="ts">
import { ref, watch } from 'vue'
import { useExpensesStore } from '@/stores/expenses'
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
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
   Loader2,
   User,
   Calendar,
   Building,
   Tag,
   CheckCircle2,
   XCircle,
   Clock,
   FileText,
   MessageSquare,
   AlertCircle
} from 'lucide-vue-next'
import dayjs from 'dayjs'
import { Alerts } from '@/utils/alerts'

const props = defineProps<{
   open: boolean
   id: number | null
}>()

const emit = defineEmits<{
   (e: 'update:open', value: boolean): void
   (e: 'action'): void
}>()

const store = useExpensesStore()
const auth = useAuthStore()
const { toast } = useToast()

const loading = ref(false)
const expense = ref<any>(null)
const remarks = ref('')
const reviewing = ref(false)

watch(() => props.id, async (newId) => {
   if (newId && props.open) {
      await fetchDetails(newId)
   }
})

async function fetchDetails(id: number) {
   loading.value = true
   try {
      expense.value = await store.fetchExpenseById(id)
   } catch (error) {
      toast({ title: 'Error', description: 'Failed to fetch expense details', variant: 'destructive' })
      emit('update:open', false)
   } finally {
      loading.value = false
   }
}

function getStatusBadge(status: string) {
   const map: Record<string, { variant: "default" | "destructive" | "outline" | "secondary", class: string }> = {
      'Approved': { variant: 'default', class: 'bg-green-500 hover:bg-green-600' },
      'Pending Approval': { variant: 'outline', class: 'text-amber-600 border-amber-200 bg-amber-50' },
      'Declined': { variant: 'destructive', class: '' },
      'Cancelled': { variant: 'secondary', class: 'opacity-70' }
   }
   return map[status] || { variant: 'outline', class: '' }
}

async function handleReview(action: 'approve' | 'reject') {
   if (!expense.value) return

   reviewing.value = true
   try {
      await store.reviewExpense(expense.value.ExpenseID, action, remarks.value)
      toast({ title: 'Success', description: `Expense ${action}d successfully` })
      emit('action')
      emit('update:open', false)
   } catch (error: any) {
      toast({
         title: 'Error',
         description: error.response?.data?.message || `Failed to ${action} expense`,
         variant: 'destructive'
      })
   } finally {
      reviewing.value = false
   }
}

async function handleCancel() {
   if (!expense.value) return

   const confirmed = await Alerts.confirm({
      title: 'Cancel Expense Request',
      text: 'Please provide a reason for cancelling this request.',
      input: 'textarea',
      inputPlaceholder: 'Cancellation reason...',
      confirmButtonText: 'Cancel Request',
      confirmButtonColor: '#d33'
   })

   if (confirmed && (confirmed as any).value) {
      try {
         await store.cancelExpense(expense.value.ExpenseID, (confirmed as any).value)
         toast({ title: 'Cancelled', description: 'Expense request has been cancelled' })
         emit('action')
         emit('update:open', false)
      } catch (error) {
         toast({ title: 'Error', description: 'Failed to cancel request', variant: 'destructive' })
      }
   }
}
</script>

<template>
   <Dialog :open="open" @update:open="emit('update:open', $event)">
      <DialogContent class="sm:max-w-[600px]">
         <DialogHeader>
            <DialogTitle class="flex items-center gap-2 text-xl">
               <FileText class="w-5 h-5 text-primary" />
               Expense Details
            </DialogTitle>
            <DialogDescription>
               Detailed view of the expense request and approval status.
            </DialogDescription>
         </DialogHeader>

         <div v-if="loading" class="flex justify-center py-12">
            <Loader2 class="w-8 h-8 animate-spin text-muted-foreground" />
         </div>

         <div v-else-if="expense" class="space-y-6">
            <div
               class="flex flex-col md:flex-row justify-between items-start md:items-center p-4 bg-muted/30 rounded-lg border gap-4">
               <div>
                  <h3 class="text-lg font-bold">{{ expense.ExpenseTitle }}</h3>
                  <p class="text-sm text-muted-foreground">{{ expense.ExpensePurpose || 'No purpose provided' }}</p>
               </div>
               <div class="text-right">
                  <div class="text-2xl font-black text-red-600">{{ auth.currencySymbol }}{{
                     expense.ExpenseAmount.toLocaleString() }}</div>
                  <Badge :variant="getStatusBadge(expense.ExpenseStatus).variant"
                     :class="getStatusBadge(expense.ExpenseStatus).class">
                     {{ expense.ExpenseStatus }}
                  </Badge>
               </div>
            </div>

            <div class="grid grid-cols-2 gap-y-4 text-sm">
               <div class="flex items-center gap-3">
                  <div class="p-2 bg-blue-50 rounded-full">
                     <Calendar class="w-4 h-4 text-blue-600" />
                  </div>
                  <div>
                     <p class="text-xs text-muted-foreground font-medium uppercase">Expense Date</p>
                     <p class="font-semibold">{{ dayjs(expense.ExpenseDate).format('MMM D, YYYY') }}</p>
                  </div>
               </div>

               <div class="flex items-center gap-3">
                  <div class="p-2 bg-purple-50 rounded-full">
                     <Tag class="w-4 h-4 text-purple-600" />
                  </div>
                  <div>
                     <p class="text-xs text-muted-foreground font-medium uppercase">Category</p>
                     <p class="font-semibold">{{ expense.CategoryName }}</p>
                  </div>
               </div>

               <div class="flex items-center gap-3">
                  <div class="p-2 bg-green-50 rounded-full">
                     <Building class="w-4 h-4 text-green-600" />
                  </div>
                  <div>
                     <p class="text-xs text-muted-foreground font-medium uppercase">Branch</p>
                     <p class="font-semibold">{{ expense.BranchName }}</p>
                  </div>
               </div>

               <div class="flex items-center gap-3">
                  <div class="p-2 bg-amber-50 rounded-full">
                     <User class="w-4 h-4 text-amber-600" />
                  </div>
                  <div>
                     <p class="text-xs text-muted-foreground font-medium uppercase">Requested By</p>
                     <p class="font-semibold">{{ expense.RequesterName }}</p>
                  </div>
               </div>
            </div>

            <!-- Approval/Rejection Details -->
            <div v-if="expense.ExpenseStatus !== 'Pending Approval'" class="p-4 rounded-lg bg-slate-50 border">
               <div class="flex items-center gap-2 mb-2">
                  <MessageSquare class="w-4 h-4 text-slate-500" />
                  <h4 class="text-sm font-bold">Reviewer's Remarks</h4>
               </div>
               <p class="text-sm text-slate-600 italic">
                  "{{ expense.ApprovalRemarks || 'No remarks provided.' }}"
               </p>
               <div class="mt-2 flex items-center justify-between">
                  <p class="text-[10px] text-slate-400 font-medium">Reviewed by: {{ expense.ApproverName || 'System' }}
                  </p>
                  <div v-if="expense.ProofFile" class="flex items-center gap-1 text-[10px] text-blue-600">
                     <AlertCircle class="w-3 h-3" />
                     <span>Proof of payment attached</span>
                  </div>
               </div>
            </div>

            <!-- Reviewer Actions -->
            <div v-if="expense.ExpenseStatus === 'Pending Approval'" class="space-y-4 pt-4 border-t">
               <div class="grid gap-2">
                  <Label for="remarks">Reviewer Remarks (Optional)</Label>
                  <Textarea id="remarks" v-model="remarks" placeholder="Provide context for approval or rejection..."
                     class="h-20" />
               </div>
               <div class="flex gap-2">
                  <Button @click="handleReview('approve')" class="flex-1 bg-green-600 hover:bg-green-700"
                     :disabled="reviewing">
                     <CheckCircle2 class="w-4 h-4 mr-2" /> Approve
                  </Button>
                  <Button @click="handleReview('reject')" variant="destructive" class="flex-1" :disabled="reviewing">
                     <XCircle class="w-4 h-4 mr-2" /> Decline
                  </Button>
               </div>
            </div>
         </div>

         <DialogFooter class="sm:justify-between items-center gap-4">
            <template v-if="expense && expense.ExpenseStatus === 'Pending Approval'">
               <Button variant="ghost" size="sm" class="text-red-600 hover:text-red-700 hover:bg-red-50"
                  @click="handleCancel">
                  Cancel Request
               </Button>
            </template>
            <div v-else></div>
            <Button variant="outline" @click="emit('update:open', false)">Close</Button>
         </DialogFooter>
      </DialogContent>
   </Dialog>
</template>
