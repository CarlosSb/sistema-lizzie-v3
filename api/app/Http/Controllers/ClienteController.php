<?php

namespace App\Http\Controllers;

use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('user');
        $isAdmin = ($user->data->nivel ?? '') === 'admin';
        
        $search = $request->get('search', '');
        $status = $request->get('status', 1);
        $page = $request->get('page', 1);
        
        $cacheKey = "clientes:list:{$search}:{$status}:{$page}";
        
        if (!$search && $page == 1) {
            return CacheService::get($cacheKey, function() use ($request) {
                return $this->getClientesData($request);
            }, 180);
        }
        
        return $this->getClientesData($request);
    }
    
    private function getClientesData(Request $request)
    {
        $query = DB::table('clientes');
        
        // Filtros avançados
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 1);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('razao_social', 'like', "%{$search}%")
                  ->orWhere('nome_fantasia', 'like', "%{$search}%")
                  ->orWhere('cpf_cnpj', 'like', "%{$search}%")
                  ->orWhere('responsavel', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('cidade') && $request->cidade) {
            $query->where('cidade', 'like', "%{$request->cidade}%");
        }
        
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->has('pessoa') && $request->pessoa) {
            $query->where('pessoa', $request->pessoa);
        }
        
        if ($request->has('rota') && $request->rota) {
            $query->where('rota', 'like', "%{$request->rota}%");
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'razao_social');
        $orderDir = $request->get('order_dir', 'asc');
        $query->orderBy($orderBy, $orderDir);

        // Paginação
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $total = $query->count();
        $clientes = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return [
            'success' => true,
            'data' => $clientes,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total)
            ]
        ];
    }

    public function show($id)
    {
        $cliente = DB::table('clientes')->where('id_cliente', $id)->first();

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cliente
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        
        if (empty($data['razao_social'])) {
            return response()->json([
                'success' => false,
                'message' => 'Razão social é obrigatória'
            ], 422);
        }

        $id = DB::table('clientes')->insertGetId([
            'razao_social' => $data['razao_social'] ?? null,
            'nome_fantasia' => $data['nome_fantasia'] ?? null,
            'responsavel' => $data['responsavel'] ?? null,
            'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
            'inscricao_estadual' => $data['inscricao_estadual'] ?? null,
            'pessoa' => $data['pessoa'] ?? 2,
            'email' => $data['email'] ?? null,
            'endereco' => $data['endereco'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade' => $data['cidade'] ?? null,
            'estado' => $data['estado'] ?? null,
            'cep' => $data['cep'] ?? null,
            'contato_1' => $data['contato_1'] ?? null,
            'contato_2' => $data['contato_2'] ?? null,
            'contato_3' => $data['contato_3'] ?? null,
            'rota' => $data['rota'] ?? null,
            'status' => $data['status'] ?? 1
        ]);

        $cliente = DB::table('clientes')->where('id_cliente', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Cliente criado com sucesso',
            'data' => $cliente
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $cliente = DB::table('clientes')->where('id_cliente', $id)->first();
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado'
            ], 404);
        }

        $data = $request->all();
        
        $updateFields = [];
        $allowedFields = [
            'razao_social', 'nome_fantasia', 'responsavel', 'cpf_cnpj',
            'inscricao_estadual', 'pessoa', 'email', 'endereco',
            'bairro', 'cidade', 'estado', 'cep',
            'contato_1', 'contato_2', 'contato_3', 'rota', 'status'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[$field] = $data[$field];
            }
        }
        
        if (!empty($updateFields)) {
            DB::table('clientes')->where('id_cliente', $id)->update($updateFields);
        }

        $cliente = DB::table('clientes')->where('id_cliente', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Cliente atualizado com sucesso',
            'data' => $cliente
        ]);
    }

    public function destroy($id)
    {
        $cliente = DB::table('clientes')->where('id_cliente', $id)->first();
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado'
            ], 404);
        }

        DB::table('clientes')->where('id_cliente', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente excluído com sucesso'
        ]);
    }
}