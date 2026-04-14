// src/composables/auth.js
import { ref } from 'vue'

/**
 * Hook personalizado para gerenciamento de autenticação.
 * @returns {{ user: import('vue').Ref<Object|null> }}
 */
export function useAuth() {
  const user = ref(null)

  // TODO: Implementar lógica real de autenticação
  // Exemplo: verificar token, chamar API, etc.

  return { user }
}