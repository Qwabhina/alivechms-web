<script setup lang="ts">
/**
 * NavigationView.vue
 * 
 * Comprehensive documentation for navigation components including:
 * - ChTabs: Tabbed interface with extensive demonstrations
 * - ChSidebar: Side navigation panel API reference
 * - ChTopbar: Top navigation bar API reference
 * 
 * @module Views/Docs/NavigationView
 */

import { ref, computed } from 'vue'
import {
  ChTabs,
  ChAlert,
  ChButton,
  ChBadge
} from '@/design-system'
import {
  UserIcon,
  MailIcon,
  PhoneIcon,
  HomeIcon,
  UsersIcon,
  CalendarIcon,
  FileTextIcon,
  HeartIcon,
  DollarSignIcon,
  ClockIcon,
  MapPinIcon,
  CameraIcon,
  EditIcon,
  TrashIcon,
  PlusIcon
} from 'lucide-vue-next'

// =============================================================================
// SECTION 1: CHTABS - STATE MANAGEMENT
// =============================================================================

/**
 * Basic horizontal tabs state
 */
const basicTab = ref('overview')

/**
 * Vertical tabs state
 */
const verticalTab = ref('general')

/**
 * Scrollable tabs state - many tabs demonstration
 */
const scrollableTab = ref('tab-1')

/**
 * Member Profile tabs - Real-world church management context
 */
const memberTab = ref('profile-overview')

/**
 * Church event tabs for multi-day event management
 */
const eventTab = ref('event-details')

// =============================================================================
// SECTION 2: MEMBER PROFILE DATA (REAL-WORLD CHURCH CONTEXT)
// =============================================================================

/**
 * Sample member profile data for demonstrations
 */
const memberProfile = {
  id: 'MEM-2024-001',
  name: 'Sarah Elizabeth Johnson',
  email: 'sarah.johnson@email.com',
  phone: '+1 (555) 234-5678',
  address: '1234 Oak Street, Springfield, IL 62701',
  dateOfBirth: '1985-03-15',
  joinDate: '2018-06-12',
  membershipType: 'Active Member',
  status: 'Active',
  photoUrl: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop&crop=face',
  occupation: 'Registered Nurse',
  maritalStatus: 'Married',
  spouseName: 'Michael Johnson',
  baptismDate: '2018-06-24',
  department: 'Women\'s Ministry',
  groups: ['Choir', 'Women\'s Fellowship', 'Sunday School Teacher']
}

/**
 * Family members data
 */
const familyMembers = [
  {
    id: 1,
    name: 'Michael Johnson',
    relationship: 'Husband',
    phone: '+1 (555) 234-5679',
    email: 'michael.johnson@email.com',
    status: 'Active',
    photoUrl: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face'
  },
  {
    id: 2,
    name: 'Emma Johnson',
    relationship: 'Daughter',
    phone: '+1 (555) 234-5680',
    email: 'emma.johnson@email.com',
    status: 'Active',
    age: 12,
    photoUrl: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=100&h=100&fit=crop&crop=face'
  },
  {
    id: 3,
    name: 'Lucas Johnson',
    relationship: 'Son',
    phone: '+1 (555) 234-5681',
    email: 'lucas.johnson@email.com',
    status: 'Active',
    age: 8,
    photoUrl: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&h=100&fit=crop&crop=face'
  }
]

/**
 * Contribution history data
 */
const contributions = ref([
  { id: 1, date: '2024-01-07', type: 'Tithes', amount: 500.00, method: 'Bank Transfer', reference: 'TXN-2024-001' },
  { id: 2, date: '2024-01-14', type: 'Tithes', amount: 500.00, method: 'Bank Transfer', reference: 'TXN-2024-002' },
  { id: 3, date: '2024-01-21', type: 'Offering', amount: 150.00, method: 'Cash', reference: 'TXN-2024-003' },
  { id: 4, date: '2024-02-04', type: 'Building Fund', amount: 1000.00, method: 'Check', reference: 'TXN-2024-004' },
  { id: 5, date: '2024-02-11', type: 'Tithes', amount: 500.00, method: 'Bank Transfer', reference: 'TXN-2024-005' },
  { id: 6, date: '2024-02-18', type: 'Missions', amount: 250.00, method: 'Bank Transfer', reference: 'TXN-2024-006' },
  { id: 7, date: '2024-02-25', type: 'Tithes', amount: 500.00, method: 'Bank Transfer', reference: 'TXN-2024-007' },
  { id: 8, date: '2024-03-03', type: 'Offering', amount: 200.00, method: 'Cash', reference: 'TXN-2024-008' }
])

/**
 * Attendance records data
 */
const attendanceRecords = ref([
  { id: 1, date: '2024-03-17', service: 'Sunday Service', status: 'Present', checkInTime: '09:45 AM' },
  { id: 2, date: '2024-03-10', service: 'Sunday Service', status: 'Present', checkInTime: '09:50 AM' },
  { id: 3, date: '2024-03-03', service: 'Sunday Service', status: 'Present', checkInTime: '09:42 AM' },
  { id: 4, date: '2024-02-25', service: 'Sunday Service', status: 'Present', checkInTime: '10:01 AM' },
  { id: 5, date: '2024-02-18', service: 'Wednesday Bible Study', status: 'Present', checkInTime: '06:35 PM' },
  { id: 6, date: '2024-02-11', service: 'Sunday Service', status: 'Present', checkInTime: '09:48 AM' },
  { id: 7, date: '2024-02-04', service: 'Sunday Service', status: 'Present', checkInTime: '09:55 AM' },
  { id: 8, date: '2024-01-28', service: 'Wednesday Bible Study', status: 'Absent', checkInTime: '-' },
  { id: 9, date: '2024-01-21', service: 'Sunday Service', status: 'Present', checkInTime: '09:40 AM' },
  { id: 10, date: '2024-01-14', service: 'Sunday Service', status: 'Present', checkInTime: '09:52 AM' }
])

/**
 * Document records data
 */
const documents = ref([
  { id: 1, name: 'Membership Certificate', type: 'Certificate', uploadDate: '2018-06-15', size: '245 KB', status: 'Active' },
  { id: 2, name: 'Baptism Certificate', type: 'Certificate', uploadDate: '2018-06-25', size: '189 KB', status: 'Active' },
  { id: 3, name: 'ID Document (Front)', type: 'ID', uploadDate: '2023-01-10', size: '1.2 MB', status: 'Active' },
  { id: 4, name: 'ID Document (Back)', type: 'ID', uploadDate: '2023-01-10', size: '1.1 MB', status: 'Active' },
  { id: 5, name: 'Emergency Contact Form', type: 'Form', uploadDate: '2024-02-01', size: '78 KB', status: 'Active' },
  { id: 6, name: 'Ministry Volunteer Agreement', type: 'Agreement', uploadDate: '2023-08-15', size: '156 KB', status: 'Active' }
])

/**
 * Yearly contribution summary
 */
const yearlyContributionTotal = computed(() => {
  return contributions.value.reduce((sum, c) => sum + c.amount, 0)
})

/**
 * Yearly attendance percentage
 */
const yearlyAttendanceRate = computed(() => {
  return Math.round((attendanceRecords.value.filter(a => a.status === 'Present').length / attendanceRecords.value.length) * 100)
})

/**
 * Tabs configuration for scrollable tabs demonstration
 */
const manyTabs = [
  { value: 'tab-1', label: 'Dashboard' },
  { value: 'tab-2', label: 'Members' },
  { value: 'tab-3', label: 'Groups' },
  { value: 'tab-4', label: 'Events' },
  { value: 'tab-5', label: 'Finance' },
  { value: 'tab-6', label: 'Reports' },
  { value: 'tab-7', label: 'Settings' },
  { value: 'tab-8', label: 'Communications' },
  { value: 'tab-9', label: 'Documents' },
  { value: 'tab-10', label: 'Volunteers' },
  { value: 'tab-11', label: 'Attendance' },
  { value: 'tab-12', label: 'Missions' }
]

/**
 * Church event data
 */
const eventData = {
  title: 'Annual Church Conference 2024',
  date: 'March 15-17, 2024',
  location: 'Main Sanctuary',
  expectedAttendees: 500,
  registeredCount: 423,
  sessions: [
    { day: 'Day 1', theme: 'Foundation of Faith', speaker: 'Rev. Dr. James Wilson' },
    { day: 'Day 2', theme: 'Walking in the Spirit', speaker: 'Pastor Sarah Mitchell' },
    { day: 'Day 3', theme: 'Building Community', speaker: 'Bishop Robert Thompson' }
  ]
}

// =============================================================================
// SECTION 3: HELPER FUNCTIONS
// =============================================================================

/**
 * Format currency for display
 */
const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount)
}
</script>

<template>
  <div class="doc-page">
    <!-- Page Header -->
    <header class="page-header">
      <h1 class="page-title">Navigation Components</h1>
      <p class="page-desc">
        Comprehensive documentation for navigation components including tabs, sidebar, and topbar.
        These components provide the structural navigation framework for the church management system.
      </p>
    </header>

    <!-- =======================================================================
    SECTION 1: CHTABS - BASIC DEMONSTRATIONS
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChTabs - Basic Usage</h2>
      <p class="doc-section-desc">
        The <code>ChTabs</code> component provides a horizontal or vertical tabbed interface for switching
        between related content panels. It's built with the zero-radius aesthetic and supports icons, badges,
        and scrollable overflow.
      </p>

      <div class="demo-grid">
        <!-- Basic Horizontal Tabs -->
        <div class="demo-block">
          <div class="demo-title">Basic Horizontal Tabs</div>
          <p class="demo-desc">Simple tabs with three sections</p>
          <ChTabs
v-model="basicTab"
            :tabs="[
  { value: 'overview', label: 'Overview' },
  { value: 'details', label: 'Details' },
  { value: 'settings', label: 'Settings' }
            ]"
          />
          <div class="tab-content-preview">
            <template v-if="basicTab === 'overview'">
              <p>Overview content - Dashboard summary and key metrics</p>
            </template>
            <template v-else-if="basicTab === 'details'">
              <p>Details content - Detailed information and configurations</p>
            </template>
            <template v-else>
              <p>Settings content - Application settings and preferences</p>
            </template>
          </div>
        </div>

        <!-- Vertical Tabs -->
        <div class="demo-block">
          <div class="demo-title">Vertical Tabs</div>
          <p class="demo-desc">Tabs arranged vertically for sidebar-like navigation</p>
          <ChTabs
            v-model="verticalTab"
            vertical
            :tabs="[
  { value: 'general', label: 'General' },
  { value: 'notifications', label: 'Notifications' },
  { value: 'privacy', label: 'Privacy' },
  { value: 'advanced', label: 'Advanced' }
            ]"
          />
          <div class="tab-content-preview vertical-content">
            <template v-if="verticalTab === 'general'">
              <h4>General Settings</h4>
              <p>Configure basic application preferences like language, timezone, and display options.</p>
            </template>
            <template v-else-if="verticalTab === 'notifications'">
              <h4>Notification Preferences</h4>
              <p>Manage how and when you receive notifications from the system.</p>
            </template>
            <template v-else-if="verticalTab === 'privacy'">
              <h4>Privacy Settings</h4>
              <p>Control who can see your information and activity within the system.</p>
            </template>
            <template v-else>
              <h4>Advanced Options</h4>
              <p>Access advanced configuration options for power users.</p>
            </template>
          </div>
        </div>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 2: CHTABS - SCROLLABLE TABS
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChTabs - Scrollable Tabs</h2>
      <p class="doc-section-desc">
        When there are many tabs that exceed the container width, the tabs become scrollable
        with navigation arrows on both ends.
      </p>

      <div class="demo-block">
        <div class="demo-title">Many Tabs with Scroll</div>
        <p class="demo-desc">Tabs that overflow will show scroll arrows</p>
        <ChTabs v-model="scrollableTab" :tabs="manyTabs.map((tab) => ({
          value: tab.value,
          label: tab.label
        }))" />
        <div class="tab-content-preview">
          <p>Currently viewing: <strong>{{manyTabs.find(t => t.value === scrollableTab)?.label}}</strong></p>
          <p>Active tab index: {{manyTabs.findIndex(t => t.value === scrollableTab) + 1}} of {{ manyTabs.length }}</p>
        </div>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 3: CHTABS - MEMBER PROFILE (REAL-WORLD CHURCH CONTEXT)
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChTabs - Member Profile (Church Context)</h2>
      <p class="doc-section-desc">
        A comprehensive example showing a church member profile with tabs for Overview, Contact Information,
        Family Details, Contributions, Attendance, and Documents. Each tab contains substantial real-world content.
      </p>

      <div class="demo-block member-profile-demo">
        <div class="demo-title">Member Profile Tabs</div>

        <ChTabs v-model="memberTab" :tabs="[
          { value: 'profile-overview', label: 'Overview', icon: UserIcon },
          { value: 'profile-contact', label: 'Contact', icon: PhoneIcon },
          { value: 'profile-family', label: 'Family', icon: HeartIcon },
          { value: 'profile-contributions', label: 'Contributions', icon: DollarSignIcon },
          { value: 'profile-attendance', label: 'Attendance', icon: CalendarIcon },
          { value: 'profile-documents', label: 'Documents', icon: FileTextIcon }
        ]" />

        <!-- TAB PANEL: OVERVIEW -->
        <div v-if="memberTab === 'profile-overview'" class="tab-panel member-overview">
          <div class="member-header">
            <img :src="memberProfile.photoUrl" :alt="memberProfile.name" class="member-photo" />
            <div class="member-info">
              <h3>{{ memberProfile.name }}</h3>
              <div class="member-meta">
                <span class="meta-item">
                  <ChBadge variant="success">{{ memberProfile.status }}</ChBadge>
                </span>
                <span class="meta-item">{{ memberProfile.membershipType }}</span>
                <span class="meta-item">Member since {{ memberProfile.joinDate }}</span>
              </div>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-value">${{ yearlyContributionTotal.toLocaleString() }}</div>
              <div class="stat-label">Total Contributions (YTD)</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ yearlyAttendanceRate }}%</div>
              <div class="stat-label">Attendance Rate</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ familyMembers.length }}</div>
              <div class="stat-label">Family Members</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ memberProfile.groups.length }}</div>
              <div class="stat-label">Active Groups</div>
            </div>
          </div>

          <div class="info-section">
            <h4>Personal Information</h4>
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Date of Birth</span>
                <span class="info-value">{{ memberProfile.dateOfBirth }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Occupation</span>
                <span class="info-value">{{ memberProfile.occupation }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Marital Status</span>
                <span class="info-value">{{ memberProfile.maritalStatus }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Department</span>
                <span class="info-value">{{ memberProfile.department }}</span>
              </div>
            </div>
          </div>

          <div class="info-section">
            <h4>Church Milestones</h4>
            <div class="milestone-list">
              <div class="milestone-item">
                <CalendarIcon class="milestone-icon" />
                <div>
                  <strong>Baptism Date</strong>
                  <span>{{ memberProfile.baptismDate }}</span>
                </div>
              </div>
              <div class="milestone-item">
                <UserIcon class="milestone-icon" />
                <div>
                  <strong>Membership Date</strong>
                  <span>{{ memberProfile.joinDate }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB PANEL: CONTACT -->
        <div v-if="memberTab === 'profile-contact'" class="tab-panel member-contact">
          <h4>Contact Information</h4>
          <p class="section-intro">Update member contact details. Changes will be reflected immediately in the
            directory.</p>

          <div class="contact-grid">
            <div class="contact-card">
              <MailIcon class="contact-icon" />
              <div class="contact-details">
                <span class="contact-label">Email Address</span>
                <span class="contact-value">{{ memberProfile.email }}</span>
                <ChButton variant="ghost" size="sm">
                  <EditIcon class="btn-icon" /> Edit
                </ChButton>
              </div>
            </div>

            <div class="contact-card">
              <PhoneIcon class="contact-icon" />
              <div class="contact-details">
                <span class="contact-label">Phone Number</span>
                <span class="contact-value">{{ memberProfile.phone }}</span>
                <ChButton variant="ghost" size="sm">
                  <EditIcon class="btn-icon" /> Edit
                </ChButton>
              </div>
            </div>

            <div class="contact-card full-width">
              <HomeIcon class="contact-icon" />
              <div class="contact-details">
                <span class="contact-label">Home Address</span>
                <span class="contact-value">{{ memberProfile.address }}</span>
                <ChButton variant="ghost" size="sm">
                  <EditIcon class="btn-icon" /> Edit
                </ChButton>
              </div>
            </div>
          </div>

          <div class="contact-form">
            <h4>Update Contact Information</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Email</label>
                <input type="email" :value="memberProfile.email" class="form-input" />
              </div>
              <div class="form-group">
                <label>Phone</label>
                <input type="tel" :value="memberProfile.phone" class="form-input" />
              </div>
            </div>
            <div class="form-group">
              <label>Address</label>
              <input type="text" :value="memberProfile.address" class="form-input" />
            </div>
            <div class="form-actions">
              <ChButton variant="primary">Save Changes</ChButton>
              <ChButton variant="outline">Cancel</ChButton>
            </div>
          </div>
        </div>

        <!-- TAB PANEL: FAMILY -->
        <div v-if="memberTab === 'profile-family'" class="tab-panel member-family">
          <div class="family-header">
            <h4>Family Directory</h4>
            <ChButton variant="primary" size="sm">
              <PlusIcon class="btn-icon" /> Add Family Member
            </ChButton>
          </div>

          <div class="family-list">
            <div v-for="member in familyMembers" :key="member.id" class="family-card">
              <img :src="member.photoUrl" :alt="member.name" class="family-photo" />
              <div class="family-info">
                <h5>{{ member.name }}</h5>
                <p class="family-relationship">{{ member.relationship }}</p>
                <div class="family-contact">
                  <span>
                    <PhoneIcon class="mini-icon" /> {{ member.phone }}
                  </span>
                  <span>
                    <MailIcon class="mini-icon" /> {{ member.email }}
                  </span>
                </div>
              </div>
              <div class="family-status">
                <ChBadge :variant="member.status === 'Active' ? 'success' : 'warning'">
                  {{ member.status }}
                </ChBadge>
                <div class="family-actions">
                  <ChButton variant="ghost" size="sm">
                    <EditIcon class="mini-icon" />
                  </ChButton>
                  <ChButton variant="ghost" size="sm">
                    <TrashIcon class="mini-icon" />
                  </ChButton>
                </div>
              </div>
            </div>
          </div>

          <div class="family-notes">
            <h4>Family Notes</h4>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et
              dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex
              ea commodo consequat.</p>
            <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
              Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est
              laborum.</p>
          </div>
        </div>

        <!-- TAB PANEL: CONTRIBUTIONS -->
        <div v-if="memberTab === 'profile-contributions'" class="tab-panel member-contributions">
          <div class="contributions-summary">
            <h4>Contribution Summary</h4>
            <div class="contribution-stats">
              <div class="contrib-stat">
                <span class="contrib-label">Year to Date</span>
                <span class="contrib-value">${{ yearlyContributionTotal.toLocaleString() }}</span>
              </div>
              <div class="contrib-stat">
                <span class="contrib-label">This Year</span>
                <span class="contrib-value">{{ contributions.length }} transactions</span>
              </div>
            </div>
          </div>

          <h4>Contribution History</h4>
          <div class="contributions-table">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Reference</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="contribution in contributions" :key="contribution.id">
                  <td>{{ contribution.date }}</td>
                  <td>
                    <ChBadge variant="primary">{{ contribution.type }}</ChBadge>
                  </td>
                  <td class="amount">{{ formatCurrency(contribution.amount) }}</td>
                  <td>{{ contribution.method }}</td>
                  <td class="reference">{{ contribution.reference }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="contribution-breakdown">
            <h4>Contribution Breakdown</h4>
            <div class="breakdown-item">
              <span class="breakdown-label">Tithes</span>
              <ChBadge variant="primary">60%</ChBadge>
            </div>
            <div class="breakdown-item">
              <span class="breakdown-label">Offering</span>
              <ChBadge variant="info">15%</ChBadge>
            </div>
            <div class="breakdown-item">
              <span class="breakdown-label">Building Fund</span>
              <ChBadge variant="success">15%</ChBadge>
            </div>
            <div class="breakdown-item">
              <span class="breakdown-label">Missions</span>
              <ChBadge variant="warning">10%</ChBadge>
            </div>
          </div>
        </div>

        <!-- TAB PANEL: ATTENDANCE -->
        <div v-if="memberTab === 'profile-attendance'" class="tab-panel member-attendance">
          <div class="attendance-summary">
            <h4>Attendance Overview</h4>
            <div class="attendance-stats">
              <div class="att-stat">
                <div class="att-value">{{ yearlyAttendanceRate }}%</div>
                <div class="att-label">Attendance Rate</div>
              </div>
              <div class="att-stat">
                <div class="att-value">{{attendanceRecords.filter(a => a.status === 'Present').length}}</div>
                <div class="att-label">Services Attended</div>
              </div>
              <div class="att-stat">
                <div class="att-value">{{attendanceRecords.filter(a => a.status === 'Absent').length}}</div>
                <div class="att-label">Absences</div>
              </div>
            </div>
          </div>

          <h4>Recent Attendance</h4>
          <div class="attendance-list">
            <div v-for="record in attendanceRecords.slice(0, 6)" :key="record.id" class="attendance-item">
              <div class="attendance-date">{{ record.date }}</div>
              <div class="attendance-service">{{ record.service }}</div>
              <ChBadge :variant="record.status === 'Present' ? 'success' : 'danger'">
                {{ record.status }}
              </ChBadge>
            </div>
          </div>

          <div class="attendance-note">
            <ChAlert variant="info" title="Attendance Note">
              <p>Track your attendance history and stay connected with weekly services and events.</p>
            </ChAlert>
          </div>
        </div>

        <!-- TAB PANEL: DOCUMENTS -->
        <div v-if="memberTab === 'profile-documents'" class="tab-panel member-documents">
          <div class="documents-header">
            <h4>Member Documents</h4>
            <ChButton variant="primary" size="sm">
              <PlusIcon class="btn-icon" /> Upload Document
            </ChButton>
          </div>

          <div class="documents-grid">
            <div v-for="doc in documents" :key="doc.id" class="document-card">
              <FileTextIcon class="doc-icon" />
              <div class="doc-info">
                <h5>{{ doc.name }}</h5>
                <p class="doc-meta">
                  <span>{{ doc.type }}</span>
                  <span>{{ doc.uploadDate }}</span>
                  <span>{{ doc.size }}</span>
                </p>
              </div>
              <ChBadge :variant="doc.status === 'Active' ? 'success' : 'warning'">
                {{ doc.status }}
              </ChBadge>
            </div>
          </div>

          <div class="document-upload-section">
            <h4>Upload New Document</h4>
            <div class="upload-area">
              <CameraIcon class="upload-icon" />
              <p>Drag and drop files here or click to browse</p>
              <p class="upload-hint">Supported formats: PDF, JPG, PNG (Max 10MB)</p>
              <ChButton variant="outline" size="sm">Browse Files</ChButton>
            </div>
          </div>

          <div class="document-policy">
            <ChAlert variant="info" title="Document Policy">
              <p>All uploaded documents are stored securely and only accessible to authorized church staff.</p>
            </ChAlert>
          </div>
        </div>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 4: CHTABS - EVENT MANAGEMENT EXAMPLE
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChTabs - Event Management (Multi-Day Event)</h2>
      <p class="doc-section-desc">
        Example showing tabs for managing a multi-day church conference with session schedules,
        speaker information, and venue details.
      </p>

      <div class="demo-block">
        <div class="demo-title">Church Conference Tabs</div>

        <ChTabs v-model="eventTab" :tabs="[
          { value: 'event-details', label: 'Event Details', icon: CalendarIcon },
          { value: 'event-schedule', label: 'Schedule', icon: ClockIcon },
          { value: 'event-registration', label: 'Registration', icon: UsersIcon },
          { value: 'event-venue', label: 'Venue', icon: MapPinIcon }
        ]" />

        <!-- Event Details Tab -->
        <div v-if="eventTab === 'event-details'" class="tab-panel">
          <h4>{{ eventData.title }}</h4>
          <div class="event-meta">
            <span>
              <CalendarIcon class="meta-icon" /> {{ eventData.date }}
            </span>
            <span>
              <MapPinIcon class="meta-icon" /> {{ eventData.location }}
            </span>
          </div>
          <p class="event-description">
            Join us for three transformative days of worship, teaching, and fellowship as we explore
            what it means to build a community rooted in faith.
          </p>
          <div class="event-stats">
            <div class="event-stat">
              <strong>{{ eventData.expectedAttendees }}</strong>
              <span>Expected</span>
            </div>
            <div class="event-stat">
              <strong>{{ eventData.registeredCount }}</strong>
              <span>Registered</span>
            </div>
            <div class="event-stat">
              <strong>{{ eventData.expectedAttendees - eventData.registeredCount }}</strong>
              <span>Spots Left</span>
            </div>
          </div>
        </div>

        <!-- Schedule Tab -->
        <div v-if="eventTab === 'event-schedule'" class="tab-panel">
          <h4>Conference Schedule</h4>
          <div class="schedule-list">
            <div v-for="session in eventData.sessions" :key="session.day" class="schedule-item">
              <div class="schedule-day">{{ session.day }}</div>
              <div class="schedule-content">
                <h5>{{ session.theme }}</h5>
                <p>Speaker: {{ session.speaker }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Registration Tab -->
        <div v-if="eventTab === 'event-registration'" class="tab-panel">
          <h4>Registration Progress</h4>
          <p>{{ eventData.registeredCount }} of {{ eventData.expectedAttendees }} spots filled.</p>
          <ChButton variant="primary">Register Now</ChButton>
        </div>

        <!-- Venue Tab -->
        <div v-if="eventTab === 'event-venue'" class="tab-panel">
          <h4>Venue Information</h4>
          <p>
            <MapPinIcon class="meta-icon" /> {{ eventData.location }}
          </p>
          <p>Our main sanctuary provides comfortable seating with state-of-the-art audio and video equipment.</p>
        </div>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 5: CHTABS - API REFERENCE
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChTabs - API Reference</h2>

      <div class="api-reference">
        <h3>Props</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Prop</th>
              <th>Type</th>
              <th>Default</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>modelValue</code> / <code>v-model</code></td>
              <td><code>string</code></td>
              <td><code>''</code></td>
              <td>Currently active tab value (two-way binding)</td>
            </tr>
            <tr>
              <td><code>tabs</code></td>
              <td><code>TabItem[]</code></td>
              <td><code>[]</code></td>
              <td>Array of tab definitions with value, label, icon, and badge</td>
            </tr>
            <tr>
              <td><code>vertical</code></td>
              <td><code>boolean</code></td>
              <td><code>false</code></td>
              <td>Display tabs vertically instead of horizontally</td>
            </tr>
          </tbody>
        </table>

        <h3>TabItem Type</h3>
        <pre class="code-block"><code>interface TabItem {
  value: string       // Unique identifier for the tab
  label: string       // Display text
  icon?: Component    // Optional icon component
  badge?: string | number  // Optional badge text
  disabled?: boolean  // Disable this specific tab
}</code></pre>

        <h3>Events</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Event</th>
              <th>Payload</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>update:modelValue</code></td>
              <td><code>string</code></td>
              <td>Emitted when active tab changes</td>
            </tr>
          </tbody>
        </table>

        <h3>Usage Example</h3>
        <pre class="code-block"><code>&lt;ChTabs
  v-model="activeTab"
  :tabs="[
    { value: 'overview', label: 'Overview', icon: HomeIcon },
    { value: 'details', label: 'Details', badge: 5 },
    { value: 'settings', label: 'Settings', disabled: true }
  ]"
&gt;
  &lt;div v-if="activeTab === 'overview'"&gt;Overview content&lt;/div&gt;
  &lt;div v-if="activeTab === 'details'"&gt;Details content&lt;/div&gt;
  &lt;div v-if="activeTab === 'settings'"&gt;Settings content&lt;/div&gt;
&lt;/ChTabs&gt;</code></pre>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 6: CHSIDEBAR - API REFERENCE
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChSidebar - API Reference</h2>
      <p class="doc-section-desc">
        The <code>ChSidebar</code> component provides the primary navigation panel for the church
        management system. It supports nested items, badges, icons, and mobile drawer mode.
      </p>

      <div class="api-reference">
        <h3>Props</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Prop</th>
              <th>Type</th>
              <th>Default</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>items</code></td>
              <td><code>NavSection[]</code></td>
              <td><code>[]</code></td>
              <td>Navigation sections and items configuration</td>
            </tr>
            <tr>
              <td><code>currentRoute</code></td>
              <td><code>string</code></td>
              <td><code>''</code></td>
              <td>Current route path for active state determination</td>
            </tr>
            <tr>
              <td><code>mobileOpen</code></td>
              <td><code>boolean</code></td>
              <td><code>false</code></td>
              <td>Control mobile drawer visibility</td>
            </tr>
            <tr>
              <td><code>logo</code></td>
              <td><code>string</code></td>
              <td><code>''</code></td>
              <td>Logo image URL for sidebar header</td>
            </tr>
            <tr>
              <td><code>orgName</code></td>
              <td><code>string</code></td>
              <td><code>''</code></td>
              <td>Organization name displayed in sidebar header</td>
            </tr>
          </tbody>
        </table>

        <h3>NavItem Type</h3>
        <pre class="code-block"><code>interface NavItem {
  label: string              // Display text
  to?: string               // Route path (for leaf items)
  icon?: Component          // Icon component (lucide-vue-next)
  badge?: string | number   // Optional badge count
  children?: NavItem[]      // Nested items (creates dropdown)
  onClick?: () => void     // Custom click handler
}

interface NavSection {
  title?: string    // Section header (optional)
  items: NavItem[]  // Items in this section
}</code></pre>

        <h3>Events</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Event</th>
              <th>Payload</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>navigate</code></td>
              <td><code>string</code></td>
              <td>Emitted when a navigation item is clicked</td>
            </tr>
            <tr>
              <td><code>update:mobileOpen</code></td>
              <td><code>boolean</code></td>
              <td>Two-way binding for mobile drawer state</td>
            </tr>
          </tbody>
        </table>

        <h3>Slots</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Slot</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>header</code></td>
              <td>Custom sidebar header content</td>
            </tr>
            <tr>
              <td><code>footer</code></td>
              <td>Custom sidebar footer content</td>
            </tr>
            <tr>
              <td><code>item</code></td>
              <td>Custom rendering for navigation items</td>
            </tr>
          </tbody>
        </table>

        <h3>Usage Example</h3>
        <pre class="code-block"><code>&lt;ChSidebar
  v-model:mobile-open="drawerOpen"
  :items="[
    {
      title: 'Main',
      items: [
        { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
        { label: 'Members', to: '/members', icon: UsersIcon, badge: 5 },
        { label: 'Groups', to: '/groups', icon: UsersRound }
      ]
    },
    {
      title: 'Management',
      items: [
        { label: 'Events', to: '/events', icon: CalendarIcon },
        { label: 'Finance', to: '/finance', icon: DollarSignIcon }
      ]
    }
  ]"
  @navigate="handleNavigation"
/&gt;</code></pre>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 7: CHTOPBAR - API REFERENCE
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChTopbar - API Reference</h2>
      <p class="doc-section-desc">
        The <code>ChTopbar</code> component provides the horizontal navigation bar at the top of
        the application. It includes mobile menu toggle, page title/breadcrumb display, and
        optional action buttons.
      </p>

      <div class="api-reference">
        <h3>Props</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Prop</th>
              <th>Type</th>
              <th>Default</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>pageTitle</code></td>
              <td><code>string</code></td>
              <td><code>''</code></td>
              <td>Page title displayed in the topbar</td>
            </tr>
            <tr>
              <td><code>showMenuButton</code></td>
              <td><code>boolean</code></td>
              <td><code>true</code></td>
              <td>Show hamburger menu button (for mobile)</td>
            </tr>
            <tr>
              <td><code>breadcrumbs</code></td>
              <td><code>Crumb[]</code></td>
              <td><code>[]</code></td>
              <td>Breadcrumb navigation items</td>
            </tr>
            <tr>
              <td><code>userName</code></td>
              <td><code>string</code></td>
              <td><code>''</code></td>
              <td>Currently logged-in user's name</td>
            </tr>
            <tr>
              <td><code>userAvatar</code></td>
              <td><code>string</code></td>
              <td><code>''</code></td>
              <td>User avatar image URL</td>
            </tr>
          </tbody>
        </table>

        <h3>Crumb Type</h3>
        <pre class="code-block"><code>interface Crumb {
  label: string  // Display text
  to?: string    // Route path (optional, if it's a link)
}</code></pre>

        <h3>Events</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Event</th>
              <th>Payload</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>menu-click</code></td>
              <td><code>-</code></td>
              <td>Fired when hamburger menu button is clicked</td>
            </tr>
            <tr>
              <td><code>user-action</code></td>
              <td><code>string</code></td>
              <td>Fired when a user menu item is clicked</td>
            </tr>
          </tbody>
        </table>

        <h3>Slots</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Slot</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>title</code></td>
              <td>Custom title content (overrides pageTitle)</td>
            </tr>
            <tr>
              <td><code>actions</code></td>
              <td>Custom action buttons on the right side</td>
            </tr>
          </tbody>
        </table>

        <h3>Usage Example</h3>
        <pre class="code-block"><code>&lt;ChTopbar
  :breadcrumbs="[
    { label: 'Home', to: '/' },
    { label: 'Members', to: '/members' },
    { label: 'John Doe' }
  ]"
  user-name="Admin User"
  user-avatar="/images/avatar.jpg"
  @menu-click="drawerOpen = true"
&gt;
  &lt;template #actions&gt;
    &lt;ChButton variant="primary" size="sm"&gt;
      &lt;PlusIcon class="btn-icon" /&gt; Add Member
    &lt;/ChButton&gt;
  &lt;/template&gt;
&lt;/ChTopbar&gt;</code></pre>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* =============================================================================
DOCUMENTATION STYLES
================================================================================ */

.doc-page {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid var(--ch-color-border-strong);
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--ch-color-text);
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.02em;
}

.page-desc {
  font-size: 1rem;
  color: var(--ch-color-text-muted);
  margin: 0;
  line-height: 1.6;
}

.doc-section {
  margin-bottom: 3rem;
  padding: 1.5rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
}

.doc-section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--ch-color-text);
  margin: 0 0 0.5rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.doc-section-desc {
  font-size: 0.9rem;
  color: var(--ch-color-text-muted);
  margin: 0 0 1.5rem 0;
  line-height: 1.6;
}

.demo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-top: 1rem;
}

.demo-block {
  background: var(--ch-color-bg);
  border: 1px solid var(--ch-color-border);
  padding: 1.5rem;
}

.demo-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--ch-color-text);
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.demo-desc {
  font-size: 0.8rem;
  color: var(--ch-color-text-muted);
  margin: 0 0 1rem 0;
}

.tab-content-preview {
  margin-top: 1rem;
  padding: 1rem;
  background: var(--ch-color-bg-subtle);
  border: 1px dashed var(--ch-color-border-strong);
  min-height: 80px;
}

.tab-content-preview p {
  margin: 0;
  color: var(--ch-color-text-muted);
}

.tab-content-preview h4 {
  margin: 0 0 0.5rem 0;
  color: var(--ch-color-text);
}

.vertical-content {
  margin-left: 1rem;
}

/* =============================================================================
MEMBER PROFILE STYLES
================================================================================ */

.member-profile-demo {
  padding: 0;
}

.member-profile-demo .demo-title {
  padding: 1rem 1.5rem;
  margin: 0;
  border-bottom: 1px solid var(--ch-color-border);
}

.tab-panel {
  padding: 1.5rem;
}

/* Overview Tab Styles */
.member-overview {
  padding: 0;
}

.member-header {
  display: flex;
  gap: 1.5rem;
  padding: 1.5rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.member-photo {
  width: 100px;
  height: 100px;
  border-radius: 0;
  object-fit: cover;
  border: 2px solid var(--ch-color-border-strong);
}

.member-info h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
}

.member-meta {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  color: var(--ch-color-text-muted);
  font-size: 0.875rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  background: var(--ch-color-border);
  border: 1px solid var(--ch-color-border);
}

.stat-card {
  background: var(--ch-color-bg);
  padding: 1rem;
  text-align: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--ch-color-primary);
}

.stat-label {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 0.25rem;
}

.info-section {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.info-section h4 {
  margin: 0 0 1rem 0;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ch-color-text-muted);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-value {
  font-size: 0.9rem;
  color: var(--ch-color-text);
}

.milestone-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.milestone-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.milestone-icon {
  width: 20px;
  height: 20px;
  color: var(--ch-color-primary);
}

.milestone-item strong {
  display: block;
  font-size: 0.875rem;
}

.milestone-item span {
  font-size: 0.8rem;
  color: var(--ch-color-text-muted);
}

/* Contact Tab Styles */
.member-contact {
  padding: 0;
}

.section-intro {
  color: var(--ch-color-text-muted);
  margin: 0 0 1.5rem 0;
  padding: 0 1.5rem;
}

.contact-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  padding: 0 1.5rem;
}

.contact-card {
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  padding: 1rem;
  display: flex;
  gap: 1rem;
}

.contact-card.full-width {
  grid-column: span 2;
}

.contact-icon {
  width: 24px;
  height: 24px;
  color: var(--ch-color-primary);
  flex-shrink: 0;
}

.contact-details {
  flex: 1;
}

.contact-label {
  display: block;
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.contact-value {
  display: block;
  margin: 0.25rem 0;
  color: var(--ch-color-text);
}

.contact-form {
  padding: 1.5rem;
  border-top: 1px solid var(--ch-color-border);
  margin-top: 1rem;
}

.contact-form h4 {
  margin: 0 0 1rem 0;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.form-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--ch-color-border);
  background: var(--ch-color-bg);
  color: var(--ch-color-text);
  font-size: 0.875rem;
}

.form-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

/* Family Tab Styles */
.member-family {
  padding: 0;
}

.family-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.family-header h4 {
  margin: 0;
}

.family-list {
  padding: 1rem 1.5rem;
}

.family-card {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  margin-bottom: 1rem;
}

.family-photo {
  width: 60px;
  height: 60px;
  border-radius: 0;
  object-fit: cover;
  border: 1px solid var(--ch-color-border);
}

.family-info {
  flex: 1;
}

.family-info h5 {
  margin: 0 0 0.25rem 0;
}

.family-relationship {
  color: var(--ch-color-text-muted);
  font-size: 0.875rem;
  margin: 0 0 0.5rem 0;
}

.family-contact {
  display: flex;
  gap: 1rem;
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.family-contact span {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.family-status {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.family-actions {
  display: flex;
  gap: 0.25rem;
}

.family-notes {
  padding: 1.5rem;
  border-top: 1px solid var(--ch-color-border);
}

.family-notes h4 {
  margin: 0 0 1rem 0;
}

.family-notes p {
  margin: 0 0 1rem 0;
  color: var(--ch-color-text-muted);
}

/* Contributions Tab Styles */
.member-contributions {
  padding: 0;
}

.contributions-summary {
  padding: 1.5rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.contributions-summary h4 {
  margin: 0 0 1rem 0;
}

.contribution-stats {
  display: flex;
  gap: 2rem;
}

.contrib-stat {
  display: flex;
  flex-direction: column;
}

.contrib-label {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
}

.contrib-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--ch-color-primary);
}

.contributions-table {
  padding: 1rem 1.5rem;
}

.contributions-table table {
  width: 100%;
  border-collapse: collapse;
}

.contributions-table th,
.contributions-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--ch-color-border);
}

.contributions-table th {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ch-color-text-muted);
}

.amount {
  font-weight: 600;
  color: var(--ch-color-success);
}

.reference {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.contribution-breakdown {
  padding: 1.5rem;
  border-top: 1px solid var(--ch-color-border);
}

.contribution-breakdown h4 {
  margin: 0 0 1rem 0;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--ch-color-border);
}

.breakdown-label {
  font-size: 0.875rem;
}

/* Attendance Tab Styles */
.member-attendance {
  padding: 0;
}

.attendance-summary {
  padding: 1.5rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.attendance-summary h4 {
  margin: 0 0 1rem 0;
}

.attendance-stats {
  display: flex;
  gap: 2rem;
}

.att-stat {
  text-align: center;
}

.att-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--ch-color-primary);
}

.att-label {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
}

.attendance-list {
  padding: 1rem 1.5rem;
}

.attendance-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.attendance-date {
  font-weight: 600;
}

.attendance-service {
  color: var(--ch-color-text-muted);
  font-size: 0.875rem;
}

.attendance-note {
  padding: 1.5rem;
}

/* Documents Tab Styles */
.member-documents {
  padding: 0;
}

.documents-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.documents-header h4 {
  margin: 0;
}

.documents-grid {
  padding: 1rem 1.5rem;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.document-card {
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.doc-icon {
  width: 32px;
  height: 32px;
  color: var(--ch-color-primary);
}

.doc-info h5 {
  margin: 0;
  font-size: 0.875rem;
}

.doc-meta {
  display: flex;
  gap: 0.75rem;
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  margin: 0;
}

.document-upload-section {
  padding: 1.5rem;
  border-top: 1px solid var(--ch-color-border);
}

.document-upload-section h4 {
  margin: 0 0 1rem 0;
}

.upload-area {
  background: var(--ch-color-bg-subtle);
  border: 2px dashed var(--ch-color-border);
  padding: 2rem;
  text-align: center;
}

.upload-icon {
  width: 48px;
  height: 48px;
  color: var(--ch-color-text-muted);
  margin-bottom: 1rem;
}

.upload-hint {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.document-policy {
  padding: 1.5rem;
}

/* Event Styles */
.event-meta {
  display: flex;
  gap: 1.5rem;
  margin: 1rem 0;
  color: var(--ch-color-text-muted);
}

.meta-icon {
  width: 16px;
  height: 16px;
  margin-right: 0.25rem;
  vertical-align: middle;
}

.event-description {
  margin: 1rem 0;
  color: var(--ch-color-text-muted);
}

.event-stats {
  display: flex;
  gap: 2rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--ch-color-border);
}

.event-stat {
  text-align: center;
}

.event-stat strong {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--ch-color-primary);
}

.event-stat span {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
}

/* Schedule Styles */
.schedule-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.schedule-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
}

.schedule-day {
  font-weight: 700;
  color: var(--ch-color-primary);
  min-width: 60px;
}

.schedule-content h5 {
  margin: 0 0 0.25rem 0;
}

.schedule-content p {
  margin: 0;
  color: var(--ch-color-text-muted);
  font-size: 0.875rem;
}

/* =============================================================================
API REFERENCE STYLES
================================================================================ */

.api-reference {
  margin-top: 1rem;
}

.api-reference h3 {
  font-size: 1rem;
  font-weight: 600;
  margin: 1.5rem 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.api-reference h3:first-child {
  margin-top: 0;
}

.api-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1rem;
}

.api-table th,
.api-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--ch-color-border);
}

.api-table th {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ch-color-text-muted);
  background: var(--ch-color-bg-subtle);
}

.api-table td {
  font-size: 0.875rem;
}

.api-table code {
  background: var(--ch-color-bg-subtle);
  padding: 0.125rem 0.25rem;
  font-size: 0.8rem;
}

.code-block {
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  padding: 1rem;
  overflow-x: auto;
  font-size: 0.8rem;
  line-height: 1.5;
}

.code-block code {
  color: var(--ch-color-text);
}

/* =============================================================================
SHARED UTILITY STYLES
================================================================================ */

.btn-icon {
  width: 14px;
  height: 14px;
  margin-right: 0.25rem;
}

.mini-icon {
  width: 14px;
  height: 14px;
}
</style>
