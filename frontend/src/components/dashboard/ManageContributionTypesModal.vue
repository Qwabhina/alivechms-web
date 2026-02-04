<script setup lang="ts">
import { ref, watch } from 'vue'
import { useContributionsStore } from '@/stores/contributions'
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
import {
   Table,
   TableBody,
   TableCell,
   TableHead,
   TableHeader,
   TableRow
} from '@/components/ui/table'
import { Tags, Plus, Pencil, Trash2, Loader2 } from 'lucide-vue-next'

const props = defineProps<{
   open: boolean
}>()

const emit = defineEmits<{
   (e: 'close'): void
}>()

const store = useContributionsStore()
const { toast } = useToast()

const loading = ref(false)
const newTypeName = ref('')
const newTypeDesc = ref('')

// Edit mode state
const editingType = ref<{ id: number; name: string; desc: string } | null>(null)

watch(() => props.open, async (open) => {
   if (open) {
      loading.value = true
      try {
         await store.fetchContributionTypes()
      } finally {
         loading.value = false
      }
   }
})

async function addType() {
   const name = newTypeName.value.trim()
   if (!name) {
      toast({ description: 'Please enter a type name', variant: 'default' })
      return
   }

   loading.value = true
   try {
      await store.createContributionType(name, newTypeDesc.value.trim() || undefined)
      toast({ title: 'Success', description: 'Contribution type added' })
      newTypeName.value = ''
      newTypeDesc.value = ''
   } catch (error: any) {
      toast({ title: 'Error', description: error.message || 'Failed to add type', variant: 'destructive' })
   } finally {
      loading.value = false
   }
}

function startEdit(type: any) {
   editingType.value = {
      id: type.ContributionTypeID,
      name: type.ContributionTypeName,
      desc: type.ContributionTypeDescription || ''
   }
}

function cancelEdit() {
   editingType.value = null
}

async function saveEdit() {
   if (!editingType.value) return

   const name = editingType.value.name.trim()
   if (!name) {
      toast({ description: 'Please enter a type name', variant: 'default' })
      return
   }

   loading.value = true
   try {
      await store.updateContributionType(
         editingType.value.id,
         name,
         editingType.value.desc.trim() || undefined
      )
      toast({ title: 'Success', description: 'Contribution type updated' })
      editingType.value = null
   } catch (error: any) {
      toast({ title: 'Error', description: error.message || 'Failed to update type', variant: 'destructive' })
   } finally {
      loading.value = false
   }
}

async function deleteType(id: number, name: string) {
   if (!confirm(`Delete contribution type "${name}"?\n\nThis cannot be undone. Types in use cannot be deleted.`)) {
      return
   }

   loading.value = true
   try {
      await store.deleteContributionType(id)
      toast({ title: 'Success', description: 'Contribution type deleted' })
   } catch (error: any) {
      toast({ title: 'Error', description: error.message || 'Failed to delete type', variant: 'destructive' })
   } finally {
      loading.value = false
   }
}
</script>

<template>
   <Dialog :open="open" @update:open="(val) => !val && emit('close')">
      <DialogContent class="max-w-2xl">
         <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
               <Tags class="w-5 h-5" />
               Manage Contribution Types
            </DialogTitle>
         </DialogHeader>

         <!-- Add New Type Row -->
         <div class="flex gap-2 mb-4">
            <Input v-model="newTypeName" placeholder="Type name (e.g., Tithe, Offering)" class="flex-1" />
            <Input v-model="newTypeDesc" placeholder="Description (optional)" class="flex-1" />
            <Button @click="addType" :disabled="loading">
               <Plus class="w-4 h-4 mr-1" />
               Add
            </Button>
         </div>

         <!-- Types Table -->
         <div class="border rounded-md max-h-[400px] overflow-y-auto">
            <Table>
               <TableHeader>
                  <TableRow class="bg-muted/50">
                     <TableHead>Name</TableHead>
                     <TableHead>Description</TableHead>
                     <TableHead class="w-[100px]">Actions</TableHead>
                  </TableRow>
               </TableHeader>
               <TableBody>
                  <!-- Loading State -->
                  <TableRow v-if="loading && store.contributionTypes.length === 0">
                     <TableCell colspan="3" class="text-center py-6">
                        <Loader2 class="w-6 h-6 animate-spin mx-auto" />
                        <p class="text-sm text-muted-foreground mt-2">Loading...</p>
                     </TableCell>
                  </TableRow>

                  <!-- Empty State -->
                  <TableRow v-else-if="store.contributionTypes.length === 0">
                     <TableCell colspan="3" class="text-center py-6 text-muted-foreground">
                        No contribution types found
                     </TableCell>
                  </TableRow>

                  <!-- Type Rows -->
                  <template v-else>
                     <TableRow v-for="type in store.contributionTypes" :key="type.ContributionTypeID">
                        <!-- Edit Mode -->
                        <template v-if="editingType?.id === type.ContributionTypeID">
                           <TableCell>
                              <Input v-model="editingType.name" class="h-8" />
                           </TableCell>
                           <TableCell>
                              <Input v-model="editingType.desc" class="h-8" />
                           </TableCell>
                           <TableCell>
                              <div class="flex gap-1">
                                 <Button size="sm" variant="ghost" @click="saveEdit" :disabled="loading">
                                    Save
                                 </Button>
                                 <Button size="sm" variant="ghost" @click="cancelEdit">
                                    Cancel
                                 </Button>
                              </div>
                           </TableCell>
                        </template>

                        <!-- View Mode -->
                        <template v-else>
                           <TableCell class="font-medium">{{ type.ContributionTypeName }}</TableCell>
                           <TableCell class="text-muted-foreground">{{ type.ContributionTypeDescription || '-' }}
                           </TableCell>
                           <TableCell>
                              <div class="flex gap-1">
                                 <Button size="icon" variant="ghost" class="h-8 w-8" @click="startEdit(type)"
                                    title="Edit">
                                    <Pencil class="w-4 h-4 text-amber-600" />
                                 </Button>
                                 <Button size="icon" variant="ghost" class="h-8 w-8"
                                    @click="deleteType(type.ContributionTypeID, type.ContributionTypeName)"
                                    title="Delete">
                                    <Trash2 class="w-4 h-4 text-red-600" />
                                 </Button>
                              </div>
                           </TableCell>
                        </template>
                     </TableRow>
                  </template>
               </TableBody>
            </Table>
         </div>

         <DialogFooter>
            <Button variant="outline" @click="emit('close')">Close</Button>
         </DialogFooter>
      </DialogContent>
   </Dialog>
</template>
