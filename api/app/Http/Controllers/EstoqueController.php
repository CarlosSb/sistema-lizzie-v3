<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstoqueController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('produtos')
            ->select(
                'produtos.id_produto',
                'produtos.produto',
                'produtos.referencia',
                'produtos.valor_unt_norde',
                DB::raw('COALESCE(SUM(item_estoques.tam_p + item_estoques.tam_m + item_estoques.tam_g + item_estoques.tam_u + item_estoques.tam_rn + item_estoques.ida_1 + item_estoques.ida_2 + item_estoques.ida_3 + item_estoques.ida_4 + item_estoques.ida_6 + item_estoques.ida_8 + item_estoques.ida_10 + item_estoques.ida_12), 0) as quantidade_total')
            )
            ->leftJoin('item_estoques', 'produtos.id_produto', '=', 'item_estoques.estoque_id')
            ->groupBy('produtos.id_produto');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('produtos.produto', 'like', "%{$search}%")
                  ->orWhere('produtos.referencia', 'like', "%{$search}%");
            });
        }

        if ($request->has('estoque_baixo')) {
            $query->having('quantidade_total', '<', 5);
        }

        // Paginação
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);
        $perPage = min(max($perPage, 1), 100);

        $total = $query->count();
        $produtos = $query->orderBy('quantidade_total', 'asc')->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'success' => true,
            'data' => $produtos,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }

    public function show($id)
    {
        $produto = DB::table('produtos')->where('id_produto', $id)->first();

        if (!$produto) {
            return response()->json(['success' => false, 'message' => 'Produto não encontrado'], 404);
        }

        $estoque = DB::table('item_estoques')
            ->where('estoque_id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'produto' => $produto,
                'estoque' => $estoque
            ]
        ]);
    }

    public function registrarEntrada(Request $request)
    {
        $data = $request->all();

        if (empty($data['id_produto'])) {
            return response()->json(['success' => false, 'message' => 'Produto obrigatório'], 422);
        }

        DB::beginTransaction();
        try {
            $estoqueId = DB::table('estoques')->insertGetId([
                'ref_produto' => $data['id_produto'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            DB::table('item_estoques')->insert([
                'estoque_id' => $estoqueId,
                'tam_p' => $data['tam_p'] ?? 0,
                'tam_m' => $data['tam_m'] ?? 0,
                'tam_g' => $data['tam_g'] ?? 0,
                'tam_u' => $data['tam_u'] ?? 0,
                'tam_rn' => $data['tam_rn'] ?? 0,
                'ida_1' => $data['ida_1'] ?? 0,
                'ida_2' => $data['ida_2'] ?? 0,
                'ida_3' => $data['ida_3'] ?? 0,
                'ida_4' => $data['ida_4'] ?? 0,
                'ida_6' => $data['ida_6'] ?? 0,
                'ida_8' => $data['ida_8'] ?? 0,
                'estampa' => $data['estampa'] ?? 0,
                'estampa_lisa' => $data['estampa_lisa'] ?? 0,
                'lisa' => $data['lisa'] ?? 0,
                'tipo_entrada' => 'entrada',
                'observacao' => $data['observacao'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            DB::commit();

            AuditService::log($request, 'entrada', 'estoques', $estoqueId, null, $data);

            return response()->json([
                'success' => true,
                'message' => 'Entrada registrada com sucesso',
                'data' => ['id' => $estoqueId]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar entrada: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reservar(Request $request)
    {
        $data = $request->all();
        $vendedorId = $data['vendedor_id'] ?? null;

        if (!$vendedorId) {
            return response()->json(['success' => false, 'message' => 'Vendedor obrigatório'], 422);
        }

        $bloqueados = [];

        foreach ($data['itens'] as $item) {
            $produtoId = $item['id_produto'];
            $quantidade = $item['quantidade'] ?? 1;

            $estoqueAtual = DB::table('item_estoques')
                ->where('estoque_id', function ($q) use ($produtoId) {
                    $q->select('id')->from('estoques')->where('ref_produto', $produtoId)->limit(1);
                })
                ->whereNull('vendedor_id')
                ->first();

            if (!$estoqueAtual || ($estoqueAtual->tam_p + $estoqueAtual->tam_m + $estoqueAtual->tam_g) < $quantidade) {
                $produto = DB::table('produtos')->where('id_produto', $produtoId)->first();
                $bloqueados[] = $produto->produto ?? "Produto #{$produtoId}";
                continue;
            }

            DB::table('item_estoques')
                ->where('id', $estoqueAtual->id)
                ->update([
                    'vendedor_id' => $vendedorId,
                    'status_uso' => 1,
                    'data_bloqueio' => date('Y-m-d H:i:s')
                ]);
        }

        if (count($bloqueados) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Estoque insuficiente para: ' . implode(', ', $bloqueados),
                'bloqueados' => $bloqueados
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estoque reservado com sucesso'
        ]);
    }

    public function liberar(Request $request)
    {
        $data = $request->all();
        $vendedorId = $data['vendedor_id'] ?? null;

        if (!$vendedorId) {
            return response()->json(['success' => false, 'message' => 'Vendedor obrigatório'], 422);
        }

        DB::table('item_estoques')
            ->where('vendedor_id', $vendedorId)
            ->where('status_uso', 1)
            ->update([
                'vendedor_id' => null,
                'status_uso' => 0,
                'data_bloqueio' => null
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Reserva liberada'
        ]);
    }

    public function movimentacao(Request $request)
    {
        $query = DB::table('reg_estoques')
            ->select(
                'reg_estoques.*',
                'vendedores.nome_vendedor',
                'pedidos.id_pedido'
            )
            ->leftJoin('vendedores', 'reg_estoques.vendedor_id', '=', 'vendedores.id_vendedor')
            ->leftJoin('pedidos', 'reg_estoques.pedido_id', '=', 'pedidos.id_pedido');

        if ($request->has('data_inicio')) {
            $query->whereDate('reg_estoques.created_at', '>=', $request->data_inicio);
        }
        if ($request->has('data_fim')) {
            $query->whereDate('reg_estoques.created_at', '<=', $request->data_fim);
        }

        $movimentacoes = $query->orderBy('reg_estoques.created_at', 'desc')->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => $movimentacoes
        ]);
    }

    public function baixaEstoque($pedidoId)
    {
        $pedido = DB::table('pedidos')->where('id_pedido', $pedidoId)->first();
        if (!$pedido) return false;

        $itens = DB::table('itens_pedidos')->where('id_pedido', $pedidoId)->get();

        foreach ($itens as $item) {
            $estoque = DB::table('item_estoques')
                ->where('estoque_id', function ($q) use ($item) {
                    $q->select('id')->from('estoques')->where('ref_produto', $item->id_produto)->limit(1);
                })
                ->first();

            if ($estoque) {
                DB::table('item_estoques')->where('id', $estoque->id)->update([
                    'tam_p' => max(0, $estoque->tam_p - ($item->tam_p ?? 0)),
                    'tam_m' => max(0, $estoque->tam_m - ($item->tam_m ?? 0)),
                    'tam_g' => max(0, $estoque->tam_g - ($item->tam_g ?? 0)),
                    'tam_u' => max(0, $estoque->tam_u - ($item->tam_u ?? 0)),
                    'tam_rn' => max(0, $estoque->tam_rn - ($item->tam_rn ?? 0)),
                    'ida_1' => max(0, $estoque->ida_1 - ($item->ida_1 ?? 0)),
                    'ida_2' => max(0, $estoque->ida_2 - ($item->ida_2 ?? 0)),
                    'ida_3' => max(0, $estoque->ida_3 - ($item->ida_3 ?? 0)),
                    'ida_4' => max(0, $estoque->ida_4 - ($item->ida_4 ?? 0)),
                    'ida_6' => max(0, $estoque->ida_6 - ($item->ida_6 ?? 0)),
                    'ida_8' => max(0, $estoque->ida_8 - ($item->ida_8 ?? 0)),
                    'ida_10' => max(0, $estoque->ida_10 - ($item->ida_10 ?? 0)),
                    'ida_12' => max(0, $estoque->ida_12 - ($item->ida_12 ?? 0)),
                ]);

                DB::table('reg_estoques')->insert([
                    'vendedor_id' => $pedido->id_vendedor,
                    'item_estoque_id' => $estoque->id,
                    'estoque_id' => $estoque->estoque_id,
                    'pedido_id' => $pedidoId,
                    'descricao' => 'Baixa por venda - Pedido #' . $pedidoId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return true;
    }

    public function registrarSaida(Request $request)
    {
        $data = $request->all();
        $vendedorId = $data['vendedor_id'] ?? null;

        if (empty($data['id_produto'])) {
            return response()->json(['success' => false, 'message' => 'Produto obrigatório'], 422);
        }

        $produtoId = $data['id_produto'];
        $tiposSaida = ['venda', 'devolucao', 'perda', 'ajuste', 'transferencia'];
        $tipo = $data['tipo'] ?? 'ajuste';

        if (!in_array($tipo, $tiposSaida)) {
            return response()->json([
                'success' => false,
                'message' => 'Ops! O tipo de saída deve ser: ' . implode(', ', $tiposSaida)
            ], 422);
        }

        // Verificar se o estoque já está reservado por outro vendedor
        $vendedorReservado = DB::table('item_estoques')
            ->where('estoque_id', function ($q) use ($produtoId) {
                $q->select('id')->from('estoques')->where('ref_produto', $produtoId)->limit(1);
            })
            ->whereNotNull('vendedor_id')
            ->where('vendedor_id', '!=', $vendedorId)
            ->first();

        if ($vendedorReservado) {
            return response()->json([
                'success' => false,
                'message' => 'Este produto está reservado para venda por outro vendedor.'
            ], 409);
        }

        $estoqueAtual = DB::table('item_estoques')
            ->where('estoque_id', function ($q) use ($produtoId) {
                $q->select('id')->from('estoques')->where('ref_produto', $produtoId)->limit(1);
            })
            ->first();

        if (!$estoqueAtual) {
            return response()->json(['success' => false, 'message' => 'Estoque não encontrado para este produto'], 404);
        }

        $saidaTotal = ($data['tam_p'] ?? 0) + ($data['tam_m'] ?? 0) +
                      ($data['tam_g'] ?? 0) + ($data['tam_u'] ?? 0) + ($data['tam_rn'] ?? 0) +
                      ($data['ida_1'] ?? 0) + ($data['ida_2'] ?? 0) + ($data['ida_3'] ?? 0) +
                      ($data['ida_4'] ?? 0) + ($data['ida_6'] ?? 0) + ($data['ida_8'] ?? 0) +
                      ($data['ida_10'] ?? 0) + ($data['ida_12'] ?? 0);

        if ($saidaTotal <= 0) {
            return response()->json(['success' => false, 'message' => 'Quantidade de saída deve ser maior que zero'], 422);
        }

        $estoqueAtualTotal = $estoqueAtual->tam_p + $estoqueAtual->tam_m +
                            $estoqueAtual->tam_g + $estoqueAtual->tam_u + $estoqueAtual->tam_rn +
                            $estoqueAtual->ida_1 + $estoqueAtual->ida_2 + $estoqueAtual->ida_3 +
                            $estoqueAtual->ida_4 + $estoqueAtual->ida_6 + $estoqueAtual->ida_8 +
                            ($estoqueAtual->ida_10 ?? 0) + ($estoqueAtual->ida_12 ?? 0);

        if ($saidaTotal > $estoqueAtualTotal) {
            return response()->json([
                'success' => false, 
                'message' => "Estoque insuficiente. Disponível: {$estoqueAtualTotal}, Solicitado: {$saidaTotal}"
            ], 422);
        }

        $novoEstoque = [
            'tam_p' => max(0, $estoqueAtual->tam_p - ($data['tam_p'] ?? 0)),
            'tam_m' => max(0, $estoqueAtual->tam_m - ($data['tam_m'] ?? 0)),
            'tam_g' => max(0, $estoqueAtual->tam_g - ($data['tam_g'] ?? 0)),
            'tam_u' => max(0, $estoqueAtual->tam_u - ($data['tam_u'] ?? 0)),
            'tam_rn' => max(0, $estoqueAtual->tam_rn - ($data['tam_rn'] ?? 0)),
            'ida_1' => max(0, $estoqueAtual->ida_1 - ($data['ida_1'] ?? 0)),
            'ida_2' => max(0, $estoqueAtual->ida_2 - ($data['ida_2'] ?? 0)),
            'ida_3' => max(0, $estoqueAtual->ida_3 - ($data['ida_3'] ?? 0)),
            'ida_4' => max(0, $estoqueAtual->ida_4 - ($data['ida_4'] ?? 0)),
            'ida_6' => max(0, $estoqueAtual->ida_6 - ($data['ida_6'] ?? 0)),
            'ida_8' => max(0, $estoqueAtual->ida_8 - ($data['ida_8'] ?? 0)),
            'ida_10' => max(0, $estoqueAtual->ida_10 - ($data['ida_10'] ?? 0)),
            'ida_12' => max(0, $estoqueAtual->ida_12 - ($data['ida_12'] ?? 0)),
        ];

        DB::beginTransaction();
        try {
            DB::table('item_estoques')->where('id', $estoqueAtual->id)->update($novoEstoque);

            $descricoes = [
                'venda' => 'Saída por venda',
                'devolucao' => 'Devolução de cliente',
                'perda' => 'Perda/Breakage',
                'ajuste' => 'Ajuste de estoque',
                'transferencia' => 'Transferência'
            ];

            DB::table('reg_estoques')->insert([
                'item_estoque_id' => $estoqueAtual->id,
                'estoque_id' => $estoqueAtual->estoque_id,
                'descricao' => $descricoes[$tipo] . ($data['observacao'] ? ' - ' . $data['observacao'] : ''),
                'tipo_movimentacao' => 'saida',
                'quantidade' => $saidaTotal,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            DB::commit();

            AuditService::log($request, 'saida', 'estoques', $estoqueAtual->id, (array) $estoqueAtual, $data);

            return response()->json([
                'success' => true,
                'message' => 'Saída de estoque registrada com sucesso',
                'data' => [
                    'estoque_anterior' => $estoqueAtualTotal,
                    'saida' => $saidaTotal,
                    'estoque_atual' => $estoqueAtualTotal - $saidaTotal
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar saída de estoque: ' . $e->getMessage()
            ], 500);
        }
    }

    public function retornoEstoque($pedidoId)
    {
        $pedido = DB::table('pedidos')->where('id_pedido', $pedidoId)->first();
        if (!$pedido) return false;

        $itens = DB::table('itens_pedidos')->where('id_pedido', $pedidoId)->get();

        foreach ($itens as $item) {
            $estoque = DB::table('item_estoques')
                ->where('estoque_id', function ($q) use ($item) {
                    $q->select('id')->from('estoques')->where('ref_produto', $item->id_produto)->limit(1);
                })
                ->first();

            if ($estoque) {
                DB::table('item_estoques')->where('id', $estoque->id)->update([
                    'tam_p' => $estoque->tam_p + ($item->tam_p ?? 0),
                    'tam_m' => $estoque->tam_m + ($item->tam_m ?? 0),
                    'tam_g' => $estoque->tam_g + ($item->tam_g ?? 0),
                    'tam_u' => $estoque->tam_u + ($item->tam_u ?? 0),
                    'tam_rn' => $estoque->tam_rn + ($item->tam_rn ?? 0),
                    'ida_1' => $estoque->ida_1 + ($item->ida_1 ?? 0),
                    'ida_2' => $estoque->ida_2 + ($item->ida_2 ?? 0),
                    'ida_3' => $estoque->ida_3 + ($item->ida_3 ?? 0),
                    'ida_4' => $estoque->ida_4 + ($item->ida_4 ?? 0),
                    'ida_6' => $estoque->ida_6 + ($item->ida_6 ?? 0),
                    'ida_8' => $estoque->ida_8 + ($item->ida_8 ?? 0),
                    'ida_10' => $estoque->ida_10 + ($item->ida_10 ?? 0),
                    'ida_12' => $estoque->ida_12 + ($item->ida_12 ?? 0),
                ]);

                DB::table('reg_estoques')->insert([
                    'vendedor_id' => $pedido->id_vendedor,
                    'item_estoque_id' => $estoque->id,
                    'estoque_id' => $estoque->estoque_id,
                    'pedido_id' => $pedidoId,
                    'descricao' => 'Retorno por cancelamento - Pedido #' . $pedidoId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return true;
    }

    public function estoqueBaixo(Request $request)
    {
        $limite = $request->get('limite', 10);
        
        $produtos = DB::table('produtos')
            ->select(
                'produtos.id_produto',
                'produtos.produto',
                'produtos.referencia',
                DB::raw('COALESCE(SUM(item_estoques.tam_p + item_estoques.tam_m + item_estoques.tam_g + item_estoques.tam_u + item_estoques.tam_rn + item_estoques.ida_1 + item_estoques.ida_2 + item_estoques.ida_3 + item_estoques.ida_4 + item_estoques.ida_6 + item_estoques.ida_8 + item_estoques.ida_10 + item_estoques.ida_12), 0) as quantidade_total')
            )
            ->leftJoin('item_estoques', 'produtos.id_produto', '=', 'item_estoques.estoque_id')
            ->groupBy('produtos.id_produto')
            ->having('quantidade_total', '<=', $limite)
            ->orderBy('quantidade_total', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $produtos,
            'limite' => $limite
        ]);
    }

    public function resumoEstoque()
    {
        $totalProdutos = DB::table('produtos')->count();
        
        $estoqueTotal = DB::table('item_estoques')
            ->select(DB::raw('COALESCE(SUM(tam_p + tam_m + tam_g + tam_u + tam_rn + ida_1 + ida_2 + ida_3 + ida_4 + ida_6 + ida_8 + ida_10 + ida_12), 0) as total'))
            ->first();

        $produtosBaixos = DB::table('produtos')
            ->select(
                DB::raw('COUNT(*) as quantidade'),
                DB::raw('COALESCE(SUM(item_estoques.tam_p + item_estoques.tam_m + item_estoques.tam_g + item_estoques.tam_u + item_estoques.tam_rn + item_estoques.ida_1 + item_estoques.ida_2 + item_estoques.ida_3 + item_estoques.ida_4 + item_estoques.ida_6 + item_estoques.ida_8), 0) as total')
            )
            ->leftJoin('item_estoques', 'produtos.id_produto', '=', 'item_estoques.estoque_id')
            ->groupBy('produtos.id_produto')
            ->having('total', '<=', 10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_produtos' => $totalProdutos,
                'total_estoque' => (int) $estoqueTotal->total,
                'produtos_baixos' => count($produtosBaixos),
                'itens_estoque_baixo' => $produtosBaixos->sum('total')
            ]
        ]);
    }
}