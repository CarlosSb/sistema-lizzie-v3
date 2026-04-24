<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
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
import apiClient from '@/lib/axios'
import logoFull from '@/assets/img-logomarca-lizzie.webp'
import logoMini from '@/assets/img-logo-lizzie.webp'

const router = useRouter()
const auth = useAuthStore()
const isSidebarOpen = ref(true)
const isMobileMenuOpen = ref(false) // Mobile specific state
const isDarkMode = ref(false)
const isAlertDropdownOpen = ref(false)
const alerts = ref<Alert[]>([])
const unreadCount = ref(0)

// Alert interface
interface Alert {
  id: number
  titulo?: string
  mensagem: string
  created_at: string
  lido: boolean
}

// Search state
const searchTerm = ref('')
const searchResults = ref<SearchResult[]>([])
const isSearching = ref(false)
const searchDebounceTimer = ref<number | null>(null)

// Search result interface
interface SearchResult {
  id: number
  type: string
  title: string
  subtitle: string
  route: { name: string; params: { id: number } }
}

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
    const response = await apiClient.get('/api/alertas')
    alerts.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to fetch alerts:', error)
  }
}

const fetchUnreadCount = async () => {
  try {
    const response = await apiClient.get('/api/alertas/nao-lidos')
    unreadCount.value = response.data?.data?.count || 0
  } catch (error) {
    console.error('Failed to fetch unread count:', error)
  }
}

const markAsRead = async (alertId: number) => {
  try {
    await apiClient.put(`/api/alertas/${alertId}/ler`)
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

// Search functions
const searchClients = async (term: string): Promise<SearchResult[]> => {
   try {
     const response = await apiClient.get('/api/clientes', {
       params: { search: term, per_page: 5 }
     })
     return response.data?.data?.map((item: any) => ({
       id: item.id_cliente,
       type: 'cliente',
       title: item.nome || item.razao_social || 'Cliente',
       subtitle: item.email || item.telefone || '',
       route: { name: 'clientes-editar', params: { id: item.id_cliente } }
     })) || []
   } catch (error) {
     console.error('Failed to search clients:', error)
     return []
   }
 }

const searchOrders = async (term: string): Promise<SearchResult[]> => {
   try {
     const response = await apiClient.get('/api/pedidos', {
       params: { search: term, per_page: 5 }
     })
     return response.data?.data?.map((item: any) => ({
       id: item.id_pedido,
       type: 'pedido',
       title: `Pedido #${item.id_pedido}`,
       subtitle: item.cliente?.razao_social || item.data_pedido || '',
       route: { name: 'pedido-detalhes', params: { id: item.id_pedido } }
     })) || []
   } catch (error) {
     console.error('Failed to search orders:', error)
     return []
   }
 }

const searchProducts = async (term: string): Promise<SearchResult[]> => {
   try {
     const response = await apiClient.get('/api/produtos', {
       params: { search: term, per_page: 5 }
     })
     return response.data?.data?.map((item: any) => ({
       id: item.id_produto,
       type: 'produto',
       title: item.produto,
       subtitle: `R$ ${item.valor_unt_norde?.toFixed(2)}`,
       route: { name: 'produto-editar', params: { id: item.id_produto } }
     })) || []
   } catch (error) {
     console.error('Failed to search products:', error)
     return []
   }
 }

const searchVendors = async (term: string): Promise<SearchResult[]> => {
   try {
     const response = await apiClient.get('/api/vendedores', {
       params: { search: term, per_page: 5 }
     })
     return response.data?.data?.map((item: any) => ({
       id: item.id_vendedor,
       type: 'vendedor',
       title: item.nome_vendedor,
       subtitle: item.email || item.telefone || '',
       route: { name: 'vendedores-editar', params: { id: item.id_vendedor } }
     })) || []
   } catch (error) {
     console.error('Failed to search vendors:', error)
     return []
   }
 }

const performSearch = async () => {
  // Clear previous debounce timer
  if (searchDebounceTimer.value) {
    clearTimeout(searchDebounceTimer.value)
    searchDebounceTimer.value = null
  }
  
  // Set searching state
  isSearching.value = true
  
  // If empty search term, clear results
  if (!searchTerm.value.trim()) {
    searchResults.value = []
    isSearching.value = false
    return
  }
  
  try {
    // Perform all searches in parallel
    const [clients, orders, products, vendors] = await Promise.all([
      searchClients(searchTerm.value),
      searchOrders(searchTerm.value),
      searchProducts(searchTerm.value),
      searchVendors(searchTerm.value)
    ])
    
    // Combine and flatten results
    const allResults = [...clients, ...orders, ...products, ...vendors]
    searchResults.value = allResults
  } catch (error) {
    console.error('Failed to perform search:', error)
    searchResults.value = []
  } finally {
    isSearching.value = false
  }
}

const handleSearchInput = () => {
  // Clear existing timeout
  if (searchDebounceTimer.value) {
    clearTimeout(searchDebounceTimer.value)
  }
  
  // Set new timeout (300ms debounce)
  searchDebounceTimer.value = setTimeout(() => {
    performSearch()
  }, 300)
}

const handleSearchEnter = () => {
  // Immediate search on Enter key
  if (searchDebounceTimer.value) {
    clearTimeout(searchDebounceTimer.value)
  }
  performSearch()
}

const navigateToResult = (result: SearchResult) => {
  if (result.route) {
    router.push(result.route)
    // Optionally close search dropdown
    searchTerm.value = ''
    searchResults.value = []
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

onUnmounted(() => {
  if (searchDebounceTimer.value) {
    clearTimeout(searchDebounceTimer.value)
  }
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
                v-model="searchTerm"
                type="text" 
                placeholder="Search customers, orders, products..." 
                aria-label="Global search"
                class="border-0 focus-visible:ring-0 text-sm bg-transparent h-10 w-full" 
                @input="handleSearchInput"
                @keyup.enter="handleSearchEnter"
              />
           </div>

            <!-- Search Results Dropdown -->
            <div v-if="isSearching" class="absolute top-full left-0 right-0 mt-2">
              <div class="px-4 py-2 text-center text-sm text-muted-foreground">
                Searching...
              </div>
            </div>
            <div v-else-if="searchResults.length > 0" class="absolute top-full left-0 right-0 mt-2 w-full md:w-96 bg-card border rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto">
              <div class="px-4 py-2 border-b">
                <span class="font-semibold text-sm">{{ searchResults.length }} results</span>
              </div>
              <div class="divide-y">
                <div v-for="(result, index) in searchResults" :key="index" class="px-4 py-3 hover:bg-accent/50 transition-colors cursor-pointer" @click="navigateToResult(result)">
                  <div class="flex items-start gap-3">
                    <!-- Entity icon based on type -->
                    <div class="flex-shrink-0 h-8 w-8 flex items-center justify-center">
                      <template v-if="result.type === 'cliente'">
                        <Users class="w-5 h-5 text-primary" />
                      </template>
                      <template v-else-if="result.type === 'pedido'">
                        <ShoppingCart class="w-5 h-5 text-primary" />
                      </template>
                      <template v-else-if="result.type === 'produto'">
                        <Package class="w-5 h-5 text-primary" />
                      </template>
                      <template v-else-if="result.type === 'vendedor'">
                        <Users class="w-5 h-5 text-primary" />
                      </template>
                      <template v-else>
                        <Users class="w-5 h-5 text-primary" />
                      </template>
                    </div>
                    <div class="flex-1">
                      <p class="text-sm font-medium">{{ result.title }}</p>
                      <p class="text-xs text-muted-foreground">{{ result.subtitle }}</p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="searchResults.length === 0 && searchTerm" class="px-4 py-4 text-center text-sm text-muted-foreground">
                No results found for "{{ searchTerm }}"
              </div>
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
