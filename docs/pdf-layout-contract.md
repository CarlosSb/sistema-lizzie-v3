# PDF Layout Contract - Pedido

## Objetivo
Garantir alta fidelidade entre:
- Previa de impressao (frontend)
- PDF final gerado no backend

Este contrato define estrutura, hierarquia visual e regras de conteudo.

## Estrutura Canonica
1. Header
2. Dados do Cliente (apenas modo `complete`)
3. Itens do Pedido
4. Resumo e Pagamento
5. Informacoes Adicionais (apenas modo `complete` e quando houver conteudo)
6. Assinaturas
7. Rodape

## Regras de Modo
- `complete`: renderiza todas as secoes.
- `summary`: omite "Dados do Cliente" e "Informacoes Adicionais".

## Tokens Visuais
- Fonte base: sans-serif
- Tamanho base: `10px` no PDF; `14px` equivalente na previa
- Titulo de secao: peso `700`, divisor inferior
- Borda padrao: `1px solid` em tons de cinza frio
- Fundo de caixas informativas: cinza claro
- Tabela de itens:
  - Cabecalho com fundo cinza claro
  - Linhas com divisor horizontal
  - Linha final de total com destaque

## Mapeamento de Dados
- `order.number`: `id_pedido`
- `order.date`: `data_pedido`
- `order.statusLabel`: derivado de `status`
- `customer.displayName`: `nome_fantasia || razao_social`
- `customer.contacts`: `contato_1`, `email`, `cidade/estado`
- `items[]`:
  - `name`: `produto`
  - `reference`: `referencia`
  - `quantity`: soma de tamanhos
  - `sizes`: lista formatada `P:1 | M:2`
  - `unitPrice`: `total_item / quantity`
  - `total`: `total_item`
- `payment.method`: `forma_pag`
- `payment.discount`: `ped_desconto`
- `payment.total`: `total_pedido`

## Regras de Sanitizacao
- Campos nulos devem virar `-` na previa e string vazia no PDF quando apropriado.
- Campos nao escalares devem ser normalizados para string.

## Critérios de Aceite Visual
- Hierarquia de secoes idêntica entre previa e PDF.
- Labels e conteudo na mesma ordem.
- Densidade de espacos semelhante.
- Quebras de linha aceitaveis sem perder informacao.
- Modo `summary` com mesmas omissoes em previa e PDF.

## Checklist de Regressao
- Pedido com poucos itens
- Pedido com muitos itens
- Pedido com observacoes longas
- Pedido sem cliente completo
- Pedido com desconto
