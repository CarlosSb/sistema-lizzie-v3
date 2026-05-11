# NEX-23 — Plano técnico: impressão na finalização do pedido (PDF)

## Objetivo
Ao mudar um pedido para **CONCLUÍDO (status 4)**, o sistema deve gerar o PDF do pedido e acionar o fluxo de impressão com comportamento previsível e sem bloquear a conclusão do pedido.

## Escopo fechado (MVP)
- Manter endpoint atual `POST /api/pdf/pedido/{id}`.
- Disparar impressão automática somente quando transição de status for para `4`.
- Em falha de impressão automática, manter pedido concluído e exibir ação manual de impressão.
- Não adicionar novas dependências.

## Estado atual (base existente)
- Backend já possui geração de PDF em `PdfController@gerarPedido`.
- Frontend já dispara auto impressão em `Pedidos/Detalhes.vue` após update de status.
- Existe fluxo manual de visualização/impressão por modal (`Ver PDF`).

## Gaps técnicos identificados
1. Regras de transição no frontend: hoje só existe `ABERTO -> CANCELADO` e `PENDENTE -> CONCLUÍDO/CANCELADO`, podendo conflitar com operação real.
2. Auto impressão depende de `setTimeout + iframe`, suscetível a bloqueio do navegador e corrida de estado.
3. Falha na impressão é silenciosa para usuário (apenas `console.error`).
4. Não há proteção explícita contra duplo clique em concluir + duplo disparo de impressão em cenários lentos.

## Estratégia simples (sem overengineering)
1. **Concluir pedido primeiro**, sempre via backend.
2. Se backend confirmar status `4`, disparar tentativa única de auto impressão.
3. Se falhar, mostrar notificação curta: "Pedido concluído. Impressão automática não permitida no navegador. Use 'Ver PDF'."
4. Preservar botão `Ver PDF` como fallback oficial.

## Tarefas técnicas (Dev Agent)

### 1) Frontend — fluxo de conclusão e impressão
Arquivo: `frontend/src/pages/Pedidos/Detalhes.vue`
- Garantir que auto impressão rode somente quando status anterior for diferente de `4` e novo status efetivo for `4`.
- Adicionar guarda contra execução duplicada (flag local de operação em andamento).
- Manter `isUpdatingStatus` cobrindo update + tentativa de impressão.
- Em erro de impressão automática, exibir aviso não bloqueante no UI (não modal, não toast externo).

Critério de aceite:
- Ao concluir pedido, status atualiza e tenta imprimir 1 vez.
- Se navegador bloquear impressão, usuário vê aviso e consegue imprimir via `Ver PDF`.

### 2) Frontend — consistência de estados permitidos
Arquivo: `frontend/src/pages/Pedidos/Detalhes.vue`
- Revisar `availableStatuses` para refletir fluxo operacional acordado com produto.
- Remover transições que não devem existir no negócio.

Critério de aceite:
- Select de status exibe apenas transições válidas.

### 3) Backend — validação mínima de status
Arquivo: `api/app/Http/Controllers/PedidoController.php`
- Confirmar que `updateStatus` só aceita status permitidos (já existe) e retorna status final persistido.
- Sem nova rota e sem mudança de contrato JSON.

Critério de aceite:
- Frontend recebe status final correto para decidir auto impressão.

### 4) Teste funcional rápido (manual)
- Cenário A: `PENDENTE -> CONCLUÍDO` com impressão permitida.
- Cenário B: `PENDENTE -> CONCLUÍDO` com bloqueio de popup/impressão.
- Cenário C: clique duplo em "Salvar Status" durante latência.
- Cenário D: erro no endpoint PDF (simular 500) após conclusão.

Critério de aceite:
- Em todos os cenários, pedido conclui sem regressão e existe caminho de impressão manual.

## Riscos e mitigação
- Bloqueio de impressão pelo navegador: mitigado por fallback explícito de "Ver PDF".
- Duplicidade de ação por clique repetido: mitigado por guarda local + botão desabilitado.
- Divergência de regras de negócio de status: validar com produto antes de fechar task 2.

## Fora de escopo
- Fila assíncrona de impressão.
- Serviço externo de spool/impressora.
- Reescrever componente `PedidoPrint.vue`.
- Alterar engine TCPDF/Dompdf.

## Sequência recomendada
1. Implementar tarefa 1.
2. Ajustar tarefa 2 com validação de negócio.
3. Validar tarefa 3 (sem refactor grande).
4. Executar roteiro de testes manuais.

## Pronto para desenvolvimento
Este plano usa apenas estrutura existente do projeto e prioriza entrega rápida com estabilidade operacional para pequenos negócios locais.

## Status de execução (2026-05-11)
- Implementação aplicada em `frontend/src/pages/Pedidos/Detalhes.vue`.
- Entregue:
  - disparo de auto impressão apenas em transição real para status `4`;
  - guarda contra disparo duplicado de update/auto print;
  - aviso visível ao usuário quando auto impressão não é possível;
  - fallback manual preservado via ação `Ver PDF`.
- Pendência para fechamento do card:
  - validação manual dos cenários A/B/C/D no ambiente funcional.
