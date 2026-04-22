<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Search, Filter, ShoppingBag, MoreHorizontal, FileText, Trash2, Loader2, AlertCircle } from 'lucide-vue-next'
import { watchDebounced } from '@vueuse/core'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import PaginationBar from '@/components/PaginationBar.vue'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Card } from '@/components/ui/card'
import apiClient from '@/lib/axios'

interface Pedido {
  id_pedido: number
  razao_social: string | null
  nome_vendedor: string | null
  total_pedido: number
  status: number // 1: ABERTO, 2: PENDENTE, 3: CANCELADO, 4: CONCLUÍDO
  data_pedido: string // Assuming a date string
  items_count?: number
}

const pedidos = ref<Pedido[]>([])
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)
const router = useRouter() // Add router instance

const search = ref('')
const statusFilter = ref<'all' | '1' | '2' | '3' | '4'>('all')
const page = ref(1)
const perPage = ref(15)
const pagination = ref<{ current_page: number; last_page: number; per_page: number; total: number } | null>(null)

const goToPedidoDetalhes = (id: number) => {
  router.push({ name: 'pedido-detalhes', params: { id: id.toString() } })
}

const normalizePedidos = (payload: any): Pedido[] => {
  const list = Array.isArray(payload) ? payload : []
  return list.map((p: any) => ({
    id_pedido: Number(p?.id_pedido),
    razao_social: p?.razao_social ?? null,
    nome_vendedor: p?.nome_vendedor ?? null,
    total_pedido: Number(p?.total_pedido) || 0,
    status: Number(p?.status) || 0,
    data_pedido: String(p?.data_pedido ?? ''),
    items_count: p?.items_count !== undefined ? Number(p.items_count) : undefined,
  }))
}

const formatMoney = (value: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0)

const fetchPedidos = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const response = await apiClient.get('/api/pedidos', {
      params: {
        search: search.value || undefined,
        status: statusFilter.value === 'all' ? undefined : Number(statusFilter.value),
        page: page.value,
        per_page: perPage.value,
      },
    })
    const data = response.data?.data
    pedidos.value = normalizePedidos(data)
    pagination.value = response.data?.pagination || null
  } catch (error: any) {
    errorMessage.value = 'Erro ao carregar pedidos.'
    console.error('Failed to fetch pedidos:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchPedidos)

watchDebounced(
  search,
  () => {
    if (page.value === 1) {
      fetchPedidos()
    } else {
      page.value = 1
    }
  },
  { debounce: 350, maxWait: 1200 }
)

watch(statusFilter, () => {
  if (page.value === 1) {
    fetchPedidos()
  } else {
    page.value = 1
  }
})

watch(page, () => {
  fetchPedidos()
})

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
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Pedidos</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">Gerencie as ordens de venda da loja.</p>
      </div>
      
      <Button @click="router.push({ name: 'pedido-novo' })" class="bg-primary hover:bg-primary/90 text-primary-foreground font-bold rounded-xl h-11 px-6 shadow-lg shadow-primary/20 flex gap-2">
        <Plus class="w-4 h-4" />
        Novo Pedido
      </Button>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col md:flex-row gap-4 items-center">
      <div class="relative flex-1 group w-full text-left">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors z-10" />
        <Input 
          type="text" 
          placeholder="Pesquisar pedido ou cliente..." 
          class="w-full bg-card border pl-11 h-12 text-sm font-medium focus-visible:ring-primary rounded-xl" 
          v-model="search"
          :disabled="isLoading"
        />
      </div>
      
      <div class="flex gap-3 w-full md:w-auto">
        <Select v-model="statusFilter">
          <SelectTrigger class="w-full md:w-56 bg-card border h-12 rounded-xl text-xs font-semibold focus:ring-primary">
            <SelectValue placeholder="TODOS STATUS" />
          </SelectTrigger>
          <SelectContent class="rounded-xl border shadow-xl">
            <SelectItem value="all" class="text-xs font-bold text-left">TODOS STATUS</SelectItem>
            <SelectItem value="1" class="text-xs font-bold text-left">ABERTO</SelectItem>
            <SelectItem value="2" class="text-xs font-bold text-left">PENDENTE</SelectItem>
            <SelectItem value="4" class="text-xs font-bold text-left">CONCLUÍDO</SelectItem>
            <SelectItem value="3" class="text-xs font-bold text-left">CANCELADO</SelectItem>
          </SelectContent>
        </Select>

        <Button variant="outline" size="icon" class="h-12 w-12 border rounded-xl hover:bg-accent transition-all">
          <Filter class="w-4 h-4" />
        </Button>
      </div>
    </div>

    <!-- Table -->
    <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
      <div v-if="isLoading" class="flex justify-center items-center py-10">
          <Loader2 class="w-8 h-8 animate-spin text-primary" />
          <p class="ml-3 text-sm text-muted-foreground">Carregando pedidos...</p>
      </div>
      <div v-else-if="errorMessage" class="text-center text-red-500 p-4">
          <AlertCircle class="inline-block w-5 h-5 mr-2" />
          {{ errorMessage }}
      </div>
      <Table v-else>
        <TableHeader class="bg-muted/30">
          <TableRow class="hover:bg-transparent">
            <TableHead class="w-[100px] text-xs font-bold uppercase tracking-wider py-5 px-8">ID</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Cliente</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5 text-center">
            <div class="flex items-center gap-1">
              <ShoppingBag class="w-3.5 h-3.5" />
              Itens
            </div>
          </TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Data</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Total</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Status</TableHead>
            <TableHead class="text-right py-5 px-8"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="pedido in pedidos" :key="pedido.id_pedido" class="hover:bg-accent/30 transition-colors group">
            <TableCell class="py-5 px-8 font-bold text-sm">#{{ pedido.id_pedido }}</TableCell>
            <TableCell class="py-5">
              <div class="flex items-center gap-3 text-left">
                <span class="font-bold text-sm tracking-tight text-foreground/80">{{ pedido.razao_social || '-' }}</span>
                <ShoppingBag class="w-3.5 h-3.5 text-primary opacity-0 group-hover:opacity-100 transition-all" />
              </div>
            </TableCell>
            <TableCell class="py-5 text-center font-bold text-xs text-muted-foreground">{{ pedido.items_count ?? '-' }}</TableCell>
            <TableCell class="py-5 text-xs font-semibold text-muted-foreground">{{ pedido.data_pedido }}</TableCell>
            <TableCell class="py-5 pr-6 font-bold text-base text-primary">{{ formatMoney(pedido.total_pedido) }}</TableCell>
            <TableCell class="py-5">
              <Badge 
                variant="outline" 
                :class="['rounded-lg px-3 py-0.5 text-[10px] font-bold tracking-wide uppercase border', getStatusClass(pedido.status)]"
              >
                {{ getStatusLabel(pedido.status) }}
              </Badge>
            </TableCell>
            <TableCell class="py-5 px-8 text-right">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon" class="h-9 w-9 rounded-lg hover:bg-accent">
                    <MoreHorizontal class="w-4 h-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="rounded-xl border shadow-xl w-48">
                  <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-wider py-2 opacity-50 px-4 text-left">Ações</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem @click="goToPedidoDetalhes(pedido.id_pedido)" class="text-xs font-bold py-2.5 cursor-pointer px-4 text-left">
                    <FileText class="w-4 h-4 mr-2" />
                    Ver Detalhes
                  </DropdownMenuItem>
                  <DropdownMenuItem class="text-xs font-bold py-2.5 cursor-pointer px-4 text-destructive focus:text-destructive text-left text-left">
                    <Trash2 class="w-4 h-4 mr-2 opacity-50" />
                    Cancelar
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
          </TableRow>
          <TableRow v-if="pedidos.length === 0">
              <TableCell colspan="7" class="text-center py-10 text-muted-foreground">Nenhum pedido encontrado.</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </Card>

    <!-- Pagination -->
    <div class="flex justify-center pt-4">
      <PaginationBar v-model:page="page" :lastPage="pagination?.last_page || 1" :disabled="isLoading" />
    </div>
  </div>
</template>

<style scoped>
@reference "@/style.css";
</style>
