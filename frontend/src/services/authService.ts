import axios from 'axios'
import apiClient from '@/lib/axios'

export interface AuthUser {
  id: number
  nome: string
  usuario: string
  nivel: string
}

export interface AuthTokens {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

interface LoginPayload {
  user: AuthUser
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

interface ApiResponse<T> {
  success: boolean
  message?: string
  data: T
}

const TOKEN_KEY = 'token'
const REFRESH_TOKEN_KEY = 'refresh_token'
const USER_KEY = 'user'

export const login = async (username: string, password: string): Promise<LoginPayload> => {
  try {
    const response = await apiClient.post<ApiResponse<LoginPayload>>('/api/auth/login', {
      usuario: username,
      senha: password,
    })

    const payload = response.data?.data

    if (!payload?.access_token) {
      throw new Error('Login falhou: token ausente.')
    }

    localStorage.setItem(TOKEN_KEY, payload.access_token)
    localStorage.setItem(REFRESH_TOKEN_KEY, payload.refresh_token)
    localStorage.setItem(USER_KEY, JSON.stringify(payload.user))

    return payload
  } catch (error: any) {
    if (axios.isAxiosError(error) && error.response) {
      const message = error.response.data?.message || error.response.data?.error || 'Credenciais inválidas ou erro no servidor.'
      throw new Error(message)
    }
    throw new Error(error.message || 'Erro de conexão com o servidor.')
  }
}

export const me = async (): Promise<AuthUser> => {
  const response = await apiClient.get<ApiResponse<AuthUser>>('/api/auth/me')
  return response.data.data
}

export const logout = (): void => {
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(REFRESH_TOKEN_KEY)
  localStorage.removeItem(USER_KEY)
}

export const getToken = (): string | null => {
  const token = localStorage.getItem(TOKEN_KEY)
  return token || null
}

export const getRefreshToken = (): string | null => {
  const token = localStorage.getItem(REFRESH_TOKEN_KEY)
  return token || null
}

export const getUser = (): AuthUser | null => {
  const raw = localStorage.getItem(USER_KEY)
  if (!raw) return null
  try {
    return JSON.parse(raw) as AuthUser
  } catch {
    return null
  }
}
