/**
 * @file services/dashboard.service.ts
 * @description API calls for the Dashboard module.
 */

import http from './http'
import type { ApiResponse } from '@/types/api'
import type { DashboardOverview } from '@/types/operations'

export const dashboardService = {
  getOverview() {
    return http.get<ApiResponse<DashboardOverview>>('dashboard/overview')
  },
}
