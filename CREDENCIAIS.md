# Credenciais de Teste - Sistema Lizzie

> **ATENÇÃO**: Arquivo apenas para uso interno em desenvolvimento/teste.

## Usuários Atualizados (bcrypt)

| Usuário | Senha | Nível | Status |
|--------|-------|-------|-------|--------|
| admin | admin123 | admin | ativo ✅ |
| teste | teste123 | admin | ativo ✅ |

## Usuários Legados (base64 - ainda funcionam)

| Usuário | Senha | Nível | Notas |
|--------|-------|-------|-------|
| clayton | clayton123 | admin | Base64 |
| savio | savio123 | vend | Base64 |
| jardel | jardel123 | admin | Base64 |
| lya | lya123 | admin | Base64 |
| irismar | irismar123 | vend | Base64 |
| arthur | arthur123 | vend | Base64 |
| caio | caio123 | admin | Base64 |
| isa | isa123 | admin | Base64 |
| julia | julia123 | admin | Base64 |
| fatima | fatima123 | admin | Base64 |

## Novos Usuários Cadastrados

- **Usuario Teste** (teste/teste123) - criado em 11/04/2026

## Notas Técnicas

- Sistema suporta ambos os formatos: bcrypt (novo) e base64 (legado)
- O AuthController detecta automaticamente pelo prefixo `$2y$`
- Campo `senha` foi ampliado para VARCHAR(255)

## API Endpoints

```bash
# Login
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"usuario": "teste", "senha": "teste123"}'
```

---

_Arquivo gerado em 11/04/2026_