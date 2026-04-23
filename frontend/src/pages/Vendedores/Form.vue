<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  ArrowLeft,
  Save,
  Loader2,
  AlertCircle,
  User
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import PasswordInput from '@/components/ui/PasswordInput.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import apiClient from '@/lib/axios'

interface Vendedor {
  id_vendedor?: number
  nome_vendedor: string
  contato_vendedor: string
  usuario: string
  senha?: string
  status: number
  cpf: string
  email: string
  telefone: string
  comissao: number
}

const router = useRouter()
const route = useRoute()

const isEditing = ref(false)
const vendedorId = ref<number | null>(null)

// Form Data
const nomeVendedor = ref('')
const contatoVendedor = ref('')
const usuario = ref('')
const senha = ref('')
const status = ref(1)
const cpf = ref('')
const email = ref('')
const telefone = ref('')
const comissao = ref(0)

// UI State
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref<string | null>(null)

const loadVendedor = async (id: number) => {
  isLoading.value = true
  try {
    const response = await apiClient.get(`/api/vendedores/${id}`)
    const vendedor: Vendedor = response.data.data

    nomeVendedor.value = vendedor.nome_vendedor
    contatoVendedor.value = vendedor.contato_vendedor
    usuario.value = vendedor.usuario
    status.value = vendedor.status
    cpf.value = vendedor.cpf
    email.value = vendedor.email
    telefone.value = vendedor.telefone
    comissao.value = vendedor.comissao
  } catch (error) {
    errorMessage.value = 'Erro ao carregar vendedor.'
    console.error('Failed to load vendedor:', error)
  } finally {
    isLoading.value = false
  }
}

const saveVendedor = async () => {
  if (!nomeVendedor.value.trim() || !usuario.value.trim()) {
    errorMessage.value = 'Nome e usuário são obrigatórios.'
    return
  }

  isSaving.value = true
  errorMessage.value = null

  try {
    const vendedorData: Vendedor = {
      nome_vendedor: nomeVendedor.value,
      contato_vendedor: contatoVendedor.value,
      usuario: usuario.value,
      status: status.value,
      cpf: cpf.value,
      email: email.value,
      telefone: telefone.value,
      comissao: comissao.value,
    }

    if (senha.value) {
      vendedorData.senha = senha.value
    }

    if (isEditing.value && vendedorId.value) {
      await apiClient.put(`/api/vendedores/${vendedorId.value}`, vendedorData)
    } else {
      await apiClient.post('/api/vendedores', vendedorData)
    }

    router.push('/vendedores')
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Erro ao salvar vendedor.'
    console.error('Failed to save vendedor:', error)
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  const id = route.params.id as string
  if (id && id !== 'novo') {
    isEditing.value = true
    vendedorId.value = parseInt(id)
    loadVendedor(vendedorId.value)
  }
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
        <h1 class="text-3xl font-bold tracking-tight">
          {{ isEditing ? 'Editar Vendedor' : 'Novo Vendedor' }}
        </h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">
          {{ isEditing ? 'Atualize as informações do vendedor.' : 'Cadastre um novo vendedor na equipe.' }}
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center items-center py-20">
      <Loader2 class="w-8 h-8 animate-spin text-primary" />
      <p class="ml-3 text-sm text-muted-foreground">Carregando...</p>
    </div>

    <!-- Error -->
    <div v-else-if="errorMessage" class="text-center text-red-500 p-8">
      <AlertCircle class="inline-block w-6 h-6 mr-2" />
      {{ errorMessage }}
    </div>

    <!-- Form -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">
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
              v-model="nomeVendedor"
              placeholder="Digite o nome completo"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>

          <div class="space-y-2">
            <Label htmlFor="contato" class="text-sm font-semibold">Contato</Label>
            <Input
              id="contato"
              v-model="contatoVendedor"
              placeholder="Telefone ou contato alternativo"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>

          <div class="space-y-2">
            <Label htmlFor="status" class="text-sm font-semibold">Status</Label>
            <Select v-model="status" :disabled="isSaving">
              <SelectTrigger class="rounded-xl h-12">
                <SelectValue />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem :value="1">Ativo</SelectItem>
                <SelectItem :value="0">Inativo</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <!-- Account Info -->
      <Card class="rounded-2xl border shadow-sm bg-card">
        <CardHeader>
          <CardTitle class="text-lg">Dados de Acesso</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="space-y-2">
            <Label htmlFor="usuario" class="text-sm font-semibold">Usuário *</Label>
            <Input
              id="usuario"
              v-model="usuario"
              placeholder="Nome de usuário para login"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>

          <div class="space-y-2">
            <Label htmlFor="senha" class="text-sm font-semibold">
              Senha {{ isEditing ? '(deixe em branco para manter)' : '*' }}
            </Label>
            <PasswordInput
              id="senha"
              v-model="senha"
              placeholder="Digite a senha"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>

          <div class="space-y-2">
            <Label htmlFor="comissao" class="text-sm font-semibold">Comissão (%)</Label>
            <Input
              id="comissao"
              type="number"
              v-model="comissao"
              placeholder="0.00"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Contact Info -->
      <Card class="rounded-2xl border shadow-sm bg-card lg:col-span-2">
        <CardHeader>
          <CardTitle class="text-lg">Informações de Contato</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label htmlFor="cpf" class="text-sm font-semibold">CPF</Label>
              <Input
                id="cpf"
                v-model="cpf"
                placeholder="000.000.000-00"
                class="rounded-xl h-12"
                :disabled="isSaving"
              />
            </div>

            <div class="space-y-2">
              <Label htmlFor="telefone" class="text-sm font-semibold">Telefone</Label>
              <Input
                id="telefone"
                v-model="telefone"
                placeholder="(00) 00000-0000"
                class="rounded-xl h-12"
                :disabled="isSaving"
              />
            </div>
          </div>

          <div class="space-y-2">
            <Label htmlFor="email" class="text-sm font-semibold">Email</Label>
            <Input
              id="email"
              type="email"
              v-model="email"
              placeholder="email@exemplo.com"
              class="rounded-xl h-12"
              :disabled="isSaving"
            />
          </div>
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
        @click="saveVendedor"
        :disabled="isSaving"
        class="rounded-xl h-11 px-6 bg-primary hover:bg-primary/90"
      >
        <template v-if="isSaving">
          <Loader2 class="w-4 h-4 animate-spin mr-2" />
          Salvando...
        </template>
        <template v-else>
          <Save class="w-4 h-4 mr-2" />
          {{ isEditing ? 'Atualizar' : 'Salvar' }} Vendedor
        </template>
      </Button>
    </div>
  </div>
</template>