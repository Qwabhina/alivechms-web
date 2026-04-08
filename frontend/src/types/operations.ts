/**
 * @file operations.ts
 * @description Types for Events, Groups, Volunteers, Documents, Dashboard.
 */

/* ---------- Event ---------- */

export interface ChurchEvent {
  EventID: number
  EventName: string
  EventDescription: string | null
  EventDate: string
  EventTime: string | null
  EventEndDate: string | null
  Location: string | null
  EventType: string | null
  IsRecurring: number
  Status: 'Upcoming' | 'Ongoing' | 'Completed' | 'Cancelled'
  CreatedBy: number | null
  CreatedAt: string
}

/* ---------- Group ---------- */

export interface Group {
  GroupID: number
  GroupName: string
  GroupDescription: string | null
  GroupTypeID: number
  GroupTypeName?: string
  LeaderID: number | null
  LeaderName?: string
  MeetingDay: string | null
  MeetingTime: string | null
  IsActive: number
  MemberCount?: number
}

export interface GroupType {
  GroupTypeID: number
  TypeName: string
  Description: string | null
  IsActive: number
}

/* ---------- Volunteer ---------- */

export interface Volunteer {
  VolunteerID: number
  MbrID: number
  MemberName?: string
  Area: string
  Role: string | null
  StartDate: string
  EndDate: string | null
  Status: 'Active' | 'Inactive'
  Notes: string | null
}

/* ---------- Document ---------- */

export interface Document {
  DocumentID: number
  DocumentTitle: string
  DocumentDescription: string | null
  FilePath: string
  FileType: string
  FileSize: number
  UploadedBy: number
  UploaderName?: string
  UploadedAt: string
  Category: string | null
}

/* ---------- Dashboard ---------- */

export interface DashboardOverview {
  members: {
    total: number
    active: number
    new_this_month: number
  }
  finance: {
    total_income: number
    total_expenses: number
    net_balance: number
  }
  events: {
    upcoming: number
    total_this_month: number
  }
  recent_members: Array<{
    MbrID: number
    MbrFirstName: string
    MbrFamilyName: string
    MbrRegistrationDate: string
    MbrProfilePicture: string | null
  }>
}
