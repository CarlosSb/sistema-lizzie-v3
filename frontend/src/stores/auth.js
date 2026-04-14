import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as apiLogin, logout as apiLogout, getUser } from '../composables/useApi'

export const useAuthStore = defineStore('auth', () => {
  const accessToken = ref(localStorage.getItem('access_token') || null)
  const refreshToken = ref(localStorage.getItem('refresh_token') || null)
  const user = ref(getUser())

  const isAuthenticated = computed(() => !!accessToken.value)
  const isAdmin = computed(() => user.value?.nivel === 'admin')
  const isVendedor = computed(() => user.value?.nivel === 'vendedor')

  async function login(usuario, senha) {
    const userData = await apiLogin(usuario, senha)
    accessToken.value = localStorage.getItem('access_token')
    refreshToken.value = localStorage.getItem('refresh_token')
    user.value = userData
    return userData
  }

  async function logout() {
    await apiLogout()
    accessToken.value = null
    refreshToken.value = null
    user.value = null
  }

  function refreshUser() {
    user.value = getUser()
  }

  return {
    accessToken,
    refreshToken,
    user,
    isAuthenticated,
    isAdmin,
    isVendedor,
    login,
    logout,
    refreshUser
  }
})