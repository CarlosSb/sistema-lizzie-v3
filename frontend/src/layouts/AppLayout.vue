<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useBreakpoints } from '@vueuse/core'
import {
  LayoutDashboard,
  Users,
  Package,
  ShoppingCart,
  LogOut,
  Menu,
  Bell,
  Search,
  Sun,
  Moon,
  X,
  Check
} from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import { Input } from '@/components/ui/input'
import logoFull from '@/assets/img-logomarca-lizzie.webp'
import logoMini from '@/assets/img-logo-lizzie.webp'

const router = useRouter()
const auth = useAuthStore()
const isSidebarOpen = ref(true)
const isMobileMenuOpen = ref(false) // Mobile specific state
const isDarkMode = ref(false)
const isAlertDropdownOpen = ref(false)
const alerts = ref([])
const unreadCount = ref(0)

const breakpoints = useBreakpoints({
  mobile: 0,
  tablet: 640,
  laptop: 1024,
  desktop: 1280,
})

const isMobile = breakpoints.smaller('laptop')

// Automatically collapse sidebar on mobile
watch(isMobile, (mobile) => {
  isSidebarOpen.value = !mobile
})

const menuItems = [
  { name: 'Dashboard', icon: LayoutDashboard, route: 'dashboard', subRoutes: ['dashboard'] },
  { name: 'Pedidos', icon: ShoppingCart, route: 'pedidos', subRoutes: ['pedidos', 'pedido-novo', 'pedido-detalhes'] },
  { name: 'Clientes', icon: Users, route: 'clientes', subRoutes: ['clientes', 'cliente-novo', 'cliente-editar', 'cliente-detalhes'] },
  { name: 'Produtos', icon: Package, route: 'produtos', subRoutes: ['produtos', 'produto-novo', 'produto-editar', 'produto-detalhes'] },
  { name: 'Vendedores', icon: Users, route: 'vendedores', subRoutes: ['vendedores', 'vendedores-novo', 'vendedores-detalhes', 'vendedores-editar'] }
]

const toggleSidebar = () => {
  if (isMobile.value) {
    isMobileMenuOpen.value = !isMobileMenuOpen.value
  } else {
    isSidebarOpen.value = !isSidebarOpen.value
  }
}

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value
  document.documentElement.classList.toggle('dark', isDarkMode.value)
  localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light')
}

const handleLogout = () => {
  auth.logout()
  router.push({ name: 'login' })
}

const fetchAlerts = async () => {
  try {
    const response = await fetch('/api/alertas')
    if (response.ok) {
      alerts.value = await response.json()
    }
  } catch (error) {
    console.error('Failed to fetch alerts:', error)
  }
}

const fetchUnreadCount = async () => {
  try {
    const response = await fetch('/api/alertas/nao-lidos')
    if (response.ok) {
      const data = await response.json()
      unreadCount.value = data.count || 0
    }
  } catch (error) {
    console.error('Failed to fetch unread count:', error)
  }
}

const markAsRead = async (alertId) => {
  try {
    await fetch(`/api/alertas/${alertId}/ler`, { method: 'PUT' })
    // Update local state
    const alert = alerts.value.find(a => a.id === alertId)
    if (alert) alert.lido = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (error) {
    console.error('Failed to mark as read:', error)
  }
}

const toggleAlertDropdown = () => {
  isAlertDropdownOpen.value = !isAlertDropdownOpen.value
  if (isAlertDropdownOpen.value) {
    fetchAlerts()
  }
}

const userInitials = () => {
  const name = auth.user?.nome || auth.user?.usuario || 'User'
  const parts = name.trim().split(/\s+/).slice(0, 2)
  return parts.map(p => p[0]?.toUpperCase()).join('') || 'U'
}

const isMenuItemActive = (item: any) => {
  const currentRouteName = router.currentRoute.value.name as string
  return item.subRoutes?.includes(currentRouteName) || currentRouteName === item.route
}

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDarkMode.value = true
    document.documentElement.classList.add('dark')
  }
  fetchUnreadCount()
})
</script>

<template>
  <div class="flex h-screen bg-background text-foreground overflow-hidden w-full">
    <!-- Overlay for Mobile -->
    <div 
      v-if="isMobile && isMobileMenuOpen" 
      @click="isMobileMenuOpen = false"
      class="fixed inset-0 bg-black/50 z-40 lg:hidden"
    ></div>

    <!-- Sidebar -->
    <aside 
      :class="[
        'bg-card border-r transition-all duration-300 flex flex-col z-50 shrink-0 shadow-sm',
        isMobile ? 'fixed inset-y-0 left-0 w-64' : 'relative',
        isMobile && !isMobileMenuOpen ? '-translate-x-full' : 'translate-x-0',
        isSidebarOpen ? 'w-64' : 'w-20'
      ]"
    >
      <div class="h-16 flex items-center justify-between px-6 border-b">
        <div class="flex items-center">
          <img
            :src="isMobile || isSidebarOpen ? logoFull : logoMini"
            alt="Lizzie Logo"
            :class="isSidebarOpen ? 'h-8 w-auto' : 'h-6 w-auto'"
          />
        </div>
        <Button v-if="isMobile" variant="ghost" size="icon" @click="isMobileMenuOpen = false">
          <X class="w-5 h-5" />
        </Button>
      </div>

      <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
        <router-link
          v-for="item in menuItems"
          :key="item.name"
          :to="{ name: item.route }"
          :class="[
            'flex items-center px-4 py-3 transition-all duration-200 group rounded-lg relative',
            isMenuItemActive(item) ? 'active-nav' : 'hover:bg-accent/50'
          ]"
          @click="isMobile ? isMobileMenuOpen = false : null"
        >
          <component :is="item.icon" class="w-5 h-5 transition-colors shrink-0" />
          <span :class="['ml-3 font-medium text-sm tracking-tight transition-opacity duration-200', isSidebarOpen ? 'opacity-100 lg:block' : 'opacity-0 lg:hidden', isMobileMenuOpen ? 'opacity-100' : 'opacity-0']">
            {{ item.name }}
          </span>
        </router-link>
      </nav>

      <div class="p-4 border-t space-y-2">
        <Button 
          variant="ghost"
          @click="toggleDarkMode"
          class="flex items-center w-full px-4 justify-start rounded-lg"
        >
          <Sun v-if="isDarkMode" class="w-5 h-5" />
          <Moon v-else class="w-5 h-5" />
          <span :class="['ml-3 font-medium text-sm transition-opacity duration-200', isSidebarOpen ? 'opacity-100' : 'opacity-0 lg:hidden']">
            {{ isDarkMode ? 'Tema Claro' : 'Tema Escuro' }}
          </span>
        </Button>

        <Button 
          variant="ghost"
          @click="handleLogout"
          class="flex items-center w-full px-4 text-destructive hover:bg-destructive/10 transition-colors group justify-start rounded-lg"
        >
          <LogOut class="w-5 h-5 group-hover:-translate-x-1 transition-transform shrink-0" />
          <span :class="['ml-3 font-medium text-sm transition-opacity duration-200', isSidebarOpen ? 'opacity-100' : 'opacity-0 lg:hidden']">
            Sair
          </span>
        </Button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
      <!-- Header -->
      <header class="h-16 border-b bg-card/50 backdrop-blur-md flex items-center px-4 lg:px-8 justify-between z-10 shrink-0 shadow-sm">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="icon" @click="toggleSidebar" class="rounded-lg hover:bg-accent">
            <Menu class="w-5 h-5" />
          </Button>
          
          <div class="hidden lg:flex items-center bg-muted/50 rounded-lg px-3 w-80 group focus-within:bg-background focus-within:ring-1 focus-within:ring-primary transition-all">
            <Search class="w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
            <Input 
              type="text" 
              placeholder="Pesquisar..." 
              class="border-0 focus-visible:ring-0 text-sm bg-transparent h-10 w-full" 
            />
          </div>
        </div>

        <div class="flex items-center gap-4">
          <div class="relative">
            <Button variant="ghost" size="icon" class="relative rounded-lg hover:bg-accent" @click="toggleAlertDropdown">
              <Bell class="w-5 h-5" />
              <span v-if="unreadCount > 0" class="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full ring-2 ring-card"></span>
            </Button>

            <!-- Alert Dropdown -->
            <div v-if="isAlertDropdownOpen" class="absolute top-full right-0 mt-2 w-80 bg-card border rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto">
              <div class="p-4 border-b flex items-center justify-between">
                <h3 class="font-semibold text-sm">Notificações</h3>
                <Button variant="ghost" size="icon" class="h-6 w-6" @click="isAlertDropdownOpen = false">
                  <X class="w-4 h-4" />
                </Button>
              </div>
              <div v-if="alerts.length === 0" class="p-4 text-center text-muted-foreground text-sm">
                Nenhum alerta no momento
              </div>
              <div v-else class="divide-y">
                <div v-for="alert in alerts" :key="alert.id" class="p-4 hover:bg-accent/50 transition-colors">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                      <p class="text-sm font-medium">{{ alert.titulo || 'Alerta' }}</p>
                      <p class="text-xs text-muted-foreground mt-1">{{ alert.mensagem }}</p>
                      <p class="text-xs text-muted-foreground mt-1">{{ new Date(alert.created_at).toLocaleString() }}</p>
                    </div>
                    <Button
                      v-if="!alert.lido"
                      variant="ghost"
                      size="icon"
                      class="h-6 w-6 shrink-0"
                      @click="markAsRead(alert.id)"
                    >
                      <Check class="w-3 h-3" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <Separator orientation="vertical" class="h-8" />

          <div class="flex flex-col items-end hidden sm:flex">
            <span class="text-sm font-semibold truncate max-w-[160px]">{{ auth.user?.nome || auth.user?.usuario || 'Usuário' }}</span>
            <span class="text-[11px] text-muted-foreground font-medium">{{ auth.user?.nivel || '' }}</span>
          </div>
          <router-link to="/perfil" class="cursor-pointer">
            <Avatar class="w-9 h-9 border ring-2 ring-primary/10 hover:ring-primary/30 transition-all">
              <AvatarImage src="" alt="Admin" />
              <AvatarFallback class="bg-primary/10 text-primary font-bold">{{ userInitials() }}</AvatarFallback>
            </Avatar>
          </router-link>
        </div>
      </header>

      <!-- Page Content -->
      <div class="flex-1 overflow-auto p-6 lg:p-10 custom-scroll bg-muted/30">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </main>
  </div>
</template>

<style scoped>
@reference "@/style.css";

.active-nav {
  @apply bg-primary text-primary-foreground shadow-lg shadow-primary/20;
}

.custom-scroll::-webkit-scrollbar {
  width: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb {
  @apply bg-border hover:bg-primary/50;
}
</style>
