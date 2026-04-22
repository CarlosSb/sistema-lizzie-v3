<script setup lang="ts">
import { ref, onMounted } from 'vue'
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

interface Vendedor {
  id_vendedor: number
  nome_vendedor: string
  contato_vendedor: string
  usuario: string
  status: number
}

const vendedores = ref<Vendedor[]>([])
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)

const fetchVendedores = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const response = await apiClient.get('/api/vendedores')
    vendedores.value = response.data
  } catch (error: any) {
    errorMessage.value = 'Erro ao carregar vendedores.'
    console.error('Failed to fetch vendedores:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchVendedores)

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
        <h1 class="text-3xl font-bold tracking-tight">Vendedores</h1>
        <p class="text-muted-foreground text-sm font-medium mt-1">Gerencie a equipe de vendas.</p>
      </div>
      
      <Button class="bg-primary hover:bg-primary/90 text-primary-foreground font-bold rounded-xl h-11 px-6 shadow-lg shadow-primary/20 flex gap-2">
        <Plus class="w-4 h-4" />
        Novo Vendedor
      </Button>
    </div>

    <!-- Toolbar -->
    <div class="relative flex-1 group w-full text-left">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors z-10" />
        <Input 
          type="text" 
          placeholder="Pesquisar vendedor por nome ou usuário..." 
          class="w-full bg-card border pl-11 h-12 text-sm font-medium focus-visible:ring-primary rounded-xl" 
        />
    </div>

    <!-- Table -->
    <Card class="rounded-2xl border shadow-sm bg-card overflow-hidden">
      <div v-if="isLoading" class="flex justify-center items-center py-10">
          <Loader2 class="w-8 h-8 animate-spin text-primary" />
          <p class="ml-3 text-sm text-muted-foreground">Carregando vendedores...</p>
      </div>
      <div v-else-if="errorMessage" class="text-center text-red-500 p-4">
          <AlertCircle class="inline-block w-5 h-5 mr-2" />
          {{ errorMessage }}
      </div>
      <Table v-else>
        <TableHeader class="bg-muted/30">
          <TableRow class="hover:bg-transparent">
            <TableHead class="w-[80px] text-xs font-bold uppercase tracking-wider py-5 px-8">ID</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Nome</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Contato</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Usuário</TableHead>
            <TableHead class="text-xs font-bold uppercase tracking-wider py-5">Status</TableHead>
            <TableHead class="text-right py-5 px-8"></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="vendedor in vendedores" :key="vendedor.id_vendedor" class="hover:bg-accent/30 transition-colors group">
            <TableCell class="py-5 px-8 font-bold text-sm">#{{ vendedor.id_vendedor }}</TableCell>
            <TableCell class="py-5">
              <div class="flex items-center gap-3 text-left">
                <span class="font-bold text-sm tracking-tight text-foreground/80">{{ vendedor.nome_vendedor }}</span>
                <User class="w-3.5 h-3.5 text-primary opacity-0 group-hover:opacity-100 transition-all" />
              </div>
            </TableCell>
            <TableCell class="py-5 font-bold text-sm text-muted-foreground">{{ vendedor.contato_vendedor }}</TableCell>
            <TableCell class="py-5 text-xs font-semibold text-muted-foreground">{{ vendedor.usuario }}</TableCell>
            <TableCell class="py-5">
              <Badge 
                variant="outline" 
                :class="['rounded-lg px-3 py-0.5 text-[10px] font-bold tracking-wide uppercase border', getStatusClass(vendedor.status)]"
              >
                {{ vendedor.status === 1 ? 'ATIVO' : 'INATIVO' }}
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
                  <DropdownMenuItem class="text-xs font-bold py-2.5 cursor-pointer px-4 text-left">
                    Ver Detalhes
                  </DropdownMenuItem>
                  <DropdownMenuItem class="text-xs font-bold py-2.5 cursor-pointer px-4 text-destructive focus:text-destructive text-left">
                    <Trash2 class="w-4 h-4 mr-2 opacity-50" />
                    Excluir
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
          </TableRow>
          <TableRow v-if="vendedores.length === 0">
              <TableCell colspan="6" class="text-center py-10 text-muted-foreground">Nenhum vendedor encontrado.</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </Card>
  </div>
</template>
