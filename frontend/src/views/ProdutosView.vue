<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../composables/useApi'

const router = useRouter()
const authStore = useAuthStore()
const loading = ref(true)
const produtos = ref([])
const search = ref('')
const showModal = ref(false)
const editingProduto = ref(null)
const form = ref({
  referencia: '', produto: '', valor_unt_norde: 0, valor_unt_norte: 0, status: true
})

onMounted(() => {
  if (!authStore.isAuthenticated || !authStore.isAdmin) {
    router.push('/')
    return
  }
  loadProdutos()
})

async function loadProdutos() {
  try {
    const response = await api.getProdutos({ search: search.value })
    produtos.value = response.data.data
  } catch (error) {
    console.error('Erro:', error)
  } finally {
    loading.value = false
  }
}

function openNew() {
  editingProduto.value = null
  form.value = { referencia: '', produto: '', valor_unt_norde: 0, valor_unt_norte: 0, status: true }
  showModal.value = true
}

function openEdit(produto) {
  editingProduto.value = produto
  form.value = { ...produto }
  showModal.value = true
}

async function saveProduto() {
  try {
    if (editingProduto.value) {
      await api.updateProduto(editingProduto.value.id_produto, form.value)
    } else {
      await api.createProduto(form.value)
    }
    showModal.value = false
    loadProdutos()
  } catch (error) {
    alert('Erro ao salvar')
  }
}

async function deleteProduto(id) {
  if (!confirm('Excluir produto?')) return
  try {
    await api.deleteProduto(id)
    loadProdutos()
  } catch (error) {
    alert('Erro ao excluir')
  }
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Produtos</h1>
      <button @click="openNew" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
        + Novo Produto
      </button>
    </div>

    <div class="mb-4">
      <input v-model="search" @input="loadProdutos" type="text" placeholder="Buscar..." 
        class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg" />
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Norde</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Norte</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="produto in produtos" :key="produto.id_produto" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm text-gray-800">{{ produto.referencia }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ produto.produto }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">R$ {{ produto.valor_unt_norde }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">R$ {{ produto.valor_unt_norte }}</td>
            <td class="px-6 py-4">
              <span :class="produto.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                class="px-2 py-1 rounded-full text-xs">{{ produto.status ? 'Ativo' : 'Inativo' }}</span>
            </td>
            <td class="px-6 py-4 text-right">
              <button @click="openEdit(produto)" class="text-primary-600 hover:underline mr-3">Editar</button>
              <button @click="deleteProduto(produto.id_produto)" class="text-red-600 hover:underline">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Produto -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
        <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white px-6 py-4">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
              {{ editingProduto ? 'Editar Produto' : 'Novo Produto' }}
            </h2>
            <button @click="showModal = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-1 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <form @submit.prevent="saveProduto" class="p-6 space-y-4">
          <div class="grid grid-cols-1 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Referência *</label>
              <input
                v-model="form.referencia"
                type="text"
                required
                :disabled="!!editingProduto"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                placeholder="Código de referência único"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto *</label>
              <input
                v-model="form.produto"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                placeholder="Nome completo do produto"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Preço Nordeste</label>
                <div class="relative">
                  <span class="absolute left-3 top-2 text-gray-500">R$</span>
                  <input
                    v-model.number="form.valor_unt_norde"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="0,00"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Preço Norte</label>
                <div class="relative">
                  <span class="absolute left-3 top-2 text-gray-500">R$</span>
                  <input
                    v-model.number="form.valor_unt_norte"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="0,00"
                  />
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between py-2">
              <label class="text-sm font-medium text-gray-700">Status do Produto</label>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">{{ form.status ? 'Ativo' : 'Inativo' }}</span>
                <button
                  type="button"
                  @click="form.status = form.status ? 0 : 1"
                  :class="form.status ? 'bg-green-500' : 'bg-gray-300'"
                  class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                >
                  <span
                    :class="form.status ? 'translate-x-5' : 'translate-x-0'"
                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                  ></span>
                </button>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
            >
              {{ loading ? 'Salvando...' : 'Salvar Produto' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>