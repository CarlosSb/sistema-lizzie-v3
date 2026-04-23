# Sistema Lizzie v3

> Sistema de gestão comercial para loja de roupas infantis - Modernizado para Vue 3 + Lumen

## Stack

| Componente | Tecnologia |
|------------|------------|
| Frontend | Vue 3 + Vite + Naive UI + Tailwind |
| Backend | Lumen 9 + PHP 8.x |
| Database | MySQL 5.7+ |
| Auth | JWT (firebase/php-jwt) |
| PWA | vite-plugin-pwa |

## Quick Start

### Backend
```bash
cd api
composer install
cp .env.example .env
# Configurar banco no .env
php lizzie.php serve
```

### Frontend
```bash
cd frontend
npm install
npm run dev
```

## Lizzie CLI (lizzie.sh)

O projeto inclui um script CLI personalizado `lizzie.sh` para facilitar o desenvolvimento e manutenção. Execute `./lizzie.sh help` para ver todos os comandos disponíveis.

### Verificação de Ambiente
```bash
./lizzie.sh check          # Verifica dependências e ambiente
```

### Instalação
```bash
./lizzie.sh install        # Instala todas as dependências (PHP + Node)
./lizzie.sh install-phpbrew # Instala phpbrew para gerenciar versões PHP
./lizzie.sh install-php82   # Instala PHP 8.2 via phpbrew
```

### Desenvolvimento
```bash
./lizzie.sh dev             # Inicia API + Frontend simultaneamente
./lizzie.sh serve           # Inicia apenas a API (porta 8000)
./lizzie.sh frontend        # Inicia apenas o Frontend (porta 5173)
```

### Build e Banco de Dados
```bash
./lizzie.sh build           # Build do frontend para produção
./lizzie.sh migrate         # Executa migrações do banco
./lizzie.sh rollback        # Reverte migrações
```

### Requisitos Verificados pelo CLI
- **PHP**: 8.1 - 8.2 (recomendado)
- **Composer**: 2.x
- **Node.js**: 18+
- **Extensões PHP**: pdo_mysql, mbstring, xml, json

## Command Line Interface (npm scripts)

O projeto também inclui scripts npm para operações específicas:

### Desenvolvimento
| Comando | Descrição | Exemplo |
|---------|-----------|---------|
| `npm run dev` | Inicia API e frontend simultaneamente | `npm run dev` |
| `npm run dev:api` | Inicia apenas o servidor da API | `npm run dev:api` |
| `npm run dev:frontend` | Inicia apenas o servidor do frontend | `npm run dev:frontend` |

### Instalação e Build
| Comando | Descrição | Exemplo |
|---------|-----------|---------|
| `npm run install:all` | Instala dependências de todos os módulos | `npm run install:all` |
| `npm run build` | Compila o frontend para produção | `npm run build` |
| `npm run build:all` | Compila frontend e otimiza API | `npm run build:all` |

### Banco de Dados
| Comando | Descrição | Exemplo |
|---------|-----------|---------|
| `npm run migrate` | Executa migrações do banco | `npm run migrate` |
| `npm run migrate:rollback` | Reverte última migração | `npm run migrate:rollback` |
| `npm run db:seed` | Popula banco com dados de teste | `npm run db:seed` |

### Utilitários
| Comando | Descrição | Exemplo |
|---------|-----------|---------|
| `npm run test` | Executa testes (configurar pastas) | `npm run test` |

### Comandos do Frontend
Execute dentro da pasta `frontend/`:

```bash
cd frontend
npm run dev      # Desenvolvimento
npm run build    # Build de produção
npm run preview  # Preview do build
```

### Comandos da API
Execute dentro da pasta `api/`:

```bash
cd api
php artisan serve           # Iniciar servidor
php artisan migrate         # Executar migrações
php artisan migrate:rollback # Reverter migração
php artisan db:seed         # Popular banco
composer install            # Instalar dependências PHP
```

### Exemplo de Workflow Completo

```bash
# Instalação inicial
npm run install:all

# Configuração do banco
cp api/.env.example api/.env
# Editar api/.env com configurações do banco
npm run migrate
npm run db:seed

# Desenvolvimento
npm run dev

# Produção
npm run build:all
```

## Credenciais Teste

| Usuário | Senha | Nível |
|---------|-------|-------|
| admin | admin123 | admin |
| teste | teste123 | admin |

## API Endpoints

### Auth
- `POST /api/auth/login` - Login
- `POST /api/auth/refresh` - Refresh token
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Dados usuário

### Clientes
- `GET /api/clientes` - Lista
- `GET /api/clientes/{id}` - Detalhe
- `POST /api/clientes` - Criar
- `PUT /api/clientes/{id}` - Atualizar
- `DELETE /api/clientes/{id}` - Excluir

### Produtos
- `GET /api/produtos` - Lista
- `GET /api/produtos/{id}` - Detalhe
- `POST /api/produtos` - Criar
- `PUT /api/produtos/{id}` - Atualizar
- `DELETE /api/produtos/{id}` - Excluir

### Pedidos
- `GET /api/pedidos` - Lista
- `GET /api/pedidos/{id}` - Detalhe
- `GET /api/pedidos/{id}/calculo` - Cálculo detalhado
- `POST /api/pedidos` - Criar
- `PUT /api/pedidos/{id}` - Atualizar
- `PUT /api/pedidos/{id}/status` - Mudar status
- `DELETE /api/pedidos/{id}` - Excluir

### Dashboard
- `GET /api/dashboard` - Estatísticas

### Relatórios
- `GET /api/relatorios/vendas`
- `GET /api/relatorios/vendedores`
- `GET /api/relatorios/produtos`

### Tempo Real
- `GET /api/alertas/stream` - SSE
- `GET /api/alertas` - Lista
- `GET /api/alertas/nao-lidos` - Contagem
- `PUT /api/alertas/{id}/ler` - Marcar lido

## Estrutura

```
sistema-lizzie-v3/
├── api/                    # Backend Lumen
│   ├── app/
│   │   ├── Http/Controllers/
│   │   └── Services/
│   └── routes/web.php
├── frontend/               # Frontend Vue 3
│   ├── src/
│   │   ├── views/
│   │   ├── composables/
│   │   └── stores/
│   └── public/
└── CREDENCIAIS.md
```

## Build Produção

```bash
# Frontend
cd frontend
npm run build

# O build será gerado em ../public/
```

---

_Lizzie - Amor de Mãe v3 - 2026_