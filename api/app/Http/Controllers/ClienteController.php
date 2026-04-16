<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    /**
     * Validate CPF (Brazilian individual taxpayer registry).
     * Checks format and checksum digits.
     */
    private function validarCpf(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1+$/', $cpf)) {
            return false;
        }
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int) $cpf[$i] * (10 - $i);
        }
        $resto = ($soma * 10) % 11;
        if ($resto === 10) $resto = 0;
        if ($resto !== (int) $cpf[9]) return false;
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int) $cpf[$i] * (11 - $i);
        }
        $resto = ($soma * 10) % 11;
        if ($resto === 10) $resto = 0;
        return $resto === (int) $cpf[10];
    }

    /**
     * Validate CNPJ (Brazilian company taxpayer registry).
     * Checks format and checksum digits.
     */
    private function validarCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }
        $pesos = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += (int) $cnpj[$i] * $pesos[$i];
        }
        $resto = ($soma * 10) % 11;
        if ($resto === 10) $resto = 0;
        if ($resto !== (int) $cnpj[12]) return false;
        $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += (int) $cnpj[$i] * $pesos[$i];
        }
        $resto = ($soma * 10) % 11;
        if ($resto === 10) $resto = 0;
        return $resto === (int) $cnpj[13];
    }

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

        // Ordenação — whitelist para evitar SQL injection
        $allowedOrderBy = ['razao_social', 'nome_fantasia', 'cpf_cnpj', 'cidade', 'estado', 'rota', 'status'];
        $orderBy = $request->get('order_by', 'razao_social');
        $orderBy = in_array($orderBy, $allowedOrderBy, true) ? $orderBy : 'razao_social';

        $allowedDir = ['asc', 'desc'];
        $orderDir = in_array(strtolower($request->get('order_dir', 'asc')), $allowedDir, true) ? strtolower($request->get('order_dir', 'asc')) : 'asc';

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

        // Validate CPF/CNPJ if provided
        if (!empty($data['cpf_cnpj'])) {
            $cpfCnpj = preg_replace('/[^0-9]/', '', $data['cpf_cnpj']);
            if (strlen($cpfCnpj) === 11 && !$this->validarCpf($cpfCnpj)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF inválido'
                ], 422);
            }
            if (strlen($cpfCnpj) === 14 && !$this->validarCnpj($cpfCnpj)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CNPJ inválido'
                ], 422);
            }
        }

        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'E-mail inválido'
            ], 422);
        }

        // Validate phone format if provided (allow numbers, spaces, parens, dash, plus)
        foreach (['contato_1', 'contato_2', 'contato_3'] as $campo) {
            if (!empty($data[$campo]) && !preg_match('/^[\d\s()+\-]+$/', $data[$campo])) {
                return response()->json([
                    'success' => false,
                    'message' => "Formato de telefone inválido no campo {$campo}"
                ], 422);
            }
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

        AuditService::log($request, 'create', 'clientes', $id, null, $data);

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

        // Validate CPF/CNPJ if being changed
        if (isset($data['cpf_cnpj']) && !empty($data['cpf_cnpj'])) {
            $cpfCnpj = preg_replace('/[^0-9]/', '', $data['cpf_cnpj']);
            if (strlen($cpfCnpj) === 11 && !$this->validarCpf($cpfCnpj)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF inválido'
                ], 422);
            }
            if (strlen($cpfCnpj) === 14 && !$this->validarCnpj($cpfCnpj)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CNPJ inválido'
                ], 422);
            }
        }

        // Validate email if being changed
        if (isset($data['email']) && !empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'E-mail inválido'
            ], 422);
        }

        // Validate phone format
        foreach (['contato_1', 'contato_2', 'contato_3'] as $campo) {
            if (isset($data[$campo]) && !empty($data[$campo]) && !preg_match('/^[\d\s()+\-]+$/', $data[$campo])) {
                return response()->json([
                    'success' => false,
                    'message' => "Formato de telefone inválido no campo {$campo}"
                ], 422);
            }
        }

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

        AuditService::log($request, 'update', 'clientes', $id, (array) $cliente, $updateFields);

        return response()->json([
            'success' => true,
            'message' => 'Cliente atualizado com sucesso',
            'data' => $cliente
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $cliente = DB::table('clientes')->where('id_cliente', $id)->first();
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado'
            ], 404);
        }

        DB::table('clientes')->where('id_cliente', $id)->delete();

        AuditService::log($request, 'delete', 'clientes', $id, (array) $cliente);

        return response()->json([
            'success' => true,
            'message' => 'Cliente excluído com sucesso'
        ]);
    }
}