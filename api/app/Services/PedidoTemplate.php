<?php

namespace App\Services;

class PedidoTemplate extends BaseTemplate
{
    public function render(PrintData $data, PrintOptions $options): string
    {
        $html = $this->getHeader($data, $options);
        $html .= $this->getClientInfo($data, $options);
        $html .= $this->getItemsTable($data);
        $html .= $this->getPaymentInfo($data);
        $html .= $this->getAdditionalInfo($data, $options);
        $html .= $this->getSignatures($data);
        $html .= $this->getFooter($data);

        return $this->wrapInContainer($html);
    }

    private function getHeader(PrintData $data, PrintOptions $options): string
    {
        $qrCodeHtml = '';
        if ($options->includeQR && !empty($data->qrCodeUrl)) {
            $qrCodeHtml = '<img src="' . $data->qrCodeUrl . '" style="width: 80px; height: 80px; margin-left: 20px;" alt="QR Code" />';
        }

        return '
            <div style="border-bottom: 2px solid #374151; padding-bottom: 20px; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 80px; height: 80px; background-color: #f3f4f6; border: 1px solid #d1d5db; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                            <span style="font-size: 12px; color: #6b7280;">LOGO</span>
                        </div>
                        <div>
                            <h1 style="font-size: 2.5rem; font-weight: bold; color: #111827; margin: 0;">Sistema Lizzie</h1>
                            <p style="font-size: 0.875rem; color: #6b7280; margin: 5px 0 0 0;">Sistema de Gestão Empresarial</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <div style="text-align: right; margin-right: 20px;">
                            <h2 style="font-size: 1.5rem; font-weight: 600; color: #111827; margin: 0;">Pedido #' . $data->pedido->id_pedido . '</h2>
                            <p style="font-size: 0.875rem; color: #6b7280; margin: 5px 0 0 0;">Data: ' . $this->formatDate($data->pedido->data_pedido) . '</p>
                            <p style="font-size: 0.875rem; color: #6b7280; margin: 2px 0 0 0;">Status: ' . $this->getStatusText($data->pedido->status) . '</p>
                        </div>
                        ' . $qrCodeHtml . '
                    </div>
                </div>
            </div>
        ';
    }

    private function getClientInfo(PrintData $data, PrintOptions $options): string
    {
        if ($options->templateId === 'recibo' || $options->templateId === 'etiqueta') {
            return '';
        }

        return '
            <div style="margin-bottom: 30px;">
                <h3 style="border-bottom: 1px solid #d1d5db; padding-bottom: 8px; margin-bottom: 15px; font-size: 1.25rem; font-weight: 700; color: #111827;">Dados do Cliente</h3>
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <p><strong style="font-weight: 600; color: #374151;">Nome/Razão Social:</strong> ' . ($data->cliente->nome_fantasia ?? $data->cliente->razao_social ?? $data->pedido->razao_social) . '</p>
                            <p><strong style="font-weight: 600; color: #374151;">Responsável:</strong> ' . ($data->cliente->responsavel ?? '') . '</p>
                            <p><strong style="font-weight: 600; color: #374151;">CPF/CNPJ:</strong> ' . ($data->cliente->cpf_cnpj ?? '') . '</p>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <p><strong style="font-weight: 600; color: #374151;">Email:</strong> ' . ($data->cliente->email ?? '') . '</p>
                            <p><strong style="font-weight: 600; color: #374151;">Contato:</strong> ' . ($data->cliente->contato_1 ?? '') . '</p>
                            <p><strong style="font-weight: 600; color: #374151;">Endereço:</strong> ' . ($data->cliente->endereco ?? '') . ', ' . ($data->cliente->bairro ?? '') . '</p>
                            <p><strong style="font-weight: 600; color: #374151;">Cidade/Estado:</strong> ' . ($data->cliente->cidade ?? '') . ', ' . ($data->cliente->estado ?? '') . ' - ' . ($data->cliente->cep ?? '') . '</p>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    private function getItemsTable(PrintData $data): string
    {
        $html = '
            <div style="margin-bottom: 30px;">
                <h3 style="border-bottom: 1px solid #d1d5db; padding-bottom: 8px; margin-bottom: 15px; font-size: 1.25rem; font-weight: 700; color: #111827;">Itens do Pedido</h3>
                <div style="overflow: hidden; border: 1px solid #d1d5db; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f9fafb;">
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Produto</th>
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Quantidade</th>
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Tamanhos</th>
                                <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Valor Unitário</th>
                                <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
        ';

        foreach ($data->itens as $item) {
            $quantidade = $this->sumQuantidades($item);
            $tamanhos = $this->getSizeBadges($item);
            $valorUnitario = $quantidade > 0 ? $item->total_item / $quantidade : 0;

            $html .= '
                            <tr>
                                <td style="padding: 12px 16px;">' . htmlspecialchars($item->produto . ' - ' . $item->referencia) . '</td>
                                <td style="padding: 12px 16px; text-align: center;">' . $quantidade . '</td>
                                <td style="padding: 12px 16px; text-align: center;">' . implode(' | ', $tamanhos) . '</td>
                                <td style="padding: 12px 16px; text-align: right;">' . $this->formatCurrency($valorUnitario) . '</td>
                                <td style="padding: 12px 16px; text-align: right; font-weight: 500;">' . $this->formatCurrency($item->total_item) . '</td>
                            </tr>
            ';
        }

        $html .= '
                            <tr style="background-color: #f9fafb; font-weight: bold;">
                                <td colspan="4" style="padding: 12px 16px; text-align: right; font-weight: bold; font-size: 1.125rem;">Total do Pedido:</td>
                                <td style="padding: 12px 16px; text-align: right; font-weight: bold; font-size: 1.125rem;">' . $this->formatCurrency($data->pedido->total_pedido) . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        ';

        return $html;
    }

    private function getPaymentInfo(PrintData $data): string
    {
        return '
            <div style="margin-bottom: 30px;">
                <h3 style="border-bottom: 1px solid #d1d5db; padding-bottom: 8px; margin-bottom: 15px; font-size: 1.25rem; font-weight: 700; color: #111827;">Resumo do Pedido e Forma de Pagamento</h3>
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <p><strong style="font-weight: 600; color: #374151;">Status:</strong> ' . $this->getStatusText($data->pedido->status) . '</p>
                            <p><strong style="font-weight: 600; color: #374151;">Forma de Pagamento:</strong> ' . ($data->pedido->forma_pag ?? '') . '</p>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            ' . ($data->pedido->ped_desconto > 0 ? '<p><strong style="font-weight: 500; color: #6b7280;">Subtotal:</strong> ' . $this->formatCurrency($data->pedido->total_pedido + $data->pedido->ped_desconto) . '</p>' : '') . '
                            ' . ($data->pedido->ped_desconto > 0 ? '<p><strong style="font-weight: 500; color: #6b7280;">Desconto:</strong> ' . $this->formatCurrency($data->pedido->ped_desconto) . '</p>' : '') . '
                            <p><strong style="font-weight: 500; color: #6b7280; font-size: 1.125rem;">Total:</strong> ' . $this->formatCurrency($data->pedido->total_pedido) . '</p>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    private function getAdditionalInfo(PrintData $data, PrintOptions $options): string
    {
        if ($options->templateId === 'recibo') {
            return '';
        }

        $info = '';

        if (!empty($data->pedido->obs_pedido)) {
            $info .= '
                <div style="margin-bottom: 20px;">
                    <h4 style="font-weight: 600; margin-bottom: 8px;">Observações do Pedido:</h4>
                    <p style="font-size: 0.875rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 12px;">' . nl2br(htmlspecialchars($data->pedido->obs_pedido)) . '</p>
                </div>
            ';
        }

        if (!empty($data->pedido->obs_entrega)) {
            $info .= '
                <div style="margin-bottom: 20px;">
                    <h4 style="font-weight: 600; margin-bottom: 8px;">Observações de Entrega:</h4>
                    <p style="font-size: 0.875rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 12px;">' . nl2br(htmlspecialchars($data->pedido->obs_entrega)) . '</p>
                </div>
            ';
        }

        if (!empty($info)) {
            return '
                <div style="margin-bottom: 30px;">
                    <h3 style="border-bottom: 1px solid #d1d5db; padding-bottom: 8px; margin-bottom: 15px; font-size: 1.25rem; font-weight: 700; color: #111827;">Informações Adicionais</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        ' . $info . '
                    </div>
                </div>
            ';
        }

        return '';
    }

    private function getSignatures(PrintData $data): string
    {
        return '
            <div style="border-top: 1px solid #e5e7eb; padding-top: 30px; margin-top: 40px;">
                <h3 style="border-bottom: 1px solid #d1d5db; padding-bottom: 8px; margin-bottom: 30px; font-size: 1.25rem; font-weight: 700; color: #111827;">Assinaturas</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px;">
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #9ca3af; padding-bottom: 60px; margin-bottom: 8px;"></div>
                        <p style="font-size: 0.875rem; font-weight: 500;">Cliente</p>
                        <p style="font-size: 0.75rem; color: #6b7280;">' . ($data->cliente->nome_fantasia ?? $data->cliente->razao_social ?? $data->pedido->razao_social) . '</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #9ca3af; padding-bottom: 60px; margin-bottom: 8px;"></div>
                        <p style="font-size: 0.875rem; font-weight: 500;">Sistema Lizzie</p>
                        <p style="font-size: 0.75rem; color: #6b7280;">Representante Autorizado</p>
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
                <p style="margin-top: 4px;">Sistema Lizzie - Gestão Empresarial | Este documento é oficial e foi gerado automaticamente pelo sistema.</p>
            </div>
        ';
    }

    private function wrapInContainer(string $html): string
    {
        return '
            <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background: white; max-width: 800px; margin: 0 auto; padding: 40px;">
                ' . $html . '
            </div>
        ';
    }

    private function getStatusText(int $status): string
    {
        return match($status) {
            1 => 'ABERTO',
            2 => 'PENDENTE',
            3 => 'CANCELADO',
            4 => 'CONCLUÍDO',
            default => 'DESCONHECIDO'
        };
    }

    private function sumQuantidades($item): int
    {
        $sizes = ['tam_pp', 'tam_p', 'tam_m', 'tam_g', 'tam_u', 'tam_rn', 'ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12', 'lisa'];
        return array_reduce($sizes, fn($acc, $key) => $acc + (intval($item->{$key} ?? 0)), 0);
    }

    private function getSizeBadges($item): array
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

        return array_map(
            fn($size) => $size['label'] . ':' . intval($item->{$size['key']} ?? 0),
            array_filter($sizes, fn($size) => intval($item->{$size['key']} ?? 0) > 0)
        );
    }
}