import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Hydrate user details on boot (token is already loaded from storage by the store)
const auth = useAuthStore(pinia)
auth.refreshUser()

app.mount('#app')
