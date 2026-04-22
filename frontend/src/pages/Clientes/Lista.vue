<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Search, Users, MoreHorizontal, User, Trash2, Loader2, AlertCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Card } from '@/components/ui/card'
import apiClient from '@/lib/axios'

interface Cliente {
  id_cliente: number
  responsavel: string
  nome_fantasia: string
  cpf_cnpj: string
  status: number
}

const clientes = ref<Cliente[]>([])
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)
const router = useRouter() // Initialize router

const goToClienteDetalhes = (id: number) => {
  router.push({ name: 'cliente-detalhes', params: { id: id.toString() } })
}

const goToEditCliente = (id: number) => {
  router.push({ name: 'cliente-editar', params: { id: id.toString() } })
}

const deleteCliente = async (id: number) => {
  if (window.confirm('Tem certeza que deseja excluir este cliente? Esta ação não pode ser desfeita.')) {
    try {
      await apiClient.delete(`/api/clientes/${id}`)
      // Refresh the list after deletion
      fetchClientes()
    } catch (error: any) {
      errorMessage.value = 'Erro ao excluir cliente.'
      console.error('Failed to delete cliente:', error)
    }
  }
}

const fetchClientes = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const response = await apiClient.get('/api/clientes')
    clientes.value = response.data
  } catch (error: any) {
    errorMessage.value = 'Erro ao carregar clientes.'
    console.error('Failed to fetch clientes:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchClientes)

const getStatusClass = (status: number) => {
  return status === 1 
    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-200 dark:border-emerald-500/30' 
    : 'bg-rose-500/10 text-rose-600 border-rose-200 dark:border-rose-500/30'
}
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Clientes</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">Gerencie a base de clientes do sistema.</p>
      </div>
      
      <Button @click="router.push({ name: 'cliente-novo' })" class="bg-primary hover:bg-primary/90 text-primary-foreground font-bold rounded-xl h-11 px-6 shadow-lg shadow-primary/20 flex gap-2">
        <Plus class="w-4 h-4" />
        Novo Cliente
      </Button>
    </div>

    <!-- Toolbar -->
    <div class="relative flex-1 group w-full text-left">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors z-10" />
        <Input 
          type="text" 
          placeholder="Pesquisar cliente por nome ou CPF/CNPJ..." 
          class="w-full bg-card border pl-11 h-12 text-sm font-medium focus-visible:ring-primary rounded-xl" 
        />
    </div>

    <!-- Table -->
    <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
      <div v-if="isLoading" class="flex justify-center items-center py-10">
          <Loader2 class="w-8 h-8 animate-spin text-primary" />
          <p class="ml-3 text-sm text-muted-foreground">Carregando clientes...</p>
      </div>
      <div v-else-if="errorMessage" class="text-center text-red-500 p-4">
          <AlertCircle class="inline-block w-5 h-5 mr-2" />
          {{ errorMessage }}
      </div>
      <Table v-else>
        <TableHeader class="bg-muted/30">
          <TableRow class="hover:bg-transparent">
            <TableHead class="w-[80px] text-xs font-bold uppercase tracking-wider py-5 px-8">ID</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Nome Fantasia</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Responsável</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">CPF/CNPJ</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Status</TableHead>
            <TableHead class="text-right py-5 px-8"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="cliente in clientes" :key="cliente.id_cliente" class="hover:bg-accent/30 transition-colors group">
            <TableCell class="py-5 px-8 font-bold text-sm">#{{ cliente.id_cliente }}</TableCell>
            <TableCell class="py-5">
              <div class="flex items-center gap-3 text-left">
                <span class="font-bold text-sm tracking-tight text-foreground/80">{{ cliente.nome_fantasia }}</span>
                <User class="w-3.5 h-3.5 text-primary opacity-0 group-hover:opacity-100 transition-all" />
              </div>
            </TableCell>
            <TableCell class="py-5 font-bold text-sm text-muted-foreground">{{ cliente.responsavel }}</TableCell>
            <TableCell class="py-5 text-xs font-semibold text-muted-foreground">{{ cliente.cpf_cnpj }}</TableCell>
            <TableCell class="py-5">
              <Badge 
                variant="outline" 
                :class="['rounded-lg px-3 py-0.5 text-[10px] font-bold tracking-wide uppercase border', getStatusClass(cliente.status)]"
              >
                {{ cliente.status === 1 ? 'ATIVO' : 'INATIVO' }}
              </Badge>
            </TableCell>
            <TableCell class="py-5 px-8 text-right">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon" class="h-9 w-9 rounded-lg hover:bg-accent">
                    <MoreHorizontal class="w-4 h-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="rounded-xl border shadow-xl w-48">
                  <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-wider py-2 opacity-50 px-4 text-left">Ações</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem @click="goToClienteDetalhes(cliente.id_cliente)" class="text-xs font-bold py-2.5 cursor-pointer px-4 text-left">
                    Ver Detalhes
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="goToEditCliente(cliente.id_cliente)" class="text-xs font-bold py-2.5 cursor-pointer px-4 text-left">
                    Editar
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="deleteCliente(cliente.id_cliente)" class="text-xs font-bold py-2.5 cursor-pointer px-4 text-destructive focus:text-destructive text-left">
                    <Trash2 class="w-4 h-4 mr-2 opacity-50" />
                    Excluir
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
          </TableRow>
          <TableRow v-if="clientes.length === 0">
              <TableCell colspan="6" class="text-center py-10 text-muted-foreground">Nenhum cliente encontrado.</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </Card>
  </div>
</template>
