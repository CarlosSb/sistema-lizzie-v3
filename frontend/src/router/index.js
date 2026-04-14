import { createRouter, createWebHistory } from 'vue-router'
import { isAuthenticated, isAdmin } from '../composables/useApi'

const HomeView = () => import('../views/HomeView.vue')
const LoginView = () => import('../views/LoginView.vue')
const PedidosView = () => import('../views/PedidosView.vue')
const ClientesView = () => import('../views/ClientesView.vue')
const ProdutosView = () => import('../views/ProdutosView.vue')
const EstoqueView = () => import('../views/EstoqueView.vue')
const RelatoriosView = () => import('../views/RelatoriosView.vue')
const ImpressaoOSView = () => import('../views/ImpressaoOSView.vue')
const ImpressaoEtiquetaView = () => import('../views/ImpressaoEtiquetaView.vue')
const PerfilView = () => import('../views/PerfilView.vue')
const UsuariosView = () => import('../views/UsuariosView.vue')

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { requiresAuth: true }
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guest: true }
    },
    {
      path: '/clientes',
      name: 'clientes',
      component: ClientesView,
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/produtos',
      name: 'produtos',
      component: ProdutosView,
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/pedidos',
      name: 'pedidos',
      component: PedidosView,
      meta: { requiresAuth: true }
    },
    {
      path: '/estoque',
      name: 'estoque',
      component: EstoqueView,
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/relatorios',
      name: 'relatorios',
      component: RelatoriosView,
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/impressao/:id',
      name: 'impressao',
      component: ImpressaoOSView,
      meta: { requiresAuth: true }
    },
    {
      path: '/etiqueta/:id',
      name: 'etiqueta',
      component: ImpressaoEtiquetaView,
      meta: { requiresAuth: true }
    },
    {
      path: '/perfil',
      name: 'perfil',
      component: PerfilView,
      meta: { requiresAuth: true }
    },
    {
      path: '/usuarios',
      name: 'usuarios',
      component: UsuariosView,
      meta: { requiresAuth: true, requiresAdmin: true }
    }
  ]
})

router.beforeEach((to, from, next) => {
  const authenticated = isAuthenticated()
  
  if (to.meta.requiresAuth && !authenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }
  
  if (to.meta.guest && authenticated) {
    next({ name: 'home' })
    return
  }
  
  if (to.meta.requiresAdmin && !isAdmin()) {
    next({ name: 'home' })
    return
  }
  
  next()
})

export default router