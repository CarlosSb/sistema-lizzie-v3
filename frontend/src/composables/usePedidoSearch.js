import { ref, computed, watch } from 'vue'
import Fuse from 'fuse.js'

export function usePedidoSearch() {
  const searchQuery = ref('')
  const searchResults = ref([])
  const searchFields = ref(['cliente.razao_social', 'cliente.nome_fantasia', 'id_pedido', 'status', 'obs_pedido'])

  const createFuse = (data, options = {}) => {
    const defaultOptions = {
      keys: searchFields.value,
      threshold: 0.3,
      distance: 100,
      minMatchCharLength: 2,
      includeScore: true,
      ignoreLocation: true
    }
    return new Fuse(data, { ...defaultOptions, ...options })
  }

  const search = (data, query) => {
    if (!query || query.length < 2) {
      searchResults.value = data
      return data
    }

    const fuse = createFuse(data)
    const results = fuse.search(query)
    searchResults.value = results.map(r => r.item)
    return results.map(r => r.item)
  }

  const filterByStatus = (data, status) => {
    if (!status) return data
    return data.filter(p => p.status === status)
  }

  const filterByDateRange = (data, startDate, endDate) => {
    return data.filter(p => {
      const date = new Date(p.created_at)
      const start = startDate ? new Date(startDate) : null
      const end = endDate ? new Date(endDate) : null
      
      if (start && date < start) return false
      if (end && date > end) return false
      return true
    })
  }

  const filterByClient = (data, clientId) => {
    if (!clientId) return data
    return data.filter(p => p.id_cliente === parseInt(clientId))
  }

  const filterByRegion = (data, regiao) => {
    if (!regiao) return data
    return data.filter(p => p.regiao === regiao)
  }

  const filterByValue = (data, minValue, maxValue) => {
    return data.filter(p => {
      const total = parseFloat(p.total_pedido || 0)
      if (minValue && total < minValue) return false
      if (maxValue && total > maxValue) return false
      return true
    })
  }

  const sortData = (data, sortBy = 'created_at', order = 'desc') => {
    return [...data].sort((a, b) => {
      let valA = a[sortBy]
      let valB = b[sortBy]

      if (sortBy === 'created_at') {
        valA = new Date(valA)
        valB = new Date(valB)
      }

      if (typeof valA === 'string') {
        valA = valA.toLowerCase()
        valB = valB.toLowerCase()
      }

      if (valA < valB) return order === 'asc' ? -1 : 1
      if (valA > valB) return order === 'asc' ? 1 : -1
      return 0
    })
  }

  const clearSearch = () => {
    searchQuery.value = ''
    searchResults.value = []
  }

  return {
    searchQuery,
    searchResults,
    searchFields,
    search,
    filterByStatus,
    filterByDateRange,
    filterByClient,
    filterByRegion,
    filterByValue,
    sortData,
    clearSearch
  }
}

export function useClienteSearch() {
  const searchQuery = ref('')
  const results = ref([])
  
  const createFuse = (data) => {
    return new Fuse(data, {
      keys: ['razao_social', 'nome_fantasia', 'cpf_cnpj', 'email', 'telefone'],
      threshold: 0.3,
      distance: 100,
      minMatchCharLength: 2,
      includeScore: true,
      ignoreLocation: true
    })
  }

  const search = (clientes, query) => {
    if (!query || query.length < 2) {
      results.value = clientes
      return clientes
    }

    const fuse = createFuse(clientes)
    const searchResults = fuse.search(query)
    results.value = searchResults.map(r => r.item)
    return results.value
  }

  return {
    searchQuery,
    results,
    search
  }
}

export function useProdutoSearch() {
  const searchQuery = ref('')
  const results = ref([])
  const loading = ref(false)

  const createFuse = (data) => {
    return new Fuse(data, {
      keys: ['produto', 'referencia', 'categoria', 'descricao'],
      threshold: 0.3,
      distance: 100,
      minMatchCharLength: 2,
      includeScore: true,
      ignoreLocation: true
    })
  }

  const search = (produtos, query) => {
    if (!query || query.length < 2) {
      results.value = produtos
      return produtos
    }

    loading.value = true
    const fuse = createFuse(produtos)
    const searchResults = fuse.search(query)
    results.value = searchResults.map(r => r.item)
    loading.value = false
    return results.value
  }

  const filterByCategory = (produtos, category) => {
    if (!category) return produtos
    return produtos.filter(p => p.categoria === category)
  }

  const filterByRegion = (produtos, regiao) => {
    return produtos.map(p => ({
      ...p,
      preco_atual: regiao === 'norte' ? p.valor_unt_norte : p.valor_unt_norde
    }))
  }

  return {
    searchQuery,
    results,
    loading,
    search,
    filterByCategory,
    filterByRegion
  }
}