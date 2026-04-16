<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('produtos');
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 1);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('produto', 'like', "%{$search}%")
                  ->orWhere('referencia', 'like', "%{$search}%");
            });
        }

        if ($request->has('preco_min') && $request->preco_min) {
            $query->where('valor_unt_norde', '>=', $request->preco_min);
        }
        if ($request->has('preco_max') && $request->preco_max) {
            $query->where('valor_unt_norde', '<=', $request->preco_max);
        }

        // Ordenação — whitelist para evitar SQL injection
        $allowedOrderBy = ['produto', 'referencia', 'valor_unt_norde', 'valor_unt_norte', 'status'];
        $orderBy = $request->get('order_by', 'produto');
        $orderBy = in_array($orderBy, $allowedOrderBy, true) ? $orderBy : 'produto';

        $allowedDir = ['asc', 'desc'];
        $orderDir = in_array(strtolower($request->get('order_dir', 'asc')), $allowedDir, true) ? strtolower($request->get('order_dir', 'asc')) : 'asc';

        $query->orderBy($orderBy, $orderDir);

        // Paginação
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $total = $query->count();
        $produtos = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'success' => true,
            'data' => $produtos,
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
        $produto = DB::table('produtos')->where('id_produto', $id)->first();

        if (!$produto) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $produto
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        
        if (empty($data['referencia']) || empty($data['produto'])) {
            return response()->json([
                'success' => false,
                'message' => 'Referência e produto são obrigatórios'
            ], 422);
        }

        $id = DB::table('produtos')->insertGetId([
            'referencia' => $data['referencia'],
            'produto' => $data['produto'],
            'valor_unt_norde' => $data['valor_unt_norde'] ?? 0,
            'valor_unt_norte' => $data['valor_unt_norte'] ?? 0,
            'status' => $data['status'] ?? 1
        ]);

        $produto = DB::table('produtos')->where('id_produto', $id)->first();

        AuditService::log($request, 'create', 'produtos', $id, null, $data);

        return response()->json([
            'success' => true,
            'message' => 'Produto criado com sucesso',
            'data' => $produto
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $produto = DB::table('produtos')->where('id_produto', $id)->first();
        
        if (!$produto) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado'
            ], 404);
        }

        $data = $request->all();
        
        $updateFields = [];
        $allowedFields = ['referencia', 'produto', 'valor_unt_norde', 'valor_unt_norte', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[$field] = $data[$field];
            }
        }
        
        if (!empty($updateFields)) {
            DB::table('produtos')->where('id_produto', $id)->update($updateFields);
        }

        $produto = DB::table('produtos')->where('id_produto', $id)->first();

        AuditService::log($request, 'update', 'produtos', $id, (array) $produto, $updateFields);

        return response()->json([
            'success' => true,
            'message' => 'Produto atualizado com sucesso',
            'data' => $produto
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $produto = DB::table('produtos')->where('id_produto', $id)->first();
        
        if (!$produto) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado'
            ], 404);
        }

        DB::table('produtos')->where('id_produto', $id)->delete();

        AuditService::log($request, 'delete', 'produtos', $id, (array) $produto);

        return response()->json([
            'success' => true,
            'message' => 'Produto excluído com sucesso'
        ]);
    }
}