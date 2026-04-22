<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  ArrowLeft,
  User,
  Pencil,
  Loader2,
  AlertCircle,
  ShoppingCart
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import apiClient from '@/lib/axios'

interface Vendedor {
  id_vendedor: number
  nome_vendedor: string
  contato_vendedor: string
  usuario: string
  status: number
  cpf: string
  email: string
  telefone: string
  comissao: number
}

interface Pedido {
  id_pedido: number
  data_pedido: string
  total_pedido: number
  status: number
}

const router = useRouter()
const route = useRoute()

const vendedor = ref<Vendedor | null>(null)
const pedidos = ref<Pedido[]>([])
const isLoading = ref(true)
const isLoadingPedidos = ref(false)
const errorMessage = ref<string | null>(null)

const fetchVendedor = async () => {
  const id = route.params.id as string
  if (!id) return

  isLoading.value = true
  errorMessage.value = null

  try {
    const response = await apiClient.get(`/api/vendedores/${id}`)
    vendedor.value = response.data.data
  } catch (error: any) {
    errorMessage.value = 'Erro ao carregar vendedor.'
    console.error('Failed to fetch vendedor:', error)
  } finally {
    isLoading.value = false
  }
}

const fetchPedidos = async () => {
  if (!vendedor.value) return

  isLoadingPedidos.value = true
  try {
    // Assuming there's an endpoint to get pedidos by vendedor
    const response = await apiClient.get('/api/pedidos', {
      params: { id_vendedor: vendedor.value.id_vendedor }
    })
    pedidos.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch pedidos:', error)
    pedidos.value = []
  } finally {
    isLoadingPedidos.value = false
  }
}

const getStatusLabel = (status: number) => {
  return status === 1 ? 'ATIVO' : 'INATIVO'
}

const getStatusClass = (status: number) => {
  return status === 1
    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-200 dark:border-emerald-500/30'
    : 'bg-rose-500/10 text-rose-600 border-rose-200 dark:border-rose-500/30'
}

const getPedidoStatusLabel = (status: number) => {
  const statuses = {
    1: 'ABERTO',
    2: 'PENDENTE',
    3: 'CANCELADO',
    4: 'CONCLUÍDO'
  }
  return statuses[status as keyof typeof statuses] || 'DESCONHECIDO'
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0)
}

onMounted(async () => {
  await fetchVendedor()
  if (vendedor.value) {
    await fetchPedidos()
  }
})
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <div class="flex items-center gap-4">
        <Button
          variant="outline"
          size="icon"
          @click="router.back()"
          class="rounded-xl h-11 w-11"
        >
          <ArrowLeft class="w-4 h-4" />
        </Button>
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Detalhes do Vendedor</h1>
          <p class="text-muted-foreground text-sm font-medium mt-1">Informações completas e histórico</p>
        </div>
      </div>

      <Button
        @click="router.push(`/vendedores/${vendedor?.id_vendedor}/editar`)"
        variant="outline"
        class="rounded-xl h-11 px-6"
      >
        <Pencil class="w-4 h-4 mr-2" />
        Editar Vendedor
      </Button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center items-center py-20">
      <Loader2 class="w-8 h-8 animate-spin text-primary" />
      <p class="ml-3 text-sm text-muted-foreground">Carregando...</p>
    </div>

    <!-- Error -->
    <div v-else-if="errorMessage" class="text-center text-red-500 p-8">
      <AlertCircle class="inline-block w-6 h-6 mr-2" />
      {{ errorMessage }}
    </div>

    <!-- Content -->
    <div v-else-if="vendedor" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Vendedor Info -->
      <Card class="lg:col-span-2 rounded-2xl border shadow-sm bg-card overflow-hidden">
        <CardHeader class="border-b bg-muted/20 px-8 py-6 flex flex-row justify-between items-center">
          <div>
            <CardTitle class="text-lg font-bold flex items-center gap-2">
              <User class="w-5 h-5 text-primary" />
              {{ vendedor.nome_vendedor }}
            </CardTitle>
            <CardDescription class="text-xs font-medium mt-1">ID: #{{ vendedor.id_vendedor }}</CardDescription>
          </div>

          <Badge
            variant="outline"
            :class="['rounded-lg px-3 py-0.5 text-[10px] font-bold tracking-wide uppercase border', getStatusClass(vendedor.status)]"
          >
            {{ getStatusLabel(vendedor.status) }}
          </Badge>
        </CardHeader>

        <CardContent class="p-8 space-y-6">
          <!-- Basic Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1">
              <p class="text-xs font-semibold text-muted-foreground">Nome Completo</p>
              <p class="text-sm font-bold">{{ vendedor.nome_vendedor }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-xs font-semibold text-muted-foreground">Usuário</p>
              <p class="text-sm font-bold">{{ vendedor.usuario }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-xs font-semibold text-muted-foreground">Contato</p>
              <p class="text-sm font-bold">{{ vendedor.contato_vendedor || 'Não informado' }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-xs font-semibold text-muted-foreground">Comissão</p>
              <p class="text-sm font-bold">{{ vendedor.comissao }}%</p>
            </div>
          </div>

          <Separator />

          <!-- Contact Info -->
          <div>
            <h4 class="text-sm font-bold mb-4">Informações de Contato</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-1">
                <p class="text-xs font-semibold text-muted-foreground">CPF</p>
                <p class="text-sm font-bold">{{ vendedor.cpf || 'Não informado' }}</p>
              </div>
              <div class="space-y-1">
                <p class="text-xs font-semibold text-muted-foreground">Telefone</p>
                <p class="text-sm font-bold">{{ vendedor.telefone || 'Não informado' }}</p>
              </div>
              <div class="space-y-1 md:col-span-2">
                <p class="text-xs font-semibold text-muted-foreground">Email</p>
                <p class="text-sm font-bold">{{ vendedor.email || 'Não informado' }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Stats Card -->
      <Card class="rounded-2xl border shadow-sm bg-card">
        <CardHeader>
          <CardTitle class="text-lg">Estatísticas</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium">Total de Pedidos</span>
            <span class="text-lg font-bold">{{ pedidos.length }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium">Status</span>
            <Badge variant="outline" :class="getStatusClass(vendedor.status)">
              {{ getStatusLabel(vendedor.status) }}
            </Badge>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Pedidos Section -->
    <Card v-if="vendedor" class="rounded-2xl border shadow-sm bg-card overflow-hidden">
      <CardHeader class="border-b bg-muted/20 px-8 py-6">
        <CardTitle class="text-lg flex items-center gap-2">
          <ShoppingCart class="w-5 h-5 text-primary" />
          Pedidos do Vendedor
        </CardTitle>
        <CardDescription>Histórico de pedidos realizados por este vendedor</CardDescription>
      </CardHeader>

      <CardContent class="p-0">
        <div v-if="isLoadingPedidos" class="flex justify-center items-center py-10">
          <Loader2 class="w-6 h-6 animate-spin text-primary" />
          <p class="ml-2 text-sm text-muted-foreground">Carregando pedidos...</p>
        </div>

        <Table v-else>
          <TableHeader class="bg-muted/30">
            <TableRow class="hover:bg-transparent">
              <TableHead class="w-[100px] text-xs font-bold uppercase tracking-wider py-5 px-8">ID</TableHead>
              <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Data</TableHead>
              <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Status</TableHead>
              <TableHead class="text-xs font-bold uppercase tracking-wider py-5 text-right">Total</TableHead>
              <TableHead class="text-right py-5 px-8"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="pedido in pedidos" :key="pedido.id_pedido" class="hover:bg-accent/30 transition-colors">
              <TableCell class="py-5 px-8 font-bold text-sm">#{{ pedido.id_pedido }}</TableCell>
              <TableCell class="py-5 text-sm">{{ new Date(pedido.data_pedido).toLocaleDateString('pt-BR') }}</TableCell>
              <TableCell class="py-5">
                <Badge variant="outline" class="text-[10px]">
                  {{ getPedidoStatusLabel(pedido.status) }}
                </Badge>
              </TableCell>
              <TableCell class="py-5 text-right font-bold text-sm">{{ formatCurrency(pedido.total_pedido) }}</TableCell>
              <TableCell class="py-5 px-8 text-right">
                <Button
                  variant="ghost"
                  size="sm"
                  @click="router.push(`/pedidos/${pedido.id_pedido}`)"
                  class="h-8 w-8 p-0"
                >
                  <ArrowLeft class="w-3 h-3 rotate-180" />
                </Button>
              </TableCell>
            </TableRow>
            <TableRow v-if="pedidos.length === 0">
              <TableCell colspan="5" class="text-center py-10 text-muted-foreground">
                Nenhum pedido encontrado para este vendedor.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>
</template>