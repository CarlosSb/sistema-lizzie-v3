# NEX-26 — Plano Operacional (Análise Crítica e Bugs)

## Resumo executivo
A NEX-26 foi tratada como incidente crítico de operação. O foco deste ciclo é reduzir risco de regressão em fluxo de pedido/finalização/impressão, acelerar diagnóstico de bugs e estabelecer rotina curta de validação para evitar retrabalho.

## Prioridades (ordem de execução)
1. Estabilizar bugs de maior impacto no fluxo de venda (bloqueio de operação do cliente).
2. Garantir confiabilidade da finalização do pedido e impressão de comprovante/PDF.
3. Validar regressão dos fluxos críticos com checklist enxuto antes de cada entrega.
4. Separar débitos não críticos para backlog sem travar entrega.

## Backlog operacional da NEX-26
- P0: Bugs que impedem concluir pedido ou gerar saída (tela travada, erro silencioso, ação sem feedback).
- P1: Inconsistências de estado (pedido finaliza parcialmente, dados divergentes entre tela e persistência).
- P1: Falhas de UX operacional que causam erro humano recorrente (botão sem estado de carregamento, mensagens ambíguas).
- P2: Melhorias de robustez e observabilidade (logs de erro acionáveis, mensagens de fallback).

## Delegação por agente
- CTO / Tech Lead (owner técnico)
  - Consolidar matriz de risco por fluxo crítico (pedido, finalização, impressão, persistência).
  - Definir sequência de correção P0/P1 e aprovar escopo mínimo por release.
  - Entregar proposta técnica de correção com impacto, risco e esforço estimado.

- Dev Agent (execução)
  - Corrigir itens P0 aprovados pelo CTO com foco em fluxo de pedido/finalização.
  - Incluir tratamento explícito de erro e feedback de UI nas ações críticas.
  - Abrir PRs pequenos por bug para facilitar validação e rollback.

- QA Agent (validação)
  - Executar checklist de regressão rápida em: criar pedido, finalizar, imprimir PDF/comprovante, reabrir histórico.
  - Validar cenários de falha (timeout, erro de geração, ação duplicada por clique repetido).
  - Publicar resultado binário por item: aprovado/reprovado + evidência curta.

## Riscos e bloqueadores atuais
- Ausência de inventário consolidado dos bugs ativos da NEX-26 no workspace.
- Possível acoplamento entre finalização e impressão gerando regressão cruzada.
- Falta de critérios de aceite operacionais curtos por bug (pode alongar ciclo).

## Próximas ações imediatas
1. CTO publicar inventário objetivo de bugs NEX-26 (P0/P1/P2) com owner e ETA.
2. Dev iniciar correções P0 em lotes pequenos (1 bug por PR) para ganho de throughput.
3. QA rodar checklist mínimo a cada lote e bloquear promoção quando houver regressão em fluxo crítico.
4. CEO consolidar status diário em formato curto: progresso, risco, bloqueio, decisão necessária do founder.

## Cadência operacional recomendada (lean)
- Checkpoint assíncrono 2x por dia (sem reunião):
  - 10:00: status de execução + riscos
  - 17:30: fechamento do dia + plano do próximo ciclo
- Escalonamento ao founder apenas para:
  - bloqueio externo
  - decisão de prioridade entre clientes
  - mudança de escopo/compromisso

## Definição de pronto para encerrar NEX-26
- Todos os P0 resolvidos e validados por QA.
- P1 críticos sem bloqueio operacional do cliente.
- Checklist de regressão crítica 100% aprovado no último ciclo.
- Riscos remanescentes documentados com plano e data.
