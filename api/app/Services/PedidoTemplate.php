<?php

namespace App\Services;

class PedidoTemplate extends BaseTemplate
{
    public function render(PrintData $data, PrintOptions $options): string
    {
        $html = $this->getStyles();
        $html .= '<main class="page">';
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
        $html .= '</main>';

        return $html;
    }

    private function getStyles(): string
    {
        return '
            <style>
                * {
                    box-sizing: border-box;
                    font-family: Arial, Helvetica, sans-serif;
                }

                @page {
                    size: A4;
                    margin: 12mm;
                }

                body {
                    margin: 0;
                    background: #eef2f7;
                    color: #0f172a;
                    font-size: 12px;
                }

                .page {
                    width: 100%;
                    background: #ffffff;
                    padding: 24px;
                    border: 1px solid #d4dce8;
                    border-radius: 6px;
                }

                .header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    border-bottom: 2px solid #1e3a5f;
                    padding-bottom: 16px;
                }

                .brand {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .logo {
                    width: 48px;
                    height: 48px;
                    border: 1px solid #cbd5e1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #94a3b8;
                    font-size: 10px;
                }

                .brand h1 {
                    margin: 0;
                    font-size: 20px;
                    color: #0f172a;
                }

                .brand p,
                .order-info p {
                    margin: 3px 0;
                    font-size: 10px;
                    color: #64748b;
                }

                .order-info {
                    text-align: right;
                }

                .order-info h2 {
                    margin: 0 0 6px;
                    font-size: 15px;
                }

                .section {
                    margin-top: 18px;
                }

                .section-title {
                    font-size: 12px;
                    font-weight: bold;
                    border-bottom: 1px solid #94a3b8;
                    padding-bottom: 5px;
                    margin-bottom: 8px;
                }

                .box {
                    border: 1px solid #cbd5e1;
                    border-radius: 6px;
                    background: #f8fafc;
                    padding: 10px;
                }

                .grid-2 {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 7px 28px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 10.5px;
                }

                th {
                    background: #f1f5f9;
                    padding: 7px;
                    text-align: left;
                    border-bottom: 1px solid #cbd5e1;
                }

                td {
                    padding: 7px;
                    border-bottom: 1px solid #cbd5e1;
                }

                th:nth-child(2),
                td:nth-child(2),
                th:nth-child(4),
                td:nth-child(4),
                th:nth-child(5),
                td:nth-child(5) {
                    text-align: right;
                }

                .total-row {
                    margin-top: 10px;
                    text-align: right;
                    font-weight: bold;
                }

                .note-box {
                    min-height: 50px;
                    margin-bottom: 10px;
                }

                .signatures {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 40px;
                    margin-top: 55px;
                }

                .signature {
                    border-top: 1px solid #0f172a;
                    text-align: center;
                    padding-top: 7px;
                    font-size: 10px;
                }

                .signature span {
                    display: block;
                    color: #64748b;
                    font-size: 9px;
                    margin-top: 2px;
                }

                .footer {
                    border-top: 1px solid #cbd5e1;
                    margin-top: 28px;
                    padding-top: 8px;
                    text-align: center;
                    color: #94a3b8;
                    font-size: 9px;
                }
            </style>
        ';
    }

    private function getHeader(PrintData $data): string
    {
        return '
            <header class="header">
                <div class="brand">
                    <div class="logo">LOGO</div>
                    <div>
                        <h1>Sistema Lizzie</h1>
                        <p>Sistema de Gestão Empresarial</p>
                    </div>
                </div>

                <div class="order-info">
                    <h2>Pedido #' . $this->h($data->pedido->id_pedido ?? '') . '</h2>
                    <p>Data: ' . $this->h($this->safeDate($data->pedido->data_pedido ?? null)) . '</p>
                    <p>Status: ' . $this->h($this->getStatusText((int)($data->pedido->status ?? 0))) . '</p>
                </div>
            </header>
        ';
    }

    private function getClientInfo(PrintData $data): string
    {
        return '
            <section class="section">
                <div class="section-title">Dados do Cliente</div>
                <div class="box grid-2">
                    <div><strong>Nome/Razão Social:</strong> ' . $this->h($this->clientName($data)) . '</div>
                    <div><strong>Responsável:</strong> ' . $this->h($this->safeText($data->cliente->responsavel ?? '-')) . '</div>
                    <div><strong>CPF/CNPJ:</strong> ' . $this->h($this->safeText($data->cliente->cpf_cnpj ?? '-')) . '</div>
                    <div><strong>Email:</strong> ' . $this->h($this->safeText($data->cliente->email ?? '-')) . '</div>
                    <div><strong>Contato:</strong> ' . $this->h($this->safeText($data->cliente->contato_1 ?? '-')) . '</div>
                    <div><strong>Cidade/Estado:</strong> ' . $this->h($this->safeText($data->cliente->cidade ?? '-')) . ' / ' . $this->h($this->safeText($data->cliente->estado ?? '-')) . '</div>
                    <div><strong>Endereço:</strong> ' . $this->h($this->safeText($data->cliente->endereco ?? '-')) . ', ' . $this->h($this->safeText($data->cliente->bairro ?? '-')) . ' - ' . $this->h($this->safeText($data->cliente->cep ?? '-')) . '</div>
                </div>
            </section>
        ';
    }

    private function getItemsTable(PrintData $data): string
    {
        $html = '
            <section class="section">
                <div class="section-title">Itens do Pedido</div>

                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd.</th>
                            <th>Tamanhos</th>
                            <th>Unitário</th>
                            <th>Total</th>
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
                            <td>' . $this->h($this->safeText($item->produto ?? '-')) . '<br><small>' . $this->h($this->safeText($item->referencia ?? '-')) . '</small></td>
                            <td>' . $quantidade . '</td>
                            <td>' . $this->h($tamanhos) . '</td>
                            <td>' . $this->h($this->formatCurrency($valorUnitario)) . '</td>
                            <td>' . $this->h($this->formatCurrency((float)($item->total_item ?? 0))) . '</td>
                        </tr>
            ';
        }

        $html .= '
                    </tbody>
                </table>

                <div class="total-row">
                    Total do Pedido: ' . $this->h($this->formatCurrency((float)($data->pedido->total_pedido ?? 0))) . '
                </div>
            </section>
        ';

        return $html;
    }

    private function getPaymentInfo(PrintData $data): string
    {
        return '
            <section class="section">
                <div class="section-title">Resumo e Pagamento</div>
                <div class="box grid-2">
                    <div>
                        <strong>Status:</strong> ' . $this->h($this->getStatusText((int)($data->pedido->status ?? 0))) . '<br>
                        <strong>Total:</strong> ' . $this->h($this->formatCurrency((float)($data->pedido->total_pedido ?? 0))) . '
                    </div>
                    <div>
                        <strong>Forma de Pagamento:</strong> ' . $this->h($this->safeText($data->pedido->forma_pag ?? '-')) . '
                    </div>
                </div>
            </section>
        ';
    }

    private function getAdditionalInfo(PrintData $data): string
    {
        $obsPedido = $this->safeOptionalText($data->pedido->obs_pedido ?? '');
        $obsEntrega = $this->safeOptionalText($data->pedido->obs_entrega ?? '');

        if ($obsPedido === '' && $obsEntrega === '') {
            return '';
        }

        $html = '
            <section class="section">
                <div class="section-title">Informações Adicionais</div>
        ';

        if ($obsPedido !== '') {
            $html .= '
                <div class="box note-box">
                    <strong>Observações do Pedido</strong><br>
                    ' . nl2br($this->h($obsPedido)) . '
                </div>
            ';
        }

        if ($obsEntrega !== '') {
            $html .= '
                <div class="box note-box">
                    <strong>Observações de Entrega</strong><br>
                    ' . nl2br($this->h($obsEntrega)) . '
                </div>
            ';
        }

        $html .= '</section>';

        return $html;
    }

    private function getSignatures(PrintData $data): string
    {
        return '
            <section class="section">
                <div class="section-title">Assinaturas</div>

                <div class="signatures">
                    <div class="signature">
                        Cliente
                        <span>' . $this->h($this->clientName($data)) . '</span>
                    </div>

                    <div class="signature">
                        Sistema Lizzie
                        <span>Representante Autorizado</span>
                    </div>
                </div>
            </section>
        ';
    }

    private function getFooter(PrintData $data): string
    {
        return '
            <footer class="footer">
                Documento gerado em ' . $this->h($this->formatDateTime($data->generatedAt)) . '<br>
                Sistema Lizzie — Gestão Empresarial. Documento gerado automaticamente.
            </footer>
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
        if ($value === null || $value === '') {
            return '-';
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

        return '-';
    }

    private function safeOptionalText($value): string
    {
        if ($value === null || $value === '') {
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
            return $encoded !== false ? $encoded : '';
        }

        return '';
    }

    private function safeDate($value): string
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

    private function formatCurrency(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}
