<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  ArrowLeft, 
  User, 
  Package,
  Save, 
  Plus, 
  Pencil,
  Trash2, 
  Loader2,
  AlertCircle
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
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
import { useAuthStore } from '@/stores/auth'

interface Cliente {
  id_cliente: number
  responsavel: string
  razao_social: string | null
  nome_fantasia: string | null
  cpf_cnpj: string
  status: number
}

interface Produto {
  id_produto: number
  referencia: string
  produto: string
  valor_unt_norte: number
  valor_unt_norde: number
  status: number
}

interface PedidoItem {
  id_produto: number
  produto_nome: string
  referencia: string
  regiao: 'nordeste' | 'norte'
  quantidades: Record<string, number>
  total_item: number
}

const router = useRouter()
const auth = useAuthStore()

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
const itemRegiao = ref<'nordeste' | 'norte'>('nordeste')
const itemQuantidades = ref<Record<string, number>>({})
const editingIndex = ref<number | null>(null)

const SIZE_KEYS = [
  'pp',
  'p',
  'm',
  'g',
  'u',
  'rn',
  'ida_1',
  'ida_2',
  'ida_3',
  'ida_4',
  'ida_6',
  'ida_8',
  'ida_10',
  'ida_12',
  'lisa',
] as const

const SIZE_GROUPS: Array<{ title: string; keys: Array<(typeof SIZE_KEYS)[number]> }> = [
  { title: 'Infantil', keys: ['pp', 'p', 'm', 'g', 'u', 'rn', 'lisa'] },
  { title: 'Idades', keys: ['ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12'] },
]

const sizeLabel = (key: (typeof SIZE_KEYS)[number]) => {
  if (key.startsWith('ida_')) return key.replace('ida_', '')
  return key.toUpperCase()
}

const resetItemForm = () => {
  selectedProductId.value = ''
  itemRegiao.value = 'nordeste'
  itemQuantidades.value = Object.fromEntries(SIZE_KEYS.map(k => [k, 0]))
  editingIndex.value = null
}

const fetchClientes = async () => {
  isLoadingClientes.value = true
  try {
    const response = await apiClient.get('/api/clientes')
    clientes.value = response.data?.data || []
  } catch (error) {
    console.error('Erro ao buscar clientes', error)
  } finally {
    isLoadingClientes.value = false
  }
}

const fetchProdutos = async () => {
  isLoadingProdutos.value = true
  try {
    const response = await apiClient.get('/api/produtos', { params: { status: 1, page: 1, per_page: 100 } })
    produtos.value = response.data?.data || []
  } catch (error) {
    console.error('Erro ao buscar produtos', error)
  } finally {
    isLoadingProdutos.value = false
  }
}

onMounted(() => {
  resetItemForm()
  fetchClientes()
  fetchProdutos()
})

const selectedProductData = computed(() => {
  return produtos.value.find(p => p.id_produto.toString() === selectedProductId.value)
})

const sumQuantidades = (q: Record<string, number>) => {
  return SIZE_KEYS.reduce((acc, key) => acc + (Number(q[key]) || 0), 0)
}

const getSizeBadges = (q: Record<string, number>) => {
  const list: Array<{ key: string; label: string; value: number }> = []
  for (const key of SIZE_KEYS) {
    const value = Number(q[key]) || 0
    if (value > 0) list.push({ key, label: sizeLabel(key), value })
  }
  return list
}

const getUnitPrice = (product: Produto, regiao: 'nordeste' | 'norte') => {
  return regiao === 'norte' ? Number(product.valor_unt_norte) || 0 : Number(product.valor_unt_norde) || 0
}

const calculateItemTotal = (product: Produto, regiao: 'nordeste' | 'norte', quantidades: Record<string, number>) => {
  const qty = sumQuantidades(quantidades)
  return qty * getUnitPrice(product, regiao)
}

const selectedProduct = computed(() => produtos.value.find(p => String(p.id_produto) === selectedProductId.value) || null)
const itemPieces = computed(() => sumQuantidades(itemQuantidades.value))
const itemUnitPrice = computed(() => {
  if (!selectedProduct.value) return 0
  return getUnitPrice(selectedProduct.value, itemRegiao.value)
})
const itemEstimate = computed(() => itemPieces.value * itemUnitPrice.value)

const saveItemToList = () => {
  if (!selectedProductId.value) {
    errorMessage.value = 'Selecione um produto.'
    return
  }
  
  const product = selectedProductData.value
  if (!product) {
    errorMessage.value = 'Produto selecionado inválido.'
    return
  }

  const qtyTotal = sumQuantidades(itemQuantidades.value)
  if (qtyTotal <= 0) {
    errorMessage.value = 'Informe ao menos 1 unidade em algum tamanho.'
    return
  }

  const entry: PedidoItem = {
    id_produto: product.id_produto,
    produto_nome: product.produto,
    referencia: product.referencia,
    regiao: itemRegiao.value,
    quantidades: { ...itemQuantidades.value },
    total_item: calculateItemTotal(product, itemRegiao.value, itemQuantidades.value),
  }

  if (editingIndex.value !== null) {
    items.value.splice(editingIndex.value, 1, entry)
  } else {
    items.value.push(entry)
  }

  resetItemForm()
}

const editItem = (index: number) => {
  const item = items.value[index]
  if (!item) return
  editingIndex.value = index
  selectedProductId.value = String(item.id_produto)
  itemRegiao.value = item.regiao
  itemQuantidades.value = { ...Object.fromEntries(SIZE_KEYS.map(k => [k, 0])), ...item.quantidades }
}

const removeItem = (index: number) => {
  items.value.splice(index, 1)
}

const totalPedido = computed(() => {
  return items.value.reduce((acc, item) => acc + (Number(item.total_item) || 0), 0)
})

const savePedido = async () => {
  if (!selectedClientId.value || items.value.length === 0) {
    errorMessage.value = 'Selecione um cliente e adicione pelo menos um item.'
    return
  }

  if (!auth.user?.id) {
    errorMessage.value = 'Usuário não identificado. Faça login novamente.'
    return
  }

  isSaving.value = true
  errorMessage.value = null

  try {
    const payload = {
      id_cliente: Number(selectedClientId.value),
      id_vendedor: auth.user.id,
      obs_pedido: obsPedido.value,
      obs_entrega: obsEntrega.value,
      forma_pag: formaPag.value,
      itens: items.value.map(item => ({
        id_produto: item.id_produto,
        regiao: item.regiao,
        quantidades: item.quantidades,
      }))
    }

    const response = await apiClient.post('/api/pedidos', payload)
    const idPedido = response.data?.data?.id_pedido
    if (idPedido) {
      router.push({ name: 'pedido-detalhes', params: { id: idPedido } })
    } else {
      router.push({ name: 'pedidos' })
    }
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Erro ao salvar o pedido.'
  } finally {
    isSaving.value = false
  }
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0)
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
            <!-- Add/Edit Item Header -->
            <div class="rounded-2xl border-2 border-primary/20 bg-muted/10 p-6">
              <div class="flex items-center justify-between gap-4 mb-6 pb-4 border-b border-primary/10">
                <div class="space-y-0.5">
                  <p class="text-lg font-bold flex items-center gap-2">
                    <span v-if="editingIndex !== null" class="flex items-center gap-2">
                      <Pencil class="w-4 h-4" /> Editar Item #{{ editingIndex + 1 }}
                    </span>
                    <span v-else class="flex items-center gap-2">
                      <Plus class="w-4 h-4" /> Novo Item
                    </span>
                  </p>
                  <p class="text-sm text-muted-foreground font-medium">Selecione produto, região e quantidades por tamanho.</p>
                </div>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-8 space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="space-y-1.5 text-left md:col-span-2">
                      <Label class="text-xs font-semibold text-foreground/70">Produto</Label>
                      <Select v-model="selectedProductId" :disabled="isLoadingProdutos || isSaving">
                        <SelectTrigger class="w-full bg-background border h-11 rounded-lg">
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
                      <Label class="text-xs font-semibold text-foreground/70">Região (preço)</Label>
                      <Select v-model="itemRegiao" :disabled="isSaving">
                        <SelectTrigger class="w-full bg-background border h-11 rounded-lg">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="nordeste">Nordeste</SelectItem>
                          <SelectItem value="norte">Norte</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  <div class="rounded-xl border bg-card p-4">
                    <div class="space-y-5">
                      <div v-for="group in SIZE_GROUPS" :key="group.title" class="space-y-3">
                        <div class="flex items-center justify-between">
                          <p class="text-[11px] font-black uppercase tracking-wider text-muted-foreground">{{ group.title }}</p>
                          <p class="text-[11px] font-semibold text-muted-foreground">Qtd: {{ group.keys.reduce((a, k) => a + (Number(itemQuantidades[k]) || 0), 0) }}</p>
                        </div>
                        <div class="grid grid-cols-4 sm:grid-cols-7 md:grid-cols-8 gap-3">
                          <div v-for="key in group.keys" :key="key" class="flex flex-col items-center space-y-1">
                            <Label class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">{{ sizeLabel(key) }}</Label>
                            <Input
                              type="number"
                              min="0"
                              class="h-10 rounded-lg text-center"
                              v-model.number="itemQuantidades[key]"
                              :disabled="isSaving"
                            />
                          </div>
                        </div>
                        <Separator v-if="group.title !== SIZE_GROUPS[SIZE_GROUPS.length - 1].title" />
                      </div>
                    </div>
                  </div>
                </div>

                <div class="lg:col-span-4">
                  <div class="rounded-2xl border bg-card p-5 space-y-4 shadow-sm">
                    <p class="text-sm font-extrabold">Resumo do item</p>
                    <div class="space-y-2 text-sm">
                      <div class="flex justify-between">
                        <span class="text-muted-foreground">Peças</span>
                        <span class="font-bold">{{ itemPieces }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-muted-foreground">Preço unitário</span>
                        <span class="font-bold">{{ formatCurrency(itemUnitPrice) }}</span>
                      </div>
                      <div class="flex justify-between border-t pt-2">
                        <span class="font-bold">Total estimado</span>
                        <span class="font-black text-primary">{{ formatCurrency(itemEstimate) }}</span>
                      </div>
                    </div>

                    <div class="flex gap-2 pt-2">
                      <Button variant="outline" class="flex-1 h-11 rounded-xl" :disabled="isSaving" @click="resetItemForm">Limpar</Button>
                      <Button @click="saveItemToList" class="flex-1 h-11 rounded-xl gap-2" :disabled="isSaving">
                        <template v-if="editingIndex !== null">
                          <Pencil class="w-4 h-4" /> Atualizar
                        </template>
                        <template v-else>
                          <Plus class="w-4 h-4" /> Adicionar
                        </template>
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Items Table -->
            <div class="border rounded-xl overflow-hidden">
              <Table>
                <TableHeader class="bg-muted/50">
                  <TableRow>
                    <TableHead>Produto</TableHead>
                    <TableHead class="text-center">Qtd</TableHead>
                    <TableHead>Tamanhos</TableHead>
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
                    <TableCell class="text-center font-bold text-xs text-muted-foreground">{{ sumQuantidades(item.quantidades) }}</TableCell>
                    <TableCell>
                      <div class="flex flex-wrap gap-1">
                        <Badge
                          v-for="b in getSizeBadges(item.quantidades)"
                          :key="`${item.id_produto}-${b.key}`"
                          variant="outline"
                          class="h-5 px-2 rounded-md text-[10px] font-bold"
                        >
                          {{ b.label }}:{{ b.value }}
                        </Badge>
                        <span v-if="getSizeBadges(item.quantidades).length === 0" class="text-xs text-muted-foreground font-semibold">-</span>
                      </div>
                    </TableCell>
                    <TableCell class="text-right font-black">{{ formatCurrency(item.total_item) }}</TableCell>
                    <TableCell class="text-right">
                      <div class="flex justify-end gap-1 opacity-70 hover:opacity-100 transition-opacity">
                        <Button variant="ghost" size="icon" @click="editItem(index)" class="hover:bg-accent">
                          <Pencil class="w-4 h-4" />
                        </Button>
                        <Button variant="ghost" size="icon" @click="removeItem(index)" class="text-destructive hover:text-destructive hover:bg-destructive/10">
                          <Trash2 class="w-4 h-4" />
                        </Button>
                      </div>
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
