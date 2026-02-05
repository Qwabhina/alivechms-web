import { defineStore } from 'pinia'
import api from '@/services/api'
import { ref } from 'vue'

export const useLookupsStore = defineStore('lookups', () => {
   const loaded = ref(false)
   const loading = ref(false)

   // Lookup Data State
   const maritalStatuses = ref<any[]>([])
   const educationLevels = ref<any[]>([])
   const membershipStatuses = ref<any[]>([])
   const phoneTypes = ref<any[]>([])
   const assetConditions = ref<any[]>([])
   const assetStatuses = ref<any[]>([])
   const communicationChannels = ref<any[]>([])
   const communicationStatuses = ref<any[]>([])
   const familyRelationships = ref<any[]>([])
   const documentCategories = ref<any[]>([])
   const paymentMethods = ref<any[]>([])
   const branches = ref<any[]>([])
   const contributionTypes = ref<any[]>([])
   const fiscalYears = ref<any[]>([])
   const expenseCategories = ref<any[]>([])
   const pledgeTypes = ref<any[]>([])

   async function fetchLookups(force = false) {
      if (loaded.value && !force) return

      loading.value = true
      try {
         const response = await api.get('lookups/all')
         const data = response.data.data || {}

         maritalStatuses.value = data.marital_statuses || []
         educationLevels.value = data.education_levels || []
         membershipStatuses.value = data.membership_statuses || []
         phoneTypes.value = data.phone_types || []
         assetConditions.value = data.asset_conditions || []
         assetStatuses.value = data.asset_statuses || []
         communicationChannels.value = data.communication_channels || []
         communicationStatuses.value = data.communication_statuses || []
         familyRelationships.value = data.family_relationships || []
         documentCategories.value = data.document_categories || []
         paymentMethods.value = data.payment_methods || []
         branches.value = data.branches || []
         contributionTypes.value = data.contribution_types || []
         fiscalYears.value = data.fiscal_years || []
         expenseCategories.value = data.expense_categories || []
         pledgeTypes.value = data.pledge_types || []

         loaded.value = true
      } catch (error) {
         console.error('Failed to fetch lookups:', error)
      } finally {
         loading.value = false
      }
   }

   return {
      loaded,
      loading,
      maritalStatuses,
      educationLevels,
      membershipStatuses,
      phoneTypes,
      assetConditions,
      assetStatuses,
      communicationChannels,
      communicationStatuses,
      familyRelationships,
      documentCategories,
      paymentMethods,
      branches,
      contributionTypes,
      fiscalYears,
      expenseCategories,
      pledgeTypes,
      fetchLookups
   }
})
