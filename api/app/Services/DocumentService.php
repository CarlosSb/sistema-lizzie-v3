<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentService
{
    private PdfGenerator $pdfGenerator;
    private string $baseDir;
    private int $ttlSeconds;

    public function __construct(PdfGenerator $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
        $this->baseDir = storage_path('app/documents');
        $ttlHours = (int)env('DOCUMENT_TTL_HOURS', 168); // 7 days default
        $this->ttlSeconds = max(1, $ttlHours) * 3600;
    }

    public function generatePedidoDocument(int $pedidoId, array $options = []): array
    {
        $this->cleanupExpiredDocuments();

        $template = (string)($options['template'] ?? 'complete');
        $printOptions = $this->createPrintOptions($options);

        $pedido = DB::table('pedidos')
            ->select('id_pedido', 'status', 'total_pedido', 'data_pedido', 'id_cliente', 'id_vendedor')
            ->where('id_pedido', $pedidoId)
            ->first();

        if (!$pedido) {
            throw new \RuntimeException('Pedido não encontrado');
        }

        $signature = $this->buildPedidoSignature($pedidoId, $template, $printOptions);
        $documentId = 'pedido_' . $pedidoId . '_' . substr($signature, 0, 16);
        $documentPath = $this->baseDir . '/' . $documentId . '.pdf';
        $metadataPath = $this->baseDir . '/' . $documentId . '.json';

        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir, 0775, true);
        }

        if (!is_file($documentPath)) {
            $data = $this->preparePedidoData($pedidoId);
            $this->logNonScalarPedidoFields($pedidoId, $data);
            $this->addQrCodeToData($data, $printOptions);
            $pdfContent = $this->pdfGenerator->generate($data, $printOptions);
            file_put_contents($documentPath, $pdfContent);
        }

        $size = filesize($documentPath) ?: 0;
        $metadata = [
            'document_id' => $documentId,
            'type' => 'pedido',
            'entity_id' => $pedidoId,
            'template' => $template,
            'version' => $signature,
            'filename' => 'pedido-' . $pedidoId . '.pdf',
            'content_type' => 'application/pdf',
            'size' => $size,
            'created_at' => date('c'),
            'content_url' => '/api/documents/' . $documentId . '/content',
            'metadata_url' => '/api/documents/' . $documentId . '/metadata',
        ];

        file_put_contents($metadataPath, json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $metadata;
    }

    public function buildPedidoPreviewModel(int $pedidoId, array $options = []): array
    {
        $data = $this->preparePedidoData($pedidoId);
        $mode = (string)($options['template'] ?? 'complete');
        $isSummary = $mode === 'summary';

        $items = array_map(function($item) {
            $quantity = $this->sumQuantidades($item);
            $unitPrice = $quantity > 0 ? ((float)($item->total_item ?? 0) / $quantity) : 0.0;

            return [
                'id' => (int)($item->id_item_pedido ?? 0),
                'name' => $this->safeText($item->produto ?? '-'),
                'reference' => $this->safeText($item->referencia ?? '-'),
                'quantity' => $quantity,
                'sizes' => $this->getSizeText($item),
                'unit_price' => $unitPrice,
                'unit_price_formatted' => $this->formatCurrency($unitPrice),
                'total' => (float)($item->total_item ?? 0),
                'total_formatted' => $this->formatCurrency((float)($item->total_item ?? 0)),
            ];
        }, $data->itens);

        $customerName = $this->safeText($data->cliente->nome_fantasia ?? $data->cliente->razao_social ?? $data->pedido->razao_social ?? '-');
        $discount = (float)($data->pedido->ped_desconto ?? 0);
        $total = (float)($data->pedido->total_pedido ?? 0);
        $notesPedido = $this->safeText($data->pedido->obs_pedido ?? '-');
        $notesEntrega = $this->safeText($data->pedido->obs_entrega ?? '-');

        return [
            'mode' => $mode,
            'mode_label' => $isSummary ? 'Resumido' : 'Completo',
            'show_client_section' => !$isSummary,
            'show_additional_info' => !$isSummary && ($notesPedido !== '-' || $notesEntrega !== '-'),
            'order' => [
                'number' => (int)($data->pedido->id_pedido ?? 0),
                'date' => $this->formatDateLikeTemplate($data->pedido->data_pedido ?? null),
                'status' => (int)($data->pedido->status ?? 0),
                'status_label' => $this->statusLabel((int)($data->pedido->status ?? 0)),
            ],
            'customer' => [
                'name' => $customerName,
                'responsible' => $this->safeText($data->cliente->responsavel ?? '-'),
                'document' => $this->safeText($data->cliente->cpf_cnpj ?? '-'),
                'email' => $this->safeText($data->cliente->email ?? '-'),
                'contact' => $this->safeText($data->cliente->contato_1 ?? '-'),
                'city_state' => $this->safeText($data->cliente->cidade ?? '-') . ' / ' . $this->safeText($data->cliente->estado ?? '-'),
                'address' => $this->safeText($data->cliente->endereco ?? '-') . ', '
                    . $this->safeText($data->cliente->bairro ?? '-') . ' - '
                    . $this->safeText($data->cliente->cep ?? '-'),
            ],
            'items' => $items,
            'payment' => [
                'method' => $this->safeText($data->pedido->forma_pag ?? '-'),
                'discount' => $discount,
                'discount_formatted' => $this->formatCurrency($discount),
                'total' => $total,
                'total_formatted' => $this->formatCurrency($total),
            ],
            'notes' => [
                'pedido' => $notesPedido,
                'entrega' => $notesEntrega,
            ],
            'signature_name' => $customerName,
            'generated_at' => $data->generatedAt->format(DATE_ATOM),
            'generated_at_formatted' => $this->formatDateTimeLikeTemplate($data->generatedAt),
            'footer_text' => 'Sistema Lizzie - Gestão Empresarial | Este documento é oficial e foi gerado automaticamente pelo sistema.',
            'layout_version' => 'pedido-template-v2',
        ];
    }

    public function cleanupNow(): array
    {
        return $this->cleanupExpiredDocuments();
    }

    private function cleanupExpiredDocuments(): array
    {
        if (!is_dir($this->baseDir)) {
            return [
                'ttl_hours' => (int)($this->ttlSeconds / 3600),
                'removed_metadata' => 0,
                'removed_pdfs' => 0,
                'removed_orphan_pdfs' => 0,
            ];
        }

        $now = time();
        $jsonFiles = glob($this->baseDir . '/*.json') ?: [];
        $removedMetadata = 0;
        $removedPdfs = 0;
        $removedOrphanPdfs = 0;

        foreach ($jsonFiles as $jsonPath) {
            $mtime = @filemtime($jsonPath);
            if ($mtime === false) {
                continue;
            }

            if (($now - $mtime) <= $this->ttlSeconds) {
                continue;
            }

            $documentId = pathinfo($jsonPath, PATHINFO_FILENAME);
            $pdfPath = $this->baseDir . '/' . $documentId . '.pdf';

            if (@unlink($jsonPath)) {
                $removedMetadata++;
            }
            if (is_file($pdfPath)) {
                if (@unlink($pdfPath)) {
                    $removedPdfs++;
                }
            }
        }

        // Cleanup orphan PDFs without metadata, using PDF mtime.
        $pdfFiles = glob($this->baseDir . '/*.pdf') ?: [];
        foreach ($pdfFiles as $pdfPath) {
            $documentId = pathinfo($pdfPath, PATHINFO_FILENAME);
            $jsonPath = $this->baseDir . '/' . $documentId . '.json';
            if (is_file($jsonPath)) {
                continue;
            }

            $mtime = @filemtime($pdfPath);
            if ($mtime === false) {
                continue;
            }

            if (($now - $mtime) > $this->ttlSeconds) {
                if (@unlink($pdfPath)) {
                    $removedOrphanPdfs++;
                }
            }
        }

        return [
            'ttl_hours' => (int)($this->ttlSeconds / 3600),
            'removed_metadata' => $removedMetadata,
            'removed_pdfs' => $removedPdfs,
            'removed_orphan_pdfs' => $removedOrphanPdfs,
        ];
    }

    public function getMetadata(string $documentId): ?array
    {
        $metadataPath = $this->baseDir . '/' . $documentId . '.json';
        if (!is_file($metadataPath)) {
            return null;
        }

        $raw = file_get_contents($metadataPath);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function getDocumentContent(string $documentId): ?array
    {
        $metadata = $this->getMetadata($documentId);
        if (!$metadata) {
            return null;
        }

        $documentPath = $this->baseDir . '/' . $documentId . '.pdf';
        if (!is_file($documentPath)) {
            return null;
        }

        $content = file_get_contents($documentPath);
        if ($content === false) {
            return null;
        }

        return [
            'metadata' => $metadata,
            'content' => $content,
        ];
    }

    private function buildPedidoSignature(int $pedidoId, string $template, PrintOptions $options): string
    {
        $pedido = DB::table('pedidos')
            ->select('id_pedido', 'status', 'total_pedido', 'ped_desconto', 'forma_pag', 'data_pedido')
            ->where('id_pedido', $pedidoId)
            ->first();

        $itens = DB::table('itens_pedidos')
            ->where('id_pedido', $pedidoId)
            ->orderBy('id_item_pedido')
            ->get(['id_item_pedido', 'id_produto', 'total_item', 'val_desconto']);

        $payload = [
            'pedido' => $pedido,
            'itens' => $itens,
            'template' => $template,
            'paper' => $options->paperSize,
            'orientation' => $options->orientation,
            'include_qr' => $options->includeQR,
            'layout_version' => 'pedido-template-v2',
        ];

        return sha1(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function preparePedidoData(int $id): PrintData
    {
        $pedido = DB::table('pedidos')
            ->select('pedidos.*', 'clientes.*', 'vendedores.nome_vendedor')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->where('pedidos.id_pedido', $id)
            ->first();

        if (!$pedido) {
            throw new \RuntimeException('Pedido não encontrado');
        }

        $itens = DB::table('itens_pedidos')
            ->select('itens_pedidos.*', 'produtos.produto', 'produtos.referencia')
            ->leftJoin('produtos', 'itens_pedidos.id_produto', '=', 'produtos.id_produto')
            ->where('itens_pedidos.id_pedido', $id)
            ->get();

        $data = new PrintData();
        $data->pedido = $pedido;
        $data->cliente = $pedido;
        $data->vendedor = (object)['nome_vendedor' => $pedido->nome_vendedor];
        $data->itens = $itens->toArray();

        return $data;
    }

    private function addQrCodeToData(PrintData $data, PrintOptions $options): void
    {
        if ($options->includeQR) {
            $qrData = 'PEDIDO-' . $data->pedido->id_pedido . '-' . date('Y-m-d');
            $data->qrCodeUrl = $this->pdfGenerator->generateQRCode($qrData, 100);
        }
    }

    private function createPrintOptions(array $options): PrintOptions
    {
        $printOptions = new PrintOptions('pedido');
        $printOptions->format = (string)($options['format'] ?? 'pdf');
        $printOptions->paperSize = (string)($options['paper_size'] ?? 'a4');
        $printOptions->orientation = (string)($options['orientation'] ?? 'portrait');
        $printOptions->includeQR = (bool)($options['include_qr'] ?? true);
        $printOptions->mode = (string)($options['template'] ?? 'complete');

        return $printOptions;
    }

    private function logNonScalarPedidoFields(int $pedidoId, PrintData $data): void
    {
        $fields = [
            'cliente.nome_fantasia' => $data->cliente->nome_fantasia ?? null,
            'cliente.razao_social' => $data->cliente->razao_social ?? null,
            'cliente.responsavel' => $data->cliente->responsavel ?? null,
            'cliente.cpf_cnpj' => $data->cliente->cpf_cnpj ?? null,
            'cliente.email' => $data->cliente->email ?? null,
            'cliente.contato_1' => $data->cliente->contato_1 ?? null,
            'cliente.endereco' => $data->cliente->endereco ?? null,
            'cliente.bairro' => $data->cliente->bairro ?? null,
            'cliente.cidade' => $data->cliente->cidade ?? null,
            'cliente.estado' => $data->cliente->estado ?? null,
            'cliente.cep' => $data->cliente->cep ?? null,
            'pedido.forma_pag' => $data->pedido->forma_pag ?? null,
            'pedido.obs_pedido' => $data->pedido->obs_pedido ?? null,
            'pedido.obs_entrega' => $data->pedido->obs_entrega ?? null,
        ];

        $nonScalar = [];
        foreach ($fields as $path => $value) {
            if ($value !== null && !is_scalar($value)) {
                $nonScalar[$path] = gettype($value);
            }
        }

        if (!empty($nonScalar)) {
            Log::warning('PDF pedido com campos não escalares', [
                'pedido_id' => $pedidoId,
                'fields' => $nonScalar,
            ]);
        }
    }

    private function sumQuantidades($item): int
    {
        $sizes = ['tam_pp', 'tam_p', 'tam_m', 'tam_g', 'tam_u', 'tam_rn', 'ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12', 'lisa'];
        return array_reduce($sizes, fn($acc, $key) => $acc + intval($item->{$key} ?? 0), 0);
    }

    private function getSizeText($item): string
    {
        $sizes = [
            ['key' => 'tam_pp', 'label' => 'PP'],
            ['key' => 'tam_p', 'label' => 'P'],
            ['key' => 'tam_m', 'label' => 'M'],
            ['key' => 'tam_g', 'label' => 'G'],
            ['key' => 'tam_u', 'label' => 'U'],
            ['key' => 'tam_rn', 'label' => 'RN'],
            ['key' => 'lisa', 'label' => 'LISA'],
            ['key' => 'ida_1', 'label' => '1'],
            ['key' => 'ida_2', 'label' => '2'],
            ['key' => 'ida_3', 'label' => '3'],
            ['key' => 'ida_4', 'label' => '4'],
            ['key' => 'ida_6', 'label' => '6'],
            ['key' => 'ida_8', 'label' => '8'],
            ['key' => 'ida_10', 'label' => '10'],
            ['key' => 'ida_12', 'label' => '12'],
        ];

        $badges = array_map(
            fn($size) => $size['label'] . ':' . intval($item->{$size['key']} ?? 0),
            array_filter($sizes, fn($size) => intval($item->{$size['key']} ?? 0) > 0)
        );

        return count($badges) > 0 ? implode(' | ', $badges) : '-';
    }

    private function safeText($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if (is_array($value)) {
            return implode(', ', array_map(
                fn($item) => is_scalar($item) || $item === null
                    ? (string)($item ?? '')
                    : json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $value
            ));
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded !== false ? $encoded : '-';
    }

    private function statusLabel(int $status): string
    {
        return match($status) {
            1 => 'ABERTO',
            2 => 'PENDENTE',
            3 => 'CANCELADO',
            4 => 'CONCLUÍDO',
            default => 'DESCONHECIDO'
        };
    }

    private function formatCurrency(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    private function formatDateLikeTemplate($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return $this->safeText($value);
        }

        return date('d/m/Y', $timestamp);
    }

    private function formatDateTimeLikeTemplate(\DateTime $date): string
    {
        return $date->format('d/m/Y H:i:s');
    }
}
