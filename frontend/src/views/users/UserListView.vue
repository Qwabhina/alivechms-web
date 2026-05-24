<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { userService } from '@/services/user.service'
import { roleService } from '@/services/role.service'
import { useToast, useModal, confirm } from '@/design-system'
import type { User, UserStats, UserFilters, UserRole, UserActivityLog } from '@/types/user'
import type { Role } from '@/types/role'

// Components
import {
  ChPageHeader,
  ChCard,
  ChButton,
  ChInput,
  ChSelect,
  ChTable,
  ChBadge,
  ChAvatar,
  ChModal,
  ChFormField,
  ChPagination,
  ChEmptyState,
} from '@/design-system'

// Icons
import {
  Search,
  UserX,
  Activity,
  RefreshCw,
} from '@lucide/vue'

// Router
const router = useRouter()

// Composables
const toast = useToast()

// ─── State ─────────────────────────────────────────────────────────────────

const users = ref<User[]>([])
const stats = ref<UserStats | null>(null)
const loading = ref(false)
const selectedUsers = ref<Set<number>>(new Set())

// Pagination
const currentPage = ref(1)
const pageSize = ref(25)
const totalUsers = ref(0)
const totalPages = computed(() => Math.ceil(totalUsers.value / pageSize.value))

// Filters
const filters = reactive({
  search: '',
  is_active: undefined as string | undefined,
  sort_by: 'CreatedAt',
  sort_dir: 'DESC',
})

const showFilters = ref(false)

// Modals
const grantAccessModal = useModal()
const revokeModal = useModal()
const rolesModal = useModal()
const activityModal = useModal()
const passwordModal = useModal()
const bulkActionModal = useModal()

// Selected user for actions
const selectedUser = ref<User | null>(null)
const userRoles = ref<UserRole[]>([])
const availableRoles = ref<Role[]>([])
const userActivity = ref<UserActivityLog[]>([])

// Grant access form
const grantForm = reactive({
  mbr_id: null as number | null,
  username: '',
  password: '',
  confirmPassword: '',
  role_ids: [] as number[],
})

const eligibleMembers = ref<Array<{ MbrID: number; FullName: string; MbrEmail: string; HasLogin: boolean }>>([])
const memberSearchQuery = ref('')

// Reset password form
const resetPasswordForm = reactive({
  newPassword: '',
  confirmPassword: '',
})

// Bulk action
const bulkAction = ref<'activate' | 'deactivate' | 'revoke'>('activate')

// ─── Computed ────────────────────────────────────────────────────────────────

const activeFiltersCount = computed(() => {
  let count = 0
  if (filters.search) count++
  if (filters.is_active !== undefined) count++
  return count
})

const hasSelectedUsers = computed(() => selectedUsers.value.size > 0)

// ─── Load Data ───────────────────────────────────────────────────────────────

async function loadUsers(page = 1) {
  loading.value = true
  try {
    // Convert string is_active to boolean for API
    const apiFilters = {
      ...filters,
      is_active: filters.is_active === 'active' ? true : filters.is_active === 'inactive' ? false : undefined,
    }
    const response = await userService.list(page, pageSize.value, apiFilters as UserFilters)
    if (response?.data) {
      users.value = response.data.data || []
      totalUsers.value = (response.data as any).meta?.total || 0
      currentPage.value = page
    }
  } catch {
    toast.error('Failed to load users')
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  try {
    const response = await userService.getStats()
    if (response?.data) {
      stats.value = response.data
    }
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

async function loadRoles() {
  try {
    const response = await roleService.list()
    if (response?.data) {
      availableRoles.value = (response.data || []) as unknown as Role[]
    }
  } catch {
    console.error('Failed to load roles')
  }
}

onMounted(() => {
  loadUsers()
  loadStats()
  loadRoles()
})

// ─── Selection ──────────────────────────────────────────────────────────────

function toggleSelectUser(userId: number) {
  if (selectedUsers.value.has(userId)) {
    selectedUsers.value.delete(userId)
  } else {
    selectedUsers.value.add(userId)
  }
}

// ─── Filter Actions ─────────────────────────────────────────────────────────

function applyFilters() {
  loadUsers(1)
  showFilters.value = false
}

function clearFilters() {
  filters.search = ''
  filters.is_active = undefined
  loadUsers(1)
}

function handleSort(column: string) {
  if (filters.sort_by === column) {
    filters.sort_dir = filters.sort_dir === 'ASC' ? 'DESC' : 'ASC'
  } else {
    filters.sort_by = column as keyof UserFilters
    filters.sort_dir = 'ASC'
  }
  loadUsers(1)
}

// ─── Grant Access ────────────────────────────────────────────────────────────

async function searchEligibleMembers() {
  if (!memberSearchQuery.value.trim()) {
    eligibleMembers.value = []
    return
  }
  try {
    const response = await userService.searchEligibleMembers(memberSearchQuery.value)
    if (response?.data) {
      eligibleMembers.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to search members:', error)
  }
}

function selectMemberForGrant(member: { MbrID: number; FullName: string; MbrEmail: string; HasLogin: boolean }) {
  grantForm.mbr_id = member.MbrID
  grantForm.username = ''
  grantForm.password = ''
  grantForm.confirmPassword = ''
  grantForm.role_ids = []
}

async function handleGrantAccess() {
  if (!grantForm.mbr_id) {
    toast.error('Please select a member')
    return
  }
  if (!grantForm.username.trim()) {
    toast.error('Username is required')
    return
  }
  if (!grantForm.password) {
    toast.error('Password is required')
    return
  }
  if (grantForm.password !== grantForm.confirmPassword) {
    toast.error('Passwords do not match')
    return
  }
  if (grantForm.password.length < 8) {
    toast.error('Password must be at least 8 characters')
    return
  }

  try {
    await userService.grantAccess({
      mbr_id: grantForm.mbr_id,
      username: grantForm.username,
      password: grantForm.password,
      role_ids: grantForm.role_ids.length > 0 ? grantForm.role_ids : undefined,
    })
    toast.success('System access granted successfully')
    grantAccessModal.close()
    loadUsers()
    loadStats()
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    toast.error(err?.response?.data?.message || 'Failed to grant access')
  }
}

// ─── Revoke Access ───────────────────────────────────────────────────────────

function openRevokeModal(user: User) {
  selectedUser.value = user
  revokeModal.open()
}

async function handleRevokeAccess() {
  if (!selectedUser.value) return
  try {
    await userService.revokeAccess(selectedUser.value.MbrID)
    toast.success('System access revoked')
    revokeModal.close()
    loadUsers()
    loadStats()
  } catch {
    toast.error('Failed to revoke access')
  }
}

// ─── Toggle Active ───────────────────────────────────────────────────────────

async function toggleUserActive(user: User) {
  const newStatus = !user.IsActive
  const action = newStatus ? 'activate' : 'deactivate'
  
  const confirmed = await confirm({
    title: `${newStatus ? 'Activate' : 'Deactivate'} User`,
    message: `Are you sure you want to ${action} ${user.FullName}'s account?`,
    confirmLabel: newStatus ? 'Activate' : 'Deactivate',
  })

  if (!confirmed) return

  try {
    await userService.toggleActive(user.MbrID, newStatus)
    toast.success(`User ${newStatus ? 'activated' : 'deactivated'} successfully`)
    loadUsers()
    loadStats()
  } catch {
    toast.error(`Failed to ${action} user`)
  }
}

// ─── Manage Roles ────────────────────────────────────────────────────────────

async function openRolesModal(user: User) {
  selectedUser.value = user
  try {
    const response = await userService.getUserRoles(user.MbrID)
    if (response?.data) {
      userRoles.value = response.data.roles || []
    }
    rolesModal.open()
  } catch {
    toast.error('Failed to load user roles')
  }
}

async function handleAssignRoles() {
  if (!selectedUser.value) return
  try {
    const roleIds = userRoles.value.map((r) => r.RoleID)
    await userService.assignRoles(selectedUser.value.MbrID, {
      role_ids: roleIds,
      replace: true,
    })
    toast.success('Roles updated successfully')
    rolesModal.close()
    loadUsers()
  } catch {
    toast.error('Failed to update roles')
  }
}

function toggleRole(role: Role) {
  const index = userRoles.value.findIndex((r) => r.RoleID === role.id)
  if (index >= 0) {
    userRoles.value.splice(index, 1)
  } else {
    userRoles.value.push({
      RoleID: role.id,
      RoleName: role.name,
      Description: role.description,
      IsActive: true,
      StartDate: null,
      EndDate: null,
    })
  }
}

function hasRole(roleId: number): boolean {
  return userRoles.value.some((r) => r.RoleID === roleId)
}

// ─── Reset Password ──────────────────────────────────────────────────────────

function openPasswordModal(user: User) {
  selectedUser.value = user
  resetPasswordForm.newPassword = ''
  resetPasswordForm.confirmPassword = ''
  passwordModal.open()
}

async function handleResetPassword() {
  if (!selectedUser.value) return
  if (!resetPasswordForm.newPassword) {
    toast.error('New password is required')
    return
  }
  if (resetPasswordForm.newPassword !== resetPasswordForm.confirmPassword) {
    toast.error('Passwords do not match')
    return
  }
  if (resetPasswordForm.newPassword.length < 8) {
    toast.error('Password must be at least 8 characters')
    return
  }

  try {
    await userService.resetPassword(selectedUser.value.MbrID, resetPasswordForm.newPassword)
    toast.success('Password reset successfully')
    passwordModal.close()
  } catch {
    toast.error('Failed to reset password')
  }
}

// ─── View Activity ───────────────────────────────────────────────────────────

async function openActivityModal(user: User) {
  selectedUser.value = user
  try {
    const response = await userService.getActivityLog(user.MbrID, 1, 50)
    if (response?.data) {
      userActivity.value = response.data.data || []
    }
    activityModal.open()
  } catch {
    toast.error('Failed to load activity log')
  }
}

// ─── Bulk Actions ────────────────────────────────────────────────────────────

function openBulkActionModal(action: 'activate' | 'deactivate' | 'revoke') {
  bulkAction.value = action
  bulkActionModal.open()
}

async function handleBulkAction() {
  const ids = Array.from(selectedUsers.value)
  
  try {
    if (bulkAction.value === 'activate') {
      await userService.bulkToggleActive(ids, true)
      toast.success(`${ids.length} users activated`)
    } else if (bulkAction.value === 'deactivate') {
      await userService.bulkToggleActive(ids, false)
      toast.success(`${ids.length} users deactivated`)
    } else if (bulkAction.value === 'revoke') {
      await userService.bulkRevokeAccess(ids)
      toast.success(`${ids.length} users access revoked`)
    }
    selectedUsers.value.clear()
    bulkActionModal.close()
    loadUsers()
    loadStats()
  } catch {
    toast.error('Bulk action failed')
  }
}

// ─── Navigation ──────────────────────────────────────────────────────────────

function viewUserDetail(user: User) {
  router.push(`/users/${user.MbrID}`)
}

function navigateToRoles() {
  router.push('/users/roles')
}
</script>

<template>
  <div class="user-management">
    <!-- Header -->
    <ChPageHeader title="User Management" subtitle="Manage system users, roles, and permissions">
      <template #actions>
        <ChButton variant="secondary" left-icon="shield" @click="navigateToRoles">
          Manage Roles
        </ChButton>
        <ChButton variant="primary" left-icon="plus" @click="grantAccessModal.open">
          Grant Access
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- Statistics Cards -->
    <div v-if="stats" class="stats-grid">
      <ChCard class="stat-card">
        <div class="stat-value">{{ stats.total_users }}</div>
        <div class="stat-label">Total Users</div>
      </ChCard>
      <ChCard class="stat-card stat-active">
        <div class="stat-value">{{ stats.active_users }}</div>
        <div class="stat-label">Active Users</div>
      </ChCard>
      <ChCard class="stat-card stat-inactive">
        <div class="stat-value">{{ stats.inactive_users }}</div>
        <div class="stat-label">Inactive Users</div>
      </ChCard>
      <ChCard class="stat-card stat-today">
        <div class="stat-value">{{ stats.logged_in_today }}</div>
        <div class="stat-label">Logged In Today</div>
      </ChCard>
    </div>

    <!-- Filters & Search -->
    <ChCard class="filters-card">
      <div class="filters-row">
        <div class="search-box">
          <Search :size="18" class="search-icon" />
          <ChInput
            v-model="filters.search"
            placeholder="Search users by name, email, or username..."
            @keyup.enter="applyFilters"
          />
        </div>
        <ChButton
          variant="ghost"
          left-icon="filter"
          :class="{ active: showFilters || activeFiltersCount > 0 }"
          @click="showFilters = !showFilters"
        >
          Filters {{ activeFiltersCount > 0 ? `(${activeFiltersCount})` : '' }}
        </ChButton>
        <ChButton variant="primary" left-icon="search" @click="applyFilters">
          Search
        </ChButton>
      </div>

      <!-- Expanded Filters -->
      <div v-if="showFilters" class="filters-expanded">
        <div class="filter-group">
          <label>Status</label>
          <ChSelect
            v-model="filters.is_active"
            :options="[
              { value: 'all', label: 'All' },
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
            ]"
          />
        </div>
        <div class="filter-actions">
          <ChButton variant="ghost" @click="clearFilters">Clear All</ChButton>
          <ChButton variant="primary" @click="applyFilters">Apply Filters</ChButton>
        </div>
      </div>
    </ChCard>

    <!-- Bulk Actions -->
    <div v-if="hasSelectedUsers" class="bulk-actions-bar">
      <span class="bulk-count">{{ selectedUsers.size }} selected</span>
      <div class="bulk-buttons">
        <ChButton variant="primary" size="sm" left-icon="check" @click="openBulkActionModal('activate')">
          Activate
        </ChButton>
        <ChButton variant="secondary" size="sm" left-icon="x" @click="openBulkActionModal('deactivate')">
          Deactivate
        </ChButton>
        <ChButton variant="danger" size="sm" left-icon="user-x" @click="openBulkActionModal('revoke')">
          Revoke Access
        </ChButton>
      </div>
    </div>

    <!-- Users Table -->
    <ChCard>
      <ChTable
        :rows="users"
        :columns="[
          { key: 'select', label: '', width: '40px' },
          { key: 'user', label: 'User', sortable: true },
          { key: 'username', label: 'Username', sortable: true },
          { key: 'status', label: 'Status', width: '100px' },
          { key: 'last_login', label: 'Last Login', sortable: true },
          { key: 'roles', label: 'Roles' },
          { key: 'actions', label: '', width: '120px' },
        ]"
        :loading="loading"
        @sort="handleSort"
      >
        <!-- Select Cell -->
        <template #cell-select="{ row }">
          <input
            type="checkbox"
            :checked="selectedUsers.has((row as User).MbrID)"
            @change="toggleSelectUser((row as User).MbrID)"
          />
        </template>

        <!-- User Cell -->
        <template #cell-user="{ row }">
          <div class="user-cell">
            <ChAvatar :name="(row as User).FullName" :src="(row as User).ProfilePhoto || undefined" size="md" />
            <div class="user-info">
              <div class="user-name">{{ (row as User).FullName }}</div>
              <div class="user-email">{{ (row as User).MbrEmail }}</div>
            </div>
          </div>
        </template>

        <!-- Username Cell -->
        <template #cell-username="{ row }">
          <code v-if="(row as User).Username" class="username">{{ (row as User).Username }}</code>
          <span v-else class="text-muted">—</span>
        </template>

        <!-- Status Cell -->
        <template #cell-status="{ row }">
          <ChBadge :variant="stats && stats.active_users / stats.total_users > 0.8 ? 'primary' : 'secondary'">
            {{ (row as User).IsActive ? 'Active' : 'Inactive' }}
          </ChBadge>
        </template>

        <!-- Last Login Cell -->
        <template #cell-last_login="{ row }">
          <span v-if="(row as User).LastLoginAt" class="last-login">
            {{ new Date((row as User).LastLoginAt!).toLocaleDateString() }}
          </span>
          <span v-else class="text-muted">Never</span>
        </template>

        <!-- Roles Cell -->
        <template #cell-roles="{ row }">
          <div v-if="(row as User).roles && (row as User).roles!.length > 0" class="roles-list">
            <ChBadge
              v-for="role in (row as User).roles!.slice(0, 2)"
              :key="role.RoleID"
              variant="info"
              size="sm"
            >
              {{ role.RoleName }}
            </ChBadge>
            <span v-if="(row as User).roles!.length > 2" class="more-roles">
              +{{ (row as User).roles!.length - 2 }}
            </span>
          </div>
          <span v-else class="text-muted">No roles</span>
        </template>

        <!-- Actions Cell -->
        <template #cell-actions="{ row }">
          <div class="action-buttons">
            <ChButton
              variant="ghost"
              size="sm"
              :left-icon="(row as User).IsActive ? 'lock' : 'unlock'"
              @click="toggleUserActive(row as User)"
              :title="(row as User).IsActive ? 'Deactivate' : 'Activate'"
            />
            <ChButton
              variant="ghost"
              size="sm"
              left-icon="shield"
              @click="openRolesModal(row as User)"
              title="Manage Roles"
            />
            <ChButton
              variant="ghost"
              size="sm"
              left-icon="key"
              @click="openPasswordModal(row)"
              title="Reset Password"
            />
            <ChButton
              variant="ghost"
              size="sm"
              left-icon="more-vertical"
              class="dropdown-trigger"
            >
              <template #dropdown>
                <div class="dropdown-menu">
                  <button class="dropdown-item" @click="viewUserDetail(row)">
                    <Activity :size="14" /> View Activity
                  </button>
                  <button class="dropdown-item" @click="openActivityModal(row)">
                    <RefreshCw :size="14" /> View Log
                  </button>
                  <hr />
                  <button class="dropdown-item danger" @click="openRevokeModal(row)">
                    <UserX :size="14" /> Revoke Access
                  </button>
                </div>
              </template>
            </ChButton>
          </div>
        </template>
      </ChTable>

      <!-- Empty State -->
      <ChEmptyState
        v-if="!loading && users.length === 0"
        icon="users"
        title="No users found"
        description="No members have been granted system access yet."
      >
        <ChButton variant="primary" left-icon="plus" @click="grantAccessModal.open">
          Grant Access
        </ChButton>
      </ChEmptyState>

      <!-- Pagination -->
      <ChPagination
        v-if="totalPages > 1"
        :page="currentPage"
        :total="totalPages"
        @update:page="loadUsers"
      />
    </ChCard>

    <!-- Grant Access Modal -->
    <ChModal :open="grantAccessModal.isOpen.value" title="Grant System Access" size="lg" @close="grantAccessModal.close">
      <div class="grant-access-form">
        <div class="form-section">
          <h4>1. Select Member</h4>
          <div class="member-search">
            <ChInput
              v-model="memberSearchQuery"
              placeholder="Search members by name or email..."
              @keyup.enter="searchEligibleMembers"
            />
            <ChButton @click="searchEligibleMembers">Search</ChButton>
          </div>
          
          <div v-if="eligibleMembers.length > 0" class="members-list">
            <div
              v-for="member in eligibleMembers"
              :key="member.MbrID"
              class="member-item"
              :class="{ selected: grantForm.mbr_id === member.MbrID, disabled: member.HasLogin }"
              @click="!member.HasLogin && selectMemberForGrant(member)"
            >
              <ChAvatar :name="member.FullName" size="sm" />
              <div class="member-info">
                <div class="member-name">{{ member.FullName }}</div>
                <div class="member-email">{{ member.MbrEmail }}</div>
              </div>
              <ChBadge v-if="member.HasLogin" variant="success" size="sm">Has Access</ChBadge>
            </div>
          </div>
          <div v-else-if="memberSearchQuery" class="no-results">
            No eligible members found
          </div>
        </div>

        <div v-if="grantForm.mbr_id" class="form-section">
          <h4>2. Account Details</h4>
          <ChFormField label="Username" required>
            <ChInput v-model="grantForm.username" placeholder="Enter username" />
          </ChFormField>
          <ChFormField label="Password" required>
            <ChInput
              v-model="grantForm.password"
              type="password"
              placeholder="Enter password (min 8 characters)"
            />
          </ChFormField>
          <ChFormField label="Confirm Password" required>
            <ChInput
              v-model="grantForm.confirmPassword"
              type="password"
              placeholder="Confirm password"
            />
          </ChFormField>
        </div>

        <div v-if="grantForm.mbr_id" class="form-section">
          <h4>3. Assign Roles (Optional)</h4>
          <div class="roles-selection">
            <label
              v-for="role in availableRoles"
              :key="role.id"
              class="role-checkbox"
            >
              <input
                type="checkbox"
                :value="role.id"
                v-model="grantForm.role_ids"
              />
              <span class="role-name">{{ role.name }}</span>
              <span v-if="role.description" class="role-desc">{{ role.description }}</span>
            </label>
          </div>
        </div>
      </div>

      <template #footer>
        <ChButton variant="ghost" @click="grantAccessModal.close">Cancel</ChButton>
        <ChButton
          variant="primary"
          :disabled="!grantForm.mbr_id || !grantForm.username || !grantForm.password"
          @click="handleGrantAccess"
        >
          Grant Access
        </ChButton>
      </template>
    </ChModal>

    <!-- Revoke Access Modal -->
    <ChModal :open="revokeModal.isOpen.value" title="Revoke System Access" size="sm" @close="revokeModal.close">
      <div class="confirm-message">
        <p>Are you sure you want to revoke system access for <strong>{{ selectedUser?.FullName }}</strong>?</p>
        <p class="warning">This will permanently remove their login credentials and they will no longer be able to access the system.</p>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="revokeModal.close">Cancel</ChButton>
        <ChButton variant="danger" @click="handleRevokeAccess">Revoke Access</ChButton>
      </template>
    </ChModal>

    <!-- Manage Roles Modal -->
    <ChModal :open="rolesModal.isOpen.value" title="Manage User Roles" size="md" @close="rolesModal.close">
      <div v-if="selectedUser" class="roles-modal-content">
        <p>Managing roles for <strong>{{ selectedUser.FullName }}</strong></p>
        <div class="available-roles">
          <label
            v-for="role in availableRoles"
            :key="role.id"
            class="role-checkbox"
          >
            <input
              type="checkbox"
              :checked="hasRole(role.id)"
              @change="toggleRole(role)"
            />
            <span class="role-name">{{ role.name }}</span>
            <span v-if="role.description" class="role-desc">{{ role.description }}</span>
          </label>
        </div>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="rolesModal.close">Cancel</ChButton>
        <ChButton variant="primary" @click="handleAssignRoles">Save Roles</ChButton>
      </template>
    </ChModal>

    <!-- Reset Password Modal -->
    <ChModal :open="passwordModal.isOpen.value" title="Reset Password" size="sm" @close="passwordModal.close">
      <div v-if="selectedUser" class="password-form">
        <p>Reset password for <strong>{{ selectedUser.FullName }}</strong></p>
        <ChFormField label="New Password" required>
          <ChInput
            v-model="resetPasswordForm.newPassword"
            type="password"
            placeholder="Enter new password (min 8 characters)"
          />
        </ChFormField>
        <ChFormField label="Confirm Password" required>
          <ChInput
            v-model="resetPasswordForm.confirmPassword"
            type="password"
            placeholder="Confirm new password"
          />
        </ChFormField>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="passwordModal.close">Cancel</ChButton>
        <ChButton variant="primary" @click="handleResetPassword">Reset Password</ChButton>
      </template>
    </ChModal>

    <!-- Activity Log Modal -->
    <ChModal :open="activityModal.isOpen.value" title="User Activity Log" size="lg" @close="activityModal.close">
      <div v-if="selectedUser" class="activity-log">
        <p>Recent activity for <strong>{{ selectedUser.FullName }}</strong></p>
        <table class="activity-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Activity</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="activity in userActivity" :key="activity.LogID">
              <td>{{ new Date(activity.CreatedAt).toLocaleString() }}</td>
              <td>{{ activity.ActivityType }}</td>
              <td>{{ activity.Description }}</td>
            </tr>
          </tbody>
        </table>
        <ChEmptyState
          v-if="userActivity.length === 0"
          icon="search"
          title="No activity recorded"
          description="This user has no recent activity."
        />
      </div>
    </ChModal>

    <!-- Bulk Action Modal -->
    <ChModal :open="bulkActionModal.isOpen.value" :title="`Bulk ${bulkAction} Users`" size="sm" @close="bulkActionModal.close">
      <div class="confirm-message">
        <p>Are you sure you want to {{ bulkAction }} <strong>{{ selectedUsers.size }} users</strong>?</p>
        <p v-if="bulkAction === 'revoke'" class="warning">
          This will permanently remove their login credentials.
        </p>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="bulkActionModal.close">Cancel</ChButton>
        <ChButton
          :variant="bulkAction === 'revoke' ? 'danger' : 'primary'"
          @click="handleBulkAction"
        >
          Confirm {{ bulkAction }}
        </ChButton>
      </template>
    </ChModal>
  </div>
</template>

<style scoped>
.user-management {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--ch-space-4);
}

.stat-card {
  padding: var(--ch-space-4);
  text-align: center;
}

.stat-value {
  font-size: 2rem;
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-primary);
}

.stat-label {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin-top: var(--ch-space-1);
}

.stat-active .stat-value {
  color: var(--ch-color-success);
}

.stat-inactive .stat-value {
  color: var(--ch-color-warning);
}

.stat-today .stat-value {
  color: var(--ch-color-info);
}

/* Filters */
.filters-card {
  padding: var(--ch-space-4);
}

.filters-row {
  display: flex;
  gap: var(--ch-space-3);
  align-items: center;
}

.search-box {
  flex: 1;
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  background: var(--ch-color-surface-elevated);
  border-radius: var(--ch-radius-md);
  padding: var(--ch-space-2) var(--ch-space-3);
}

.search-icon {
  color: var(--ch-color-text-muted);
}

.filters-expanded {
  margin-top: var(--ch-space-4);
  padding-top: var(--ch-space-4);
  border-top: 1px solid var(--ch-color-border);
  display: flex;
  gap: var(--ch-space-4);
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
}

.filter-group label {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}

.filter-actions {
  margin-left: auto;
  display: flex;
  gap: var(--ch-space-2);
}

/* Bulk Actions */
.bulk-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--ch-space-3) var(--ch-space-4);
  background: var(--ch-color-surface-elevated);
  border-radius: var(--ch-radius-md);
  border: 1px solid var(--ch-color-border);
}

.bulk-count {
  font-weight: var(--ch-font-medium);
}

.bulk-buttons {
  display: flex;
  gap: var(--ch-space-2);
}

/* User Cell */
.user-cell {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: var(--ch-font-medium);
}

.user-email {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}

.username {
  background: var(--ch-color-surface-elevated);
  padding: var(--ch-space-1) var(--ch-space-2);
  border-radius: var(--ch-radius-sm);
  font-size: var(--ch-text-sm);
}

/* Roles */
.roles-list {
  display: flex;
  gap: var(--ch-space-1);
  flex-wrap: wrap;
  align-items: center;
}

.more-roles {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: var(--ch-space-1);
}

/* Dropdown */
.dropdown-menu {
  min-width: 180px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  padding: var(--ch-space-2) var(--ch-space-3);
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  cursor: pointer;
  font-size: var(--ch-text-sm);
}

.dropdown-item:hover {
  background: var(--ch-color-surface-elevated);
}

.dropdown-item.danger {
  color: var(--ch-color-danger);
}

.dropdown-item.danger:hover {
  background: var(--ch-color-danger-subtle);
}

/* Grant Access Form */
.grant-access-form {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
}

.form-section h4 {
  margin-bottom: var(--ch-space-3);
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
}

.member-search {
  display: flex;
  gap: var(--ch-space-2);
  margin-bottom: var(--ch-space-3);
}

.members-list {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-md);
}

.member-item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  padding: var(--ch-space-3);
  cursor: pointer;
  border-bottom: 1px solid var(--ch-color-border);
}

.member-item:last-child {
  border-bottom: none;
}

.member-item:hover:not(.disabled) {
  background: var(--ch-color-surface-elevated);
}

.member-item.selected {
  background: var(--ch-color-primary-subtle);
}

.member-item.disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.member-info {
  flex: 1;
}

.member-name {
  font-weight: var(--ch-font-medium);
}

.member-email {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}

.no-results {
  text-align: center;
  padding: var(--ch-space-4);
  color: var(--ch-color-text-muted);
}

/* Roles Selection */
.roles-selection,
.available-roles {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
}

.role-checkbox {
  display: flex;
  align-items: flex-start;
  gap: var(--ch-space-2);
  padding: var(--ch-space-2);
  border-radius: var(--ch-radius-sm);
  cursor: pointer;
}

.role-checkbox:hover {
  background: var(--ch-color-surface-elevated);
}

.role-name {
  font-weight: var(--ch-font-medium);
}

.role-desc {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin-left: var(--ch-space-1);
}

/* Confirm Messages */
.confirm-message {
  text-align: center;
}

.confirm-message p {
  margin-bottom: var(--ch-space-3);
}

.confirm-message .warning {
  color: var(--ch-color-warning);
  font-size: var(--ch-text-sm);
}

/* Activity Log */
.activity-log {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

.activity-table {
  width: 100%;
  border-collapse: collapse;
}

.activity-table th,
.activity-table td {
  padding: var(--ch-space-3);
  text-align: left;
  border-bottom: 1px solid var(--ch-color-border);
}

.activity-table th {
  font-weight: var(--ch-font-semibold);
  background: var(--ch-color-surface-elevated);
}

/* Password Form */
.password-form {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

/* Text Utilities */
.text-muted {
  color: var(--ch-color-text-muted);
}

.last-login {
  font-size: var(--ch-text-sm);
}

.mt-4 {
  margin-top: var(--ch-space-4);
}
</style>
