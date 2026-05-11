<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, User, Package, ShoppingCart, Loader2, AlertCircle, Save, Plus, Pencil, Trash2, X, Printer, FileText } from 'lucide-vue-next'
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
const printOption = ref<string>('')

// PDF Viewer Modal
const showPdfModal = ref(false)
const pdfBlob = ref<Blob | null>(null)
const pdfBlobUrl = ref<string>('')
type PdfDocumentState = 'idle' | 'generating' | 'ready' | 'printing' | 'printed' | 'error'
interface GeneratedDocumentMetadata {
  document_id: string
  type: string
  entity_id: number
  template: string
  version: string
  filename: string
  content_type: string
  size: number
  created_at: string
  content_url: string
  metadata_url: string
}

interface PreviewContractItem {
  id: number
  name: string
  reference: string
  quantity: number
  sizes: string
  unit_price: number
  unit_price_formatted: string
  total: number
  total_formatted: string
}

interface PreviewContractResponse {
  mode: 'complete' | 'summary' | string
  mode_label: string
  show_client_section: boolean
  show_additional_info: boolean
  order: {
    number: number
    date: string
    status: number
    status_label: string
  }
  customer: {
    name: string
    responsible: string
    document: string
    email: string
    contact: string
    city_state: string
    address: string
  }
  items: PreviewContractItem[]
  payment: {
    method: string
    discount: number
    discount_formatted: string
    total: number
    total_formatted: string
  }
  notes: {
    pedido: string
    entrega: string
  }
  signature_name: string
  generated_at?: string
  generated_at_formatted?: string
  footer_text?: string
  layout_version: string
}

interface PreviewModel {
  modeLabel: string
  showClientSection: boolean
  showAdditionalInfo: boolean
  orderNumber: string
  orderDate: string
  orderStatus: string
  customer: {
    name: string
    responsible: string
    document: string
    email: string
    contact: string
    cityState: string
    address: string
  }
  items: Array<{
    id: number
    name: string
    reference: string
    quantity: number
    sizes: string
    unitPrice: string
    total: string
  }>
  payment: {
    method: string
    discount: number
    total: string
  }
  notes: {
    pedido: string
    entrega: string
  }
  signatureName: string
  generatedAtFormatted: string
  footerText: string
}

const pdfDocumentState = ref<PdfDocumentState>('idle')
const pdfError = ref<string>('')
const currentPrintMode = ref<'complete' | 'summary'>('complete')
const documentMetadata = ref<GeneratedDocumentMetadata | null>(null)
const serverPreviewModel = ref<PreviewModel | null>(null)
const isLoadingPreviewModel = ref(false)
const documentActionFeedback = ref<{ type: 'success' | 'error'; message: string } | null>(null)
let feedbackTimer: number | null = null
const autoPrintWarning = ref<string | null>(null)
const isAutoPrintingCompletedOrder = ref(false)

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

const getItemUnitPrice = (item: PedidoItem) => {
  const quantity = getItemQtdTotal(item)
  return quantity > 0 ? Number(item.total_item) / quantity : 0
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0)
}

const formatDateBr = (value: unknown) => {
  if (!value) return '-'
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) return safeText(value)
  return date.toLocaleDateString('pt-BR')
}

const formatDateTimeBr = (value: unknown) => {
  if (!value) return '-'
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) return safeText(value)
  return date.toLocaleString('pt-BR')
}

const getSizeBadges = (q: Record<string, number>) => {
  const list: Array<{ key: string; label: string; value: number }> = []
  for (const key of SIZE_KEYS) {
    const value = Number(q[key]) || 0
    if (value > 0) list.push({ key, label: sizeLabel(key), value })
  }
  return list
}

const getItemSizeText = (item: PedidoItem) => {
  const badges = getSizeBadges(mapItemToQuantidades(item))
  return badges.length > 0 ? badges.map(b => `${b.label}:${b.value}`).join(' | ') : '-'
}

const safeText = (value: unknown) => {
  if (value === null || value === undefined || value === '') return '-'
  return String(value)
}

const hasContent = (value: string) => value !== '-'

const mapPreviewFromApi = (data: PreviewContractResponse): PreviewModel => {
  const items = (data.items || []).map(item => ({
    id: Number(item.id) || 0,
    name: safeText(item.name),
    reference: safeText(item.reference),
    quantity: Number(item.quantity) || 0,
    sizes: safeText(item.sizes),
    unitPrice: safeText(item.unit_price_formatted || formatCurrency(Number(item.unit_price) || 0)),
    total: safeText(item.total_formatted || formatCurrency(Number(item.total) || 0)),
  }))

  return {
    modeLabel: safeText(data.mode_label),
    showClientSection: Boolean(data.show_client_section),
    showAdditionalInfo: Boolean(data.show_additional_info),
    orderNumber: safeText(data.order?.number),
    orderDate: safeText(data.order?.date),
    orderStatus: safeText(data.order?.status_label),
    customer: {
      name: safeText(data.customer?.name),
      responsible: safeText(data.customer?.responsible),
      document: safeText(data.customer?.document),
      email: safeText(data.customer?.email),
      contact: safeText(data.customer?.contact),
      cityState: safeText(data.customer?.city_state),
      address: safeText(data.customer?.address),
    },
    items,
    payment: {
      method: safeText(data.payment?.method),
      discount: Number(data.payment?.discount) || 0,
      total: safeText(data.payment?.total_formatted || formatCurrency(Number(data.payment?.total) || 0)),
    },
    notes: {
      pedido: safeText(data.notes?.pedido),
      entrega: safeText(data.notes?.entrega),
    },
    signatureName: safeText(data.signature_name),
    generatedAtFormatted: safeText(data.generated_at_formatted || formatDateTimeBr(data.generated_at)),
    footerText: safeText(data.footer_text || 'Sistema Lizzie - Gestão Empresarial | Este documento é oficial e foi gerado automaticamente pelo sistema.'),
  }
}

const localPreviewModel = computed<PreviewModel>(() => {
  const pedidoData = pedido.value
  const clienteData = cliente.value
  const isSummaryMode = currentPrintMode.value === 'summary'

  const itemRows = (pedidoData?.itens || []).map(item => ({
    id: item.id_item_pedido,
    name: safeText(item.produto),
    reference: safeText(item.referencia),
    quantity: getItemQtdTotal(item),
    sizes: getItemSizeText(item),
    unitPrice: formatCurrency(getItemUnitPrice(item)),
    total: formatCurrency(Number(item.total_item) || 0),
  }))

  const customerName = safeText(clienteData?.nome_fantasia || clienteData?.razao_social || pedidoData?.razao_social)

  return {
    modeLabel: isSummaryMode ? 'Resumido' : 'Completo',
    showClientSection: !isSummaryMode,
    showAdditionalInfo: !isSummaryMode && Boolean(pedidoData?.obs_pedido || pedidoData?.obs_entrega),
    orderNumber: safeText(pedidoData?.id_pedido),
    orderDate: formatDateBr(pedidoData?.data_pedido),
    orderStatus: pedidoData ? getStatusLabel(pedidoData.status) : '-',
    customer: {
      name: customerName,
      responsible: safeText(clienteData?.responsavel),
      document: safeText(clienteData?.cpf_cnpj),
      email: safeText(clienteData?.email),
      contact: safeText(clienteData?.contato_1),
      cityState: `${safeText(clienteData?.cidade)} / ${safeText(clienteData?.estado)}`,
      address: `${safeText(clienteData?.endereco)}, ${safeText(clienteData?.bairro)} - ${safeText(clienteData?.cep)}`,
    },
    items: itemRows,
    payment: {
      method: safeText(pedidoData?.forma_pag),
      discount: Number(pedidoData?.ped_desconto) || 0,
      total: formatCurrency(Number(pedidoData?.total_pedido) || 0),
    },
    notes: {
      pedido: safeText(pedidoData?.obs_pedido),
      entrega: safeText(pedidoData?.obs_entrega),
    },
    signatureName: customerName,
    generatedAtFormatted: new Date().toLocaleString('pt-BR'),
    footerText: 'Sistema Lizzie - Gestão Empresarial | Este documento é oficial e foi gerado automaticamente pelo sistema.',
  }
})

const previewModel = computed<PreviewModel>(() => {
  return serverPreviewModel.value || localPreviewModel.value
})

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
  if (!pedido.value || newStatus.value === null || isUpdatingStatus.value) return

  if (pedido.value.status === newStatus.value) {
    return
  }

  isUpdatingStatus.value = true
  errorMessage.value = null
  autoPrintWarning.value = null

  try {
    const previousStatus = pedido.value.status
    const response = await apiClient.put(`/api/pedidos/${pedido.value.id_pedido}/status`, {
      status: newStatus.value
    })

    const updatedStatus = response.data?.data?.status !== undefined ? Number(response.data.data.status) : newStatus.value
    pedido.value.status = updatedStatus
    newStatus.value = updatedStatus

    const shouldAutoPrint = previousStatus !== 4 && updatedStatus === 4
    if (shouldAutoPrint) {
      const printed = await autoPrintCompletedOrder()
      if (!printed) {
        autoPrintWarning.value = 'Pedido concluído. O navegador bloqueou ou falhou na impressão automática. Use "Ver PDF" para imprimir.'
      }
    }
  } catch (error: any) {
    errorMessage.value = 'Erro ao atualizar status do pedido.'
    console.error('Failed to update order status:', error)
  } finally {
    isUpdatingStatus.value = false
  }
}

const isGeneratingPdf = computed(() => pdfDocumentState.value === 'generating')

const setPdfState = (state: PdfDocumentState, errorMessage = '') => {
  pdfDocumentState.value = state
  pdfError.value = errorMessage
}

const showDocumentFeedback = (type: 'success' | 'error', message: string) => {
  documentActionFeedback.value = { type, message }
  if (feedbackTimer) {
    window.clearTimeout(feedbackTimer)
  }
  feedbackTimer = window.setTimeout(() => {
    documentActionFeedback.value = null
    feedbackTimer = null
  }, 2500)
}

const revokePdfBlobUrl = () => {
  if (pdfBlobUrl.value) {
    URL.revokeObjectURL(pdfBlobUrl.value)
    pdfBlobUrl.value = ''
  }
}

const normalizeTemplateMode = (mode: unknown): 'complete' | 'summary' => {
  return mode === 'summary' ? 'summary' : 'complete'
}

const loadPreviewModel = async (mode: 'complete' | 'summary') => {
  if (!pedido.value) return false

  isLoadingPreviewModel.value = true
  try {
    const response = await apiClient.get(`/api/documents/pedido/${pedido.value.id_pedido}/preview`, {
      params: { template: mode },
    })
    const payload = response.data?.data as PreviewContractResponse | undefined
    if (!payload) {
      throw new Error('Payload de prévia não retornado pela API.')
    }
    serverPreviewModel.value = mapPreviewFromApi(payload)
    return true
  } catch (error: any) {
    console.error('Erro ao carregar contrato de prévia:', error)
    serverPreviewModel.value = null
    return false
  } finally {
    isLoadingPreviewModel.value = false
  }
}

const generatePedidoPdf = async (mode: unknown = 'complete') => {
  if (!pedido.value || isGeneratingPdf.value) return false

  const normalizedMode = normalizeTemplateMode(mode)
  currentPrintMode.value = normalizedMode
  setPdfState('generating')

  try {
    const generateResponse = await apiClient.post(`/api/documents/pedido/${pedido.value.id_pedido}/generate`, {
      template: normalizedMode,
      format: 'pdf',
      paper_size: 'a4',
      orientation: 'portrait',
      include_qr: false,
    })

    const metadata = generateResponse.data?.data as GeneratedDocumentMetadata | undefined
    const contentUrl = metadata?.content_url
    if (!contentUrl) {
      throw new Error('URL de conteúdo do documento não retornada pela API.')
    }
    documentMetadata.value = metadata || null

    const contentResponse = await apiClient.get(contentUrl, {
      responseType: 'blob'
    })

    const blob = new Blob([contentResponse.data], { type: 'application/pdf' })
    if (blob.size === 0) {
      throw new Error('PDF gerado está vazio')
    }

    revokePdfBlobUrl()
    pdfBlob.value = blob
    pdfBlobUrl.value = URL.createObjectURL(blob)
    setPdfState('ready')
    return true
  } catch (error: any) {
    const message = 'Erro ao gerar PDF: ' + (error.response?.data?.message || error.message)
    setPdfState('error', message)
    console.error('Erro ao gerar PDF:', error)
    return false
  }
}

const showPdfPreview = async (mode: unknown = 'complete') => {
  if (!pedido.value) return
  const normalizedMode = normalizeTemplateMode(mode)
  if (currentPrintMode.value !== normalizedMode) {
    revokePdfBlobUrl()
    pdfBlob.value = null
    documentMetadata.value = null
  }
  currentPrintMode.value = normalizedMode
  pdfError.value = ''
  showPdfModal.value = true
  await loadPreviewModel(normalizedMode)
}

const autoPrintCompletedOrder = async () => {
  if (!pedido.value || isAutoPrintingCompletedOrder.value) return false
  isAutoPrintingCompletedOrder.value = true

  try {
    const generated = await generatePedidoPdf('complete')
    if (!generated) return false

    showPdfModal.value = true
    const printed = await printPdf()
    return printed
  } catch (error: any) {
    setPdfState('error', 'Erro ao gerar PDF: ' + (error.response?.data?.message || error.message))
    console.error('Erro ao gerar PDF:', error)
    return false
  } finally {
    isAutoPrintingCompletedOrder.value = false
  }
}

const closePdfModal = () => {
  showPdfModal.value = false
  revokePdfBlobUrl()
  pdfBlob.value = null
  documentMetadata.value = null
  serverPreviewModel.value = null
  documentActionFeedback.value = null
  if (feedbackTimer) {
    window.clearTimeout(feedbackTimer)
    feedbackTimer = null
  }
  setPdfState('idle')
}

const printPedido = (mode: 'complete' | 'summary') => {
  showPdfPreview(mode)
}

const regeneratePdf = async () => {
  await loadPreviewModel(currentPrintMode.value)
  const generated = await generatePedidoPdf(currentPrintMode.value)
  if (generated) {
    showDocumentFeedback('success', 'Documento regerado com sucesso.')
  }
}

const ensurePdfReady = async () => {
  if (pdfBlobUrl.value && documentMetadata.value?.template === currentPrintMode.value) {
    return true
  }

  return await generatePedidoPdf(currentPrintMode.value)
}

const printPdf = async () => {
  const ready = await ensurePdfReady()
  if (!ready || !pdfBlobUrl.value) return false

  setPdfState('printing')

  const printWindow = window.open(pdfBlobUrl.value, '_blank')
  if (!printWindow) {
    setPdfState('error', 'Falha ao abrir a janela de impressão. Verifique se o bloqueador de pop-up está ativo.')
    showDocumentFeedback('error', 'Falha ao abrir a janela de impressão. Verifique o bloqueador de pop-up.')
    return false
  }

  printWindow.focus()
  setTimeout(() => {
    try {
      printWindow.print()
      setPdfState('printed')
      showDocumentFeedback('success', 'Comando de impressão enviado.')
    } catch (error) {
      setPdfState('error', 'Falha ao imprimir PDF.')
      showDocumentFeedback('error', 'Falha ao iniciar a impressão.')
      console.error('Erro ao imprimir PDF:', error)
    }
  }, 500)

  return true
}

const openPdfInNewTab = async () => {
  const ready = await ensurePdfReady()
  if (!ready || !pdfBlobUrl.value) return false

  const win = window.open(pdfBlobUrl.value, '_blank')
  if (win) {
    showDocumentFeedback('success', 'PDF aberto em nova aba.')
  } else {
    showDocumentFeedback('error', 'Não foi possível abrir nova aba. Verifique o bloqueador de pop-up.')
  }
  return Boolean(win)
}

const downloadPdf = async () => {
  const ready = await ensurePdfReady()
  if (!ready || !pdfBlobUrl.value) return

  const filename = documentMetadata.value?.filename || `pedido-${pedido.value?.id_pedido || 'documento'}.pdf`
  const anchor = document.createElement('a')
  anchor.href = pdfBlobUrl.value
  anchor.download = filename
  document.body.appendChild(anchor)
  anchor.click()
  document.body.removeChild(anchor)
  showDocumentFeedback('success', 'Download do PDF iniciado.')
}

const formatDocumentSize = (size?: number) => {
  if (!size || size <= 0) return '-'
  if (size < 1024) return `${size} B`
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`
  return `${(size / (1024 * 1024)).toFixed(2)} MB`
}

const formatDocumentDate = (isoDate?: string) => {
  if (!isoDate) return '-'
  const date = new Date(isoDate)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('pt-BR')
}

const getDocumentStateLabel = (state: PdfDocumentState) => {
  switch (state) {
    case 'idle': return 'Aguardando'
    case 'generating': return 'Gerando'
    case 'ready': return 'Pronto'
    case 'printing': return 'Imprimindo'
    case 'printed': return 'Impresso'
    case 'error': return 'Erro'
    default: return 'Aguardando'
  }
}

const getDocumentStateClass = (state: PdfDocumentState) => {
  switch (state) {
    case 'generating': return 'bg-blue-100 text-blue-700 border-blue-200'
    case 'ready': return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    case 'printing': return 'bg-amber-100 text-amber-700 border-amber-200'
    case 'printed': return 'bg-green-100 text-green-700 border-green-200'
    case 'error': return 'bg-rose-100 text-rose-700 border-rose-200'
    default: return 'bg-slate-100 text-slate-700 border-slate-200'
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
              @click="showPdfPreview()"
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
                Prévia
              </template>
            </Button>
          </div>
        </CardHeader>
        <CardContent class="p-6 space-y-4">
          <div v-if="autoPrintWarning" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
            <p class="text-xs font-medium text-amber-700">{{ autoPrintWarning }}</p>
          </div>
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

    <!-- PDF Preview Modal -->
    <div v-if="showPdfModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-2 sm:p-4">
      <div class="bg-white rounded-lg shadow-xl w-full h-[96vh] max-w-[96vw] overflow-hidden flex flex-col">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 p-4 border-b bg-gray-50 shrink-0">
          <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold flex items-center gap-2">
              <FileText class="w-5 h-5" />
              Prévia de Impressão - Pedido #{{ previewModel.orderNumber }} ({{ previewModel.modeLabel }})
            </h2>
            <Badge
              variant="outline"
              :class="['text-[10px] font-bold uppercase tracking-wide border', getDocumentStateClass(pdfDocumentState)]"
            >
              {{ getDocumentStateLabel(pdfDocumentState) }}
            </Badge>
          </div>
          <Button @click="closePdfModal" variant="ghost" size="icon" class="h-8 w-8 rounded-full">
            <X class="w-4 h-4" />
          </Button>
        </div>

        <div v-if="pdfError" class="p-4 bg-red-50 border-b border-red-200 shrink-0">
          <div class="flex items-center gap-2 text-red-700">
            <AlertCircle class="w-4 h-4" />
            <span class="text-sm">{{ pdfError }}</span>
          </div>
        </div>

        <div
          v-if="documentActionFeedback"
          :class="[
            'px-4 py-2 border-b text-sm shrink-0',
            documentActionFeedback.type === 'success'
              ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
              : 'bg-rose-50 border-rose-200 text-rose-700'
          ]"
        >
          {{ documentActionFeedback.message }}
        </div>

        <div class="p-3 sm:p-4 flex-1 min-h-0 flex flex-col bg-slate-100">
          <div v-if="documentMetadata" class="mb-3 rounded-lg border bg-white px-3 py-2 shrink-0">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
              <p><span class="font-semibold">Versão:</span> {{ documentMetadata.version?.slice(0, 12) || '-' }}</p>
              <p><span class="font-semibold">Tamanho:</span> {{ formatDocumentSize(documentMetadata.size) }}</p>
              <p><span class="font-semibold">Gerado em:</span> {{ formatDocumentDate(documentMetadata.created_at) }}</p>
            </div>
          </div>
          <div v-if="isGeneratingPdf" class="mb-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-700 flex items-center gap-2 shrink-0">
            <Loader2 class="w-4 h-4 animate-spin" />
            <span>Gerando PDF para ação selecionada...</span>
          </div>
          <div v-if="isLoadingPreviewModel" class="mb-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 flex items-center gap-2 shrink-0">
            <Loader2 class="w-4 h-4 animate-spin" />
            <span>Sincronizando prévia com o template do backend...</span>
          </div>

          <div class="flex-1 min-h-0 overflow-auto">
            <div class="mx-auto w-full max-w-[794px] bg-white text-slate-950 shadow-sm border rounded-md p-6 text-[10px] leading-[1.35]">
              <div class="border-b-2 border-slate-800 pb-[14px] mb-[18px] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                  <div class="w-[54px] h-[54px] border border-slate-300 bg-slate-100 flex items-center justify-center text-[8px] text-slate-500">
                    LOGO
                  </div>
                  <div>
                    <h3 class="text-[22px] font-bold leading-none text-slate-900">Sistema Lizzie</h3>
                    <p class="text-[10px] text-slate-500 mt-1">Sistema de Gestão Empresarial</p>
                  </div>
                </div>
                <div class="sm:text-right">
                  <p class="text-[16px] font-bold text-slate-900">Pedido #{{ previewModel.orderNumber }}</p>
                  <p class="text-[10px] text-slate-500">Data: {{ previewModel.orderDate }}</p>
                  <p class="text-[10px] text-slate-500">Status: {{ previewModel.orderStatus }}</p>
                </div>
              </div>

              <section v-if="previewModel.showClientSection" class="mb-[18px]">
                <h4 class="text-[12px] font-bold border-b border-slate-300 pb-[5px] mb-2">Dados do Cliente</h4>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-[10px] grid grid-cols-1 md:grid-cols-2 gap-2">
                  <p><span class="font-semibold">Nome/Razão Social:</span> {{ previewModel.customer.name }}</p>
                  <p><span class="font-semibold">Responsável:</span> {{ previewModel.customer.responsible }}</p>
                  <p><span class="font-semibold">CPF/CNPJ:</span> {{ previewModel.customer.document }}</p>
                  <p><span class="font-semibold">Email:</span> {{ previewModel.customer.email }}</p>
                  <p><span class="font-semibold">Contato:</span> {{ previewModel.customer.contact }}</p>
                  <p><span class="font-semibold">Cidade/Estado:</span> {{ previewModel.customer.cityState }}</p>
                  <p class="md:col-span-2"><span class="font-semibold">Endereço:</span> {{ previewModel.customer.address }}</p>
                </div>
              </section>

              <section class="mb-[18px]">
                <h4 class="text-[12px] font-bold border-b border-slate-300 pb-[5px] mb-2">Itens do Pedido</h4>
                <div class="overflow-x-auto rounded-md border border-slate-200">
                  <table class="w-full border-collapse">
                    <thead class="bg-slate-100 text-slate-900">
                      <tr>
                        <th class="px-[6px] py-[7px] text-left font-semibold">Produto</th>
                        <th class="px-[6px] py-[7px] text-center font-semibold">Qtd.</th>
                        <th class="px-[6px] py-[7px] text-center font-semibold">Tamanhos</th>
                        <th class="px-[6px] py-[7px] text-right font-semibold">Unitário</th>
                        <th class="px-[6px] py-[7px] text-right font-semibold">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in previewModel.items" :key="item.id" class="border-t border-slate-200">
                        <td class="px-[6px] py-[7px] min-w-[220px]">
                          <p class="font-medium">{{ item.name }}</p>
                          <p class="text-[8px] text-slate-500">{{ item.reference }}</p>
                        </td>
                        <td class="px-[6px] py-[7px] text-center">{{ item.quantity }}</td>
                        <td class="px-[6px] py-[7px] text-center">{{ item.sizes }}</td>
                        <td class="px-[6px] py-[7px] text-right">{{ item.unitPrice }}</td>
                        <td class="px-[6px] py-[7px] text-right font-semibold">{{ item.total }}</td>
                      </tr>
                      <tr class="border-t border-slate-300 bg-slate-100 font-bold">
                        <td colspan="4" class="px-[6px] py-[7px] text-right text-[11px]">Total do Pedido:</td>
                        <td class="px-[6px] py-[7px] text-right text-[11px]">{{ previewModel.payment.total }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <section class="mb-[18px]">
                <h4 class="text-[12px] font-bold border-b border-slate-300 pb-[5px] mb-2">Resumo e Pagamento</h4>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-[10px] grid grid-cols-1 md:grid-cols-2 gap-2">
                  <p><span class="font-semibold">Status:</span> {{ previewModel.orderStatus }}</p>
                  <p><span class="font-semibold">Forma de Pagamento:</span> {{ previewModel.payment.method }}</p>
                  <p v-if="previewModel.payment.discount > 0"><span class="font-semibold">Desconto:</span> {{ formatCurrency(previewModel.payment.discount) }}</p>
                  <p class="font-bold"><span>Total:</span> {{ previewModel.payment.total }}</p>
                </div>
              </section>

              <section v-if="previewModel.showAdditionalInfo" class="mb-[18px]">
                <h4 class="text-[12px] font-bold border-b border-slate-300 pb-[5px] mb-2">Informações Adicionais</h4>
                <div class="space-y-2">
                  <div v-if="hasContent(previewModel.notes.pedido)" class="rounded-md border border-slate-200 bg-slate-50 p-[9px]">
                    <p class="font-semibold mb-1">Observações do Pedido</p>
                    <p>{{ previewModel.notes.pedido }}</p>
                  </div>
                  <div v-if="hasContent(previewModel.notes.entrega)" class="rounded-md border border-slate-200 bg-slate-50 p-[9px]">
                    <p class="font-semibold mb-1">Observações de Entrega</p>
                    <p>{{ previewModel.notes.entrega }}</p>
                  </div>
                </div>
              </section>

              <section class="mb-[18px]">
                <h4 class="text-[12px] font-bold border-b border-slate-300 pb-[5px] mb-2">Assinaturas</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-10 text-center">
                  <div class="pt-[38px]">
                    <div class="border-b border-slate-500 h-[34px] mb-1.5"></div>
                    <p class="font-medium">Cliente</p>
                    <p class="text-[8px] text-slate-500">{{ previewModel.signatureName }}</p>
                  </div>
                  <div class="pt-[38px]">
                    <div class="border-b border-slate-500 h-[34px] mb-1.5"></div>
                    <p class="font-medium">Sistema Lizzie</p>
                    <p class="text-[8px] text-slate-500">Representante Autorizado</p>
                  </div>
                </div>
              </section>

              <section class="text-center text-[8px] text-slate-500 border-t-2 border-slate-200 pt-[10px] mt-[28px]">
                <p class="font-semibold">Documento gerado em {{ previewModel.generatedAtFormatted }}</p>
                <p>{{ previewModel.footerText }}</p>
              </section>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap justify-end gap-3 p-4 border-t bg-gray-50 shrink-0">
          <Button @click="closePdfModal" variant="outline">
            Fechar
          </Button>
          <Button
            @click="openPdfInNewTab"
            :disabled="pdfDocumentState === 'generating'"
            variant="outline"
          >
            Abrir PDF
          </Button>
          <Button
            @click="downloadPdf"
            :disabled="pdfDocumentState === 'generating'"
            variant="outline"
          >
            Baixar PDF
          </Button>
          <Button
            @click="regeneratePdf"
            :disabled="pdfDocumentState === 'generating'"
            variant="outline"
          >
            Regerar
          </Button>
          <Button
            @click="printPdf"
            :disabled="pdfDocumentState === 'generating' || pdfDocumentState === 'printing'"
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
