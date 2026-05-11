# Sistema Lizzie v3

Sistema de gestão comercial para loja de roupas infantis, com frontend em Vue 3 e API em Lumen/PHP. O projeto cobre cadastro de clientes, produtos, vendedores, pedidos, dashboard, relatórios, estoque, autenticação JWT e geração de PDFs.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Frontend | Vue 3, TypeScript, Vite, Vue Router, Pinia |
| UI | Tailwind CSS 4, reka-ui, lucide-vue-next |
| API | Lumen 9, PHP 8.1+ |
| Banco | MySQL |
| Autenticação | JWT com `firebase/php-jwt` |
| PDF | TCPDF, Dompdf, PDF.js, html2pdf/jsPDF como fallback |

## Requisitos

- PHP 8.1 ou 8.2 recomendado
- Composer 2.x
- Node.js 18+
- npm
- MySQL
- Extensões PHP: `pdo_mysql`, `mbstring`, `xml`, `json`

Use o verificador do projeto para conferir o ambiente:

```bash
./lizzie.sh check
```

## Instalação

```bash
npm run install:all
cp api/.env.example api/.env
```

Edite `api/.env` com os dados do banco:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_lizzie
DB_USERNAME=root
DB_PASSWORD=
```

O frontend lê a URL da API em `frontend/.env`:

```env
VITE_API_BASE_URL="http://localhost:8000"
```

Depois execute as migrations:

```bash
./lizzie.sh migrate
```

## Desenvolvimento

Suba API e frontend juntos:

```bash
./lizzie.sh dev
```

URLs padrão:

- API: `http://localhost:8000`
- Frontend: `http://localhost:5173`

Comandos separados:

```bash
./lizzie.sh serve      # API
./lizzie.sh frontend   # Frontend
```

Também existem scripts npm na raiz:

```bash
npm run dev
npm run dev:api
npm run dev:frontend
npm run build
npm run migrate
npm run migrate:rollback
```

## Build

```bash
./lizzie.sh build
```

Ou diretamente no frontend:

```bash
cd frontend
npm run build
npm run preview
```

## Autenticação

A API usa JWT. O frontend mantém `access_token`, `refresh_token` e usuário no `localStorage`; o cliente Axios injeta o bearer token nas rotas protegidas e tenta renovar o token automaticamente em respostas `401`.

Credenciais de teste documentadas no projeto:

| Usuário | Senha | Nível |
| --- | --- | --- |
| admin | admin123 | admin |
| teste | teste123 | admin |

## Funcionalidades

- Dashboard e estatísticas
- Login, perfil e gerenciamento de usuários
- Clientes
- Produtos
- Vendedores
- Pedidos e itens de pedido
- Estoque, entradas, saídas, reservas e alertas de baixo estoque
- Busca global
- Alertas em tempo real via SSE
- Relatórios de vendas, vendedores, produtos e clientes
- Geração server-side de PDFs para pedidos, etiquetas, carnês, recibos e relatórios

## Rotas Da API

Rotas públicas com prefixo `/api`:

| Método | Rota | Descrição |
| --- | --- | --- |
| `POST` | `/api/auth/login` | Login |
| `POST` | `/api/auth/refresh` | Renovação de token |
| `GET` | `/api/auth/me` | Usuário autenticado |
| `PUT` | `/api/auth/profile` | Atualiza perfil |
| `GET` | `/api/busca` | Busca global |
| `GET` | `/api/alertas/stream` | Stream SSE |
| `GET` | `/api/alertas` | Lista alertas |
| `GET` | `/api/alertas/nao-lidos` | Contagem de não lidos |
| `PUT` | `/api/alertas/{id}/ler` | Marca alerta como lido |

Rotas protegidas por JWT:

| Recurso | Rotas |
| --- | --- |
| Dashboard | `GET /api/dashboard`, `GET /api/dashboard/estatisticas` |
| Usuários | `GET/POST /api/usuarios`, `PUT/DELETE /api/usuarios/{id}` |
| Clientes | `GET/POST /api/clientes`, `GET/PUT/DELETE /api/clientes/{id}` |
| Produtos | `GET/POST /api/produtos`, `GET/PUT/DELETE /api/produtos/{id}` |
| Vendedores | `GET/POST /api/vendedores`, `GET/PUT/DELETE /api/vendedores/{id}` |
| Pedidos | `GET/POST /api/pedidos`, `GET/PUT/DELETE /api/pedidos/{id}`, `PUT /api/pedidos/{id}/status`, `GET /api/pedidos/{id}/calculo`, `GET /api/pedidos/{id}/etiqueta` |
| Itens de pedido | `GET/POST /api/pedidos/{id}/itens`, `PUT/DELETE /api/itens/{id}` |
| Estoque | `GET /api/estoque`, `GET /api/estoque/{id}`, `GET /api/estoque/baixo`, `GET /api/estoque/resumo`, `GET /api/estoque/movimentacao`, `POST /api/estoque/entrada`, `POST /api/estoque/saida`, `POST /api/estoque/reservar`, `POST /api/estoque/liberar` |
| Relatórios | `GET /api/relatorios/vendas`, `GET /api/relatorios/vendedores`, `GET /api/relatorios/produtos`, `GET /api/relatorios/estatisticas`, `GET /api/relatorios/insights`, `GET /api/relatorios/clientes`, `GET /api/relatorios/clientes/{id}` |
| PDFs | `POST /api/pdf/pedido/{id}`, `POST /api/pdf/etiqueta/{id}`, `POST /api/pdf/carne/{id}`, `POST /api/pdf/recibo/{id}`, `POST /api/pdf/relatorio/vendas`, `POST /api/pdf/relatorio/vendedores`, `POST /api/pdf/relatorio/produtos` |

## Estrutura

```text
sistema-lizzie-v3/
├── api/
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Http/Middleware/
│   │   ├── Http/Requests/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/migrations/
│   ├── routes/web.php
│   └── lizzie.php
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── layouts/
│   │   ├── lib/
│   │   ├── pages/
│   │   ├── router/
│   │   ├── services/
│   │   └── stores/
│   └── vite.config.ts
├── lizzie.sh
├── package.json
└── PDF_SYSTEM_README.md
```

## Testes

Backend:

```bash
cd api
./vendor/bin/phpunit
```

Frontend:

```bash
cd frontend
npm run build
```

## Documentação Relacionada

- `PDF_SYSTEM_README.md`: detalhes do sistema de PDFs.
- `frontend/README.md`: comandos e organização do app Vue.

---

_Lizzie - Amor de Mãe v3 - 2026_
