<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import Button from '../ui/Button.vue'
import {
  HomeOutline,
  DocumentTextOutline,
  PeopleOutline,
  CubeOutline,
  BarChartOutline,
  SettingsOutline,
  LogOutOutline,
  ChevronBackOutline
} from '@vicons/ionicons5'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const props = defineProps({
  collapsed: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:collapsed'])

const navigationItems = [
  {
    name: 'Dashboard',
    path: '/',
    icon: HomeOutline,
    badge: null
  },
  {
    name: 'Pedidos',
    path: '/pedidos',
    icon: DocumentTextOutline,
    badge: '12'
  },
  {
    name: 'Clientes',
    path: '/clientes',
    icon: PeopleOutline,
    badge: null
  },
  {
    name: 'Produtos',
    path: '/produtos',
    icon: CubeOutline,
    badge: '3'
  },
  {
    name: 'Estoque',
    path: '/estoque',
    icon: BarChartOutline,
    badge: '2'
  },
  {
    name: 'Relatórios',
    path: '/relatorios',
    icon: BarChartOutline,
    badge: null
  },
  {
    name: 'Configurações',
    path: '/configuracoes',
    icon: SettingsOutline,
    badge: null
  }
]

const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/')
}

const navigateTo = (path) => {
  router.push(path)
}

const logout = async () => {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    console.error('Logout error:', error)
  }
}

const toggleSidebar = () => {
  emit('update:collapsed', !props.collapsed)
}
</script>

<template>
  <aside :class="['sidebar', { 'sidebar-collapsed': collapsed, 'sidebar-open': !collapsed }]">
    <div class="sidebar-content">
      <!-- Header -->
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <div class="brand-icon">LZ</div>
          <span v-if="!collapsed" class="brand-text">Lizzie</span>
        </div>


      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <ul class="nav-list">
          <li
            v-for="item in navigationItems"
            :key="item.path"
            class="nav-item"
          >
            <button
              :class="['nav-link', { 'nav-link-active': isActive(item.path) }]"
              @click="navigateTo(item.path)"
              :aria-label="'Navegar para ' + item.name"
              :aria-current="isActive(item.path) ? 'page' : undefined"
            >
              <component :is="item.icon" class="nav-icon" />
              <span v-if="!collapsed" class="nav-text">{{ item.name }}</span>
              <span v-if="!collapsed && item.badge" class="nav-badge" :aria-label="item.badge + ' itens'">{{ item.badge }}</span>
            </button>
          </li>
        </ul>
      </nav>

      <!-- Footer -->
      <div class="sidebar-footer">
        <Button
          variant="ghost"
          size="sm"
          @click="logout"
          class="logout-btn"
          :block="!collapsed"
          aria-label="Fazer logout do sistema"
        >
          <LogOutOutline class="nav-icon" />
          <span v-if="!collapsed" class="nav-text">Sair</span>
        </Button>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  width: 280px;
  background-color: var(--gray-50);
  border-right: 1px solid var(--gray-200);
  display: flex;
  flex-direction: column;
  transform: translateX(-100%);
  transition: transform var(--transition-normal);
  z-index: var(--z-fixed);
  backdrop-filter: blur(8px);
}

.sidebar-open {
  transform: translateX(0);
}

.sidebar-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: var(--space-4);
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-8);
  padding-bottom: var(--space-4);
  border-bottom: 1px solid var(--gray-200);
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.brand-icon {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #1a56db, #1e40af);
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: white;
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.brand-text {
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
  color: var(--gray-900);
}



.sidebar-nav {
  flex: 1;
  margin-bottom: var(--space-4);
}

.nav-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.nav-item {
  margin-bottom: var(--space-1);
}

.nav-link {
  width: 100%;
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-3);
  border-radius: var(--radius-lg);
  text-decoration: none;
  color: var(--gray-600);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  transition: all var(--transition-fast);
  position: relative;
  overflow: hidden;
}

.nav-link:hover {
  background-color: var(--gray-100);
  color: var(--gray-900);
  transform: translateX(4px);
}

.nav-link-active {
  background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
  color: var(--primary-700);
  font-weight: var(--font-semibold);
  box-shadow: var(--shadow-sm);
}

.nav-link-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
  border-radius: 0 2px 2px 0;
}

.nav-icon {
  font-size: 16px !important;
  flex-shrink: 0;
  width: 16px !important;
  height: 16px !important;
}

.nav-text {
  flex: 1;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-badge {
  background-color: var(--error-500);
  color: white;
  font-size: 10px;
  font-weight: var(--font-bold);
  padding: 2px 6px;
  border-radius: 10px;
  min-width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sidebar-footer {
  margin-top: auto;
  padding-top: var(--space-4);
  border-top: 1px solid var(--gray-200);
}

.logout-btn {
  color: var(--error-600);
  justify-content: flex-start;
}

.logout-btn:hover {
  background-color: var(--error-50);
  color: var(--error-700);
}



/* Responsive */
@media (max-width: var(--bp-tablet)) {
  .sidebar {
    position: fixed;
    top: 64px;
    height: calc(100vh - 64px);
    transform: translateX(-100%);
    transition: transform var(--transition-normal);
    z-index: var(--z-modal);
    box-shadow: var(--shadow-xl);
  }
}

@media (min-width: var(--bp-tablet)) {
  .sidebar {
    transform: translateX(0);
  }
}

@media (max-width: var(--bp-medium)) {
  .sidebar {
    top: 56px;
    height: calc(100vh - 56px);
  }
}

@media (max-width: var(--bp-mobile)) {
  .sidebar {
    top: 0;
    height: 100vh;
  }

  .nav-link {
    padding: var(--space-4) var(--space-3);
    min-height: 48px; /* Better touch target */
  }

  .nav-text {
    font-size: var(--text-sm);
  }

  .nav-icon {
    font-size: 16px !important;
    width: 16px !important;
    height: 16px !important;
  }

  .collapse-btn .n-icon {
    font-size: 18px !important;
  }
}

@media (max-width: var(--bp-small)) {
  .nav-icon {
    font-size: 14px !important;
    width: 14px !important;
    height: 14px !important;
  }
}

/* Dark theme */
[data-theme="dark"] .sidebar {
  background-color: #0f172a;
  border-right-color: #334155;
}

[data-theme="dark"] .sidebar-header {
  border-bottom-color: #334155;
}

[data-theme="dark"] .brand-text {
  color: #f1f5f9;
}

[data-theme="dark"] .nav-link {
  color: #94a3b8;
}

[data-theme="dark"] .nav-link:hover {
  background-color: #1e293b;
  color: #f1f5f9;
}

[data-theme="dark"] .nav-link-active {
  background: linear-gradient(135deg, #1e3a8a, #1e40af);
  color: #dbeafe;
}

[data-theme="dark"] .sidebar-footer {
  border-top-color: #334155;
}

[data-theme="dark"] .logout-btn {
  color: #f87171;
}

[data-theme="dark"] .logout-btn:hover {
  background-color: #7f1d1d;
  color: #fecaca;
}
</style>