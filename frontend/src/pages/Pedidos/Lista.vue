<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Search, Filter, ShoppingBag, MoreHorizontal, FileText, Trash2, Loader2, AlertCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
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
  razao_social: string // Assuming client name from API
  nome_fantasia: string // Assuming client name from API
  total_pedido: number
  status: number // 1: ABERTO, 2: PENDENTE, 3: CANCELADO, 4: CONCLUÍDO
  data_pedido: string // Assuming a date string
  items_count: number // Assuming API returns items count
}

const pedidos = ref<Pedido[]>([])
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)
const router = useRouter() // Add router instance

const goToPedidoDetalhes = (id: number) => {
  router.push({ name: 'pedido-detalhes', params: { id: id.toString() } })
}

const fetchPedidos = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const response = await apiClient.get('/api/pedidos')
    pedidos.value = response.data
  } catch (error: any) {
    errorMessage.value = 'Erro ao carregar pedidos.'
    console.error('Failed to fetch pedidos:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchPedidos)

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
        />
      </div>
      
      <div class="flex gap-3 w-full md:w-auto">
        <Select>
          <SelectTrigger class="w-full md:w-56 bg-card border h-12 rounded-xl text-xs font-semibold focus:ring-primary">
            <SelectValue placeholder="TODOS STATUS" />
          </SelectTrigger>
          <SelectContent class="rounded-xl border shadow-xl">
            <SelectItem value="todos" class="text-xs font-bold text-left">TODOS STATUS</SelectItem>
            <SelectItem value="aberto" class="text-xs font-bold text-left">ABERTO</SelectItem>
            <SelectItem value="pendente" class="text-xs font-bold text-left">PENDENTE</SelectItem>
            <SelectItem value="concluido" class="text-xs font-bold text-left">CONCLUÍDO</SelectItem>
            <SelectItem value="cancelado" class="text-xs font-bold text-left">CANCELADO</SelectItem>
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
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5 text-center">Itens</TableHead>
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
                <span class="font-bold text-sm tracking-tight text-foreground/80">{{ pedido.nome_fantasia || pedido.razao_social }}</span>
                <ShoppingBag class="w-3.5 h-3.5 text-primary opacity-0 group-hover:opacity-100 transition-all" />
              </div>
            </TableCell>
            <TableCell class="py-5 text-center font-bold text-xs text-muted-foreground">{{ pedido.items_count }}</TableCell>
            <TableCell class="py-5 text-xs font-semibold text-muted-foreground">{{ pedido.data_pedido }}</TableCell>
            <TableCell class="py-5 font-bold text-base text-primary">R$ {{ pedido.total_pedido.toFixed(2).replace('.', ',') }}</TableCell>
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
                    <FileText class="w-4 h-4 mr-2 opacity-50" />
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
      <div class="flex gap-2 p-1 bg-card border rounded-xl shadow-sm">
        <Button v-for="i in 3" :key="i" 
                :variant="i === 1 ? 'default' : 'ghost'"
                size="sm"
                class="w-9 h-9 rounded-lg font-bold text-xs">
          {{ i }}
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "@/style.css";
</style>
