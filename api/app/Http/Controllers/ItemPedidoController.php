<?php

namespace App\Http\Controllers;

use App\Models\ItemPedido;
use App\Models\Pedido;
use Illuminate\Http\Request;

class ItemPedidoController extends Controller
{
    public function index($pedidoId)
    {
        $itens = ItemPedido::where('id_pedido', $pedidoId)->with('produto')->get();

        return response()->json([
            'success' => true,
            'data' => $itens
        ]);
    }

    public function store(Request $request, $pedidoId)
    {
        $pedido = Pedido::findOrFail($pedidoId);

        $validated = $request->validate([
            'id_produto' => 'required|integer|exists:produtos,id_produto',
            'quantidade' => 'required|integer|min:1'
        ]);

        $produto = \App\Models\Produto::findOrFail($validated['id_produto']);
        $subtotal = $validated['quantidade'] * $produto->preco_venda;

        $item = ItemPedido::create([
            'id_pedido' => $pedidoId,
            'id_produto' => $validated['id_produto'],
            'quantidade' => $validated['quantidade'],
            'preco_unitario' => $produto->preco_venda,
            'subtotal' => $subtotal
        ]);

        $this->atualizarTotalPedido($pedidoId);

        return response()->json([
            'success' => true,
            'message' => 'Item adicionado ao pedido',
            'data' => $item->load('produto')
        ], 201);
    }

    public function destroy($id)
    {
        $item = ItemPedido::findOrFail($id);
        $pedidoId = $item->id_pedido;
        $item->delete();

        $this->atualizarTotalPedido($pedidoId);

        return response()->json([
            'success' => true,
            'message' => 'Item removido do pedido'
        ]);
    }

    private function atualizarTotalPedido($pedidoId)
    {
        $total = ItemPedido::where('id_pedido', $pedidoId)->sum('subtotal');
        Pedido::where('id_pedido', $pedidoId)->update(['total_pedido' => $total]);
    }
}