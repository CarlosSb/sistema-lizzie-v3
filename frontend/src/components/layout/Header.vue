<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import Button from '../ui/Button.vue'
import { useTheme } from '../../composables/useTheme'
import {
  PersonOutline,
  NotificationsOutline,
  MenuOutline,
  SunnyOutline,
  MoonOutline,
  CheckmarkDoneOutline
} from '@vicons/ionicons5'


const props = defineProps<{
  sidebarCollapsed?: boolean
}>()

const emit = defineEmits<{
  toggleSidebar: () => void
}>()

const router = useRouter()
const authStore = useAuthStore()
const { isDark, toggleTheme } = useTheme()

// Estado das notificações
const showNotifications = ref(false)
const notifications = ref([
  {
    id: 1,
    title: 'Novo pedido criado',
    message: 'Pedido #1234 foi criado com sucesso',
    time: '2 min atrás',
    read: false
  },
  {
    id: 2,
    title: 'Cliente atualizado',
    message: 'Dados do cliente João Silva foram atualizados',
    time: '15 min atrás',
    read: false
  },
  {
    id: 3,
    title: 'Estoque baixo',
    message: 'Produto "Camiseta Básica" está com estoque baixo',
    time: '1 hora atrás',
    read: true
  }
])

const unreadNotifications = computed(() =>
  notifications.value.filter(n => !n.read).length
)

const userInitials = computed(() => {
  if (!authStore.user) return 'U'
  const name = authStore.user.name || authStore.user.username || ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const userDisplayName = computed(() => {
  return authStore.user?.name || authStore.user?.username || 'Usuário'
})

// Functions
const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
}

const markAsRead = (notificationId) => {
  const notification = notifications.value.find(n => n.id === notificationId)
  if (notification) {
    notification.read = true
  }
}

const markAllAsRead = () => {
  notifications.value.forEach(n => n.read = true)
}

const goToProfile = () => {
  router.push('/perfil')
}

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
          :aria-label="'Alternar menu lateral'"
          :aria-expanded="!sidebarCollapsed"
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



      <!-- Right Section -->
      <div class="header-right">
        <!-- Theme Toggle -->
        <Button
          variant="ghost"
          size="sm"
          @click="toggleTheme"
          class="theme-toggle header-btn"
          :aria-label="isDark ? 'Alternar para tema claro' : 'Alternar para tema escuro'"
        >
          <component :is="isDark ? SunnyOutline : MoonOutline" />
        </Button>

        <!-- Notifications Dropdown -->
        <div class="notifications-dropdown">
          <Button
            variant="ghost"
            size="sm"
            @click="toggleNotifications"
            class="notifications-btn header-btn"
            :aria-label="'Notificações - ' + unreadNotifications + ' não lidas'"
            aria-haspopup="menu"
            :aria-expanded="showNotifications"
          >
            <NotificationsOutline />
            <span v-if="unreadNotifications > 0" class="notification-badge">
              {{ unreadNotifications > 9 ? '9+' : unreadNotifications }}
            </span>
          </Button>

          <!-- Notifications Panel -->
          <div v-if="showNotifications" class="notifications-panel">
            <div class="notifications-header">
              <h3 class="notifications-title">Notificações</h3>
              <Button
                v-if="unreadNotifications > 0"
                variant="ghost"
                size="sm"
                @click="markAllAsRead"
                class="mark-all-read-btn"
              >
                <CheckmarkDoneOutline />
                Marcar todas como lidas
              </Button>
            </div>

            <div class="notifications-list">
              <div
                v-for="notification in notifications"
                :key="notification.id"
                :class="['notification-item', { 'notification-unread': !notification.read }]"
                @click="markAsRead(notification.id)"
              >
                <div class="notification-content">
                  <h4 class="notification-title">{{ notification.title }}</h4>
                  <p class="notification-message">{{ notification.message }}</p>
                  <span class="notification-time">{{ notification.time }}</span>
                </div>
                <div v-if="!notification.read" class="notification-dot"></div>
              </div>

              <div v-if="notifications.length === 0" class="no-notifications">
                <NotificationsOutline class="no-notifications-icon" />
                <p>Nenhuma notificação</p>
              </div>
            </div>
          </div>
        </div>

        <!-- User Menu -->
        <Button
          variant="ghost"
          size="sm"
          @click="goToProfile"
          class="user-btn header-btn"
          :aria-label="'Ir para perfil do usuário ' + userDisplayName"
        >
          <div class="user-avatar">
            <span class="user-initials">{{ userInitials }}</span>
          </div>
          <span class="user-name">{{ userDisplayName }}</span>
        </Button>
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
  z-index: var(--z-header);
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
  padding: var(--space-3) var(--space-6);
  max-width: 100%;
  margin: 0 auto;
  min-height: 64px;
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



.header-right {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  min-width: 140px;
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

/* Notifications Dropdown */
.notifications-dropdown {
  position: relative;
}

.notifications-panel {
  position: absolute;
  top: calc(100% + var(--space-2));
  right: 0;
  width: 380px;
  max-width: calc(100vw - var(--space-4));
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  z-index: var(--z-popover);
  overflow: hidden;
}

[data-theme="dark"] .notifications-panel {
  background: var(--gray-800);
  border-color: var(--gray-700);
}

.notifications-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4);
  border-bottom: 1px solid var(--gray-200);
}

[data-theme="dark"] .notifications-header {
  border-bottom-color: var(--gray-700);
}

.notifications-title {
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
  color: var(--gray-900);
  margin: 0;
}

[data-theme="dark"] .notifications-title {
  color: var(--gray-100);
}

.mark-all-read-btn {
  color: var(--primary-600);
  font-size: var(--text-sm);
}

.mark-all-read-btn:hover {
  background: var(--primary-50);
}

[data-theme="dark"] .mark-all-read-btn {
  color: var(--primary-400);
}

[data-theme="dark"] .mark-all-read-btn:hover {
  background: rgba(59, 130, 246, 0.1);
}

.notifications-list {
  max-height: 400px;
  overflow-y: auto;
}

.notification-item {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-4);
  border-bottom: 1px solid var(--gray-100);
  cursor: pointer;
  transition: background-color var(--transition-fast);
}

.notification-item:hover {
  background: var(--gray-100);
}

[data-theme="dark"] .notification-item {
  border-bottom-color: var(--gray-700);
}

[data-theme="dark"] .notification-item:hover {
  background: var(--gray-700);
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-unread {
  background: var(--primary-50);
}

[data-theme="dark"] .notification-unread {
  background: rgba(59, 130, 246, 0.1);
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-title {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--gray-900);
  margin: 0 0 var(--space-1) 0;
  line-height: 1.4;
}

[data-theme="dark"] .notification-title {
  color: var(--gray-100);
}

.notification-message {
  font-size: var(--text-sm);
  color: var(--gray-600);
  margin: 0 0 var(--space-1) 0;
  line-height: 1.4;
  word-break: break-word;
}

[data-theme="dark"] .notification-message {
  color: var(--gray-300);
}

.notification-time {
  font-size: var(--text-xs);
  color: var(--gray-500);
  font-weight: var(--font-medium);
}

[data-theme="dark"] .notification-time {
  color: var(--gray-400);
}

.notification-dot {
  width: 8px;
  height: 8px;
  background: var(--primary-500);
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: var(--space-2);
}

.no-notifications {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--space-8);
  color: var(--gray-500);
  text-align: center;
}

.no-notifications-icon {
  font-size: 48px;
  margin-bottom: var(--space-3);
  opacity: 0.5;
}

[data-theme="dark"] .no-notifications {
  color: var(--gray-400);
}

.header-btn {
  width: 40px;
  height: 40px;
  padding: 0.5rem;
  border-radius: var(--radius-lg);
}

/* Responsive Design */
@media (max-width: var(--bp-tablet)) {
  .header-content {
    padding: var(--space-3) var(--space-4);
    min-height: 56px;
  }

  .sidebar-toggle {
    display: flex;
  }

  .brand-info {
    display: none;
  }

  .header-right {
    gap: var(--space-1);
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
    width: 36px;
    height: 36px;
    padding: 0.375rem;
  }

  .header-btn :deep(.n-icon) {
    font-size: var(--icon-size-mobile) !important;
  }

  .sidebar-toggle :deep(.n-icon) {
    font-size: var(--icon-size-desktop) !important;
  }

  /* Ajustar painel de notificações para mobile */
  .notifications-panel {
    width: calc(100vw - var(--space-4));
    right: calc(-1 * var(--space-2));
  }
}

@media (max-width: var(--bp-small)) {
  .header-content {
    padding: var(--space-2) var(--space-3);
    min-height: 48px;
  }

  .header-btn {
    width: 32px;
    height: 32px;
    padding: 0.25rem;
  }

  .header-btn :deep(.n-icon) {
    font-size: var(--icon-size-small) !important;
  }

  .sidebar-toggle :deep(.n-icon) {
    font-size: var(--icon-size-mobile) !important;
  }

  .brand-logo {
    width: 32px;
    height: 32px;
  }

  .logo-icon {
    font-size: 14px;
  }

  .user-avatar {
    width: 28px;
    height: 28px;
  }

  .user-initials {
    font-size: 10px;
  }

  .notifications-panel {
    width: calc(100vw - var(--space-2));
    right: calc(-1 * var(--space-1));
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