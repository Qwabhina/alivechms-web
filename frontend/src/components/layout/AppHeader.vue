<template>
   <header class="app-header">
      <div class="header-left">
         <button class="sidebar-toggle" @click="toggleSidebar">
            <i class="pi pi-bars"></i>
         </button>
         <h1 class="app-title">AliveChMS</h1>
      </div>

    <div class="header-center">
         <div class="search-container">
            <i class="pi pi-search search-icon"></i>
            <input type="text" v-model="searchQuery" placeholder="Search..." class="search-input"
               @keyup.enter="handleSearch" />
         </div>
      </div>
      <div class="header-right">
         <button class="icon-btn" title="Notifications">
            <i class="pi pi-bell"></i>
         </button>

         <div class="user-menu">
            <button class="user-btn" @click="showUserMenu = !showUserMenu">
               <span class="user-avatar">{{ userInitials }}</span>
               <span class="user-name">{{ authStore.userFullName }}</span>
               <i class="pi pi-chevron-down"></i>
            </button>

            <div v-if="showUserMenu" class="dropdown-menu">
               <a href="#" class="menu-item">
                  <i class="pi pi-user"></i> Profile
               </a>
               <a href="#" class="menu-item">
                  <i class="pi pi-cog"></i> Settings
               </a>
               <hr class="menu-divider" />
               <a href="#" class="menu-item" @click.prevent="handleLogout">
                  <i class="pi pi-sign-out"></i> Logout
               </a>
            </div>
         </div>
      </div>
   </header>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import { useLayoutStore } from '@/stores/layoutStore';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const layoutStore = useLayoutStore();
const router = useRouter();
const showUserMenu = ref(false);
const searchQuery = ref('');

const userInitials = computed(() => {
   const name = authStore.userFullName || authStore.user?.Username || '';
   return name
      .split(' ')
      .map((n: string) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
});

function toggleSidebar() {
   layoutStore.toggleSidebar();
}

function handleSearch() {
   if (searchQuery.value.trim()) {
      console.log('Searching for:', searchQuery.value);
      // Implement global search navigation here
   }
}

async function handleLogout() {
   await authStore.logout();
   router.push('/login');
}
</script>

<style scoped>
.app-header {
   height: 64px;
   background: var(--color-surface);
   border-bottom: 1px solid var(--color-border);
   display: flex;
   align-items: center;
   justify-content: space-between;
   padding: 0 var(--space-lg);
   box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
      position: sticky;
      top: 0;
      z-index: 40;
}

.header-left {
   display: flex;
   align-items: center;
   gap: var(--space-md);
}
   
      .header-center {
         flex: 1;
         display: flex;
         justify-content: center;
         max-width: 600px;
         padding: 0 var(--space-md);
      }
   
      .search-container {
         position: relative;
         width: 100%;
         max-width: 400px;
      }
   
      .search-icon {
         position: absolute;
         left: 12px;
         top: 50%;
         transform: translateY(-50%);
         color: var(--color-text-muted);
      }
   
      .search-input {
         width: 100%;
         padding: 10px 16px 10px 36px;
         background: var(--color-bg);
         border: 1px solid transparent;
         border-radius: var(--radius-full);
         font-size: var(--font-size-sm);
         color: var(--color-text);
         transition: all var(--transition-fast);
      }
   
      .search-input:focus {
         background: var(--color-surface);
         border-color: var(--color-primary);
         box-shadow: 0 0 0 2px rgba(var(--color-primary-rgb), 0.1);
         outline: none;
      }
.sidebar-toggle {
   background: none;
   border: none;
   font-size: 1.25rem;
   cursor: pointer;
   color: var(--color-text-muted);
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: var(--radius-md);
      transition: all var(--transition-fast);
}

.sidebar-toggle:hover {
   background: var(--color-bg);
   color: var(--color-primary);
}

.app-title {
   font-size: 1.25rem;
   font-weight: 700;
   color: var(--color-primary);
   margin: 0;
   letter-spacing: -0.025em;
}

.header-right {
   display: flex;
   align-items: center;
   gap: var(--space-md);
}

.icon-btn {
   background: none;
   border: none;
   font-size: 1.25rem;
   cursor: pointer;
   color: var(--color-text-muted);
   padding: var(--space-sm);
   border-radius: var(--radius-sm);
   transition: all var(--transition-fast);
   position: relative;
}

.icon-btn:hover {
   background: var(--color-bg);
   color: var(--color-text);
}

.user-menu {
   position: relative;
}

.user-btn {
   display: flex;
   align-items: center;
   gap: var(--space-sm);
   background: none;
   border: none;
   cursor: pointer;
   padding: var(--space-sm) var(--space-md);
   border-radius: var(--radius-md);
   transition: background var(--transition-fast);
}

.user-btn:hover {
   background: var(--color-bg);
}

.user-avatar {
   width: 36px;
   height: 36px;
   border-radius: var(--radius-full);
   background: var(--color-primary);
   color: white;
   display: flex;
   align-items: center;
   justify-content: center;
   font-weight: 600;
   font-size: var(--font-size-sm);
}

.user-name {
   font-weight: 500;
   color: var(--color-text);
}

.dropdown-menu {
   position: absolute;
   top: calc(100% + var(--space-sm));
   right: 0;
   background: var(--color-surface);
   border: 1px solid var(--color-border);
   border-radius: var(--radius-md);
   box-shadow: var(--shadow-lg);
   min-width: 200px;
   z-index: 1000;
}

.menu-item {
   display: flex;
   align-items: center;
   gap: var(--space-sm);
   padding: var(--space-sm) var(--space-md);
   color: var(--color-text);
   text-decoration: none;
   transition: background var(--transition-fast);
}

.menu-item:hover {
   background: var(--color-bg);
}

.menu-divider {
   margin: var(--space-xs) 0;
   border: none;
   border-top: 1px solid var(--color-border);
}

@media (max-width: 768px) {
   .user-name {
      display: none;
   }

   .app-title {
      font-size: var(--font-size-lg);
   }
}
</style>
