# Sistema de Geração de PDF

Documentação do fluxo de geração e visualização de PDFs do Sistema Lizzie v3.

## Visão Geral

O projeto possui geração server-side de documentos em PDF pela API Lumen, com visualização no frontend Vue usando PDF.js. O fluxo principal fica na página de detalhes do pedido, onde o usuário pode abrir o PDF em modal, imprimir e manter fallback client-side quando necessário.

## Componentes

### Backend

| Arquivo | Responsabilidade |
| --- | --- |
| `api/app/Http/Controllers/PdfController.php` | Expõe endpoints de geração, prepara dados e devolve `application/pdf` |
| `api/app/Services/PdfGenerator.php` | Escolhe o template e renderiza com TCPDF ou Dompdf |
| `api/app/Services/PrintData.php` | Estrutura de dados usada pelos templates |
| `api/app/Services/PrintOptions.php` | Opções de impressão: formato, papel, orientação, QR code e cópias |
| `api/app/Services/*Template.php` | Templates HTML dos documentos |

### Frontend

| Arquivo | Responsabilidade |
| --- | --- |
| `frontend/src/components/PdfViewer.vue` | Renderiza PDFs no navegador com PDF.js |
| `frontend/src/components/PedidoPrint.vue` | Fluxo de impressão do pedido, com geração server-side e fallback client-side |
| `frontend/src/pages/Pedidos/Detalhes.vue` | Botão "Ver PDF", modal de preview e impressão automática ao concluir pedido |

## Tipos De Documento

| Documento | Endpoint |
| --- | --- |
| Pedido completo | `POST /api/pdf/pedido/{id}` |
| Etiqueta | `POST /api/pdf/etiqueta/{id}` |
| Carnê | `POST /api/pdf/carne/{id}` |
| Recibo | `POST /api/pdf/recibo/{id}` |
| Relatório de vendas | `POST /api/pdf/relatorio/vendas` |
| Relatório de vendedores | `POST /api/pdf/relatorio/vendedores` |
| Relatório de produtos | `POST /api/pdf/relatorio/produtos` |

Todas as rotas de PDF ficam no grupo protegido por JWT.

## Opções De Requisição

O corpo da requisição pode receber:

| Campo | Tipo | Padrão | Observação |
| --- | --- | --- | --- |
| `format` | string | `pdf` | Formato de saída atualmente esperado |
| `paper_size` | string | `a4` | Ex.: `a4`, `a5`, `letter` |
| `orientation` | string | `portrait` | `portrait` ou `landscape` |
| `include_qr` | boolean | `false` | Etiquetas forçam QR code |
| `copies` | number | `1` | Reservado para controle de cópias |

Exemplo:

```ts
const response = await apiClient.post(
  `/api/pdf/pedido/${id}`,
  {
    format: 'pdf',
    paper_size: 'a4',
    orientation: 'portrait',
    include_qr: true,
  },
  { responseType: 'blob' }
)

const pdfBlob = new Blob([response.data], { type: 'application/pdf' })
const url = URL.createObjectURL(pdfBlob)
```

## Resposta

Os endpoints retornam o conteúdo binário do PDF com headers:

```http
Content-Type: application/pdf
Content-Disposition: inline; filename="pedido-123.pdf"
Cache-Control: private, max-age=0, must-revalidate
```

Em erro, a API retorna JSON com `success: false` e `message`.

## QR Code

Quando `include_qr` está ativo, a API gera um QR code PNG em base64. O conteúdo segue o padrão:

```text
PEDIDO-{id_pedido}-{YYYY-MM-DD}
```

Etiquetas sempre incluem QR code.

## Fluxo No Frontend

1. A página `Pedidos/Detalhes.vue` chama o endpoint server-side com `responseType: 'blob'`.
2. O retorno vira um `Blob` de PDF.
3. O app cria uma URL temporária com `URL.createObjectURL`.
4. `PdfViewer.vue` carrega o PDF e renderiza páginas em canvas.
5. O usuário pode navegar, ajustar zoom e imprimir.
6. Ao fechar o modal, a URL temporária é revogada.

Ao marcar um pedido como concluído, a página tenta gerar e imprimir o PDF automaticamente. Falhas nessa impressão automática são tratadas sem interromper o fluxo do usuário.

## Renderização

`PdfGenerator` usa TCPDF por padrão. Existe suporte a Dompdf por meio de `setEngine('dompdf')`, desde que a chamada seja configurada no serviço.

Os templates renderizam HTML compatível com impressão. Para adicionar um novo documento:

1. Crie uma classe de template em `api/app/Services`.
2. Implemente a renderização recebendo `PrintData` e `PrintOptions`.
3. Registre o novo `templateId` em `PdfGenerator::generate`.
4. Adicione o método e a rota em `PdfController` e `api/routes/web.php`.
5. Consuma a rota no frontend com `responseType: 'blob'`.

## Dependências

Backend:

```bash
cd api
composer require tecnickcom/tcpdf dompdf/dompdf chillerlan/php-qrcode
```

Frontend:

```bash
cd frontend
npm install pdfjs-dist
```

Essas dependências já aparecem nos manifestos atuais do projeto.

## Validação

Comandos úteis:

```bash
cd api
./vendor/bin/phpunit
```

```bash
cd frontend
npm run build
```

## Observações

- Relatórios aceitam filtros como `data_inicio` e `data_fim` quando o controller correspondente usa esses campos.
- O carnê já possui endpoint e template, mas os dados detalhados de parcelas dependem de uma estrutura de pagamentos a ser conectada ao banco.
- O fallback client-side em `PedidoPrint.vue` usa `html2pdf.js`, `html2canvas` e `jsPDF`.

---

Status da documentação: atualizada em maio de 2026.
