<script setup lang="ts">
import { ref, watch } from 'vue'
import { useContributionsStore } from '@/stores/contributions'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
  TableFooter
} from '@/components/ui/table'
import { Receipt, Printer } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  contributionId: number | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const store = useContributionsStore()
const loading = ref(false)
const receipt = ref<any>(null)
const error = ref('')

watch([() => props.open, () => props.contributionId], async ([isOpen, id]) => {
  if (isOpen && typeof id === 'number') {
    loading.value = true
    error.value = ''
    try {
      receipt.value = await store.getReceipt(id)
    } catch (e: any) {
      error.value = e.message || 'Failed to load receipt'
      receipt.value = null
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

function formatDateTime(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString()
}

function printReceipt() {
  const printArea = document.getElementById('receiptPrintArea')
  if (!printArea) return

  const printWindow = window.open('', '_blank')
  if (!printWindow) return

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Contribution Receipt</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        body { padding: 20px; font-family: system-ui, -apple-system, sans-serif; }
        @media print { body { padding: 0; } }
      </style>
    </head>
    <body>
      ${printArea.innerHTML}
      <script>window.onload = function() { window.print(); }<\/script>
    </body>
    </html>
  `)
  printWindow.document.close()
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => !val && emit('close')">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Receipt class="w-5 h-5" />
          Contribution Receipt
        </DialogTitle>
      </DialogHeader>

      <!-- Loading State -->
      <div v-if="loading" class="py-12 text-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary mx-auto"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="py-12 text-center text-red-500">
        <div class="text-4xl mb-2">⚠️</div>
        <p>{{ error }}</p>
      </div>

      <!-- Receipt Content -->
      <div v-else-if="receipt" id="receiptPrintArea" class="p-4 bg-white">
        <!-- Church Header -->
        <div class="text-center mb-6">
          <h4 class="text-lg font-semibold">{{ receipt.church?.name }}</h4>
          <p v-if="receipt.church?.address" class="text-sm text-muted-foreground">{{ receipt.church.address }}</p>
          <p v-if="receipt.church?.phone" class="text-sm text-muted-foreground">Tel: {{ receipt.church.phone }}</p>
        </div>

        <!-- Receipt Title -->
        <div class="text-center mb-6">
          <h5 class="text-base font-semibold uppercase">Contribution Receipt</h5>
          <p class="text-sm text-muted-foreground">
            <strong>Receipt #:</strong> {{ receipt.receipt_number }}
          </p>
        </div>

        <hr class="my-4" />

        <!-- Member and Date -->
        <div class="flex justify-between mb-4">
          <div>
            <p class="mb-1"><strong>Received From:</strong></p>
            <p>{{ receipt.member?.name }}</p>
          </div>
          <div class="text-right">
            <p class="mb-1"><strong>Date:</strong></p>
            <p>{{ formatDate(receipt.date) }}</p>
          </div>
        </div>

        <!-- Amount Table -->
        <Table class="mb-4 border">
          <TableHeader>
            <TableRow class="bg-muted/50">
              <TableHead>Description</TableHead>
              <TableHead class="text-right w-[150px]">Amount</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow>
              <TableCell>
                {{ receipt.type }}
                <br v-if="receipt.description" />
                <small v-if="receipt.description" class="text-muted-foreground">{{ receipt.description }}</small>
              </TableCell>
              <TableCell class="text-right font-bold">{{ formatCurrency(receipt.amount) }}</TableCell>
            </TableRow>
          </TableBody>
          <TableFooter>
            <TableRow class="bg-muted/50">
              <TableCell class="font-semibold">Total</TableCell>
              <TableCell class="text-right font-semibold">{{ formatCurrency(receipt.amount) }}</TableCell>
            </TableRow>
          </TableFooter>
        </Table>

        <!-- Payment Details -->
        <div class="mb-6">
          <p class="text-sm"><strong>Payment Method:</strong> {{ receipt.payment_method }}</p>
          <p class="text-sm"><strong>Fiscal Year:</strong> {{ receipt.fiscal_year || '-' }}</p>
        </div>

        <hr class="my-4" />

        <!-- Signatures -->
        <div class="flex justify-between mt-6">
          <p class="text-sm text-muted-foreground">Received By: _____________________</p>
          <p class="text-sm text-muted-foreground">Signature: _____________________</p>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
          <p class="text-sm text-muted-foreground">Thank you for your generous contribution!</p>
          <p class="text-xs text-muted-foreground">Generated: {{ formatDateTime(receipt.generated_at) }}</p>
        </div>
      </div>

      <DialogFooter class="gap-2">
        <Button variant="outline" @click="emit('close')">Close</Button>
        <Button @click="printReceipt">
          <Printer class="w-4 h-4 mr-2" />
          Print
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
