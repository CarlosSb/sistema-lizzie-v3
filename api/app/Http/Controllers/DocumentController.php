<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    private DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function generatePedido(Request $request, int $id)
    {
        try {
            $metadata = $this->documentService->generatePedidoDocument($id, [
                'template' => $this->normalizeStringOption($request->input('template', 'complete'), 'template'),
                'format' => $this->normalizeStringOption($request->input('format', 'pdf'), 'format'),
                'paper_size' => $this->normalizeStringOption($request->input('paper_size', 'a4'), 'paper_size'),
                'orientation' => $this->normalizeStringOption($request->input('orientation', 'portrait'), 'orientation'),
                'include_qr' => $request->boolean('include_qr', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento gerado com sucesso',
                'data' => $metadata,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar documento: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function previewPedido(Request $request, int $id)
    {
        try {
            $preview = $this->documentService->buildPedidoPreviewModel($id, [
                'template' => $this->normalizeStringOption($request->query('template', 'complete'), 'template'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prévia carregada com sucesso',
                'data' => $preview,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar prévia: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function normalizeStringOption($value, string $field): string
    {
        if (is_array($value)) {
            throw new \InvalidArgumentException("Campo '{$field}' inválido: esperado texto, recebido array.");
        }

        if (is_object($value)) {
            throw new \InvalidArgumentException("Campo '{$field}' inválido: esperado texto, recebido objeto.");
        }

        return trim((string)$value);
    }

    public function metadata(string $documentId)
    {
        $metadata = $this->documentService->getMetadata($documentId);

        if (!$metadata) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $metadata,
        ]);
    }

    public function content(string $documentId)
    {
        $document = $this->documentService->getDocumentContent($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não encontrado',
            ], 404);
        }

        $metadata = $document['metadata'];
        $content = $document['content'];

        return response($content)
            ->header('Content-Type', $metadata['content_type'] ?? 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . ($metadata['filename'] ?? 'documento.pdf') . '"')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate');
    }

    public function cleanup()
    {
        try {
            $result = $this->documentService->cleanupNow();

            return response()->json([
                'success' => true,
                'message' => 'Limpeza executada com sucesso',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar limpeza de documentos: ' . $e->getMessage(),
            ], 500);
        }
    }
}
