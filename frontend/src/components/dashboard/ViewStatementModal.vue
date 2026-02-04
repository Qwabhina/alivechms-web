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
import { Card, CardContent } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
  TableFooter
} from '@/components/ui/table'
import { FileText, Printer } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  memberId: number | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const store = useContributionsStore()
const loading = ref(false)
const statement = ref<any>(null)
const error = ref('')

watch([() => props.open, () => props.memberId], async ([isOpen, id]) => {
  if (isOpen && typeof id === 'number') {
    loading.value = true
    error.value = ''
    try {
      statement.value = await store.getMemberStatement(id)
    } catch (e: any) {
      error.value = e.message || 'Failed to load statement'
      statement.value = null
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
  return new Date(dateStr).toLocaleDateString()
}

function formatDateTime(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString()
}

function printStatement() {
  const printArea = document.getElementById('statementPrintArea')
  if (!printArea) return

  const printWindow = window.open('', '_blank')
  if (!printWindow) return

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Contribution Statement</title>
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
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <FileText class="w-5 h-5" />
          Contribution Statement
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

      <!-- Statement Content -->
      <div v-else-if="statement" id="statementPrintArea" class="p-4 bg-white">
        <!-- Header Row -->
        <div class="flex justify-between mb-6">
          <div>
            <h4 class="text-lg font-semibold">{{ statement.church?.name }}</h4>
            <p v-if="statement.church?.address" class="text-sm text-muted-foreground">{{ statement.church.address }}</p>
            <p v-if="statement.church?.phone" class="text-sm text-muted-foreground">Tel: {{ statement.church.phone }}
            </p>
          </div>
          <div class="text-right">
            <h5 class="text-base font-semibold uppercase">Contribution Statement</h5>
            <p class="text-sm text-muted-foreground">
              <strong>Statement #:</strong> {{ statement.statement_number }}
            </p>
            <p class="text-sm text-muted-foreground">
              <strong>Fiscal Year:</strong> {{ statement.fiscal_year?.name || 'All Time' }}
            </p>
          </div>
        </div>

        <!-- Member Summary Card -->
        <Card class="mb-6">
          <CardContent class="py-3">
            <div class="flex justify-between items-center">
              <div>
                <p class="font-semibold">{{ statement.member?.name }}</p>
                <p v-if="statement.member?.email" class="text-sm text-muted-foreground">{{ statement.member.email }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm">
                  <strong>Total Contributions:</strong>
                  <span class="text-green-600 text-lg ml-2">{{ formatCurrency(statement.grand_total) }}</span>
                </p>
                <p class="text-sm text-muted-foreground">{{ statement.contribution_count }} contributions</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Summary by Type -->
        <h6 class="font-semibold mb-3">Summary by Type</h6>
        <Table class="mb-6 border">
          <TableHeader>
            <TableRow class="bg-muted/50">
              <TableHead>Contribution Type</TableHead>
              <TableHead class="text-center">Count</TableHead>
              <TableHead class="text-right">Total</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="t in statement.totals_by_type" :key="t.ContributionTypeName">
              <TableCell>{{ t.ContributionTypeName }}</TableCell>
              <TableCell class="text-center">{{ t.count }}</TableCell>
              <TableCell class="text-right font-semibold">{{ formatCurrency(t.total) }}</TableCell>
            </TableRow>
          </TableBody>
          <TableFooter>
            <TableRow class="bg-muted/50">
              <TableCell class="font-semibold">Grand Total</TableCell>
              <TableCell class="text-center font-semibold">{{ statement.contribution_count }}</TableCell>
              <TableCell class="text-right font-semibold">{{ formatCurrency(statement.grand_total) }}</TableCell>
            </TableRow>
          </TableFooter>
        </Table>

        <!-- Contribution Details -->
        <h6 class="font-semibold mb-3">Contribution Details</h6>
        <Table class="border">
          <TableHeader>
            <TableRow class="bg-muted/50">
              <TableHead>Date</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Payment Method</TableHead>
              <TableHead class="text-right">Amount</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="c in statement.contributions" :key="c.ContributionID">
              <TableCell>{{ formatDate(c.ContributionDate) }}</TableCell>
              <TableCell>{{ c.ContributionTypeName }}</TableCell>
              <TableCell>{{ c.PaymentOptionName }}</TableCell>
              <TableCell class="text-right">{{ formatCurrency(c.ContributionAmount) }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Footer -->
        <div class="text-center mt-6">
          <p class="text-xs text-muted-foreground">Generated: {{ formatDateTime(statement.generated_at) }}</p>
        </div>
      </div>

      <DialogFooter class="gap-2">
        <Button variant="outline" @click="emit('close')">Close</Button>
        <Button @click="printStatement">
          <Printer class="w-4 h-4 mr-2" />
          Print Statement
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
