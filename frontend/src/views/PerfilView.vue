<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../composables/useApi'
import { NCard, NButton, NIcon, NInput, NForm, NFormItem, useMessage } from 'naive-ui'
import { PersonOutline, LockClosedOutline } from '@vicons/ionicons5'

const router = useRouter()
const authStore = useAuthStore()
const message = useMessage()

const loading = ref(false)
const form = ref({
  nome: '',
  senha_atual: '',
  nova_senha: '',
  confirmar_senha: ''
})

onMounted(() => {
  if (authStore.user) {
    form.value.nome = authStore.user.nome
  }
})

async function salvarPerfil() {
  if (!form.value.nome) {
    message.warning('Nome é obrigatório')
    return
  }

  if (form.value.nova_senha && form.value.nova_senha !== form.value.confirmar_senha) {
    message.error('As senhas não conferem')
    return
  }

  if (form.value.nova_senha && !form.value.senha_atual) {
    message.warning('Informe a senha atual para alterar a senha')
    return
  }

  loading.value = true
  try {
    const data = { nome: form.value.nome }
    if (form.value.nova_senha) {
      data.senha = form.value.nova_senha
    }
    await api.atualizarPerfil(data)
    message.success('Perfil atualizado com sucesso')
    authStore.refreshUser()
    form.value.senha_atual = ''
    form.value.nova_senha = ''
    form.value.confirmar_senha = ''
  } catch (error) {
    message.error(error.response?.data?.message || 'Erro ao atualizar perfil')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="perfil-container">
    <div class="page-header">
      <h1 class="page-title">Meu Perfil</h1>
      <p class="page-subtitle">Gerencie suas informações pessoais</p>
    </div>

    <NCard>
      <NForm :model="form" label-placement="top">
        <div class="form-section">
          <div class="section-title">
            <NIcon size="20"><PersonOutline /></NIcon>
            <span>Informações Pessoais</span>
          </div>
          
          <NFormItem label="Nome">
            <NInput v-model:value="form.nome" placeholder="Seu nome" />
          </NFormItem>
          
          <NFormItem label="Usuário">
            <NInput :value="authStore.user?.usuario" disabled placeholder="Usuário" />
          </NFormItem>
          
          <NFormItem label="Nível de Acesso">
            <NInput :value="authStore.isAdmin ? 'Administrador' : 'Vendedor'" disabled />
          </NFormItem>
        </div>

        <div class="form-section">
          <div class="section-title">
            <NIcon size="20"><LockClosedOutline /></NIcon>
            <span>Alterar Senha</span>
          </div>
          
          <NFormItem label="Senha Atual">
            <NInput v-model:value="form.senha_atual" type="password" placeholder="Digite a senha atual" />
          </NFormItem>
          
          <NFormItem label="Nova Senha">
            <NInput v-model:value="form.nova_senha" type="password" placeholder="Digite a nova senha" />
          </NFormItem>
          
          <NFormItem label="Confirmar Senha">
            <NInput v-model:value="form.confirmar_senha" type="password" placeholder="Confirme a nova senha" />
          </NFormItem>
        </div>

        <div class="form-actions">
          <NButton type="primary" :loading="loading" @click="salvarPerfil">
            Salvar Alterações
          </NButton>
        </div>
      </NForm>
    </NCard>
  </div>
</template>

<style scoped>
.perfil-container {
  max-width: 600px;
  margin: 0 auto;
}

.page-header {
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

.form-section {
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 1px solid #e2e8f0;
}

.form-section:last-of-type {
  border-bottom: none;
  margin-bottom: 16px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 16px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
}
</style>