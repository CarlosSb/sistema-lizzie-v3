<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Rate limiting middleware based on IP address.
 * Stores attempt counts in a simple DB table for persistence across restarts.
 */
class RateLimitMiddleware
{
    public function handle($request, Closure $next, $maxAttempts = 5, $decayMinutes = 15)
    {
        $ip = $request->ip();
        $key = 'ratelimit:' . $ip;

        // Use file-based cache for simplicity (no extra table needed)
        $cacheDir = __DIR__ . '/../../storage/cache/ratelimit';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . '/' . md5($ip) . '.json';
        $now = time();
        $decaySeconds = $decayMinutes * 60;

        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && ($data['expires'] ?? 0) > $now) {
                if (($data['attempts'] ?? 0) >= $maxAttempts) {
                    $retryAfter = ($data['expires'] ?? 0) - $now;
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login attempts. Please try again later.',
                        'retry_after_seconds' => $retryAfter,
                    ], 429, ['Retry-After' => $retryAfter]);
                }
                $data['attempts'] = ($data['attempts'] ?? 0) + 1;
                file_put_contents($cacheFile, json_encode($data));
                return $next($request);
            }
        }

        // First attempt or expired
        file_put_contents($cacheFile, json_encode([
            'attempts' => 1,
            'expires' => $now + $decaySeconds,
        ]));

        return $next($request);
    }

    public function reset($request)
    {
        $ip = $request->ip();
        $cacheDir = __DIR__ . '/../../storage/cache/ratelimit';
        $cacheFile = $cacheDir . '/' . md5($ip) . '.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }
}
