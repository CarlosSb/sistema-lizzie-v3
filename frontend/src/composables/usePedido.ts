import { ref, shallowRef, computed } from 'vue'
import apiClient from '@/lib/axios'
import { getPedidoStatusLabel, getPedidoStatusTransitions } from '@/lib/pedidoStatus'

interface PedidoDetalhes {
  id_pedido: number
  id_cliente: number
  id_vendedor: number
  razao_social: string | null
  nome_vendedor: string | null
  total_pedido: number
  status: number
  data_pedido: string
  forma_pag: string | null
  ped_desconto: number
  obs_pedido: string | null
  obs_entrega: string | null
  obs_cancelamento: string | null
  itens: PedidoItem[]
}

interface PedidoItem {
  id_item_pedido: number
  id_produto: number
  produto: string
  referencia: string
  tam_pp: number
  tam_p: number
  tam_m: number
  tam_g: number
  tam_u: number
  tam_rn: number
  ida_1: number
  ida_2: number
  ida_3: number
  ida_4: number
  ida_6: number
  ida_8: number
  ida_10: number
  ida_12: number
  lisa: number
  total_item: number
  val_desconto: number
}

export function usePedido() {
  const pedido = shallowRef<PedidoDetalhes | null>(null)
  const isLoading = ref(true)
  const errorMessage = ref<string | null>(null)
  const isUpdatingStatus = ref(false)
  const newStatus = ref<number | null>(null)

  const fetchPedidoDetalhes = async (pedidoId: string) => {
    isLoading.value = true
    errorMessage.value = null
    try {
      const response = await apiClient.get(`/api/pedidos/${pedidoId}`)
      pedido.value = response.data?.data || null

      // Initialize newStatus with current status for editing
      if (pedido.value) {
        newStatus.value = pedido.value.status
      }
    } catch (error: any) {
      errorMessage.value = 'Erro ao carregar detalhes do pedido.'
      console.error('Failed to fetch pedido details:', error)
    } finally {
      isLoading.value = false
    }
  }

  const updateOrderStatus = async () => {
    if (!pedido.value || newStatus.value === null || isUpdatingStatus.value) return

    if (pedido.value.status === newStatus.value) {
      return
    }

    isUpdatingStatus.value = true
    errorMessage.value = null

    try {
      const response = await apiClient.put(`/api/pedidos/${pedido.value.id_pedido}/status`, {
        status: newStatus.value
      })

      const updatedStatus = response.data?.data?.status !== undefined ? Number(response.data.data.status) : newStatus.value
      pedido.value.status = updatedStatus
      newStatus.value = updatedStatus
    } catch (error: any) {
      errorMessage.value = 'Erro ao atualizar status do pedido.'
      console.error('Failed to update order status:', error)
    } finally {
      isUpdatingStatus.value = false
    }
  }

  const getStatusLabel = (status: number) => {
    return getPedidoStatusLabel(status)
  }

  const availableStatuses = computed(() => {
    if (!pedido.value) return []
    return getPedidoStatusTransitions(pedido.value.status)
  })

  return {
    pedido,
    isLoading,
    errorMessage,
    isUpdatingStatus,
    newStatus,
    fetchPedidoDetalhes,
    updateOrderStatus,
    getStatusLabel,
    availableStatuses,
  }
}
