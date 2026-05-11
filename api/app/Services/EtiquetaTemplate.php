<?php

namespace App\Services;

class EtiquetaTemplate extends BaseTemplate
{
    public function render(PrintData $data, PrintOptions $options): string
    {
        $html = $this->getEtiquetaContent($data, $options);
        return $this->wrapInEtiquetaContainer($html);
    }

    private function getEtiquetaContent(PrintData $data, PrintOptions $options): string
    {
        $qrCode = '';
        if ($options->includeQR) {
            $generator = new PdfGenerator();
            $qrData = 'PEDIDO-' . $data->pedido->id_pedido . '-' . $this->formatDate($data->pedido->data_pedido);
            $qrCode = '<img src="' . $generator->generateQRCode($qrData, 60) . '" style="width: 60px; height: 60px;" />';
        }

        return '
            <div style="text-align: center; padding: 20px;">
                <h1 style="font-size: 1.5rem; font-weight: bold; margin-bottom: 10px;">ETIQUETA DE PEDIDO</h1>
                <div style="border: 2px solid #000; padding: 15px; margin: 10px 0; background: #f9f9f9;">
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 10px;">#' . $data->pedido->id_pedido . '</div>
                    <div style="font-size: 1.2rem; margin-bottom: 8px;"><strong>Cliente:</strong> ' . ($data->cliente->nome_fantasia ?? $data->cliente->razao_social ?? $data->pedido->razao_social) . '</div>
                    <div style="font-size: 1rem; margin-bottom: 8px;"><strong>Data:</strong> ' . $this->formatDate($data->pedido->data_pedido) . '</div>
                    <div style="font-size: 1.2rem; margin-bottom: 10px;"><strong>Valor:</strong> ' . $this->formatCurrency($data->pedido->total_pedido) . '</div>
                    ' . ($qrCode ? '<div style="margin-top: 10px;">' . $qrCode . '</div>' : '') . '
                </div>
                <div style="font-size: 0.8rem; color: #666; margin-top: 10px;">
                    Sistema Lizzie - Pedido gerado em ' . $this->formatDateTime($data->generatedAt) . '
                </div>
            </div>
        ';
    }

    private function wrapInEtiquetaContainer(string $html): string
    {
        return '
            <div style="font-family: Arial, sans-serif; width: 100%; height: 100%; background: white; page-break-inside: avoid;">
                ' . $html . '
            </div>
        ';
    }
}