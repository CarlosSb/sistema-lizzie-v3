<script setup>
import { ref, onMounted, computed, h } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../composables/useApi'
import { NCard, NDataTable, NButton, NIcon, NInput, NSelect, NDatePicker, NSpin, NSpace, NModal, NForm, NFormItem, NInputNumber, useMessage, NTabs, NTabPane, NGrid, NGi } from 'naive-ui'
import { AddOutline, RefreshOutline, SearchOutline, ArrowDownOutline, ArrowUpOutline } from '@vicons/ionicons5'

const router = useRouter()
const authStore = useAuthStore()
const message = useMessage()
const loading = ref(true)
const estoques = ref([])
const movimentacao = ref([])
const produtoNome = ref('')
const filtroStatus = ref(null)
const activeTab = ref('lista')
const showEntradaModal = ref(false)
const showSaidaModal = ref(false)
const entradaForm = ref({ produto_id: null, tam_p: 0, tam_m: 0, tam_g: 0, tam_u: 0, tam_rn: 0, ida_1: 0, ida_2: 0, ida_3: 0, ida_4: 0, ida_6: 0, ida_8: 0, observacao: '' })
const saidaForm = ref({ id_produto: null, tipo: 'ajuste', tam_p: 0, tam_m: 0, tam_g: 0, tam_u: 0, tam_rn: 0, ida_1: 0, ida_2: 0, ida_3: 0, ida_4: 0, ida_6: 0, ida_8: 0, observacao: '' })

const tipoSaidaOptions = [
  { label: 'Selecione o tipo', value: '' },
  { label: 'Venda', value: 'venda' },
  { label: 'Perda', value: 'perda' },
  { label: 'Avaria', value: 'avaria' },
  { label: 'Outros', value: 'outros' }
]
const page = ref(1)
const perPage = ref(20)
const total = ref(0)

const statusOptions = [
  { label: 'Todos', value: null },
  { label: 'Disponível', value: 'disponivel' },
  { label: 'Reservado', value: 'reservado' },
  { label: 'Baixado', value: 'baixado' }
]



const columns = [
  { title: 'Produto', key: 'produto', render: row => row.produto?.nome || `ID: ${row.id_produto}` },
  { title: 'Tamanho', key: 'tamanho' },
  { title: 'Cor', key: 'cor' },
  { title: 'Quantidade', key: 'quantidade' },
  { title: 'Status', key: 'status', render: row => h('span', { class: `status-badge status-${row.status}` }, row.status) },
  { title: 'Local', key: 'localizacao' }
]

const movColumns = [
  { title: 'Data', key: 'created_at', render: row => new Date(row.created_at).toLocaleString('pt-BR') },
  { title: 'Tipo', key: 'tipo_movimentacao' },
  { title: 'Descrição', key: 'descricao' },
  { title: 'Quantidade', key: 'quantidade' }
]

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }
  if (!authStore.isAdmin && !authStore.isVendedor) {
    router.push('/')
    return
  }
  await loadEstoques()
})

async function loadEstoques() {
  loading.value = true
  try {
    const response = await api.getEstoques({ page: page.value, per_page: perPage.value })
    estoques.value = response.data.data
    total.value = response.data.pagination?.total || 0
  } catch (error) {
    message.error('Erro ao carregar estoques')
  } finally {
    loading.value = false
  }
}

async function loadMovimentacao() {
  try {
    const response = await api.getMovimentacao()
    movimentacao.value = response.data.data
  } catch (error) {
    message.error('Erro ao carregar movimentação')
  }
}

async function registrarEntrada() {
  if (!entradaForm.value.produto_id) {
    message.warning('Selecione o produto')
    return
  }
  try {
    await api.registrarEntrada(entradaForm.value)
    message.success('Entrada registrada com sucesso')
    showEntradaModal.value = false
    entradaForm.value = { produto_id: null, tam_p: 0, tam_m: 0, tam_g: 0, tam_u: 0, tam_rn: 0, ida_1: 0, ida_2: 0, ida_3: 0, ida_4: 0, ida_6: 0, ida_8: 0, observacao: '' }
    await loadEstoques()
  } catch (error) {
    message.error(error.response?.data?.message || 'Erro ao registrar entrada')
  }
}

async function registrarSaida() {
  if (!saidaForm.value.id_produto) {
    message.warning('Selecione o produto')
    return
  }
  
  const temQuantidade = saidaForm.value.tam_p || saidaForm.value.tam_m || 
                        saidaForm.value.tam_g || saidaForm.value.tam_u || saidaForm.value.tam_rn ||
                        saidaForm.value.ida_1 || saidaForm.value.ida_2 || saidaForm.value.ida_3 || 
                        saidaForm.value.ida_4 || saidaForm.value.ida_6 || saidaForm.value.ida_8
  
  if (!temQuantidade) {
    message.warning('Informe pelo menos uma quantidade')
    return
  }
  
  try {
    await api.registrarSaida(saidaForm.value)
    message.success('Saída registrada com sucesso')
    showSaidaModal.value = false
    saidaForm.value = { id_produto: null, tipo: 'ajuste', tam_p: 0, tam_m: 0, tam_g: 0, tam_u: 0, tam_rn: 0, ida_1: 0, ida_2: 0, ida_3: 0, ida_4: 0, ida_6: 0, ida_8: 0, observacao: '' }
    await loadEstoques()
    await loadMovimentacao()
  } catch (error) {
    message.error(error.response?.data?.message || 'Erro ao registrar saída')
  }
}

function openSaida() {
  showSaidaModal.value = true
  activeTab.value = 'saida'
}

function closeAllModals() {
  showEntradaModal.value = false
  showSaidaModal.value = false
}
</script>

<template>
  <div class="estoque-container">
    <div class="page-header">
      <div class="header-top">
        <div class="header-titles">
          <h1 class="page-title">Estoque</h1>
          <p class="page-subtitle">Gerencie entradas, saídas e movimentação</p>
        </div>
      </div>
      <div class="filters-row">
        <NInput v-model:value="produtoNome" placeholder="Buscar produto..." clearable class="search-input">
          <template #prefix><NIcon><SearchOutline /></NIcon></template>
        </NInput>
        <NSelect v-model:value="filtroStatus" :options="statusOptions" placeholder="Status" class="filter-select" />
        <NButton @click="loadEstoques"><NIcon><RefreshOutline /></NIcon></NButton>
        <NButton type="success" @click="showEntradaModal = true; activeTab = 'entrada'">
          <template #icon><NIcon><ArrowDownOutline /></NIcon></template>
          Entrada
        </NButton>
        <NButton type="warning" @click="openSaida">
          <template #icon><NIcon><ArrowUpOutline /></NIcon></template>
          Saída
        </NButton>
      </div>
    </div>

    <NTabs v-model:value="activeTab" type="line" @update:value="activeTab === 'movimentacao' && loadMovimentacao()">
      <NTabPane name="lista" tab="📦 Lista de Estoque">
        <NSpin :show="loading">
          <NCard>
            <NDataTable :columns="columns" :data="estoques" :pagination="{ page, per_page: perPage, total }" @update:pagination="loadEstoques" />
          </NCard>
        </NSpin>
      </NTabPane>
      
      <NTabPane name="movimentacao" tab="📋 Movimentação">
        <NCard>
          <NDataTable :columns="movColumns" :data="movimentacao" :pagination="false" />
        </NCard>
      </NTabPane>
    </NTabs>

    <!-- Modal Entrada -->
    <!-- Modal Registrar Entrada -->
    <div v-if="showEntradaModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-4">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Registrar Entrada de Estoque
            </h2>
            <button @click="showEntradaModal = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-1 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <form @submit.prevent="registrarEntrada" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ID do Produto</label>
            <input
              v-model.number="entradaForm.produto_id"
              type="number"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              placeholder="Digite o ID do produto"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">Quantidades por Tamanho</label>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-600 mb-1">P</label>
                <input
                  v-model.number="entradaForm.tam_p"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">M</label>
                <input
                  v-model.number="entradaForm.tam_m"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">G</label>
                <input
                  v-model.number="entradaForm.tam_g"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">U</label>
                <input
                  v-model.number="entradaForm.tam_u"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">RN</label>
                <input
                  v-model.number="entradaForm.tam_rn"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">PP</label>
                <input
                  v-model.number="entradaForm.tam_pp"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
            <textarea
              v-model="entradaForm.observacao"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              placeholder="Observações sobre a entrada..."
            ></textarea>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="closeAllModals"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
            >
              {{ loading ? 'Registrando...' : 'Registrar Entrada' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Registrar Saída -->
    <div v-if="showSaidaModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
        <div class="bg-gradient-to-r from-red-600 to-red-700 text-white px-6 py-4">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
              </svg>
              Registrar Saída de Estoque
            </h2>
            <button @click="showSaidaModal = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-1 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <form @submit.prevent="registrarSaida" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ID do Produto</label>
            <input
              v-model.number="saidaForm.id_produto"
              type="number"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
              placeholder="Digite o ID do produto"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Saída</label>
            <n-select
              v-model:value="saidaForm.tipo"
              :options="tipoSaidaOptions"
              class="w-full"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">Quantidades por Tamanho</label>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-600 mb-1">P</label>
                <input
                  v-model.number="saidaForm.tam_p"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-red-500 focus:border-red-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">M</label>
                <input
                  v-model.number="saidaForm.tam_m"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-red-500 focus:border-red-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">G</label>
                <input
                  v-model.number="saidaForm.tam_g"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-red-500 focus:border-red-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">U</label>
                <input
                  v-model.number="saidaForm.tam_u"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-red-500 focus:border-red-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">RN</label>
                <input
                  v-model.number="saidaForm.tam_rn"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-red-500 focus:border-red-500"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-600 mb-1">PP</label>
                <input
                  v-model.number="saidaForm.tam_pp"
                  type="number"
                  min="0"
                  class="w-full px-2 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-red-500 focus:border-red-500"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
            <textarea
              v-model="saidaForm.observacao"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
              placeholder="Observações sobre a saída..."
            ></textarea>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="closeAllModals"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
            >
              {{ loading ? 'Registrando...' : 'Registrar Saída' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.estoque-container {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 16px;
}

.page-header {
  margin-bottom: 24px;
}

.header-top {
  margin-bottom: 20px;
}

.header-titles {
  display: flex;
  flex-direction: column;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.page-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.filters-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.search-input {
  width: 220px;
}

.filter-select {
  width: 140px;
}

/* Grid de tamanhos */
.tamanhos-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 8px;
  margin-bottom: 16px;
}

.status-badge {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  text-transform: capitalize;
}
.status-disponivel { background: #d1fae5; color: #065f46; }
.status-reservado { background: #dbeafe; color: #1e40af; }
.status-baixado { background: #fee2e2; color: #991b1b; }

@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-input,
  .filter-select {
    width: 100%;
  }
  
  .tamanhos-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>