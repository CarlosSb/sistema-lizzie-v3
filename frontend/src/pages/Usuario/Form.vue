<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeft,
  Save,
  Loader2,
  AlertCircle,
  User,
  Lock
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import PasswordInput from '@/components/ui/PasswordInput.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

// Form Data
const nome = ref('')
const email = ref('')
const senhaAtual = ref('')
const novaSenha = ref('')
const confirmarSenha = ref('')

// UI State
const isSaving = ref(false)
const errorMessage = ref<string | null>(null)

const loadUserData = () => {
  const user = auth.user
  if (user) {
    nome.value = user.nome || ''
    // email.value = user.email || '' // Not available in auth.user
  }
}

const validatePasswordStrength = (password: string): string | null => {
  if (password.length < 8) {
    return 'A senha deve ter pelo menos 8 caracteres.'
  }
  if (!/[A-Z]/.test(password)) {
    return 'A senha deve conter pelo menos uma letra maiúscula.'
  }
  if (!/[a-z]/.test(password)) {
    return 'A senha deve conter pelo menos uma letra minúscula.'
  }
  if (!/\d/.test(password)) {
    return 'A senha deve conter pelo menos um número.'
  }
  return null
}

const saveProfile = async () => {
  if (!nome.value.trim()) {
    errorMessage.value = 'Nome é obrigatório.'
    return
  }

  if (!email.value.trim()) {
    errorMessage.value = 'Email é obrigatório.'
    return
  }

  if (novaSenha.value) {
    const strengthError = validatePasswordStrength(novaSenha.value)
    if (strengthError) {
      errorMessage.value = strengthError
      return
    }

    if (novaSenha.value !== confirmarSenha.value) {
      errorMessage.value = 'As senhas não coincidem.'
      return
    }
  }

  isSaving.value = true
  errorMessage.value = null

  try {
    // Here you would call the API to update user profile
    // For now, we'll just simulate success
    console.log('Updating profile:', {
      nome: nome.value,
      email: email.value,
      senha: novaSenha.value ? '***' : undefined
    })

    // Update local auth store if needed
    // auth.updateUser({ nome: nome.value, email: email.value })

    // Show success message or redirect
    alert('Perfil atualizado com sucesso!')
    router.push('/perfil')
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Erro ao atualizar perfil.'
    console.error('Failed to update profile:', error)
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  loadUserData()
})
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex items-center gap-4 border-b pb-8">
      <Button
        variant="outline"
        size="icon"
        @click="router.back()"
        class="rounded-xl h-11 w-11"
      >
        <ArrowLeft class="w-4 h-4" />
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Editar Perfil</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">Atualize suas informações pessoais</p>
      </div>
    </div>

    <!-- Error -->
    <div v-if="errorMessage" class="text-center text-red-500 p-4 bg-red-50 border border-red-200 rounded-lg">
      <AlertCircle class="inline-block w-5 h-5 mr-2" />
      {{ errorMessage }}
    </div>

    <!-- Form -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Basic Info -->
      <Card class="rounded-2xl border shadow-sm bg-card">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-lg">
            <User class="w-5 h-5 text-primary" />
            Informações Básicas
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="space-y-2">
            <Label htmlFor="nome" class="text-sm font-semibold">Nome Completo *</Label>
            <Input
              id="nome"
              v-model="nome"
              placeholder="Digite seu nome completo"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>

          <div class="space-y-2">
            <Label class="text-sm font-semibold">Email</Label>
            <Input
              :value="'seu.email@exemplo.com'"
              class="rounded-xl h-12 bg-muted"
              disabled
            />
            <p class="text-xs text-muted-foreground">Entre em contato com o administrador para alterar o email</p>
          </div>

          <div class="space-y-2">
            <Label class="text-sm font-semibold">Usuário</Label>
            <Input
              :value="auth.user?.usuario || ''"
              class="rounded-xl h-12 bg-muted"
              disabled
            />
            <p class="text-xs text-muted-foreground">O usuário não pode ser alterado</p>
          </div>
        </CardContent>
      </Card>

      <!-- Password -->
      <Card class="rounded-2xl border shadow-sm bg-card">
        <CardHeader>
          <CardTitle class="text-lg flex items-center gap-2">
            <Lock class="w-5 h-5 text-primary" />
            Alterar Senha
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="space-y-2">
            <Label htmlFor="senha-atual" class="text-sm font-semibold">Senha Atual</Label>
            <PasswordInput
              id="senha-atual"
              v-model="senhaAtual"
              placeholder="Digite sua senha atual"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>

          <div class="space-y-2">
            <Label htmlFor="nova-senha" class="text-sm font-semibold">Nova Senha</Label>
            <PasswordInput
              id="nova-senha"
              v-model="novaSenha"
              placeholder="Digite a nova senha"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>

          <div class="space-y-2">
            <Label htmlFor="confirmar-senha" class="text-sm font-semibold">Confirmar Nova Senha</Label>
            <PasswordInput
              id="confirmar-senha"
              v-model="confirmarSenha"
              placeholder="Confirme a nova senha"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>

          <p class="text-xs text-muted-foreground">
            Deixe os campos de senha em branco se não quiser alterar a senha atual.
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Actions -->
    <div class="flex justify-end gap-4 pt-8 border-t">
      <Button
        variant="outline"
        @click="router.back()"
        :disabled="isSaving"
        class="rounded-xl h-11 px-6"
      >
        Cancelar
      </Button>
      <Button
        @click="saveProfile"
        :disabled="isSaving"
        class="rounded-xl h-11 px-6 bg-primary hover:bg-primary/90"
      >
        <template v-if="isSaving">
          <Loader2 class="w-4 h-4 animate-spin mr-2" />
          Salvando...
        </template>
        <template v-else>
          <Save class="w-4 h-4 mr-2" />
          Salvar Alterações
        </template>
      </Button>
    </div>
  </div>
</template>