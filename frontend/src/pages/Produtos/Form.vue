<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ArrowLeft, Package, Save, Loader2, AlertCircle, Pencil } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import apiClient from '@/lib/axios'

// Define interface for Product data, mimicking API response structure
interface Produto {
  id_produto?: number // Optional for create mode
  referencia: string
  produto: string
  valor_unt_norte: string
  valor_unt_norde: string
  status: number // 1 for active, 0 for inactive
}

const route = useRoute()
const router = useRouter()

const produto = ref<Produto>({
  referencia: '',
  produto: '',
  valor_unt_norte: '0.00',
  valor_unt_norde: '0.00',
  status: 1, // Default to active
})

const isEditing = computed(() => route.params.id !== undefined)
const pageTitle = computed(() => isEditing.value ? 'Editar Produto' : 'Novo Produto')
const pageDescription = computed(() => isEditing.value ? 'Modifique os dados do produto.' : 'Preencha os campos para registrar um novo produto.')

const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref<string | null>(null)

const fetchProdutoDetalhes = async () => {
  isLoading.value = true
  const produtoId = route.params.id
  if (isEditing.value && produtoId) {
    try {
      // Assume API endpoint for getting single product details is GET /api/produtos/{id}
      const response = await apiClient.get(`/api/produtos/${produtoId}`)
      // Assuming response structure is { success: true, data: { produto: {...} } } or similar
      // Adjust based on actual API response structure, prioritizing nested data if user confirmed pattern
      produto.value = response.data?.data?.produto || response.data?.produto || response.data
      if (!produto.value) throw new Error("Formato de resposta inesperado");
      // Ensure status is handled correctly if API returns it as string or other type
      produto.value.status = Number(produto.value.status) || 1;
    } catch (error: any) {
      errorMessage.value = 'Erro ao carregar detalhes do produto.'
      console.error('Failed to fetch produto details:', error)
    } finally {
      isLoading.value = false
    }
  } else {
    isLoading.value = false // Not loading if it'0s a new product
  }
}

onMounted(() => {
  if (isEditing.value) {
    fetchProdutoDetalhes()
  }
})

const saveProduto = async () => {
  isSaving.value = true
  errorMessage.value = null

  // Basic validation
  if (!produto.value.referencia || !produto.value.produto || !produto.value.valor_unt_norte) {
    errorMessage.value = 'Referência, Nome do Produto e Preço (Norte) são obrigatórios.'
    isSaving.value = false
    return
  }

  try {
    if (isEditing.value && produto.value.id_produto) {
      // Update existing product
      await apiClient.put(`/api/produtos/${produto.value.id_produto}`, produto.value)
    } else {
      // Create new product
      await apiClient.post('/api/produtos', produto.value)
    }
    router.push({ name: 'produtos' }) // Redirect to list page on success
  } catch (error: any) {
    const responseError = error.response?.data?.message || error.response?.data?.error || 'Erro ao salvar produto.'
    errorMessage.value = responseError
    console.error('Failed to save produto:', error)
  } finally {
    isSaving.value = false
  }
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
        <h1 class="text-3xl font-bold tracking-tight">{{ pageTitle }}</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">{{ pageDescription }}</p>
      </div>
    </div>

    <div v-if="errorMessage" class="bg-destructive/10 border border-destructive/20 text-destructive p-4 rounded-xl flex items-center gap-3">
      <AlertCircle class="w-5 h-5" />
      <p class="text-sm font-bold">{{ errorMessage }}</p>
    </div>

    <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
      <CardHeader class="border-b bg-muted/20 px-8 py-6">
        <CardTitle class="text-lg font-bold flex items-center gap-2">
          <Package class="w-5 h-5 text-primary" /> Detalhes do Produto
        </CardTitle>
      </CardHeader>
      <CardContent class="p-8">
        <div v-if="isLoading && isEditing" class="flex justify-center items-center py-10">
            <Loader2 class="w-8 h-8 animate-spin text-primary" />
            <p class="ml-3 text-sm text-muted-foreground">Carregando dados do produto...</p>
        </div>
        <form v-else @submit.prevent="saveProduto" class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Product Info -->
          <div class="space-y-1.5 md:col-span-2">
            <Label class="text-xs font-semibold text-foreground/70">Nome do Produto *</Label>
            <Input v-model="produto.produto" required placeholder="Nome completo do produto" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Referência *</Label>
            <Input v-model="produto.referencia" required placeholder="Código de referência" class="h-11 rounded-lg" />
          </div>

          <!-- Pricing -->
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Valor Unitário (Norte) *</Label>
            <Input type="number" v-model="produto.valor_unt_norte" required placeholder="Ex: 27.50" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Valor Unitário (Nordeste)</Label>
            <Input type="number" v-model="produto.valor_unt_norde" placeholder="Ex: 23.00" class="h-11 rounded-lg" />
          </div>

          <!-- Status -->
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Status</Label>
            <Select v-model="produto.status">
              <SelectTrigger class="w-full bg-background border h-11 rounded-lg">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="1">Ativo</SelectItem>
                <SelectItem :value="0">Inativo</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="md:col-span-3 flex items-center justify-end gap-4 pt-6">
            <Button type="submit" class="h-12 w-full md:w-auto bg-primary hover:bg-primary/90 text-primary-foreground font-black text-base rounded-xl shadow-lg shadow-primary/20 gap-3" :disabled="isSaving">
              <template v-if="isSaving">
                <Loader2 class="w-5 h-5 animate-spin" /> Salvando...
              </template>
              <template v-else>
                <Save class="w-5 h-5" /> Salvar Produto
              </template>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
