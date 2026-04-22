<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { LogIn, ShieldCheck, AlertCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const username = ref('')
const password = ref('')
const errorMessage = ref<string | null>(null)
const loading = ref(false)

const handleLogin = async () => {
  errorMessage.value = null // Clear previous errors
  loading.value = true // Set loading state

  try {
    await auth.login(username.value, password.value)
    router.push({ name: 'dashboard' }) // Redirect on success
  } catch (error: any) {
    errorMessage.value = error.message || 'Erro ao realizar login.'
  } finally {
    loading.value = false // Clear loading state
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-background p-4 overflow-hidden relative">
    <!-- Soft Decorative Background -->
    <div class="absolute inset-0 z-0 opacity-20 pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/10 blur-[120px] rounded-full text-left"></div>
      <div class="absolute bottom-[-5%] right-[-5%] w-[30%] h-[30%] bg-primary/20 blur-[100px] rounded-full"></div>
    </div>

    <Card class="w-full max-w-[420px] relative z-10 border shadow-2xl animate-in zoom-in duration-500 rounded-2xl bg-card/80 backdrop-blur-xl text-left">
      <CardHeader class="space-y-4 pt-10 pb-6">
        <div class="flex flex-col items-center gap-4 text-center">
          <div class="inline-flex items-center justify-center w-14 h-14 bg-primary text-primary-foreground rounded-2xl shadow-lg shadow-primary/30">
            <ShieldCheck class="w-8 h-8" />
          </div>
          <div class="space-y-1">
            <CardTitle class="text-3xl font-bold tracking-tight">
              Lizzie<span class="text-primary">v3</span>
            </CardTitle>
            <CardDescription class="text-sm font-medium text-muted-foreground">
              SISTEMA DE GESTÃO COMERCIAL
            </CardDescription>
          </div>
        </div>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="handleLogin" class="space-y-5">
          <div class="space-y-4">
            <div class="grid w-full items-center gap-1.5">
              <Label class="text-xs font-semibold text-foreground/70 ml-1">Usuário</Label>
              <Input 
                v-model="username"
                type="text" 
                class="rounded-xl border bg-background/50 h-11 focus-visible:ring-primary" 
                placeholder="nome.vendedor" 
                :disabled="loading"
              />
            </div>
            
            <div class="grid w-full items-center gap-1.5">
              <Label class="text-xs font-semibold text-foreground/70 ml-1">Senha</Label>
              <Input 
                v-model="password"
                type="password" 
                class="rounded-xl border bg-background/50 h-11 focus-visible:ring-primary" 
                placeholder="••••••••" 
                :disabled="loading"
              />
            </div>
            
            <div v-if="errorMessage" class="text-red-500 text-sm text-center">
              {{ errorMessage }}
            </div>
          </div>

          <Button 
            type="submit" 
            size="lg"
            class="w-full bg-primary hover:bg-primary/90 text-primary-foreground h-12 text-sm font-bold rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-[0.98] mt-2"
            :disabled="loading"
          >
            <template v-if="loading">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Entrando...
            </template>
            <template v-else>
              Entrar no Sistema
              <LogIn class="w-4 h-4 ml-2" />
            </template>
          </Button>
        </form>
      </CardContent>

      <CardFooter class="flex flex-col gap-6 pt-4 pb-10 text-center">
        <div class="flex items-center gap-2 justify-center text-xs font-medium text-muted-foreground border-t w-full pt-6">
          <AlertCircle class="w-3.5 h-3.5 text-primary" />
          <span>Acesso Restrito</span>
        </div>
      </CardFooter>
    </Card>

    <!-- Bottom Info -->
    <div class="fixed bottom-8 w-full max-w-[420px] flex justify-between text-[11px] font-medium text-muted-foreground/50 pointer-events-none">
      <span>Lizzie - Amor de Mãe</span>
      <span>v3.0.0</span>
    </div>
  </div>
</template>
