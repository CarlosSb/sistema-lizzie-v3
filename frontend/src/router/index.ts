import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../pages/Dashboard.vue'
import Login from '../pages/Login.vue'
import { useAuthStore } from '@/stores/auth' // Import auth store

const routes = [
  {
    path: '/',
    component: () => import('../layouts/AppLayout.vue'),
    meta: { requiresAuth: true }, // All children under AppLayout require authentication by default
    children: [
      {
        path: '',
        name: 'dashboard',
        component: Dashboard
      },
      {
        path: 'pedidos',
        name: 'pedidos',
        component: () => import('../pages/Pedidos/Lista.vue')
      },
      {
        path: 'pedidos/novo',
        name: 'pedido-novo',
        component: () => import('../pages/Pedidos/Form.vue')
      },
      {
        path: 'pedidos/:id',
        name: 'pedido-detalhes',
        component: () => import('../pages/Pedidos/Detalhes.vue')
      },
      {
        path: 'clientes',
        name: 'clientes',
        component: () => import('../pages/Clientes/Lista.vue')
      },
      {
        path: 'clientes/novo',
        name: 'cliente-novo',
        component: () => import('../pages/Clientes/Form.vue')
      },
      {
        path: 'clientes/editar/:id',
        name: 'cliente-editar',
        component: () => import('../pages/Clientes/Form.vue')
      },
      {
        path: 'clientes/:id',
        name: 'cliente-detalhes',
        component: () => import('../pages/Clientes/Detalhes.vue')
      },
      {
        path: 'produtos',
        name: 'produtos',
        component: () => import('../pages/Produtos/Lista.vue')
      },
      {
        path: 'produtos/novo',
        name: 'produto-novo',
        component: () => import('../pages/Produtos/Form.vue')
      },
      {
        path: 'produtos/editar/:id',
        name: 'produto-editar',
        component: () => import('../pages/Produtos/Form.vue')
      },
      {
        path: 'produtos/:id',
        name: 'produto-detalhes',
        component: () => import('../pages/Produtos/Detalhes.vue')
      },
      {
        path: 'vendedores',
        name: 'vendedores',
        component: () => import('../pages/Vendedores/Lista.vue')
      },
      {
        path: 'vendedores/novo',
        name: 'vendedores-novo',
        component: () => import('../pages/Vendedores/Form.vue')
      },
      {
        path: 'vendedores/:id',
        name: 'vendedores-detalhes',
        component: () => import('../pages/Vendedores/Detalhes.vue'),
        props: true
      },
      {
        path: 'vendedores/:id/editar',
        name: 'vendedores-editar',
        component: () => import('../pages/Vendedores/Form.vue'),
        props: true
      },
    ]
  },
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { requiresAuth: false } // Login page does not require authentication
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore() // Access the auth store

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // If route requires auth and user is not authenticated, redirect to login
    next({ name: 'login' })
  } else if (!to.meta.requiresAuth && authStore.isAuthenticated) {
    // If route does NOT require auth (e.g., login page) and user IS authenticated, redirect to dashboard
    next({ name: 'dashboard' })
  } else {
    // Otherwise, proceed
    next()
  }
})

export default router
