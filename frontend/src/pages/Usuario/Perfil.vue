<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeft,
  User,
  Pencil,
  Loader2,
  AlertCircle,
  Mail,
  Shield
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const isLoading = ref(false)

const user = auth.user

const getNivelLabel = (nivel: string | undefined) => {
  const labels: Record<string, string> = {
    'admin': 'Administrador',
    'user': 'Usuário',
    'vendedor': 'Vendedor'
  }
  return labels[nivel || ''] || 'Usuário'
}

const getNivelColor = (nivel: string | undefined) => {
  const colors: Record<string, string> = {
    'admin': 'bg-red-500/10 text-red-600 border-red-200',
    'user': 'bg-blue-500/10 text-blue-600 border-blue-200',
    'vendedor': 'bg-green-500/10 text-green-600 border-green-200'
  }
  return colors[nivel || ''] || 'bg-gray-500/10 text-gray-600 border-gray-200'
}
</script>

<template>
  <div class="space-y-8 text-left animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-8">
      <div class="flex items-center gap-4">
        <Button
          variant="outline"
          size="icon"
          @click="router.back()"
          class="rounded-xl h-11 w-11"
        >
          <ArrowLeft class="w-4 h-4" />
        </Button>
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Meu Perfil</h1>
          <p class="text-muted-foreground text-sm font-medium mt-1">Gerencie suas informações pessoais</p>
        </div>
      </div>

      <Button
        @click="router.push('/perfil/editar')"
        variant="outline"
        class="rounded-xl h-11 px-6"
      >
        <Pencil class="w-4 h-4 mr-2" />
        Editar Perfil
      </Button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center items-center py-20">
      <Loader2 class="w-8 h-8 animate-spin text-primary" />
      <p class="ml-3 text-sm text-muted-foreground">Carregando...</p>
    </div>

    <!-- Error -->
    <div v-else-if="!user" class="text-center text-red-500 p-8">
      <AlertCircle class="inline-block w-6 h-6 mr-2" />
      Erro ao carregar informações do usuário.
    </div>

    <!-- Profile Content -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Main Info -->
      <Card class="lg:col-span-2 rounded-2xl border shadow-sm bg-card overflow-hidden">
        <CardHeader class="border-b bg-muted/20 px-8 py-6 flex flex-row justify-between items-center">
          <div>
            <CardTitle class="text-lg font-bold flex items-center gap-2">
              <User class="w-5 h-5 text-primary" />
              {{ user.nome || user.usuario || 'Usuário' }}
            </CardTitle>
            <CardDescription class="text-xs font-medium mt-1">Informações pessoais</CardDescription>
          </div>

          <Badge
            variant="outline"
            :class="['rounded-lg px-3 py-0.5 text-[10px] font-bold tracking-wide uppercase border', getNivelColor(user.nivel)]"
          >
            <Shield class="w-3 h-3 mr-1" />
            {{ getNivelLabel(user.nivel) }}
          </Badge>
        </CardHeader>

        <CardContent class="p-8 space-y-6">
          <!-- Basic Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1">
              <p class="text-xs font-semibold text-muted-foreground">Nome Completo</p>
              <p class="text-sm font-bold">{{ user.nome || 'Não informado' }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-xs font-semibold text-muted-foreground">Usuário</p>
              <p class="text-sm font-bold">{{ user.usuario || 'Não informado' }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-xs font-semibold text-muted-foreground">Email</p>
              <p class="text-sm font-bold flex items-center">
                <Mail class="w-4 h-4 mr-2 text-muted-foreground" />
                email@exemplo.com
              </p>
            </div>
            <div class="space-y-1">
              <p class="text-xs font-semibold text-muted-foreground">Nível de Acesso</p>
              <p class="text-sm font-bold">{{ getNivelLabel(user.nivel) }}</p>
            </div>
          </div>

          <Separator />

          <!-- Account Info -->
          <div>
            <h4 class="text-sm font-bold mb-4">Informações da Conta</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-1">
                <p class="text-xs font-semibold text-muted-foreground">Status da Conta</p>
                <Badge variant="outline" class="bg-emerald-500/10 text-emerald-600 border-emerald-200">
                  Ativa
                </Badge>
              </div>
              <div class="space-y-1">
                <p class="text-xs font-semibold text-muted-foreground">Último Acesso</p>
                <p class="text-sm font-bold">{{ new Date().toLocaleDateString('pt-BR') }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Stats Card -->
      <Card class="rounded-2xl border shadow-sm bg-card">
        <CardHeader>
          <CardTitle class="text-lg">Resumo da Conta</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium">Nível</span>
            <Badge variant="outline" :class="getNivelColor(user.nivel)">
              {{ getNivelLabel(user.nivel) }}
            </Badge>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium">Status</span>
            <span class="text-sm font-bold text-emerald-600">Ativo</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium">Membro desde</span>
            <span class="text-sm font-bold">{{ new Date().toLocaleDateString('pt-BR') }}</span>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
