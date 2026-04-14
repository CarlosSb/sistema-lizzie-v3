<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import Button from '../ui/Button.vue'
import { useTheme } from '../../composables/useTheme'
import {
  PersonOutline,
  NotificationsOutline,
  SearchOutline,
  MenuOutline,
  ChevronDown,
  CloseOutline,
  SunnyOutline,
  MoonOutline
} from '@vicons/ionicons5'


const props = defineProps<{
  sidebarCollapsed?: boolean
}>()

const emit = defineEmits<{
  toggleSidebar: () => void
}>()

const authStore = useAuthStore()
const { isDark, toggleTheme } = useTheme()

const userInitials = computed(() => {
  if (!authStore.user) return 'U'
  const name = authStore.user.name || authStore.user.username || ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const userDisplayName = computed(() => {
  return authStore.user?.name || authStore.user?.username || 'Usuário'
})

// Animation state for header entrance
const headerAnimated = ref(false)
onMounted(() => {
  const headerEl = document.querySelector('.header')
  if (headerEl) headerEl.style.animationPlayState = 'running'
})
</script>

<template>
  <header class="header">
    <div class="header-content">
      <!-- Left Section -->
      <div class="header-left">
        <Button
          variant="ghost"
          size="sm"
          @click="$emit('toggleSidebar')"
          class="sidebar-toggle"
        >
          <MenuOutline />
        </Button>

        <div class="brand">
          <div class="brand-logo">
            <div class="logo-icon">LZ</div>
          </div>
          <div class="brand-info">
            <h1 class="brand-name">Sistema Lizzie</h1>
            <p class="brand-tagline">Gestão Empresarial</p>
          </div>
        </div>
      </div>

      <!-- Center Section - Search -->
      <div class="header-center">
        <div class="search-container">
          <div class="search-icon-wrapper">
            <SearchOutline class="search-icon" />
          </div>
          <input
            type="text"
            placeholder="Buscar clientes, pedidos, produtos..."
            class="search-input"
          />
        </div>
      </div>

      <!-- Right Section -->
      <div class="header-right">
        <!-- Theme Toggle -->
        <Button
          variant="ghost"
          size="sm"
          @click="toggleTheme"
          class="theme-toggle header-btn"
        >
          <component :is="isDark ? SunnyOutline : MoonOutline" />
        </Button>

        <!-- Notifications -->
        <Button variant="ghost" size="sm" class="notifications-btn header-btn">
          <NotificationsOutline />
          <span class="notification-badge">3</span>
        </Button>

        <!-- User Menu -->
        <div class="user-menu">
          <Button variant="ghost" size="sm" class="user-btn header-btn">
            <div class="user-avatar">
              <span class="user-initials">{{ userInitials }}</span>
            </div>
            <span class="user-name">{{ userDisplayName }}</span>
            <ChevronDown class="dropdown-icon" />
          </Button>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
.header {
  background-color: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  position: sticky;
  top: 0;
  z-index: var(--z-fixed);
  backdrop-filter: blur(8px);
  transition: all var(--transition-fast);
  animation: headerSlideIn 0.6s ease-out;
  animation-play-state: paused;
}

@keyframes headerSlideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4) var(--space-6);
  max-width: 100%;
  margin: 0 auto;
}

.header-left {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  min-width: 200px;
}

.sidebar-toggle {
  display: none;
}

.brand {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.brand-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #1a56db, #1e40af);
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.logo-icon {
  font-size: 16px;
  font-weight: var(--font-bold);
  color: white;
  letter-spacing: -0.5px;
}

.brand-name {
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
  color: var(--gray-900);
  margin: 0;
}

.brand-tagline {
  font-size: var(--text-xs);
  color: var(--gray-500);
  margin: 0;
  font-weight: var(--font-medium);
}

.header-center {
  flex: 1;
  max-width: 500px;
  margin: 0 var(--space-8);
}

.search-container {
  position: relative;
  width: 100%;
  display: flex;
  align-items: center;
}

.search-icon-wrapper {
  position: absolute;
  left: var(--space-3);
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  color: var(--gray-500);
  transition: color var(--transition-fast);
}

.search-input {
  width: 100%;
  padding: var(--space-3) var(--space-4) var(--space-3) var(--space-11);
  border: 1px solid var(--gray-300);
  border-radius: var(--radius-lg);
  background-color: var(--gray-25);
  font-size: var(--text-sm);
  transition: all var(--transition-fast);
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-500);
  background-color: white;
  box-shadow: 0 0 0 3px var(--primary-100);
}

.search-input:focus + .search-icon-wrapper {
  color: var(--primary-600);
}

.header-right {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  min-width: 180px;
  justify-content: flex-end;
}

.theme-toggle,
.notifications-btn {
  position: relative;
}

.notification-badge {
  position: absolute;
  top: -4px;
  right: -4px;
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
  border: 2px solid var(--gray-50);
}

.user-menu {
  margin-left: var(--space-2);
}

.user-btn {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-3);
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--secondary-500), var(--secondary-600));
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-sm);
}

.user-initials {
  font-size: 12px;
  font-weight: var(--font-bold);
  color: white;
}

.user-name {
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
  color: var(--gray-700);
}

.dropdown-icon {
  font-size: 14px;
  color: var(--gray-500);
  transition: transform var(--transition-fast);
}

.user-btn:hover .dropdown-icon {
  transform: rotate(180deg);
}

.header-btn {
  width: 44px;
  height: 44px;
  padding: 0.625rem;
}

/* Responsive Design */
@media (max-width: var(--bp-tablet)) {
  .header-center {
    display: none;
  }

  .sidebar-toggle {
    display: flex;
  }

  .brand-info {
    display: none;
  }

  .header-right {
    gap: var(--space-2);
    min-width: auto;
  }

  .user-name {
    display: none;
  }

  .user-avatar {
    width: 32px;
    height: 32px;
  }

  .header-btn {
    width: 44px;
    height: 44px;
  }

  .header-btn :deep(.n-icon) {
    font-size: var(--icon-size-mobile) !important;
  }

  .sidebar-toggle :deep(.n-icon) {
    font-size: var(--icon-size-desktop) !important;
  }
}

@media (max-width: var(--bp-small)) {
  .header-btn :deep(.n-icon) {
    font-size: var(--icon-size-small) !important;
  }

  .sidebar-toggle :deep(.n-icon) {
    font-size: var(--icon-size-mobile) !important;
  }
}

@media (min-width: var(--bp-desktop)) {
  .header-center {
    max-width: 600px;
  }
}

/* Dark theme */
[data-theme="dark"] .header {
  background-color: #0f172a;
  border-bottom-color: #334155;
}

[data-theme="dark"] .brand-name {
  color: #f1f5f9;
}

[data-theme="dark"] .brand-tagline {
  color: #94a3b8;
}

[data-theme="dark"] .search-input {
  background-color: #1e293b;
  border-color: #475569;
  color: #f1f5f9;
}

[data-theme="dark"] .search-input:focus {
  background-color: #334155;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px #1e3a8a;
}

[data-theme="dark"] .user-name {
  color: #cbd5e1;
}

[data-theme="dark"] .notification-badge {
  border-color: var(--gray-900);
}

[data-theme="dark"] .dropdown-icon {
  color: #94a3b8;
}
</style>