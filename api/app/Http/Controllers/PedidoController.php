<?php

namespace App\Http\Controllers;

use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    private PedidoService $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    public function index(Request $request)
    {
        $user = $request->attributes->get('user');
        $isAdmin = ($user->data->nivel ?? '') === 'admin';

        $query = DB::table('pedidos')
            ->select('pedidos.*', 'clientes.razao_social', 'vendedores.nome_vendedor')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor');

        if (!$isAdmin) {
            $query->where('pedidos.id_vendedor', $user->data->id);
        }

        // Filtros avançados
        if ($request->has('status') && $request->status) {
            $query->where('pedidos.status', $request->status);
        }

        if ($request->has('id_cliente') && $request->id_cliente) {
            $query->where('pedidos.id_cliente', $request->id_cliente);
        }

        if ($request->has('id_vendedor') && $request->id_vendedor) {
            $query->where('pedidos.id_vendedor', $request->id_vendedor);
        }

        if ($request->has('data_inicio') && $request->data_inicio) {
            $query->whereDate('pedidos.data_pedido', '>=', $request->data_inicio);
        }

        if ($request->has('data_fim') && $request->data_fim) {
            $query->whereDate('pedidos.data_pedido', '<=', $request->data_fim);
        }

        // Busca por texto
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('clientes.razao_social', 'like', "%{$search}%")
                  ->orWhere('vendedores.nome_vendedor', 'like', "%{$search}%")
                  ->orWhere('pedidos.id_pedido', 'like', "%{$search}%");
            });
        }

        // Filtro por total
        if ($request->has('min_total') && $request->min_total) {
            $query->where('pedidos.total_pedido', '>=', $request->min_total);
        }
        if ($request->has('max_total') && $request->max_total) {
            $query->where('pedidos.total_pedido', '<=', $request->max_total);
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'data_pedido');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy("pedidos.{$orderBy}", $orderDir);

        // Paginação
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100); // Limitar entre 1 e 100

        $total = $query->count();
        $pedidos = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'success' => true,
            'data' => $pedidos,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total)
            ]
        ]);
    }

    public function show($id)
    {
        $pedido = DB::table('pedidos')
            ->select('pedidos.*', 'clientes.razao_social', 'vendedores.nome_vendedor')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->where('pedidos.id_pedido', $id)
            ->first();

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado'
            ], 404);
        }

        $itens = DB::table('itens_pedidos')
            ->select('itens_pedidos.*', 'produtos.produto', 'produtos.referencia')
            ->leftJoin('produtos', 'itens_pedidos.id_produto', '=', 'produtos.id_produto')
            ->where('itens_pedidos.id_pedido', $id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                ...(array) $pedido,
                'itens' => $itens
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        if (empty($data['id_cliente']) || empty($data['id_vendedor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente e vendedor são obrigatórios'
            ], 422);
        }

        if (empty($data['itens']) || !is_array($data['itens'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido deve ter pelo menos um item'
            ], 422);
        }

        $pedido = $this->pedidoService->criarPedido($data);

        $alertaId = DB::table('alertas')->insertGetId([
            'tipo' => 'pedido_novo',
            'titulo' => 'Novo Pedido #' . $pedido['id_pedido'],
            'mensagem' => 'Novo pedido criado',
            'referencia_id' => $pedido['id_pedido'],
            'lido' => 0,
            'data_alerta' => date('Y-m-d H:i:s')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pedido criado com sucesso',
            'data' => $pedido
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $pedido = DB::table('pedidos')->where('id_pedido', $id)->first();

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado'
            ], 404);
        }

        $data = $request->all();
        
        $updateFields = [];
        
        if (isset($data['obs_pedido'])) $updateFields['obs_pedido'] = $data['obs_pedido'];
        if (isset($data['obs_entrega'])) $updateFields['obs_entrega'] = $data['obs_entrega'];
        if (isset($data['data_entrega'])) $updateFields['data_entrega'] = $data['data_entrega'];
        if (isset($data['forma_pag'])) $updateFields['forma_pag'] = $data['forma_pag'];
        
        if (!empty($updateFields)) {
            DB::table('pedidos')->where('id_pedido', $id)->update($updateFields);
        }

        if (isset($data['desconto_percentual']) || isset($data['desconto_valor'])) {
            $this->pedidoService->aplicarDescontoPedido(
                $id, 
                $data['desconto_percentual'] ?? 0, 
                $data['desconto_valor'] ?? 0
            );
        }

        $pedido = DB::table('pedidos')
            ->select('pedidos.*', 'clientes.razao_social', 'vendedores.nome_vendedor')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->where('pedidos.id_pedido', $id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Pedido atualizado com sucesso',
            'data' => $pedido
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->all();
        
        $status = $data['status'] ?? null;
        
        if (!$status || !in_array($status, [1, 2, 3, 4])) {
            return response()->json([
                'success' => false,
                'message' => 'Status inválido. Use: 1=Aberto, 2=Pendente, 3=Cancelado, 4=Concluído'
            ], 422);
        }

        $pedido = $this->pedidoService->atualizarStatus(
            $id, 
            $status, 
            $data['obs_cancelamento'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Status atualizado com sucesso',
            'data' => $pedido
        ]);
    }

    public function destroy($id)
    {
        $pedido = DB::table('pedidos')->where('id_pedido', $id)->first();
        
        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado'
            ], 404);
        }
        
        DB::table('itens_pedidos')->where('id_pedido', $id)->delete();
        DB::table('pedidos')->where('id_pedido', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pedido excluído com sucesso'
        ]);
    }

    public function calculo($id)
    {
        $calculo = $this->pedidoService->getDetalheCalculo($id);

        return response()->json([
            'success' => true,
            'data' => $calculo
        ]);
    }

    public function etiqueta($id)
    {
        $pedido = DB::table('pedidos')
            ->select('pedidos.*', 'clientes.*')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->where('pedidos.id_pedido', $id)
            ->first();

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado'
            ], 404);
        }

        $remetente = [
            'nome' => 'CLAYTON A. FREIRE CONFECÇÃO - ME',
            'cnpj' => '11.233.562/0001-00',
            'endereco' => 'RUA ANTONIO SABINO, 068',
            'bairro' => 'SÃO BENEDITO',
            'cidade' => 'SÃO BENEDITO',
            'estado' => 'CE',
            'cep' => '62370-000',
            'marca' => 'LIZZIE - AMOR DE MÃE'
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'pedido_id' => $pedido->id_pedido,
                'remetente' => $remetente,
                'destinatario' => [
                    'responsavel' => $pedido->responsavel ?? '',
                    'nome_fantasia' => $pedido->nome_fantasia ?? '',
                    'razao_social' => $pedido->razao_social ?? '',
                    'pessoa' => $pedido->pessoa ?? 1,
                    'cpf_cnpj' => $pedido->cpf_cnpj ?? '',
                    'endereco' => $pedido->endereco ?? '',
                    'bairro' => $pedido->bairro ?? '',
                    'cidade' => $pedido->cidade ?? '',
                    'estado' => $pedido->estado ?? '',
                    'cep' => $pedido->cep ?? '',
                    'contato' => $pedido->contato ?? ''
                ]
            ]
        ]);
    }
}