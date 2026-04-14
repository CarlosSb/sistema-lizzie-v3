import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
})

let isRefreshing = false
let refreshSubscribers: ((token: string) => void)[] = []

function subscribeTokenRefresh(cb: (token: string) => void) {
  refreshSubscribers.push(cb)
}

function onTokenRefreshed(accessToken: string) {
  refreshSubscribers.forEach(cb => cb(accessToken))
  refreshSubscribers = []
}

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('access_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        return new Promise((resolve) => {
          subscribeTokenRefresh((accessToken) => {
            originalRequest.headers.Authorization = `Bearer ${accessToken}`
            resolve(api(originalRequest))
          })
        })
      }

      originalRequest._retry = true
      isRefreshing = true

      const refreshToken = localStorage.getItem('refresh_token')

      if (!refreshToken) {
        localStorage.removeItem('access_token')
        localStorage.removeItem('refresh_token')
        localStorage.removeItem('user')
        window.location.href = '/login'
        return Promise.reject(error)
      }

      try {
        const response = await axios.post(
          `${import.meta.env.VITE_API_URL || '/api'}/api/vendedores/refresh`,
          { refresh_token: refreshToken }
        )

        const { access_token, refresh_token } = response.data.data

        localStorage.setItem('access_token', access_token)
        localStorage.setItem('refresh_token', refresh_token)

        onTokenRefreshed(access_token)

        originalRequest.headers.Authorization = `Bearer ${access_token}`
        return api(originalRequest)

      } catch (refreshError) {
        localStorage.removeItem('access_token')
        localStorage.removeItem('refresh_token')
        localStorage.removeItem('user')
        window.location.href = '/login'
        return Promise.reject(refreshError)
      } finally {
        isRefreshing = false
      }
    }

    return Promise.reject(error)
  }
)

// Tipos para a API
export interface User {
  id: number
  nome: string
  usuario: string
  nivel: string
}

export interface AuthResponse {
  success: boolean
  data: {
    user: User
    access_token: string
    refresh_token: string
    token_type: string
    expires_in: number
  }
}

export const login = async (usuario: string, senha: string): Promise<User> => {
  const response = await api.post<AuthResponse>('/api/vendedores/login', { usuario, senha })
  if (response.data.success) {
    const { user, access_token, refresh_token } = response.data.data
    localStorage.setItem('access_token', access_token)
    localStorage.setItem('refresh_token', refresh_token)
    localStorage.setItem('user', JSON.stringify(user))
    return user
  }
  throw new Error(response.data.message)
}

export const logout = async (): Promise<void> => {
  try {
    await api.post('/api/vendedores/logout')
  } catch (e) {
    console.error('Logout API error:', e)
  }
  localStorage.removeItem('access_token')
  localStorage.removeItem('refresh_token')
  localStorage.removeItem('user')
}

export const getUser = (): User | null => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
}

export const isAuthenticated = (): boolean => {
  return !!localStorage.getItem('access_token')
}

export const isAdmin = (): boolean => {
  const user = getUser()
  return user?.nivel === 'admin'
}

export const isVendedor = (): boolean => {
  const user = getUser()
  return user?.nivel === 'vendedor'
}

export default {
  login,
  logout,
  getUser,
  isAuthenticated,
  isAdmin,
  isVendedor,

  getClientes: (params?: Record<string, any>) => api.get('/clientes', { params }),
  getCliente: (id: number) => api.get(`/clientes/${id}`),
  createCliente: (data: any) => api.post('/clientes', data),
  updateCliente: (id: number, data: any) => api.put(`/clientes/${id}`, data),
  deleteCliente: (id: number) => api.delete(`/clientes/${id}`),

  getProdutos: (params?: Record<string, any>) => api.get('/produtos', { params }),
  getProduto: (id: number) => api.get(`/produtos/${id}`),
  createProduto: (data: any) => api.post('/produtos', data),
  updateProduto: (id: number, data: any) => api.put(`/produtos/${id}`, data),
  deleteProduto: (id: number) => api.delete(`/produtos/${id}`),

  getPedidos: (params?: Record<string, any>) => api.get('/pedidos', { params }),
  getPedido: (id: number) => api.get(`/pedidos/${id}`),
  getPedidoEtiqueta: (id: number) => api.get(`/pedidos/${id}/etiqueta`),
  createPedido: (data: any) => api.post('/pedidos', data),
  updatePedido: (id: number, data: any) => api.put(`/pedidos/${id}`, data),
  deletePedido: (id: number) => api.delete(`/pedidos/${id}`),
  updateStatusPedido: (id: number, data: any) => api.put(`/pedidos/${id}/status`, data),

  getItensPedido: (pedidoId: number) => api.get(`/pedidos/${pedidoId}/itens`),
  getCalculoPedido: (pedidoId: number) => api.get(`/pedidos/${pedidoId}/calculo`),
  addItemPedido: (pedidoId: number, data: any) => api.post(`/pedidos/${pedidoId}/itens`, data),
  removeItemPedido: (id: number) => api.delete(`/itens/${id}`),

  getVendedores: (params?: Record<string, any>) => api.get('/vendedores', { params }),
  getVendedor: (id: number) => api.get(`/vendedores/${id}`),
  createVendedor: (data: any) => api.post('/vendedores', data),
  updateVendedor: (id: number, data: any) => api.put(`/vendedores/${id}`, data),
  deleteVendedor: (id: number) => api.delete(`/vendedores/${id}`),

  getDashboard: () => api.get('/dashboard'),
  getEstatisticas: () => api.get('/dashboard/estatisticas'),

  getAlertas: (params?: Record<string, any>) => api.get('/alertas', { params }),
  getAlertasNaoLidos: () => api.get('/alertas/nao-lidos'),
  marcarAlertaLido: (id: number) => api.put(`/alertas/${id}/ler`),

  getEstoques: (params?: Record<string, any>) => api.get('/estoque', { params }),
  getEstoque: (id: number) => api.get(`/estoque/${id}`),
  getMovimentacao: () => api.get('/estoque/movimentacao'),
  getEstoqueBaixo: (limite = 10) => api.get('/estoque/baixo', { params: { limite } }),
  getResumoEstoque: () => api.get('/estoque/resumo'),
  registrarEntrada: (data: any) => api.post('/estoque/entrada', data),
  registrarSaida: (data: any) => api.post('/estoque/saida', data),
  reservarEstoque: (data: any) => api.post('/estoque/reservar', data),
  liberarEstoque: (data: any) => api.post('/estoque/liberar', data),

  getRelatorioVendas: (params?: Record<string, any>) => api.get('/relatorios/vendas', { params }),
  getRelatorioVendedores: (params?: Record<string, any>) => api.get('/relatorios/vendedores', { params }),
  getRelatorioProdutos: (params?: Record<string, any>) => api.get('/relatorios/produtos', { params }),
  getRelatorioEstatisticas: (params?: Record<string, any>) => api.get('/relatorios/estatisticas', { params }),
  getRelatorioInsights: (params?: Record<string, any>) => api.get('/relatorios/insights', { params }),
  getRelatorioClientes: (params?: Record<string, any>) => api.get('/relatorios/clientes', { params }),
  getClienteDetalhes: (id: number, params?: Record<string, any>) => api.get(`/relatorios/clientes/${id}`, { params }),

  getUsuarios: () => api.get('/usuarios'),
  getUsuario: (id: number) => api.get(`/usuarios/${id}`),
  criarUsuario: (data: any) => api.post('/usuarios', data),
  atualizarUsuario: (id: number, data: any) => api.put(`/usuarios/${id}`, data),
  excluirUsuario: (id: number) => api.delete(`/usuarios/${id}`),
  atualizarPerfil: (data: any) => api.put('/api/vendedores/profile', data)
}