<?php

namespace App\Http\Controllers;

use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $ano = $request->has('ano') ? $request->ano : date('Y');
        $mes = $request->has('mes') ? $request->mes : null;
        
        $cacheKey = "dashboard:{$ano}:{$mes}";
        
        return CacheService::get($cacheKey, function() use ($ano, $mes) {
            return $this->getDashboardData($ano, $mes);
        }, 300);
    }
    
    private function getDashboardData($ano, $mes)
    {
        $statusCounts = DB::table('pedidos')
            ->select('status', DB::raw('COUNT(*) as quantidade'))
            ->groupBy('status')
            ->pluck('quantidade', 'status')
            ->toArray();
        
        $pedidosAberto = $statusCounts[1] ?? 0;
        $pedidosPendente = $statusCounts[2] ?? 0;
        $pedidosCancelado = $statusCounts[3] ?? 0;
        $pedidosConcluido = $statusCounts[4] ?? 0;
        
        $clientesAtivos = DB::table('clientes')->where('status', 1)->count();
        $clientesInativos = DB::table('clientes')->where('status', 0)->count();
        
        $produtosDisponiveis = DB::table('produtos')->where('status', 1)->count();

        // Aniversariantes do dia
        $aniversariantes = DB::table('clientes')
            ->select('id_cliente', 'responsavel', 'razao_social', 'data_nascimento', 'contato_1')
            ->where('status', 1)
            ->whereRaw('DAY(data_nascimento) = DAY(CURDATE())')
            ->whereRaw('MONTH(data_nascimento) = MONTH(CURDATE())')
            ->get();
        
        $totalVendas = DB::table('pedidos')->where('status', 4)->sum('total_pedido') ?? 0;
        
        $whereBase = DB::table('pedidos')->where('status', 4);
        if ($mes) {
            $whereBase->whereMonth('data_pedido', $mes)->whereYear('data_pedido', $ano);
        } else {
            $whereBase->whereYear('data_pedido', $ano);
        }
        
        $vendasPeriodo = $whereBase->sum('total_pedido') ?? 0;
        $pedidosPeriodo = DB::table('pedidos')
            ->whereYear('data_pedido', $ano)
            ->when($mes, fn($q) => $q->whereMonth('data_pedido', $mes))
            ->count();
        
        $vendasPorDia = DB::table('pedidos')
            ->select(DB::raw('DATE(data_pedido) as data'), DB::raw('SUM(total_pedido) as total'), DB::raw('COUNT(*) as quantidade'))
            ->where('status', 4)
            ->whereYear('data_pedido', $ano)
            ->groupBy(DB::raw('DATE(data_pedido)'))
            ->orderBy('data')
            ->limit(30)
            ->get();
        
        $topProdutos = DB::table('itens_pedidos')
            ->select('itens_pedidos.id_produto', 'produtos.produto', DB::raw('SUM(itens_pedidos.total_item) as total_vendido'), DB::raw('COUNT(*) as quantidade'))
            ->join('produtos', 'itens_pedidos.id_produto', '=', 'produtos.id_produto')
            ->join('pedidos', 'itens_pedidos.id_pedido', '=', 'pedidos.id_pedido')
            ->where('pedidos.status', 4)
            ->groupBy('itens_pedidos.id_produto')
            ->orderByDesc('total_vendido')
            ->limit(10)
            ->get();
        
        $topClientes = DB::table('pedidos')
            ->select('pedidos.id_cliente', 'clientes.razao_social', DB::raw('SUM(pedidos.total_pedido) as total_compras'), DB::raw('COUNT(*) as quantidade_pedidos'))
            ->join('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->where('pedidos.status', 4)
            ->groupBy('pedidos.id_cliente')
            ->orderByDesc('total_compras')
            ->limit(10)
            ->get();
        
        $vendasPorVendedor = DB::table('pedidos')
            ->select('pedidos.id_vendedor', 'vendedores.nome_vendedor', DB::raw('SUM(pedidos.total_pedido) as total_vendas'), DB::raw('COUNT(*) as quantidade_pedidos'))
            ->join('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->where('pedidos.status', 4)
            ->groupBy('pedidos.id_vendedor')
            ->orderByDesc('total_vendas')
            ->get();
        
        return [
            'success' => true,
            'data' => [
                'clientes' => [
                    'ativos' => $clientesAtivos,
                    'inativos' => $clientesInativos,
                    'total' => $clientesAtivos + $clientesInativos
                ],
                'pedidos' => [
                    'aberto' => $pedidosAberto,
                    'pendente' => $pedidosPendente,
                    'cancelado' => $pedidosCancelado,
                    'concluido' => $pedidosConcluido,
                    'total' => $pedidosAberto + $pedidosPendente + $pedidosCancelado + $pedidosConcluido
                ],
                'produtos' => [
                    'disponiveis' => $produtosDisponiveis
                ],
                'aniversariantes' => [
                    'hoje' => $aniversariantes,
                    'quantidade' => count($aniversariantes)
                ],
                'vendas' => [
                    'total_geral' => $totalVendas,
                    'vendas_periodo' => $vendasPeriodo,
                    'pedidos_periodo' => $pedidosPeriodo
                ],
                'graficos' => [
                    'vendas_por_dia' => $vendasPorDia,
                    'top_produtos' => $topProdutos,
                    'top_clientes' => $topClientes,
                    'vendas_por_vendedor' => $vendasPorVendedor
                ]
            ]
        ];
    }
    
    public function relatorioVendas(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim = $request->data_fim ?? date('Y-m-t');

        // Se data_inicio for timestamp, converter para data
        if (is_numeric($dataInicio) && strlen($dataInicio) > 10) {
            $dataInicio = date('Y-m-d', $dataInicio / 1000);
        }
        // Se data_fim for timestamp, converter para data
        if (is_numeric($dataFim) && strlen($dataFim) > 10) {
            $dataFim = date('Y-m-d', $dataFim / 1000);
        }

        // Filtro de status - default: apenas concluídos (4)
        $statusFilter = $request->status ?? [4];
        if (!is_array($statusFilter)) {
            $statusFilter = [$statusFilter];
        }

        $query = DB::table('pedidos')
            ->select('pedidos.*', 'clientes.razao_social', 'vendedores.nome_vendedor')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->whereIn('pedidos.status', $statusFilter)
            ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59']);

        if ($request->has('id_vendedor') && $request->id_vendedor) {
            $query->where('pedidos.id_vendedor', $request->id_vendedor);
        }
        
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);
        $perPage = min(max($perPage, 1), 100);

        $total = $query->count();
        $vendas = $query->orderBy('pedidos.data_pedido', 'desc')->offset(($page - 1) * $perPage)->limit($perPage)->get();
        
        return response()->json([
            'success' => true,
            'data' => $vendas,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }
    
    public function relatorioVendedores(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim = $request->data_fim ?? date('Y-m-t');

        // Se data_inicio for timestamp, converter para data
        if (is_numeric($dataInicio) && strlen($dataInicio) > 10) {
            $dataInicio = date('Y-m-d', $dataInicio / 1000);
        }
        // Se data_fim for timestamp, converter para data
        if (is_numeric($dataFim) && strlen($dataFim) > 10) {
            $dataFim = date('Y-m-d', $dataFim / 1000);
        }

        // Filtro de status - default: apenas concluídos (4)
        $statusFilter = $request->status ?? [4];
        if (!is_array($statusFilter)) {
            $statusFilter = [$statusFilter];
        }

        $vendedores = DB::table('vendedores')
            ->select(
                'vendedores.id_vendedor',
                'vendedores.nome_vendedor',
                DB::raw('COUNT(pedidos.id_pedido) as quantidade_pedidos'),
                DB::raw('SUM(pedidos.total_pedido) as total_vendas'),
                DB::raw('AVG(pedidos.total_pedido) as media_pedido')
            )
            ->leftJoin('pedidos', function($join) use ($dataInicio, $dataFim, $statusFilter) {
                $join->on('vendedores.id_vendedor', '=', 'pedidos.id_vendedor')
                    ->whereIn('pedidos.status', $statusFilter)
                    ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59']);
            })
            ->groupBy('vendedores.id_vendedor', 'vendedores.nome_vendedor')
            ->orderByDesc('total_vendas')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $vendedores
        ]);
    }
    
    public function relatorioProdutos(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim = $request->data_fim ?? date('Y-m-t');

        // Se data_inicio for timestamp, converter para data
        if (is_numeric($dataInicio) && strlen($dataInicio) > 10) {
            $dataInicio = date('Y-m-d', $dataInicio / 1000);
        }
        // Se data_fim for timestamp, converter para data
        if (is_numeric($dataFim) && strlen($dataFim) > 10) {
            $dataFim = date('Y-m-d', $dataFim / 1000);
        }

        // Filtro de status - default: apenas concluídos (4)
        $statusFilter = $request->status ?? [4];
        if (!is_array($statusFilter)) {
            $statusFilter = [$statusFilter];
        }

        $produtos = DB::table('itens_pedidos')
            ->select(
                'produtos.id_produto',
                'produtos.produto',
                'produtos.referencia',
                DB::raw('SUM(itens_pedidos.total_item) as total_vendido'),
                DB::raw('SUM(itens_pedidos.tam_pp) + SUM(itens_pedidos.tam_p) + SUM(itens_pedidos.tam_m) + SUM(itens_pedidos.tam_g) + SUM(itens_pedidos.tam_u) + SUM(itens_pedidos.tam_rn) + SUM(itens_pedidos.ida_1) + SUM(itens_pedidos.ida_2) + SUM(itens_pedidos.ida_3) + SUM(itens_pedidos.ida_4) + SUM(itens_pedidos.ida_6) + SUM(itens_pedidos.ida_8) + SUM(itens_pedidos.ida_10) + SUM(itens_pedidos.ida_12) + SUM(itens_pedidos.lisa) as quantidade')
            )
            ->join('produtos', 'itens_pedidos.id_produto', '=', 'produtos.id_produto')
            ->join('pedidos', function($join) use ($dataInicio, $dataFim, $statusFilter, $request) {
                $join->on('itens_pedidos.id_pedido', '=', 'pedidos.id_pedido')
                    ->whereIn('pedidos.status', $statusFilter)
                    ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59']);

                // Filtrar por vendedor se especificado
                if ($request->has('id_vendedor') && $request->id_vendedor) {
                    $join->where('pedidos.id_vendedor', $request->id_vendedor);
                }
            })
            ->groupBy('produtos.id_produto', 'produtos.produto', 'produtos.referencia')
            ->orderByDesc('total_vendido')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $produtos
        ]);
    }

    public function estatisticas(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim = $request->data_fim ?? date('Y-m-t');

        // Se data_inicio for timestamp, converter para data
        if (is_numeric($dataInicio) && strlen($dataInicio) > 10) {
            $dataInicio = date('Y-m-d', $dataInicio / 1000);
        }
        // Se data_fim for timestamp, converter para data
        if (is_numeric($dataFim) && strlen($dataFim) > 10) {
            $dataFim = date('Y-m-d', $dataFim / 1000);
        }

        // Filtro de status - default: apenas concluídos (4)
        $statusFilter = $request->status ?? [4];
        if (!is_array($statusFilter)) {
            $statusFilter = [$statusFilter];
        }

        // Período anterior para comparação
        $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / (60 * 60 * 24);
        $inicioAnterior = date('Y-m-d', strtotime($dataInicio . ' -' . ($diasPeriodo + 1) . ' days'));
        $fimAnterior = date('Y-m-d', strtotime($dataInicio . ' -1 day'));

        // === MÉTRICAS AVANÇADAS ===

        // Faturamento Real (apenas concluídos)
        $faturamentoReal = DB::table('pedidos')
            ->where('status', 4)
            ->whereBetween('data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->sum('total_pedido');

        // Receita Projetada (pendentes + concluídos)
        $receitaProjetada = DB::table('pedidos')
            ->whereIn('status', [2, 4])
            ->whereBetween('data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->sum('total_pedido');

        // Valor em Aberto (apenas pendentes)
        $valorAberto = DB::table('pedidos')
            ->where('status', 2)
            ->whereBetween('data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->sum('total_pedido');

        // Pedidos Perdidos (cancelados)
        $pedidosPerdidos = DB::table('pedidos')
            ->where('status', 3)
            ->whereBetween('data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->sum('total_pedido');

        // Em Análise (abertos)
        $emAnalise = DB::table('pedidos')
            ->where('status', 1)
            ->whereBetween('data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->sum('total_pedido');

        // Contagem de pedidos por status
        $contagemStatus = DB::table('pedidos')
            ->whereBetween('data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->select('status', DB::raw('COUNT(*) as quantidade'), DB::raw('SUM(total_pedido) as valor'))
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->toArray();

        // === MÉTRICAS LEGACY (para compatibilidade) ===
        // Dados do período atual (baseado no filtro de status)
        $vendasAtual = DB::table('pedidos')
            ->whereIn('status', $statusFilter)
            ->whereBetween('data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->select(
                DB::raw('COUNT(*) as quantidade'),
                DB::raw('SUM(total_pedido) as total'),
                DB::raw('AVG(total_pedido) as ticket_medio')
            )
            ->first();

        // Dados do período anterior
        $vendasAnterior = DB::table('pedidos')
            ->whereIn('status', $statusFilter)
            ->whereBetween('data_pedido', [$inicioAnterior, $fimAnterior . ' 23:59:59'])
            ->select(
                DB::raw('COUNT(*) as quantidade'),
                DB::raw('SUM(total_pedido) as total'),
                DB::raw('AVG(total_pedido) as ticket_medio')
            )
            ->first();

        // Vendas por dia (baseado no filtro de status)
        $vendasDiarias = DB::table('pedidos')
            ->whereIn('status', $statusFilter)
            ->whereBetween('data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->select(
                DB::raw('DATE(data_pedido) as data'),
                DB::raw('COUNT(*) as quantidade'),
                DB::raw('SUM(total_pedido) as total')
            )
            ->groupBy(DB::raw('DATE(data_pedido)'))
            ->orderBy('data')
            ->get();

        // Top 3 vendedores (baseado no filtro de status)
        $topVendedores = DB::table('pedidos')
            ->select(
                'vendedores.id_vendedor',
                'vendedores.nome_vendedor',
                DB::raw('COUNT(*) as quantidade'),
                DB::raw('SUM(total_pedido) as total')
            )
            ->join('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->whereIn('pedidos.status', $statusFilter)
            ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->groupBy('vendedores.id_vendedor', 'vendedores.nome_vendedor')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        // Calcular crescimento
        $crescimentoVendas = $vendasAnterior->total > 0
            ? round((($vendasAtual->total - $vendasAnterior->total) / $vendasAnterior->total) * 100, 1)
            : 0;
        $crescimentoPedidos = $vendasAnterior->quantidade > 0
            ? round((($vendasAtual->quantidade - $vendasAnterior->quantidade) / $vendasAnterior->quantidade) * 100, 1)
            : 0;
        $crescimentoTicket = $vendasAnterior->ticket_medio > 0
            ? round((($vendasAtual->ticket_medio - $vendasAnterior->ticket_medio) / $vendasAnterior->ticket_medio) * 100, 1)
            : 0;

        // Calcular taxas
        $totalAtivos = ($contagemStatus[2]->quantidade ?? 0) + ($contagemStatus[4]->quantidade ?? 0); // Pendente + Concluído
        $taxaConversao = $totalAtivos > 0
            ? round(($contagemStatus[4]->quantidade ?? 0) / $totalAtivos * 100, 1)
            : 0;

        $totalGeral = array_sum(array_map(fn($item) => $item->quantidade, $contagemStatus));
        $taxaPerda = $totalGeral > 0
            ? round(($contagemStatus[3]->quantidade ?? 0) / $totalGeral * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                // Métricas avançadas
                'metricas' => [
                    'faturamento_real' => $faturamentoReal ?: 0,
                    'receita_projetada' => $receitaProjetada ?: 0,
                    'valor_aberto' => $valorAberto ?: 0,
                    'pedidos_perdidos' => $pedidosPerdidos ?: 0,
                    'em_analise' => $emAnalise ?: 0,
                    'taxa_conversao' => $taxaConversao,
                    'taxa_perda' => $taxaPerda
                ],
                // Estatísticas por status
                'status_contagem' => $contagemStatus,
                // Métricas legacy (para compatibilidade)
                'periodo_atual' => [
                    'total_vendas' => $vendasAtual->total ?: 0,
                    'quantidade_pedidos' => $vendasAtual->quantidade ?: 0,
                    'ticket_medio' => $vendasAtual->ticket_medio ?: 0
                ],
                'periodo_anterior' => [
                    'total_vendas' => $vendasAnterior->total ?: 0,
                    'quantidade_pedidos' => $vendasAnterior->quantidade ?: 0,
                    'ticket_medio' => $vendasAnterior->ticket_medio ?: 0
                ],
                'crescimento' => [
                    'vendas' => $crescimentoVendas,
                    'pedidos' => $crescimentoPedidos,
                    'ticket_medio' => $crescimentoTicket
                ],
                'vendas_diarias' => $vendasDiarias,
                'top_vendedores' => $topVendedores
            ]
        ]);
    }

    public function insights(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim = $request->data_fim ?? date('Y-m-t');

        // Se data_inicio for timestamp, converter para data
        if (is_numeric($dataInicio) && strlen($dataInicio) > 10) {
            $dataInicio = date('Y-m-d', $dataInicio / 1000);
        }
        // Se data_fim for timestamp, converter para data
        if (is_numeric($dataFim) && strlen($dataFim) > 10) {
            $dataFim = date('Y-m-d', $dataFim / 1000);
        }

        // Produtos com potencial (alta quantidade, ticket baixo = oportunidade)
        $produtosPotencial = DB::table('itens_pedidos')
            ->select(
                'produtos.id_produto',
                'produtos.produto',
                'produtos.referencia',
                DB::raw('SUM(itens_pedidos.total_item) as total_vendido'),
                DB::raw('SUM(itens_pedidos.tam_pp) + SUM(itens_pedidos.tam_p) + SUM(itens_pedidos.tam_m) + SUM(itens_pedidos.tam_g) + SUM(itens_pedidos.tam_u) + SUM(itens_pedidos.tam_rn) + SUM(itens_pedidos.ida_1) + SUM(itens_pedidos.ida_2) + SUM(itens_pedidos.ida_3) + SUM(itens_pedidos.ida_4) + SUM(itens_pedidos.ida_6) + SUM(itens_pedidos.ida_8) as quantidade_vendida'),
                DB::raw('AVG(itens_pedidos.total_item) as ticket_medio')
            )
            ->join('produtos', 'itens_pedidos.id_produto', '=', 'produtos.id_produto')
            ->join('pedidos', function($join) use ($dataInicio, $dataFim) {
                $join->on('itens_pedidos.id_pedido', '=', 'pedidos.id_pedido')
                    ->where('pedidos.status', 4)
                    ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59']);
            })
            ->groupBy('produtos.id_produto', 'produtos.produto', 'produtos.referencia')
            ->having('quantidade_vendida', '>=', 10)
            ->orderByDesc('quantidade_vendida')
            ->limit(5)
            ->get();

        // Vendedores com melhor desempenho
        $vendedoresPerformance = DB::table('pedidos')
            ->select(
                'vendedores.id_vendedor',
                'vendedores.nome_vendedor',
                DB::raw('COUNT(*) as quantidade_pedidos'),
                DB::raw('SUM(total_pedido) as total_vendas'),
                DB::raw('AVG(total_pedido) as ticket_medio')
            )
            ->join('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->where('pedidos.status', 4)
            ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->groupBy('vendedores.id_vendedor', 'vendedores.nome_vendedor')
            ->orderByDesc('total_vendas')
            ->get();

        // Produtos mais vendido
        $topProdutos = DB::table('itens_pedidos')
            ->select(
                'produtos.id_produto',
                'produtos.produto',
                DB::raw('SUM(itens_pedidos.total_item) as total_vendido'),
                DB::raw('SUM(itens_pedidos.tam_pp) + SUM(itens_pedidos.tam_p) + SUM(itens_pedidos.tam_m) + SUM(itens_pedidos.tam_g) + SUM(itens_pedidos.tam_u) + SUM(itens_pedidos.tam_rn) + SUM(itens_pedidos.ida_1) + SUM(itens_pedidos.ida_2) + SUM(itens_pedidos.ida_3) + SUM(itens_pedidos.ida_4) + SUM(itens_pedidos.ida_6) + SUM(itens_pedidos.ida_8) as quantidade_vendida')
            )
            ->join('produtos', 'itens_pedidos.id_produto', '=', 'produtos.id_produto')
            ->join('pedidos', function($join) use ($dataInicio, $dataFim) {
                $join->on('itens_pedidos.id_pedido', '=', 'pedidos.id_pedido')
                    ->where('pedidos.status', 4)
                    ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59']);
            })
            ->groupBy('produtos.id_produto', 'produtos.produto')
            ->orderByDesc('quantidade_vendida')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'produtos_potencial' => $produtosPotencial,
                'vendedores_performance' => $vendedoresPerformance,
                'top_produtos' => $topProdutos
            ]
        ]);
    }

    public function relatorioClientes(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim = $request->data_fim ?? date('Y-m-t');

        // Se data_inicio for timestamp, converter para data
        if (is_numeric($dataInicio) && strlen($dataInicio) > 10) {
            $dataInicio = date('Y-m-d', $dataInicio / 1000);
        }
        // Se data_fim for timestamp, converter para data
        if (is_numeric($dataFim) && strlen($dataFim) > 10) {
            $dataFim = date('Y-m-d', $dataFim / 1000);
        }

        // Status filter - default: apenas concluídos (4)
        $statusFilter = $request->status ?? [4];
        if (!is_array($statusFilter)) {
            $statusFilter = [$statusFilter];
        }

        // Clientes com compras no período
        $clientes = DB::table('clientes')
            ->select(
                'clientes.id_cliente',
                'clientes.razao_social',
                'clientes.cpf_cnpj',
                'clientes.email',
                'clientes.contato_1',
                DB::raw('COUNT(pedidos.id_pedido) as quantidade_pedidos'),
                DB::raw('SUM(pedidos.total_pedido) as total_compras'),
                DB::raw('AVG(pedidos.total_pedido) as ticket_medio'),
                DB::raw('MAX(pedidos.data_pedido) as ultima_compra')
            )
            ->leftJoin('pedidos', function($join) use ($dataInicio, $dataFim, $statusFilter) {
                $join->on('clientes.id_cliente', '=', 'pedidos.id_cliente')
                    ->whereIn('pedidos.status', $statusFilter)
                    ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59']);
            })
            ->groupBy('clientes.id_cliente', 'clientes.razao_social', 'clientes.cpf_cnpj', 'clientes.email', 'clientes.contato_1')
            ->having('quantidade_pedidos', '>', 0)
            ->orderByDesc('total_compras');

        // Paginação
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);
        $perPage = min(max($perPage, 1), 100);

        // Get total count using subquery to avoid GROUP BY issues
        $totalQuery = DB::table('clientes')
            ->leftJoin('pedidos', function($join) use ($dataInicio, $dataFim, $statusFilter) {
                $join->on('clientes.id_cliente', '=', 'pedidos.id_cliente')
                    ->whereIn('pedidos.status', $statusFilter)
                    ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59']);
            })
            ->select(DB::raw('COUNT(DISTINCT clientes.id_cliente) as total'))
            ->whereRaw('EXISTS (SELECT 1 FROM pedidos p WHERE p.id_cliente = clientes.id_cliente AND p.status IN (' . implode(',', $statusFilter) . ') AND p.data_pedido BETWEEN ? AND ?)', [$dataInicio, $dataFim . ' 23:59:59']);

        if ($request->has('id_cliente') && $request->id_cliente) {
            $totalQuery->where('clientes.id_cliente', $request->id_cliente);
        }

        $total = $totalQuery->first()->total ?? 0;
        $clientesData = $clientes->offset(($page - 1) * $perPage)->limit($perPage)->get();

        // Estatísticas gerais
        $estatisticas = [
            'total_clientes' => $total,
            'receita_total' => $clientesData->sum('total_compras'),
            'ticket_medio_geral' => $total > 0 ? $clientesData->sum('total_compras') / $total : 0,
            'pedidos_total' => $clientesData->sum('quantidade_pedidos')
        ];

        return response()->json([
            'success' => true,
            'data' => $clientesData,
            'estatisticas' => $estatisticas,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }

    public function clienteDetalhes(Request $request, $id)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim = $request->data_fim ?? date('Y-m-t');

        if (is_numeric($dataInicio) && strlen($dataInicio) > 10) {
            $dataInicio = date('Y-m-d', $dataInicio / 1000);
        }
        if (is_numeric($dataFim) && strlen($dataFim) > 10) {
            $dataFim = date('Y-m-d', $dataFim / 1000);
        }

        $cliente = DB::table('clientes')->where('id_cliente', $id)->first();

        if (!$cliente) {
            return response()->json(['success' => false, 'message' => 'Cliente não encontrado'], 404);
        }

        $statusFilter = $request->status ?? [4];
        if (!is_array($statusFilter)) {
            $statusFilter = [$statusFilter];
        }

        // Pedidos do cliente
        $pedidos = DB::table('pedidos')
            ->select(
                'pedidos.*',
                'vendedores.nome_vendedor',
                DB::raw('(SELECT COUNT(*) FROM itens_pedidos WHERE id_pedido = pedidos.id_pedido) as quantidade_itens')
            )
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->where('pedidos.id_cliente', $id)
            ->whereIn('pedidos.status', $statusFilter)
            ->whereBetween('pedidos.data_pedido', [$dataInicio, $dataFim . ' 23:59:59'])
            ->orderBy('pedidos.data_pedido', 'desc')
            ->get();

        // Estatísticas do cliente
        $totalGasto = $pedidos->sum('total_pedido');
        $quantidadePedidos = $pedidos->count();
        $ticketMedio = $quantidadePedidos > 0 ? $totalGasto / $quantidadePedidos : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'cliente' => $cliente,
                'pedidos' => $pedidos,
                'estatisticas' => [
                    'total_gasto' => $totalGasto,
                    'quantidade_pedidos' => $quantidadePedidos,
                    'ticket_medio' => $ticketMedio,
                    'ultimo_pedido' => $pedidos->first()?->data_pedido
                ]
            ]
        ]);
    }
}