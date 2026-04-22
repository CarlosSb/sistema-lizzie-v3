<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, User, Package, ShoppingCart, Calendar, DollarSign, Loader2, AlertCircle, Save } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
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
  produto_nome: string
  referencia: string
  quantidade: number
  valor_unitario: number
  total_item: number
}

const route = useRoute()
const pedido = ref<PedidoDetalhes | null>(null)
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)
const isUpdatingStatus = ref(false) // For loading state on status update
const newStatus = ref<number | null>(null) // Ref for the new status value

const fetchPedidoDetalhes = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const pedidoId = route.params.id
    if (!pedidoId) {
      throw new Error('ID do pedido não fornecido.')
    }
    // Assuming payload is nested in 'data' based on user's feedback
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

onMounted(fetchPedidoDetalhes)

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

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

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
      // If API response is nested { data: { status: ... } }
      pedido.value.status = response.data?.data?.status !== undefined ? response.data.data.status : newStatus.value;
      // If API response is direct { status: ... }
      // pedido.value.status = response.data?.status !== undefined ? response.data.status : newStatus.value;
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
            <Select v-model="newStatus" class="w-[150px]">
              <SelectTrigger class="h-10 rounded-lg border-dashed border-primary/30">
                <SelectValue :placeholder="getStatusLabel(pedido.status)" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="1">ABERTO</SelectItem>
                <SelectItem value="2">PENDENTE</SelectItem>
                <SelectItem value="4">CONCLUÍDO</SelectItem>
                <SelectItem value="3">CANCELADO</SelectItem>
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
            <p class="font-bold text-sm">{{ pedido.nome_fantasia || pedido.razao_social }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">Responsável</p>
            <p class="font-bold text-sm">{{ pedido.responsavel }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">CPF/CNPJ</p>
            <p class="font-bold text-sm">{{ pedido.cpf_cnpj }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">Email</p>
            <p class="font-bold text-sm">{{ pedido.email }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">Contato</p>
            <p class="font-bold text-sm">{{ pedido.contato_1 }} <span v-if="pedido.contato_2">/ {{ pedido.contato_2 }}</span></p>
          </div>
          <div>
            <p class="text-xs font-semibold text-muted-foreground">Endereço</p>
            <p class="font-bold text-sm">{{ pedido.endereco }}, {{ pedido.bairro }}</p>
            <p class="font-bold text-sm">{{ pedido.cidade }} - {{ pedido.estado }}, {{ pedido.cep }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Itens do Pedido Card -->
      <Card class="lg:col-span-3 rounded-2xl border shadow-sm bg-card overflow-hidden">
        <CardHeader class="border-b bg-muted/20 px-8 py-6">
          <CardTitle class="text-lg font-bold flex items-center gap-2">
            <Package class="w-5 h-5 text-primary" /> Itens do Pedido
          </CardTitle>
          <CardDescription class="text-xs font-medium mt-1">Lista de produtos neste pedido</CardDescription>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>ID</TableHead>
                <TableHead>Produto</TableHead>
                <TableHead>Referência</TableHead>
                <TableHead class="text-center">Qtd</TableHead>
                <TableHead class="text-right">Valor Unitário</TableHead>
                <TableHead class="text-right">Total Item</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in pedido.itens" :key="item.id_item_pedido">
                <TableCell>#{{ item.id_item_pedido }}</TableCell>
                <TableCell class="font-medium">{{ item.produto_nome }}</TableCell>
                <TableCell>{{ item.referencia }}</TableCell>
                <TableCell class="text-center">{{ item.quantidade }}</TableCell>
                <TableCell class="text-right">{{ formatCurrency(item.valor_unitario) }}</TableCell>
                <TableCell class="text-right">{{ formatCurrency(item.total_item) }}</TableCell>
              </TableRow>
              <TableRow v-if="pedido.itens.length === 0">
                <TableCell colspan="6" class="text-center py-10 text-muted-foreground">Nenhum item neste pedido.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
