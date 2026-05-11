# NEX-21 - Impressão de finalização do pedido (PDF)

## Objetivo
Garantir que, ao finalizar um pedido (`status = 4`), o sistema gere e dispare a impressão do PDF de forma confiável, com fallback claro para o usuário.

## Estado atual (base já existente)
- Backend já possui endpoint: `POST /api/pdf/pedido/{id}`.
- Frontend já chama esse endpoint ao mudar status para concluído em `frontend/src/pages/Pedidos/Detalhes.vue` (`updateOrderStatus` -> `autoPrintCompletedOrder`).
- Geração é server-side (TCPDF via `PdfGenerator`) e retorno em `blob`.

## Gaps identificados
1. Fluxo silencioso em falha: se impressão automática falha, usuário não recebe feedback.
2. Dependência de `setTimeout + iframe.print()`: propenso a bloqueio de navegador/popup.
3. Não há validação manual mínima do fluxo no fechamento do pedido (cenário crítico).
4. Lógica de impressão está duplicada em pontos diferentes da mesma tela.

## Escopo técnico (simples)
- Não alterar arquitetura de PDF.
- Não introduzir novas dependências.
- Consolidar apenas o fluxo de finalização com comportamento previsível.

## Tarefas para Dev Agent

### 1) Centralizar geração/impressão em função única
- Arquivo: `frontend/src/pages/Pedidos/Detalhes.vue`
- Ação:
  - Criar função única para:
    - gerar blob PDF (`/api/pdf/pedido/:id`),
    - tentar `print()` automático,
    - fallback para abrir preview/modal quando `print` não ocorrer.
- Critério de aceite:
  - Sem código duplicado de geração de PDF na tela.

### 2) Melhorar feedback no fechamento
- Arquivo: `frontend/src/pages/Pedidos/Detalhes.vue`
- Ação:
  - Ao concluir pedido:
    - manter atualização de status;
    - se impressão automática falhar, exibir mensagem não-bloqueante (`pdfError`) com ação “Visualizar PDF”.
- Critério de aceite:
  - Usuário nunca fica sem resposta quando falhar impressão.

### 3) Garantir fallback explícito
- Arquivos:
  - `frontend/src/pages/Pedidos/Detalhes.vue`
  - (opcional) `frontend/src/components/PdfViewer.vue` apenas se necessário.
- Ação:
  - Em qualquer falha de impressão automática, abrir modal com PDF pronto para impressão manual.
- Critério de aceite:
  - Concluir pedido sempre resulta em: impressão automática **ou** preview imprimível.

### 4) Endurecer tratamento de erro da requisição PDF
- Arquivo: `frontend/src/pages/Pedidos/Detalhes.vue`
- Ação:
  - Tratar falhas de `blob`/status HTTP com mensagem amigável.
  - Evitar `console.error` sem retorno visual quando for ação crítica.
- Critério de aceite:
  - Erros de endpoint de PDF são visíveis e recuperáveis pelo operador.

### 5) Validação manual mínima (sem overtesting)
- Cenários:
  1. `PENDENTE -> CONCLUÍDO` com sucesso de impressão automática.
  2. `PENDENTE -> CONCLUÍDO` com falha simulada no endpoint PDF e fallback para modal.
  3. Pedido não concluído não deve disparar impressão automática.
- Evidência:
  - Checklist curto no PR + 2 prints da UI (sucesso e fallback).

## Riscos técnicos e mitigação
- Bloqueio de impressão por navegador:
  - Mitigar com fallback para preview/modal.
- Regressão no fluxo de status:
  - Mitigar mantendo `PUT /status` inalterado e isolando apenas pós-ação.
- Vazamento de URL blob:
  - Mitigar com `URL.revokeObjectURL` no fechamento/troca.

## Fora de escopo
- Novo template PDF.
- Alterações de layout do documento.
- Filas assíncronas de impressão.
- Refatoração ampla do módulo de pedidos.

## Definição de pronto (DoD)
- Código de impressão pós-conclusão centralizado.
- Fallback funcional e visível para o usuário.
- Sem novas dependências.
- Validação manual dos 3 cenários concluída.
