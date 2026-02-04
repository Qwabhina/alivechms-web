<script setup lang="ts">
import { ref } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import { 
  BarChart3, 
  Users, 
  Settings, 
  LogOut, 
  Menu, 
  Shield, 
  Calendar, 
  DollarSign, 
  Briefcase,
  LayoutDashboard,
  Bell,
  Search,
  ChevronRight
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { 
  Sidebar, 
  SidebarContent, 
  SidebarFooter, 
  SidebarHeader, 
  SidebarMenu, 
  SidebarMenuItem, 
  SidebarMenuButton,
  SidebarProvider,
  SidebarTrigger,
  SidebarInset
} from '@/components/ui/sidebar'
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuLabel, 
  DropdownMenuSeparator, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Alerts } from '@/utils/alerts'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const handleLogout = async () => {
  const confirmed = await Alerts.confirm({
    title: 'Logout',
    text: 'Are you sure you want to log out?',
    icon: 'question',
    confirmButtonText: 'Logout',
  })

  if (confirmed) {
    await authStore.logout()
    router.push({ name: 'login' })
  }
}

const navItems = [
  { name: 'Dashboard', icon: LayoutDashboard, route: 'home' },
  { name: 'Members', icon: Users, route: 'members' },
  { name: 'Finance', icon: DollarSign, route: 'finance' },
  { name: 'Events', icon: Calendar, route: 'events' },
  { name: 'Settings', icon: Settings, route: 'settings' },
]

const isActive = (routeName: string) => route.name === routeName
</script>

<template>
  <SidebarProvider>
    <Sidebar variant="inset" class="bg-[#000250] text-[#f1f5f9] border-r border-[#00028a]/20">
      <SidebarHeader class="h-16 flex items-center px-6 border-b border-[#00028a]/20">
        <div class="flex items-center gap-3">
          <img 
            :src="settingsStore.churchLogoUrl" 
            :alt="settingsStore.settings.church_name"
            class="w-8 h-8 object-contain"
          />
          <span class="font-bold text-lg truncate">{{ settingsStore.settings.church_name }}</span>
        </div>
      </SidebarHeader>
      <SidebarContent class="py-4">
        <SidebarMenu>
          <SidebarMenuItem v-for="item in navItems" :key="item.name">
            <SidebarMenuButton 
              as-child 
              :active="isActive(item.route)"
              class="hover:bg-[#e5a100]/10 hover:text-[#e5a100] transition-colors px-6 h-12"
            >
              <router-link :to="{ name: item.route }" class="flex items-center gap-3 w-full">
                <component :is="item.icon" class="w-5 h-5" />
                <span class="font-medium">{{ item.name }}</span>
                <ChevronRight v-if="isActive(item.route)" class="ml-auto w-4 h-4 text-[#e5a100]" />
              </router-link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarContent>
      <SidebarFooter class="p-4 border-t border-[#00028a]/20">
        <div class="flex items-center gap-3 px-2">
          <Avatar class="h-9 w-9 border-2 border-[#e5a100]">
            <AvatarImage :src="`https://api.dicebear.com/7.x/initials/svg?seed=${authStore.user?.username}`" />
            <AvatarFallback>{{ authStore.user?.username?.substring(0, 2).toUpperCase() }}</AvatarFallback>
          </Avatar>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold truncate">{{ authStore.user?.username }}</p>
            <p class="text-xs text-gray-400 truncate capitalize">{{ authStore.user?.role || 'User' }}</p>
          </div>
          <Button variant="ghost" size="icon" @click="handleLogout" class="hover:text-red-400">
            <LogOut class="w-5 h-5" />
          </Button>
        </div>
      </SidebarFooter>
    </Sidebar>

    <SidebarInset>
      <header class="h-16 border-b bg-white flex items-center justify-between px-6 sticky top-0 z-10">
        <div class="flex items-center gap-4">
          <SidebarTrigger />
          <div class="hidden md:flex relative w-80">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <Input 
              placeholder="Search members, events..." 
              class="pl-10 h-9 bg-gray-50 border-none focus-visible:ring-1 focus-visible:ring-[#00028a]/20"
            />
          </div>
        </div>
        
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="icon" class="relative">
            <Bell class="w-5 h-5" />
            <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
          </Button>
          
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="ghost" class="p-1 h-auto flex items-center gap-2">
                <span class="hidden sm:inline font-medium text-sm">{{ authStore.user?.username }}</span>
                <Avatar class="h-8 w-8">
                  <AvatarImage :src="`https://api.dicebear.com/7.x/initials/svg?seed=${authStore.user?.username}`" />
                  <AvatarFallback>AD</AvatarFallback>
                </Avatar>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
              <DropdownMenuLabel>My Account</DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem>Profile Settings</DropdownMenuItem>
              <DropdownMenuItem>User Management</DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem @click="handleLogout" class="text-red-600 focus:text-red-600">
                <LogOut class="mr-2 h-4 w-4" />
                <span>Log out</span>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>
      
      <main class="p-6 h-[calc(100vh-64px)] overflow-y-auto bg-gray-50/50">
        <RouterView />
      </main>
    </SidebarInset>
  </SidebarProvider>
</template>

<style scoped>
.router-link-active {
  @apply text-[#e5a100];
}
</style>
