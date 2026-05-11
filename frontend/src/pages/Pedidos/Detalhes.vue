<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, User, Package, ShoppingCart, Loader2, AlertCircle, Save, Plus, Pencil, Trash2, X, Printer, FileText } from 'lucide-vue-next'
import PedidoPrint from '@/components/PedidoPrint.vue'
import PdfViewer from '@/components/PdfViewer.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import apiClient from '@/lib/axios'

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

interface Produto {
  id_produto: number
  referencia: string
  produto: string
  valor_unt_norte: number
  valor_unt_norde: number
  status: number
}

interface ClienteDetalhes {
  id_cliente: number
  razao_social: string | null
  nome_fantasia: string | null
  responsavel: string | null
  cpf_cnpj: string | null
  email: string | null
  endereco: string | null
  bairro: string | null
  cidade: string | null
  estado: string | null
  cep: string | null
  contato_1: string | null
  contato_2: string | null
  contato_3: string | null
}

const route = useRoute()
const pedido = ref<PedidoDetalhes | null>(null)
const cliente = ref<ClienteDetalhes | null>(null)
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)
const isUpdatingStatus = ref(false) // For loading state on status update
const newStatus = ref<number | null>(null) // Ref for the new status value
const showPreviewModal = ref(false)
const printMode = ref<'complete' | 'summary' | null>(null)
const printOption = ref<string>('')

// PDF Viewer Modal
const showPdfModal = ref(false)
const pdfUrl = ref<string>('')
const isGeneratingPdf = ref(false)
const pdfError = ref<string>('')

const produtos = ref<Produto[]>([])
const isLoadingProdutos = ref(false)

const itemFormProductId = ref<string>('')
const itemFormRegiao = ref<'nordeste' | 'norte'>('nordeste')
const itemFormQuantidades = ref<Record<string, number>>({})
const editingItemId = ref<number | null>(null)
const isSavingItem = ref(false)
const isItemEditorOpen = ref(false)

const SIZE_KEYS = [
  'pp',
  'p',
  'm',
  'g',
  'u',
  'rn',
  'ida_1',
  'ida_2',
  'ida_3',
  'ida_4',
  'ida_6',
  'ida_8',
  'ida_10',
  'ida_12',
  'lisa',
] as const

const SIZE_GROUPS: Array<{ title: string; keys: Array<(typeof SIZE_KEYS)[number]> }> = [
  { title: 'Infantil', keys: ['pp', 'p', 'm', 'g', 'u', 'rn', 'lisa'] },
  { title: 'Idades', keys: ['ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12'] },
]

const sizeLabel = (key: (typeof SIZE_KEYS)[number]) => {
  if (key.startsWith('ida_')) return key.replace('ida_', '')
  return key.toUpperCase()
}

const resetItemForm = () => {
  itemFormProductId.value = ''
  itemFormRegiao.value = 'nordeste'
  itemFormQuantidades.value = Object.fromEntries(SIZE_KEYS.map(k => [k, 0]))
  editingItemId.value = null
  isItemEditorOpen.value = false
}

const mapItemToQuantidades = (item: PedidoItem) => {
  return {
    pp: item.tam_pp || 0,
    p: item.tam_p || 0,
    m: item.tam_m || 0,
    g: item.tam_g || 0,
    u: item.tam_u || 0,
    rn: item.tam_rn || 0,
    ida_1: item.ida_1 || 0,
    ida_2: item.ida_2 || 0,
    ida_3: item.ida_3 || 0,
    ida_4: item.ida_4 || 0,
    ida_6: item.ida_6 || 0,
    ida_8: item.ida_8 || 0,
    ida_10: item.ida_10 || 0,
    ida_12: item.ida_12 || 0,
    lisa: item.lisa || 0,
  }
}

const sumQuantidades = (q: Record<string, number>) => {
  return SIZE_KEYS.reduce((acc, key) => acc + (Number(q[key]) || 0), 0)
}

const getItemQtdTotal = (item: PedidoItem) => sumQuantidades(mapItemToQuantidades(item))

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0)
}

const getSizeBadges = (q: Record<string, number>) => {
  const list: Array<{ key: string; label: string; value: number }> = []
  for (const key of SIZE_KEYS) {
    const value = Number(q[key]) || 0
    if (value > 0) list.push({ key, label: sizeLabel(key), value })
  }
  return list
}

const openAddItem = () => {
  resetItemForm()
  isItemEditorOpen.value = true
}

const openEditItem = (item: PedidoItem) => {
  editingItemId.value = item.id_item_pedido
  itemFormProductId.value = String(item.id_produto)
  itemFormQuantidades.value = mapItemToQuantidades(item)
  isItemEditorOpen.value = true
}

const fetchProdutos = async () => {
  isLoadingProdutos.value = true
  try {
    const response = await apiClient.get('/api/produtos', { params: { status: 1, page: 1, per_page: 100 } })
    produtos.value = response.data?.data || []
  } finally {
    isLoadingProdutos.value = false
  }
}

const saveItem = async () => {
  if (!pedido.value) return

  const total = sumQuantidades(itemFormQuantidades.value)
  if (!itemFormProductId.value || total <= 0) {
    errorMessage.value = 'Selecione um produto e informe ao menos 1 unidade.'
    return
  }

  isSavingItem.value = true
  errorMessage.value = null
  try {
    const payload = {
      id_produto: Number(itemFormProductId.value),
      regiao: itemFormRegiao.value,
      quantidades: itemFormQuantidades.value,
    }

    if (editingItemId.value) {
      await apiClient.put(`/api/itens/${editingItemId.value}`, payload)
    } else {
      await apiClient.post(`/api/pedidos/${pedido.value.id_pedido}/itens`, payload)
    }

    await fetchPedidoDetalhes()
    resetItemForm()
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Erro ao salvar item.'
  } finally {
    isSavingItem.value = false
  }
}

const removeItem = async (itemId: number) => {
  if (!window.confirm('Remover este item do pedido?')) return
  isSavingItem.value = true
  errorMessage.value = null
  try {
    await apiClient.delete(`/api/itens/${itemId}`)
    await fetchPedidoDetalhes()
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Erro ao remover item.'
  } finally {
    isSavingItem.value = false
  }
}

const selectedProduct = computed(() => produtos.value.find(p => String(p.id_produto) === itemFormProductId.value) || null)
const itemPieces = computed(() => sumQuantidades(itemFormQuantidades.value))
const itemUnitPrice = computed(() => {
  if (!selectedProduct.value) return 0
  return itemFormRegiao.value === 'norte' ? Number(selectedProduct.value.valor_unt_norte) || 0 : Number(selectedProduct.value.valor_unt_norde) || 0
})
const itemEstimate = computed(() => itemPieces.value * itemUnitPrice.value)

const fetchPedidoDetalhes = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const pedidoId = route.params.id
    if (!pedidoId) {
      throw new Error('ID do pedido não fornecido.')
    }
    const response = await apiClient.get(`/api/pedidos/${pedidoId}`) 
    pedido.value = response.data?.data || null
    cliente.value = null

    if (pedido.value?.id_cliente) {
      const clienteResponse = await apiClient.get(`/api/clientes/${pedido.value.id_cliente}`)
      cliente.value = clienteResponse.data?.data || null
    }

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

const getStatusClass = (status: number) => {
  switch (status) {
    case 1: return 'bg-blue-500/10 text-blue-600 border-blue-200 dark:border-blue-500/30' // ABERTO
    case 2: return 'bg-amber-500/10 text-amber-600 border-amber-200 dark:border-amber-500/30' // PENDENTE
    case 3: return 'bg-rose-500/10 text-rose-600 border-rose-200 dark:border-rose-500/30' // CANCELADO
    case 4: return 'bg-emerald-500/10 text-emerald-600 border-emerald-200 dark:border-emerald-500/30' // CONCLUÍDO
    default: return ''
  }
}

const getStatusLabel = (status: number) => {
  switch (status) {
    case 1: return 'ABERTO'
    case 2: return 'PENDENTE'
    case 3: return 'CANCELADO'
    case 4: return 'CONCLUÍDO'
    default: return 'DESCONHECIDO'
  }
}

const availableStatuses = computed(() => {
  if (!pedido.value) return []
  const current = pedido.value.status
  if (current === 1) { // ABERTO
    return [{ value: 3, label: 'CANCELADO' }]
  } else if (current === 2) { // PENDENTE
    return [
      { value: 4, label: 'CONCLUÍDO' },
      { value: 3, label: 'CANCELADO' }
    ]
  } else {
    return []
  }
})

onMounted(async () => {
  resetItemForm()
  await Promise.all([fetchPedidoDetalhes(), fetchProdutos()])
})

const goBack = () => {
  window.history.back()
}

const updateOrderStatus = async () => {
  if (!pedido.value || newStatus.value === null) return // Ensure we have a pedido and a new status selected

  if (pedido.value.status === newStatus.value) { // No change needed
    return
  }

  isUpdatingStatus.value = true
  errorMessage.value = null // Clear previous error

  try {
    // Assume API endpoint for status update is PUT /api/pedidos/:id/status
    const response = await apiClient.put(`/api/pedidos/${pedido.value.id_pedido}/status`, {
      status: newStatus.value
    })
    // Update local state to reflect the change immediately
    if (pedido.value) {
      pedido.value.status = response.data?.data?.status !== undefined ? response.data.data.status : newStatus.value
    }

    // Auto-print PDF when status changes to Concluído (4)
    if (newStatus.value === 4) {
      await autoPrintCompletedOrder()
    }

    // Optionally re-fetch all details, or just update the status badge
    // fetchPedidoDetalhes()
  } catch (error: any) {
    errorMessage.value = 'Erro ao atualizar status do pedido.'
    console.error('Failed to update order status:', error)
  } finally {
    isUpdatingStatus.value = false
  }
}

const autoPrintCompletedOrder = async () => {
  try {
    // Generate and print PDF automatically for completed orders
    const response = await apiClient.post(`/api/pdf/pedido/${pedido.value?.id_pedido}`, {
      format: 'pdf',
      paper_size: 'a4',
      orientation: 'portrait',
      include_qr: true // Include QR code for completed orders
    }, {
      responseType: 'blob'
    })

    const pdfBlob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(pdfBlob)

    // Auto-print the PDF
    const iframe = document.createElement('iframe')
    iframe.style.display = 'none'
    iframe.src = url
    document.body.appendChild(iframe)

    setTimeout(() => {
      try {
        iframe.contentWindow?.print()
      } catch (error) {
        console.error('Erro ao imprimir PDF automaticamente:', error)
        // Fallback: open in new tab
        const newWindow = window.open(url, '_blank')
        newWindow?.focus()
        setTimeout(() => {
          newWindow?.print()
        }, 500)
      }
      document.body.removeChild(iframe)
      URL.revokeObjectURL(url)
    }, 500)

  } catch (error) {
    console.error('Erro ao gerar PDF automaticamente para pedido concluído:', error)
    // Don't show error to user for auto-print failures
  }
}

const printPedido = (mode: 'complete' | 'summary') => {
  printMode.value = mode
  showPreviewModal.value = true
  console.log(`Opening preview for pedido in ${mode} mode...`) // Debug log
}

const onPdfGenerated = (mode: 'complete' | 'summary') => {
  showPreviewModal.value = false
  printMode.value = null
  // Auto-update status to PENDENTE for complete mode
  if (mode === 'complete') {
    newStatus.value = 2
    updateOrderStatus()
  }
}

const showPdfPreview = async () => {
  if (!pedido.value) return

  isGeneratingPdf.value = true
  pdfError.value = ''

  try {
    const response = await apiClient.post(`/api/pdf/pedido/${pedido.value.id_pedido}`, {
      format: 'pdf',
      paper_size: 'a4',
      orientation: 'portrait',
      include_qr: true
    }, {
      responseType: 'blob'
    })

    const pdfBlob = new Blob([response.data], { type: 'application/pdf' })
    pdfUrl.value = URL.createObjectURL(pdfBlob)
    showPdfModal.value = true
  } catch (error: any) {
    pdfError.value = 'Erro ao gerar PDF: ' + (error.response?.data?.message || error.message)
    console.error('Erro ao gerar PDF:', error)
  } finally {
    isGeneratingPdf.value = false
  }
}

const closePdfModal = () => {
  showPdfModal.value = false
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = ''
  }
}

const printPdf = () => {
  if (pdfUrl.value) {
    const printWindow = window.open(pdfUrl.value, '_blank')
    if (printWindow) {
      printWindow.focus()
      setTimeout(() => {
        printWindow.print()
      }, 500)
    }
  }
}

watch(printOption, (newVal) => {
  if (newVal) {
    if (newVal === 'Imprimir Completo') {
      printPedido('complete')
    } else if (newVal === 'Imprimir Resumido') {
      printPedido('summary')
    }
    printOption.value = ''
  }
})
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <Button variant="ghost" @click="goBack" class="px-2">
        <ArrowLeft class="w-5 h-5 mr-2" />
        <span class="font-bold">Voltar aos Pedidos</span>
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Detalhes do Pedido</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">
          Informações completas do pedido #{{ route.params.id }}
        </p>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center items-center h-64">
      <Loader2 class="w-10 h-10 animate-spin text-primary" />
      <p class="ml-3 text-lg text-muted-foreground">Carregando detalhes do pedido...</p>
    </div>
    <div v-else-if="errorMessage" class="text-center text-red-500 p-4 border border-red-300 rounded-lg">
      <AlertCircle class="inline-block w-5 h-5 mr-2" />
      {{ errorMessage }}
      <Button variant="link" @click="fetchPedidoDetalhes">Tentar novamente</Button>
    </div>

    <div v-else-if="!pedido" class="text-center text-muted-foreground p-4 border rounded-lg">
      <AlertCircle class="inline-block w-5 h-5 mr-2" />
      Pedido não encontrado.
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Pedido Info Card -->
      <Card class="lg:col-span-2 rounded-2xl border shadow-sm bg-card overflow-hidden">
        <CardHeader class="border-b bg-muted/20 px-8 py-6 flex flex-row justify-between items-center">
          <div>
            <CardTitle class="text-lg font-bold flex items-center gap-2">
              <ShoppingCart class="w-5 h-5 text-primary" /> Pedido #{{ pedido.id_pedido }}
            </CardTitle>
            <CardDescription class="text-xs font-medium mt-1">Detalhes e status do pedido</CardDescription>
          </div>
          
          <!-- Status Update Section -->
          <div class="flex items-center gap-3">
              <Select v-model="newStatus" :disabled="availableStatuses.length === 0" class="w-[150px]">
                <SelectTrigger class="h-10 rounded-lg border-dashed border-primary/30">
                  <SelectValue :placeholder="getStatusLabel(pedido.status)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="status in availableStatuses" :key="status.value" :value="status.value">{{ status.label }}</SelectItem>
                </SelectContent>
              </Select>
            <Button @click="updateOrderStatus" :disabled="isUpdatingStatus || pedido.status === newStatus" variant="outline" class="h-10 rounded-lg">
              <template v-if="isUpdatingStatus">
                <Loader2 class="w-4 h-4 animate-spin" /> Salvando
              </template>
              <template v-else>
                <Save class="w-4 h-4 mr-2" /> Salvar Status
              </template>
            </Button>
          </div>

          <!-- Print Options -->
          <div class="flex items-center gap-2">
            <Select v-model="printOption">
              <SelectTrigger class="w-[180px] h-10 rounded-lg">
                <SelectValue placeholder="Imprimir..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Imprimir Completo" :disabled="!pedido || pedido.itens.length === 0">
                  <Printer class="w-4 h-4 mr-2" /> Imprimir Completo
                </SelectItem>
                <SelectItem value="Imprimir Resumido">
                  <Printer class="w-4 h-4 mr-2" /> Imprimir Resumido
                </SelectItem>
              </SelectContent>
            </Select>

            <!-- PDF Preview Button -->
            <Button
              @click="showPdfPreview"
              :disabled="isGeneratingPdf || !pedido"
              variant="outline"
              class="h-10 rounded-lg border-blue-200 hover:border-blue-300 hover:bg-blue-50"
            >
              <template v-if="isGeneratingPdf">
                <Loader2 class="w-4 h-4 animate-spin mr-2" />
                Gerando...
              </template>
              <template v-else>
                <FileText class="w-4 h-4 mr-2" />
                Ver PDF
              </template>
            </Button>
          </div>
        </CardHeader>
        <CardContent class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs font-semibold text-muted-foreground">Status</p>
              <Badge 
                variant="outline" 
                :class="['rounded-lg px-3 py-0.5 text-[10px] font-bold tracking-wide uppercase border', getStatusClass(pedido.status)]"
              >
                {{ getStatusLabel(pedido.status) }}
              </Badge>
            </div>
            <div>
              <p class="text-xs font-semibold text-muted-foreground">Data do Pedido</p>
              <p class="font-bold text-sm">{{ pedido.data_pedido }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs font-semibold text-muted-foreground">Valor Total</p>
              <p class="font-bold text-lg text-primary">{{ formatCurrency(pedido.total_pedido) }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold text-muted-foreground">Forma de Pagamento</p>
              <p class="font-bold text-sm">{{ pedido.forma_pag }}</p>
            </div>
          </div>
          <div v-if="pedido.ped_desconto > 0">
            <p class="text-xs font-semibold text-muted-foreground">Desconto</p>
            <p class="font-bold text-sm">{{ formatCurrency(pedido.ped_desconto) }}</p>
          </div>
          <div v-if="pedido.obs_pedido">
            <p class="text-xs font-semibold text-muted-foreground">Observações do Pedido</p>
            <p class="text-sm">{{ pedido.obs_pedido }}</p>
          </div>
          <div v-if="pedido.obs_entrega">
            <p class="text-xs font-semibold text-muted-foreground">Observações de Entrega</p>
            <p class="text-sm">{{ pedido.obs_entrega }}</p>
          </div>
          <div v-if="pedido.obs_cancelamento && pedido.status === 3">
            <p class="text-xs font-semibold text-muted-foreground">Motivo Cancelamento</p>
            <p class="text-sm text-destructive">{{ pedido.obs_cancelamento }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Cliente Info Card -->
      <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
        <CardHeader class="border-b bg-muted/20 px-8 py-6">
          <CardTitle class="text-lg font-bold flex items-center gap-2">
            <User class="w-5 h-5 text-primary" /> Cliente
          </CardTitle>
          <CardDescription class="text-xs font-medium mt-1">Dados do cliente associado</CardDescription>
        </CardHeader>
        <CardContent class="p-6 space-y-4">
          <div>
            <p class="text-xs font-semibold text-muted-foreground">Nome Fantasia</p>
            <p class="font-bold text-sm">{{ cliente?.nome_fantasia || cliente?.razao_social || pedido.razao_social || '-' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">Responsável</p>
            <p class="font-bold text-sm">{{ cliente?.responsavel || '-' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">CPF/CNPJ</p>
            <p class="font-bold text-sm">{{ cliente?.cpf_cnpj || '-' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">Email</p>
            <p class="font-bold text-sm">{{ cliente?.email || '-' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">Contato</p>
            <p class="font-bold text-sm">{{ cliente?.contato_1 || '-' }} <span v-if="cliente?.contato_2">/ {{ cliente?.contato_2 }}</span></p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">Endereço</p>
            <p class="font-bold text-sm">{{ cliente?.endereco || '-' }}<span v-if="cliente?.bairro">, {{ cliente?.bairro }}</span></p>
            <p class="font-bold text-sm">{{ cliente?.cidade || '-' }}<span v-if="cliente?.estado"> - {{ cliente?.estado }}</span><span v-if="cliente?.cep">, {{ cliente?.cep }}</span></p>
          </div>
        </CardContent>
      </Card>

      <!-- Itens do Pedido Card -->
      <Card class="lg:col-span-3 rounded-2xl border shadow-sm bg-card overflow-hidden">
        <CardHeader class="border-b bg-muted/20 px-8 py-6">
          <div class="flex items-center justify-between gap-4">
            <CardTitle class="text-lg font-bold flex items-center gap-2">
            <Package class="w-5 h-5 text-primary" /> Itens do Pedido
            </CardTitle>
            <Button variant="outline" class="h-10 rounded-lg gap-2" @click="openAddItem">
              <Plus class="w-4 h-4" /> Novo Item
            </Button>
          </div>
          <CardDescription class="text-xs font-medium mt-1">Adicionar, editar e remover itens do pedido</CardDescription>
        </CardHeader>
        <CardContent v-if="isItemEditorOpen" class="p-6 border-b bg-muted/10">
          <div class="flex items-center justify-between gap-4 mb-4">
            <div class="space-y-0.5">
              <p class="text-sm font-extrabold tracking-tight">
                {{ editingItemId ? `Editar item #${editingItemId}` : 'Novo item' }}
              </p>
              <p class="text-xs text-muted-foreground font-medium">Defina produto, região e quantidades por tamanho.</p>
            </div>
            <Button variant="ghost" size="icon" class="h-9 w-9 rounded-lg" :disabled="isSavingItem" @click="resetItemForm">
              <X class="w-4 h-4" />
            </Button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8 space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2 space-y-1.5">
                  <Label class="text-xs font-semibold text-foreground/70">Produto</Label>
                  <Select v-model="itemFormProductId" :disabled="isSavingItem || isLoadingProdutos">
                    <SelectTrigger class="w-full bg-background border h-11 rounded-lg">
                      <SelectValue placeholder="Selecione um produto..." />
                    </SelectTrigger>
                    <SelectContent class="max-h-64">
                      <SelectItem v-for="p in produtos" :key="p.id_produto" :value="String(p.id_produto)">
                        {{ p.produto }} - {{ p.referencia }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold text-foreground/70">Região (preço)</Label>
                  <Select v-model="itemFormRegiao" :disabled="isSavingItem">
                    <SelectTrigger class="w-full bg-background border h-11 rounded-lg">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="nordeste">Nordeste</SelectItem>
                      <SelectItem value="norte">Norte</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="rounded-xl border bg-card/50 p-4">
                <div class="space-y-5">
                  <div v-for="group in SIZE_GROUPS" :key="group.title" class="space-y-3">
                    <div class="flex items-center justify-between">
                      <p class="text-[11px] font-black uppercase tracking-wider text-muted-foreground">{{ group.title }}</p>
                      <p class="text-[11px] font-semibold text-muted-foreground">Qtd: {{ group.keys.reduce((a, k) => a + (Number(itemFormQuantidades[k]) || 0), 0) }}</p>
                    </div>
                    <div class="grid grid-cols-4 sm:grid-cols-7 md:grid-cols-8 gap-3">
                      <div v-for="key in group.keys" :key="key" class="flex flex-col items-center space-y-1">
                        <Label class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">{{ sizeLabel(key) }}</Label>
                        <Input
                          type="number"
                          min="0"
                          class="h-10 rounded-lg text-center"
                          v-model.number="itemFormQuantidades[key]"
                          :disabled="isSavingItem"
                        />
                      </div>
                    </div>
                    <Separator v-if="group.title !== SIZE_GROUPS[SIZE_GROUPS.length - 1].title" />
                  </div>
                </div>
              </div>
            </div>

            <div class="lg:col-span-4">
              <div class="rounded-2xl border bg-card p-5 space-y-4 shadow-sm">
                <p class="text-sm font-extrabold">Resumo do item</p>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-muted-foreground">Peças</span>
                    <span class="font-bold">{{ itemPieces }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-muted-foreground">Preço unitário</span>
                    <span class="font-bold">{{ formatCurrency(itemUnitPrice) }}</span>
                  </div>
                  <div class="flex justify-between border-t pt-2">
                    <span class="font-bold">Total estimado</span>
                    <span class="font-black text-primary">{{ formatCurrency(itemEstimate) }}</span>
                  </div>
                </div>

                <div class="flex gap-2 pt-2">
                  <Button variant="outline" class="flex-1 h-11 rounded-xl" :disabled="isSavingItem" @click="resetItemForm">Cancelar</Button>
                  <Button class="flex-1 h-11 rounded-xl gap-2" :disabled="isSavingItem" @click="saveItem">
                    <template v-if="editingItemId">
                      <Pencil class="w-4 h-4" /> Salvar
                    </template>
                    <template v-else>
                      <Plus class="w-4 h-4" /> Adicionar
                    </template>
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </CardContent>

        <CardContent class="p-6">
          <div v-if="pedido.itens.length === 0" class="text-center py-10 text-muted-foreground">
            Nenhum item neste pedido.
          </div>
          <div v-else class="space-y-4">
            <Card v-for="item in pedido.itens" :key="item.id_item_pedido" class="border rounded-lg shadow-sm">
              <CardContent class="p-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div class="flex-1 space-y-2">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-bold text-muted-foreground">#{{ item.id_item_pedido }}</span>
                      <Badge variant="outline" class="text-xs">{{ item.referencia }}</Badge>
                    </div>
                    <p class="font-bold text-base">{{ item.produto }}</p>
                    <div class="flex items-center gap-4 text-sm">
                      <span class="text-muted-foreground">Quantidade:</span>
                      <span class="font-semibold">{{ getItemQtdTotal(item) }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                      <span class="text-muted-foreground">Total:</span>
                      <span class="font-bold text-primary">{{ formatCurrency(item.total_item) }}</span>
                    </div>
                    <div v-if="getSizeBadges(mapItemToQuantidades(item)).length > 0" class="flex flex-wrap gap-1 mt-2">
                      <Badge
                        v-for="b in getSizeBadges(mapItemToQuantidades(item))"
                        :key="`${item.id_item_pedido}-${b.key}`"
                        variant="outline"
                        class="h-5 px-2 rounded-md text-[10px] font-bold"
                      >
                        {{ b.label }}:{{ b.value }}
                      </Badge>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <Button variant="ghost" size="icon" class="h-9 w-9 rounded-lg hover:bg-accent" :disabled="isSavingItem" @click="openEditItem(item)">
                      <Pencil class="w-4 h-4" />
                    </Button>
                    <Button variant="ghost" size="icon" class="h-9 w-9 rounded-lg text-destructive hover:text-destructive" :disabled="isSavingItem" @click="removeItem(item.id_item_pedido)">
                      <Trash2 class="w-4 h-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Preview Modal -->
    <PedidoPrint v-if="showPreviewModal && printMode" :pedido="pedido" :cliente="cliente" :mode="printMode" :showButton="true" :preview="true" @closePreview="showPreviewModal = false" @pdfGenerated="onPdfGenerated" />

    <!-- PDF Preview Modal -->
    <div v-if="showPdfModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden">
        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
          <h2 class="text-lg font-semibold flex items-center gap-2">
            <FileText class="w-5 h-5" />
            Preview do PDF - Pedido #{{ pedido?.id_pedido }}
          </h2>
          <Button @click="closePdfModal" variant="ghost" size="icon" class="h-8 w-8 rounded-full">
            <X class="w-4 h-4" />
          </Button>
        </div>

        <div v-if="pdfError" class="p-4 bg-red-50 border-b border-red-200">
          <div class="flex items-center gap-2 text-red-700">
            <AlertCircle class="w-4 h-4" />
            <span class="text-sm">{{ pdfError }}</span>
          </div>
        </div>

        <div class="p-4">
          <PdfViewer
            :pdfUrl="pdfUrl"
            :width="800"
            :height="600"
            v-if="pdfUrl && !pdfError"
          />
        </div>

        <div class="flex justify-end gap-3 p-4 border-t bg-gray-50">
          <Button @click="closePdfModal" variant="outline">
            Fechar
          </Button>
          <Button
            @click="printPdf"
            :disabled="!pdfUrl"
            class="bg-blue-600 hover:bg-blue-700 text-white"
          >
            <Printer class="w-4 h-4 mr-2" />
            Imprimir
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
