<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const loading = ref(false)
const error = ref('')
const form = ref({ usuario: '', senha: '' })

async function handleLogin() {
  error.value = ''
  
  if (!form.value.usuario || !form.value.senha) {
    error.value = 'Preencha usuário e senha'
    return
  }
  
  loading.value = true
  
  try {
    await authStore.login(form.value.usuario, form.value.senha)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Erro ao fazer login'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Lizzie - Amor de Mãe</h1>
        <p class="text-gray-500">Sistema de Gestão</p>
      </div>
      
      <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
        {{ error }}
      </div>
      
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Usuário</label>
          <input 
            v-model="form.usuario" 
            type="text" 
            required 
            autocomplete="username"
            class="w-full mt-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500" 
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Senha</label>
          <input 
            v-model="form.senha" 
            type="password" 
            required 
            autocomplete="current-password"
            class="w-full mt-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500" 
          />
        </div>
        <button 
          type="submit" 
          :disabled="loading" 
          class="w-full py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
        >
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </button>
      </form>
    </div>
  </div>
</template>