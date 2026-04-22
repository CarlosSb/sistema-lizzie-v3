import axios, { AxiosError } from 'axios'
import { useAuthStore } from '@/stores/auth'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

const isAuthRoute = (url?: string) => {
  if (!url) return false
  return url.startsWith('/api/auth/')
}

async function refreshAccessToken(): Promise<string | null> {
  const refreshToken = localStorage.getItem('refresh_token')
  if (!refreshToken) return null

  const response = await axios.post(
    `${API_BASE_URL}/api/auth/refresh`,
    { refresh_token: refreshToken },
    {
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
    }
  )

  const data = response.data?.data
  const newAccessToken = data?.access_token as string | undefined
  const newRefreshToken = data?.refresh_token as string | undefined

  if (newAccessToken) localStorage.setItem('token', newAccessToken)
  if (newRefreshToken) localStorage.setItem('refresh_token', newRefreshToken)

  try {
    const authStore = useAuthStore()
    if (newAccessToken) authStore.token = newAccessToken
    if (newRefreshToken) authStore.refreshToken = newRefreshToken
  } catch {
    // ignore store update if pinia isn't ready
  }

  return newAccessToken || null
}

apiClient.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    const token = authStore.token

    if (token && !isAuthRoute(config.url)) {
      config.headers.Authorization = `Bearer ${token}`
    }

    return config
  },
  (error) => Promise.reject(error)
)

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest: any = error.config
    const status = error.response?.status

    if (
      status === 401 &&
      originalRequest &&
      !originalRequest._retry &&
      !isAuthRoute(originalRequest.url)
    ) {
      originalRequest._retry = true

      try {
        const newToken = await refreshAccessToken()
        if (!newToken) throw new Error('Refresh token ausente ou inválido')

        originalRequest.headers = originalRequest.headers || {}
        originalRequest.headers.Authorization = `Bearer ${newToken}`
        return apiClient(originalRequest)
      } catch {
        try {
          const authStore = useAuthStore()
          authStore.logout()
        } catch {
          localStorage.removeItem('token')
          localStorage.removeItem('refresh_token')
          localStorage.removeItem('user')
        }

        if (typeof window !== 'undefined') {
          window.location.href = '/login'
        }
      }
    }

    return Promise.reject(error)
  }
)

export default apiClient
