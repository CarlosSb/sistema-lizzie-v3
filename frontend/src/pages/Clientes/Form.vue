<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Save, Users, Loader2, AlertCircle, CheckCircle, XCircle } from 'lucide-vue-next'
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

// Interfaces
interface Cliente {
  id_cliente?: number // Optional for create mode
  responsavel: string
  nome_fantasia: string | null
  razao_social: string | null
  cpf_cnpj: string
  email: string | null
  telefone: string | null
  endereco: string | null
  bairro: string | null
  cidade: string | null
  estado: string | null
  cep: string | null
  status: number // 1 for active, 0 for inactive
}

const route = useRoute()
const router = useRouter()

const cliente = ref<Cliente>({
  responsavel: '',
  nome_fantasia: '',
  razao_social: '',
  cpf_cnpj: '',
  email: '',
  telefone: '',
  endereco: '',
  bairro: '',
  cidade: '',
  estado: '',
  cep: '',
  status: 1, // Default to active
})

const isEditing = computed(() => route.params.id !== undefined)
const pageTitle = computed(() => isEditing.value ? 'Editar Cliente' : 'Novo Cliente')
const pageDescription = computed(() => isEditing.value ? 'Modifique os dados do cliente.' : 'Preencha os campos para registrar um novo cliente.')

const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref<string | null>(null)

const fetchClienteDetalhes = async () => {
  isLoading.value = true
  const clienteId = route.params.id
  if (clienteId) {
    try {
      const response = await apiClient.get(`/api/clientes/${clienteId}`)
      cliente.value = response.data
      // Ensure status is handled correctly if API returns it as string or other type
      cliente.value.status = Number(cliente.value.status) || 1;
    } catch (error: any) {
      errorMessage.value = 'Erro ao carregar detalhes do cliente.'
      console.error('Failed to fetch cliente details:', error)
    } finally {
      isLoading.value = false
    }
  } else {
    isLoading.value = false
  }
}

onMounted(() => {
  if (isEditing.value) {
    fetchClienteDetalhes()
  } else {
    isLoading.value = false // Not loading if it's a new client
  }
})

const saveCliente = async () => {
  isSaving.value = true
  errorMessage.value = null

  // Basic validation (can be expanded)
  if (!cliente.value.responsavel || !cliente.value.cpf_cnpj) {
    errorMessage.value = 'Nome do Responsável e CPF/CNPJ são obrigatórios.'
    isSaving.value = false
    return
  }

  try {
    if (isEditing.value && cliente.value.id_cliente) {
      // Update existing client
      await apiClient.put(`/api/clientes/${cliente.value.id_cliente}`, cliente.value)
    } else {
      // Create new client
      await apiClient.post('/api/clientes', cliente.value)
    }
    router.push({ name: 'clientes' }) // Redirect to list page on success
  } catch (error: any) {
    if (apiClient.isAxiosError(error) && error.response) {
      const message = error.response.data?.message || error.response.data?.error || 'Erro ao salvar cliente.'
      errorMessage.value = message
    } else {
      errorMessage.value = 'Erro de conexão ou salvamento.'
    }
    console.error('Failed to save cliente:', error)
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <Button variant="ghost" @click="router.push({ name: 'clientes' })" class="px-2">
        <ArrowLeft class="w-5 h-5 mr-2" />
        <span class="font-bold">Voltar para Clientes</span>
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">{{ pageTitle }}</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">{{ pageDescription }}</p>
      </div>
    </div>

    <div v-if="errorMessage" class="bg-destructive/10 border border-destructive/20 text-destructive p-4 rounded-xl flex items-center gap-3 mb-6">
      <AlertCircle class="w-5 h-5" />
      <p class="text-sm font-bold">{{ errorMessage }}</p>
    </div>

    <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
      <CardHeader class="border-b bg-muted/20 px-8 py-6">
        <CardTitle class="text-lg font-bold flex items-center gap-2">
          <Users class="w-5 h-5 text-primary" /> Dados do Cliente
        </CardTitle>
      </CardHeader>
      <CardContent class="p-8">
        <div v-if="isLoading && isEditing" class="flex justify-center items-center py-10">
            <Loader2 class="w-8 h-8 animate-spin text-primary" />
            <p class="ml-3 text-sm text-muted-foreground">Carregando dados...</p>
        </div>
        <form v-else @submit.prevent="saveCliente" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Basic Info -->
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Nome do Responsável *</Label>
            <Input v-model="cliente.responsavel" required placeholder="Nome completo do responsável" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Nome Fantasia</Label>
            <Input v-model="cliente.nome_fantasia" placeholder="Nome fantasia (opcional)" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Razão Social</Label>
            <Input v-model="cliente.razao_social" placeholder="Razão social (opcional)" class="h-11 rounded-lg" />
          </div>

          <!-- Identification -->
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">CPF / CNPJ *</Label>
            <Input v-model="cliente.cpf_cnpj" required placeholder="Ex: 000.000.000-00 ou 00.000.000/0000-00" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Email</Label>
            <Input type="email" v-model="cliente.email" placeholder="email@exemplo.com" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Telefone</Label>
            <Input v-model="cliente.telefone" placeholder="(XX) XXXXX-XXXX" class="h-11 rounded-lg" />
          </div>

          <!-- Address -->
          <div class="space-y-1.5 md:col-span-2 lg:col-span-1">
            <Label class="text-xs font-semibold text-foreground/70">Endereço</Label>
            <Input v-model="cliente.endereco" placeholder="Rua, número, complemento" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Bairro</Label>
            <Input v-model="cliente.bairro" placeholder="Bairro" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Cidade</Label>
            <Input v-model="cliente.cidade" placeholder="Cidade" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Estado</Label>
            <Input v-model="cliente.estado" placeholder="UF" class="h-11 rounded-lg" />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">CEP</Label>
            <Input v-model="cliente.cep" placeholder="00000-000" class="h-11 rounded-lg" />
          </div>

          <!-- Status -->
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold text-foreground/70">Status</Label>
            <Select v-model="cliente.status">
              <SelectTrigger class="w-full bg-background border h-10 rounded-lg">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="1">Ativo</SelectItem>
                <SelectItem :value="0">Inativo</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="md:col-span-3 lg:col-span-1 flex items-center justify-end gap-4 pt-6">
            <Button type="submit" class="h-12 w-full md:w-auto bg-primary hover:bg-primary/90 text-primary-foreground font-black text-base rounded-xl shadow-lg shadow-primary/20 gap-3" :disabled="isSaving">
              <template v-if="isSaving">
                <Loader2 class="w-5 h-5 animate-spin" /> Salvando...
              </template>
              <template v-else>
                <Save class="w-5 h-5" /> Salvar Cliente
              </template>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
