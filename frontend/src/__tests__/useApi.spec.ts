import { expect, vi, beforeEach } from 'vitest'
import { vi as viMock } from 'vitest'

import { login, logout, getUser, isAuthenticated, isAdmin, isVendedor } from '@/composables/useApi'
import { setLoggedInUser, clearAuthState } from '@/stores/auth'

vi.mock('@/composables/useApi')

describe('useApi composable', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    clearAuthState()
  })

  it('should call login API and store tokens', async () => {
    // Mock API response
    vi.spyOn(require('@/composables/useApi'), 'login').mockResolvedValueOnce({
      data: {
        data: {
          user: { id: 1, nome: 'Admin', usuario: 'admin', controle_acesso: 'admin' },
          access_token: 'mock_access_token',
          refresh_token: 'mock_refresh_token',
          token_type: 'Bearer',
          expires_in: 3600,
        }
      }
    })

    const user = await login('admin', '123')
    expect(user.id).toBe(1)
    expect(localStorage.getItem('access_token')).toBe('mock_access_token')
    expect(localStorage.getItem('refresh_token')).toBe('mock_refresh_token')
    expect(localStorage.getItem('user')).toBe(JSON.stringify({ id: 1, nome: 'Admin', usuario: 'admin' }))
  })

  it('should throw error on login failure', async () => {
    vi.spyOn(require('@/composables/useApi'), 'login').mockRejectedValueOnce({ data: { data: { message: 'Credenciais inválidas' } } })
    await expect(login('wrong', 'wrong')).rejects.toThrow('Credenciais inválidas')
  })

  it('should logout and clear auth state', async () => {
    vi.spyOn(require('@/composables/useApi'), 'logout').mockResolvedValueOnce({})
    await logout()
    expect(localStorage.getItem('access_token')).toBeNull()
    expect(localStorage.getItem('refresh_token')).toBeNull()
    expect(localStorage.getItem('user')).toBeNull()
  })

  it('should return current user from localStorage', () => {
    const mockUser = { id: 2, nome: 'Test', usuario: 'test' }
    localStorage.setItem('user', JSON.stringify(mockUser))
    expect(getUser()).toEqual(mockUser)
  })

  it('should return null if no user stored', () => {
    localStorage.removeItem('user')
    expect(getUser()).toBeNull()
  })

  it('should return true if access_token exists', () => {
    localStorage.setItem('access_token', 'some_token')
    expect(isAuthenticated()).toBe(true)
  })

  it('should return false if no access_token', () => {
    localStorage.removeItem('access_token')
    expect(isAuthenticated()).toBe(false)
  })

  it('should return true if user nivel is admin', () => {
    const mockUser = { id: 1, nome: 'Admin', usuario: 'admin', nivel: 'admin' }
    localStorage.setItem('user', JSON.stringify(mockUser))
    expect(isAdmin()).toBe(true)
  })

  it('should return false if user nivel is not admin', () => {
    const mockUser = { id: 1, nome: 'User', usuario: 'user', nivel: 'vendedor' }
    localStorage.setItem('user', JSON.stringify(mockUser))
    expect(isAdmin()).toBe(false)
  })

  it('should return true if user nivel is vendedor', () => {
    const mockUser = { id: 1, nome: 'Vendedor', usuario: 'vendedor', nivel: 'vendedor' }
    localStorage.setItem('user', JSON.stringify(mockUser))
    expect(isVendedor()).toBe(true)
  })

  it('should return false if user nivel is not vendedor', () => {
    const mockUser = { id: 1, nome: 'User', usuario: 'user', nivel: 'admin' }
    localStorage.setItem('user', JSON.stringify(mockUser))
    expect(isVendedor()).toBe(false)
  })
})