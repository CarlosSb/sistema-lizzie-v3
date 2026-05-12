export type PedidoStatus = 1 | 2 | 3 | 4

export const PEDIDO_STATUS = {
  ABERTO: 1,
  PENDENTE: 2,
  CANCELADO: 3,
  CONCLUIDO: 4,
} as const

export const getPedidoStatusLabel = (status: number) => {
  switch (status) {
    case PEDIDO_STATUS.ABERTO: return 'ABERTO'
    case PEDIDO_STATUS.PENDENTE: return 'PENDENTE'
    case PEDIDO_STATUS.CANCELADO: return 'CANCELADO'
    case PEDIDO_STATUS.CONCLUIDO: return 'CONCLUÍDO'
    default: return 'DESCONHECIDO'
  }
}

export const getPedidoStatusClass = (status: number) => {
  switch (status) {
    case PEDIDO_STATUS.ABERTO: return 'bg-blue-500/10 text-blue-600 border-blue-200 dark:border-blue-500/30'
    case PEDIDO_STATUS.PENDENTE: return 'bg-amber-500/10 text-amber-600 border-amber-200 dark:border-amber-500/30'
    case PEDIDO_STATUS.CANCELADO: return 'bg-rose-500/10 text-rose-600 border-rose-200 dark:border-rose-500/30'
    case PEDIDO_STATUS.CONCLUIDO: return 'bg-emerald-500/10 text-emerald-600 border-emerald-200 dark:border-emerald-500/30'
    default: return ''
  }
}

export const getPedidoStatusTransitions = (currentStatus: number) => {
  switch (currentStatus) {
    case PEDIDO_STATUS.ABERTO:
      return [
        { value: PEDIDO_STATUS.PENDENTE, label: 'Enviar para pendente' },
        { value: PEDIDO_STATUS.CANCELADO, label: 'Cancelar pedido' },
      ]
    case PEDIDO_STATUS.PENDENTE:
      return [
        { value: PEDIDO_STATUS.ABERTO, label: 'Reabrir pedido' },
        { value: PEDIDO_STATUS.CONCLUIDO, label: 'Concluir pedido' },
        { value: PEDIDO_STATUS.CANCELADO, label: 'Cancelar pedido' },
      ]
    case PEDIDO_STATUS.CONCLUIDO:
      return [
        { value: PEDIDO_STATUS.CANCELADO, label: 'Cancelar e devolver estoque' },
      ]
    default:
      return []
  }
}

export const isPedidoStatusFinal = (status: number) => {
  return status === PEDIDO_STATUS.CANCELADO || status === PEDIDO_STATUS.CONCLUIDO
}

export const isPedidoStatusPrintable = (status: number) => {
  return status === PEDIDO_STATUS.PENDENTE || isPedidoStatusFinal(status)
}
