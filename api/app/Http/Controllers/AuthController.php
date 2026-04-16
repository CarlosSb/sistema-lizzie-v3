<?php

namespace App\Http\Controllers;

use App\Services\JwtService;
use App\Services\AuditService;
use App\Models\Vendedor;
use App\Http\Middleware\RateLimitMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function login(Request $request)
    {
        $data = $request->all();
        
        if (empty($data['usuario']) || empty($data['senha'])) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário e senha são obrigatórios'
            ], 422);
        }

        $vendedor = Vendedor::where('usuario', $data['usuario'])->first();

        if (!$vendedor) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado'
            ], 401);
        }

        if ($vendedor->status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário inativo'
            ], 401);
        }

        $senhaValida = false;

        if (str_starts_with($vendedor->senha, '$2y$')) {
            $senhaValida = password_verify($data['senha'], $vendedor->senha);
        } else {
            // Senha legada em base64: verifica e faz upgrade para bcrypt
            $senhaValida = ($vendedor->senha === base64_encode($data['senha']));

            if ($senhaValida) {
                // Upgrade automático: converte senha base64 para bcrypt
                DB::table('vendedores')
                    ->where('id_vendedor', $vendedor->id_vendedor)
                    ->update(['senha' => password_hash($data['senha'], PASSWORD_DEFAULT)]);
            }
        }

        if (!$senhaValida) {
            return response()->json([
                'success' => false,
                'message' => 'Senha incorreta'
            ], 401);
        }

        $tokens = $this->jwtService->generateToken($vendedor);

        // Armazenar hash do refresh token
        $refreshTokenHash = hash('sha256', $tokens['refresh_token']);
        DB::table('vendedores')
            ->where('id_vendedor', $vendedor->id_vendedor)
            ->update(['refresh_token' => $refreshTokenHash]);

        AuditService::logLogin($request, $vendedor->id_vendedor, $vendedor->nome_vendedor);

        // Reset rate limit on successful login
        (new RateLimitMiddleware())->reset($request);

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'data' => [
                'user' => [
                    'id' => $vendedor->id_vendedor,
                    'nome' => $vendedor->nome_vendedor,
                    'usuario' => $vendedor->usuario,
                    'nivel' => $vendedor->controle_acesso
                ],
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_type' => $tokens['token_type'],
                'expires_in' => $tokens['expires_in']
            ]
        ]);
    }

    public function refresh(Request $request)
    {
        $data = $request->all();

        if (empty($data['refresh_token'])) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token é obrigatório'
            ], 422);
        }

        // Verificar hash do refresh token no banco
        $refreshTokenHash = hash('sha256', $data['refresh_token']);
        $vendedor = DB::table('vendedores')
            ->where('refresh_token', $refreshTokenHash)
            ->first();

        if (!$vendedor || $vendedor->status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Token de refresh inválido ou expirado'
            ], 401);
        }

        $tokens = $this->jwtService->refreshAccessToken($data['refresh_token']);

        if (!$tokens) {
            return response()->json([
                'success' => false,
                'message' => 'Token de refresh inválido ou expirado'
            ], 401);
        }

        // Atualizar hash do novo refresh token
        $newRefreshTokenHash = hash('sha256', $tokens['refresh_token']);
        DB::table('vendedores')
            ->where('id_vendedor', $vendedor->id_vendedor)
            ->update(['refresh_token' => $newRefreshTokenHash]);

        return response()->json([
            'success' => true,
            'message' => 'Token refreshado com sucesso',
            'data' => $tokens
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->attributes->get('user');

        if ($user) {
            DB::table('vendedores')
                ->where('id_vendedor', $user->data->id)
                ->update(['refresh_token' => null]);

            AuditService::logLogout($request, $user->data->id, $user->data->nome);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->attributes->get('user');
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->data->id,
                'nome' => $user->data->nome,
                'usuario' => $user->data->usuario,
                'nivel' => $user->data->nivel
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->attributes->get('user');
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Não autorizado'], 401);
        }

        $data = $request->all();
        
        $updateData = [];
        
        if (isset($data['nome'])) {
            $updateData['nome_vendedor'] = $data['nome'];
        }
        
        if (isset($data['senha']) && !empty($data['senha'])) {
            $updateData['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
        }

        if (!empty($updateData)) {
            Vendedor::where('id_vendedor', $user->data->id)->update($updateData);
        }

        $vendedor = Vendedor::where('id_vendedor', $user->data->id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso',
            'data' => [
                'id' => $vendedor->id_vendedor,
                'nome' => $vendedor->nome_vendedor,
                'usuario' => $vendedor->usuario,
                'nivel' => $vendedor->controle_acesso
            ]
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->attributes->get('user');
        if (!$user || ($user->data->nivel ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Acesso restrito'], 403);
        }

        $vendedores = DB::table('vendedores')
            ->select('id_vendedor', 'nome_vendedor', 'usuario', 'controle_acesso', 'status')
            ->orderBy('nome_vendedor')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vendedores
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->attributes->get('user');
        if (!$user || ($user->data->nivel ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Acesso restrito'], 403);
        }

        $data = $request->all();
        
        if (empty($data['nome_vendedor']) || empty($data['usuario']) || empty($data['senha'])) {
            return response()->json([
                'success' => false,
                'message' => 'Nome, usuário e senha são obrigatórios'
            ], 422);
        }

        $existe = DB::table('vendedores')->where('usuario', $data['usuario'])->first();
        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário já existe'
            ], 422);
        }

        $id = DB::table('vendedores')->insertGetId([
            'nome_vendedor' => $data['nome_vendedor'],
            'usuario' => $data['usuario'],
            'senha' => password_hash($data['senha'], PASSWORD_DEFAULT),
            'controle_acesso' => $data['controle_acesso'] ?? 'vendedor',
            'status' => $data['status'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuário criado com sucesso',
            'data' => ['id' => $id]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->attributes->get('user');
        if (!$user || ($user->data->nivel ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Acesso restrito'], 403);
        }

        $data = $request->all();
        
        $updateData = [];
        
        if (isset($data['nome_vendedor'])) {
            $updateData['nome_vendedor'] = $data['nome_vendedor'];
        }
        
        if (isset($data['controle_acesso'])) {
            $updateData['controle_acesso'] = $data['controle_acesso'];
        }
        
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        
        if (isset($data['senha']) && !empty($data['senha'])) {
            $updateData['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
        }

        DB::table('vendedores')->where('id_vendedor', $id)->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->attributes->get('user');
        if (!$user || ($user->data->nivel ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Acesso restrito'], 403);
        }

        if ($id == $user->data->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir seu próprio usuário'
            ], 422);
        }

        DB::table('vendedores')->where('id_vendedor', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuário excluído com sucesso'
        ]);
    }
}