<script setup>
import { ref, onMounted, computed, reactive, h, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../composables/useApi'
import { NCard, NDataTable, NButton, NIcon, NDatePicker, NSelect, NSpin, NSpace, NTabs, NTabPane, useMessage, NDropdown, NSkeleton, NEmpty, NStatistic, NGrid, NGi, NTag, NProgress, NModal, NButtonGroup } from 'naive-ui'
import { DownloadOutline, RefreshOutline, FileTrayOutline, AlertCircleOutline, TrendingUpOutline, TrendingDownOutline, PeopleOutline, CartOutline, CashOutline, BagHandleOutline } from '@vicons/ionicons5'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const router = useRouter()
const authStore = useAuthStore()
const message = useMessage()
const loading = ref(true)
const loadingEstatisticas = ref(true)
const activeTab = ref('dashboard')

// Data refs
const estatisticas = ref(null)
const insights = ref(null)
const dataVendas = ref([])
const dataVendedores = ref([])
const dataProdutos = ref([])
const dataClientes = ref([])
const clienteDetalhes = ref(null)
const showClienteModal = ref(false)

// Filters
const range = ref(null)
const vendedorId = ref(null)
const datePresetSelection = ref('30d')
const selectedVendedor = ref(null)
const crosstabData = ref([])
const loadingCrosstab = ref(false)
const showCrosstab = ref(false)
const crosstabMode = ref('produtos') // 'produtos' or 'vendas'

// Status filter
const statusFilter = ref([4]) // Default: apenas concluídos
const statusOptions = [
  { label: 'Em Aberto', value: 1 },
  { label: 'Pendente', value: 2 },
  { label: 'Cancelado', value: 3 },
  { label: 'Concluído', value: 4 }
]

// Chart view mode
const chartViewMode = ref('real') // 'real' ou 'projetada'
const chartViewOptions = [
  { label: 'Faturamento Real', value: 'real' },
  { label: 'Receita Projetada', value: 'projetada' }
]

// Pagination config
const paginationConfig = reactive({
  pageSize: 10,
  showSizePicker: true,
  pageSizes: [5, 10, 20, 50],
  showQuickJumper: true,
  prefix: ({ itemCount }) => `Total: ${itemCount}`
})

// Control flag to prevent duplicate loadData calls
const isLoadingData = ref(false)
const crosstabTitle = ref('')

// Chart configs
const chartOptionsLine = reactive({
  chart: { type: 'area', toolbar: { show: false }, fontFamily: 'Nunito, sans-serif', zoom: { enabled: false } },
  colors: ['#6366F1'],
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'vertical',
      shadeIntensity: 0.4,
      gradientToColors: ['#6366F1'],
      inverseColors: false,
      opacityFrom: 0.7,
      opacityTo: 0.1,
      stops: [0, 100]
    }
  },
  markers: { size: 0 },
  xaxis: { labels: { style: { fontSize: '11px' } }, tooltip: { enabled: false } },
  yaxis: { labels: { style: { fontSize: '11px' }, formatter: (val) => formatCompact(val) } },
  grid: { borderColor: '#f1f5f9' },
  tooltip: { y: { formatter: (val) => formatMoney(val) } },
  dataLabels: { enabled: false }
})

const chartOptionsBar = reactive({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Nunito, sans-serif' },
  colors: ['#6366F1', '#10B981', '#F59E0B'],
  plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%' } },
  dataLabels: { enabled: true, style: { fontSize: '11px' } },
  xaxis: { labels: { style: { fontSize: '11px' }, formatter: (val) => formatCompact(val) } },
  yaxis: { labels: { style: { fontSize: '11px' } } },
  grid: { borderColor: '#f1f5f9' },
  tooltip: { y: { formatter: (val) => formatMoney(val) } }
})

const colors = ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899']

// Date presets
const datePresets = [
  { label: 'Hoje', value: 'today' },
  { label: 'Últimos 7 dias', value: '7d' },
  { label: 'Últimos 15 dias', value: '15d' },
  { label: 'Últimos 30 dias', value: '30d' },
  { label: 'Últimos 60 dias', value: '60d' },
  { label: 'Últimos 90 dias', value: '90d' },
  { label: 'Este mês', value: 'month' },
  { label: 'Mês passado', value: 'lastMonth' },
  { label: 'Últimos 6 meses', value: '6months' },
  { label: 'Este ano', value: 'year' },
  { label: 'Ano passado', value: 'lastYear' }
]

function getDateRange(preset) {
  const today = new Date()
  const todayStr = today.toISOString().split('T')[0]
  
  switch (preset) {
    case 'today':
      return [todayStr, todayStr]
    case '7d':
      const d7 = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
      return [d7.toISOString().split('T')[0], todayStr]
    case '15d':
      const d15 = new Date(today.getTime() - 15 * 24 * 60 * 60 * 1000)
      return [d15.toISOString().split('T')[0], todayStr]
    case '30d':
      const d30 = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
      return [d30.toISOString().split('T')[0], todayStr]
    case '60d':
      const d60 = new Date(today.getTime() - 60 * 24 * 60 * 60 * 1000)
      return [d60.toISOString().split('T')[0], todayStr]
    case '90d':
      const d90 = new Date(today.getTime() - 90 * 24 * 60 * 60 * 1000)
      return [d90.toISOString().split('T')[0], todayStr]
    case 'month':
      const first = new Date(today.getFullYear(), today.getMonth(), 1)
      return [first.toISOString().split('T')[0], todayStr]
    case 'lastMonth':
      const firstLast = new Date(today.getFullYear(), today.getMonth() - 1, 1)
      const lastLast = new Date(today.getFullYear(), today.getMonth(), 0)
      return [firstLast.toISOString().split('T')[0], lastLast.toISOString().split('T')[0]]
    case '6months':
      const d6m = new Date(today.getFullYear(), today.getMonth() - 6, 1)
      return [d6m.toISOString().split('T')[0], todayStr]
    case 'year':
      const firstYear = new Date(today.getFullYear(), 0, 1)
      return [firstYear.toISOString().split('T')[0], todayStr]
    case 'lastYear':
      const firstLastYear = new Date(today.getFullYear() - 1, 0, 1)
      const lastLastYear = new Date(today.getFullYear() - 1, 11, 31)
      return [firstLastYear.toISOString().split('T')[0], lastLastYear.toISOString().split('T')[0]]
    default:
      return [new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0], todayStr]
  }
}

function detectPresetFromRange(currentRange) {
  if (!currentRange || !currentRange[0] || !currentRange[1]) {
    return null
  }

  const [start, end] = currentRange

  // Try exact match first
  for (const preset of datePresets) {
    const presetRange = getDateRange(preset.value)
    if (presetRange[0] === start && presetRange[1] === end) {
      return preset.value
    }
  }

  // If no exact match, try to find the closest preset (for cases where dates are very close)
  // This helps with timezone issues or slight date differences
  const startDate = new Date(start)
  const endDate = new Date(end)

  for (const preset of datePresets) {
    const presetRange = getDateRange(preset.value)
    const presetStart = new Date(presetRange[0])
    const presetEnd = new Date(presetRange[1])

    // Check if dates are within 1 day difference (handles timezone issues)
    const startDiff = Math.abs((startDate - presetStart) / (1000 * 60 * 60 * 24))
    const endDiff = Math.abs((endDate - presetEnd) / (1000 * 60 * 60 * 24))

    if (startDiff <= 1 && endDiff <= 1) {
      return preset.value
    }
  }

  return null
}

function onDatePresetChange(preset) {
  datePresetSelection.value = preset
  const newRange = getDateRange(preset)

  // Update range only if it's different to avoid triggering watcher unnecessarily
  if (!range.value || range.value[0] !== newRange[0] || range.value[1] !== newRange[1]) {
    range.value = newRange
  } else {
    // If range is the same, just load data
    loadData()
  }
}

function clearFilters() {
  // Reset all filters to default values
  statusFilter.value = [4] // Default: apenas concluídos
  range.value = getDateRange('30d') // Default: últimos 30 dias
  datePresetSelection.value = '30d'
  chartViewMode.value = 'real' // Default: faturamento real

  // Reload data with default filters
  loadData()
}

function getParams() {
  const dateRange = range.value || getDateRange('30d')
  const params = {}

  if (dateRange[0]) params.data_inicio = dateRange[0]
  if (dateRange[1]) params.data_fim = dateRange[1]

  if (vendedorId.value) params.id_vendedor = vendedorId.value
  if (statusFilter.value && statusFilter.value.length > 0) {
    params.status = statusFilter.value
  }
  return params
}

// Chart data
const vendasTrendChart = computed(() => {
  if (!estatisticas.value?.vendas_diarias?.length) return null
  let data = estatisticas.value.vendas_diarias

  // Para períodos muito grandes, reduzir pontos para melhorar performance e legibilidade
  if (data.length > 90) {
    // Manter pontos semanais ou mensais dependendo do período
    const daysDiff = Math.ceil((new Date(data[data.length - 1].data) - new Date(data[0].data)) / (1000 * 60 * 60 * 24))
    let step = daysDiff > 365 ? 7 : 1 // Semanal para períodos > 1 ano, diário para menores

    const filteredData = []
    for (let i = 0; i < data.length; i += step) {
      filteredData.push(data[i])
    }
    data = filteredData
  }

  // Determinar nome e cor da série baseado no modo
  const isRealMode = chartViewMode.value === 'real'
  const seriesName = isRealMode ? 'Faturamento Real' : 'Receita Projetada'
  const seriesColor = isRealMode ? '#10B981' : '#3B82F6'

  return {
    series: [{
      name: seriesName,
      data: data.map(d => parseFloat(d.total) || 0)
    }],
    options: {
      ...chartOptionsLine,
      colors: [seriesColor],
      fill: {
        ...chartOptionsLine.fill,
        gradient: {
          ...chartOptionsLine.fill.gradient,
          gradientToColors: [seriesColor]
        }
      },
      xaxis: {
        categories: data.map(d => {
          const date = new Date(d.data)
          const daysDiff = Math.ceil((new Date(data[data.length - 1].data) - new Date(data[0].data)) / (1000 * 60 * 60 * 24))

          // Formatar labels baseado no período
          if (daysDiff > 365) {
            return date.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' })
          } else if (daysDiff > 90) {
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
          } else {
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
          }
        })
      }
    }
  }
})

const topVendedoresChart = computed(() => {
  if (!estatisticas.value?.top_vendedores?.length) return null
  const data = estatisticas.value.top_vendedores
  return {
    series: [{ data: data.map(v => parseFloat(v.total) || 0) }],
    options: {
      ...chartOptionsBar,
      xaxis: { categories: data.map(v => v.nome_vendedor), labels: { style: { fontSize: '11px' } } },
      plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%' } }
    }
  }
})

const topProdutosChart = computed(() => {
  if (!insights.value?.top_produtos?.length) return null
  const data = insights.value.top_produtos
  return {
    series: [{ data: data.map(p => parseFloat(p.total_vendido) || 0) }],
    options: {
      ...chartOptionsBar,
      xaxis: { categories: data.map(p => p.produto.substring(0, 15) + (p.produto.length > 15 ? '...' : '')), labels: { style: { fontSize: '10px' } } },
      plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%' } }
    }
  }
})

const dailyAverage = computed(() => {
  if (!range.value || !estatisticas.value?.periodo_atual?.total_vendas) return 0
  const start = new Date(range.value[0])
  const end = new Date(range.value[1])
  const days = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)))
  return estatisticas.value.periodo_atual.total_vendas / days
})

// Table columns
const columnsVendas = [
  { title: 'ID', key: 'id_pedido', width: 70 },
  { title: 'Data', key: 'data_pedido', render: row => new Date(row.data_pedido).toLocaleDateString('pt-BR'), width: 100 },
  { title: 'Cliente', key: 'razao_social' },
  { title: 'Vendedor', key: 'nome_vendedor', width: 120 },
  { title: 'Total', key: 'total_pedido', render: row => formatMoney(row.total_pedido), width: 120 },
  { title: 'Status', key: 'status', render: row => statusLabel(row.status), width: 100 }
]

const columnsVendedores = [
  { title: 'Vendedor', key: 'nome_vendedor' },
  { title: 'Pedidos', key: 'quantidade_pedidos', width: 100 },
  { title: 'Total Vendas', key: 'total_vendas', render: row => formatMoney(row.total_vendas), width: 140 },
  { title: 'Média/Pedido', key: 'media_pedido', render: row => formatMoney(row.media_pedido), width: 120 },
  { 
    title: 'Ação', 
    key: 'acao', 
    width: 140, 
    render: row => h(NButtonGroup, {}, () => [
      h(NButton, { size: 'small', type: 'info', onClick: () => loadVendedorVendas(row.id_vendedor, row.nome_vendedor) }, () => 'Pedidos'),
      h(NButton, { size: 'small', onClick: () => loadVendedorProdutos(row.id_vendedor, row.nome_vendedor) }, () => 'Produtos')
    ])
  }
]

const columnsProdutos = [
  {
    title: 'Produto',
    key: 'produto',
    render: row => h('div', {}, [
      h('div', { style: 'font-weight: 500; color: #1e293b;' }, row.produto),
      row.referencia ? h('div', { style: 'font-size: 12px; color: #64748b;' }, `Ref: ${row.referencia}`) : null
    ])
  },
  {
    title: 'Quantidade',
    key: 'quantidade',
    width: 100,
    render: row => h('span', { style: 'font-weight: 600; color: #10b981;' }, row.quantidade || 0)
  },
  {
    title: 'Valor Total',
    key: 'total_vendido',
    width: 120,
    render: row => h('span', { style: 'font-weight: 600; color: #6366f1;' }, formatMoney(row.total_vendido))
  },
  {
    title: 'Valor Médio',
    key: 'valor_medio',
    width: 100,
    render: row => {
      const media = row.quantidade > 0 ? row.total_vendido / row.quantidade : 0
      return h('span', { style: 'font-size: 14px; color: #f59e0b;' }, formatMoney(media))
    }
  }
]

const columnsVendasVendedor = [
  { title: 'ID', key: 'id_pedido', width: 70 },
  { title: 'Data', key: 'data_pedido', render: row => new Date(row.data_pedido).toLocaleDateString('pt-BR'), width: 100 },
  { title: 'Cliente', key: 'razao_social' },
  { title: 'Total', key: 'total_pedido', render: row => formatMoney(row.total_pedido), width: 120 },
  { title: 'Status', key: 'status', render: row => statusLabel(row.status), width: 100 }
]

const columnsClientes = [
  { title: 'Cliente', key: 'razao_social' },
  { title: 'CNPJ', key: 'cnpj', width: 140 },
  { title: 'Pedidos', key: 'quantidade_pedidos', width: 90 },
  { title: 'Total Compras', key: 'total_compras', render: row => formatMoney(row.total_compras), width: 130 },
  { title: 'Ticket Médio', key: 'ticket_medio', render: row => formatMoney(row.ticket_medio), width: 110 },
  { title: 'Última Compra', key: 'ultima_compra', render: row => row.ultima_compra ? new Date(row.ultima_compra).toLocaleDateString('pt-BR') : '-', width: 110 },
  { 
    title: 'Ação', 
    key: 'acao', 
    width: 80, 
    render: row => h(NButton, { size: 'small', type: 'info', onClick: () => loadClienteDetalhes(row.id_cliente, row.razao_social) }, () => 'Ver')
  }
]

const columnsPedidosCliente = [
  { title: 'ID', key: 'id_pedido', width: 70 },
  { title: 'Data', key: 'data_pedido', render: row => new Date(row.data_pedido).toLocaleDateString('pt-BR'), width: 100 },
  { title: 'Vendedor', key: 'nome_vendedor', width: 120 },
  { title: 'Itens', key: 'quantidade_itens', width: 70 },
  { title: 'Total', key: 'total_pedido', render: row => formatMoney(row.total_pedido), width: 120 },
  { title: 'Status', key: 'status', render: row => statusLabel(row.status), width: 100 }
]

// Computed
const vendorOptions = computed(() => {
  return dataVendedores.value.map(v => ({ label: v.nome_vendedor, value: v.id_vendedor }))
})

// Vendor filter options (carregar todos os vendedores ativos)
const allVendorOptions = computed(() => {
  const options = [{ label: 'Todos', value: null }]
  if (dataVendedores.value?.length) {
    dataVendedores.value.forEach(v => {
      options.push({ label: v.nome_vendedor, value: v.id_vendedor })
    })
  }
  return options
})

function statusLabel(status) {
  const labels = { 1: 'Aberto', 2: 'Pendente', 3: 'Cancelado', 4: 'Concluído' }
  return labels[status] || status
}

function formatMoney(val) {
  if (!val) return 'R$ 0,00'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val)
}

function formatDateRange(startDate, endDate) {
  if (!startDate || !endDate) return '-'

  const start = new Date(startDate)
  const end = new Date(endDate)

  const formatDate = (date) => {
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  }

  return `${formatDate(start)} até ${formatDate(end)}`
}

function formatCompact(val) {
  if (!val) return '0'
  const num = parseFloat(val)
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

function getCrescimentoColor(val) {
  if (val > 0) return 'success'
  if (val < 0) return 'error'
  return 'default'
}

async function loadVendedorVendas(vendedorId, nome) {
  selectedVendedor.value = { id: vendedorId, nome }
  crosstabTitle.value = `Pedidos de ${nome}`
  crosstabMode.value = 'vendas'
  showCrosstab.value = true
  loadingCrosstab.value = true

  crosstabData.value = []

  try {
    const params = getParams()
    params.id_vendedor = vendedorId

    const vendas = await api.getRelatorioVendas(params)
    const vendasData = vendas.data?.data || []

    await new Promise(resolve => setTimeout(resolve, 50))

    crosstabData.value = vendasData
  } catch (error) {
    console.error('Erro:', error)
    message.error('Erro ao carregar pedidos do vendedor')
  } finally {
    loadingCrosstab.value = false
  }
}

async function loadVendedorProdutos(vendedorId, nome) {
  selectedVendedor.value = { id: vendedorId, nome }
  crosstabTitle.value = `Produtos Vendidos por ${nome}`
  crosstabMode.value = 'produtos'
  showCrosstab.value = true
  loadingCrosstab.value = true

  crosstabData.value = []

  try {
    const params = getParams()
    params.id_vendedor = vendedorId

    const produtos = await api.getRelatorioProdutos(params)
    const produtosData = produtos.data?.data || []

    await new Promise(resolve => setTimeout(resolve, 50))

    crosstabData.value = produtosData
  } catch (error) {
    console.error('Erro:', error)
    message.error('Erro ao carregar produtos do vendedor')
  } finally {
    loadingCrosstab.value = false
  }
}

function closeCrosstab() {
  showCrosstab.value = false
  selectedVendedor.value = null
  crosstabData.value = []
}

async function loadClienteDetalhes(clienteId, nome) {
  selectedVendedor.value = { id: clienteId, nome }
  showClienteModal.value = true
  
  try {
    const params = getParams()
    const response = await api.getClienteDetalhes(clienteId, params)
    clienteDetalhes.value = response.data.data
  } catch (error) {
    console.error('Erro:', error)
    message.error('Erro ao carregar detalhes do cliente')
  }
}

function closeClienteModal() {
  showClienteModal.value = false
  clienteDetalhes.value = null
  selectedVendedor.value = null
}

onMounted(async () => {
  if (!authStore.isAuthenticated || !authStore.isAdmin) {
    router.push('/')
    return
  }
  range.value = getDateRange('30d')
  await loadData()
})

watch(range, (newVal, oldVal) => {
  // Only trigger if range actually changed and is valid
  if (newVal && newVal[0] && newVal[1] &&
      (!oldVal || oldVal[0] !== newVal[0] || oldVal[1] !== newVal[1])) {
    const detectedPreset = detectPresetFromRange(newVal)

    // Only update preset selection if it's different
    if (datePresetSelection.value !== detectedPreset) {
      datePresetSelection.value = detectedPreset
    }

    loadData()
  }
})

async function loadData() {
  // Prevent duplicate simultaneous calls
  if (isLoadingData.value) {
    return
  }

  isLoadingData.value = true
  loading.value = true
  loadingEstatisticas.value = true

  try {
    const params = getParams()

    const [estat, insight, vendas, vendedores, produtos, clientes] = await Promise.all([
      api.getRelatorioEstatisticas(params),
      api.getRelatorioInsights(params),
      api.getRelatorioVendas(params),
      api.getRelatorioVendedores(params),
      api.getRelatorioProdutos(params),
      api.getRelatorioClientes(params)
    ])

    estatisticas.value = estat.data?.data || null
    insights.value = insight.data?.data || null
    dataVendas.value = vendas.data?.data || []
    dataVendedores.value = vendedores.data?.data || []
    dataProdutos.value = produtos.data?.data || []
    dataClientes.value = clientes.data?.data || []
  } catch (error) {
    console.error('Erro:', error)
    message.error('Erro ao carregar relatórios')
  } finally {
    loading.value = false
    loadingEstatisticas.value = false
    isLoadingData.value = false
  }
}

function exportCSV(data, filename) {
  if (!data.length) return
  const headers = Object.keys(data[0])
  const rows = data.map(row => headers.map(h => row[h]).join(';'))
  const csv = [headers.join(';'), ...rows].join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${filename}_${new Date().toISOString().split('T')[0]}.csv`
  a.click()
}

function exportPDF(data, filename, title) {
  if (!data.length) {
    message.warning('Sem dados para exportar')
    return
  }
  const doc = new jsPDF()
  doc.setFontSize(14)
  doc.text(title || 'Relatório', 14, 20)
  doc.setFontSize(10)
  doc.text(`Gerado em: ${new Date().toLocaleDateString('pt-BR')}`, 14, 28)
  
  const headers = [Object.keys(data[0])]
  const rows = data.map(row => headers[0].map(h => row[h]))
  
  autoTable(doc, {
    head: headers,
    body: rows,
    startY: 35,
    styles: { fontSize: 8 },
    headStyles: { fillColor: [59, 130, 246] }
  })
  
  doc.save(`${filename}_${new Date().toISOString().split('T')[0]}.pdf`)
}

function exportFullReport() {
  if (!estatisticas.value || !insights.value) return
  
  const doc = new jsPDF()
  
  // Title
  doc.setFontSize(18)
  doc.text('Relatório Completo de Vendas', 14, 20)
  doc.setFontSize(10)
  doc.text(`Período: ${range.value?.[0]} até ${range.value?.[1]}`, 14, 28)
  doc.text(`Gerado em: ${new Date().toLocaleDateString('pt-BR')}`, 14, 34)
  
  // KPI Section
  doc.setFontSize(14)
  doc.text('Resumo de Desempenho', 14, 45)
  doc.setFontSize(10)
  
  const current = estatisticas.value.periodo_atual
  const growth = estatisticas.value.crescimento
  
  doc.text(`Total de Vendas: ${formatMoney(current.total_vendas)} (${growth.vendas > 0 ? '+' : ''}${growth.vendas}%)`, 14, 55)
  doc.text(`Quantidade de Pedidos: ${current.quantidade_pedidos} (${growth.pedidos > 0 ? '+' : ''}${growth.pedidos}%)`, 14, 62)
  doc.text(`Ticket Médio: ${formatMoney(current.ticket_medio)} (${growth.ticket_medio > 0 ? '+' : ''}${growth.ticket_medio}%)`, 14, 69)
  
  // Top Vendedores
  doc.setFontSize(14)
  doc.text('Top Vendedores', 14, 82)
  
  if (estatisticas.value.top_vendedores?.length) {
    const vendorData = estatisticas.value.top_vendedores.map(v => [v.nome_vendedor, v.quantidade, formatMoney(v.total)])
    autoTable(doc, {
      head: [['Vendedor', 'Pedidos', 'Total']],
      body: vendorData,
      startY: 87,
      styles: { fontSize: 9 }
    })
  }
  
  // Top Produtos
  const finalY = doc.lastAutoTable?.finalY || 120
  doc.setFontSize(14)
  doc.text('Top Produtos', 14, finalY + 10)
  
  if (insights.value.top_produtos?.length) {
    const productData = insights.value.top_produtos.map(p => [p.produto, p.quantidade_vendida, formatMoney(p.total_vendido)])
    autoTable(doc, {
      head: [['Produto', 'Qtd Vendida', 'Total']],
      body: productData,
      startY: finalY + 15,
      styles: { fontSize: 9 }
    })
  }
  
  doc.save(`relatorio_completo_${new Date().toISOString().split('T')[0]}.pdf`)
  message.success('Relatório exportado com sucesso!')
}

const exportOptions = computed(() => [
  { label: 'CSV - Vendas', key: 'csv-vendas' },
  { label: 'PDF - Vendas', key: 'pdf-vendas' },
  { label: 'PDF Completo', key: 'pdf-full' }
])

async function handleExport(key) {
  if (key === 'csv-vendas') {
    exportCSV(dataVendas.value, 'vendas')
  } else if (key === 'pdf-vendas') {
    exportPDF(dataVendas.value, 'vendas', 'Relatório de Vendas')
  } else if (key === 'pdf-full') {
    exportFullReport()
  }
}
</script>

<template>
<div class="relatorios-container">
    <!-- Header -->
    <div class="page-header">
      <div class="header-top">
        <div class="header-titles">
          <h1 class="page-title">Relatórios</h1>
          <p class="page-subtitle">Análise completa de vendas e desempenho</p>
        </div>
      </div>
      <div class="filters-row">
        <NSelect
          :value="datePresetSelection"
          :options="datePresets"
          placeholder="Período"
          class="filter-select"
          @update:value="onDatePresetChange"
        />
        <NDatePicker v-model:value="range" type="daterange" clearable class="filter-date" />
        <NSelect
          v-model:value="statusFilter"
          :options="statusOptions"
          placeholder="Status"
          multiple
          class="filter-select"
          clearable
          @update:value="loadData"
        />
        <NSelect
          v-model:value="chartViewMode"
          :options="chartViewOptions"
          placeholder="Visualização"
          class="filter-select"
        />
        <NButton @click="loadData" class="filter-btn">
          <template #icon><NIcon><RefreshOutline /></NIcon></template>
          Filtrar
        </NButton>
        <NButton @click="clearFilters" type="default" class="filter-btn">
          <template #icon><NIcon><RefreshOutline /></NIcon></template>
          Limpar
        </NButton>
        <NDropdown :options="exportOptions" @select="handleExport">
          <NButton type="primary" class="filter-btn">
            <template #icon><NIcon><DownloadOutline /></NIcon></template>
            Exportar
          </NButton>
        </NDropdown>
      </div>
    </div>

    <template v-if="loadingEstatisticas">
      <NGrid :x-gap="20" :y-gap="20" cols="s:1 m:2 l:4" responsive="screen">
        <NGi v-for="i in 4" :key="i">
          <NCard><NSkeleton :animated="true" /></NCard>
        </NGi>
      </NGrid>
    </template>
    
    <template v-else-if="estatisticas">
      <!-- Linha 1: Métricas Principais Avançadas -->
      <NGrid :x-gap="20" :y-gap="20" cols="s:1 m:2 l:4" responsive="screen" class="mb-4">
        <NGi>
          <NTooltip trigger="hover" placement="top">
            <template #trigger>
              <NCard class="kpi-card">
                <div class="kpi-content">
                  <div class="kpi-icon" style="background: #10B981;">
                    <NIcon size="24" color="#10B981"><CashOutline /></NIcon>
                  </div>
                  <div class="kpi-data">
                    <span class="kpi-label">Faturamento Real</span>
                    <span class="kpi-value">{{ formatMoney(estatisticas.metricas?.faturamento_real || 0) }}</span>
                    <div class="kpi-growth success">
                      <NIcon size="14"><TrendingUpOutline /></NIcon>
                      Realizado
                    </div>
                  </div>
                </div>
              </NCard>
            </template>
            <span>Soma dos pedidos CONCLUÍDOS (status 4) - faturamento efetivamente realizado</span>
          </NTooltip>
        </NGi>

        <NGi>
          <NTooltip trigger="hover" placement="top">
            <template #trigger>
              <NCard class="kpi-card">
                <div class="kpi-content">
                  <div class="kpi-icon" style="background: #3B82F6;">
                    <NIcon size="24" color="#3B82F6"><TrendingUpOutline /></NIcon>
                  </div>
                  <div class="kpi-data">
                    <span class="kpi-label">Receita Projetada</span>
                    <span class="kpi-value">{{ formatMoney(estatisticas.metricas?.receita_projetada || 0) }}</span>
                    <div class="kpi-sub">{{ estatisticas.metricas?.taxa_conversao || 0 }}% convertido</div>
                  </div>
                </div>
              </NCard>
            </template>
            <span>Soma dos pedidos PENDENTES + CONCLUÍDOS - receita provável do pipeline</span>
          </NTooltip>
        </NGi>

        <NGi>
          <NTooltip trigger="hover" placement="top">
            <template #trigger>
              <NCard class="kpi-card">
                <div class="kpi-content">
                  <div class="kpi-icon" style="background: #F59E0B;">
                    <NIcon size="24" color="#F59E0B"><BagHandleOutline /></NIcon>
                  </div>
                  <div class="kpi-data">
                    <span class="kpi-label">Valor em Aberto</span>
                    <span class="kpi-value">{{ formatMoney(estatisticas.metricas?.valor_aberto || 0) }}</span>
                    <div class="kpi-growth warning">
                      <NIcon size="14"><BagHandleOutline /></NIcon>
                      Pendente
                    </div>
                  </div>
                </div>
              </NCard>
            </template>
            <span>Soma dos pedidos PENDENTES (status 2) - aguardando entrega/faturamento</span>
          </NTooltip>
        </NGi>

        <NGi>
          <NTooltip trigger="hover" placement="top">
            <template #trigger>
              <NCard class="kpi-card">
                <div class="kpi-content">
                  <div class="kpi-icon" style="background: #64748B;">
                    <NIcon size="24" color="#64748B"><PeopleOutline /></NIcon>
                  </div>
                  <div class="kpi-data">
                    <span class="kpi-label">Em Análise</span>
                    <span class="kpi-value">{{ formatMoney(estatisticas.metricas?.em_analise || 0) }}</span>
                    <div class="kpi-growth default">
                      <NIcon size="14"><PeopleOutline /></NIcon>
                      Potencial
                    </div>
                  </div>
                </div>
              </NCard>
            </template>
            <span>Soma dos pedidos EM ABERTO (status 1) - oportunidades em análise</span>
          </NTooltip>
        </NGi>
      </NGrid>

      <!-- Linha 2: Métricas adicionais -->
      <NGrid :x-gap="20" :y-gap="20" cols="s:1 m:2 l:6" responsive="screen" class="mb-6">
        <NGi span="1">
          <NCard class="kpi-card mini">
            <div class="kpi-mini">
              <span class="kpi-mini-label">Período</span>
              <span class="kpi-mini-value">{{ formatDateRange(range?.[0], range?.[1]) }}</span>
            </div>
          </NCard>
        </NGi>
        <NGi span="1">
          <NCard class="kpi-card mini">
            <div class="kpi-mini">
              <span class="kpi-mini-label">Total Período Anterior</span>
              <span class="kpi-mini-value">{{ formatMoney(estatisticas.periodo_anterior.total_vendas) }}</span>
            </div>
          </NCard>
        </NGi>
        <NGi span="1">
          <NCard class="kpi-card mini">
            <div class="kpi-mini">
              <span class="kpi-mini-label">Vendas Período Anterior</span>
              <span class="kpi-mini-value">{{ estatisticas.periodo_anterior.quantidade_pedidos }}</span>
            </div>
          </NCard>
        </NGi>
        <NGi span="1">
          <NCard class="kpi-card mini">
            <div class="kpi-mini">
              <span class="kpi-mini-label">Ticket Médio Anterior</span>
              <span class="kpi-mini-value">{{ formatMoney(estatisticas.periodo_anterior.ticket_medio) }}</span>
            </div>
          </NCard>
        </NGi>
        <NGi span="1">
          <NCard class="kpi-card mini">
            <div class="kpi-mini">
              <span class="kpi-mini-label">Diferença Vendas</span>
              <span class="kpi-mini-value" :class="estatisticas.crescimento.vendas >= 0 ? 'text-success' : 'text-error'">
                {{ estatisticas.crescimento.vendas > 0 ? '+' : '' }}{{ formatMoney(estatisticas.periodo_atual.total_vendas - estatisticas.periodo_anterior.total_vendas) }}
              </span>
            </div>
          </NCard>
        </NGi>
        <NGi span="1">
          <NCard class="kpi-card mini">
            <div class="kpi-mini">
              <span class="kpi-mini-label">Média Dia</span>
              <span class="kpi-mini-value">{{ formatMoney(dailyAverage) }}</span>
            </div>
          </NCard>
        </NGi>
      </NGrid>
    </template>

    <!-- Tabs -->
    <NTabs v-model:value="activeTab" type="line" animated>
        <NTabPane name="dashboard" tab="📊 Dashboard">
          <!-- Gráfico de Tendência de Vendas - Largura Total -->
          <NGrid :x-gap="20" :y-gap="20" cols="1" responsive="screen" class="mb-6">
            <NGi>
              <NCard :title="chartViewMode === 'real' ? '📈 Tendência de Faturamento Real' : '📈 Tendência de Receita Projetada'">
                <apexchart v-if="vendasTrendChart" type="area" height="350" :options="vendasTrendChart.options" :series="vendasTrendChart.series" />
                <NEmpty v-else description="Sem dados no período" />
              </NCard>
            </NGi>
          </NGrid>

          <!-- Outros gráficos em grid 2x2 -->
          <NGrid :x-gap="20" :y-gap="20" cols="s:1 m:2 l:2" responsive="screen">
            <NGi>
              <NCard title="👥 Top Vendedores">
                <apexchart v-if="topVendedoresChart" type="bar" height="280" :options="topVendedoresChart.options" :series="topVendedoresChart.series" />
                <NEmpty v-else description="Sem dados" />
              </NCard>
            </NGi>
            <NGi>
              <NCard title="📦 Produtos Mais Vendidos">
                <apexchart v-if="topProdutosChart" type="bar" height="280" :options="topProdutosChart.options" :series="topProdutosChart.series" />
                <NEmpty v-else description="Sem dados" />
              </NCard>
            </NGi>
            <NGi span="s:1 m:2 l:2">
              <NCard title="🔥 Produtos com Potencial">
                <div v-if="insights?.produtos_potencial?.length" class="insights-list">
                  <div v-for="(prod, idx) in insights.produtos_potencial" :key="prod.id_produto" class="insight-item">
                    <div class="insight-rank">{{ idx + 1 }}</div>
                    <div class="insight-info">
                      <div class="insight-title">{{ prod.produto }}</div>
                      <div class="insight-meta">{{ prod.quantidade_vendida }} vendidos · {{ formatMoney(prod.total_vendido) }}</div>
                    </div>
                    <NTag type="warning" size="small">Alto potencial</NTag>
                  </div>
                </div>
                <NEmpty v-else description="Nenhum produto com alto potencial identificado" />
              </NCard>
            </NGi>
          </NGrid>
        </NTabPane>

        <NTabPane name="vendas" tab="💰 Vendas">
          <NCard>
            <NDataTable v-if="dataVendas.length > 0" :columns="columnsVendas" :data="dataVendas" striped :pagination="paginationConfig" />
            <NEmpty v-else description="Nenhuma venda no período">
              <template #extra>
                <NButton @click="onDatePresetChange('30d')">Ver últimos 30 dias</NButton>
              </template>
            </NEmpty>
          </NCard>
        </NTabPane>

        <NTabPane name="vendedores" tab="👥 Vendedores">
          <NCard>
            <NDataTable v-if="dataVendedores.length > 0" :columns="columnsVendedores" :data="dataVendedores" striped :pagination="paginationConfig" />
            <NEmpty v-else description="Nenhum dado de vendedor" />
          </NCard>
        </NTabPane>

        <NTabPane name="produtos" tab="📦 Produtos">
          <NCard>
            <NDataTable v-if="dataProdutos.length > 0" :columns="columnsProdutos" :data="dataProdutos" striped :pagination="paginationConfig" />
            <NEmpty v-else description="Nenhum produto vendido" />
          </NCard>
        </NTabPane>

        <NTabPane name="clientes" tab="👤 Clientes">
          <NCard>
            <template #header-extra>
              <NButton @click="loadData" size="small">
                <template #icon><NIcon><RefreshOutline /></NIcon></template>
                Atualizar
              </NButton>
            </template>
            <NDataTable v-if="dataClientes.length > 0" :columns="columnsClientes" :data="dataClientes" striped :pagination="paginationConfig" />
            <NEmpty v-else description="Nenhum dado de cliente">
              <template #extra>
                <NButton @click="loadData">Carregar Dados</NButton>
              </template>
            </NEmpty>
          </NCard>
        </NTabPane>
      </NTabs>

<!-- Modal Crosstab: Produtos/Vendas por Vendedor -->
    <NModal v-model:show="showCrosstab" preset="card" :title="crosstabTitle" style="width: 800px;">
      <!-- Loading state -->
      <NSpin v-if="loadingCrosstab" style="min-height: 200px;">
        Carregando...
      </NSpin>

      <!-- Content when loaded -->
      <div v-else>
        <!-- Resumo do vendedor -->
        <div v-if="crosstabData.length > 0" class="vendedor-summary" style="margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 8px;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <span style="font-weight: 600; color: #1e293b;">{{ selectedVendedor?.nome }}</span>
              <div style="font-size: 14px; color: #64748b; margin-top: 4px;">
                <span v-if="crosstabMode === 'produtos'">{{ crosstabData.length }} produto{{ crosstabData.length !== 1 ? 's' : '' }} vendido{{ crosstabData.length !== 1 ? 's' : '' }}</span>
                <span v-else>{{ crosstabData.length }} pedido{{ crosstabData.length !== 1 ? 's' : '' }}</span>
              </div>
            </div>
            <div style="text-align: right;">
              <div style="font-size: 18px; font-weight: 700; color: #6366f1;">
                <span v-if="crosstabMode === 'produtos'">{{ formatMoney(crosstabData.reduce((sum, prod) => sum + (parseFloat(prod.total_vendido) || 0), 0)) }}</span>
                <span v-else>{{ formatMoney(crosstabData.reduce((sum, ped) => sum + (parseFloat(ped.total_pedido) || 0), 0)) }}</span>
              </div>
              <div style="font-size: 12px; color: #64748b;">
                Total no período
              </div>
            </div>
          </div>
        </div>

        <!-- Tabela de produtos -->
        <NDataTable
          v-if="crosstabMode === 'produtos' && crosstabData.length > 0"
          :columns="columnsProdutos"
          :data="crosstabData"
          :pagination="paginationConfig"
          striped
          size="small"
        />

        <!-- Tabela de vendas -->
        <NDataTable
          v-else-if="crosstabMode === 'vendas' && crosstabData.length > 0"
          :columns="columnsVendasVendedor"
          :data="crosstabData"
          :pagination="paginationConfig"
          striped
          size="small"
        />
        <NEmpty v-else description="Nenhum dado encontrado" />
      </div>

      <template #footer>
        <NButton @click="closeCrosstab">Fechar</NButton>
      </template>
    </NModal>

    <!-- Modal Detalhes do Cliente -->
    <div v-if="showClienteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white px-6 py-4">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold flex items-center">
              <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
              Pedidos de {{ selectedVendedor?.nome }}
            </h2>
            <button @click="showClienteModal = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-1 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6 overflow-y-auto max-h-[70vh]">
          <div v-if="clienteDetalhes">
            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wide">Total Gasto</p>
                    <p class="text-2xl font-bold text-blue-800">{{ formatMoney(clienteDetalhes.estatisticas.total_gasto) }}</p>
                  </div>
                  <div class="bg-blue-500 rounded-full p-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-green-600 uppercase tracking-wide">Total de Pedidos</p>
                    <p class="text-2xl font-bold text-green-800">{{ clienteDetalhes.estatisticas.quantidade_pedidos }}</p>
                  </div>
                  <div class="bg-green-500 rounded-full p-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-purple-600 uppercase tracking-wide">Ticket Médio</p>
                    <p class="text-2xl font-bold text-purple-800">{{ formatMoney(clienteDetalhes.estatisticas.ticket_medio) }}</p>
                  </div>
                  <div class="bg-purple-500 rounded-full p-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tabela de Pedidos -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
              <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  Histórico de Pedidos
                </h3>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left font-medium text-gray-700">Pedido</th>
                      <th class="px-4 py-3 text-left font-medium text-gray-700">Data</th>
                      <th class="px-4 py-3 text-left font-medium text-gray-700">Cliente</th>
                      <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                      <th class="px-4 py-3 text-right font-medium text-gray-700">Valor</th>
                      <th class="px-4 py-3 text-center font-medium text-gray-700">Itens</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="pedido in clienteDetalhes.pedidos" :key="pedido.id_pedido" class="hover:bg-gray-50">
                      <td class="px-4 py-3 font-medium text-gray-900">#{{ pedido.id_pedido }}</td>
                      <td class="px-4 py-3 text-gray-600">{{ formatDate(pedido.data_pedido) }}</td>
                      <td class="px-4 py-3 text-gray-600">{{ pedido.razao_social }}</td>
                      <td class="px-4 py-3">
                        <span :class="getStatusClasses(pedido.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                          {{ getStatusLabel(pedido.status) }}
                        </span>
                      </td>
                      <td class="px-4 py-3 text-right font-semibold text-green-600">R$ {{ formatMoney(pedido.total_pedido) }}</td>
                      <td class="px-4 py-3 text-center">
                        <button @click="verItensCliente(pedido)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                          Ver
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            <p class="text-gray-500 text-lg">Nenhum dado encontrado</p>
            <p class="text-gray-400 text-sm">Não foi possível carregar as informações do cliente.</p>
          </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 flex justify-end">
          <button @click="closeClienteModal" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium">
            Fechar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.relatorios-container {
  width: 100%;
  max-width: 1600px;
  margin: 0 auto;
  padding: 0 16px;
}

.page-header {
  margin-bottom: 24px;
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.header-titles {
  flex-shrink: 0;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.page-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.filters-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.filter-select {
  min-width: 140px;
  flex: 0 0 auto;
}

.filter-date {
  min-width: 240px;
  flex: 0 0 auto;
}

.filter-btn {
  flex-shrink: 0;
}

/* Responsive */
@media (max-width: var(--bp-large)) {
  .filters-row {
    gap: 10px;
  }
  
  .filter-select {
    min-width: 120px;
  }
  
  .filter-date {
    min-width: 200px;
  }
}

@media (max-width: var(--bp-medium)) {
  .relatorios-container {
    padding: 0 12px;
  }
  
  .header-top {
    flex-direction: column;
    gap: 16px;
  }
  
  .filters-row {
    flex-wrap: wrap;
  }
  
  .filter-select,
  .filter-date {
    flex: 1 1 calc(50% - 6px);
    min-width: 140px;
  }
  
  .filter-btn {
    flex: 1 1 auto;
    min-width: fit-content;
  }
}

@media (max-width: var(--bp-small)) {
  .relatorios-container {
    padding: 0 8px;
  }
  
  .page-title {
    font-size: 22px;
  }
  
  .page-subtitle {
    font-size: 13px;
  }
  
  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-select,
  .filter-date {
    width: 100%;
    flex: 1 1 100%;
  }
  
  .filter-btn {
    width: 100%;
  }
}

.mb-6 {
  margin-bottom: 24px;
}

.kpi-card {
  transition: transform 0.2s, box-shadow 0.2s;
  min-height: 80px;
  display: flex;
  align-items: stretch;
  justify-content: center;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.kpi-content {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 16px;
  width: 100%;
  height: 100%;
  padding: 12px 16px;
}

.kpi-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  align-self: center;
}

.kpi-data {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  flex: 1;
  min-width: 0; /* Prevent flex item from overflowing */
  gap: 2px;
}

.kpi-label {
  font-size: 13px;
  color: #64748b;
}

.kpi-value {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
  min-height: 24px;
  display: flex;
  align-items: center;
  margin-bottom: 1px;
}

.kpi-growth {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 4px;
  font-size: 12px;
  font-weight: 500;
  margin-top: 2px;
}

.kpi-growth.success {
  color: #10B981;
}

.kpi-growth.error {
  color: #EF4444;
}

.kpi-growth.default {
  color: #64748b;
}

.kpi-sub {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
  font-weight: 500;
}

.kpi-card.mini {
  padding: 0;
  min-height: 70px;
  display: flex;
  align-items: stretch;
  justify-content: center;
  flex: 1;
}

.kpi-mini {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  gap: 4px;
  width: 100%;
  height: 100%;
  padding: 12px 16px;
}

.kpi-mini-label {
  font-size: 11px;
  font-weight: 500;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.kpi-mini-value {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
}

.kpi-mini-value {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
}

.text-success {
  color: #10B981 !important;
}

.text-error {
  color: #EF4444 !important;
}

.insights-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.insight-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f8fafc;
  border-radius: 8px;
}

.insight-rank {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #6366F1;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}

.insight-info {
  flex: 1;
}

.insight-title {
  font-weight: 500;
  color: #1e293b;
  font-size: 14px;
}

.insight-meta {
  font-size: 12px;
  color: #64748b;
}

/* Melhorar aparência do gráfico de área */
.apexcharts-area {
  filter: drop-shadow(0 2px 4px rgba(99, 102, 241, 0.1));
}
</style>