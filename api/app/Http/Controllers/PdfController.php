<?php

namespace App\Http\Controllers;

use App\Services\PdfGenerator;
use App\Services\PrintData;
use App\Services\PrintOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PdfController extends Controller
{
    private PdfGenerator $pdfGenerator;

    public function __construct(PdfGenerator $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
    }

    public function gerarPedido(Request $request, $id)
    {
        try {
            $data = $this->preparePedidoData($id);
            $options = $this->createPrintOptions($request, 'pedido');
            $this->addQrCodeToData($data, $options);

            $pdfContent = $this->pdfGenerator->generate($data, $options);

            return $this->sendPdfResponse($pdfContent, 'pedido-' . $id . '.pdf');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF do pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    public function gerarEtiqueta(Request $request, $id)
    {
        try {
            $data = $this->preparePedidoData($id);
            $options = $this->createPrintOptions($request, 'etiqueta');
            // Etiquetas sempre incluem QR code por padrão
            $options->includeQR = true;
            $this->addQrCodeToData($data, $options);

            $pdfContent = $this->pdfGenerator->generate($data, $options);

            return $this->sendPdfResponse($pdfContent, 'etiqueta-' . $id . '.pdf');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar etiqueta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function gerarCarne(Request $request, $id)
    {
        try {
            $data = $this->preparePedidoData($id);
            $this->loadPagamentoData($data, $id);
            $options = $this->createPrintOptions($request, 'carne');

            $pdfContent = $this->pdfGenerator->generate($data, $options);

            return $this->sendPdfResponse($pdfContent, 'carne-' . $id . '.pdf');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar carnê: ' . $e->getMessage()
            ], 500);
        }
    }

    public function gerarRecibo(Request $request, $id)
    {
        try {
            $data = $this->preparePedidoData($id);
            $options = $this->createPrintOptions($request, 'recibo');

            $pdfContent = $this->pdfGenerator->generate($data, $options);

            return $this->sendPdfResponse($pdfContent, 'recibo-' . $id . '.pdf');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar recibo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function relatorioVendas(Request $request)
    {
        try {
            $data = $this->prepareVendasReportData($request);
            $options = $this->createPrintOptions($request, 'relatorio_vendas');

            $pdfContent = $this->pdfGenerator->generate($data, $options);

            return $this->sendPdfResponse($pdfContent, 'relatorio-vendas.pdf');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar relatório de vendas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function relatorioVendedores(Request $request)
    {
        try {
            $data = $this->prepareVendedoresReportData($request);
            $options = $this->createPrintOptions($request, 'relatorio_vendedores');

            $pdfContent = $this->pdfGenerator->generate($data, $options);

            return $this->sendPdfResponse($pdfContent, 'relatorio-vendedores.pdf');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar relatório de vendedores: ' . $e->getMessage()
            ], 500);
        }
    }

    public function relatorioProdutos(Request $request)
    {
        try {
            $data = $this->prepareProdutosReportData($request);
            $options = $this->createPrintOptions($request, 'relatorio_produtos');

            $pdfContent = $this->pdfGenerator->generate($data, $options);

            return $this->sendPdfResponse($pdfContent, 'relatorio-produtos.pdf');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar relatório de produtos: ' . $e->getMessage()
            ], 500);
        }
    }

    private function preparePedidoData($id): PrintData
    {
        $pedido = DB::table('pedidos')
            ->select('pedidos.*', 'clientes.*', 'vendedores.nome_vendedor')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->where('pedidos.id_pedido', $id)
            ->first();

        if (!$pedido) {
            throw new \Exception('Pedido não encontrado');
        }

        $itens = DB::table('itens_pedidos')
            ->select('itens_pedidos.*', 'produtos.produto', 'produtos.referencia')
            ->leftJoin('produtos', 'itens_pedidos.id_produto', '=', 'produtos.id_produto')
            ->where('itens_pedidos.id_pedido', $id)
            ->get();

        $data = new PrintData();
        $data->pedido = $pedido;
        $data->cliente = $pedido; // Cliente data is included in the joined query
        $data->vendedor = (object) ['nome_vendedor' => $pedido->nome_vendedor];
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

    private function loadPagamentoData(PrintData $data, $id): void
    {
        // Load payment/installment data if exists
        // This would depend on your database schema for installments
        $data->pagamentos = []; // Placeholder
    }

    private function prepareVendasReportData(Request $request): PrintData
    {
        $query = DB::table('pedidos')
            ->select('pedidos.*', 'clientes.razao_social', 'vendedores.nome_vendedor')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor');

        // Apply filters similar to DashboardController
        if ($request->has('data_inicio') && $request->data_inicio) {
            $query->whereDate('pedidos.data_pedido', '>=', $request->data_inicio);
        }

        if ($request->has('data_fim') && $request->data_fim) {
            $query->whereDate('pedidos.data_pedido', '<=', $request->data_fim);
        }

        $pedidos = $query->orderBy('pedidos.data_pedido', 'desc')->get();

        $data = new PrintData();
        $data->itens = $pedidos; // Using itens to store the sales data

        return $data;
    }

    private function prepareVendedoresReportData(Request $request): PrintData
    {
        // Similar to DashboardController relatorioVendedores logic
        $vendedores = DB::table('vendedores')
            ->select(
                'vendedores.*',
                DB::raw('COUNT(pedidos.id_pedido) as total_pedidos'),
                DB::raw('SUM(pedidos.total_pedido) as total_vendas'),
                DB::raw('AVG(pedidos.total_pedido) as media_venda')
            )
            ->leftJoin('pedidos', function($join) use ($request) {
                $join->on('vendedores.id_vendedor', '=', 'pedidos.id_vendedor')
                     ->where('pedidos.status', '=', 4); // Only completed orders

                if ($request->has('data_inicio')) {
                    $join->where('pedidos.data_pedido', '>=', $request->data_inicio);
                }
                if ($request->has('data_fim')) {
                    $join->where('pedidos.data_pedido', '<=', $request->data_fim);
                }
            })
            ->groupBy('vendedores.id_vendedor')
            ->orderBy('total_vendas', 'desc')
            ->get();

        $data = new PrintData();
        $data->itens = $vendedores;

        return $data;
    }

    private function prepareProdutosReportData(Request $request): PrintData
    {
        // Similar to DashboardController relatorioProdutos logic
        $produtos = DB::table('produtos')
            ->select(
                'produtos.*',
                DB::raw('SUM(itens_pedidos.quantidade) as quantidade_vendida'),
                DB::raw('SUM(itens_pedidos.total_item) as receita_total')
            )
            ->leftJoin('itens_pedidos', 'produtos.id_produto', '=', 'itens_pedidos.id_produto')
            ->leftJoin('pedidos', function($join) use ($request) {
                $join->on('itens_pedidos.id_pedido', '=', 'pedidos.id_pedido')
                     ->where('pedidos.status', '=', 4); // Only completed orders

                if ($request->has('data_inicio')) {
                    $join->where('pedidos.data_pedido', '>=', $request->data_inicio);
                }
                if ($request->has('data_fim')) {
                    $join->where('pedidos.data_pedido', '<=', $request->data_fim);
                }
            })
            ->groupBy('produtos.id_produto')
            ->orderBy('quantidade_vendida', 'desc')
            ->get();

        $data = new PrintData();
        $data->itens = $produtos;

        return $data;
    }

    private function createPrintOptions(Request $request, string $templateId): PrintOptions
    {
        $options = new PrintOptions($templateId);

        if ($request->has('format')) {
            $options->format = $request->format;
        }

        if ($request->has('paper_size')) {
            $options->paperSize = $request->paper_size;
        }

        if ($request->has('orientation')) {
            $options->orientation = $request->orientation;
        }

        if ($request->has('include_qr')) {
            $options->includeQR = $request->boolean('include_qr');
        }

        if ($request->has('copies')) {
            $options->copies = (int) $request->copies;
        }

        return $options;
    }

    private function sendPdfResponse(string $pdfContent, string $filename)
    {
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate');
    }
}
