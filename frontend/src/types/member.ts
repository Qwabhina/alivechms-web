/**
 * @file member.ts
 * @description Types for the People module (Members, Families, Visitors, Volunteers).
 */

/* ---------- Member ---------- */

export interface Member {
  MbrID: number
  MbrUniqueID?: string
  MbrFirstName: string
  MbrFamilyName: string
  MbrOtherNames: string | null
  MbrGender: 'Male' | 'Female' | 'Other'
  MbrEmailAddress: string
  MbrResidentialAddress: string | null
  MbrDateOfBirth: string | null
  MbrOccupation: string | null
  MbrRegistrationDate: string
  MbrProfilePicture: string | null
  MbrMaritalStatusID: number | null
  MbrEducationLevelID: number | null
  MbrMembershipStatusID: number | null
  BranchID: number
  FamilyID: number | null
  Deleted: number

  /** Joined fields */
  MembershipStatusName?: string
  MaritalStatusName?: string
  EducationLevelName?: string
  BranchName?: string
  FamilyName?: string

  /** Nested data (from Member::get) */
  phones?: MemberPhone[]
  PhoneNumbers?: string[]
  PrimaryPhone?: string | null
  milestones?: MemberMilestone[]

  /** Auth info (joined on some queries) */
  Username?: string
  IsActive?: number
  HasLogin?: boolean
}

/* ---------- Create / Update Payloads ---------- */

export interface MemberCreate {
  first_name: string
  family_name: string
  email_address: string
  other_names?: string
  gender?: 'Male' | 'Female' | 'Other'
  date_of_birth?: string
  address?: string
  phone_numbers?: PhoneInput[]
  occupation?: string
  marital_status_id?: number
  education_level_id?: number
  membership_status_id?: number
  family_id?: number
  branch_id?: number
  username?: string
  password?: string
  profile_picture?: string | null
  unique_id?: string
}

export type MemberUpdate = Partial<MemberCreate> & {
  member_role?: number
  enable_login?: boolean
  remove_profile_picture?: boolean
}

/* ---------- Phone ---------- */

export interface MemberPhone {
  PhoneID: number
  MbrID: number
  PhoneNumber: string
  PhoneTypeID: number
  PhoneTypeName?: string
  IsPrimary: number
}

export interface PhoneInput {
  number: string
  type_id?: number
  is_primary?: boolean
}

/* ---------- Milestone ---------- */

export interface MemberMilestone {
  MilestoneID: number
  MbrID: number
  MilestoneTypeID: number
  MilestoneTypeName?: string
  MilestoneDate: string
  Notes: string | null
}

/* ---------- Statistics ---------- */

export interface MemberStats {
  total_members: number
  active_members: number
  inactive_members: number
  new_this_month: number
  gender_distribution: Array<{ gender: string; count: number }>
  age_distribution: Array<{ group: string; count: number }>
}

/* ---------- Lookup Data ---------- */

export interface MemberLookupData {
  marital_statuses: LookupItem[]
  education_levels: LookupItem[]
  membership_statuses: LookupItem[]
  phone_types: LookupItem[]
  branches: BranchItem[]
}

export interface LookupItem {
  id: number
  name: string
  DisplayOrder?: number
}

export interface LookupCategory {
  id: string
  name: string
  items?: LookupItem[]
}

export interface BranchItem {
  id: number
  name: string
  code: string
}

/* ---------- Filters ---------- */

export interface MemberFilters {
  search?: string
  status?: string
  family_id?: number
  date_from?: string
  date_to?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

/* ---------- Family ---------- */

export interface Family {
  FamilyID: number
  FamilyName: string
  FamilyHead?: number | null
  FamilyHeadID?: number | null
  FamilyHeadName?: string
  Address: string | null
  City?: string
  Region?: string
  Country?: string
  HomePhone?: string
  CreatedAt: string
  UpdatedAt?: string
  MemberCount?: number
}
