<?php

namespace App\Http\Controllers;

use App\Models\ItemPedido;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemPedidoController extends Controller
{
    private PedidoService $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    public function index($pedidoId)
    {
        $itens = DB::table('itens_pedidos')
            ->select('itens_pedidos.*', 'produtos.produto', 'produtos.referencia')
            ->leftJoin('produtos', 'itens_pedidos.id_produto', '=', 'produtos.id_produto')
            ->where('itens_pedidos.id_pedido', $pedidoId)
            ->orderBy('itens_pedidos.id_item_pedido', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $itens
        ]);
    }

    public function store(Request $request, $pedidoId)
    {
        $validated = $request->validate([
            'id_produto' => 'required|integer|exists:produtos,id_produto',
            'regiao' => 'nullable|string|in:norte,nordeste',
            'quantidades' => 'required|array',
            'quantidades.pp' => 'nullable|integer|min:0',
            'quantidades.p' => 'nullable|integer|min:0',
            'quantidades.m' => 'nullable|integer|min:0',
            'quantidades.g' => 'nullable|integer|min:0',
            'quantidades.u' => 'nullable|integer|min:0',
            'quantidades.rn' => 'nullable|integer|min:0',
            'quantidades.ida_1' => 'nullable|integer|min:0',
            'quantidades.ida_2' => 'nullable|integer|min:0',
            'quantidades.ida_3' => 'nullable|integer|min:0',
            'quantidades.ida_4' => 'nullable|integer|min:0',
            'quantidades.ida_6' => 'nullable|integer|min:0',
            'quantidades.ida_8' => 'nullable|integer|min:0',
            'quantidades.ida_10' => 'nullable|integer|min:0',
            'quantidades.ida_12' => 'nullable|integer|min:0',
            'quantidades.lisa' => 'nullable|integer|min:0',
            'desconto_percentual' => 'nullable|numeric|min:0',
            'desconto_valor' => 'nullable|numeric|min:0',
            'sexo' => 'nullable|string|in:M,F,U',
            'tem_estampa' => 'nullable|boolean',
            'tem_estampa_lisa' => 'nullable|boolean',
            'id_subcategoria' => 'nullable|integer|min:0',
        ]);

        $quantidades = $validated['quantidades'] ?? [];
        $soma = 0;
        foreach ($quantidades as $qtd) {
            $soma += (int) $qtd;
        }

        if ($soma <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Informe ao menos 1 unidade em algum tamanho'
            ], 422);
        }

        $item = $this->pedidoService->adicionarItem((int) $pedidoId, [
            'id_produto' => (int) $validated['id_produto'],
            'regiao' => $validated['regiao'] ?? 'nordeste',
            'quantidades' => $quantidades,
            'desconto_percentual' => (float) ($validated['desconto_percentual'] ?? 0),
            'desconto_valor' => (float) ($validated['desconto_valor'] ?? 0),
            'sexo' => $validated['sexo'] ?? 'U',
            'tem_estampa' => (bool) ($validated['tem_estampa'] ?? false),
            'tem_estampa_lisa' => (bool) ($validated['tem_estampa_lisa'] ?? false),
            'id_subcategoria' => (int) ($validated['id_subcategoria'] ?? 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item adicionado ao pedido',
            'data' => $item
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'regiao' => 'nullable|string|in:norte,nordeste',
            'quantidades' => 'required|array',
            'quantidades.pp' => 'nullable|integer|min:0',
            'quantidades.p' => 'nullable|integer|min:0',
            'quantidades.m' => 'nullable|integer|min:0',
            'quantidades.g' => 'nullable|integer|min:0',
            'quantidades.u' => 'nullable|integer|min:0',
            'quantidades.rn' => 'nullable|integer|min:0',
            'quantidades.ida_1' => 'nullable|integer|min:0',
            'quantidades.ida_2' => 'nullable|integer|min:0',
            'quantidades.ida_3' => 'nullable|integer|min:0',
            'quantidades.ida_4' => 'nullable|integer|min:0',
            'quantidades.ida_6' => 'nullable|integer|min:0',
            'quantidades.ida_8' => 'nullable|integer|min:0',
            'quantidades.ida_10' => 'nullable|integer|min:0',
            'quantidades.ida_12' => 'nullable|integer|min:0',
            'quantidades.lisa' => 'nullable|integer|min:0',
            'desconto_percentual' => 'nullable|numeric|min:0',
            'desconto_valor' => 'nullable|numeric|min:0',
            'sexo' => 'nullable|string|in:M,F,U',
            'tem_estampa' => 'nullable|boolean',
            'tem_estampa_lisa' => 'nullable|boolean',
            'id_subcategoria' => 'nullable|integer|min:0',
        ]);

        $quantidades = $validated['quantidades'] ?? [];
        $soma = 0;
        foreach ($quantidades as $qtd) {
            $soma += (int) $qtd;
        }

        if ($soma <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Informe ao menos 1 unidade em algum tamanho'
            ], 422);
        }

        $item = $this->pedidoService->atualizarItem((int) $id, [
            'regiao' => $validated['regiao'] ?? 'nordeste',
            'quantidades' => $quantidades,
            'desconto_percentual' => (float) ($validated['desconto_percentual'] ?? 0),
            'desconto_valor' => (float) ($validated['desconto_valor'] ?? 0),
            'sexo' => $validated['sexo'] ?? 'U',
            'tem_estampa' => (bool) ($validated['tem_estampa'] ?? false),
            'tem_estampa_lisa' => (bool) ($validated['tem_estampa_lisa'] ?? false),
            'id_subcategoria' => (int) ($validated['id_subcategoria'] ?? 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item atualizado com sucesso',
            'data' => $item
        ]);
    }

    public function destroy($id)
    {
        $this->pedidoService->removerItem((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Item removido do pedido'
        ]);
    }
}
