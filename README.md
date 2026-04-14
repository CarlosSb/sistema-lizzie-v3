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