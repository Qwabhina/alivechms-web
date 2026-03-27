<script setup lang="ts">
/**
 * DocLayout.vue - Documentation Layout Component
 * 
 * This layout provides the structure for all design system documentation pages.
 * It includes a responsive sidebar with mobile drawer support, a topbar with
 * search, notifications, and user menu functionality.
 * 
 * Features:
 * - Mobile-responsive sidebar drawer with overlay
 * - Search modal for quick navigation
 * - Notification dropdown panel
 * - User menu dropdown
 * - Comprehensive navigation with icons using lucide-vue-next
 * 
 * @requires lucide-vue-next for iconography
 */

import { ref, computed, onMounted, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTheme } from '@/design-system'
import {
  Home,
  Palette,
  Box,
  FileInput,
  LayoutGrid,
  Compass,
  Bell,
  Search,
  User,
  Settings,
  LogOut,
  Menu,
  X,
  ChevronDown,
  ChevronLeft,
  Moon,
  Sun,
  Bookmark,
  HelpCircle,
  ExternalLink,
  BellRing,
  Check,
  AlertCircle,
  Info,
  MousePointer,
  Code,
  MessageSquare,
  ChevronDown as ChevronDownIcon,
  Database,
  Calendar,
  DollarSign,
} from 'lucide-vue-next'

// Type definitions for sidebar navigation items
interface NavItem {
  label: string
  to: string
  icon: Component
}

interface NavSection {
  id: string
  label: string
  icon: Component
  items: NavItem[]
}

const route = useRoute()
const router = useRouter()

// ============================================================
// REACTIVE STATE
// ============================================================

const isSidebarOpen = ref(false)
const isSearchOpen = ref(false)
const isNotificationsOpen = ref(false)
const isUserMenuOpen = ref(false)
const isMobile = ref(false)
const { isDarkMode, toggleDarkMode } = useTheme()
const searchQuery = ref('')
const expandedSections = ref<Record<string, boolean>>({
  'getting-started': true,
  components: false,
  patterns: false,
  resources: false,
})

// ============================================================
// SIDEBAR NAVIGATION DATA
// ============================================================

const sidebarSections: NavSection[] = [
  {
    id: 'getting-started',
    label: 'Getting Started',
    icon: Home,
    items: [
      { label: 'Introduction', to: '/docs/introduction', icon: Info },
      { label: 'Foundation', to: '/docs/foundation', icon: Palette },
      { label: 'Installation', to: '/docs/installation', icon: Settings },
    ]
  },
  {
    id: 'components',
    label: 'Components',
    icon: Box,
    items: [
      { label: 'Core', to: '/docs/core', icon: Box },
      { label: 'Forms & Flows', to: '/docs/forms', icon: FileInput },
      { label: 'Data Display', to: '/docs/data', icon: LayoutGrid },
      { label: 'Navigation', to: '/docs/navigation', icon: Compass },
      { label: 'UI Cues & Feedback', to: '/docs/feedback', icon: Bell },
      { label: 'Interactive', to: '/docs/interactive', icon: MousePointer },
    ]
  },
  {
    id: 'composables',
    label: 'Composables & Utils',
    icon: Code,
    items: [
      { label: 'useValidation', to: '/docs/composables#use-validation', icon: Check },
      { label: 'useLocalStorage', to: '/docs/composables#local-storage', icon: Database },
      { label: 'Date Utilities', to: '/docs/composables#date-utils', icon: Calendar },
      { label: 'Currency Formatting', to: '/docs/composables#currency', icon: DollarSign },
    ]
  },
  {
    id: 'patterns',
    label: 'Patterns',
    icon: Bookmark,
    items: [
      { label: 'Layout Patterns', to: '/docs/patterns/layout', icon: LayoutGrid },
      { label: 'Form Patterns', to: '/docs/patterns/forms', icon: FileInput },
      { label: 'Data Patterns', to: '/docs/patterns/data', icon: Box },
    ]
  },
  {
    id: 'resources',
    label: 'Resources',
    icon: HelpCircle,
    items: [
      { label: 'Icons', to: '/docs/resources/icons', icon: Box },
      { label: 'Utilities', to: '/docs/resources/utilities', icon: Settings },
      { label: 'Changelog', to: '/docs/changelog', icon: ExternalLink },
    ]
  }
]

// ============================================================
// NOTIFICATIONS DATA
// ============================================================

const notifications = ref([
  {
    id: 1,
    type: 'info',
    title: 'Design System Updated',
    message: 'Version 2.0.0 has been released with new components.',
    time: '2 hours ago',
    read: false,
  },
  {
    id: 2,
    type: 'warning',
    title: 'Component Deprecated',
    message: 'ChButton v1 will be removed in v3.0. Please migrate.',
    time: '1 day ago',
    read: false,
  },
  {
    id: 3,
    type: 'success',
    title: 'Documentation Updated',
    message: 'Foundation page has been expanded with new tokens.',
    time: '3 days ago',
    read: true,
  },
])

// ============================================================
// USER DATA
// ============================================================

const user = {
  name: 'Developer Mode',
  email: 'engineer@alivechms',
  avatar: 'https://i.pravatar.cc/150?img=11'
}

// ============================================================
// COMPUTED PROPERTIES
// ============================================================

const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.read).length
})

const searchResults = computed(() => {
  if (!searchQuery.value.trim()) return []

  const query = searchQuery.value.toLowerCase()
  const results: Array<{ label: string; to: string; section: string }> = []

  sidebarSections.forEach(section => {
    section.items.forEach(item => {
      if (item.label.toLowerCase().includes(query)) {
        results.push({
          label: item.label,
          to: item.to,
          section: section.label,
        })
      }
    })
  })

  return results.slice(0, 8)
})

// ============================================================
// METHODS
// ============================================================

function handleNavigate(to: string) {
  router.push(to)
  if (isMobile.value) {
    isSidebarOpen.value = false
  }
}

function toggleSection(sectionId: string) {
  expandedSections.value[sectionId] = !expandedSections.value[sectionId]
}

function toggleSearch() {
  isSearchOpen.value = !isSearchOpen.value
  if (isSearchOpen.value) {
    isNotificationsOpen.value = false
    isUserMenuOpen.value = false
  }
}

function toggleNotifications() {
  isNotificationsOpen.value = !isNotificationsOpen.value
  if (isNotificationsOpen.value) {
    isSearchOpen.value = false
    isUserMenuOpen.value = false
  }
}

function toggleUserMenu() {
  isUserMenuOpen.value = !isUserMenuOpen.value
  if (isUserMenuOpen.value) {
    isSearchOpen.value = false
    isNotificationsOpen.value = false
  }
}

function toggleSidebar() {
  isSidebarOpen.value = !isSidebarOpen.value
}

function closeSidebar() {
  isSidebarOpen.value = false
}

function toggleTheme() {
  toggleDarkMode()
}

function markAsRead(notificationId: number) {
  const notification = notifications.value.find(n => n.id === notificationId)
  if (notification) {
    notification.read = true
  }
}

function markAllAsRead() {
  notifications.value.forEach(n => {
    n.read = true
  })
}

function handleSearchResultClick(to: string) {
  router.push(to)
  isSearchOpen.value = false
  searchQuery.value = ''
}

function closeAllDropdowns() {
  isSearchOpen.value = false
  isNotificationsOpen.value = false
  isUserMenuOpen.value = false
}

function handleQuickLink(item: NavItem) {
  handleSearchResultClick(item.to)
}

// ============================================================
// LIFECYCLE HOOKS
// ============================================================

onMounted(() => {
  const checkMobile = () => {
    isMobile.value = window.innerWidth < 1024
    if (!isMobile.value) {
      isSidebarOpen.value = true // Open by default on desktop
    }
  }

  checkMobile()
  window.addEventListener('resize', checkMobile)

  document.addEventListener('click', (e: MouseEvent) => {
    const target = e.target as HTMLElement
    if (target.closest('.dropdown-container')) return

    closeAllDropdowns()
  })

  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
      closeAllDropdowns()
      if (isMobile.value) {
        isSidebarOpen.value = false
      }
    }
  })
})
</script>

<template>
  <div class="doc-layout" :class="{ 
    'sidebar-open': isSidebarOpen && isMobile,
    'sidebar-collapsed': !isSidebarOpen && !isMobile 
  }">
    <!-- Mobile Overlay -->
    <div v-if="isMobile && isSidebarOpen" class="sidebar-overlay" @click="closeSidebar"></div>

    <!-- Sidebar -->
    <aside class="doc-sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo">
          <span class="logo-icon">⬡</span>
          <span class="logo-text">AliveCHMS</span>
        </div>

        <button v-if="isMobile" class="sidebar-close-btn" @click="closeSidebar" aria-label="Close sidebar">
          <X :size="24" />
        </button>
      </div>

      <nav class="sidebar-nav">
        <div v-for="section in sidebarSections" :key="section.id" class="nav-section">
          <button class="section-header" @click="toggleSection(section.id)">
            <component :is="section.icon" :size="18" class="section-icon" />
            <span class="section-label">{{ section.label }}</span>
            <ChevronDown :size="16" class="section-chevron" :class="{ 'rotated': expandedSections[section.id] }" />
          </button>

          <Transition name="slide">
            <div v-if="expandedSections[section.id]" class="section-items">
              <RouterLink v-for="item in section.items" :key="item.to" :to="item.to" class="nav-item"
                :class="{ 'active': route.path === item.to }" @click="handleNavigate(item.to)">
                <component :is="item.icon" :size="16" class="nav-item-icon" />
                <span class="nav-item-label">{{ item.label }}</span>
              </RouterLink>
            </div>
          </Transition>
        </div>
      </nav>

      <div class="sidebar-footer">
        <button class="theme-toggle" @click="toggleTheme">
          <Moon v-if="!isDarkMode" :size="18" />
          <Sun v-else :size="18" />
          <span>{{ !isDarkMode ? 'Light Mode' : 'Dark Mode' }}</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="doc-main">
      <header class="doc-topbar">
        <div class="topbar-left">
          <button class="topbar-btn mobile-menu-btn" @click="toggleSidebar" aria-label="Toggle sidebar">
            <Menu :size="24" />
          </button>

          <div class="doc-topbar-title">
            <ChevronLeft :size="18" class="breadcrumb-icon" />
            <span>Design System</span>
          </div>
        </div>

        <div class="topbar-right">
          <!-- Search Button -->
          <button class="topbar-btn dropdown-trigger" @click.stop="toggleSearch" aria-label="Search">
            <Search :size="20" />
            <span class="btn-label">Search</span>
            <kbd class="kbd">⌘K</kbd>
          </button>

          <!-- Notifications Button -->
          <div class="dropdown-container">
            <button class="topbar-btn dropdown-trigger" :class="{ 'has-badge': unreadCount > 0 }"
              @click.stop="toggleNotifications" aria-label="Notifications">
              <Bell :size="20" />
              <span v-if="unreadCount > 0" class="notification-badge">
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </span>
            </button>

            <Transition name="dropdown">
              <div v-if="isNotificationsOpen" class="dropdown-panel notifications-panel" @click.stop>
                <div class="panel-header">
                  <h3>Notifications</h3>
                  <button v-if="unreadCount > 0" class="mark-all-btn" @click="markAllAsRead">
                    Mark all read
                  </button>
                </div>

                <div class="notifications-list">
                  <div v-for="notification in notifications" :key="notification.id" class="notification-item"
                    :class="{ 'unread': !notification.read }" @click="markAsRead(notification.id)">
                    <div class="notification-icon" :class="notification.type">
                      <BellRing v-if="notification.type === 'warning'" :size="16" />
                      <Check v-else-if="notification.type === 'success'" :size="16" />
                      <Info v-else :size="16" />
                    </div>
                    <div class="notification-content">
                      <div class="notification-title">{{ notification.title }}</div>
                      <div class="notification-message">{{ notification.message }}</div>
                      <div class="notification-time">{{ notification.time }}</div>
                    </div>
                    <div v-if="!notification.read" class="unread-dot"></div>
                  </div>
                </div>
              </div>
            </Transition>
          </div>

          <!-- User Menu -->
          <div class="dropdown-container">
            <button class="user-menu-trigger dropdown-trigger" @click.stop="toggleUserMenu" aria-label="User menu">
              <img :src="user.avatar" :alt="user.name" class="user-avatar" />
              <ChevronDown :size="16" />
            </button>

            <Transition name="dropdown">
              <div v-if="isUserMenuOpen" class="dropdown-panel user-panel" @click.stop>
                <div class="user-info">
                  <img :src="user.avatar" :alt="user.name" class="user-panel-avatar" />
                  <div>
                    <div class="user-name">{{ user.name }}</div>
                    <div class="user-email">{{ user.email }}</div>
                  </div>
                </div>

                <div class="dropdown-divider"></div>

                <button class="dropdown-item">
                  <User :size="16" />
                  <span>Profile</span>
                </button>
                <button class="dropdown-item">
                  <Settings :size="16" />
                  <span>Settings</span>
                </button>

                <div class="dropdown-divider"></div>

                <button class="dropdown-item danger">
                  <LogOut :size="16" />
                  <span>Sign Out</span>
                </button>
              </div>
            </Transition>
          </div>
        </div>
      </header>

      <main class="doc-content">
        <RouterView />
      </main>
    </div>

    <!-- Search Modal -->
    <Transition name="modal">
      <div v-if="isSearchOpen" class="search-modal" @click="closeAllDropdowns">
        <div class="search-container" @click.stop>
          <div class="search-input-wrapper">
            <Search :size="20" class="search-icon" />
            <input v-model="searchQuery" type="text" class="search-input" placeholder="Search documentation..."
              autofocus />
            <kbd class="kbd escape-kbd" @click="closeAllDropdowns">ESC</kbd>
          </div>

          <div v-if="searchResults.length > 0" class="search-results">
            <div class="results-label">Results</div>
            <button v-for="result in searchResults" :key="result.to" class="search-result-item"
              @click="handleSearchResultClick(result.to)">
              <div class="result-label">{{ result.label }}</div>
              <div class="result-section">{{ result.section }}</div>
            </button>
          </div>

          <div v-else-if="searchQuery.trim()" class="no-results">
            <AlertCircle :size="48" />
            <p>No results found for "{{ searchQuery }}"</p>
          </div>

          <div v-else class="search-hints">
            <p class="hints-label">Quick Links</p>
            <div class="hints-grid">
              <button v-for="item in (sidebarSections[0]?.items || []).slice(0, 4)" :key="item.to" class="hint-item"
                @click="handleQuickLink(item)">
                <component :is="item.icon" :size="16" />
                <span>{{ item.label }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* ============================================================
   LAYOUT CONTAINER
   ============================================================ */
.doc-layout {
  display: flex;
  height: 100vh;
  width: 100vw;
  overflow: hidden;
  background-color: var(--ch-color-bg-subtle);
  position: relative;
}

/* ============================================================
   MOBILE OVERLAY
   ============================================================ */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 40;
  backdrop-filter: blur(2px);
}

/* ============================================================
   SIDEBAR
   ============================================================ */
.doc-sidebar {
  width: 280px;
  height: 100vh;
  background: var(--ch-color-surface);
  border-right: 1px solid var(--ch-color-border-strong);
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 50;
  transition: transform 0.3s ease-in-out;
}

.doc-sidebar.sidebar-collapsed {
  width: 72px;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--ch-space-4) var(--ch-space-5);
  border-bottom: 1px solid var(--ch-color-border-strong);
  min-height: 64px;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.logo-icon {
  font-size: 24px;
  color: var(--ch-color-primary);
}

.logo-text {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-bold);
  letter-spacing: -0.02em;
  color: var(--ch-color-text);
  white-space: nowrap;
  overflow: hidden;
  transition: opacity 0.2s ease, width 0.2s ease;
}

.sidebar-collapsed .logo-text {
  opacity: 0;
  width: 0;
}
  
  .sidebar-close-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 1px solid var(--ch-color-border-strong);
    background: transparent;
    color: var(--ch-color-text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
  }
  
  .sidebar-close-btn:hover {
    background: var(--ch-color-bg-subtle);
    color: var(--ch-color-text);
  }
  
  /* ============================================================
               SIDEBAR NAVIGATION
               ============================================================ */
  .sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: var(--ch-space-4) 0;
  }
  
  .nav-section {
    margin-bottom: var(--ch-space-2);
  }
  
  .section-header {
    display: flex;
    align-items: center;
    gap: var(--ch-space-3);
    width: 100%;
    padding: var(--ch-space-3) var(--ch-space-5);
    background: transparent;
    border: none;
    color: var(--ch-color-text);
    font-size: var(--ch-text-sm);
    font-weight: var(--ch-font-semibold);
    cursor: pointer;
    transition: background 0.15s ease;
  }
  
  .section-header:hover {
    background: var(--ch-color-bg-subtle);
  }
  
  .section-icon {
    color: var(--ch-color-text-muted);
    flex-shrink: 0;
  }
  
  .section-label {
    flex: 1;
    text-align: left;
  }
  
.section-chevron {
  color: var(--ch-color-text-subtle);
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.sidebar-collapsed .section-label,
.sidebar-collapsed .section-chevron {
  opacity: 0;
  width: 0;
  pointer-events: none;
}
  
  .section-chevron.rotated {
    transform: rotate(180deg);
  }
  
  .section-items {
    overflow: hidden;
  }
  
  .nav-item {
    display: flex;
    align-items: center;
    gap: var(--ch-space-3);
    padding: var(--ch-space-2) var(--ch-space-5);
    padding-left: calc(var(--ch-space-5) + 18px + var(--ch-space-3));
    color: var(--ch-color-text-muted);
    text-decoration: none;
    font-size: var(--ch-text-sm);
    cursor: pointer;
    transition: all 0.15s ease;
    border-left: 2px solid transparent;
  }
  
  .nav-item:hover {
    background: var(--ch-color-bg-subtle);
  color: var(--ch-color-text);
}

.nav-item.active {
  background: var(--ch-color-primary);
  color: white;
  border-left-color: var(--ch-color-primary);
}

.nav-item-icon {
  flex-shrink: 0;
}

.nav-item-label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: opacity 0.2s ease;
}

.sidebar-collapsed .nav-item-label {
  opacity: 0;
}
  
  /* ============================================================
               SIDEBAR FOOTER
               ============================================================ */
  .sidebar-footer {
    padding: var(--ch-space-4) var(--ch-space-5);
    border-top: 1px solid var(--ch-color-border-strong);
  }
  
  .theme-toggle {
    display: flex;
    align-items: center;
    gap: var(--ch-space-3);
    width: 100%;
    padding: var(--ch-space-2) var(--ch-space-3);
    background: transparent;
    border: 1px solid var(--ch-color-border-strong);
    color: var(--ch-color-text-muted);
    font-size: var(--ch-text-sm);
    cursor: pointer;
    transition: all 0.15s ease;
}

.theme-toggle:hover {
  background: var(--ch-color-bg-subtle);
  color: var(--ch-color-text);
}

.sidebar-collapsed .theme-toggle span {
  display: none;
}

.sidebar-collapsed .theme-toggle {
  justify-content: center;
  padding: var(--ch-space-2) 0;
}

/* ============================================================
   MAIN CONTENT AREA
   ============================================================ */
.doc-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100vh;
  margin-left: 280px;
    transition: margin-left 0.3s ease-in-out;
  }
  
  @media (min-width: 1024px) {
    .doc-layout.sidebar-collapsed .doc-main {
      margin-left: 72px;
    }
  }
  
  @media (max-width: 1023px) {
    .doc-sidebar {
      transform: translateX(-100%);
    }
  
    .doc-layout.sidebar-open .doc-sidebar {
      transform: translateX(0);
    }
  
    .doc-main {
      margin-left: 0;
    }
  }
  
  /* ============================================================
               TOPBAR
               ============================================================ */
  .doc-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--ch-space-3) var(--ch-space-6);
    background: var(--ch-color-surface);
    border-bottom: 1px solid var(--ch-color-border-strong);
    min-height: 64px;
  }
  
  .topbar-left {
    display: flex;
    align-items: center;
    gap: var(--ch-space-4);
  }
  
  .topbar-right {
    display: flex;
    align-items: center;
    gap: var(--ch-space-3);
}

.doc-topbar-title {
  display: flex;
    align-items: center;
    gap: var(--ch-space-2);
  font-family: var(--ch-font-display);
  font-weight: var(--ch-font-semibold);
  font-size: var(--ch-text-lg);
  letter-spacing: -0.01em;
  color: var(--ch-color-text);
  }
  
  .breadcrumb-icon {
    color: var(--ch-color-text-subtle);
  }
  
  .topbar-btn {
    display: flex;
    align-items: center;
    gap: var(--ch-space-2);
    padding: var(--ch-space-2) var(--ch-space-3);
    background: transparent;
    border: 1px solid var(--ch-color-border-strong);
    color: var(--ch-color-text-muted);
    font-size: var(--ch-text-sm);
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;
  }
  
  .topbar-btn:hover {
    background: var(--ch-color-bg-subtle);
    color: var(--ch-color-text);
  }
  
  .btn-label {
    display: none;
  }
  
  @media (min-width: 768px) {
    .btn-label {
      display: inline;
    }
  }
  
  .mobile-menu-btn {
    display: flex;
  }
  
  @media (min-width: 1024px) {
    .mobile-menu-btn {
      display: none;
    }
  }
  
  .kbd {
    display: none;
    padding: 2px 6px;
    background: var(--ch-color-bg-subtle);
    border: 1px solid var(--ch-color-border);
    font-size: 11px;
    font-family: var(--ch-font-mono);
    color: var(--ch-color-text-subtle);
  }
  
  @media (min-width: 768px) {
    .kbd {
      display: inline;
    }
  }
  
  .has-badge {
    position: relative;
  }
  
  .notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    background: var(--ch-color-danger);
    color: white;
    font-size: 11px;
    font-weight: var(--ch-font-bold);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  /* ============================================================
               USER MENU
               ============================================================ */
  .user-menu-trigger {
    display: flex;
    align-items: center;
    gap: var(--ch-space-2);
    padding: var(--ch-space-1);
    background: transparent;
    border: none;
    color: var(--ch-color-text-muted);
    cursor: pointer;
  }
  
  .user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 0;
    object-fit: cover;
    border: 2px solid var(--ch-color-border-strong);
  }
  
  /* ============================================================
               DROPDOWN PANELS
               ============================================================ */
  .dropdown-container {
    position: relative;
  }
  
  .dropdown-panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: var(--ch-color-surface);
    border: 1px solid var(--ch-color-border-strong);
    z-index: 100;
    min-width: 200px;
    max-height: 400px;
    overflow-y: auto;
  }
  
  .dropdown-item {
    display: flex;
    align-items: center;
    gap: var(--ch-space-3);
    width: 100%;
    padding: var(--ch-space-3) var(--ch-space-4);
    background: transparent;
    border: none;
    color: var(--ch-color-text);
    font-size: var(--ch-text-sm);
    cursor: pointer;
    transition: background 0.15s ease;
    text-align: left;
  }
  
  .dropdown-item:hover {
    background: var(--ch-color-bg-subtle);
  }
  
  .dropdown-item.danger {
    color: var(--ch-color-danger);
  }
  
  .dropdown-divider {
    height: 1px;
    background: var(--ch-color-border);
    margin: var(--ch-space-2) 0;
  }
  
  .notifications-panel {
    width: 360px;
  }
  
  .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--ch-space-4);
    border-bottom: 1px solid var(--ch-color-border);
  }
  
  .panel-header h3 {
    margin: 0;
    font-size: var(--ch-text-base);
    font-weight: var(--ch-font-semibold);
  }
  
  .mark-all-btn {
    background: transparent;
    border: none;
    color: var(--ch-color-primary);
    font-size: var(--ch-text-sm);
    cursor: pointer;
  }
  
  .mark-all-btn:hover {
    text-decoration: underline;
  }
  
  .notifications-list {
    max-height: 320px;
    overflow-y: auto;
  }
  
  .notification-item {
    display: flex;
    gap: var(--ch-space-3);
    padding: var(--ch-space-4);
    border-bottom: 1px solid var(--ch-color-border);
    cursor: pointer;
    transition: background 0.15s ease;
  }
  
  .notification-item:hover {
    background: var(--ch-color-bg-subtle);
  }
  
  .notification-item.unread {
    background: rgba(59, 130, 246, 0.05);
  }
  
  .notification-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  
  .notification-icon.info {
    background: rgba(59, 130, 246, 0.1);
    color: var(--ch-color-info);
  }
  
  .notification-icon.warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--ch-color-warning);
  }
  
  .notification-icon.success {
    background: rgba(34, 197, 94, 0.1);
    color: var(--ch-color-success);
  }
  
  .notification-content {
    flex: 1;
    min-width: 0;
  }
  
  .notification-title {
    font-size: var(--ch-text-sm);
    font-weight: var(--ch-font-semibold);
    color: var(--ch-color-text);
    margin-bottom: 2px;
  }
  
  .notification-message {
    font-size: var(--ch-text-sm);
    color: var(--ch-color-text-muted);
    margin-bottom: 4px;
  }
  
  .notification-time {
    font-size: var(--ch-text-xs);
    color: var(--ch-color-text-subtle);
  }
  
  .unread-dot {
    width: 8px;
    height: 8px;
    background: var(--ch-color-primary);
    flex-shrink: 0;
    margin-top: 6px;
  }
  
  .user-panel {
    width: 240px;
    padding: var(--ch-space-4);
  }
  
  .user-info {
    display: flex;
    align-items: center;
    gap: var(--ch-space-3);
  }
  
  .user-panel-avatar {
    width: 48px;
    height: 48px;
    border-radius: 0;
    object-fit: cover;
    border: 2px solid var(--ch-color-border-strong);
  }
  
  .user-name {
    font-size: var(--ch-text-sm);
    font-weight: var(--ch-font-semibold);
    color: var(--ch-color-text);
  }
  
  .user-email {
    font-size: var(--ch-text-xs);
    color: var(--ch-color-text-muted);
  }
  
  /* ============================================================
               SEARCH MODAL
               ============================================================ */
  .search-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 10vh;
    z-index: 200;
    backdrop-filter: blur(4px);
  }
  
  .search-container {
    width: 100%;
    max-width: 600px;
    background: var(--ch-color-surface);
    border: 1px solid var(--ch-color-border-strong);
    max-height: 70vh;
    overflow-y: auto;
  }
  
  .search-input-wrapper {
    display: flex;
    align-items: center;
    gap: var(--ch-space-3);
    padding: var(--ch-space-4);
    border-bottom: 1px solid var(--ch-color-border);
  }
  
  .search-icon {
    color: var(--ch-color-text-muted);
    flex-shrink: 0;
  }
  
  .search-input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: var(--ch-text-lg);
    color: var(--ch-color-text);
    outline: none;
  }
  
  .search-input::placeholder {
    color: var(--ch-color-text-subtle);
  }
  
  .escape-kbd {
    display: inline;
    cursor: pointer;
  }
  
  .search-results {
    padding: var(--ch-space-2);
  }
  
  .results-label {
    padding: var(--ch-space-2) var(--ch-space-3);
    font-size: var(--ch-text-xs);
    font-weight: var(--ch-font-semibold);
    text-transform: uppercase;
    color: var(--ch-color-text-subtle);
    letter-spacing: 0.05em;
  }
  
  .search-result-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: var(--ch-space-3);
    background: transparent;
    border: none;
    text-align: left;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  
  .search-result-item:hover {
    background: var(--ch-color-bg-subtle);
  }
  
  .result-label {
    font-size: var(--ch-text-sm);
    font-weight: var(--ch-font-medium);
    color: var(--ch-color-text);
  }
  
  .result-section {
    font-size: var(--ch-text-xs);
    color: var(--ch-color-text-subtle);
  }
  
  .no-results {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--ch-space-4);
    padding: var(--ch-space-12);
    color: var(--ch-color-text-muted);
    text-align: center;
  }
  
  .search-hints {
    padding: var(--ch-space-4);
  }
  
  .hints-label {
    font-size: var(--ch-text-xs);
    font-weight: var(--ch-font-semibold);
    text-transform: uppercase;
    color: var(--ch-color-text-subtle);
    letter-spacing: 0.05em;
    margin-bottom: var(--ch-space-3);
  }
  
  .hints-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--ch-space-2);
  }
  
  .hint-item {
    display: flex;
    align-items: center;
    gap: var(--ch-space-2);
    padding: var(--ch-space-3);
    background: var(--ch-color-bg-subtle);
    border: 1px solid var(--ch-color-border);
    color: var(--ch-color-text-muted);
    font-size: var(--ch-text-sm);
    cursor: pointer;
    transition: all 0.15s ease;
  }
  
  .hint-item:hover {
    border-color: var(--ch-color-border-strong);
    color: var(--ch-color-text);
}

/* ============================================================
   CONTENT AREA
   ============================================================ */
.doc-content {
  flex: 1;
  overflow-y: auto;
  padding: var(--ch-space-12);
}

/* ============================================================
   ANIMATIONS
   ============================================================ */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  max-height: 0;
}

.slide-enter-to,
.slide-leave-from {
  opacity: 1;
  max-height: 500px;
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.modal-enter-active,
.modal-leave-active {
  transition: all 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .search-container,
.modal-leave-to .search-container {
  transform: scale(0.95);
}

/* ============================================================
   GLOBAL STYLES (Applied to child components)
   ============================================================ */
:global(.doc-page) {
  max-width: 1024px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-12);
}

:global(.page-header) {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
}

:global(.page-title) {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-4xl);
  font-weight: var(--ch-font-bold);
  letter-spacing: -0.02em;
  margin: 0;
}

:global(.page-desc) {
  font-size: var(--ch-text-lg);
  color: var(--ch-color-text-muted);
  max-width: 60ch;
  line-height: var(--ch-leading-relaxed);
}

:global(.doc-section) {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
}

:global(.doc-section-title) {
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-semibold);
  padding-bottom: var(--ch-space-4);
  border-bottom: 2px solid var(--ch-color-border-strong);
  margin: 0;
}

:global(.demo-grid) {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--ch-space-6);
}

:global(.demo-block) {
  padding: var(--ch-space-6);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

:global(.demo-title) {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-bold);
  text-transform: uppercase;
  color: var(--ch-color-text-subtle);
  letter-spacing: 0.05em;
  margin-bottom: var(--ch-space-2);
}
</style>
