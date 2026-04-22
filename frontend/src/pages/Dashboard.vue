<script setup lang="ts">
import { ref, onMounted } from 'vue'
import {
  Users,
  Package,
  ShoppingCart,
  TrendingUp,
  Clock,
  AlertCircle,
  Loader2 // Added for loading indicator
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import apiClient from '@/lib/axios' // Import the configured axios instance

interface Stat {
  name: string
  value: string
  icon: any // lucide-vue-next icon component
  description: string
}

interface RecentOrder {
  id: string
  client: string
  total: string
  status: string
  time: string
}

const dashboardStats = ref<Stat[]>([])
const recentOrdersData = ref<RecentOrder[]>([])
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)

const fetchDashboardData = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const [dashboardResponse, statsResponse] = await Promise.all([
      apiClient.get('/api/dashboard'),
      apiClient.get('/api/dashboard/estatisticas')
    ])

    // --- Process dashboardResponse ---
    // Assuming dashboardResponse.data contains client, product, order summaries
    // This mapping needs to align with actual API response from /api/dashboard
    const dashboardSummary = dashboardResponse.data.summary || {}
    dashboardStats.value = [
      { name: 'Clientes Ativos', value: dashboardSummary.active_clients?.toString() || 'N/A', icon: Users, description: dashboardSummary.client_change || '' },
      { name: 'Estoque Total', value: dashboardSummary.total_stock?.toString() || 'N/A', icon: Package, description: dashboardSummary.new_items || '' },
      { name: 'Pedidos Hoje', value: dashboardSummary.orders_today?.toString() || 'N/A', icon: ShoppingCart, description: dashboardSummary.orders_today_value || '' },
    ]

    // Assuming dashboardResponse.data.recent_orders contains recent order list
    // This mapping needs to align with actual API response from /api/dashboard
    recentOrdersData.value = dashboardResponse.data.recent_orders?.map((order: any) => ({
      id: order.id.toString(),
      client: order.client_name,
      total: order.total_amount,
      status: order.status_label.toUpperCase(),
      time: order.time_ago, // Or actual time
    })) || []

    // --- Process statsResponse (if needed, currently not explicitly used in template beyond above) ---
    // statsResponse.data can be used for 'Insights de Vendas' or other detailed charts/graphs
    console.log('Dashboard Stats API Response:', statsResponse.data);


  } catch (error: any) {
    if (apiClient.isAxiosError(error) && error.response) {
      errorMessage.value = error.response.data.message || 'Erro ao carregar dados do dashboard.'
    } else {
      errorMessage.value = 'Erro de conexão com o servidor.'
    }
    console.error('Failed to fetch dashboard data:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col gap-1">
      <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
      <p class="text-muted-foreground text-sm font-medium">Bem-vindo de volta ao Sistema Lizzie.</p>
    </div>

    <div v-if="isLoading" class="flex justify-center items-center h-64">
      <Loader2 class="w-10 h-10 animate-spin text-primary" />
      <p class="ml-3 text-lg text-muted-foreground">Carregando dados do dashboard...</p>
    </div>
    <div v-else-if="errorMessage" class="text-center text-red-500 p-4 border border-red-300 rounded-lg">
      <AlertCircle class="inline-block w-5 h-5 mr-2" />
      {{ errorMessage }}
      <Button variant="link" @click="fetchDashboardData">Tentar novamente</Button>
    </div>

    <div v-else>
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card v-for="stat in dashboardStats" :key="stat.name"
             class="bg-card border shadow-sm hover:shadow-md transition-all duration-300 rounded-2xl overflow-hidden">
          <CardContent class="p-6">
            <div class="flex justify-between items-start">
              <div class="space-y-1">
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">{{ stat.name }}</p>
                <p class="text-3xl font-bold tracking-tight">{{ stat.value }}</p>
                <p class="text-[11px] font-medium text-primary">{{ stat.description }}</p>
              </div>
              <div class="p-3 bg-primary/10 rounded-xl text-primary">
                <component :is="stat.icon" class="w-6 h-6" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Activity -->
        <Card class="border shadow-sm rounded-2xl overflow-hidden">
          <CardHeader class="flex flex-row items-center justify-between border-b bg-muted/20 px-8 py-6 space-y-0">
            <div>
              <CardTitle class="text-lg font-bold flex items-center gap-2">
                <Clock class="w-5 h-5 text-primary" /> Atividade Recente
              </CardTitle>
              <CardDescription class="text-xs font-medium mt-1">Últimos pedidos realizados</CardDescription>
            </div>
            <Button variant="ghost" size="sm" class="text-primary font-bold hover:bg-primary/5">Ver Tudo</Button>
          </CardHeader>

          <CardContent class="p-6">
            <div class="space-y-3">
              <div v-for="order in recentOrdersData" :key="order.id"
                   class="flex items-center justify-between p-4 rounded-xl border bg-card/50 hover:bg-accent transition-all group">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-primary/5 rounded-lg flex items-center justify-center font-bold text-primary text-xs">
                    #{{ order.id }}
                  </div>
                  <div>
                    <p class="text-sm font-bold">{{ order.client }}</p>
                    <p class="text-[11px] font-medium text-muted-foreground">{{ order.time }}</p>
                  </div>
                </div>
                <div class="text-right space-y-1">
                  <p class="text-sm font-bold">{{ order.total }}</p>
                  <Badge variant="outline" class="text-[9px] font-bold px-2 py-0 h-4 border-primary/20 bg-primary/5 text-primary">
                    {{ order.status }}
                  </Badge>
                </div>
              </div>
              <div v-if="recentOrdersData.length === 0" class="text-center text-muted-foreground p-4">
                Nenhum pedido recente encontrado.
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- System Insights -->
        <Card class="border shadow-sm rounded-2xl overflow-hidden">
          <CardHeader class="border-b bg-muted/20 px-8 py-6">
            <CardTitle class="text-lg font-bold flex items-center gap-2">
              <TrendingUp class="w-5 h-5 text-primary" /> Insights de Vendas
            </CardTitle>
            <CardDescription class="text-xs font-medium mt-1">Dados processados hoje</CardDescription>
          </CardHeader>

          <CardContent class="p-8">
            <div class="h-64 flex flex-col items-center justify-center border-2 border-dashed rounded-2xl text-center p-8">
              <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mb-4 text-muted-foreground opacity-40">
                <AlertCircle class="w-8 h-8" />
              </div>
              <p class="text-sm font-bold mb-1">Aguardando mais dados</p>
              <p class="text-xs text-muted-foreground max-w-[200px] font-medium">Os relatórios de performance serão ativados assim que mais pedidos forem concluídos.</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>  </div>
</template>
