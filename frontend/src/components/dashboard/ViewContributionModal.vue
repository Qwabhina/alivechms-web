<script setup lang="ts">
import { ref, watch } from 'vue'
import { useContributionsStore } from '@/stores/contributions'
import {
   Dialog,
   DialogContent,
   DialogFooter
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Coins, Pencil, Receipt, X } from 'lucide-vue-next'

const props = defineProps<{
   open: boolean
   contributionId: number | null
}>()

const emit = defineEmits<{
   (e: 'close'): void
   (e: 'edit', contribution: any): void
   (e: 'receipt', id: number): void
}>()

const store = useContributionsStore()
const loading = ref(false)
const contribution = ref<any>(null)
const error = ref('')

watch([() => props.open, () => props.contributionId], async ([isOpen, id]) => {
   if (isOpen && typeof id === 'number') {
      loading.value = true
      error.value = ''
      try {
         contribution.value = await store.getContribution(id)
      } catch (e: any) {
         error.value = e.message || 'Failed to load contribution'
         contribution.value = null
      } finally {
         loading.value = false
      }
   }
})

function formatCurrency(amount: number | string | null | undefined): string {
   if (amount === null || amount === undefined) return '-'
   const num = typeof amount === 'string' ? parseFloat(amount) : amount
   if (isNaN(num)) return '-'
   return `${store.currencySymbol} ${num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`
}

function formatDate(dateStr: string): string {
   if (!dateStr) return '-'
   return new Date(dateStr).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
   })
}
</script>

<template>
   <Dialog :open="open" @update:open="(val) => !val && emit('close')">
      <DialogContent class="max-w-md p-0 overflow-hidden">
         <!-- Close button -->
         <Button variant="ghost" size="icon" class="absolute top-3 right-3 z-10" @click="emit('close')">
            <X class="w-4 h-4" />
         </Button>

         <!-- Loading State -->
         <div v-if="loading" class="p-8 text-center">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary mx-auto"></div>
            <p class="text-muted-foreground mt-3">Loading...</p>
         </div>

         <!-- Error State -->
         <div v-else-if="error" class="p-8 text-center text-red-500">
            <div class="text-4xl mb-2">⚠️</div>
            <p>{{ error }}</p>
         </div>

         <!-- Content -->
         <template v-else-if="contribution">
            <!-- Header with amount -->
            <div class="text-center py-6 px-4" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
               <div class="rounded-full bg-white w-16 h-16 flex items-center justify-center mx-auto mb-3">
                  <Coins class="w-8 h-8 text-green-600" />
               </div>
               <h2 class="text-white text-2xl font-bold">{{ formatCurrency(contribution.ContributionAmount) }}</h2>
               <p class="text-white/70">{{ contribution.ContributionTypeName || 'Contribution' }}</p>
            </div>

            <!-- Details -->
            <div class="p-6 space-y-4">
               <div class="grid grid-cols-2 gap-4">
                  <div>
                     <p class="text-xs text-muted-foreground uppercase">Member</p>
                     <p class="font-semibold">{{ contribution.MbrFirstName }} {{ contribution.MbrFamilyName }}</p>
                  </div>
                  <div>
                     <p class="text-xs text-muted-foreground uppercase">Date</p>
                     <p class="font-semibold">{{ formatDate(contribution.ContributionDate) }}</p>
                  </div>
               </div>

               <div class="grid grid-cols-2 gap-4">
                  <div>
                     <p class="text-xs text-muted-foreground uppercase">Payment Method</p>
                     <p>{{ contribution.PaymentOptionName || '-' }}</p>
                  </div>
                  <div>
                     <p class="text-xs text-muted-foreground uppercase">Fiscal Year</p>
                     <p>{{ contribution.FiscalYearName || '-' }}</p>
                  </div>
               </div>

               <div v-if="contribution.Notes" class="pt-3 border-t">
                  <p class="text-xs text-muted-foreground uppercase">Description</p>
                  <p>{{ contribution.Notes }}</p>
               </div>
            </div>
         </template>

         <DialogFooter class="border-t p-4 gap-2">
            <Button variant="outline" @click="emit('close')">Close</Button>
            <Button variant="outline" class="text-green-600"
               @click="contribution && emit('receipt', contribution.ContributionID)">
               <Receipt class="w-4 h-4 mr-2" />
               Print Receipt
            </Button>
            <Button @click="contribution && emit('edit', contribution)">
               <Pencil class="w-4 h-4 mr-2" />
               Edit
            </Button>
         </DialogFooter>
      </DialogContent>
   </Dialog>
</template>
