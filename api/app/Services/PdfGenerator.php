<?php

namespace App\Services;

use TCPDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;
use Exception;

class PdfGenerator
{
    private string $engine = 'tcpdf'; // 'tcpdf' or 'dompdf'

    public function generate(PrintData $data, PrintOptions $options): string
    {
        switch ($options->templateId) {
            case 'pedido':
                return $this->generatePedido($data, $options);
            case 'etiqueta':
                return $this->generateEtiqueta($data, $options);
            case 'carne':
                return $this->generateCarne($data, $options);
            case 'recibo':
                return $this->generateRecibo($data, $options);
            case 'relatorio_vendas':
                return $this->generateRelatorioVendas($data, $options);
            case 'relatorio_vendedores':
                return $this->generateRelatorioVendedores($data, $options);
            case 'relatorio_produtos':
                return $this->generateRelatorioProdutos($data, $options);
            default:
                throw new Exception("Template não encontrado: {$options->templateId}");
        }
    }

    private function generatePedido(PrintData $data, PrintOptions $options): string
    {
        $template = new \App\Services\PedidoTemplate();
        $html = $template->render($data, $options);

        return $this->renderPdf($html, $options);
    }

    private function generateEtiqueta(PrintData $data, PrintOptions $options): string
    {
        $template = new \App\Services\EtiquetaTemplate();
        $html = $template->render($data, $options);

        return $this->renderPdf($html, $options);
    }

    private function generateCarne(PrintData $data, PrintOptions $options): string
    {
        $template = new \App\Services\CarneTemplate();
        $html = $template->render($data, $options);

        return $this->renderPdf($html, $options);
    }

    private function generateRecibo(PrintData $data, PrintOptions $options): string
    {
        $template = new \App\Services\ReciboTemplate();
        $html = $template->render($data, $options);

        return $this->renderPdf($html, $options);
    }

    private function generateRelatorioVendas(PrintData $data, PrintOptions $options): string
    {
        $template = new \App\Services\RelatorioTemplate();
        $html = $template->renderVendas($data, $options);

        return $this->renderPdf($html, $options);
    }

    private function generateRelatorioVendedores(PrintData $data, PrintOptions $options): string
    {
        $template = new \App\Services\RelatorioTemplate();
        $html = $template->renderVendedores($data, $options);

        return $this->renderPdf($html, $options);
    }

    private function generateRelatorioProdutos(PrintData $data, PrintOptions $options): string
    {
        $template = new \App\Services\RelatorioTemplate();
        $html = $template->renderProdutos($data, $options);

        return $this->renderPdf($html, $options);
    }

    private function renderPdf(string $html, PrintOptions $options): string
    {
        if ($this->engine === 'tcpdf') {
            return $this->renderWithTcpdf($html, $options);
        } else {
            return $this->renderWithDompdf($html, $options);
        }
    }

    private function renderWithTcpdf(string $html, PrintOptions $options): string
    {
        $pdf = new TCPDF(
            strtoupper($options->orientation[0]),
            'mm',
            strtoupper($options->paperSize),
            true,
            'UTF-8',
            false
        );

        $pdf->SetCreator('Sistema Lizzie');
        $pdf->SetAuthor('Sistema Lizzie');
        $pdf->SetTitle('Documento');
        $pdf->SetSubject('Documento gerado automaticamente');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S');
    }

    private function renderWithDompdf(string $html, PrintOptions $options): string
    {
        $dompdfOptions = new Options();
        $dompdfOptions->set('defaultFont', 'Arial');
        $dompdfOptions->set('isRemoteEnabled', true);
        $dompdfOptions->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($dompdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($options->paperSize, $options->orientation);
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateQRCode(string $data, int $size = 100): string
    {
        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 5,
            'imageBase64' => true,
        ]);

        $qrcode = new QRCode($options);
        return $qrcode->render($data);
    }

    public function setEngine(string $engine): void
    {
        if (!in_array($engine, ['tcpdf', 'dompdf'])) {
            throw new Exception("Engine não suportada: {$engine}");
        }
        $this->engine = $engine;
    }
}