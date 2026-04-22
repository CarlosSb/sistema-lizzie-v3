<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { 
  ArrowLeft, 
  Search, 
  User, 
  ShoppingCart, 
  Save, 
  Plus, 
  Trash2, 
  Loader2, 
  AlertCircle 
} from 'lucide-vue-next'
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import apiClient from '@/lib/axios'

interface Cliente {
  id_cliente: number
  responsavel: string
  nome_fantasia: string
  cpf_cnpj: string
  status: number
}

interface Produto {
  id_produto: number
  referencia: string
  produto: string
  valor_unt_norte: string
  valor_unt_norde: string
  status: number
}

interface PedidoItem {
  id_produto: number
  produto_nome: string
  referencia: string
  quantidade: number
  valor_unitario: number
  total: number
}

const router = useRouter()

// Form Data
const selectedClientId = ref<string>('')
const obsPedido = ref('')
const obsEntrega = ref('')
const formaPag = ref('DINHEIRO')
const items = ref<PedidoItem[]>([])

// Support Data
const clientes = ref<Cliente[]>([])
const produtos = ref<Produto[]>([])
const isLoadingClientes = ref(false)
const isLoadingProdutos = ref(false)
const isSaving = ref(false)
const errorMessage = ref<string | null>(null)

// Product Selection State
const selectedProductId = ref<string>('')
const itemQuantity = ref(1)

const fetchClientes = async () => {
  isLoadingClientes.value = true
  try {
    const response = await apiClient.get('/api/clientes')
    // Adjusting to expect payload potentially under response.data.data or directly in response.data
    clientes.value = response.data?.data?.clientes || response.data?.clientes || response.data || []
  } catch (error) {
    console.error('Erro ao buscar clientes', error)
  } finally {
    isLoadingClientes.value = false
  }
}

const fetchProdutos = async () => {
  isLoadingProdutos.value = true
  try {
    const response = await apiClient.get('/api/produtos')
    produtos.value = response.data?.data?.produtos || response.data?.produtos || response.data || []
  } catch (error) {
    console.error('Erro ao buscar produtos', error)
  } finally {
    isLoadingProdutos.value = false
  }
}

onMounted(() => {
  fetchClientes()
  fetchProdutos()
})

const selectedProductData = computed(() => {
  return produtos.value.find(p => p.id_produto.toString() === selectedProductId.value)
})

const updateItemTotal = (item: PedidoItem) => {
  // Ensure price and quantity are valid numbers before calculation
  const price = parseFloat(item.valor_unitario.toString()) || 0;
  const quantity = Number(item.quantidade) || 0;

  if (!isNaN(price) && !isNaN(quantity) && quantity > 0) {
    item.total = price * quantity;
  } else {
    item.total = 0; // Reset total if invalid input
  }
};

const addItem = () => {
  if (!selectedProductId.value) {
    errorMessage.value = 'Selecione um produto.'
    return
  }
  
  const product = selectedProductData.value
  if (!product) {
    errorMessage.value = 'Produto selecionado inválido.'
    return
  }

  const quantity = Number(itemQuantity.value);
  // Prioritize valor_unt_norde, then valor_unt_norte, default to 0 if invalid
  const price = parseFloat(product.valor_unt_norde) || parseFloat(product.valor_unt_norte) || 0;

  if (isNaN(price) || isNaN(quantity) || quantity <= 0) {
    errorMessage.value = 'Por favor, insira uma quantidade válida e verifique o preço do produto.'
    return
  }

  const existingItemIndex = items.value.findIndex(item => item.id_produto === product.id_produto)
  
  if (existingItemIndex > -1) {
    items.value[existingItemIndex].quantidade += quantity;
    // Recalculate total for existing item
    updateItemTotal(items.value[existingItemIndex]);
  } else {
    items.value.push({
      id_produto: product.id_produto,
      produto_nome: product.produto,
      referencia: product.referencia,
      quantidade: quantity,
      valor_unitario: price,
      total: price * quantity // Calculate total for new item
    })
  }

  // Reset selection
  selectedProductId.value = ''
  itemQuantity.value = 1
}

const removeItem = (index: number) => {
  items.value.splice(index, 1)
}

const totalPedido = computed(() => {
  return items.value.reduce((acc, item) => acc + item.total, 0)
})

const savePedido = async () => {
  if (!selectedClientId.value || items.value.length === 0) {
    errorMessage.value = 'Selecione um cliente e adicione pelo menos um item.'
    return
  }

  isSaving.value = true
  errorMessage.value = null

  try {
    const payload = {
      id_cliente: selectedClientId.value,
      obs_pedido: obsPedido.value,
      obs_entrega: obsEntrega.value,
      forma_pag: formaPag.value,
      itens: items.value.map(item => ({
        id_produto: item.id_produto,
        quantidade: item.quantidade,
        valor_unitario: item.valor_unitario
      }))
    }

    const response = await apiClient.post('/api/pedidos', payload)
    router.push({ name: 'pedido-detalhes', params: { id: response.data.id_pedido } })
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Erro ao salvar o pedido.'
  } finally {
    isSaving.value = false
  }
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <Button variant="ghost" @click="router.push({ name: 'pedidos' })" class="px-2">
        <ArrowLeft class="w-5 h-5 mr-2" />
        <span class="font-bold">Cancelar e Voltar</span>
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Novo Pedido</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">Preencha os dados para gerar uma nova venda.</p>
      </div>
    </div>

    <div v-if="errorMessage" class="bg-destructive/10 border border-destructive/20 text-destructive p-4 rounded-xl flex items-center gap-3">
      <AlertCircle class="w-5 h-5" />
      <p class="text-sm font-bold">{{ errorMessage }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Pedido Data -->
      <div class="lg:col-span-2 space-y-8">
        <!-- Cliente Selection -->
        <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
          <CardHeader class="border-b bg-muted/20 px-8 py-6">
            <CardTitle class="text-lg font-bold flex items-center gap-2">
              <User class="w-5 h-5 text-primary" /> Seleção de Cliente
            </CardTitle>
          </CardHeader>
          <CardContent class="p-6">
            <div class="grid w-full items-center gap-1.5">
              <Label class="text-xs font-semibold text-foreground/70 mb-1">Cliente</Label>
              <Select v-model="selectedClientId">
                <SelectTrigger class="w-full bg-background border h-11 rounded-lg">
                  <SelectValue placeholder="Selecione um cliente..." />
                </SelectTrigger>
                <SelectContent class="max-h-60 overflow-y-auto">
                  <SelectItem v-for="cliente in clientes" :key="cliente.id_cliente" :value="cliente.id_cliente.toString()">
                    {{ cliente.nome_fantasia || cliente.razao_social }} ({{ cliente.cpf_cnpj }})
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="isLoadingClientes" class="text-[10px] text-muted-foreground animate-pulse">Carregando clientes...</p>
            </div>
          </CardContent>
        </Card>

        <!-- Items Selection -->
        <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
          <CardHeader class="border-b bg-muted/20 px-8 py-6">
            <CardTitle class="text-lg font-bold flex items-center gap-2">
              <Package class="w-5 h-5 text-primary" /> Itens do Pedido
            </CardTitle>
          </CardHeader>
          <CardContent class="p-6 space-y-6">
            <!-- Add Item Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-muted/30 p-4 rounded-xl border border-dashed">
              <div class="md:col-span-2 space-y-1.5 text-left">
                <Label class="text-xs font-semibold text-foreground/70">Produto</Label>
                <Select v-model="selectedProductId">
                  <SelectTrigger class="w-full bg-background border h-10 rounded-lg">
                    <SelectValue placeholder="Escolha um produto..." />
                  </SelectTrigger>
                  <SelectContent class="max-h-60">
                    <SelectItem v-for="prod in produtos" :key="prod.id_produto" :value="prod.id_produto.toString()">
                      {{ prod.produto }} - {{ prod.referencia }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5 text-left">
                <Label class="text-xs font-semibold text-foreground/70">Quantidade</Label>
                <Input type="number" v-model.number="itemQuantity" min="1" class="h-10 rounded-lg" />
              </div>
              <Button @click="addItem" variant="secondary" class="h-10 font-bold gap-2">
                <Plus class="w-4 h-4" /> Add
              </Button>
            </div>

            <!-- Items Table -->
            <div class="border rounded-xl overflow-hidden">
              <Table>
                <TableHeader class="bg-muted/50">
                  <TableRow>
                    <TableHead>Produto</TableHead>
                    <TableHead class="text-center">Qtd</TableHead>
                    <TableHead class="text-right">Unitário</TableHead>
                    <TableHead class="text-right">Total</TableHead>
                    <TableHead></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(item, index) in items" :key="item.id_produto">
                    <TableCell>
                      <div class="flex flex-col text-left">
                        <span class="font-bold text-sm">{{ item.produto_nome }}</span>
                        <span class="text-[10px] text-muted-foreground">REF: {{ item.referencia }}</span>
                      </div>
                    </TableCell>
                    <TableCell class="text-center">
                      <Input type="number" v-model.number="item.quantidade" min="1" class="h-8 w-16 text-center p-0 border-none focus:ring-0" @input="updateItemTotal(item)" />
                    </TableCell>
                    <TableCell class="text-right">{{ formatCurrency(item.valor_unitario) }}</TableCell>
                    <TableCell class="text-right font-bold">{{ formatCurrency(item.total) }}</TableCell>
                    <TableCell class="text-right">
                      <Button variant="ghost" size="icon" @click="removeItem(index)" class="text-destructive hover:text-destructive hover:bg-destructive/10">
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                  <TableRow v-if="items.length === 0">
                    <TableCell colspan="5" class="text-center py-10 text-muted-foreground">Nenhum item adicionado.</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Pedido Summary & Actions -->
      <div class="space-y-6">
        <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
          <CardHeader class="border-b bg-muted/20 px-8 py-6 text-left">
            <CardTitle class="text-lg font-bold">Resumo</CardTitle>
          </CardHeader>
          <CardContent class="p-6 space-y-6">
            <div class="space-y-4">
              <div class="flex justify-between items-center">
                <span class="text-sm text-muted-foreground">Subtotal</span>
                <span class="font-bold">{{ formatCurrency(totalPedido) }}</span>
              </div>
              <div class="border-t pt-4 flex justify-between items-center">
                <span class="font-bold text-lg">Total</span>
                <span class="font-black text-2xl text-primary">{{ formatCurrency(totalPedido) }}</span>
              </div>
            </div>

            <div class="space-y-4 pt-4 border-t text-left">
              <div class="space-y-1.5">
                <Label class="text-xs font-semibold text-foreground/70">Forma de Pagamento</Label>
                <Select v-model="formaPag">
                  <SelectTrigger class="w-full bg-background border h-10 rounded-lg">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="DINHEIRO">Dinheiro</SelectItem>
                    <SelectItem value="PIX">PIX</SelectItem>
                    <SelectItem value="BOLETO">Boleto</SelectItem>
                    <SelectItem value="CARTÃO">Cartão de Crédito</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-1.5">
                <Label class="text-xs font-semibold text-foreground/70">Obs. do Pedido</Label>
                <Input v-model="obsPedido" placeholder="Alguma observação..." class="rounded-lg" />
              </div>

              <div class="space-y-1.5">
                <Label class="text-xs font-semibold text-foreground/70">Obs. de Entrega</Label>
                <Input v-model="obsEntrega" placeholder="Detalhes da entrega..." class="rounded-lg" />
              </div>
            </div>

            <Button 
              @click="savePedido" 
              class="w-full h-12 bg-primary hover:bg-primary/90 text-primary-foreground font-black text-base rounded-xl shadow-lg shadow-primary/20 gap-3"
              :disabled="isSaving || items.length === 0"
            >
              <template v-if="isSaving">
                <Loader2 class="w-5 h-5 animate-spin" /> Salvando...
              </template>
              <template v-else>
                <Save class="w-5 h-5" /> CONCLUIR PEDIDO
              </template>
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
