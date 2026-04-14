# Plano de Modernização - Sistema Lizzie v3

> **Versão:** 1.0  
> **Data:** 11/04/2026  
> **Status:** Aprovado para Execução  
> ** stack:** Vue 3 + Lumen + MySQL + PWA

---

## 1. Visão Geral do Projeto

### 1.1 Contexto
Sistema legado de gestão comercial para loja de roupas infantis ("Lizzie - Amor de Mãe") necessitando modernização para stack moderna, escalável e com boa experiência do usuário.

### 1.2 Objetivos
- Modernizar stack tecnológica (legado PHP → Vue 3 + Lumen)
- Melhorar UX/UI (mobile-first, design moderno)
- Implementar tempo real (alertas de novos pedidos)
- Criar relatórios ricos e precisos
- Preparar para futuro app mobile (PWA)
- Manter cálculos precisos de pedidos e descontos

---

## 2. Análise do Sistema Atual

### 2.1 Estrutura do Banco de Dados

#### Tabela: `clientes` (17 colunas)
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id_cliente | int | PK |
| responsavel | varchar(100) | Nome do responsável |
| razao_social | varchar(100) | Razão social |
| nome_fantasia | varchar(100) | Nome fantasia |
| pessoa | int | Tipo (1=Física, 2=Jurídica) |
| cpf_cnpj | varchar(20) | CPF ou CNPJ |
| inscricao_estadual | varchar(20) | IE |
| data_nascimento | date | Data de fundação/nascimento |
| contato_1, 2, 3 | varchar(15) | Telefones |
| email | varchar(100) | E-mail |
| endereco, bairro, cidade, estado, cep | varchar | Endereço |
| rota | varchar(30) | Rota de entrega |
| status | bit(1) | Ativo/Inativo |

#### Tabela: `produtos` (6 colunas)
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id_produto | int | PK |
| referencia | varchar(50) | Código interno |
| produto | varchar(80) | Nome do produto |
| valor_unt_norde | decimal(10,2) | Preço região Norte |
| valor_unt_norte | decimal(10,2) | Preço região Nordeste |
| status | int | Ativo/Inativo |

> **Nota:** Sistema tem apenas preço sem estoque implementado.

#### Tabela: `vendedores` (7 colunas)
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id_vendedor | int | PK |
| nome_vendedor | varchar(100) | Nome |
| contato_vendedor | varchar(15) | Telefone |
| controle_acesso | varchar(20) | Nível de acesso |
| usuario | varchar(20) | Login (unique) |
| senha | varchar(20) | Senha (plain text - migrar!) |
| status | int | Ativo/Inativo |

#### Tabela: `pedidos` (12 colunas)
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id_pedido | int | PK |
| id_cliente | int | FK → clientes |
| id_vendedor | int | FK → vendedores |
| total_pedido | decimal(10,2) | Valor total |
| obs_pedido | text | Observações |
| obs_entrega | text | Observações entrega |
| obs_cancelamento | text | Motivo cancelamento |
| data_entrega | date | Data prevista |
| forma_pag | varchar(50) | Forma pagamento |
| ped_desconto | decimal(10,2) | Desconto no pedido |
| status | int | 1=Aberto, 2=Pendente, 3=Cancelado, 4=Concluído |
| data_pedido | timestamp | Data/hora criação |

#### Tabela: `itens_pedidos` (29 colunas) - **COMPLEXA**
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id_item_pedido | int | PK |
| id_pedido | int | FK → pedidos |
| id_produto | int | FK → produtos |
| id_subcategoria | int | Subcategoria |
| estampa | bit | Tem estampa |
| estampa_lisa | bit | Estampa lisa |
| lisa | int | Quantidade lisa |
| tam_pp, tam_p, tam_m, tam_g, tam_u, tam_rn | int | Tamanhosinfantis |
| ida_1 ao ida_12 | int | Tamanhos femininos |
| masculino | bit | Para menino |
| feminino | bit | Para menina |
| total_item | decimal(10,2) | Total do item |
| val_desconto | decimal(10,2) | Desconto no item |
| tam_pp | int | Tamanho PP adulto |

---

## 3. Stack Tecnológica

### 3.1 Backend
| Componente | Tecnologia | Versão |
|------------|------------|--------|
| Framework | Lumen | 9.x |
| PHP | PHP | 8.1 - 8.2 (8.4 compatível) |
| Auth | JWT (tymon/jwt-auth) | ^1.0 |
| Database | MySQL | 5.7+ |
| CORS | barryvdh/laravel-cors | ^0.11 |

### 3.2 Frontend
| Componente | Tecnologia | Versão |
|------------|------------|--------|
| Framework | Vue.js | 3.4+ |
| Build | Vite | 5.x |
| State | Pinia | 2.x |
| Router | Vue Router | 4.x |
| HTTP | Axios | 1.x |
| UI Lib | **PrimeVue** ou **Naive UI** | Latest |
| Styling | Tailwind CSS | 3.x |
| Icons | Phosphor Icons | Latest |
| Charts | ApexCharts | Latest |
| PWA | vite-plugin-pwa | Latest |
| Dates | date-fns | Latest |

### 3.3 Tempo Real
| Estratégia | Implementação |
|-----------|---------------|
| Primária | Server-Sent Events (SSE) |
| Fallback | Polling (30 segundos) |

---

## 4. Arquitetura do Sistema

### 4.1 Diagrama de Arquitetura

```
┌─────────────────────────────────────────────────────────────────────────┐
│                            FRONTEND (Vue 3)                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │  Pinia   │  │  Router  │  │  Axios   │  │PrimeVue  │  │  PWA     │  │
│  │ (State)  │  │          │  │ + JWT    │  │ (UI)     │  │          │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐    │
│  │                    Composables (Hooks)                        │    │
│  │  useAuth  │  useAlerts  │  usePedido  │  useCálculos         │    │
│  └──────────────────────────────────────────────────────────────┘    │
└───────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │   Axios + JWT Interceptors    │
                    └───────────────┬───────────────┘
                                    │
┌───────────────────────────────────────────────────────────────────────┐
│                         BACKEND (Lumen API)                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────────────────┐ │
│  │  Auth    │  │  Routes  │  │Controllers│  │   Middleware        │ │
│  │ (JWT)    │  │ (REST)   │  │          │  │ (CORS, Auth, Log)   │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────────────────┘ │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐    │
│  │                    Models (Eloquent)                          │    │
│  │  Cliente  │  Produto  │  Pedido  │  ItemPedido  │  Vendedor  │    │
│  └──────────────────────────────────────────────────────────────┘    │
└───────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌───────────────────────────────────────────────────────────────────────┐
│                         DATABASE (MySQL)                              │
│                                                                      │
│   clientes ──── pedidos ──── itens_pedidos ──── produtos            │
│       │                                                            │
│       └────────────── vendedores                                    │
│                                                                      │
│   Tabelas auxiliares: estoques, item_estoques, reg_estoques,        │
│                       subcategoria_produtos                         │
└───────────────────────────────────────────────────────────────────────┘
```

### 4.2 Estrutura de Diretórios

```
sistema-lizzie-v3/
├── api/                          # Lumen API
│   ├── app/
│   │   ├── Console/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ClienteController.php
│   │   │   │   ├── PedidoController.php
│   │   │   │   ├── ProdutoController.php
│   │   │   │   ├── VendedorController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── AlertaController.php
│   │   │   │   └── RelatorioController.php
│   │   │   └── Middleware/
│   │   │       └── JwtMiddleware.php
│   │   ├── Models/
│   │   │   ├── Cliente.php
│   │   │   ├── Pedido.php
│   │   │   ├── Produto.php
│   │   │   ├── ItemPedido.php
│   │   │   └── Vendedor.php
│   │   └── Providers/
│   │       └── AppServiceProvider.php
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   │   └── migrations/       # Não usado (banco existente)
│   ├── public/
│   ├── routes/
│   │   └── web.php
│   ├── storage/
│   ├── .env
│   ├── artisan
│   ├── composer.json
│   └── lizzie.php            # CLI simplificado
│
├── frontend/                    # Vue 3
│   ├── src/
│   │   ├── assets/
│   │   │   ├── styles/
│   │   │   │   ├── main.css
│   │   │   │   └── variables.css
│   │   │   └── images/
│   │   ├── components/
│   │   │   ├── common/        # Button, Input, Modal, Card, Table
│   │   │   ├── layout/        # Header, Sidebar, Footer
│   │   │   ├── pedidos/       # FormItem, ListaItens, ResumoPedido
│   │   │   ├── clientes/      # CardCliente, ListaClientes
│   │   │   └── relatorios/    # ChartCard, TabelaRelatorio
│   │   ├── composables/       # Hooks Vue
│   │   │   ├── useAuth.js
│   │   │   ├── useAlerts.js
│   │   │   ├── usePedido.js
│   │   │   ├── useCalkculos.js
│   │   │   └── useApi.js
│   │   ├── layouts/
│   │   │   ├── AuthLayout.vue
│   │   │   └── AppLayout.vue
│   │   ├── pages/
│   │   │   ├── Login.vue
│   │   │   ├── Dashboard.vue
│   │   │   ├── Pedidos/
│   │   │   │   ├── Lista.vue
│   │   │   │   ├── Novo.vue
│   │   │   │   └── Detalhe.vue
│   │   │   ├── Clientes/
│   │   │   ├── Produtos/
│   │   │   ├── Relatorios/
│   │   │   └── Configuracoes/
│   │   ├── router/
│   │   ├── stores/            # Pinia
│   │   │   ├── auth.js
│   │   │   ├── pedidos.js
│   │   │   ├── clientes.js
│   │   │   └── notifications.js
│   │   ├── services/          # API services
│   │   ├── utils/
│   │   │   ├── calcula.js     # Cálculos de pedido
│   │   │   ├── formatters.js  # Date, Currency
│   │   │   └── validators.js
│   │   ├── App.vue
│   │   └── main.js
│   ├── public/
│   │   ├── manifest.json      # PWA
│   │   └── icons/
│   ├── package.json
│   ├── vite.config.js
│   ├── tailwind.config.js
│   └── index.html
│
├── .github/
│   └── workflows/
│       └── deploy.yml         # CI/CD
│
├── lizzie.sh                  # CLI principal
├── setup.sh                   # Setup ambiente
└── README.md
```

---

## 5. Especificações de Cálculo

### 5.1 Cálculo de Item de Pedido

```javascript
// Estrutura de entrada
{
  id_produto: number,
  produto: string,
  preco_unitario: number,           // valor_unt_norde ou valor_unt_norte
  cantidades_por_tamanho: {
    // Tamanhos infantis
    pp: 0,  p: 5,  m: 10,  g: 5,  u: 0,  rn: 0,
    // Tamanhos femininos
    ida_1: 2, ida_2: 0, ida_4: 0, ida_6: 0,
    ida_8: 0, ida_10: 0, ida_12: 0
  },
  tem_estampa: boolean,
  tem_estampa_lisa: boolean,
  sexo: 'M' | 'F' | 'U',           // masculino, feminino, unissex
  desconto_percentual: 0,          // 0-100
  desconto_valor: 0
}
```

```javascript
// Cálculo
const quantidade_total = Object.values(cantidades_por_tamanho).reduce((a, b) => a + b, 0);
const subtotal = quantidade_total * preco_unitario;
const desconto_percentual_valor = (subtotal * desconto_percentual) / 100;
const total_item = subtotal - desconto_percentual_valor - desconto_valor;
```

### 5.2 Cálculo de Pedido

```javascript
// Cálculo total do pedido
const subtotal_pedido = itens.reduce((sum, item) => sum + item.subtotal, 0);
const total_descontos_itens = itens.reduce((sum, item) => sum + item.desconto_total, 0);
const total_pedido = subtotal_pedido - total_descontos_itens - desconto_pedido;
```

---

## 6. Endpoints da API

### 6.1 Autenticação
| Método | Endpoint | Descrição | Auth |
|--------|----------|-----------|------|
| POST | `/api/auth/login` | Login | Não |
| POST | `/api/auth/refresh` | Atualizar token | JWT |
| POST | `/api/auth/logout` | Logout | JWT |
| GET | `/api/auth/me` | Dados usuário | JWT |

### 6.2 Clientes
| Método | Endpoint | Descrição | Auth |
|--------|----------|-----------|------|
| GET | `/api/clientes` | Lista (paginado) | JWT |
| GET | `/api/clientes/{id}` | Detalhe | JWT |
| POST | `/api/clientes` | Criar | JWT (Admin) |
| PUT | `/api/clientes/{id}` | Atualizar | JWT (Admin) |
| DELETE | `/api/clientes/{id}` | Excluir | JWT (Admin) |

### 6.3 Produtos
| Método | Endpoint | Descrição | Auth |
|--------|----------|-----------|------|
| GET | `/api/produtos` | Lista (paginado) | JWT |
| GET | `/api/produtos/{id}` | Detalhe | JWT |
| POST | `/api/produtos` | Criar | JWT (Admin) |
| PUT | `/api/produtos/{id}` | Atualizar | JWT (Admin) |
| DELETE | `/api/produtos/{id}` | Excluir | JWT (Admin) |

### 6.4 Pedidos
| Método | Endpoint | Descrição | Auth |
|--------|----------|-----------|------|
| GET | `/api/pedidos` | Lista com filtros | JWT |
| GET | `/api/pedidos/{id}` | Detalhe com itens | JWT |
| POST | `/api/pedidos` | Criar pedido | JWT |
| PUT | `/api/pedidos/{id}` | Atualizar | JWT |
| PUT | `/api/pedidos/{id}/status` | Mudar status | JWT |
| DELETE | `/api/pedidos/{id}` | Cancelar | JWT |
| GET | `/api/pedidos/{id}/calculo` | Ver cálculo | JWT |

### 6.5 Vendedores
| Método | Endpoint | Descrição | Auth |
|--------|----------|-----------|------|
| GET | `/api/vendedores` | Lista | JWT |
| GET | `/api/vendedores/{id}` | Detalhe | JWT |
| POST | `/api/vendedores` | Criar | JWT (Admin) |
| PUT | `/api/vendedores/{id}` | Atualizar | JWT (Admin) |

### 6.6 Dashboard
| Método | Endpoint | Descrição | Auth |
|--------|----------|-----------|------|
| GET | `/api/dashboard` | Estatísticas geral | JWT |
| GET | `/api/dashboard/vendas-hoje` | Vendas de hoje | JWT |
| GET | `/api/dashboard/pedidos-ultimos` | Últimos pedidos | JWT |

### 6.7 Alertas (Tempo Real)
| Método | Endpoint | Descrição | Auth |
|--------|----------|-----------|------|
| GET | `/api/alertas/stream` | SSE (event stream) | JWT |
| GET | `/api/alertas` | Lista (polling) | JWT |
| GET | `/api/alertas/nao-lidos` | Contagem não lidos | JWT |
| PUT | `/api/alertas/{id}/ler` | Marcar como lido | JWT |

### 6.8 Relatórios
| Método | Endpoint | Descrição | Auth |
|--------|----------|-----------|------|
| GET | `/api/relatorios/vendas` | Vendas por período | JWT |
| GET | `/api/relatorios/vendedores` | Performance vendedores | JWT |
| GET | `/api/relatorios/produtos` | Produtos mais vendidos | JWT |
| GET | `/api/relatorios/clientes` | Clientes por compras | JWT |
| GET | `/api/relatorios/exportar` | Exportar PDF/Excel | JWT |

---

## 7. Plano de Execução

### Fase 1: Infraestrutura e Autenticação
**Semana 1-2**

- [ ] 1.1 Configurar JWT no Lumen (tymon/jwt-auth)
- [ ] 1.2 Criar middleware de autenticação
- [ ] 1.3 Implementar AuthController com login/logout/refresh
- [ ] 1.4 Configurar CORS
- [ ] 1.5 **Frontend**: Axios com interceptors JWT
- [ ] 1.6 Criar store Pinia auth
- [ ] 1.7 Implementar página de Login
- [ ] 1.8 Proteger rotas (middleware frontend)

### Fase 2: Core - Pedidos (Semana 3-4)
- [ ] 2.1 Reescrever PedidoController com Entity
- [ ] 2.2 Implementar service de cálculo
- [ ] 2.3 CRUD completo de pedidos com validação
- [ ] 2.4 CRUD de itens do pedido
- [ ] 2.5 Fluxo de status (aberto → pendente → concluído/cancelado)
- [ ] 2.6 **Frontend**: Formulário de criação de pedido
- [ ] 2.7 **Frontend**: Lista de pedidos com filtros
- [ ] 2.8 **Frontend**: Detalhe do pedido com itens

### Fase 3: Core - Clientes e Produtos (Semana 5)
- [ ] 3.1 Atualizar ClienteController/Model
- [ ] 3.2 Atualizar ProdutoController/Model
- [ ] 3.3 Atualizar VendedorController/Model
- [ ] 3.4 **Frontend**: Lista de clientes
- [ ] 3.5 **Frontend**: Lista de produtos
- [ ] 3.6 **Frontend**: Busca e filtros

### Fase 4: UI/UX Moderno (Semana 6)
- [ ] 4.1 Integrar biblioteca de componentes (PrimeVue/Naive UI)
- [ ] 4.2 Aplicar Tailwind com design system
- [ ] 4.3 Criar layout responsivo (mobile-first)
- [ ] 4.4 Implementar tema claro/escuro
- [ ] 4.5 Animações de transição
- [ ] 4.6 Refatorar todas as views

### Fase 5: Tempo Real (Semana 7)
- [ ] 5.1 Implementar endpoint SSE para alertas
- [ ] 5.2 Implementar polling fallback
- [ ] 5.3 Badge de notificações no header
- [ ] 5.4 Toast notifications
- [ ] 5.5 Sound alert (opcional)
- [ ] 5.6 **Frontend**: Panel de alertas

### Fase 6: Relatórios (Semana 8)
- [ ] 6.1 Dashboard com cards e gráficos
- [ ] 6.2 Relatório de vendas por período
- [ ] 6.3 Relatório por vendedor
- [ ] 6.4 Relatório de produtos mais vendidos
- [ ] 6.5 Relatório por cliente
- [ ] 6.6 Exportação PDF
- [ ] 6.7 Exportação Excel

### Fase 7: PWA (Semana 9)
- [ ] 7.1 Configurar vite-plugin-pwa
- [ ] 7.2 Service Worker
- [ ] 7.3 Cache strategy
- [ ] 7.4 Manifest.json
- [ ] 7.5 Ícones PWA
- [ ] 7.6 Install prompt
- [ ] 7.7 Push notifications (browser)

### Fase 8: Polish e Deploy (Semana 10)
- [ ] 8.1 Testes QA
- [ ] 8.2 Correção de bugs
- [ ] 8.3 Documentação
- [ ] 8.4 CI/CD GitHub Actions
- [ ] 8.5 Deploy produção

---

## 8. Requisitos Não-Funcionais

### 8.1 Performance
- Page load < 3 segundos
- API response < 500ms (p95)
- Polling intervalo: 30 segundos

### 8.2 Segurança
- HTTPS obrigatório
- JWT com expiração (1 hora access, 7 dias refresh)
- Rate limiting em endpoints sensíveis
- Sanitização de inputs
- CORS restrito

### 8.3 Compatibilidade
- Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- Mobile: iOS 14+, Android 9+
- Progressive enhancement (funciona sem JS)

### 8.4 Acessibilidade
- WCAG 2.1 AA
- Navegação por teclado
- Screen reader compatible
- Contrast ratio adequado

---

## 9. Glossário

| Termo | Definição |
|-------|-----------|
| **Lumen** | Micro-framework Laravel para API |
| **JWT** | JSON Web Token (autenticação stateless) |
| **SSE** | Server-Sent Events (tempo real unidirecional) |
| **PWA** | Progressive Web App |
| **Polling** | Requisições periódicas (fallback para SSE) |
| **Norde** | Preço para região Norte |
| **Norte** | Preço para região Nordeste |
| **Estampa** | Tipo deustomização de tecido |
| **Tamanhos** | PP, P, M, G, U (adultos), RN, Ida 1-12 (femininos) |

---

## 10. Riscos e Mitigações

| Risco | Impacto | Mitigação |
|-------|---------|-----------|
| PHP 8.4 incompatibilidades | Alto | Usar PHP 8.2 no prod; testes exhaustivos |
| Dados legacy inconsistentes | Médio | Validação rígida; logs de erro |
| Performance PWA offline | Baixo | Cache-first strategy |
| JWT expira durante uso | Médio | Auto-refresh; prompt re-login |

---

## 11. Próximos Passos

1. Aprovar este documento
2. Iniciar **Fase 1: Infraestrutura**
3. Setup ambiente de desenvolvimento
4. Primeiro commit com estrutura base

---

*Documento gerado em 11/04/2026*
*Sistema Lizzie v3 - Modernização*