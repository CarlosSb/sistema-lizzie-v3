# Frontend - Sistema Lizzie v3

Aplicação Vue 3 do Sistema Lizzie v3, construída com Vite, TypeScript, Vue Router, Pinia, Tailwind CSS e componentes baseados em reka-ui.

## Requisitos

- Node.js 18+
- npm
- API rodando em `http://localhost:8000` ou URL configurada em `.env`

## Configuração

Crie ou ajuste `frontend/.env`:

```env
VITE_API_BASE_URL="http://localhost:8000"
```

Instale dependências:

```bash
npm install
```

## Comandos

```bash
npm run dev      # servidor Vite
npm run build    # typecheck + build de produção
npm run preview  # preview local do build
```

Também é possível iniciar pela raiz do projeto:

```bash
./lizzie.sh frontend
```

## Estrutura

```text
src/
├── components/
│   ├── ui/              # componentes reutilizáveis
│   ├── PaginationBar.vue
│   ├── PdfViewer.vue
│   └── PedidoPrint.vue
├── layouts/
│   └── AppLayout.vue
├── lib/
│   ├── axios.ts         # cliente HTTP com JWT e refresh token
│   └── utils.ts
├── pages/
│   ├── Clientes/
│   ├── Pedidos/
│   ├── Produtos/
│   ├── Usuario/
│   ├── Vendedores/
│   ├── Dashboard.vue
│   └── Login.vue
├── router/
├── services/
├── stores/
└── main.ts
```

## Rotas Da Aplicação

| Rota | Página |
| --- | --- |
| `/login` | Login |
| `/` | Dashboard |
| `/pedidos` | Lista de pedidos |
| `/pedidos/novo` | Novo pedido |
| `/pedidos/:id` | Detalhes do pedido |
| `/clientes` | Lista de clientes |
| `/clientes/novo` | Novo cliente |
| `/clientes/editar/:id` | Editar cliente |
| `/clientes/:id` | Detalhes do cliente |
| `/produtos` | Lista de produtos |
| `/produtos/novo` | Novo produto |
| `/produtos/editar/:id` | Editar produto |
| `/produtos/:id` | Detalhes do produto |
| `/vendedores` | Lista de vendedores |
| `/vendedores/novo` | Novo vendedor |
| `/vendedores/:id` | Detalhes do vendedor |
| `/vendedores/:id/editar` | Editar vendedor |
| `/perfil` | Perfil do usuário |
| `/perfil/editar` | Editar perfil |

Rotas internas sob `AppLayout` exigem autenticação. Usuários autenticados são redirecionados para o dashboard ao tentar acessar `/login`.

## API E Autenticação

O cliente HTTP fica em `src/lib/axios.ts`.

- Base URL: `VITE_API_BASE_URL`
- Tokens salvos no `localStorage`
- Header `Authorization: Bearer <token>` nas rotas protegidas
- Refresh automático em respostas `401`
- Logout e redirecionamento para `/login` quando o refresh falha

O estado de autenticação fica em `src/stores/auth.ts`, usando Pinia.

## PDFs

O fluxo de PDF usa:

- `PdfViewer.vue` para preview com PDF.js
- `PedidoPrint.vue` para impressão e fallback client-side
- `Pedidos/Detalhes.vue` para abrir modal "Ver PDF" e acionar impressão automática quando o pedido é concluído

Veja também `../PDF_SYSTEM_README.md`.

## UI

Os componentes reutilizáveis vivem em `src/components/ui`. O projeto usa Tailwind CSS 4, `class-variance-authority`, `tailwind-merge`, `reka-ui` e ícones de `lucide-vue-next`.
