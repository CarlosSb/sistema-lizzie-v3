<script setup>
import { ref, reactive, computed, onMounted, watch, h } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { usePedido } from '../composables/usePedido'
import { usePedidoSearch, useProdutoSearch } from '../composables/usePedidoSearch'
import { usePedidoPrint } from '../composables/usePedidoPrint'
import api from '../composables/useApi'
import {
  NSelect, NButton, NIcon, NSpin, NInput, NDataTable, NModal,
  NCard, NTag, NPagination, NDropdown, NSpace, NBadge,
  NStatistic, NGrid, NGridItem, NDivider, NAvatar, NEmpty,
  NDrawer, NDrawerContent, NScrollbar, useMessage, NInputGroup,
  NSteps, NStep, NForm, NFormItem
} from 'naive-ui'
import {
  PrintOutline, AddOutline, RefreshOutline, SearchOutline,
  FilterOutline, DownloadOutline, ChevronDownOutline,
  EyeOutline, CreateOutline, TrashOutline, CheckmarkCircleOutline,
  CloseCircleOutline, TimeOutline, CalendarOutline, PersonOutline,
  LocationOutline, CallOutline, MailOutline, DocumentTextOutline,
  CashOutline, CarOutline, CloseOutline
} from '@vicons/ionicons5'
import { formatMoney, formatDate, formatDateTime } from '../utils/formatters'

const router = useRouter()
const authStore = useAuthStore()
const message = useMessage()

const loading = ref(true)
const pedidos = ref([])
const allPedidos = ref([])

const showModal = ref(false)
const showItensModal = ref(false)
const showCalculoModal = ref(false)
const showDetailsDrawer = ref(false)
const selectedPedido = ref(null)
const itens = ref([])
const calculo = ref(null)

// Wizard variables
const currentStep = ref(0)
const wizardSteps = [
  { title: 'Cliente', description: 'Selecionar cliente e região' },
  { title: 'Produtos', description: 'Adicionar itens ao pedido' },
  { title: 'Detalhes', description: 'Informações adicionais' },
  { title: 'Revisão', description: 'Confirmar pedido' }
]
const stepValidation = reactive({
  0: false, // Cliente step
  1: false, // Produtos step
  2: true,  // Detalhes step (optional)
  3: false  // Revisão step
})

// Auto-save variables
const DRAFT_KEY = 'pedido_draft'
const autoSaveInterval = ref(null)
const lastSaved = ref(null)

// Templates variables
const TEMPLATES_KEY = 'pedido_templates'
const showTemplateModal = ref(false)
const templateName = ref('')
const templates = ref([])
const loadingTemplates = ref(false)

const clientes = ref([])
const produtos = ref([])

const { searchQuery, searchResults, search, filterByStatus, filterByDateRange, filterByClient, filterByRegion, filterByValue, sortData, clearSearch } = usePedidoSearch()
const { searchQuery: produtoSearchQuery, results: produtoSearchResults, search: searchProdutos, loading: produtoSearchLoading } = useProdutoSearch()
const { exportPedidosToCSV, printing, printElement, generatePedidoPrintData, generateEtiquetaData, generateRomaneioData } = usePedidoPrint()

const statusFilter = ref(null)
const clientFilter = ref(null)
const regionFilter = ref(null)
const dateStartFilter = ref(null)
const dateEndFilter = ref(null)
const sortBy = ref('created_at')
const sortOrder = ref('desc')

const currentPage = ref(1)
const pageSize = ref(15)
const totalCount = ref(0)

const TAMANHOS_INFANTIS = ['pp', 'p', 'm', 'g', 'u', 'rn']
const TAMANHOS_FEMININO = ['ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12']
const TAMANHOS = ['pp', 'p', 'm', 'g', 'u', 'rn', 'ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12']

const statusOptions = [
  { label: 'Pendente', value: 'pendente', type: 'warning' },
  { label: 'Aprovado', value: 'aprovado', type: 'info' },
  { label: 'Concluído', value: 'concluido', type: 'success' },
  { label: 'Cancelado', value: 'cancelado', type: 'error' }
]

const regionOptions = [
  { label: 'Nordeste', value: 'norde' },
  { label: 'Norte', value: 'norte' }
]

const clienteOptions = computed(() => [
  { label: 'Todos os clientes', value: null },
  ...clientes.value.map(c => ({ label: c.razao_social, value: c.id_cliente }))
])

const produtoOptions = computed(() => [
  { label: 'Selecione Produto', value: '' },
  ...produtos.value.map(p => ({ label: `${p.produto} - R$ ${p.valor_unt_norde}`, value: p.id_produto }))
])

const { pedido, adicionarItem, removerItem, atualizarQuantidade, getPrecoUnitario, calcularSubtotalItem, calcularDescontoItem, calcularTotalItem, calcularQuantidadeTotal, resumo, criarPayload, reset } = usePedido()

const formMode = ref('new')
const editingId = ref(null)
const saving = ref(false)
const calcLoading = ref(false)

const form = reactive({
  id_cliente: '',
  id_vendedor: '',
  obs_pedido: '',
  obs_entrega: '',
  forma_pag: '',
  regiao: 'norde',
  desconto_percentual: 0,
  desconto_valor: 0
})

const selectedProduto = ref('')
const itemQuantidades = reactive({})
const itemDesconto = reactive({ p: 0, v: 0 })

const filteredPedidos = computed(() => {
  let result = [...allPedidos.value]
  
  if (searchQuery.value && searchQuery.value.length >= 2) {
    result = search(result, searchQuery.value)
  }
  
  if (statusFilter.value) {
    result = result.filter(p => p.status === statusFilter.value)
  }
  
  if (clientFilter.value) {
    result = result.filter(p => p.id_cliente === clientFilter.value)
  }
  
  if (regionFilter.value) {
    result = result.filter(p => p.regiao === regionFilter.value)
  }
  
  if (dateStartFilter.value || dateEndFilter.value) {
    result = result.filter(p => {
      const date = new Date(p.created_at)
      const start = dateStartFilter.value ? new Date(dateStartFilter.value) : null
      const end = dateEndFilter.value ? new Date(dateEndFilter.value) : null
      if (start && date < start) return false
      if (end && date > end) return false
      return true
    })
  }
  
  result = sortData(result, sortBy.value, sortOrder.value)
  
  return result
})

const paginatedPedidos = computed(() => {
  const data = filteredPedidos.value
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  totalCount.value = data.length
  return data.slice(start, end)
})

const stats = computed(() => {
  const total = allPedidos.value.length
  const pendente = allPedidos.value.filter(p => p.status === 'pendente').length
  const aprovado = allPedidos.value.filter(p => p.status === 'aprovado').length
  const concluido = allPedidos.value.filter(p => p.status === 'concluido').length
  const cancelado = allPedidos.value.filter(p => p.status === 'cancelado').length
  const valorTotal = allPedidos.value.reduce((sum, p) => sum + parseFloat(p.total_pedido || 0), 0)

  return { total, pendente, aprovado, concluido, cancelado, valorTotal }
})

// Wizard validation computed properties
const isClienteStepValid = computed(() => {
  return form.id_cliente && form.regiao
})

const isProdutosStepValid = computed(() => {
  return pedido.itens.length > 0
})

const isRevisaoStepValid = computed(() => {
  return isClienteStepValid.value && isProdutosStepValid.value
})

// Enhanced product search options
const enhancedProdutoOptions = computed(() => {
  if (produtoSearchQuery.value && produtoSearchQuery.value.length >= 2) {
    const searchResults = searchProdutos(produtos.value, produtoSearchQuery.value)
    return [
      { label: 'Selecione Produto', value: '', disabled: true },
      ...searchResults.map(p => ({
        label: `${p.produto} - R$ ${p.valor_unt_norde} (${p.referencia})`,
        value: p.id_produto,
        group: p.categoria || 'Outros'
      }))
    ]
  }
  return produtoOptions.value
})

// Update step validation reactively
watch(isClienteStepValid, (newVal) => {
  stepValidation[0] = newVal
})

watch(isProdutosStepValid, (newVal) => {
  stepValidation[1] = newVal
})

watch(isRevisaoStepValid, (newVal) => {
  stepValidation[3] = newVal
})

// Stop auto-save when modal closes
watch(showModal, (newVal) => {
  if (!newVal) {
    stopAutoSave()
  }
})

const columns = [
  {
    title: 'ID',
    key: 'id_pedido',
    width: 80,
    render(row) {
      return h('span', { class: 'font-mono font-medium' }, `#${row.id_pedido}`)
    }
  },
  {
    title: 'Data',
    key: 'created_at',
    width: 110,
    sorter: 'default',
    render(row) {
      return h('span', {}, formatDate(row.created_at))
    }
  },
  {
    title: 'Cliente',
    key: 'cliente',
    minWidth: 200,
    render(row) {
      return h('div', { class: 'truncate' }, [
        h('div', { class: 'font-medium' }, row.cliente?.razao_social || 'N/A'),
        h('div', { class: 'text-xs text-gray-500' }, row.cliente?.nome_fantasia || '')
      ])
    }
  },
  {
    title: 'Região',
    key: 'regiao',
    width: 100,
    render(row) {
      const region = row.regiao === 'norde' ? 'Nordeste' : 'Norte'
      return h(NTag, { type: row.regiao === 'norde' ? 'warning' : 'info', size: 'small' }, () => region)
    }
  },
  {
    title: 'Qtd',
    key: 'itens_count',
    width: 70,
    align: 'center',
    render(row) {
      return h('span', { class: 'font-medium' }, row.itens_count || 0)
    }
  },
  {
    title: 'Total',
    key: 'total_pedido',
    width: 120,
    sorter: 'default',
    align: 'right',
    render(row) {
      return h('span', { class: 'font-bold text-green-600' }, `R$ ${formatMoney(row.total_pedido)}`)
    }
  },
  {
    title: 'Status',
    key: 'status',
    width: 120,
    render(row) {
      const statusConfig = {
        pendente: { type: 'warning', label: 'Pendente' },
        aprovado: { type: 'info', label: 'Aprovado' },
        concluido: { type: 'success', label: 'Concluído' },
        cancelado: { type: 'error', label: 'Cancelado' }
      }
      const config = statusConfig[row.status] || { type: 'default', label: row.status }
      return h(NTag, { type: config.type, size: 'small' }, () => config.label)
    }
  },
  {
    title: 'Ações',
    key: 'actions',
    width: 160,
    render(row) {
      return h('div', { class: 'flex gap-1' }, [
        h(NButton, {
          size: 'tiny',
          quaternary: true,
          onClick: () => openDetails(row)
        }, () => h(NIcon, null, () => h(EyeOutline))),
        h(NButton, {
          size: 'tiny',
          quaternary: true,
          onClick: () => printPedido(row)
        }, () => h(NIcon, null, () => h(PrintOutline))),
        h(NButton, {
          size: 'tiny',
          quaternary: true,
          onClick: () => editPedido(row)
        }, () => h(NIcon, null, () => h(CreateOutline)))
      ])
    }
  }
]

// Wizard navigation functions
function nextStep() {
  if (currentStep.value < wizardSteps.length - 1) {
    const currentStepIndex = currentStep.value
    if (stepValidation[currentStepIndex]) {
      currentStep.value++
    } else {
      message.warning('Complete os campos obrigatórios desta etapa')
    }
  }
}

function prevStep() {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

function goToStep(step) {
  if (step <= currentStep.value || stepValidation[currentStep.value]) {
    currentStep.value = step
  }
}

function resetWizard() {
  currentStep.value = 0
  Object.keys(stepValidation).forEach(key => {
    stepValidation[key] = key === '2' ? true : false // Step 2 (Detalhes) is optional
  })
}

// Auto-save functions
function startAutoSave() {
  stopAutoSave() // Clear any existing interval
  autoSaveInterval.value = setInterval(() => {
    saveDraft()
  }, 3000) // Save every 3 seconds
}

function stopAutoSave() {
  if (autoSaveInterval.value) {
    clearInterval(autoSaveInterval.value)
    autoSaveInterval.value = null
  }
}

function saveDraft() {
  const draftData = {
    form: { ...form },
    pedido: { ...pedido },
    currentStep: currentStep.value,
    selectedProduto: selectedProduto.value,
    itemQuantidades: { ...itemQuantidades },
    itemDesconto: { ...itemDesconto },
    timestamp: new Date().toISOString()
  }

  try {
    localStorage.setItem(DRAFT_KEY, JSON.stringify(draftData))
    lastSaved.value = new Date()
  } catch (error) {
    console.warn('Failed to save draft:', error)
  }
}

function loadDraft() {
  try {
    const draftStr = localStorage.getItem(DRAFT_KEY)
    if (draftStr) {
      const draftData = JSON.parse(draftStr)
      // Only load if draft is less than 24 hours old
      const draftTime = new Date(draftData.timestamp)
      const now = new Date()
      const hoursDiff = (now - draftTime) / (1000 * 60 * 60)

      if (hoursDiff < 24) {
        Object.assign(form, draftData.form)
        Object.assign(pedido, draftData.pedido)
        currentStep.value = draftData.currentStep || 0
        selectedProduto.value = draftData.selectedProduto || ''
        Object.assign(itemQuantidades, draftData.itemQuantidades || {})
        Object.assign(itemDesconto, draftData.itemDesconto || {})

        lastSaved.value = draftTime
        return true
      }
    }
  } catch (error) {
    console.warn('Failed to load draft:', error)
  }
  return false
}

function clearDraft() {
  localStorage.removeItem(DRAFT_KEY)
  lastSaved.value = null
}

// Template functions
function loadTemplates() {
  try {
    const templatesStr = localStorage.getItem(TEMPLATES_KEY)
    if (templatesStr) {
      templates.value = JSON.parse(templatesStr)
    }
  } catch (error) {
    console.warn('Failed to load templates:', error)
    templates.value = []
  }
}

function saveTemplate(name) {
  if (!name.trim()) {
    message.warning('Digite um nome para o template')
    return
  }

  if (templates.value.some(t => t.name === name.trim())) {
    message.warning('Já existe um template com este nome')
    return
  }

  const templateData = {
    id: Date.now().toString(),
    name: name.trim(),
    form: { ...form },
    pedido: { ...pedido },
    createdAt: new Date().toISOString()
  }

  templates.value.push(templateData)
  saveTemplatesToStorage()

  message.success(`Template "${name}" salvo com sucesso!`)
  templateName.value = ''
  showTemplateModal.value = false
}

function loadTemplate(templateId) {
  const template = templates.value.find(t => t.id === templateId)
  if (template) {
    // Reset current state
    reset()
    resetWizard()

    // Load template data
    Object.assign(form, template.form)
    Object.assign(pedido, template.pedido)

    message.success(`Template "${template.name}" carregado!`)
    showTemplateModal.value = false
  }
}

function deleteTemplate(templateId) {
  const index = templates.value.findIndex(t => t.id === templateId)
  if (index > -1) {
    const templateName = templates.value[index].name
    templates.value.splice(index, 1)
    saveTemplatesToStorage()
    message.success(`Template "${templateName}" excluído!`)
  }
}

function saveTemplatesToStorage() {
  try {
    localStorage.setItem(TEMPLATES_KEY, JSON.stringify(templates.value))
  } catch (error) {
    console.warn('Failed to save templates:', error)
  }
}

function openNew() {
  reset()
  resetWizard()

  // Load draft if available
  const hasDraft = loadDraft()
  if (hasDraft) {
    message.info('Rascunho carregado automaticamente', { duration: 3000 })
  }

  formMode.value = 'new'
  showModal.value = true

  // Start auto-save
  startAutoSave()
}

function openDetails(pedido) {
  selectedPedido.value = pedido
  showDetailsDrawer.value = true
  verItens(pedido)
}

function editPedido(p) {
  reset()
  Object.assign(pedido, p)
  formMode.value = 'edit'
  editingId.value = p.id_pedido
  showModal.value = true
}

async function loadPedidos() {
  try {
    loading.value = true
    const response = await api.getPedidos()
    allPedidos.value = response.data?.data || []
  } catch (error) {
    console.error('Erro ao carregar pedidos:', error)
    message.error('Erro ao carregar pedidos')
  } finally {
    loading.value = false
  }
}

async function loadClientes() {
  try {
    const response = await api.getClientes()
    clientes.value = response.data?.data || response.data || []
  } catch (error) {
    console.error('Erro ao carregar clientes:', error)
  }
}

async function loadProdutos() {
  try {
    const response = await api.getProdutos()
    produtos.value = response.data?.data || response.data || []
  } catch (error) {
    console.error('Erro ao carregar produtos:', error)
  }
}

function getItemPreco(item) {
  return item.regiao === 'norte' ? item.produto.valor_unt_norte : item.produto.valor_unt_norde
}

function getItemSubtotal(item) {
  const preco = getItemPreco(item)
  const qtd = calcularQuantidadeTotal(item.quantidades)
  return qtd * preco
}

async function savePedido() {
  if (!form.id_cliente) {
    message.warning('Selecione um cliente')
    return
  }
  if (pedido.itens.length === 0) {
    message.warning('Adicione pelo menos um item')
    return
  }

  saving.value = true
  try {
    const payload = criarPayload()
    payload.id_cliente = parseInt(form.id_cliente)
    payload.id_vendedor = authStore.user?.id
    payload.obs_pedido = form.obs_pedido
    payload.obs_entrega = form.obs_entrega
    payload.forma_pag = form.forma_pag
    payload.regiao = form.regiao
    payload.desconto_percentual = form.desconto_percentual
    payload.desconto_valor = form.desconto_valor

    await api.createPedido(payload)
    showModal.value = false
    resetWizard()
    clearDraft()
    stopAutoSave()
    message.success('Pedido criado com sucesso!')
    await loadPedidos()
  } catch (error) {
    console.error('Erro:', error)
    message.error('Erro ao criar pedido')
  } finally {
    saving.value = false
  }
}

async function verItens(pedido) {
  selectedPedido.value = pedido

  try {
    if (!pedido._itens) {
      const response = await api.getItensPedido(pedido.id_pedido)
      pedido._itens = response.data?.data || []
    }
    itens.value = pedido._itens || []
  } catch (error) {
    console.error('Erro ao carregar itens:', error)
    itens.value = []
  }
}

async function verDetalhes(pedido) {
  selectedPedido.value = pedido
  showCalculoModal.value = true

  calcLoading.value = true
  try {
    const response = await api.getCalculoPedido(pedido.id_pedido)
    calculo.value = response.data.data
  } catch (error) {
    console.error('Erro:', error)
  } finally {
    calcLoading.value = false
  }
}

async function updateStatus(pedido, newStatus) {
  try {
    await api.updateStatusPedido(pedido.id_pedido, { status: newStatus })
    message.success('Status atualizado')
    await loadPedidos()
  } catch (error) {
    console.error('Erro ao atualizar status:', error)
    message.error('Erro ao atualizar status')
  }
}

function printPedido(pedido) {
  selectedPedido.value = pedido
  verItens(pedido).then(() => {
    printElement(`print-pedido-${pedido.id_pedido}`)
  })
}

async function handlePrint(type) {
  if (!selectedPedido.value) return

  // Ensure we have the items loaded
  await verItens(selectedPedido.value)

  // Find the client data
  const cliente = clientes.value.find(c => c.id_cliente == selectedPedido.value.id_cliente)

  // Create a new window with the appropriate print template
  const printWindow = window.open('', '_blank', 'width=800,height=600')

  let componentHtml = ''
  const pedidoData = selectedPedido.value
  const itensData = itens.value
  const clienteData = cliente

  if (type === 'pedido') {
    // Generate HTML for PedidoPrint
    componentHtml = generatePedidoPrintHtml(pedidoData, itensData, clienteData)
  } else if (type === 'etiquetas') {
    // Generate HTML for EtiquetaPrint
    componentHtml = generateEtiquetasPrintHtml(pedidoData, itensData, clienteData)
  } else if (type === 'romaneio') {
    // Generate HTML for RomaneioPrint
    componentHtml = generateRomaneioPrintHtml(pedidoData, itensData, clienteData)
  }

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Imprimir - ${type}</title>
      <style>
        body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
        .print-button { display: none; }
        @media print { .no-print { display: none !important; } }
      </style>
    </head>
    <body>
      ${componentHtml}
      <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
          Imprimir
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
          Fechar
        </button>
      </div>
    </body>
    </html>
  `)

  printWindow.document.close()
}

// Helper functions to generate HTML for each print type
function generatePedidoPrintHtml(pedido, itens, cliente) {
  // Simplified HTML generation - in a real app, you'd use the actual Vue components
  return `
    <div style="font-family: 'Courier New', monospace; font-size: 12px;">
      <h1 style="text-align: center; margin-bottom: 20px;">PEDIDO DE VENDA</h1>
      <p><strong>Pedido #${pedido.id_pedido}</strong></p>
      <p><strong>Cliente:</strong> ${cliente?.razao_social || 'N/A'}</p>
      <p><strong>Data:</strong> ${formatDate(pedido.created_at)}</p>
      <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
          <tr style="border-bottom: 1px solid #000;">
            <th style="text-align: left; padding: 5px;">Produto</th>
            <th style="text-align: left; padding: 5px;">Qtd</th>
            <th style="text-align: right; padding: 5px;">Valor</th>
          </tr>
        </thead>
        <tbody>
          ${itens.map(item => `
            <tr style="border-bottom: 1px solid #ddd;">
              <td style="padding: 5px;">${item.produto?.produto || item.produto}</td>
              <td style="padding: 5px;">${calcularQuantidadeTotal(item.quantidades)}</td>
              <td style="text-align: right; padding: 5px;">R$ ${formatMoney(item.total_item)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      <p style="text-align: right; margin-top: 20px; font-weight: bold;">
        Total: R$ ${formatMoney(itens.reduce((sum, item) => sum + parseFloat(item.total_item || 0), 0))}
      </p>
    </div>
  `
}

function generateEtiquetasPrintHtml(pedido, itens, cliente) {
  // Generate labels HTML
  let labelsHtml = ''
  itens.forEach(item => {
    const sizes = ['tam_pp', 'tam_p', 'tam_m', 'tam_g', 'tam_u', 'tam_rn']
    sizes.forEach(sizeKey => {
      const qty = item[sizeKey] || 0
      if (qty > 0) {
        for (let i = 0; i < qty; i++) {
          labelsHtml += `
            <div style="width: 70mm; height: 40mm; border: 1px solid #000; padding: 3mm; margin: 2mm; display: inline-block;">
              <div style="text-align: center; font-size: 8px; font-weight: bold;">Sistema Lizzie</div>
              <div style="text-align: center; font-size: 7px;">ETIQUETA DE PRODUTO</div>
              <div style="text-align: center; font-size: 9px; font-weight: bold; margin: 3px 0;">${item.produto?.produto || item.produto}</div>
              <div style="display: flex; justify-content: space-between; font-size: 8px;">
                <span>Tamanho: ${sizeKey.replace('tam_', '').toUpperCase()}</span>
                <span>R$ ${formatMoney(item.preco_unit || 0)}</span>
              </div>
              <div style="margin-top: 2px; font-size: 7px;">Pedido: ${pedido.id_pedido}</div>
            </div>
          `
        }
      }
    })
  })

  return `
    <div style="font-family: Arial, sans-serif;">
      <h2 style="text-align: center; margin-bottom: 20px;">Etiquetas do Pedido #${pedido.id_pedido}</h2>
      <div style="display: flex; flex-wrap: wrap;">
        ${labelsHtml}
      </div>
    </div>
  `
}

function generateRomaneioPrintHtml(pedido, itens, cliente) {
  return `
    <div style="font-family: Arial, sans-serif; font-size: 12px;">
      <h1 style="text-align: center; margin-bottom: 20px;">ROMANEIO DE ENTREGA</h1>
      <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
        <div>
          <p><strong>Pedido #${pedido.id_pedido}</strong></p>
          <p><strong>Data:</strong> ${formatDate(pedido.created_at)}</p>
        </div>
        <div>
          <p><strong>Cliente:</strong> ${cliente?.razao_social || 'N/A'}</p>
          <p><strong>Entrega:</strong> ${pedido.data_entrega ? formatDate(pedido.data_entrega) : 'A combinar'}</p>
        </div>
      </div>
      <table style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="border-bottom: 1px solid #000;">
            <th style="text-align: left; padding: 5px;">Produto</th>
            <th style="text-align: left; padding: 5px;">Referência</th>
            <th style="text-align: center; padding: 5px;">Qtd</th>
          </tr>
        </thead>
        <tbody>
          ${itens.map(item => `
            <tr style="border-bottom: 1px solid #ddd;">
              <td style="padding: 5px;">${item.produto?.produto || item.produto}</td>
              <td style="padding: 5px;">${item.produto?.referencia || item.referencia || '-'}</td>
              <td style="text-align: center; padding: 5px;">${calcularQuantidadeTotal(item.quantidades)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      <div style="margin-top: 30px;">
        <p><strong>Recebido por:</strong> _______________________________</p>
        <p><strong>Entregue por:</strong> _______________________________</p>
        <p><strong>Data:</strong> ___/___/_____</p>
      </div>
    </div>
  `
}

function handleExport() {
  exportPedidosToCSV(filteredPedidos.value, 'pedidos')
  message.success('Pedidos exportados com sucesso!')
}

function clearFilters() {
  statusFilter.value = null
  clientFilter.value = null
  regionFilter.value = null
  dateStartFilter.value = null
  dateEndFilter.value = null
  searchQuery.value = ''
  sortBy.value = 'created_at'
  sortOrder.value = 'desc'
}



function getStatusType(status) {
  const types = {
    pendente: 'warning',
    aprovado: 'info',
    concluido: 'success',
    cancelado: 'error'
  }
  return types[status] || 'default'
}

function getQuantityTotal(item) {
  return TAMANHOS.reduce((sum, t) => sum + (item[`tam_${t}`] || item[t] || 0), 0)
}

onMounted(async () => {
  await Promise.all([loadClientes(), loadProdutos()])
  await loadPedidos()
  loadTemplates()
})
</script>

<template>
  <div class="pedidos-view p-6">
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Pedidos</h1>
          <p class="text-sm text-gray-500 mt-1">Gerencie os pedidos da sua loja</p>
        </div>
        <div class="flex gap-3">
          <NButton @click="loadPedidos" quaternary>
            <template #icon><NIcon><RefreshOutline /></NIcon></template>
          </NButton>
          <NButton @click="handleExport" quaternary>
            <template #icon><NIcon><DownloadOutline /></NIcon></template>
            Exportar
          </NButton>
          <NButton type="primary" @click="openNew">
            <template #icon><NIcon><AddOutline /></NIcon></template>
            Novo Pedido
          </NButton>
        </div>
      </div>

      <NCard class="mb-6">
        <NGrid :cols="4" :x-gap="24" :y-gap="16">
          <NGridItem>
            <NStatistic label="Total de Pedidos" :value="stats.total" />
          </NGridItem>
          <NGridItem>
            <NStatistic label="Pendente" :value="stats.pendente" :value-style="{ color: '#ca8a04' }" />
          </NGridItem>
          <NGridItem>
            <NStatistic label="Concluídos" :value="stats.concluido" :value-style="{ color: '#16a34a' }" />
          </NGridItem>
          <NGridItem>
            <NStatistic label="Valor Total" :value="`R$ ${formatMoney(stats.valorTotal)}`" />
          </NGridItem>
        </NGrid>
      </NCard>

      <NCard class="mb-4">
        <div class="flex flex-wrap gap-4 items-end">
          <div class="flex-1 min-w-[200px]">
            <NInputGroup>
              <NInput v-model:value="searchQuery" placeholder="Buscar por cliente, ID, observações..." clearable>
                <template #prefix><NIcon><SearchOutline /></NIcon></template>
              </NInput>
            </NInputGroup>
          </div>
          <div class="w-[150px]">
            <NSelect v-model:value="statusFilter" :options="statusOptions" placeholder="Status" clearable />
          </div>
          <div class="w-[200px]">
            <NSelect v-model:value="clientFilter" :options="clienteOptions" placeholder="Cliente" clearable />
          </div>
          <div class="w-[120px]">
            <NSelect v-model:value="regionFilter" :options="regionOptions" placeholder="Região" clearable />
          </div>
          <NButton @click="clearFilters" quaternary>Limpar Filtros</NButton>
        </div>
      </NCard>

      <NCard>
        <NDataTable
          :columns="columns"
          :data="paginatedPedidos"
          :loading="loading"
          striped
        />
        <div class="flex justify-between items-center mt-4">
          <div class="text-sm text-gray-500">
            Mostrando {{ paginatedPedidos.length }} de {{ totalCount }} pedidos
          </div>
          <NPagination
            v-model:page="currentPage"
            :page-size="pageSize"
            :total="totalCount"
            show-size-picker
            :page-sizes="[10, 15, 20, 30, 50]"
            @update:page-size="pageSize = $event"
          />
        </div>
      </NCard>
    </div>

    <NDrawer v-model:show="showDetailsDrawer" :width="500" placement="right">
      <NDrawerContent title="Detalhes do Pedido" closable>
        <NScrollbar v-if="selectedPedido">
          <div class="space-y-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="font-semibold text-lg mb-3">Pedido #{{ selectedPedido.id_pedido }}</h3>
              <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <span class="text-gray-500">Status:</span>
                  <NTag :type="getStatusType(selectedPedido.status)" size="small" class="ml-2">
                    {{ selectedPedido.status }}
                  </NTag>
                </div>
                <div>
                  <span class="text-gray-500">Data:</span>
                  <span class="ml-2">{{ formatDate(selectedPedido.created_at) }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Região:</span>
                  <span class="ml-2">{{ selectedPedido.regiao === 'norde' ? 'Nordeste' : 'Norte' }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Total:</span>
                  <span class="ml-2 font-bold text-green-600">R$ {{ formatMoney(selectedPedido.total_pedido) }}</span>
                </div>
              </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="font-medium mb-3 flex items-center">
                <NIcon class="mr-2"><PersonOutline /></NIcon>
                Cliente
              </h4>
              <div class="space-y-2 text-sm">
                <div><span class="text-gray-500">Nome:</span> {{ selectedPedido.cliente?.razao_social }}</div>
                <div><span class="text-gray-500">Fantasia:</span> {{ selectedPedido.cliente?.nome_fantasia }}</div>
                <div><span class="text-gray-500">CNPJ/CPF:</span> {{ selectedPedido.cliente?.cnpj || selectedPedido.cliente?.cpf }}</div>
                <div><span class="text-gray-500">Cidade:</span> {{ selectedPedido.cliente?.cidade }} - {{ selectedPedido.cliente?.uf }}</div>
              </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="font-medium mb-3 flex items-center">
                <NIcon class="mr-2"><DocumentTextOutline /></NIcon>
                Itens do Pedido
              </h4>
              <div v-if="itens && itens.length > 0" class="space-y-3">
                <div v-for="(item, idx) in itens" :key="idx" class="bg-white rounded p-3 border">
                  <div class="font-medium">{{ item.produto?.produto || item.produto }}</div>
                  <div class="text-xs text-gray-500">Ref: {{ item.produto?.referencia || item.referencia }}</div>
                  <div class="mt-2 flex flex-wrap gap-1">
                    <span v-for="t in TAMANHOS" :key="t" class="text-xs bg-blue-100 px-2 py-1 rounded">
                      {{ t.toUpperCase() }}: {{ item[`tam_${t}`] || 0 }}
                    </span>
                  </div>
                  <div class="mt-2 text-right font-medium">
                    Total: {{ getQuantityTotal(item) }} | R$ {{ formatMoney(item.total_item) }}
                  </div>
                </div>
              </div>
              <NEmpty v-else description="Nenhum item" />
            </div>

            <div v-if="selectedPedido.obs_pedido || selectedPedido.obs_entrega" class="bg-gray-50 rounded-lg p-4">
              <h4 class="font-medium mb-3">Observações</h4>
              <div v-if="selectedPedido.obs_pedido" class="text-sm mb-2">
                <span class="text-gray-500">Pedido:</span> {{ selectedPedido.obs_pedido }}
              </div>
              <div v-if="selectedPedido.obs_entrega" class="text-sm">
                <span class="text-gray-500">Entrega:</span> {{ selectedPedido.obs_entrega }}
              </div>
            </div>
          </div>
        </NScrollbar>
        <template #footer>
          <div class="flex gap-2">
            <NDropdown
              :options="[
                {
                  label: 'Imprimir Pedido',
                  key: 'pedido',
                  icon: () => h(NIcon, null, () => h(DocumentTextOutline))
                },
                {
                  label: 'Imprimir Etiquetas',
                  key: 'etiquetas',
                  icon: () => h(NIcon, null, () => h(PrintOutline))
                },
                {
                  label: 'Imprimir Romaneio',
                  key: 'romaneio',
                  icon: () => h(NIcon, null, () => h(CarOutline))
                }
              ]"
              @select="handlePrint"
            >
              <NButton>
                <template #icon><NIcon><PrintOutline /></NIcon></template>
                Imprimir
                <template #suffix><NIcon><ChevronDownOutline /></NIcon></template>
              </NButton>
            </NDropdown>
            <NButton @click="editPedido(selectedPedido); showDetailsDrawer = false">
              <template #icon><NIcon><CreateOutline /></NIcon></template>
              Editar
            </NButton>
          </div>
        </template>
      </NDrawerContent>
    </NDrawer>

    <NModal v-model:show="showModal" preset="card" :style="{ width: '900px', maxHeight: '90vh' }" title="Novo Pedido" :mask-closable="false">
      <template #header>
        <div class="flex justify-between items-center w-full">
          <h3 class="text-lg font-semibold">Novo Pedido</h3>
          <div class="flex items-center gap-3">
            <div v-if="lastSaved" class="text-xs text-gray-500 flex items-center">
              <NIcon class="mr-1" size="14"><CheckmarkCircleOutline /></NIcon>
              Salvo automaticamente {{ formatDateTime(lastSaved) }}
            </div>
            <div class="flex gap-2">
              <NButton size="small" quaternary @click="showTemplateModal = true">
                <template #icon><NIcon><DocumentTextOutline /></NIcon></template>
                Templates
              </NButton>
              <NButton v-if="pedido.itens.length > 0" size="small" quaternary @click="showTemplateModal = true; templateName = ''">
                <template #icon><NIcon><CheckmarkCircleOutline /></NIcon></template>
                Salvar Template
              </NButton>
            </div>
          </div>
        </div>
      </template>
      <!-- Wizard Steps -->
      <NSteps :current="currentStep" status="process" class="mb-6">
        <NStep
          v-for="(step, index) in wizardSteps"
          :key="index"
          :title="step.title"
          :description="step.description"
          :status="currentStep > index ? 'finish' : currentStep === index ? 'process' : 'wait'"
        />
      </NSteps>

      <div class="max-h-[60vh] overflow-y-auto pr-2">
        <!-- Step 0: Cliente -->
        <div v-if="currentStep === 0" class="space-y-6">
          <div class="bg-blue-50 rounded-lg p-6">
            <h3 class="font-semibold text-lg text-gray-800 mb-4 flex items-center">
              <NIcon class="mr-2 text-blue-600"><PersonOutline /></NIcon>
              Informações do Cliente
            </h3>
            <NForm :model="form" :rules="{ id_cliente: { required: true, message: 'Cliente é obrigatório' } }">
              <div class="grid grid-cols-2 gap-6">
                <NFormItem label="Cliente" path="id_cliente" :show-feedback="false">
                  <NSelect
                    v-model:value="form.id_cliente"
                    :options="clienteOptions.filter(c => c.value)"
                    placeholder="Selecione um cliente"
                    clearable
                    filterable
                  />
                </NFormItem>
                <NFormItem label="Região" path="regiao">
                  <NSelect v-model:value="form.regiao" :options="regionOptions" />
                </NFormItem>
              </div>
            </NForm>
            <div v-if="!isClienteStepValid" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
              <p class="text-sm text-yellow-800">⚠️ Complete os campos obrigatórios para continuar</p>
            </div>
          </div>
        </div>

        <!-- Step 1: Produtos -->
        <div v-if="currentStep === 1" class="space-y-6">
          <div class="bg-green-50 rounded-lg p-6">
            <h3 class="font-semibold text-lg text-gray-800 mb-4 flex items-center">
              <NIcon class="mr-2 text-green-600"><DocumentTextOutline /></NIcon>
              Itens do Pedido
            </h3>

            <!-- Adicionar Item -->
            <div class="mb-6 p-4 bg-white rounded-lg border-2 border-dashed border-gray-300">
              <h4 class="font-medium mb-3">Adicionar Produto</h4>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Produto</label>
                  <NSelect
                    v-model:value="selectedProduto"
                    :options="enhancedProdutoOptions"
                    :loading="produtoSearchLoading"
                    placeholder="Digite para buscar produtos..."
                    filterable
                    clearable
                    remote
                    :on-search="query => produtoSearchQuery.value = query"
                    :render-option="({ node, option }) => {
                      if (option.group) {
                        return h('div', { class: 'flex items-center justify-between' }, [
                          h('span', option.label),
                          h('span', { class: 'text-xs text-gray-500' }, option.group)
                        ])
                      }
                      return node
                    }"
                  />
                </div>
                <div class="md:col-span-3">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Tamanhos</label>
                  <div class="grid grid-cols-7 gap-1 text-sm">
                    <div v-for="t in TAMANHOS" :key="t" class="text-center">
                      <label class="block text-xs text-gray-500 mb-1">{{ t.toUpperCase() }}</label>
                      <NInput
                        type="number"
                        min="0"
                        :value="itemQuantidades[t]"
                        @update:value="e => atualizarQuantidade(null, t, parseInt(e) || 0)"
                        class="text-center"
                        size="small"
                      />
                    </div>
                  </div>
                </div>
              </div>
              <NButton
                type="primary"
                @click="adicionarItem(selectedProduto)"
                :disabled="!selectedProduto"
                block
              >
                <template #icon><NIcon><AddOutline /></NIcon></template>
                Adicionar Item
              </NButton>
            </div>

            <!-- Lista de Itens -->
            <div v-if="pedido.itens.length > 0" class="space-y-3">
              <h4 class="font-medium">Itens Adicionados ({{ pedido.itens.length }})</h4>
              <div v-for="(item, idx) in pedido.itens" :key="idx" class="bg-white p-4 rounded-lg border">
                <div class="flex justify-between items-start mb-3">
                  <div>
                    <h5 class="font-medium text-gray-800">{{ item.produto.produto }}</h5>
                    <p class="text-sm text-gray-600">Ref: {{ item.produto.referencia }}</p>
                  </div>
                  <NButton size="small" quaternary type="error" @click="removerItem(idx)">
                    <template #icon><NIcon><TrashOutline /></NIcon></template>
                  </NButton>
                </div>

                <div class="grid grid-cols-7 gap-1 text-sm mb-3">
                  <div v-for="t in TAMANHOS" :key="t" class="text-center">
                    <label class="block text-xs text-gray-500 mb-1">{{ t.toUpperCase() }}</label>
                    <NInput
                      type="number"
                      min="0"
                      :value="item.quantidades[t]"
                      @update:value="e => atualizarQuantidade(idx, t, parseInt(e) || 0)"
                      class="text-center"
                      size="small"
                    />
                  </div>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Quantidade total: {{ calcularQuantidadeTotal(item.quantidades) }}</span>
                  <span class="font-semibold text-green-600">R$ {{ getItemSubtotal(item).toFixed(2) }}</span>
                </div>
              </div>
            </div>

            <NEmpty v-else description="Nenhum item adicionado" class="py-8" />

            <div v-if="!isProdutosStepValid" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
              <p class="text-sm text-yellow-800">⚠️ Adicione pelo menos um item para continuar</p>
            </div>
          </div>
        </div>

        <!-- Step 2: Detalhes (Optional) -->
        <div v-if="currentStep === 2" class="space-y-6">
          <div class="bg-purple-50 rounded-lg p-6">
            <h3 class="font-semibold text-lg text-gray-800 mb-4 flex items-center">
              <NIcon class="mr-2 text-purple-600"><DocumentTextOutline /></NIcon>
              Detalhes Adicionais
            </h3>

            <div class="space-y-4">
              <NFormItem label="Observações do Pedido">
                <NInput
                  v-model:value="form.obs_pedido"
                  type="textarea"
                  placeholder="Observações específicas do pedido..."
                  :autosize="{ minRows: 3, maxRows: 5 }"
                />
              </NFormItem>

              <NFormItem label="Observações de Entrega">
                <NInput
                  v-model:value="form.obs_entrega"
                  type="textarea"
                  placeholder="Instruções especiais para entrega..."
                  :autosize="{ minRows: 3, maxRows: 5 }"
                />
              </NFormItem>

              <NFormItem label="Forma de Pagamento">
                <NSelect
                  v-model:value="form.forma_pag"
                  :options="[
                    { label: 'Dinheiro', value: 'dinheiro' },
                    { label: 'Cartão de Crédito', value: 'cartao_credito' },
                    { label: 'Cartão de Débito', value: 'cartao_debito' },
                    { label: 'PIX', value: 'pix' },
                    { label: 'Boleto', value: 'boleto' },
                    { label: 'Cheque', value: 'cheque' }
                  ]"
                  placeholder="Selecione a forma de pagamento"
                  clearable
                />
              </NFormItem>
            </div>
          </div>
        </div>

        <!-- Step 3: Revisão -->
        <div v-if="currentStep === 3" class="space-y-6">
          <div class="bg-gray-50 rounded-lg p-6">
            <h3 class="font-semibold text-lg text-gray-800 mb-4 flex items-center">
              <NIcon class="mr-2 text-gray-600"><CheckmarkCircleOutline /></NIcon>
              Revisão do Pedido
            </h3>

            <!-- Resumo do Cliente -->
            <div class="mb-6 p-4 bg-white rounded-lg">
              <h4 class="font-medium mb-3 text-gray-700">Cliente</h4>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Nome:</span> {{ clientes.find(c => c.id_cliente == form.id_cliente)?.razao_social }}</div>
                <div><span class="text-gray-500">Região:</span> {{ form.regiao === 'norde' ? 'Nordeste' : 'Norte' }}</div>
              </div>
            </div>

            <!-- Resumo dos Itens -->
            <div class="mb-6 p-4 bg-white rounded-lg">
              <h4 class="font-medium mb-3 text-gray-700">Itens ({{ pedido.itens.length }})</h4>
              <div class="space-y-2">
                <div v-for="(item, idx) in pedido.itens" :key="idx" class="flex justify-between text-sm py-2 border-b border-gray-100">
                  <span>{{ item.produto.produto }}</span>
                  <span class="font-medium">{{ calcularQuantidadeTotal(item.quantidades) }} un | R$ {{ getItemSubtotal(item).toFixed(2) }}</span>
                </div>
              </div>
            </div>

            <!-- Resumo Financeiro -->
            <div class="p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
              <h4 class="font-medium mb-3 text-gray-700">Resumo Financeiro</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span>Quantidade Total:</span>
                  <span class="font-medium">{{ resumo.quantidadeTotal }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Subtotal:</span>
                  <span>R$ {{ resumo.subtotalItens.toFixed(2) }}</span>
                </div>
                <div v-if="resumo.descontosItens > 0" class="flex justify-between text-red-600">
                  <span>Descontos Itens:</span>
                  <span>- R$ {{ resumo.descontosItens.toFixed(2) }}</span>
                </div>
                <div v-if="form.desconto_percentual > 0" class="flex justify-between text-red-600">
                  <span>Desconto Pedido ({{ form.desconto_percentual }}%):</span>
                  <span>- R$ {{ (resumo.subtotalItens * form.desconto_percentual / 100).toFixed(2) }}</span>
                </div>
                <div v-if="form.desconto_valor > 0" class="flex justify-between text-red-600">
                  <span>Desconto Valor:</span>
                  <span>- R$ {{ form.desconto_valor.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold text-blue-600 border-t border-blue-300 pt-2">
                  <span>Total:</span>
                  <span>R$ {{ resumo.total.toFixed(2) }}</span>
                </div>
              </div>
            </div>

            <div v-if="!isRevisaoStepValid" class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
              <p class="text-sm text-red-800">❌ Dados incompletos. Verifique os passos anteriores.</p>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-between items-center">
          <div class="flex gap-2">
            <NButton
              @click="prevStep()"
              :disabled="currentStep === 0"
              quaternary
            >
              Anterior
            </NButton>
          </div>

          <div class="flex gap-2">
            <NButton @click="showModal = false; resetWizard()" quaternary>
              Cancelar
            </NButton>

            <NButton
              v-if="currentStep < wizardSteps.length - 1"
              type="primary"
              @click="nextStep()"
              :disabled="!stepValidation[currentStep]"
            >
              Próximo
            </NButton>

            <NButton
              v-if="currentStep === wizardSteps.length - 1"
              type="primary"
              @click="savePedido()"
              :loading="saving"
              :disabled="!isRevisaoStepValid"
            >
              {{ saving ? 'Criando...' : 'Criar Pedido' }}
            </NButton>
          </div>
        </div>
      </template>
    </NModal>

    <!-- Template Modal -->
    <NModal v-model:show="showTemplateModal" preset="card" title="Templates de Pedido" :style="{ width: '600px' }">
      <div class="space-y-4">
        <!-- Save Template Section -->
        <div v-if="!templateName" class="p-4 bg-blue-50 rounded-lg">
          <h4 class="font-medium mb-2">Salvar Template</h4>
          <div class="flex gap-2">
            <NInput
              v-model:value="templateName"
              placeholder="Nome do template..."
              clearable
            />
            <NButton
              type="primary"
              @click="saveTemplate(templateName)"
              :disabled="!templateName.trim() || pedido.itens.length === 0"
            >
              Salvar
            </NButton>
          </div>
          <p v-if="pedido.itens.length === 0" class="text-sm text-red-600 mt-2">
            Adicione itens ao pedido antes de salvar como template
          </p>
        </div>

        <!-- Load Templates Section -->
        <div>
          <h4 class="font-medium mb-3">Templates Salvos</h4>
          <div v-if="templates.length === 0" class="text-center py-8 text-gray-500">
            <NIcon size="48" class="mb-2"><DocumentTextOutline /></NIcon>
            <p>Nenhum template salvo ainda</p>
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="template in templates"
              :key="template.id"
              class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50"
            >
              <div class="flex-1">
                <div class="font-medium">{{ template.name }}</div>
                <div class="text-sm text-gray-500">
                  {{ template.pedido.itens?.length || 0 }} itens •
                  Criado em {{ formatDate(template.createdAt) }}
                </div>
              </div>
              <div class="flex gap-2">
                <NButton size="small" type="primary" @click="loadTemplate(template.id)">
                  Carregar
                </NButton>
                <NButton size="small" quaternary type="error" @click="deleteTemplate(template.id)">
                  <template #icon><NIcon><TrashOutline /></NIcon></template>
                </NButton>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end">
          <NButton @click="showTemplateModal = false; templateName = ''">Fechar</NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>

<style scoped>
.pedidos-view {
  background-color: #f8fafc;
  min-height: 100vh;
}

:deep(.n-card) {
  border-radius: 8px;
}

:deep(.n-data-table) {
  font-size: 14px;
}
</style>