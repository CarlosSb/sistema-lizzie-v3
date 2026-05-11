<?php

namespace App\Services;

class PedidoTemplate extends BaseTemplate
{
    public function render(PrintData $data, PrintOptions $options): string
    {
        $html = $this->getStyles();
        $html .= '<div class="document">';
        $html .= $this->getHeader($data);

        if ($this->isComplete($options)) {
            $html .= $this->getClientInfo($data);
        }

        $html .= $this->getItemsTable($data);
        $html .= $this->getPaymentInfo($data);

        if ($this->isComplete($options)) {
            $html .= $this->getAdditionalInfo($data);
        }

        $html .= $this->getSignatures($data);
        $html .= $this->getFooter($data);
        $html .= '</div>';

        return $html;
    }

    private function getStyles(): string
    {
        return '
            <style>
                body {
                    background: #ffffff;
                    color: #0f172a;
                    font-family: helvetica, arial, sans-serif;
                    font-size: 10px;
                    line-height: 1.35;
                }
                .document {
                    background: #ffffff;
                    padding: 0;
                }
                .header {
                    border-bottom: 2px solid #1e293b;
                    padding-bottom: 14px;
                    margin-bottom: 18px;
                }
                .header-table,
                .info-table,
                .items-table,
                .signature-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .logo-box {
                    width: 54px;
                    height: 54px;
                    border: 1px solid #cbd5e1;
                    background-color: #f1f5f9;
                    color: #64748b;
                    text-align: center;
                    line-height: 54px;
                    font-size: 8px;
                }
                .brand-title {
                    font-size: 22px;
                    font-weight: bold;
                    color: #0f172a;
                    margin: 0;
                }
                .muted {
                    color: #64748b;
                }
                .order-title {
                    font-size: 16px;
                    font-weight: bold;
                    color: #0f172a;
                    text-align: right;
                    margin: 0;
                }
                .section {
                    margin-bottom: 18px;
                }
                .section-title {
                    font-size: 12px;
                    font-weight: bold;
                    color: #0f172a;
                    border-bottom: 1px solid #cbd5e1;
                    padding-bottom: 5px;
                    margin-bottom: 8px;
                }
                .info-box {
                    border: 1px solid #e2e8f0;
                    background-color: #f8fafc;
                    padding: 10px;
                }
                .info-table td {
                    width: 50%;
                    padding: 3px 6px;
                    vertical-align: top;
                }
                .label {
                    font-weight: bold;
                    color: #334155;
                }
                .items-table th {
                    background-color: #f1f5f9;
                    border-bottom: 1px solid #cbd5e1;
                    color: #0f172a;
                    font-weight: bold;
                    padding: 7px 6px;
                }
                .items-table thead {
                    display: table-header-group;
                }
                .items-table td {
                    border-top: 1px solid #e2e8f0;
                    padding: 7px 6px;
                    vertical-align: top;
                }
                .items-table tr {
                    page-break-inside: avoid;
                }
                .items-table .total-row td {
                    background-color: #f1f5f9;
                    font-weight: bold;
                    font-size: 11px;
                }
                .text-center {
                    text-align: center;
                }
                .text-right {
                    text-align: right;
                }
                .product-reference {
                    color: #64748b;
                    font-size: 8px;
                }
                .note-box {
                    border: 1px solid #e2e8f0;
                    background-color: #f8fafc;
                    padding: 9px;
                    margin-bottom: 8px;
                    page-break-inside: avoid;
                }
                .signature-block {
                    padding-top: 38px;
                    text-align: center;
                }
                .signature-line {
                    border-bottom: 1px solid #64748b;
                    height: 34px;
                    margin-bottom: 6px;
                }
                .footer {
                    text-align: center;
                    color: #64748b;
                    border-top: 2px solid #e2e8f0;
                    padding-top: 10px;
                    margin-top: 28px;
                    font-size: 8px;
                }
            </style>
        ';
    }

    private function getHeader(PrintData $data): string
    {
        return '
            <div class="header">
                <table class="header-table">
                    <tr>
                        <td style="width: 64px;">
                            <div class="logo-box">LOGO</div>
                        </td>
                        <td>
                            <div class="brand-title">Sistema Lizzie</div>
                            <div class="muted">Sistema de Gestão Empresarial</div>
                        </td>
                        <td style="width: 210px; text-align: right;">
                            <div class="order-title">Pedido #' . $this->h($data->pedido->id_pedido ?? '') . '</div>
                            <div class="muted">Data: ' . $this->h($this->formatDate((string)($data->pedido->data_pedido ?? ''))) . '</div>
                            <div class="muted">Status: ' . $this->h($this->getStatusText((int)($data->pedido->status ?? 0))) . '</div>
                        </td>
                    </tr>
                </table>
            </div>
        ';
    }

    private function getClientInfo(PrintData $data): string
    {
        return '
            <div class="section">
                <div class="section-title">Dados do Cliente</div>
                <div class="info-box">
                    <table class="info-table">
                        <tr>
                            <td><span class="label">Nome/Razão Social:</span> ' . $this->h($this->clientName($data)) . '</td>
                            <td><span class="label">Responsável:</span> ' . $this->h($this->safeText($data->cliente->responsavel ?? '-')) . '</td>
                        </tr>
                        <tr>
                            <td><span class="label">CPF/CNPJ:</span> ' . $this->h($this->safeText($data->cliente->cpf_cnpj ?? '-')) . '</td>
                            <td><span class="label">Email:</span> ' . $this->h($this->safeText($data->cliente->email ?? '-')) . '</td>
                        </tr>
                        <tr>
                            <td><span class="label">Contato:</span> ' . $this->h($this->safeText($data->cliente->contato_1 ?? '-')) . '</td>
                            <td><span class="label">Cidade/Estado:</span> ' . $this->h($this->safeText($data->cliente->cidade ?? '-')) . ' / ' . $this->h($this->safeText($data->cliente->estado ?? '-')) . '</td>
                        </tr>
                        <tr>
                            <td colspan="2"><span class="label">Endereço:</span> ' . $this->h($this->safeText($data->cliente->endereco ?? '-')) . ', ' . $this->h($this->safeText($data->cliente->bairro ?? '-')) . ' - ' . $this->h($this->safeText($data->cliente->cep ?? '-')) . '</td>
                        </tr>
                    </table>
                </div>
            </div>
        ';
    }

    private function getItemsTable(PrintData $data): string
    {
        $html = '
            <div class="section">
                <div class="section-title">Itens do Pedido</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 34%; text-align: left;">Produto</th>
                            <th style="width: 10%;" class="text-center">Qtd.</th>
                            <th style="width: 24%;" class="text-center">Tamanhos</th>
                            <th style="width: 16%;" class="text-right">Unitário</th>
                            <th style="width: 16%;" class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
        ';

        foreach ($data->itens as $item) {
            $quantidade = $this->sumQuantidades($item);
            $valorUnitario = $quantidade > 0 ? ((float)($item->total_item ?? 0) / $quantidade) : 0;
            $tamanhos = $this->getSizeText($item);

            $html .= '
                        <tr>
                            <td>
                                <strong>' . $this->h($this->safeText($item->produto ?? '-')) . '</strong><br />
                                <span class="product-reference">' . $this->h($this->safeText($item->referencia ?? '-')) . '</span>
                            </td>
                            <td class="text-center">' . $quantidade . '</td>
                            <td class="text-center">' . $this->h($tamanhos) . '</td>
                            <td class="text-right">' . $this->h($this->formatCurrency($valorUnitario)) . '</td>
                            <td class="text-right"><strong>' . $this->h($this->formatCurrency((float)($item->total_item ?? 0))) . '</strong></td>
                        </tr>
            ';
        }

        $html .= '
                        <tr class="total-row">
                            <td colspan="4" class="text-right">Total do Pedido:</td>
                            <td class="text-right">' . $this->h($this->formatCurrency((float)($data->pedido->total_pedido ?? 0))) . '</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        ';

        return $html;
    }

    private function getPaymentInfo(PrintData $data): string
    {
        $discount = (float)($data->pedido->ped_desconto ?? 0);
        $discountHtml = $discount > 0
            ? '<tr><td><span class="label">Desconto:</span> ' . $this->h($this->formatCurrency($discount)) . '</td><td></td></tr>'
            : '';

        return '
            <div class="section">
                <div class="section-title">Resumo e Pagamento</div>
                <div class="info-box">
                    <table class="info-table">
                        <tr>
                            <td><span class="label">Status:</span> ' . $this->h($this->getStatusText((int)($data->pedido->status ?? 0))) . '</td>
                            <td><span class="label">Forma de Pagamento:</span> ' . $this->h($this->safeText($data->pedido->forma_pag ?? '-')) . '</td>
                        </tr>
                        ' . $discountHtml . '
                        <tr>
                            <td><span class="label">Total:</span> <strong>' . $this->h($this->formatCurrency((float)($data->pedido->total_pedido ?? 0))) . '</strong></td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            </div>
        ';
    }

    private function getAdditionalInfo(PrintData $data): string
    {
        $obsPedido = $this->safeText($data->pedido->obs_pedido ?? '');
        $obsEntrega = $this->safeText($data->pedido->obs_entrega ?? '');

        if ($obsPedido === '' && $obsEntrega === '') {
            return '';
        }

        $html = '
            <div class="section">
                <div class="section-title">Informações Adicionais</div>
        ';

        if ($obsPedido !== '') {
            $html .= '
                <div class="note-box">
                    <strong>Observações do Pedido</strong><br />
                    ' . nl2br($this->h($obsPedido)) . '
                </div>
            ';
        }

        if ($obsEntrega !== '') {
            $html .= '
                <div class="note-box">
                    <strong>Observações de Entrega</strong><br />
                    ' . nl2br($this->h($obsEntrega)) . '
                </div>
            ';
        }

        return $html . '</div>';
    }

    private function getSignatures(PrintData $data): string
    {
        return '
            <div class="section">
                <div class="section-title">Assinaturas</div>
                <table class="signature-table">
                    <tr>
                        <td style="width: 48%;" class="signature-block">
                            <div class="signature-line"></div>
                            <strong>Cliente</strong><br />
                            <span class="muted">' . $this->h($this->clientName($data)) . '</span>
                        </td>
                        <td style="width: 4%;"></td>
                        <td style="width: 48%;" class="signature-block">
                            <div class="signature-line"></div>
                            <strong>Sistema Lizzie</strong><br />
                            <span class="muted">Representante Autorizado</span>
                        </td>
                    </tr>
                </table>
            </div>
        ';
    }

    private function getFooter(PrintData $data): string
    {
        return '
            <div class="footer">
                <strong>Documento gerado em ' . $this->h($this->formatDateTime($data->generatedAt)) . '</strong><br />
                Sistema Lizzie - Gestão Empresarial | Este documento é oficial e foi gerado automaticamente pelo sistema.
            </div>
        ';
    }

    private function isComplete(PrintOptions $options): bool
    {
        return ($options->mode ?? 'complete') !== 'summary';
    }

    private function clientName(PrintData $data): string
    {
        return $this->safeText($data->cliente->nome_fantasia ?? $data->cliente->razao_social ?? $data->pedido->razao_social ?? '-');
    }

    private function h($value): string
    {
        return htmlspecialchars($this->safeText($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function safeText($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if (is_array($value)) {
            $flat = array_map(
                fn($item) => is_scalar($item) || $item === null ? (string)($item ?? '') : json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $value
            );

            return implode(', ', $flat);
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }

            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded !== false ? $encoded : '[objeto]';
        }

        return '';
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

    private function getSizeText($item): string
    {
        $badges = $this->getSizeBadges($item);
        return count($badges) > 0 ? implode(' | ', $badges) : '-';
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
