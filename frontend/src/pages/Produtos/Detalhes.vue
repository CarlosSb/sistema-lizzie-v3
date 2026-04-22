<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Package, Loader2, AlertCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import apiClient from '@/lib/axios'

interface ProdutoDetalhes {
  id_produto: number
  referencia: string
  produto: string
  valor_unt_norte: string
  valor_unt_norde: string
  status: number
}

const route = useRoute()
const router = useRouter()

const produto = ref<ProdutoDetalhes | null>(null)
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)

const fetchProdutoDetalhes = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const produtoId = route.params.id
    if (!produtoId) {
      throw new Error('ID do produto não fornecido.')
    }
    const response = await apiClient.get(`/api/produtos/${produtoId}`)
    produto.value = response.data?.data || null
    if (!produto.value) throw new Error('Formato de resposta inesperado')
  } catch (error: any) {
    errorMessage.value = 'Erro ao carregar detalhes do produto.'
    console.error('Failed to fetch produto details:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchProdutoDetalhes)

const getStatusLabel = (status: number) => {
  switch (status) {
    case 1: return 'Ativo'
    case 0: return 'Inativo'
    default: return 'Desconhecido'
  }
}

const getStatusClass = (status: number) => {
  return status === 1
    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-200 dark:border-emerald-500/30'
    : 'bg-rose-500/10 text-rose-600 border-rose-200 dark:border-rose-500/30'
}

const formatCurrency = (value: string | number) => {
  // Ensure value is a number before formatting
  const numericValue = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(numericValue)) {
    return 'R$ 0,00'; // Handle potential NaN values gracefully
  }
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(numericValue);
}
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <Button variant="ghost" @click="router.push({ name: 'produtos' })" class="px-2">
        <ArrowLeft class="w-5 h-5 mr-2" />
        <span class="font-bold">Voltar aos Produtos</span>
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Detalhes do Produto</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">
          Informações completas do produto #{{ route.params.id }}
        </p>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center items-center h-64">
      <Loader2 class="w-10 h-10 animate-spin text-primary" />
      <p class="ml-3 text-lg text-muted-foreground">Carregando detalhes do produto...</p>
    </div>
    <div v-else-if="errorMessage" class="text-center text-red-500 p-4 border border-red-300 rounded-lg">
      <AlertCircle class="inline-block w-5 h-5 mr-2" />
      {{ errorMessage }}
      <Button variant="link" @click="fetchProdutoDetalhes">Tentar novamente</Button>
    </div>

    <div v-else-if="!produto" class="text-center text-muted-foreground p-4 border rounded-lg">
      <AlertCircle class="inline-block w-5 h-5 mr-2" />
      Produto não encontrado.
    </div>

    <Card v-else class="rounded-2xl border shadow-sm bg-card overflow-hidden">
      <CardHeader class="border-b bg-muted/20 px-8 py-6">
        <CardTitle class="text-lg font-bold flex items-center gap-2">
          <Package class="w-5 h-5 text-primary" /> Produto #{{ produto.id_produto }}
        </CardTitle>
        <CardDescription class="text-xs font-medium mt-1">Informações detalhadas do produto</CardDescription>
      </CardHeader>
      <CardContent class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-3">
          <p class="text-xs font-semibold text-muted-foreground">Nome do Produto</p>
          <p class="font-bold text-sm">{{ produto.produto }}</p>
        </div>
        <div class="space-y-3">
          <p class="text-xs font-semibold text-muted-foreground">Referência</p>
          <p class="font-bold text-sm">{{ produto.referencia }}</p>
        </div>
        <div class="space-y-3">
          <p class="text-xs font-semibold text-muted-foreground">Preço (Norte)</p>
          <p class="font-bold text-sm">{{ formatCurrency(parseFloat(produto.valor_unt_norte)) }}</p>
        </div>
        <div class="space-y-3">
          <p class="text-xs font-semibold text-muted-foreground">Preço (Nordeste)</p>
          <p class="font-bold text-sm">{{ formatCurrency(parseFloat(produto.valor_unt_norde)) }}</p>
        </div>
        <div class="space-y-3">
          <p class="text-xs font-semibold text-muted-foreground">Status</p>
          <Badge 
            variant="outline" 
            :class="['rounded-lg px-3 py-0.5 text-[10px] font-bold tracking-wide uppercase border', getStatusClass(produto.status)]"
          >
            {{ getStatusLabel(produto.status) }}
          </Badge>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
