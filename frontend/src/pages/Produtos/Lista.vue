<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Search, Package, MoreHorizontal, Trash2, Loader2, AlertCircle, FileText, Pencil } from 'lucide-vue-next' // Added Pencil for Edit
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
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

interface Produto {
  id_produto: number
  referencia: string
  produto: string
  valor_unt_norte: string
  valor_unt_norde: string
  status: number
}

const produtos = ref<Produto[]>([])
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)
const router = useRouter() // Initialize router

const goToProdutoDetalhes = (id: number) => {
  router.push({ name: 'produto-detalhes', params: { id: id.toString() } })
}

const goToEditProduto = (id: number) => {
  router.push({ name: 'produto-editar', params: { id: id.toString() } })
}

const deleteProduto = async (id: number) => {
  if (window.confirm('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.')) {
    try {
      await apiClient.delete(`/api/produtos/${id}`)
      // Refresh the list after deletion
      fetchProdutos()
    } catch (error: any) {
      errorMessage.value = 'Erro ao excluir produto.'
      console.error('Failed to delete produto:', error)
    }
  }
}

const fetchProdutos = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const response = await apiClient.get('/api/produtos')
    // Assuming payload is nested in 'data' based on user's feedback about consistent structure
    produtos.value = response.data?.data?.produtos || response.data?.produtos || response.data || []
  } catch (error: any) {
    errorMessage.value = 'Erro ao carregar produtos.'
    console.error('Failed to fetch produtos:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchProdutos)

const getStatusClass = (status: number) => {
  return status === 1 
    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-200 dark:border-emerald-500/30' 
    : 'bg-rose-500/10 text-rose-600 border-rose-200 dark:border-rose-500/30'
}
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Produtos</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">Gerencie o catálogo de produtos.</p>
      </div>
      
      <Button @click="router.push({ name: 'produto-novo' })" class="bg-primary hover:bg-primary/90 text-primary-foreground font-bold rounded-xl h-11 px-6 shadow-lg shadow-primary/20 flex gap-2">
        <Plus class="w-4 h-4" />
        Novo Produto
      </Button>
    </div>

    <div class="relative flex-1 group w-full text-left">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors z-10" />
        <Input 
          type="text" 
          placeholder="Pesquisar produto por nome ou referência..." 
          class="w-full bg-card border pl-11 h-12 text-sm font-medium focus-visible:ring-primary rounded-xl" 
        />
    </div>

    <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
      <div v-if="isLoading" class="flex justify-center items-center py-10">
          <Loader2 class="w-8 h-8 animate-spin text-primary" />
          <p class="ml-3 text-sm text-muted-foreground">Carregando produtos...</p>
      </div>
      <div v-else-if="errorMessage" class="text-center text-red-500 p-4">
          <AlertCircle class="inline-block w-5 h-5 mr-2" />
          {{ errorMessage }}
      </div>
      <Table v-else>
        <TableHeader class="bg-muted/30">
          <TableRow class="hover:bg-transparent">
            <TableHead class="w-[80px] text-xs font-bold uppercase tracking-wider py-5 px-8">ID</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Referência</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Produto</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Preço Norte</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Preço Nordeste</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Status</TableHead>
            <TableHead class="text-right py-5 px-8"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="produto in produtos" :key="produto.id_produto" class="hover:bg-accent/30 transition-colors group">
            <TableCell class="py-5 px-8 font-bold text-sm">#{{ produto.id_produto }}</TableCell>
            <TableCell class="py-5 font-bold text-sm text-muted-foreground">{{ produto.referencia }}</TableCell>
            <TableCell class="py-5">
              <div class="flex items-center gap-3 text-left">
                <span class="font-bold text-sm tracking-tight text-foreground/80">{{ produto.produto }}</span>
                <Package class="w-3.5 h-3.5 text-primary opacity-0 group-hover:opacity-100 transition-all" />
              </div>
            </TableCell>
            <TableCell class="py-5 font-bold text-sm text-primary">R$ {{ produto.valor_unt_norte }}</TableCell>
            <TableCell class="py-5 font-bold text-sm text-primary">R$ {{ produto.valor_unt_norde }}</TableCell>
            <TableCell class="py-5">
              <Badge 
                variant="outline" 
                :class="['rounded-lg px-3 py-0.5 text-[10px] font-bold tracking-wide uppercase border', getStatusClass(produto.status)]"
              >
                {{ produto.status === 1 ? 'ATIVO' : 'INATIVO' }}
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
                  <DropdownMenuItem @click="goToProdutoDetalhes(produto.id_produto)" class="text-xs font-bold py-2.5 cursor-pointer px-4 text-left">
                    Ver Detalhes
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="goToEditProduto(produto.id_produto)" class="text-xs font-bold py-2.5 cursor-pointer px-4 text-left">
                    Editar
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="deleteProduto(produto.id_produto)" class="text-xs font-bold py-2.5 cursor-pointer px-4 text-destructive focus:text-destructive text-left">
                    <Trash2 class="w-4 h-4 mr-2 opacity-50" />
                    Excluir
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
          </TableRow>
          <TableRow v-if="produtos.length === 0">
              <TableCell colspan="7" class="text-center py-10 text-muted-foreground">Nenhum produto encontrado.</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </Card>
  </div>
</template>
