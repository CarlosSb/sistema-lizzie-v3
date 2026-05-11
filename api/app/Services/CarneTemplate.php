<?php

namespace App\Services;

class CarneTemplate extends BaseTemplate
{
    public function render(PrintData $data, PrintOptions $options): string
    {
        $html = '';

        // Assuming pagamentos contains installment data
        if (!empty($data->pagamentos)) {
            foreach ($data->pagamentos as $index => $pagamento) {
                $html .= $this->getInstallmentCard($data, $pagamento, $index + 1, $options);
            }
        } else {
            // Generate installments based on order items (simplified)
            $totalParcelas = 3; // This should come from order data
            $valorParcela = $data->pedido->total_pedido / $totalParcelas;

            for ($i = 1; $i <= $totalParcelas; $i++) {
                $pagamento = (object) [
                    'numero' => $i,
                    'valor' => $valorParcela,
                    'vencimento' => date('Y-m-d', strtotime($data->pedido->data_pedido . " +$i months")),
                    'total_parcelas' => $totalParcelas
                ];
                $html .= $this->getInstallmentCard($data, $pagamento, $i, $options);
            }
        }

        return $this->wrapInCarneContainer($html);
    }

    private function getInstallmentCard(PrintData $data, $pagamento, int $numero, PrintOptions $options): string
    {
        $qrCode = '';
        if ($options->includeQR) {
            $generator = new PdfGenerator();
            $qrData = 'PARCELA-' . $data->pedido->id_pedido . '-' . $numero;
            $qrCode = '<img src="' . $generator->generateQRCode($qrData, 40) . '" style="width: 40px; height: 40px;" />';
        }

        return '
            <div style="border: 1px solid #000; margin-bottom: 10px; padding: 10px; page-break-inside: avoid;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem;">CARNE DE PAGAMENTO</h3>
                        <p style="margin: 2px 0; font-size: 0.9rem;">Pedido #' . $data->pedido->id_pedido . '</p>
                    </div>
                    ' . ($qrCode ? '<div>' . $qrCode . '</div>' : '') . '
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <div>
                        <p style="margin: 2px 0; font-size: 0.9rem;"><strong>Cliente:</strong> ' . ($data->cliente->nome_fantasia ?? $data->cliente->razao_social ?? $data->pedido->razao_social) . '</p>
                        <p style="margin: 2px 0; font-size: 0.9rem;"><strong>Parcela:</strong> ' . $numero . '/' . ($pagamento->total_parcelas ?? '3') . '</p>
                    </div>
                    <div style="text-align: right;">
                        <p style="margin: 2px 0; font-size: 0.9rem;"><strong>Vencimento:</strong> ' . $this->formatDate($pagamento->vencimento) . '</p>
                        <p style="margin: 2px 0; font-size: 1.2rem; font-weight: bold;">' . $this->formatCurrency($pagamento->valor) . '</p>
                    </div>
                </div>

                <div style="border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 0.8rem;">
                            <p style="margin: 2px 0;">Sistema Lizzie - Gestão Empresarial</p>
                            <p style="margin: 2px 0;">Gerado em: ' . $this->formatDateTime($data->generatedAt) . '</p>
                        </div>
                        <div style="text-align: center; font-size: 0.8rem;">
                            <div style="border-bottom: 1px solid #000; width: 150px; margin-bottom: 5px;"></div>
                            <p style="margin: 0;">Assinatura do Cliente</p>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    private function wrapInCarneContainer(string $html): string
    {
        return '
            <div style="font-family: Arial, sans-serif; width: 100%; background: white;">
                ' . $html . '
            </div>
        ';
    }
}