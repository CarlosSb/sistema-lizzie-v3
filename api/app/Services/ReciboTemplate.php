<?php

namespace App\Services;

class ReciboTemplate extends BaseTemplate
{
    public function render(PrintData $data, PrintOptions $options): string
    {
        $html = $this->getHeader($data);
        $html .= $this->getReceiptContent($data);
        $html .= $this->getSignatures($data);
        $html .= $this->getFooter($data);

        return $this->wrapInContainer($html);
    }

    private function getHeader(PrintData $data): string
    {
        return '
            <div style="text-align: center; border-bottom: 2px solid #374151; padding-bottom: 20px; margin-bottom: 30px;">
                <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">RECIBO DE ENTREGA</h1>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 5px 0 0 0;">Pedido #' . $data->pedido->id_pedido . ' - ' . $this->formatDate($data->pedido->data_pedido) . '</p>
            </div>
        ';
    }

    private function getReceiptContent(PrintData $data): string
    {
        return '
            <div style="margin-bottom: 30px;">
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
                    <p style="margin-bottom: 15px;"><strong>Cliente:</strong> ' . ($data->cliente->nome_fantasia ?? $data->cliente->razao_social ?? $data->pedido->razao_social) . '</p>
                    <p style="margin-bottom: 15px;"><strong>Valor Recebido:</strong> ' . $this->formatCurrency($data->pedido->total_pedido) . '</p>
                    <p style="margin-bottom: 15px;"><strong>Referente a:</strong> Serviços/produtos conforme pedido #' . $data->pedido->id_pedido . '</p>
                    <p style="margin-bottom: 15px;"><strong>Data da Entrega:</strong> ' . $this->formatDate($data->pedido->data_entrega ?? $data->pedido->data_pedido) . '</p>
                </div>
            </div>
        ';
    }

    private function getSignatures(PrintData $data): string
    {
        return '
            <div style="border-top: 1px solid #e5e7eb; padding-top: 30px; margin-top: 40px;">
                <h3 style="border-bottom: 1px solid #d1d5db; padding-bottom: 8px; margin-bottom: 30px; font-size: 1.25rem; font-weight: 700; color: #111827;">Assinaturas</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px;">
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #9ca3af; padding-bottom: 60px; margin-bottom: 8px;"></div>
                        <p style="font-size: 0.875rem; font-weight: 500;">Cliente - Recebi os produtos/serviços</p>
                        <p style="font-size: 0.75rem; color: #6b7280;">' . ($data->cliente->nome_fantasia ?? $data->cliente->razao_social ?? $data->pedido->razao_social) . '</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #9ca3af; padding-bottom: 60px; margin-bottom: 8px;"></div>
                        <p style="font-size: 0.875rem; font-weight: 500;">Sistema Lizzie</p>
                        <p style="font-size: 0.75rem; color: #6b7280;">Entregue por representante autorizado</p>
                    </div>
                </div>
            </div>
        ';
    }

    private function getFooter(PrintData $data): string
    {
        return '
            <div style="text-align: center; font-size: 0.75rem; color: #6b7280; margin-top: 60px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                <p style="font-weight: 500;">Documento gerado em ' . $this->formatDateTime($data->generatedAt) . '</p>
                <p style="margin-top: 4px;">Sistema Lizzie - Gestão Empresarial | Recibo oficial de entrega.</p>
            </div>
        ';
    }

    private function wrapInContainer(string $html): string
    {
        return '
            <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background: white; max-width: 600px; margin: 0 auto; padding: 40px;">
                ' . $html . '
            </div>
        ';
    }
}