<?php

namespace App\Services;

class PedidoTemplate extends BaseTemplate
{
    public function render(PrintData $data, PrintOptions $options): string
    {
        $isComplete = $this->isComplete($options);

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Pedido Sistema Lizzie</title>
  ' . $this->getStyles() . '
</head>
<body>
  <main class="page">
    ' . $this->getHeader($data) . '
    ' . ($isComplete ? $this->getClientInfo($data) : '') . '
    ' . $this->getItemsTable($data) . '
    ' . $this->getPaymentInfo($data) . '
    ' . ($isComplete ? $this->getAdditionalInfo($data) : '') . '
    ' . $this->getSignatures($data) . '
    ' . $this->getFooter($data) . '
  </main>
</body>
</html>';
    }

    private function getStyles(): string
    {
        return '
  <style>
    @page {
      size: A4 portrait;
      margin: 10mm 10mm 12mm 10mm;
    }

    * {
      box-sizing: border-box;
      font-family: Arial, Helvetica, sans-serif;
    }

    html,
    body {
      margin: 0;
      min-height: 100%;
      background: #ffffff;
      color: #0f172a;
    }

    body {
      padding: 0;
    }

    .page {
      width: 100%;
      margin: 0 auto;
      background: #ffffff;
      padding: 5mm 0;
      border-radius: 0;
      border: 0;
      border-top: 1px solid #d4dce8;
      border-bottom: 1px solid #d4dce8;
      -webkit-box-decoration-break: clone;
      box-decoration-break: clone;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 1px solid #0f172a;
      padding-bottom: 9px;
    }

    .brand {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .logo {
      width: 44px;
      height: 44px;
      border: 1px solid #cbd5e1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 9px;
      color: #94a3b8;
      background: #f8fafc;
      flex: 0 0 auto;
    }

    .brand h1 {
      margin: 0;
      font-size: 18px;
      line-height: 1.05;
    }

    .brand p,
    .order-info p {
      margin: 3px 0;
      font-size: 9px;
      color: #64748b;
    }

    .order-info {
      text-align: right;
    }

    .order-info h2 {
      margin: 0 0 5px;
      font-size: 14px;
      line-height: 1.1;
    }

    .section {
      margin-top: 10px;
    }

    .section-title {
      font-weight: bold;
      font-size: 10px;
      margin-bottom: 5px;
      border-bottom: 1px solid #94a3b8;
      padding-bottom: 3px;
    }

    .box {
      border: 0;
      border-radius: 0;
      padding: 7px;
      background: #f8fafc;
    }

    .client-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4px 24px;
      font-size: 10px;
      line-height: 1.25;
    }

    .client-grid .full {
      grid-column: 1 / -1;
    }

    strong {
      color: #0f172a;
      font-weight: 700;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 10px;
    }

    th {
      text-align: left;
      background: #f1f5f9;
      padding: 5px;
      border-bottom: 1px solid #cbd5e1;
      color: #0f172a;
      font-weight: 700;
    }

    td {
      padding: 5px;
      border-bottom: 1px solid #cbd5e1;
      vertical-align: top;
    }

    th:nth-child(2),
    td:nth-child(2),
    th:nth-child(4),
    td:nth-child(4),
    th:nth-child(5),
    td:nth-child(5) {
      text-align: right;
    }

    tr {
      break-inside: avoid;
    }

    .product-name {
      font-weight: 700;
    }

    .product-reference {
      margin-top: 2px;
      color: #64748b;
      font-size: 8px;
    }

    .total-row {
      text-align: right;
      font-weight: bold;
      padding-top: 7px;
      font-size: 10px;
    }

    .payment {
      display: grid;
      grid-template-columns: 1fr 1fr;
      font-size: 10px;
      gap: 24px;
      line-height: 1.25;
    }

    .note-box {
      min-height: 0;
      font-size: 10px;
      color: #334155;
      line-height: 1.25;
    }

    .note-box + .note-box {
      margin-top: 8px;
    }

    .signatures {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 32px;
      margin-top: 24px;
      break-inside: avoid;
    }

    .signature {
      border-top: 1px solid #0f172a;
      text-align: center;
      padding-top: 6px;
      font-size: 10px;
    }

    .signature span {
      display: block;
      color: #64748b;
      font-size: 8px;
      margin-top: 3px;
    }

    .footer {
      border-top: 1px solid #cbd5e1;
      margin-top: 12px;
      padding-top: 7px;
      text-align: center;
      color: #94a3b8;
      font-size: 8px;
    }
  </style>';
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
    </header>';
    }

    private function getClientInfo(PrintData $data): string
    {
        return '
    <section class="section">
      <div class="section-title">Dados do Cliente</div>

      <div class="box client-grid">
        <div><strong>Nome/Razão Social:</strong> ' . $this->h($this->clientName($data)) . '</div>
        <div><strong>Responsável:</strong> ' . $this->h($this->safeText($data->cliente->responsavel ?? '-')) . '</div>
        <div><strong>CPF/CNPJ:</strong> ' . $this->h($this->safeText($data->cliente->cpf_cnpj ?? '-')) . '</div>
        <div><strong>Email:</strong> ' . $this->h($this->safeText($data->cliente->email ?? '-')) . '</div>
        <div><strong>Contato:</strong> ' . $this->h($this->safeText($data->cliente->contato_1 ?? '-')) . '</div>
        <div><strong>Cidade/Estado:</strong> ' . $this->h($this->safeText($data->cliente->cidade ?? '-')) . ' / ' . $this->h($this->safeText($data->cliente->estado ?? '-')) . '</div>
        <div class="full"><strong>Endereço:</strong> ' . $this->h($this->safeText($data->cliente->endereco ?? '-')) . ', ' . $this->h($this->safeText($data->cliente->bairro ?? '-')) . ' - ' . $this->h($this->safeText($data->cliente->cep ?? '-')) . '</div>
      </div>
    </section>';
    }

    private function getItemsTable(PrintData $data): string
    {
        $html = '
    <section class="section">
      <div class="section-title">Itens do Pedido</div>

      <table>
        <thead>
          <tr>
            <th style="width: 36%;">Produto</th>
            <th style="width: 8%;">Qtd.</th>
            <th style="width: 32%;">Tamanhos</th>
            <th style="width: 12%;">Unitário</th>
            <th style="width: 12%;">Total</th>
          </tr>
        </thead>
        <tbody>';

        foreach ($data->itens as $item) {
            $quantity = $this->sumQuantidades($item);
            $unitPrice = $quantity > 0 ? ((float)($item->total_item ?? 0) / $quantity) : 0;

            $html .= '
          <tr>
            <td>
              <div class="product-name">' . $this->h($this->safeText($item->produto ?? '-')) . '</div>
              <div class="product-reference">' . $this->h($this->safeText($item->referencia ?? '-')) . '</div>
            </td>
            <td>' . $quantity . '</td>
            <td>' . $this->h($this->getSizeText($item)) . '</td>
            <td>' . $this->h($this->formatCurrency($unitPrice)) . '</td>
            <td>' . $this->h($this->formatCurrency((float)($item->total_item ?? 0))) . '</td>
          </tr>';
        }

        $html .= '
        </tbody>
      </table>

      <div class="total-row">
        Total do Pedido: ' . $this->h($this->formatCurrency((float)($data->pedido->total_pedido ?? 0))) . '
      </div>
    </section>';

        return $html;
    }

    private function getPaymentInfo(PrintData $data): string
    {
        $discount = (float)($data->pedido->ped_desconto ?? 0);
        $total = (float)($data->pedido->total_pedido ?? 0);
        $subtotalHtml = $discount > 0
            ? '<strong>Subtotal:</strong> ' . $this->h($this->formatCurrency($total + $discount)) . '<br />'
            : '';
        $discountHtml = $discount > 0
            ? '<strong>Desconto:</strong> ' . $this->h($this->formatCurrency($discount)) . '<br />'
            : '';

        return '
    <section class="section">
      <div class="section-title">Resumo e Pagamento</div>

      <div class="box payment">
        <div>
          <strong>Status:</strong> ' . $this->h($this->getStatusText((int)($data->pedido->status ?? 0))) . '<br />
          ' . $discountHtml . '
          <strong>Total:</strong> ' . $this->h($this->formatCurrency($total)) . '
        </div>

        <div>
          <strong>Forma de Pagamento:</strong> ' . $this->h($this->safeText($data->pedido->forma_pag ?? '-')) . '<br />
          ' . $subtotalHtml . '
        </div>
      </div>
    </section>';
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
      <div class="section-title">Informações Adicionais</div>';

        if ($obsPedido !== '') {
            $html .= '
      <div class="box note-box">
        <strong>Observações do Pedido</strong><br />
        ' . nl2br($this->h($obsPedido)) . '
      </div>';
        }

        if ($obsEntrega !== '') {
            $html .= '
      <div class="box note-box">
        <strong>Observações de Entrega</strong><br />
        ' . nl2br($this->h($obsEntrega)) . '
      </div>';
        }

        $html .= '
    </section>';

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
    </section>';
    }

    private function getFooter(PrintData $data): string
    {
        return '
    <footer class="footer">
      Documento gerado em ' . $this->h($this->formatGeneratedAt($data->generatedAt)) . '<br />
      Sistema Lizzie - Gestão Empresarial
    </footer>';
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

    private function formatGeneratedAt(\DateTime $date): string
    {
        return $date->format('d/m/Y \à\s H:i');
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
            ['key' => 'ida_1', 'label' => '1'],
            ['key' => 'ida_2', 'label' => '2'],
            ['key' => 'ida_3', 'label' => '3'],
            ['key' => 'ida_4', 'label' => '4'],
            ['key' => 'ida_6', 'label' => '6'],
            ['key' => 'ida_8', 'label' => '8'],
            ['key' => 'ida_10', 'label' => '10'],
            ['key' => 'ida_12', 'label' => '12'],
            ['key' => 'lisa', 'label' => 'Lisa'],
        ];

        $badges = [];
        foreach ($sizes as $size) {
            $quantity = intval($item->{$size['key']} ?? 0);
            if ($quantity > 0) {
                $badges[] = $size['label'] . ':' . $quantity;
            }
        }

        return $badges;
    }

    protected function formatCurrency(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}
