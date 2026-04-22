<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Users, Loader2, AlertCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import apiClient from '@/lib/axios'

interface ClienteDetalhes {
  id_cliente: number
  razao_social: string | null
  nome_fantasia: string | null
  responsavel: string | null
  cpf_cnpj: string | null
  email: string | null
  endereco: string | null
  bairro: string | null
  cidade: string | null
  estado: string | null
  cep: string | null
  contato_1: string | null
  contato_2: string | null
  contato_3: string | null
  rota: string | null
  status: number
}

const route = useRoute()
const router = useRouter()

const cliente = ref<ClienteDetalhes | null>(null)
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)

const getStatusClass = (status: number) => {
  return status === 1
    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-200 dark:border-emerald-500/30'
    : 'bg-rose-500/10 text-rose-600 border-rose-200 dark:border-rose-500/30'
}

const fetchClienteDetalhes = async () => {
  isLoading.value = true
  errorMessage.value = null

  try {
    const clienteId = route.params.id
    if (!clienteId) throw new Error('ID do cliente não fornecido.')

    const response = await apiClient.get(`/api/clientes/${clienteId}`)
    cliente.value = response.data?.data || null
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Erro ao carregar detalhes do cliente.'
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchClienteDetalhes)
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <Button variant="ghost" @click="router.push({ name: 'clientes' })" class="px-2">
        <ArrowLeft class="w-5 h-5 mr-2" />
        <span class="font-bold">Voltar para Clientes</span>
      </Button>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Detalhes do Cliente</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">Informações completas do cliente #{{ route.params.id }}</p>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center items-center h-64">
      <Loader2 class="w-10 h-10 animate-spin text-primary" />
      <p class="ml-3 text-lg text-muted-foreground">Carregando detalhes do cliente...</p>
    </div>
    <div v-else-if="errorMessage" class="text-center text-red-500 p-4 border border-red-300 rounded-lg">
      <AlertCircle class="inline-block w-5 h-5 mr-2" />
      {{ errorMessage }}
      <Button variant="link" @click="fetchClienteDetalhes">Tentar novamente</Button>
    </div>
    <div v-else-if="!cliente" class="text-center text-muted-foreground p-4 border rounded-lg">
      <AlertCircle class="inline-block w-5 h-5 mr-2" />
      Cliente não encontrado.
    </div>

    <Card v-else class="rounded-2xl border shadow-sm bg-card overflow-hidden">
      <CardHeader class="border-b bg-muted/20 px-8 py-6">
        <CardTitle class="text-lg font-bold flex items-center gap-2">
          <Users class="w-5 h-5 text-primary" /> Cliente #{{ cliente.id_cliente }}
        </CardTitle>
        <CardDescription class="text-xs font-medium mt-1">Dados cadastrais</CardDescription>
      </CardHeader>
      <CardContent class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">Razão Social</p>
          <p class="font-bold text-sm">{{ cliente.razao_social || '-' }}</p>
        </div>
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">Nome Fantasia</p>
          <p class="font-bold text-sm">{{ cliente.nome_fantasia || '-' }}</p>
        </div>
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">Responsável</p>
          <p class="font-bold text-sm">{{ cliente.responsavel || '-' }}</p>
        </div>
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">CPF/CNPJ</p>
          <p class="font-bold text-sm">{{ cliente.cpf_cnpj || '-' }}</p>
        </div>
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">E-mail</p>
          <p class="font-bold text-sm">{{ cliente.email || '-' }}</p>
        </div>
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">Rota</p>
          <p class="font-bold text-sm">{{ cliente.rota || '-' }}</p>
        </div>
        <div class="space-y-1.5 md:col-span-2">
          <p class="text-xs font-semibold text-muted-foreground">Endereço</p>
          <p class="font-bold text-sm">
            {{ cliente.endereco || '-' }}<span v-if="cliente.bairro">, {{ cliente.bairro }}</span>
          </p>
          <p class="font-bold text-sm">
            {{ cliente.cidade || '-' }}<span v-if="cliente.estado"> - {{ cliente.estado }}</span><span v-if="cliente.cep">, {{ cliente.cep }}</span>
          </p>
        </div>
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">Contato 1</p>
          <p class="font-bold text-sm">{{ cliente.contato_1 || '-' }}</p>
        </div>
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">Contato 2</p>
          <p class="font-bold text-sm">{{ cliente.contato_2 || '-' }}</p>
        </div>
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">Contato 3</p>
          <p class="font-bold text-sm">{{ cliente.contato_3 || '-' }}</p>
        </div>
        <div class="space-y-1.5">
          <p class="text-xs font-semibold text-muted-foreground">Status</p>
          <Badge
            variant="outline"
            :class="['rounded-lg px-3 py-0.5 text-[10px] font-bold tracking-wide uppercase border', getStatusClass(cliente.status)]"
          >
            {{ cliente.status === 1 ? 'ATIVO' : 'INATIVO' }}
          </Badge>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

