/**
 * @file settings.ts
 * @description Types for Settings, Lookups, and Branches.
 */

/* ---------- Public Settings ---------- */

export interface PublicSettings {
  church_name: string | null
  church_motto: string | null
  church_website: string | null
  church_logo: string | null
  currency_symbol: string
  currency_code: string
  date_format: string
  time_format: string
  timezone: string
  language: string
  items_per_page: number
}

/* ---------- All Settings (Admin) ---------- */

export interface Setting {
  SettingID: number
  SettingKey: string
  SettingValue: string | null
  SettingType: string
  Category: string
  Description: string | null
}

export interface SettingsUpdatePayload {
  settings: Array<{ key: string; value: string }>
}

/* ---------- Branch ---------- */

export interface Branch {
  BranchID: number
  BranchName: string
  BranchCode: string | null
  BranchAddress: string | null
  IsActive: number
  CreatedAt: string
}
