<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuscaController extends Controller
{
    public function index(Request $request)
    {
        $termo = $request->get('q', '');
        
        if (strlen($termo) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Termo de busca deve ter pelo menos 2 caracteres'
            ], 422);
        }

        $resultados = [
            'clientes' => $this->buscarClientes($termo),
            'produtos' => $this->buscarProdutos($termo),
            'pedidos' => $this->buscarPedidos($termo),
            'vendedores' => $this->buscarVendedores($termo)
        ];

        return response()->json([
            'success' => true,
            'data' => $resultados,
            'total' => count($resultados['clientes']) + count($resultados['produtos']) + count($resultados['pedidos']) + count($resultados['vendedores'])
        ]);
    }

    private function buscarClientes(string $termo)
    {
        return DB::table('clientes')
            ->select('id_cliente', 'razao_social', 'nome_fantasia', 'cpf_cnpj', 'telefone', 'cidade', 'estado')
            ->where('status', 1)
            ->where(function ($q) use ($termo) {
                $q->where('razao_social', 'like', "%{$termo}%")
                  ->orWhere('nome_fantasia', 'like', "%{$termo}%")
                  ->orWhere('cpf_cnpj', 'like', "%{$termo}%")
                  ->orWhere('telefone', 'like', "%{$termo}%");
            })
            ->limit(10)
            ->get();
    }

    private function buscarProdutos(string $termo)
    {
        return DB::table('produtos')
            ->select('id_produto', 'produto', 'referencia', 'valor_unt_norde', 'valor_unt')
            ->where('status', 1)
            ->where(function ($q) use ($termo) {
                $q->where('produto', 'like', "%{$termo}%")
                  ->orWhere('referencia', 'like', "%{$termo}%");
            })
            ->limit(10)
            ->get();
    }

    private function buscarPedidos(string $termo)
    {
        return DB::table('pedidos')
            ->select('pedidos.id_pedido', 'pedidos.data_pedido', 'pedidos.total_pedido', 'pedidos.status', 'clientes.razao_social', 'vendedores.nome_vendedor')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->where(function ($q) use ($termo) {
                $q->where('pedidos.id_pedido', 'like', "%{$termo}%")
                  ->orWhere('clientes.razao_social', 'like', "%{$termo}%")
                  ->orWhere('vendedores.nome_vendedor', 'like', "%{$termo}%");
            })
            ->orderBy('pedidos.data_pedido', 'desc')
            ->limit(10)
            ->get();
    }

    private function buscarVendedores(string $termo)
    {
        return DB::table('vendedores')
            ->select('id_vendedor', 'nome_vendedor', 'usuario', 'telefone')
            ->where('status', 1)
            ->where(function ($q) use ($termo) {
                $q->where('nome_vendedor', 'like', "%{$termo}%")
                  ->orWhere('usuario', 'like', "%{$termo}%");
            })
            ->limit(10)
            ->get();
    }
}