<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Vendedor;
use Illuminate\Support\Facades\Hash;

class JwtService
{
    private string $secretKey;
    private int $ttl = 3600; // 1 hour
    private int $refreshTtl = 604800; // 7 days

    public function __construct()
    {
        $this->secretKey = env('JWT_SECRET', 'lizzie-jwt-secret-key-2024');
    }

    public function generateToken(Vendedor $vendedor): array
    {
        $now = time();
        
        $payload = [
            'iss' => 'lizzie-api',
            'aud' => 'lizzie-app',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttl,
            'data' => [
                'id' => $vendedor->id_vendedor,
                'nome' => $vendedor->nome_vendedor,
                'usuario' => $vendedor->usuario,
                'nivel' => $vendedor->controle_acesso
            ]
        ];

        $accessToken = JWT::encode($payload, $this->secretKey, 'HS256');

        $refreshPayload = [
            'iss' => 'lizzie-api',
            'aud' => 'lizzie-app',
            'iat' => $now,
            'exp' => $now + $this->refreshTtl,
            'data' => [
                'id' => $vendedor->id_vendedor,
                'type' => 'refresh'
            ]
        ];

        $refreshToken = JWT::encode($refreshPayload, $this->secretKey, 'HS256');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->ttl
        ];
    }

    public function verifyToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function refreshAccessToken(string $refreshToken): ?array
    {
        $decoded = $this->verifyToken($refreshToken);
        
        if (!$decoded || !isset($decoded->data->type) || $decoded->data->type !== 'refresh') {
            return null;
        }

        $vendedor = Vendedor::find($decoded->data->id);
        
        if (!$vendedor || $vendedor->status != 1) {
            return null;
        }

        return $this->generateToken($vendedor);
    }

    public function getUserFromToken(string $token): ?object
    {
        return $this->verifyToken($token);
    }
}