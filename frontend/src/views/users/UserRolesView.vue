<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { roleService } from '@/services/role.service'
import { useToast, useModal } from '@/design-system'
import type { Role, RoleWithPermissions, Permission } from '@/types/role'
// Import only what we need from role, not from operations to avoid conflicts

// Components
import {
  ChPageHeader,
  ChCard,
  ChButton,
  ChInput,
  ChTextarea,
  ChTable,
  ChBadge,
  ChModal,
  ChFormField,
  ChTabs,
  ChEmptyState,
  ChSwitch,
} from '@/design-system'

// Icons
import {
  Search,
  Shield,
  Check,
  X,
  Grid3X3,
  List,
} from '@lucide/vue'

// Router
const router = useRouter()

// Composables
const toast = useToast()

// ─── State ─────────────────────────────────────────────────────────────────

const activeTab = ref('roles')
const loading = ref(false)

// Roles
const roles = ref<Role[]>([])
const selectedRole = ref<RoleWithPermissions | null>(null)
const roleSearch = ref('')

// Permissions
const permissions = ref<Permission[]>([])
const permissionMatrix = ref<Map<number, Set<number>> | null>(new Map())

// Modals
const roleModal = useModal()
const deleteModal = useModal()

// Forms
const roleForm = reactive({
  id: null as number | null,
  name: '',
  description: '',
  is_active: true,
})

const selectedPermissions = ref<Set<number>>(new Set())

// Permission categories for grouping
const permissionCategories = computed(() => {
  const cats = new Map<string, Permission[]>()
  permissions.value.forEach((p) => {
    const category = p.name.split('.')[0] || 'general'
    if (!cats.has(category)) {
      cats.set(category, [])
    }
    cats.get(category)!.push(p)
  })
  return cats
})

// ─── Load Data ─────────────────────────────────────────────────────────────

async function loadRoles() {
  loading.value = true
  try {
    const response = await roleService.list()
    if (response?.data) {
      roles.value = response.data.data || []
    }
  } catch {
    toast.error('Failed to load roles')
  } finally {
    loading.value = false
  }
}

async function loadPermissions() {
  try {
    const response = await roleService.listPermissions()
    if (response?.data) {
      permissions.value = response.data || []
    }
  } catch{
    toast.error('Failed to load permissions')
  }
}

async function loadPermissionMatrix() {
  try {
    const response = await roleService.getPermissionMatrix()
    if (response?.data) {
      const matrix = new Map<number, Set<number>>()
      response.data.forEach((item: { role: Role; permissions: Record<string, boolean> }) => {
        const perms = new Set<number>()
        Object.entries(item.permissions).forEach(([permName, has]) => {
          if (has) {
            // Find permission ID by name
            const perm = permissions.value.find(p => p.name === permName)
            if (perm) perms.add(perm.id)
          }
        })
        matrix.set(item.role.id, perms)
      })
      permissionMatrix.value = matrix
    }
  } catch{
    toast.error('Failed to load permission matrix')
  }
}

onMounted(() => {
  loadRoles()
  loadPermissions()
  loadPermissionMatrix()
})

// ─── Computed ────────────────────────────────────────────────────────────────

const filteredRoles = computed(() => {
  if (!roleSearch.value) return roles.value
  const query = roleSearch.value.toLowerCase()
  return roles.value.filter(
    (r) =>
      r.name.toLowerCase().includes(query) ||
      r.description?.toLowerCase().includes(query),
  )
})

const isEditingRole = computed(() => roleForm.id !== null)

// ─── Role CRUD ───────────────────────────────────────────────────────────────

function openCreateRoleModal() {
  roleForm.id = null
  roleForm.name = ''
  roleForm.description = ''
  roleForm.is_active = true
  selectedPermissions.value.clear()
  roleModal.open()
}

async function openEditRoleModal(role: Role) {
  roleForm.id = role.id
  roleForm.name = role.name
  roleForm.description = role.description || ''
  roleForm.is_active = role.is_active

  // Load role permissions
  try {
    const response = await roleService.get(role.id)
    if (response?.data) {
      selectedRole.value = response.data
      selectedPermissions.value = new Set(response.data.permissions.map((p: string) => {
        const perm = permissions.value.find(perm => perm.name === p)
        return perm?.id || 0
      }).filter((id: number) => id > 0))
    }
  } catch {
    toast.error('Failed to load role details')
    return
  }

  roleModal.open()
}

async function saveRole() {
  if (!roleForm.name.trim()) {
    toast.error('Role name is required')
    return
  }

  try {
    if (isEditingRole.value) {
      await roleService.update(roleForm.id!, {
        name: roleForm.name,
        description: roleForm.description,
        is_active: roleForm.is_active,
        permission_ids: Array.from(selectedPermissions.value),
      })
      toast.success('Role updated successfully')
    } else {
      await roleService.create({
        name: roleForm.name,
        description: roleForm.description,
        is_active: roleForm.is_active,
        permission_ids: Array.from(selectedPermissions.value),
      })
      toast.success('Role created successfully')
    }
    roleModal.close()
    loadRoles()
    loadPermissionMatrix()
  } catch (error: any) {
    toast.error(error?.response?.data?.message || 'Failed to save role')
  }
}

function openDeleteModal(role: Role) {
  selectedRole.value = role as RoleWithPermissions
  deleteModal.open()
}

async function deleteRole() {
  if (!selectedRole.value) return

  try {
    await roleService.delete(selectedRole.value.id)
    toast.success('Role deleted successfully')
    deleteModal.close()
    loadRoles()
    loadPermissionMatrix()
  } catch (error: any) {
    toast.error(error?.response?.data?.message || 'Failed to delete role')
  }
}

// ─── Permission Management ─────────────────────────────────────────────────

function togglePermission(permissionId: number) {
  if (selectedPermissions.value.has(permissionId)) {
    selectedPermissions.value.delete(permissionId)
  } else {
    selectedPermissions.value.add(permissionId)
  }
}

function hasPermission(roleId: number, permissionId: number): boolean {
  return permissionMatrix.value?.get(roleId)?.has(permissionId) || false
}

async function toggleRolePermission(role: Role, permission: Permission) {
  const currentPerms = permissionMatrix.value?.get(role.id) || new Set()
  const newPerms = new Set(currentPerms)

  if (newPerms.has(permission.id)) {
    newPerms.delete(permission.id)
  } else {
    newPerms.add(permission.id)
  }

  try {
    await roleService.update(role.id, {
      permission_ids: Array.from(newPerms) as number[],
    })
    if (permissionMatrix.value) {
      permissionMatrix.value.set(role.id, newPerms)
    }
    toast.success('Permission updated')
  } catch {
    toast.error('Failed to update permission')
  }
}

function selectAllInCategory(category: string) {
  const perms = permissionCategories.value.get(category) || []
  perms.forEach((p) => selectedPermissions.value.add(p.id))
}

function deselectAllInCategory(category: string) {
  const perms = permissionCategories.value.get(category) || []
  perms.forEach((p) => selectedPermissions.value.delete(p.id))
}

// ─── Navigation ─────────────────────────────────────────────────────────────

function goBack() {
  router.push('/users')
}
</script>

<template>
  <div class="roles-management">
    <!-- Header -->
    <ChPageHeader title="Roles & Permissions" subtitle="Manage user roles and their permissions">
      <template #leading>
        <ChButton variant="ghost" size="sm" left-icon="arrow-left" @click="goBack">
          Back to Users
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- Tabs -->
    <ChTabs v-model="activeTab" :tabs="[
      { label: 'Roles', value: 'roles', icon: Shield },
      { label: 'Permission Matrix', value: 'matrix', icon: Grid3X3 },
      { label: 'All Permissions', value: 'permissions', icon: List },
    ]" />

    <!-- Tab Content -->
    <div v-show="activeTab === 'roles'" class="tab-content">
      <!-- Roles Management -->
      <ChCard>
          <div class="section-header">
            <div class="search-box">
              <Search :size="18" />
              <ChInput v-model="roleSearch" placeholder="Search roles..." />
            </div>
            <ChButton variant="primary" left-icon="plus" @click="openCreateRoleModal">
              Create Role
            </ChButton>
          </div>

          <ChTable
            :rows="filteredRoles"
            :columns="[
              { key: 'name', label: 'Role Name', sortable: true },
              { key: 'description', label: 'Description' },
              { key: 'status', label: 'Status', width: '100px' },
              { key: 'permissions', label: 'Permissions' },
              { key: 'actions', label: '', width: '120px' },
            ]"
            :loading="loading"
          >
            <template #cell-name="{ row }">
              <div class="role-name-cell">
                <Shield :size="18" class="role-icon" />
                <span class="font-medium">{{ (row as Role).name }}</span>
              </div>
            </template>

            <template #cell-description="{ row }">
              <span class="text-muted">{{ (row as Role).description || 'No description' }}</span>
            </template>

            <template #cell-status="{ row }">
              <ChBadge :variant="(row as Role).is_active ? 'success' : 'secondary'">
                {{ (row as Role).is_active ? 'Active' : 'Inactive' }}
              </ChBadge>
            </template>

            <template #cell-permissions="{ row }">
              <ChBadge variant="info" size="sm">
                {{ permissionMatrix?.get((row as Role).id)?.size || 0 }} permissions
              </ChBadge>
            </template>

            <template #cell-actions="{ row }">
              <div class="action-buttons">
                <ChButton variant="ghost" size="sm" left-icon="edit-2" @click="openEditRoleModal(row as Role)">
                  Edit
                </ChButton>
                <ChButton
                  variant="ghost"
                  size="sm"
                  left-icon="trash-2"
                  class="danger"
                  @click="openDeleteModal(row as Role)"
                >
                  Delete
                </ChButton>
              </div>
            </template>
          </ChTable>

          <ChEmptyState
            v-if="!loading && filteredRoles.length === 0"
            icon="search"
            title="No roles found"
            description="Create your first role to get started."
          >
            <ChButton variant="primary" left-icon="plus" @click="openCreateRoleModal">
              Create Role
            </ChButton>
          </ChEmptyState>
        </ChCard>
    </div>

    <div v-show="activeTab === 'matrix'" class="tab-content">
      <!-- Permission Matrix -->
      <ChCard>
          <div class="matrix-header">
            <h3>Permission Matrix</h3>
            <p class="text-muted">
              Click on any cell to toggle a permission for a role. Changes are saved automatically.
            </p>
          </div>

          <div class="permission-matrix">
            <table class="matrix-table">
              <thead>
                <tr>
                  <th class="permission-header">Permission</th>
                  <th v-for="role in roles.filter((r) => r.is_active)" :key="role.id" class="role-header">
                    {{ role.name }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <template v-for="[category, perms] in permissionCategories" :key="category">
                  <tr class="category-row">
                    <td :colspan="roles.filter((r) => r.is_active).length + 1" class="category-cell">
                      <strong>{{ category.charAt(0).toUpperCase() + category.slice(1) }}</strong>
                    </td>
                  </tr>
                  <tr v-for="perm in perms" :key="perm.id" class="permission-row">
                    <td class="permission-name">
                      {{ perm.name }}
                      <span v-if="perm.description" class="perm-desc">{{ perm.description }}</span>
                    </td>
                    <td
                      v-for="role in roles.filter((r) => r.is_active)"
                      :key="role.id"
                      class="matrix-cell"
                      :class="{ granted: hasPermission(role.id, perm.id) }"
                      @click="toggleRolePermission(role, perm)"
                    >
                      <Check v-if="hasPermission(role.id, perm.id)" :size="16" class="check-icon" />
                      <X v-else :size="16" class="x-icon" />
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <ChEmptyState
            v-if="roles.length === 0"
            icon="search"
            title="No roles to display"
            description="Create roles and permissions to build the permission matrix."
          />
        </ChCard>
    </div>

    <div v-show="activeTab === 'permissions'" class="tab-content">
      <!-- Permissions List -->
      <ChCard>
          <div class="permissions-list">
            <div
              v-for="[category, perms] in permissionCategories"
              :key="category"
              class="permission-category"
            >
              <h4 class="category-title">
                {{ category.charAt(0).toUpperCase() + category.slice(1) }}
              </h4>
              <div class="category-perms">
                <div v-for="perm in perms" :key="perm.id" class="permission-item">
                  <code class="perm-name">{{ perm.name }}</code>
                  <span v-if="perm.description" class="perm-description">{{ perm.description }}</span>
                </div>
              </div>
            </div>
          </div>

          <ChEmptyState
            v-if="permissions.length === 0"
            icon="search"
            title="No permissions"
            description="System permissions will appear here."
          />
        </ChCard>
    </div>

    <!-- Create/Edit Role Modal -->
    <ChModal :open="roleModal.isOpen.value" :title="isEditingRole ? 'Edit Role' : 'Create Role'" size="lg" @close="roleModal.close">
      <div class="role-form">
        <ChFormField label="Role Name" required>
          <ChInput v-model="roleForm.name" placeholder="Enter role name" />
        </ChFormField>

        <ChFormField label="Description">
          <ChTextarea
            v-model="roleForm.description"
            placeholder="Enter role description"
            :rows="3"
          />
        </ChFormField>

        <ChFormField label="Active">
          <ChSwitch v-model="roleForm.is_active" />
        </ChFormField>

        <div class="permissions-section">
          <h4>Permissions</h4>
          <p class="text-muted">Select the permissions for this role</p>

          <div class="permissions-categories">
            <div
              v-for="[category, perms] in permissionCategories"
              :key="category"
              class="perm-category"
            >
              <div class="category-header">
                <h5>{{ category.charAt(0).toUpperCase() + category.slice(1) }}</h5>
                <div class="category-actions">
                  <ChButton
                    variant="ghost"
                    size="sm"
                    @click="selectAllInCategory(category)"
                  >
                    Select All
                  </ChButton>
                  <ChButton
                    variant="ghost"
                    size="sm"
                    @click="deselectAllInCategory(category)"
                  >
                    Deselect All
                  </ChButton>
                </div>
              </div>
              <div class="category-checkboxes">
                <label
                  v-for="perm in perms"
                  :key="perm.id"
                  class="perm-checkbox"
                >
                  <input
                    type="checkbox"
                    :checked="selectedPermissions.has(perm.id)"
                    @change="togglePermission(perm.id)"
                  />
                  <span class="perm-label">
                    <code>{{ perm.name }}</code>
                    <span v-if="perm.description" class="perm-desc">{{ perm.description }}</span>
                  </span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <ChButton variant="ghost" @click="roleModal.close">Cancel</ChButton>
        <ChButton variant="primary" left-icon="save" @click="saveRole">
          {{ isEditingRole ? 'Save Changes' : 'Create Role' }}
        </ChButton>
      </template>
    </ChModal>

    <!-- Delete Role Modal -->
    <ChModal :open="deleteModal.isOpen.value" title="Delete Role" size="sm" @close="deleteModal.close">
      <div class="delete-confirm">
        <p>Are you sure you want to delete the role <strong>{{ selectedRole?.name }}</strong>?</p>
        <p class="warning">This action cannot be undone. Users with this role will lose these permissions.</p>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="deleteModal.close">Cancel</ChButton>
        <ChButton variant="danger" left-icon="trash-2" @click="deleteRole">Delete Role</ChButton>
      </template>
    </ChModal>
  </div>
</template>

<style scoped>
.roles-management {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
}

/* Section Header */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--ch-space-4);
  gap: var(--ch-space-3);
}

.search-box {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  flex: 1;
  max-width: 400px;
  background: var(--ch-color-surface-elevated);
  border-radius: var(--ch-radius-md);
  padding: var(--ch-space-2) var(--ch-space-3);
}

/* Role Name Cell */
.role-name-cell {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.role-icon {
  color: var(--ch-color-primary);
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: var(--ch-space-1);
}

.action-buttons .danger {
  color: var(--ch-color-danger);
}

/* Permission Matrix */
.matrix-header {
  margin-bottom: var(--ch-space-4);
}

.matrix-header h3 {
  margin-bottom: var(--ch-space-2);
}

.permission-matrix {
  overflow-x: auto;
}

.matrix-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 600px;
}

.matrix-table th,
.matrix-table td {
  padding: var(--ch-space-3);
  text-align: center;
  border: 1px solid var(--ch-color-border);
}

.matrix-table th {
  background: var(--ch-color-surface-elevated);
  font-weight: var(--ch-font-semibold);
  position: sticky;
  top: 0;
  z-index: 1;
}

.permission-header {
  text-align: left !important;
  min-width: 250px;
}

.role-header {
  min-width: 100px;
}

.category-row {
  background: var(--ch-color-surface-elevated);
}

.category-cell {
  text-align: left;
  padding: var(--ch-space-2) var(--ch-space-3);
  font-size: var(--ch-text-sm);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.permission-name {
  text-align: left;
  font-size: var(--ch-text-sm);
}

.perm-desc {
  display: block;
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  margin-top: var(--ch-space-1);
}

.matrix-cell {
  cursor: pointer;
  transition: background 0.2s;
}

.matrix-cell:hover {
  background: var(--ch-color-surface-elevated);
}

.matrix-cell.granted {
  background: var(--ch-color-success-subtle);
}

.check-icon {
  color: var(--ch-color-success);
}

.x-icon {
  color: var(--ch-color-text-muted);
}

/* Permissions List */
.permissions-list {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
}

.permission-category {
  border-bottom: 1px solid var(--ch-color-border);
  padding-bottom: var(--ch-space-4);
}

.permission-category:last-child {
  border-bottom: none;
}

.category-title {
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-semibold);
  margin-bottom: var(--ch-space-3);
  color: var(--ch-color-primary);
}

.category-perms {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--ch-space-3);
}

.permission-item {
  display: flex;
  flex-direction: column;
  padding: var(--ch-space-3);
  background: var(--ch-color-surface-elevated);
  border-radius: var(--ch-radius-md);
}

.perm-name {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
}

.perm-description {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin-top: var(--ch-space-1);
}

/* Role Form */
.role-form {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

.permissions-section {
  border-top: 1px solid var(--ch-color-border);
  padding-top: var(--ch-space-4);
  margin-top: var(--ch-space-2);
}

.permissions-section h4 {
  margin-bottom: var(--ch-space-1);
}

.permissions-categories {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
  margin-top: var(--ch-space-4);
  max-height: 400px;
  overflow-y: auto;
}

.perm-category {
  border: 1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-md);
  padding: var(--ch-space-3);
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--ch-space-3);
}

.category-header h5 {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.category-actions {
  display: flex;
  gap: var(--ch-space-1);
}

.category-checkboxes {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: var(--ch-space-2);
}

.perm-checkbox {
  display: flex;
  align-items: flex-start;
  gap: var(--ch-space-2);
  padding: var(--ch-space-2);
  border-radius: var(--ch-radius-sm);
  cursor: pointer;
}

.perm-checkbox:hover {
  background: var(--ch-color-surface-elevated);
}

.perm-label {
  display: flex;
  flex-direction: column;
  font-size: var(--ch-text-sm);
}

.perm-label code {
  font-weight: var(--ch-font-medium);
}

.perm-label .perm-desc {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* Delete Confirm */
.delete-confirm {
  text-align: center;
}

.delete-confirm p {
  margin-bottom: var(--ch-space-3);
}

.delete-confirm .warning {
  color: var(--ch-color-warning);
  font-size: var(--ch-text-sm);
}

/* Text Utilities */
.text-muted {
  color: var(--ch-color-text-muted);
}

.font-medium {
  font-weight: var(--ch-font-medium);
}

.danger {
  color: var(--ch-color-danger);
}
</style>
