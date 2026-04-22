import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
  getRefreshToken as authServiceGetRefreshToken,
  getToken as authServiceGetToken,
  getUser as authServiceGetUser,
  login as authServiceLogin,
  logout as authServiceLogout,
  me as authServiceMe,
  type AuthUser,
} from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(authServiceGetToken())
  const refreshToken = ref<string | null>(authServiceGetRefreshToken())
  const user = ref<AuthUser | null>(authServiceGetUser())

  const isAuthenticated = computed(() => !!token.value)

  const login = async (username: string, password: string) => {
    try {
      const payload = await authServiceLogin(username, password)
      token.value = payload.access_token
      refreshToken.value = payload.refresh_token
      user.value = payload.user
    } catch (error: any) {
      authServiceLogout()
      token.value = null
      refreshToken.value = null
      user.value = null
      throw error
    }
  }

  const logout = () => {
    authServiceLogout(); // authServiceLogout will remove token from localStorage
    token.value = null;
    refreshToken.value = null;
    user.value = null;
  }

  const refreshUser = async () => {
    if (!token.value) return
    try {
      const me = await authServiceMe()
      user.value = me
      localStorage.setItem('user', JSON.stringify(me))
    } catch {
      logout()
    }
  }

  // Future: Function to check if token is valid/expired
  // const checkAuth = async () => { ... }

  return {
    token,
    refreshToken,
    user,
    isAuthenticated,
    login,
    logout,
    refreshUser,
  };
});
