<script setup>
import { ref, onMounted, computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../composables/useApi'
import { NCard, NStatistic, NGrid, NGi, NIcon, NButton, NSpin, NSpace, NTabs, NTabPane, NSkeleton } from 'naive-ui'
import { PeopleOutline, CartOutline, BagHandleOutline, TrendingUpOutline, CashOutline, AddOutline, RefreshOutline } from '@vicons/ionicons5'
import VueApexCharts from 'vue3-apexcharts'

const PeopleIcon = PeopleOutline
const CartIcon = CartOutline
const TrendingIcon = TrendingUpOutline

const router = useRouter()
const authStore = useAuthStore()
const loading = ref(true)
const dashboard = ref(null)
const scaleMode = ref('normal')

const colors = ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316']

function setScaleMode(mode) {
  scaleMode.value = mode
}

function getFilteredData(data, mode) {
  if (mode === 'top') {
    return data.filter(v => v.total_vendas >= data[0]?.total_vendas * 0.3)
  }
  return data
}

const chartOptions = reactive({
  chart: {
    type: 'donut',
    toolbar: { show: false },
    fontFamily: 'Nunito, sans-serif'
  },
  colors: ['#18c5a8', '#3b82f6', '#f59e0b', '#ef4444'],
  labels: [],
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '65%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Total',
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
          }
        }
      }
    }
  },
  legend: { position: 'bottom' },
  responsive: [{ breakpoint: 480, options: { chart: { width: 200 }, legend: { position: 'bottom' } } }]
})

const chartOptionsBar = reactive({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Nunito, sans-serif' },
  colors: ['#18c5a8'],
  plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
  dataLabels: { enabled: true, style: { fontSize: '14px' } },
  xaxis: { 
    categories: [],
    labels: { style: { fontSize: '14px' } }
  },
  yaxis: {
    labels: { style: { fontSize: '14px' } }
  },
  responsive: [{ breakpoint: 480, options: { chart: { width: 200 } } }]
})

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }
  await loadDashboard()
})

async function loadDashboard() {
  try {
    const response = await api.getDashboard()
    dashboard.value = response.data.data
  } catch (error) {
    console.error('Erro:', error)
  } finally {
    loading.value = false
  }
}

function formatMoney(val) {
  if (!val) return 'R$ 0,00'
  const num = parseFloat(val)
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num)
}

function formatCompact(val) {
  if (!val) return '0'
  const num = parseFloat(val)
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-BR')
}

const topProdutosChart = computed(() => {
  if (!dashboard.value?.graficos?.top_produtos?.length) return null
  const data = dashboard.value.graficos.top_produtos.slice(0, 5)
  return {
    series: [{ data: data.map(p => p.quantidade) }],
    options: {
      ...chartOptionsBar,
      xaxis: { categories: data.map(p => p.produto) },
      title: { text: 'Top 5 Produtos', style: { color: '#fff' } }
    }
  }
})

const vendasVendedorChart = computed(() => {
  if (!dashboard.value?.graficos?.vendas_por_vendedor?.length) return null
  let data = dashboard.value.graficos.vendas_por_vendedor
  
  if (scaleMode.value === 'top') {
    data = data.slice(0, 3)
  } else if (scaleMode.value === 'bottom') {
    data = data.slice(-3)
  }
  
  return {
    series: [{ data: data.map(v => parseFloat(v.total_vendas) || 0) }],
    options: {
      chart: { 
        type: 'bar', 
        toolbar: { 
          show: true,
          offsetX: 0,
          offsetY: 0,
          tools: {
            download: false,
            selection: true,
            zoom: true,
            zoomin: true,
            zoomout: true,
            pan: false,
            reset: true
          }
        }, 
        fontFamily: 'Nunito, sans-serif',
        zoom: {
          enabled: true,
          type: 'x',
          autoScaleYaxis: true
        },
        animations: {
          enabled: true
        }
      },
      colors: colors,
      plotOptions: { 
        bar: { 
          horizontal: true, 
          borderRadius: 4, 
          barHeight: '70%',
          distributed: true
        } 
      },
      dataLabels: { 
        enabled: true,
        style: { fontSize: '10px', colors: ['#fff'] },
        formatter: (val) => formatCompact(val)
      },
      xaxis: { 
        categories: data.map(v => v.nome_vendedor || 'Sem nome'),
        labels: { 
          style: { fontSize: '11px' },
          formatter: (val) => formatCompact(val)
        }
      },
      yaxis: {
        labels: { 
          style: { fontSize: '11px' },
          formatter: (val) => `${val} pedidos`
        }
      },
      legend: { show: false },
      tooltip: {
        y: {
          formatter: (val) => formatMoney(val)
        }
      },
      stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
      },
      fill: {
        opacity: 1
      }
    }
  }
})

const pedidosStatusChart = computed(() => {
  if (!dashboard.value?.pedidos) return null
  const p = dashboard.value.pedidos
  return {
    series: [p.aberto || 0, p.pendente || 0, p.concluido || 0, p.cancelado || 0],
    options: {
      ...chartOptions,
      labels: ['Aberto', 'Pendente', 'Concluído', 'Cancelado'],
      title: { text: 'Pedidos por Status', style: { color: '#fff' } }
    }
  }
})
</script>

<template>
  <div class="dashboard-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Visão geral do sistema</p>
      </div>
      <div class="header-actions">
        <NButton @click="loadDashboard" :loading="loading">
          <template #icon><NIcon><RefreshOutline /></NIcon></template>
          Atualizar
        </NButton>
        <NButton type="primary" @click="router.push('/pedidos')">
          <template #icon><NIcon><AddOutline /></NIcon></template>
          Novo Pedido
        </NButton>
      </div>
    </div>

    <!-- Skeleton Loading -->
    <template v-if="loading">
      <div class="skeleton-grid">
        <NCard v-for="i in 4" :key="i" class="stat-card">
          <NSkeleton :animated="true" />
          <NSkeleton :animated="true" />
          <NSkeleton :animated="true" />
        </NCard>
      </div>
    </template>

    <!-- Real Content -->
    <template v-else>
      <!-- Linha 1 - Métricas principais -->
      <div class="stats-grid">
        <div class="flex-1 min-w-[200px]">
          <NCard hoverable class="stat-card">
            <NStatistic label="Clientes Ativos" :value="dashboard?.clientes?.ativos || 0">
              <template #prefix>
                <NIcon color="#6366F1"><PeopleOutline /></NIcon>
              </template>
            </NStatistic>
            <div class="stat-meta">
              {{ dashboard?.clientes?.inativos || 0 }} inativos · {{ dashboard?.clientes?.total || 0 }} total
            </div>
          </NCard>
        </div>

        <div class="flex-1 min-w-[200px]">
          <NCard hoverable class="stat-card">
            <NStatistic label="Pedidos Abertos" :value="dashboard?.pedidos?.aberto || 0">
              <template #prefix>
                <NIcon color="#F59E0B"><CartOutline /></NIcon>
              </template>
            </NStatistic>
            <div class="stat-meta">
              {{ dashboard?.pedidos?.pendente || 0 }} pendentes
            </div>
          </NCard>
        </div>

        <div class="flex-1 min-w-[200px]">
          <NCard>
            <NStatistic label="Pedidos Concluídos" :value="dashboard?.pedidos.concluido || 0">
              <template #prefix>
                <NIcon><BagHandleOutline /></NIcon>
              </template>
            </NStatistic>
            <div class="mt-2 text-sm text-gray-500">
              {{ dashboard?.pedidos.cancelado || 0 }} cancelados
            </div>
          </NCard>
        </div>

        <div class="flex-1 min-w-[200px]">
          <NCard>
            <NStatistic label="Produtos Disponíveis" :value="dashboard?.produtos.disponiveis || 0">
              <template #prefix>
                <NIcon><TrendingUpOutline /></NIcon>
              </template>
            </NStatistic>
          </NCard>
        </div>
      </div>

      <!-- Linha 2 - Total Vendas -->
      <div class="mb-4">
        <NCard v-if="authStore.isAdmin">
          <NStatistic label="Total Vendas (Geral)" :value="formatMoney(dashboard?.vendas.total_geral)">
            <template #prefix>
              <NIcon><CashOutline /></NIcon>
            </template>
          </NStatistic>
        </NCard>
      </div>

      <!-- Linha 3 - Gráficos -->
      <div v-if="authStore.isAdmin" class="charts-grid">
        <div v-if="dashboard?.graficos?.top_produtos?.length > 0">
          <NCard title="Top Produtos" class="top-produtos-card">
            <div class="chart-container">
              <apexchart v-if="topProdutosChart" type="bar" height="300" :options="topProdutosChart.options" :series="topProdutosChart.series" />
            </div>
            <div v-if="!topProdutosChart" class="space-y-3">
              <div v-for="produto in dashboard.graficos.top_produtos.slice(0, 5)" :key="produto.id_produto" class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                <div>
                  <div class="font-medium">{{ produto.produto }}</div>
                  <div class="text-sm text-gray-500">{{ produto.quantidade }} itens vendidos</div>
                </div>
                <div class="font-bold text-green-600">{{ formatMoney(produto.total_vendido) }}</div>
              </div>
            </div>
          </NCard>
        </div>

        <div>
          <NCard title="Pedidos por Status" class="top-produtos-card">
            <apexchart v-if="pedidosStatusChart" type="donut" height="300" :options="pedidosStatusChart.options" :series="pedidosStatusChart.series" />
          </NCard>
        </div>
      </div>

      <!-- Linha 4 - Vendas por Vendedor -->
      <div v-if="authStore.isAdmin && dashboard?.graficos?.vendas_por_vendedor?.length > 0" class="charts-grid">
        <div class="charts-grid-full">
          <NCard title="Vendas por Vendedor" class="vendas-vendedor-card">
            <div class="flex flex-wrap gap-2 mb-4">
              <NButton size="small" :type="scaleMode === 'all' ? 'primary' : 'default'" @click="setScaleMode('all')">
                Todos
              </NButton>
              <NButton size="small" :type="scaleMode === 'top' ? 'primary' : 'default'" @click="setScaleMode('top')">
                Top 3
              </NButton>
              <NButton size="small" :type="scaleMode === 'bottom' ? 'primary' : 'default'" @click="setScaleMode('bottom')">
                Menores
              </NButton>
            </div>
            <apexchart v-if="vendasVendedorChart" type="bar" height="300" :options="vendasVendedorChart.options" :series="vendasVendedorChart.series" />
            <div v-else class="text-center text-gray-500 py-8">
              Nenhum dado disponível
            </div>
          </NCard>
        </div>

      </div>
    </template>
  </div>
</template>

<style scoped>
.dashboard-container {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 var(--space-4);
}

@media (max-width: var(--bp-tablet)) {
  .dashboard-container {
    padding: 0 var(--space-2);
  }
}

@media (max-width: var(--bp-mobile)) {
  .dashboard-container {
    padding: 0;
  }
}

.vendas-vendedor-card :deep(.apexcharts-toolbar) {
  top: 10px;
  right: 10px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--space-6);
  gap: var(--space-4);
}

@media (max-width: var(--bp-tablet)) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: var(--space-3);
  }
}

.page-title {
  font-size: var(--text-3xl);
  font-weight: var(--font-bold);
  color: var(--gray-900);
  margin: 0;
  line-height: 1.2;
}

[data-theme="dark"] .page-title {
  color: var(--gray-100);
}

.page-subtitle {
  font-size: var(--text-sm);
  color: var(--gray-500);
  margin: var(--space-1) 0 0 0;
  font-weight: var(--font-medium);
}

[data-theme="dark"] .page-subtitle {
  color: var(--gray-400);
}

.stat-card {
  transition: all var(--transition-fast);
  border-radius: var(--radius-xl);
  border: 1px solid var(--gray-200);
  background: var(--gray-50);
}

[data-theme="dark"] .stat-card {
  background: var(--gray-800);
  border-color: var(--gray-700);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.stat-meta {
  margin-top: var(--space-2);
  font-size: var(--text-xs);
  color: var(--gray-500);
  font-weight: var(--font-medium);
}

[data-theme="dark"] .stat-meta {
  color: var(--gray-400);
}

.top-produtos-card {
  min-height: 400px;
  border-radius: var(--radius-xl);
}

.chart-container {
  width: 100%;
  min-height: 320px;
  border-radius: var(--radius-lg);
}

/* Grid responsivo para estatísticas */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: var(--space-4);
  margin-bottom: var(--space-6);
}

@media (max-width: var(--bp-tablet)) {
  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-3);
  }
}

@media (max-width: var(--bp-mobile)) {
  .stats-grid {
    grid-template-columns: 1fr;
    gap: var(--space-2);
  }
}

/* Charts responsivos */
.charts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-6);
  margin-bottom: var(--space-6);
}

@media (max-width: var(--bp-tablet)) {
  .charts-grid {
    grid-template-columns: 1fr;
    gap: var(--space-4);
  }

  .charts-grid-full {
    grid-column: 1 / -1;
  }
}

/* Ações do header responsivas */
.header-actions {
  display: flex;
  gap: var(--space-3);
  flex-shrink: 0;
}

@media (max-width: var(--bp-mobile)) {
  .header-actions {
    flex-direction: column;
    width: 100%;
  }

  .header-actions :deep(.n-button) {
    width: 100%;
    justify-content: center;
  }
}

/* Loading skeleton responsivo */
.skeleton-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: var(--space-4);
}

@media (max-width: var(--bp-tablet)) {
  .skeleton-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
}

@media (max-width: var(--bp-mobile)) {
  .skeleton-grid {
    grid-template-columns: 1fr;
  }
}
</style>