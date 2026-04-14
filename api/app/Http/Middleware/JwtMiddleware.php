<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\JwtService;

class JwtMiddleware
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle($request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            return response()->json([
                'success' => false,
                'message' => 'Token não fornecido'
            ], 401);
        }

        $parts = explode(' ', $authHeader);
        
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            return response()->json([
                'success' => false,
                'message' => 'Formato de token inválido'
            ], 401);
        }

        $token = $parts[1];
        $decoded = $this->jwtService->getUserFromToken($token);

        if (!$decoded) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado'
            ], 401);
        }

        $request->attributes->set('user', $decoded);
        
        return $next($request);
    }
}