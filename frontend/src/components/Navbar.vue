<script setup>
import { onMounted, onUnmounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useAlertas } from '../composables/useAlertas'
import { NButton, NIcon, NBadge, NPopover, NList, NListItem, NEmpty } from 'naive-ui'
import { SunnyOutline, MoonOutline, NotificationsOutline } from '@vicons/ionicons5'

const props = defineProps({
  isDark: Boolean
})

const emit = defineEmits(['toggle-theme'])

const router = useRouter()
const authStore = useAuthStore()

const { alertas, naoLidosCount, init: initAlertas, stopConnection: stopAlertas, marcarLido } = useAlertas()

onMounted(() => {
  if (authStore.isAuthenticated) {
    initAlertas()
  }
})

onUnmounted(() => {
  stopAlertas()
})

async function handleLogout() {
  stopAlertas()
  await authStore.logout()
  router.push('/login')
}

function handleMarcarLido(id) {
  marcarLido(id)
}
</script>

<template>
  <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <RouterLink to="/" class="text-xl font-semibold text-gray-800 dark:text-white">
            Lizzie - Amor de Mãe
          </RouterLink>
          <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
            <RouterLink
              v-if="authStore.isAuthenticated"
              to="/"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900"
              active-class="text-blue-600 border-b-2 border-blue-600"
            >
              Dashboard
            </RouterLink>
            <RouterLink
              v-if="authStore.isAuthenticated && authStore.isAdmin"
              to="/clientes"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900"
              active-class="text-blue-600 border-b-2 border-blue-600"
            >
              Clientes
            </RouterLink>
            <RouterLink
              v-if="authStore.isAuthenticated && authStore.isAdmin"
              to="/produtos"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900"
              active-class="text-blue-600 border-b-2 border-blue-600"
            >
              Produtos
            </RouterLink>
            <RouterLink
              v-if="authStore.isAuthenticated"
              to="/pedidos"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900"
              active-class="text-blue-600 border-b-2 border-blue-600"
            >
              Pedidos
            </RouterLink>
            <RouterLink
              v-if="authStore.isAuthenticated && authStore.isAdmin"
              to="/estoque"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900"
              active-class="text-blue-600 border-b-2 border-blue-600"
            >
              Estoque
            </RouterLink>
            <RouterLink
              v-if="authStore.isAuthenticated && authStore.isAdmin"
              to="/relatorios"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900"
              active-class="text-blue-600 border-b-2 border-blue-600"
            >
              Relatórios
            </RouterLink>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <NPopover trigger="click" placement="bottom-end">
            <template #trigger>
              <NBadge :value="naoLidosCount" :max="9" :show="naoLidosCount > 0">
                <NButton quaternary circle>
                  <template #icon>
                    <NIcon><NotificationsOutline /></NIcon>
                  </template>
                </NButton>
              </NBadge>
            </template>
            <div class="w-64 max-h-80 overflow-y-auto">
              <NList v-if="alertas.length > 0" bordered>
                <NListItem v-for="alerta in alertas.slice(0, 5)" :key="alerta.id_alerta">
                  <div class="flex justify-between items-start py-1">
                    <div>
                      <div class="font-medium text-sm">{{ alerta.titulo }}</div>
                      <div class="text-xs text-gray-500">{{ alerta.mensagem }}</div>
                    </div>
                    <NButton text size="tiny" @click="handleMarcarLido(alerta.id_alerta)">Ler</NButton>
                  </div>
                </NListItem>
              </NList>
              <NEmpty v-else description="Sem alertas" />
            </div>
          </NPopover>
          
          <NButton quaternary circle @click="emit('toggle-theme')" :title="isDark ? 'Modo Claro' : 'Modo Escuro'">
            <template #icon>
              <NIcon><SunnyOutline v-if="isDark" /><MoonOutline v-else /></NIcon>
            </template>
          </NButton>
          <button
            v-if="authStore.isAuthenticated"
            @click="handleLogout"
            class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900"
          >
            Sair ({{ authStore.user?.nome }})
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>