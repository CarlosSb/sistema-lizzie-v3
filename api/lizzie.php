#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->boot();

$command = $argv[1] ?? 'status';

switch ($command) {
    case 'migrate':
        migrate($app);
        break;
    case 'rollback':
        rollback($app);
        break;
    case 'serve':
        serve($app);
        break;
    case 'status':
    default:
        echo "Lizzie CLI\n";
        echo "Usage: php lizzie.php [command]\n\n";
        echo "Commands:\n";
        echo "  serve     Start PHP built-in server\n";
        echo "  migrate   Run migrations\n";
        echo "  rollback  Rollback (drop tables)\n";
        break;
}

function getPdo() {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $db   = getenv('DB_DATABASE') ?: 'db_lizzie';
    $user = getenv('DB_USERNAME') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: '';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

function migrate($app) {
    echo "Running migrations...\n\n";
    
    $files = glob(__DIR__.'/database/migrations/*.php');
    sort($files);
    
    $pdo = getPdo();
    
    foreach ($files as $file) {
        $class = basename($file, '.php');
        echo "Migrating: $class\n";
        
        try {
            $migration = require $file;
            if (is_object($migration) && method_exists($migration, 'up')) {
                $migration->up();
            }
            echo "Migrated:  $class\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nDone!\n";
}

function rollback($app) {
    echo "Rolling back...\n";
    $pdo = getPdo();
    
    $tables = ['itens_pedidos', 'pedidos', 'produtos', 'vendedores', 'clientes'];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $table");
            echo "Dropped: $table\n";
        } catch (Exception $e) {
            echo "Error dropping $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Done!\n";
}

function serve($app) {
    echo "Starting server at http://localhost:8000\n";
    echo "Press Ctrl+C to stop\n";
    exec("php -S localhost:8000 -t public");
}