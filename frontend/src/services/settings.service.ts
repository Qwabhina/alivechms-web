/**
 * @file services/settings.service.ts
 * @description API calls for Settings and Public data.
 */

import http from './http'
import type { ApiResponse } from '@/types/api'
import type { PublicSettings, Setting, SettingsUpdatePayload } from '@/types/settings'
import type { Permission } from '@/types/auth'

export const settingsService = {
  /** Public settings (no auth required) */
  getPublicSettings() {
    return http.get<ApiResponse<PublicSettings>>('public/settings')
  },

  /** All settings (admin) */
  getAll() {
    return http.get<ApiResponse<Setting[]>>('settings/all')
  },

  /** Settings by category */
  getByCategory() {
    return http.get<ApiResponse<{ data: Record<string, Setting[]> }>>('settings/category')
  },

  /** Update settings in bulk */
  update(payload: SettingsUpdatePayload) {
    return http.post<ApiResponse<null>>('settings/update', payload)
  },

  /** Upload church logo */
  uploadLogo(file: File) {
    const fd = new FormData()
    fd.append('logo', file)
    return http.post<ApiResponse<{ path: string; url: string }>>('settings/upload-logo', fd)
  },

  /** Initialize default settings */
  initialize() {
    return http.post<ApiResponse<null>>('settings/initialize')
  },

  /** Get public permissions list */
  getPermissions() {
    return http.get<ApiResponse<Permission[]>>('public/permissions')
  },

  /** Get system info */
  getSystemInfo() {
    return http.get<ApiResponse<{ name: string; version: string; timezone: string; maintenance_mode: boolean }>>('public/info')
  },
}
