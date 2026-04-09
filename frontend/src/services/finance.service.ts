/**
 * @file services/finance.service.ts
 * @description API calls for the Financial module.
 */

import http from './http'
import type { ApiResponse, PaginatedResponse } from '@/types/api'
import type {
  Contribution,
  ContributionCreate,
  ContributionUpdate,
  ContributionFilters,
  ContributionStats,
  ContributionType,
  PaymentMethod,
  FiscalYear,
} from '@/types/finance'

// ─── Finance Lookup Data ──────────────────────────────────────────────────────

/**
 * Shape returned by GET lookups/all for the finance module.
 * Contains all dropdown data needed by contribution forms.
 */
export interface FinanceLookupData {
  contribution_types: Array<{ id: number; name: string; description?: string | null }>
  payment_methods: Array<{ id: number; name: string }>
  fiscal_years: Array<{
    id: number
    name: string
    StartDate: string
    EndDate: string
    Status: string
  }>
  expense_categories: Array<{ id: number; name: string }>
  pledge_types: Array<{ id: number; name: string }>
  branches: Array<{ id: number; name: string; code: string }>
  marital_statuses: Array<{ id: number; name: string }>
  membership_statuses: Array<{ id: number; name: string }>
}

// ─── Contribution Service ─────────────────────────────────────────────────────

export const contributionService = {
  /**
   * Paginated list of contributions with optional filters and sorting.
   * Maps to GET contribution/all
   */
  getAll(page = 1, limit = 25, filters?: ContributionFilters) {
    return http.get<PaginatedResponse<Contribution>>('contribution/all', {
      params: { page, limit, ...filters },
    })
  },

  /**
   * Fetch a single contribution by ID.
   * Maps to GET contribution/view/:id
   */
  getById(id: number) {
    return http.get<ApiResponse<Contribution>>(`contribution/view/${id}`)
  },

  /**
   * Record a new contribution.
   * Maps to POST contribution/create
   */
  create(data: ContributionCreate) {
    return http.post<ApiResponse<{ id: number; contribution_id: number }>>(
      'contribution/create',
      data,
    )
  },

  /**
   * Update an existing contribution.
   * Maps to PUT contribution/update/:id
   */
  update(id: number, data: ContributionUpdate) {
    return http.put<ApiResponse<{ status: string }>>(`contribution/update/${id}`, data)
  },

  /**
   * Soft-delete a contribution.
   * Maps to DELETE contribution/delete/:id
   */
  delete(id: number) {
    return http.delete<ApiResponse<{ status: string }>>(`contribution/delete/${id}`)
  },

  /**
   * Aggregate contribution statistics (totals, averages, breakdown by type/month).
   * Optionally scoped to a fiscal year.
   * Maps to GET contribution/stats
   */
  getStats(fiscalYearId?: number) {
    return http.get<ApiResponse<ContributionStats>>('contribution/stats', {
      params: fiscalYearId !== undefined ? { fiscal_year_id: fiscalYearId } : undefined,
    })
  },

  /**
   * All active contribution types for dropdowns.
   * Maps to GET contribution/types
   */
  getTypes() {
    return http.get<ApiResponse<ContributionType[]>>('contribution/types')
  },

  /**
   * All active payment methods for dropdowns.
   * Maps to GET contribution/payment-methods
   */
  getPaymentMethods() {
    return http.get<ApiResponse<PaymentMethod[]>>('contribution/payment-methods')
  },
}

// ─── Fiscal Year Service ──────────────────────────────────────────────────────

export const fiscalYearService = {
  /**
   * Paginated list of fiscal years.
   * Maps to GET fiscalyear/all
   */
  getAll(page = 1, limit = 25) {
    return http.get<PaginatedResponse<FiscalYear>>('fiscalyear/all', {
      params: { page, limit },
    })
  },

  /**
   * Fetch a single fiscal year by ID.
   * Maps to GET fiscalyear/view/:id
   */
  getById(id: number) {
    return http.get<ApiResponse<FiscalYear>>(`fiscalyear/view/${id}`)
  },

  /**
   * Create a new fiscal year.
   * Maps to POST fiscalyear/create
   */
  create(data: { year_name: string; start_date: string; end_date: string }) {
    return http.post<ApiResponse<FiscalYear>>('fiscalyear/create', data)
  },

  /**
   * Close an active fiscal year (prevents new contributions from being logged).
   * Maps to POST fiscalyear/close/:id
   */
  close(id: number) {
    return http.post<ApiResponse<null>>(`fiscalyear/close/${id}`)
  },
}

// ─── Lookup Service ───────────────────────────────────────────────────────────

export const lookupService = {
  /**
   * Fetch all lookup/dropdown data in a single request.
   * Returns contribution types, payment methods, fiscal years, branches, etc.
   * Maps to GET lookups/all
   */
  getAll() {
    return http.get<ApiResponse<FinanceLookupData & Record<string, unknown>>>('lookups/all')
  },
}
