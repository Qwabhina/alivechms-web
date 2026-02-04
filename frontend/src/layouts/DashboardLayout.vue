<script setup lang="ts">
import { ref } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import { 
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
  ChevronRight,
  User,
  Home,
  Users2,
  Hand,
  Trophy,
  Coins,
  Bookmark,
  Receipt,
  PieChart,
  BarChart3,
  CalendarCheck,
  ClipboardCheck,
  Mail,
  MessageSquare,
  Megaphone,
  Package,
  Archive,
  Tags,
  Sliders,
  ShieldCheck,
  Building,
  CalendarRange,
  History,
  ChevronDown
} from 'lucide-vue-next'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
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
  SidebarMenuSub,
  SidebarMenuSubItem,
  SidebarMenuSubButton,
  SidebarProvider,
  SidebarTrigger,
  SidebarInset,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarGroupContent
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
  {
    name: 'Dashboard',
    icon: LayoutDashboard,
    route: 'home'
  },
  {
    name: 'Members & People',
    icon: Users,
    children: [
      { name: 'Members', icon: User, route: 'members' },
      { name: 'Families', icon: Home, route: 'families' },
      { name: 'Groups', icon: Users2, route: 'groups' },
      { name: 'Volunteers', icon: Hand, route: 'volunteers' },
      { name: 'Milestones', icon: Trophy, route: 'milestones' },
    ]
  },
  {
    name: 'Finance',
    icon: DollarSign,
    children: [
      { name: 'Contributions', icon: Coins, route: 'contributions' },
      { name: 'Pledges', icon: Bookmark, route: 'pledges' },
      { name: 'Expenses', icon: Receipt, route: 'expenses' },
      { name: 'Budgets', icon: PieChart, route: 'budgets' },
      { name: 'Financial Reports', icon: BarChart3, route: 'financial-reports' },
    ]
  },
  {
    name: 'Events & Activities',
    icon: Calendar,
    children: [
      { name: 'Events', icon: CalendarCheck, route: 'events' },
      { name: 'Attendance', icon: ClipboardCheck, route: 'attendance' },
    ]
  },
  {
    name: 'Communication',
    icon: Mail,
    children: [
      { name: 'Messages', icon: MessageSquare, route: 'messages' },
      { name: 'Announcements', icon: Megaphone, route: 'announcements' },
    ]
  },
  {
    name: 'Assets',
    icon: Package,
    children: [
      { name: 'All Assets', icon: Archive, route: 'assets' },
      { name: 'Asset Categories', icon: Tags, route: 'asset-categories' },
    ]
  },
  {
    name: 'Settings',
    icon: Settings,
    children: [
      { name: 'General', icon: Sliders, route: 'settings-general' },
      { name: 'Users', icon: Users, route: 'users' },
      { name: 'Roles & Permissions', icon: ShieldCheck, route: 'roles' },
      { name: 'Branches', icon: Building, route: 'branches' },
      { name: 'Fiscal Years', icon: CalendarRange, route: 'fiscal-years' },
      { name: 'Audit Log', icon: History, route: 'audit-log' },
    ]
  },
]

const isActive = (routeName: string) => route.name === routeName
const openGroup = ref<string | null>(null)

const isGroupActive = (item: any) => {
  if (!item.children) return isActive(item.route)
  return item.children.some((child: any) => isActive(child.route))
}

const toggleGroup = (name: string) => {
  if (openGroup.value === name) {
    openGroup.value = null
  } else {
    openGroup.value = name
  }
}

// Initial state: open active group
const activeGroup = navItems.find(item => isGroupActive(item))
if (activeGroup) openGroup.value = activeGroup.name
</script>

<template>
  <SidebarProvider>
    <Sidebar variant="inset" class="bg-[#000250] text-[#f1f5f9] border-r border-[#00028a]/20">
     <SidebarHeader class="h-16 flex items-center px-6 border-b border-[#00028a]/20 lg:hidden">
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
       <!-- New Mobile Header Section (Legacy Parity) -->
        <div class="lg:hidden px-6 mb-6 space-y-4">
          <div class="flex items-center gap-3">
            <Avatar class="h-10 w-10 border-2 border-[#e5a100]">
              <AvatarImage :src="`https://api.dicebear.com/7.x/initials/svg?seed=${authStore.user?.username}`" />
              <AvatarFallback>{{ authStore.user?.username?.substring(0, 2).toUpperCase() }}</AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold truncate text-white">{{ authStore.user?.username }}</p>
              <p class="text-xs text-blue-200 truncate capitalize">{{ authStore.user?.role || 'User' }}</p>
            </div>
          </div>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-200" />
            <Input placeholder="Search..."
              class="pl-10 h-10 bg-white/10 border-white/20 text-white placeholder:text-blue-200/50" />
          </div>
          <div class="flex gap-2">
            <Button variant="outline" size="sm" class="flex-1 bg-white/5 border-white/20 text-white hover:bg-white/10">
              <Bell class="w-4 h-4 mr-2" /> Notifications
            </Button>
            <Button variant="outline" size="icon" size-sm
              class="bg-white/5 border-white/20 text-white hover:bg-white/10">
              <User class="w-4 h-4" />
            </Button>
          </div>
          <hr class="border-white/10" />
        </div>

        <SidebarMenu class="px-3 gap-1">
          <template v-for="item in navItems" :key="item.name">
            <!-- Single Item -->
            <SidebarMenuItem v-if="!item.children">
              <SidebarMenuButton as-child :active="isActive(item.route)"
                class="hover:bg-[#e5a100]/10 hover:text-[#e5a100] data-[active=true]:bg-[#e5a100]/20 data-[active=true]:text-[#e5a100] transition-all duration-200 px-3 h-11 rounded-lg">
                <router-link :to="{ name: item.route }" class="flex items-center gap-3 w-full">
                  <component :is="item.icon" class="w-5 h-5" />
                  <span class="font-medium">{{ item.name }}</span>
                 <ChevronRight v-if="isActive(item.route)" class="ml-auto w-4 h-4" />
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>
           <!-- Group Item (Collapsible) -->
            <SidebarMenuItem v-else>
              <!-- Divider before Settings per legacy parity -->
              <hr v-if="item.name === 'Settings'" class="border-white/10 my-4 mx-3" />
              <Collapsible :open="openGroup === item.name" @update:open="() => toggleGroup(item.name)"
                class="group/collapsible">
                <CollapsibleTrigger as-child>
                  <SidebarMenuButton
                    class="hover:bg-white/5 data-[active=true]:text-[#e5a100] transition-colors px-3 h-11 rounded-lg w-full"
                    :active="isGroupActive(item)">
                    <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
                    <span class="font-medium ml-3">{{ item.name }}</span>
                    <ChevronDown
                      class="ml-auto w-4 h-4 transition-transform duration-300 group-data-[state=open]/collapsible:rotate-180" />
                  </SidebarMenuButton>
                </CollapsibleTrigger>
                <transition name="menu-slide">
                  <CollapsibleContent>
                    <SidebarMenuSub class="ml-3 mt-1 border-l border-white/10 pl-2">
                      <SidebarMenuSubItem v-for="child in item.children" :key="child.name">
                        <SidebarMenuSubButton as-child :active="isActive(child.route)"
                          class="hover:text-[#e5a100] data-[active=true]:text-[#e5a100] transition-colors py-2 px-3 block rounded-md">
                          <router-link :to="{ name: child.route }" class="flex items-center gap-2">
                            <component :is="child.icon" class="w-4 h-4" />
                            <span class="text-sm">{{ child.name }}</span>
                          </router-link>
                        </SidebarMenuSubButton>
                      </SidebarMenuSubItem>
                    </SidebarMenuSub>
                  </CollapsibleContent>
                </transition>
              </Collapsible>
            </SidebarMenuItem>
          </template>
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
          <!-- Logo & Church Name (Top Nav Migration) -->
          <div class="hidden lg:flex items-center gap-3 border-l pl-4 ml-2">
            <img :src="settingsStore.churchLogoUrl" :alt="settingsStore.settings.church_name"
              class="w-8 h-8 object-contain" />
            <span class="font-bold text-slate-800 text-lg whitespace-nowrap">{{ settingsStore.settings.church_name
              }}</span>
          </div>

          <div class="hidden md:flex relative w-64 ml-4">
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
/* Enhanced Menu Animations */
.menu-slide-enter-active,
.menu-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  max-height: 500px;
}

.menu-slide-enter-from,
.menu-slide-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-4px);
}
</style>
