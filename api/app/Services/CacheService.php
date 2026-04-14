<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CacheService
{
    private static string $cacheDir = __DIR__ . '/../../storage/cache';
    
    public static function get(string $key, callable $callback, int $ttl = 300): array
    {
        $cacheFile = self::$cacheDir . '/' . md5($key) . '.json';
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && $data['expires'] > time()) {
                return $data['value'];
            }
        }
        
        $value = $callback();
        
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0755, true);
        }
        
        file_put_contents($cacheFile, json_encode([
            'value' => $value,
            'expires' => time() + $ttl
        ]));
        
        return $value;
    }
    
    public static function forget(string $key): void
    {
        $cacheFile = self::$cacheDir . '/' . md5($key) . '.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }
    
    public static function flush(): void
    {
        if (is_dir(self::$cacheDir)) {
            foreach (glob(self::$cacheDir . '/*.json') as $file) {
                @unlink($file);
            }
        }
    }
}