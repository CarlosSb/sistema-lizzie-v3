<script setup>
import { ref, onMounted, h } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../composables/useApi'
import { NCard, NDataTable, NButton, NIcon, NModal, NForm, NFormItem, NInput, NSelect, NSwitch, useMessage, NSpace } from 'naive-ui'
import { PersonAddOutline, CreateOutline, TrashOutline, RefreshOutline } from '@vicons/ionicons5'

const router = useRouter()
const authStore = useAuthStore()
const message = useMessage()
const loading = ref(true)
const usuarios = ref([])
const showModal = ref(false)
const editingUsuario = ref(null)
const loadingSave = ref(false)

const form = ref({
  nome_vendedor: '',
  usuario: '',
  senha: '',
  controle_acesso: 'vendedor',
  status: 1
})

const columns = [
  { title: 'Nome', key: 'nome_vendedor' },
  { title: 'Usuário', key: 'usuario' },
  { title: 'Nível', key: 'controle_acesso', render: row => row.controle_acesso === 'admin' ? 'Admin' : 'Vendedor' },
  { title: 'Status', key: 'status', render: row => row.status === 1 ? 'Ativo' : 'Inativo' },
  { title: 'Ações', key: 'acao', width: 140, render: row => h(NSpace, { size: 'small' }, () => [
    h(NButton, { size: 'small', onClick: () => openEdit(row) }, () => h(NIcon, null, { default: () => h(CreateOutline) })),
    h(NButton, { size: 'small', type: 'error', onClick: () => deleteUsuario(row.id_vendedor) }, () => h(NIcon, null, { default: () => h(TrashOutline) }))
  ])}
]

const nivelOptions = [
  { label: 'Vendedor', value: 'vendedor' },
  { label: 'Administrador', value: 'admin' }
]

onMounted(() => {
  if (!authStore.isAuthenticated || !authStore.isAdmin) {
    router.push('/')
    return
  }
  loadUsuarios()
})

async function loadUsuarios() {
  loading.value = true
  try {
    const response = await api.getUsuarios()
    usuarios.value = response.data.data || []
  } catch (error) {
    message.error('Erro ao carregar usuários')
  } finally {
    loading.value = false
  }
}

function openNew() {
  editingUsuario.value = null
  form.value = {
    nome_vendedor: '',
    usuario: '',
    senha: '',
    controle_acesso: 'vendedor',
    status: 1
  }
  showModal.value = true
}

function openEdit(usuario) {
  editingUsuario.value = usuario
  form.value = {
    nome_vendedor: usuario.nome_vendedor,
    usuario: usuario.usuario,
    senha: '',
    controle_acesso: usuario.controle_acesso,
    status: usuario.status
  }
  showModal.value = true
}

async function saveUsuario() {
  if (!form.value.nome_vendedor || !form.value.usuario) {
    message.warning('Nome e usuário são obrigatórios')
    return
  }
  if (!editingUsuario.value && !form.value.senha) {
    message.warning('Senha é obrigatória para novo usuário')
    return
  }

  loadingSave.value = true
  try {
    const data = {
      nome_vendedor: form.value.nome_vendedor,
      controle_acesso: form.value.controle_acesso,
      status: form.value.status
    }
    if (form.value.senha) {
      data.senha = form.value.senha
    }

    if (editingUsuario.value) {
      await api.atualizarUsuario(editingUsuario.value.id_vendedor, data)
      message.success('Usuário atualizado')
    } else {
      await api.criarUsuario({ ...data, usuario: form.value.usuario })
      message.success('Usuário criado')
    }
    showModal.value = false
    loadUsuarios()
  } catch (error) {
    message.error(error.response?.data?.message || 'Erro ao salvar')
  } finally {
    loadingSave.value = false
  }
}

async function deleteUsuario(id) {
  if (!confirm('Excluir este usuário?')) return
  try {
    await api.excluirUsuario(id)
    message.success('Usuário excluído')
    loadUsuarios()
  } catch (error) {
    message.error(error.response?.data?.message || 'Erro ao excluir')
  }
}
</script>

<template>
  <div class="usuarios-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Gerenciamento de Usuários</h1>
        <p class="page-subtitle">Cadastre e gerencie usuários do sistema</p>
      </div>
      <NButton type="primary" @click="openNew">
        <template #icon><NIcon><PersonAddOutline /></NIcon></template>
        Novo Usuário
      </NButton>
    </div>

    <NCard>
      <NDataTable :columns="columns" :data="usuarios" :loading="loading" :pagination="false" />
    </NCard>

    <!-- Modal Usuário -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              {{ editingUsuario ? 'Editar Usuário' : 'Novo Usuário' }}
            </h2>
            <button @click="showModal = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-1 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <form @submit.prevent="saveUsuario" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
            <input
              v-model="form.nome_vendedor"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Digite o nome completo"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Usuário/Login *</label>
            <input
              v-model="form.usuario"
              type="text"
              required
              :disabled="!!editingUsuario"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
              placeholder="Digite o login"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Senha {{ editingUsuario ? '(Deixe vazio para manter)' : '*' }}
            </label>
            <input
              v-model="form.senha"
              type="password"
              :required="!editingUsuario"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :placeholder="editingUsuario ? 'Deixe vazio para manter' : 'Digite a senha'"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nível de Acesso</label>
            <n-select
              v-model:value="form.controle_acesso"
              :options="nivelOptions"
              class="w-full"
            />
          </div>

          <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-gray-700">Status</label>
            <div class="flex items-center space-x-2">
              <span class="text-sm text-gray-600">{{ form.status ? 'Ativo' : 'Inativo' }}</span>
              <button
                type="button"
                @click="form.status = form.status ? 0 : 1"
                :class="form.status ? 'bg-green-500' : 'bg-gray-300'"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                <span
                  :class="form.status ? 'translate-x-5' : 'translate-x-0'"
                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                ></span>
              </button>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="loadingSave"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
            >
              {{ loadingSave ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.usuarios-container {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
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

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 16px;
}
</style>