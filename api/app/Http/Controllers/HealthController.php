<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke()
    {
        $health = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'timezone' => env('APP_TIMEZONE', 'UTC'),
            'services' => [
                'database' => ['status' => 'ok', 'latency_ms' => 0],
                'cache' => ['status' => 'ok'],
            ],
            'app' => [
                'name' => env('APP_NAME', 'Lizzie'),
                'env' => env('APP_ENV', 'local'),
                'debug' => (bool) env('APP_DEBUG', false),
                'version' => '3.0.0',
            ],
        ];

        // Check database
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $health['services']['database']['latency_ms'] = round((microtime(true) - $start) * 1000, 2);
        } catch (\Exception $e) {
            $health['status'] = 'degraded';
            $health['services']['database'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        // Check cache directory
        $cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
            $health['status'] = 'degraded';
            $health['services']['cache'] = [
                'status' => 'error',
                'message' => 'Cache directory not writable',
            ];
        }

        // Count key data
        try {
            $health['data'] = [
                'clientes' => (int) DB::table('clientes')->where('status', 1)->count(),
                'produtos' => (int) DB::table('produtos')->where('status', 1)->count(),
                'pedidos' => (int) DB::table('pedidos')->where('status', 1)->count(),
                'alertas_nao_lidos' => (int) DB::table('alertas')->where('lido', 0)->count(),
            ];
        } catch (\Exception $e) {
            // Table might not exist, skip
        }

        $httpStatus = $health['status'] === 'ok' ? 200 : 503;

        return response()->json($health, $httpStatus);
    }
}
