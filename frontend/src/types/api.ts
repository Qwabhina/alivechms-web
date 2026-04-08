/**
 * @file api.ts
 * @description Core API response types matching the backend's ResponseHelper output.
 */

/* ---------- Standard Envelope ---------- */

export interface ApiResponse<T = unknown> {
  status: 'success'
  message: string
  data?: T
  timestamp: string
}

export interface ApiError {
  status: 'error'
  message: string
  code: number
  errors?: Record<string, string[]>
  error_code?: string
  timestamp: string
}

export interface PaginatedResponse<T = unknown> {
  status: 'success'
  message: string
  data: T[]
  pagination: Pagination
  timestamp: string
}

export interface Pagination {
  current_page: number
  per_page: number
  total: number
  total_pages: number
  has_next: boolean
  has_prev: boolean
}

/* ---------- Common Query Helpers ---------- */

export interface PaginationParams {
  page?: number
  limit?: number
}

export interface SortParams {
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

export type FilterParams = PaginationParams & SortParams & Record<string, string | number | undefined>
