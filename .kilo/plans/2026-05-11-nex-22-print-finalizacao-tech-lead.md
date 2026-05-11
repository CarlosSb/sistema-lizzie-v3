# NEX-22 - Impressão de finalização do pedido (PDF)

## Escopo objetivo
Garantir que, ao alterar pedido para `CONCLUÍDO (4)`, o sistema gere o PDF server-side e dispare a impressão com fallback seguro, sem bloquear a operação do usuário.

## Estado atual
- Backend de PDF já existe (`POST /api/pdf/pedido/{id}`).
- Frontend já tenta impressão automática ao concluir pedido.
- Risco principal era confiabilidade da impressão (timing fixo antes de `iframe` estar pronto).

## Ajuste aplicado nesta heartbeat
- Padronizado fluxo de impressão com:
  - tentativa por `iframe.onload`
  - fallback por timeout (`1200ms`)
  - fallback final em nova aba se `print()` falhar
  - limpeza segura de `iframe` e `ObjectURL`

## Tarefas técnicas (Dev Agent)
1. Validar fluxo manualmente em Chrome e Edge:
- Pedido `PENDENTE -> CONCLUÍDO` deve abrir diálogo de impressão.
- Em falha de `iframe.print`, deve abrir nova aba para impressão.

2. Garantir não regressão:
- Botão `Ver PDF` continua funcionando.
- Impressão manual (`Imprimir Completo/Resumido`) continua funcionando.

3. Ajuste opcional de UX (se aprovado):
- Adicionar toast discreto: "Pedido concluído. Enviando para impressão...".

## Critérios de aceite
- Atualizar status para `CONCLUÍDO` não gera erro visível ao usuário.
- Tentativa de impressão ocorre automaticamente após conclusão.
- Falha de impressão não impede status concluído.
- Não há vazamento de `ObjectURL` após impressão.

## Riscos e mitigação
- Bloqueador de pop-up pode impedir fallback em nova aba.
- Mitigação: manter caminho primário por `iframe` e instruir operação a permitir pop-up do domínio.
