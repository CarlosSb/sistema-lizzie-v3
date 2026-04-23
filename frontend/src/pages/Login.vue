<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { LogIn, AlertCircle, Loader2 } from 'lucide-vue-next'
import logomarca from '@/assets/img-logomarca-lizzie.webp'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import PasswordInput from '@/components/ui/PasswordInput.vue'
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
  <div class="min-h-screen flex items-center justify-center bg-background p-4">
    <Card class="w-full max-w-[380px] border shadow-lg rounded-xl bg-card text-left">
      <CardHeader class="space-y-3 pt-6">
        <div class="flex flex-col items-center gap-4 text-center">
          <img
            :src="logomarca"
            alt="Lizzie Logomarca"
            class="h-12 w-auto"
          />
          <div class="space-y-1">
            <CardDescription class="text-sm text-muted-foreground">
              Sistema de gestão comercial
            </CardDescription>
          </div>
        </div>
      </CardHeader>

      <CardContent class="pt-2">
        <form @submit.prevent="handleLogin" class="space-y-4">
          <div class="space-y-3">
            <div class="grid w-full items-center gap-1.5">
              <Label class="text-xs font-semibold text-foreground/70 ml-1">Usuário</Label>
              <Input
                v-model="username"
                type="text"
                class="rounded-lg border bg-background/50 h-10 focus-visible:ring-primary"
                placeholder="nome.vendedor"
                :disabled="loading"
              />
            </div>

            <div class="grid w-full items-center gap-1.5">
              <Label class="text-xs font-semibold text-foreground/70 ml-1">Senha</Label>
              <PasswordInput
                v-model="password"
                class="rounded-lg border bg-background/50 h-10 focus-visible:ring-primary"
                placeholder="••••••••"
                :disabled="loading"
              />
            </div>
          </div>

          <Button type="submit" :disabled="loading" class="w-full rounded-lg h-10 font-semibold bg-primary hover:bg-primary/90 mt-4">
            <template v-if="loading">
              <Loader2 class="w-4 h-4 animate-spin mr-2" />
              Entrando...
            </template>
            <template v-else>
              <LogIn class="w-4 h-4 mr-2" />
              Entrar
            </template>
          </Button>
        </form>
      </CardContent>

      <CardFooter class="pt-2 pb-4 text-center">
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
