<?php

namespace App\Services;

class RelatorioTemplate extends BaseTemplate
{
    public function render(PrintData $data, PrintOptions $options): string
    {
        // This should be overridden by specific report methods
        return '';
    }

    public function renderVendas(PrintData $data, PrintOptions $options): string
    {
        $html = $this->getReportHeader('Relatório de Vendas', $data);
        $html .= $this->getSalesTable($data);
        $html .= $this->getReportFooter($data);

        return $this->wrapInReportContainer($html);
    }

    public function renderVendedores(PrintData $data, PrintOptions $options): string
    {
        $html = $this->getReportHeader('Relatório de Vendedores', $data);
        $html .= $this->getVendedoresTable($data);
        $html .= $this->getReportFooter($data);

        return $this->wrapInReportContainer($html);
    }

    public function renderProdutos(PrintData $data, PrintOptions $options): string
    {
        $html = $this->getReportHeader('Relatório de Produtos', $data);
        $html .= $this->getProdutosTable($data);
        $html .= $this->getReportFooter($data);

        return $this->wrapInReportContainer($html);
    }

    private function getReportHeader(string $title, PrintData $data): string
    {
        return '
            <div style="border-bottom: 2px solid #374151; padding-bottom: 20px; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 60px; height: 60px; background-color: #f3f4f6; border: 1px solid #d1d5db; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                            <span style="font-size: 10px; color: #6b7280;">LOGO</span>
                        </div>
                        <div>
                            <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin: 0;">Sistema Lizzie</h1>
                            <p style="font-size: 0.875rem; color: #6b7280; margin: 5px 0 0 0;">Relatórios</p>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <h2 style="font-size: 1.5rem; font-weight: 600; color: #111827; margin: 0;">' . $title . '</h2>
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 5px 0 0 0;">Gerado em: ' . $this->formatDateTime($data->generatedAt) . '</p>
                    </div>
                </div>
            </div>
        ';
    }

    private function getSalesTable(PrintData $data): string
    {
        $html = '
            <div style="margin-bottom: 30px;">
                <h3 style="border-bottom: 1px solid #d1d5db; padding-bottom: 8px; margin-bottom: 15px; font-size: 1.25rem; font-weight: 700; color: #111827;">Vendas por Período</h3>
                <div style="overflow: hidden; border: 1px solid #d1d5db; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f9fafb;">
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Data</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Cliente</th>
                                <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Valor</th>
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
        ';

        // Assuming data->itens contains sales data
        $total = 0;
        foreach ($data->itens as $item) {
            $html .= '
                            <tr>
                                <td style="padding: 12px 16px;">' . $this->formatDate($item->data_pedido ?? date('Y-m-d')) . '</td>
                                <td style="padding: 12px 16px;">' . htmlspecialchars($item->razao_social ?? 'Cliente') . '</td>
                                <td style="padding: 12px 16px; text-align: right;">' . $this->formatCurrency($item->total_pedido ?? 0) . '</td>
                                <td style="padding: 12px 16px; text-align: center;">' . $this->getStatusText($item->status ?? 1) . '</td>
                            </tr>
            ';
            $total += $item->total_pedido ?? 0;
        }

        $html .= '
                            <tr style="background-color: #f9fafb; font-weight: bold;">
                                <td colspan="2" style="padding: 12px 16px; text-align: right; font-weight: bold;">Total:</td>
                                <td style="padding: 12px 16px; text-align: right; font-weight: bold;">' . $this->formatCurrency($total) . '</td>
                                <td style="padding: 12px 16px;"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        ';

        return $html;
    }

    private function getVendedoresTable(PrintData $data): string
    {
        // Similar structure for vendedores report
        return '
            <div style="margin-bottom: 30px;">
                <h3 style="border-bottom: 1px solid #d1d5db; padding-bottom: 8px; margin-bottom: 15px; font-size: 1.25rem; font-weight: 700; color: #111827;">Performance dos Vendedores</h3>
                <div style="overflow: hidden; border: 1px solid #d1d5db; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f9fafb;">
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Vendedor</th>
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Pedidos</th>
                                <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Total Vendido</th>
                                <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Média/Pedido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" style="padding: 12px 16px; text-align: center; color: #6b7280;">Dados do relatório serão populados dinamicamente</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        ';
    }

    private function getProdutosTable(PrintData $data): string
    {
        // Similar structure for produtos report
        return '
            <div style="margin-bottom: 30px;">
                <h3 style="border-bottom: 1px solid #d1d5db; padding-bottom: 8px; margin-bottom: 15px; font-size: 1.25rem; font-weight: 700; color: #111827;">Produtos Mais Vendidos</h3>
                <div style="overflow: hidden; border: 1px solid #d1d5db; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f9fafb;">
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Produto</th>
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Quantidade</th>
                                <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb;">Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="3" style="padding: 12px 16px; text-align: center; color: #6b7280;">Dados do relatório serão populados dinamicamente</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        ';
    }

    private function getReportFooter(PrintData $data): string
    {
        return '
            <div style="text-align: center; font-size: 0.75rem; color: #6b7280; margin-top: 60px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                <p style="font-weight: 500;">Relatório gerado em ' . $this->formatDateTime($data->generatedAt) . '</p>
                <p style="margin-top: 4px;">Sistema Lizzie - Gestão Empresarial | Relatório oficial do sistema.</p>
            </div>
        ';
    }

    private function wrapInReportContainer(string $html): string
    {
        return '
            <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background: white; max-width: 1000px; margin: 0 auto; padding: 40px;">
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
}