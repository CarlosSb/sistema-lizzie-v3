import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
})

let isRefreshing = false
let refreshSubscribers = []

function subscribeTokenRefresh(cb) {
  refreshSubscribers.push(cb)
}

function onTokenRefreshed(accessToken) {
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
          `${import.meta.env.VITE_API_URL || '/api'}/auth/refresh`,
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

export const login = async (usuario, senha) => {
  const response = await api.post('/auth/login', { usuario, senha })
  if (response.data.success) {
    const { user, access_token, refresh_token, expires_in } = response.data.data
    localStorage.setItem('access_token', access_token)
    localStorage.setItem('refresh_token', refresh_token)
    localStorage.setItem('user', JSON.stringify(user))
    return user
  }
  throw new Error(response.data.message)
}

export const logout = async () => {
  try {
    await api.post('/auth/logout')
  } catch (e) {
    console.error('Logout API error:', e)
  }
  localStorage.removeItem('access_token')
  localStorage.removeItem('refresh_token')
  localStorage.removeItem('user')
}

export const getUser = () => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
}

export const isAuthenticated = () => {
  return !!localStorage.getItem('access_token')
}

export const isAdmin = () => {
  const user = getUser()
  return user?.nivel === 'admin'
}

export const isVendedor = () => {
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
  
  getClientes: (params) => api.get('/clientes', { params }),
  getCliente: (id) => api.get(`/clientes/${id}`),
  createCliente: (data) => api.post('/clientes', data),
  updateCliente: (id, data) => api.put(`/clientes/${id}`, data),
  deleteCliente: (id) => api.delete(`/clientes/${id}`),

  getProdutos: (params) => api.get('/produtos', { params }),
  getProduto: (id) => api.get(`/produtos/${id}`),
  createProduto: (data) => api.post('/produtos', data),
  updateProduto: (id, data) => api.put(`/produtos/${id}`, data),
  deleteProduto: (id) => api.delete(`/produtos/${id}`),

  getPedidos: (params) => api.get('/pedidos', { params }),
  getPedido: (id) => api.get(`/pedidos/${id}`),
  getPedidoEtiqueta: (id) => api.get(`/pedidos/${id}/etiqueta`),
  createPedido: (data) => api.post('/pedidos', data),
  updatePedido: (id, data) => api.put(`/pedidos/${id}`, data),
  deletePedido: (id) => api.delete(`/pedidos/${id}`),
  updateStatusPedido: (id, data) => api.put(`/pedidos/${id}/status`, data),

  getItensPedido: (pedidoId) => api.get(`/pedidos/${pedidoId}/itens`),
  getCalculoPedido: (pedidoId) => api.get(`/pedidos/${pedidoId}/calculo`),
  addItemPedido: (pedidoId, data) => api.post(`/pedidos/${pedidoId}/itens`, data),
  removeItemPedido: (id) => api.delete(`/itens/${id}`),

  getVendedores: (params) => api.get('/vendedores', { params }),
  getVendedor: (id) => api.get(`/vendedores/${id}`),
  createVendedor: (data) => api.post('/vendedores', data),
  updateVendedor: (id, data) => api.put(`/vendedores/${id}`, data),
  deleteVendedor: (id) => api.delete(`/vendedores/${id}`),

  getDashboard: () => api.get('/dashboard'),
  getEstatisticas: () => api.get('/dashboard/estatisticas'),
  
  getAlertas: (params) => api.get('/alertas', { params }),
  getAlertasNaoLidos: () => api.get('/alertas/nao-lidos'),
  marcarAlertaLido: (id) => api.put(`/alertas/${id}/ler`),

  getEstoques: (params) => api.get('/estoque', { params }),
  getEstoque: (id) => api.get(`/estoque/${id}`),
  getMovimentacao: () => api.get('/estoque/movimentacao'),
  getEstoqueBaixo: (limite = 10) => api.get('/estoque/baixo', { params: { limite } }),
  getResumoEstoque: () => api.get('/estoque/resumo'),
  registrarEntrada: (data) => api.post('/estoque/entrada', data),
  registrarSaida: (data) => api.post('/estoque/saida', data),
  reservarEstoque: (data) => api.post('/estoque/reservar', data),
  liberarEstoque: (data) => api.post('/estoque/liberar', data),

  getRelatorioVendas: (params) => api.get('/relatorios/vendas', { params }),
  getRelatorioVendedores: (params) => api.get('/relatorios/vendedores', { params }),
  getRelatorioProdutos: (params) => api.get('/relatorios/produtos', { params }),
  getRelatorioEstatisticas: (params) => api.get('/relatorios/estatisticas', { params }),
  getRelatorioInsights: (params) => api.get('/relatorios/insights', { params }),
  getRelatorioClientes: (params) => api.get('/relatorios/clientes', { params }),
  getClienteDetalhes: (id, params) => api.get(`/relatorios/clientes/${id}`, { params }),

  getUsuarios: () => api.get('/usuarios'),
  getUsuario: (id) => api.get(`/usuarios/${id}`),
  criarUsuario: (data) => api.post('/usuarios', data),
  atualizarUsuario: (id, data) => api.put(`/usuarios/${id}`, data),
  excluirUsuario: (id) => api.delete(`/usuarios/${id}`),
  atualizarPerfil: (data) => api.put('/auth/profile', data)
}