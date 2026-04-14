import { ref, onMounted, onUnmounted } from 'vue'
import { useMessage } from 'naive-ui'
import api from './useApi'

const alertas = ref([])
const naoLidosCount = ref(0)
const eventSource = ref(null)
const isConnected = ref(false)
const usePolling = ref(false)

let pollingInterval = null

export function useAlertas() {
  const message = useMessage()
  
  function connectSSE() {
    if (eventSource.value) return
    
    const token = localStorage.getItem('access_token')
    if (!token) return
    
    try {
      eventSource.value = new EventSource(`${import.meta.env.VITE_API_URL || '/api'}/alertas/stream`, {
        withCredentials: true
      })
      
      eventSource.value.onopen = () => {
        isConnected.value = true
        console.log('SSE conectado')
        loadNaoLidos()
      }
      
      eventSource.value.onerror = (error) => {
        console.log('SSE erro, usando polling:', error)
        isConnected.value = false
        eventSource.value?.close()
        eventSource.value = null
        startPolling()
      }
      
      eventSource.value.addEventListener('novo_pedido', (event) => {
        const data = JSON.parse(event.data)
        alertas.value.unshift(data)
        naoLidosCount.value++
        message.warning(data.titulo, {
          duration: 5000
        })
        loadNaoLidos()
      })
    } catch (error) {
      console.log('SSE não suportado, usando polling')
      startPolling()
    }
  }
  
  function startPolling() {
    usePolling.value = true
    pollingInterval = setInterval(async () => {
      try {
        const response = await api.getAlertas({ nao_lidos: 1 })
        const novos = response.data.data
        
        if (novos.length > naoLidosCount.value && naoLidosCount.value > 0) {
          message.warning('Novo(s) alerta(s)!', { duration: 3000 })
        }
        
        naoLidosCount.value = novos.length
        alertas.value = novos
      } catch (error) {
        console.error('Polling erro:', error)
      }
    }, 30000)
  }
  
  function stopConnection() {
    eventSource.value?.close()
    eventSource.value = null
    
    if (pollingInterval) {
      clearInterval(pollingInterval)
      pollingInterval = null
    }
    
    isConnected.value = false
    usePolling.value = false
  }
  
  async function loadAlertas() {
    try {
      const response = await api.getAlertas()
      alertas.value = response.data.data
    } catch (error) {
      console.error('Erro carregar alertas:', error)
    }
  }
  
  async function loadNaoLidos() {
    try {
      const response = await api.getAlertasNaoLidos()
      naoLidosCount.value = response.data.data.count
    } catch (error) {
      console.error('Erro carregar não lidos:', error)
    }
  }
  
  async function marcarLido(id) {
    try {
      await api.marcarAlertaLido(id)
      await loadNaoLidos()
      await loadAlertas()
    } catch (error) {
      console.error('Erro marcar lido:', error)
    }
  }
  
  function init() {
    connectSSE()
    loadAlertas()
    loadNaoLidos()
  }
  
  return {
    alertas,
    naoLidosCount,
    isConnected,
    usePolling,
    init,
    loadAlertas,
    loadNaoLidos,
    marcarLido,
    stopConnection
  }
}