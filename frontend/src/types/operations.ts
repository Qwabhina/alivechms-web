/**
 * @file operations.ts
 * @description Types for Events, Groups, Volunteers, Documents, Dashboard.
 */

import type { Role, Permission } from './role'
import type { Branch } from './settings'

export interface ChurchEvent {
  EventID: number
  EventName?: string
  EventTitle?: string
  EventDescription?: string | null
  EventDate?: string
  StartTime?: string | null
  EndTime?: string | null
  EventTime?: string | null
  Location?: string | null
  EventType?: string | null
  MaxAttendees?: number | null
  BranchID?: number
  GroupID?: number
  CreatedAt?: string
  UpdatedAt?: string
}

export interface Group {
  GroupID: number
  GroupName?: string
  GroupDescription?: string | null
  MeetingDay?: string | null
  MeetingTime?: string | null
  MeetingLocation?: string | null
  TypeName?: string | null
  LeaderName?: string | null
  CoLeaderName?: string | null
  TotalMembers?: number
  Description?: string | null
}

export interface BranchDetail extends Branch {
  ManagerName?: string
  ContactEmail?: string
}

export interface BranchCreateInput {
  branch_name: string
  branch_code?: string
  branch_address?: string
}

export interface BranchUpdateInput extends Partial<BranchCreateInput> {
  branch_id: number
}

export interface BranchListFilters {
  search?: string
  is_active?: number
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

/* ---------- Extended Event Types ---------- */

export interface EventAttendance {
  AttendanceID: number
  EventID: number
  MemberID: number
  MemberName: string
  Attended: boolean
  Notes?: string
  CheckedInAt?: string
  CheckedInBy?: number
}

export interface EventDetail extends ChurchEvent {
  attendance: EventAttendance[]
  totalAttendees: number
  expectedAttendees: number
  BranchName?: string
  GroupName?: string
}

export interface EventCreateInput {
  event_title: string
  event_description?: string | null
  event_date: string
  start_time?: string | null
  end_time?: string | null
  location?: string | null
  event_type?: string | null
  branch_id?: number
  group_id?: number
  max_attendees?: number
  event_status?: EventStatus
  recurring_type?: RecurringType
  recurring_end_date?: string
  template_id?: number
}

export interface EventUpdateInput extends Partial<EventCreateInput> {
  event_id: number
}

export interface EventListFilters {
  branch_id?: number
  group_id?: number
  start_date?: string
  end_date?: string
  search?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
  event_status?: EventStatus
  event_type?: string
  upcoming_only?: boolean
  past_only?: boolean
}

export interface BulkAttendanceInput {
  event_id: number
  attendees: Array<{
    member_id: number
    attended: boolean
    notes?: string
  }>
}

/* ---------- Enhanced Event Types ---------- */

export type EventStatus = 'draft' | 'published' | 'cancelled' | 'completed'
export type RecurringType = 'none' | 'daily' | 'weekly' | 'biweekly' | 'monthly' | 'yearly'
export type EventViewMode = 'list' | 'calendar' | 'grid'

export interface EventRegistration {
  RegistrationID: number
  EventID: number
  MemberID: number
  MemberName: string
  Email?: string
  Phone?: string
  RegisteredAt: string
  Status: 'registered' | 'cancelled' | 'waitlisted'
  CheckedInAt?: string
}

export interface EventVolunteer {
  VolunteerID: number
  EventID: number
  MemberID: number
  MemberName: string
  Role: string
  AssignedAt: string
  Status: 'assigned' | 'confirmed' | 'cancelled'
}

export interface EventWaitlist {
  WaitlistID: number
  EventID: number
  MemberID: number
  MemberName: string
  AddedAt: string
  Position: number
}

export interface EventTemplate {
  TemplateID: number
  TemplateName: string
  EventType: string
  DefaultDuration: number
  DefaultLocation?: string
  DefaultMaxAttendees?: number
  Description?: string
  IsActive: boolean
}

export interface EventConflict {
  EventID: number
  EventTitle: string
  EventDate: string
  StartTime: string
  EndTime: string
  Location: string
  ConflictType: 'time' | 'location'
}

export interface EventCheckIn {
  CheckInID: number
  EventID: number
  MemberID: number
  MemberName: string
  CheckInTime: string
  CheckedInBy: number
  Status: 'checked-in' | 'checked-out'
}

export interface EventResource {
  ResourceID: number
  EventID: number
  ResourceName: string
  ResourceType: 'document' | 'link' | 'image'
  FilePath?: string
  Url?: string
  Description?: string
  UploadedAt: string
}

export interface EventListFilters {
  branch_id?: number
  group_id?: number
  start_date?: string
  end_date?: string
  search?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
  event_status?: EventStatus
  event_type?: string
  upcoming_only?: boolean
  past_only?: boolean
}

export interface EventCreateInput {
  event_title: string
  event_description?: string | null
  event_date: string
  start_time?: string | null
  end_time?: string | null
  location?: string | null
  event_type?: string | null
  branch_id?: number
  group_id?: number
  max_attendees?: number
  event_status?: EventStatus
  recurring_type?: RecurringType
  recurring_end_date?: string
  template_id?: number
}

/* ---------- Extended Group Types ---------- */

export interface GroupMember {
  MemberID: number
  FullName: string
  JoinedDate: string
  Role?: string
}

export interface GroupDetail extends Group {
  members: GroupMember[]
  CoLeaderName?: string
  BranchName?: string
  CreatedAt?: string
  UpdatedAt?: string
}

export interface GroupCreateInput {
  group_name: string
  group_type_id?: number
  leader_id?: number
  co_leader_id?: number
  description?: string | null
  meeting_day?: string | null
  meeting_time?: string | null
  meeting_location?: string | null
  branch_id?: number
  max_members?: number
}

export interface GroupUpdateInput extends Partial<GroupCreateInput> {
  group_id: number
}

export interface GroupListFilters {
  group_type_id?: number
  branch_id?: number
  search?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

export interface GroupTypeCreateInput {
  type_name: string
  description?: string
}

export interface GroupTypeUpdateInput extends Partial<GroupTypeCreateInput> {
  group_type_id: number
}

/* ---------- Extended Volunteer Types ---------- */

export type VolunteerStatus = 'active' | 'inactive' | 'suspended'
export type AvailabilityDay = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday'

export interface VolunteerAvailability {
  AvailabilityID: number
  VolunteerID: number
  DayOfWeek: AvailabilityDay
  StartTime: string
  EndTime: string
}

export interface VolunteerAssignment {
  AssignmentID: number
  VolunteerID: number
  EventID?: number
  EventName?: string
  GroupID?: number
  GroupName?: string
  Role: string
  AssignedAt: string
  AssignedBy?: number
  AssignedByName?: string
}

export interface VolunteerDetail extends Volunteer {
  availability: VolunteerAvailability[]
  assignments: VolunteerAssignment[]
  totalAssignments: number
  Skills?: string[]
  Interests?: string[]
}

export interface VolunteerCreateInput {
  member_id: number
  skills?: string[]
  interests?: string[]
  notes?: string
  availability?: Array<{
    day_of_week: AvailabilityDay
    start_time: string
    end_time: string
  }>
}

export interface VolunteerUpdateInput extends Partial<VolunteerCreateInput> {
  volunteer_id: number
  status?: VolunteerStatus
}

export interface VolunteerListFilters {
  member_id?: number
  status?: VolunteerStatus
  skill?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

/* ---------- Extended Visitor Types ---------- */

export type VisitorStatus = 'new' | 'contacted' | 'visiting' | 'member' | 'inactive'
export type FollowUpStatus = 'pending' | 'completed' | 'overdue'

export interface Visitor {
  VisitorID: number
  FirstName: string
  LastName: string
  FullName?: string
  Email?: string
  Phone?: string
  Address?: string
  City?: string
  VisitDate: string
  VisitedBranchID?: number
  VisitedBranchName?: string
  ReferredBy?: string
  PurposeOfVisit?: string
  Status: VisitorStatus
  Notes?: string
  CreatedAt?: string
  UpdatedAt?: string
}

export interface VisitorFollowUp {
  FollowUpID: number
  VisitorID: number
  AssignedTo: number
  AssignedToName?: string
  FollowUpDate: string
  FollowUpType: 'call' | 'email' | 'visit' | 'sms'
  Notes?: string
  Status: FollowUpStatus
  CompletedAt?: string
  CompletedBy?: number
}

export interface VisitorDetail extends Visitor {
  followUps: VisitorFollowUp[]
  totalFollowUps: number
  pendingFollowUps: number
}

export interface VisitorCreateInput {
  first_name: string
  last_name: string
  email?: string
  phone?: string
  address?: string
  city?: string
  visit_date?: string
  visited_branch_id?: number
  referred_by?: string
  purpose_of_visit?: string
  notes?: string
}

export interface VisitorUpdateInput extends Partial<VisitorCreateInput> {
  visitor_id: number
  status?: VisitorStatus
}

export interface FollowUpCreateInput {
  visitor_id: number
  assigned_to: number
  follow_up_date: string
  follow_up_type: 'call' | 'email' | 'visit' | 'sms'
  notes?: string
}

export interface FollowUpUpdateInput {
  follow_up_id: number
  status: FollowUpStatus
  notes?: string
  completed_at?: string
}

export interface VisitorListFilters {
  status?: VisitorStatus
  branch_id?: number
  start_date?: string
  end_date?: string
  assigned_to?: number
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

/* ---------- Extended Document Types ---------- */

export type DocumentType = 'member' | 'event' | 'finance' | 'general'

export interface DocumentDetail extends Document {
  RelatedEntityType?: string
  RelatedEntityID?: number
  RelatedEntityName?: string
  Tags?: string[]
}

export interface DocumentCreateInput {
  document_name: string
  document_type: DocumentType
  related_entity_type?: string
  related_entity_id?: number
  description?: string
  tags?: string[]
}

export interface DocumentUpdateInput extends Partial<DocumentCreateInput> {
  document_id: number
}

export interface DocumentListFilters {
  document_type?: DocumentType
  entity_type?: string
  entity_id?: number
  search?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

/* ---------- Extended Family Types ---------- */
import type { Family } from './member'
export interface FamilyMember {
  MemberID: number
  FullName: string
  Relationship: string
  MembershipStatus: string
  IsFamilyHead: boolean
}

export interface FamilyDetail extends Family {
  members: FamilyMember[]
  MemberCount: number
}

export interface FamilyCreateInput {
  family_name: string
  family_head_id?: number | null
  address?: string | null
  city?: string | null
  region?: string | null
  country?: string | null
  home_phone?: string | null
}

export interface FamilyUpdateInput extends Partial<FamilyCreateInput> {
  family_id: number
}

export interface FamilyListFilters {
  search?: string
  city?: string
  region?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

/* ---------- Communication Types ---------- */

export type CommunicationType = 'email' | 'sms'
export type CommunicationStatus = 'pending' | 'sent' | 'failed' | 'delivered' | 'opened'

export interface Communication {
  CommunicationID: number
  Type: CommunicationType
  Subject?: string
  Content: string
  SenderID: number
  SenderName?: string
  RecipientCount: number
  Status: CommunicationStatus
  SentAt?: string
  CreatedAt: string
}

export interface CommunicationRecipient {
  RecipientID: number
  CommunicationID: number
  MemberID?: number
  MemberName?: string
  Email?: string
  Phone?: string
  Status: CommunicationStatus
  DeliveredAt?: string
  OpenedAt?: string
}

export interface CommunicationTemplate {
  TemplateID: number
  TemplateName: string
  Type: CommunicationType
  Subject?: string
  Content: string
  Variables?: string[]
  CreatedAt?: string
  UpdatedAt?: string
}

export interface CommunicationCreateInput {
  type: CommunicationType
  subject?: string
  content: string
  recipient_ids: number[]
  scheduled_at?: string
}

export interface CommunicationTemplateCreateInput {
  template_name: string
  type: CommunicationType
  subject?: string
  content: string
  variables?: string[]
}

export interface CommunicationTemplateUpdateInput extends Partial<CommunicationTemplateCreateInput> {
  template_id: number
}

export interface CommunicationListFilters {
  type?: CommunicationType
  status?: CommunicationStatus
  sender_id?: number
  start_date?: string
  end_date?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

/* ---------- Audit Types ---------- */

export type AuditAction = 'create' | 'update' | 'delete' | 'view' | 'login' | 'logout' | 'export'

export interface AuditLog {
  AuditID: number
  UserID?: number
  UserName?: string
  Action: AuditAction
  EntityType: string
  EntityID?: number
  OldValues?: Record<string, unknown>
  NewValues?: Record<string, unknown>
  IpAddress?: string
  UserAgent?: string
  CreatedAt: string
}

export interface AuditLogListFilters {
  user_id?: number
  action?: AuditAction
  entity_type?: string
  entity_id?: number
  start_date?: string
  end_date?: string
  sort_by?: string
  sort_dir?: 'ASC' | 'DESC'
}

/* ---------- Role & Permission Types ---------- */

/* Role & Permission types are imported from './auth' to avoid duplicate exports */

export interface RoleWithPermissions extends Role {
  permissions: Permission[]
}

export interface UserRoleT {
  UserRoleID: number
  UserID: number
  RoleID: number
  RoleName?: string
  AssignedBy?: number
  AssignedAt?: string
}

export interface RoleCreateInput {
  role_name: string
  description?: string
  permission_ids: number[]
}

export interface RoleUpdateInput extends Partial<RoleCreateInput> {
  role_id: number
}

export interface AssignRoleInput {
  user_id: number
  role_id: number
}

export interface PermissionMatrix {
  role: Role
  permissions: Record<string, boolean>
}

/* ---------- Membership Type ---------- */

export interface MembershipType {
  MembershipTypeID: number
  TypeName: string
  Description?: string
  Benefits?: string
  Requirements?: string
  IsActive: boolean
  CreatedAt?: string
  UpdatedAt?: string
}

export interface MembershipTypeCreateInput {
  type_name: string
  description?: string
  benefits?: string
  requirements?: string
}

export interface MembershipTypeUpdateInput extends Partial<MembershipTypeCreateInput> {
  membership_type_id: number
  is_active?: boolean
}

/* ---------- Lookup Types ---------- */

import type { LookupItem } from './member'

export interface LookupCreateInput {
  name: string
  value?: string
  description?: string
  category: string
  sort_order?: number
}

export interface LookupUpdateInput extends Partial<LookupCreateInput> {
  id: number
  is_active?: boolean
}
