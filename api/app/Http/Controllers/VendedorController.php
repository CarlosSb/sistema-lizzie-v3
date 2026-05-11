<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use App\Services\AuditService;
use Illuminate\Http\Request;

class VendedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendedor::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nome_vendedor', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%");
            });
        }

        $vendedores = $query->orderBy('nome_vendedor')->get();

        return response()->json([
            'success' => true,
            'data' => $vendedores,
            'total' => $vendedores->count()
        ]);
    }

    public function show($id)
    {
        $vendedor = Vendedor::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $vendedor
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_vendedor' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'comissao' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|boolean'
        ]);

        $vendedor = Vendedor::create($validated);

        AuditService::log($request, 'create', 'vendedores', $vendedor->id_vendedor, null, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendedor criado com sucesso',
            'data' => $vendedor
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $vendedor = Vendedor::findOrFail($id);
        $oldData = $vendedor->toArray();

        $validated = $request->validate([
            'nome_vendedor' => 'sometimes|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'comissao' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|boolean'
        ]);

        $vendedor->update($validated);

        AuditService::log($request, 'update', 'vendedores', $vendedor->id_vendedor, $oldData, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendedor atualizado com sucesso',
            'data' => $vendedor
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $vendedor = Vendedor::findOrFail($id);
        $oldData = $vendedor->toArray();
        $vendedor->delete();

        AuditService::log($request, 'delete', 'vendedores', $id, $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Vendedor excluído com sucesso'
        ]);
    }
}