<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AuditService
{
    public static function log(Request $request, string $acao, string $tabela, ?int $registroId = null, ?array $dadosAnteriores = null, ?array $dadosNovos = null): void
    {
        $user = $request->attributes->get('user');
        
        DB::table('audit_logs')->insert([
            'acao' => $acao,
            'tabela' => $tabela,
            'registro_id' => $registroId,
            'usuario_id' => $user->data->id ?? null,
            'usuario_nome' => $user->data->nome ?? null,
            'dados_anteriores' => $dadosAnteriores ? json_encode($dadosAnteriores, JSON_UNESCAPED_UNICODE) : null,
            'dados_novos' => $dadosNovos ? json_encode($dadosNovos, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function logLogin(Request $request, int $usuarioId, string $nome): void
    {
        DB::table('audit_logs')->insert([
            'acao' => 'login',
            'tabela' => 'usuarios',
            'registro_id' => $usuarioId,
            'usuario_id' => $usuarioId,
            'usuario_nome' => $nome,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function logLogout(Request $request, int $usuarioId, string $nome): void
    {
        DB::table('audit_logs')->insert([
            'acao' => 'logout',
            'tabela' => 'usuarios',
            'registro_id' => $usuarioId,
            'usuario_id' => $usuarioId,
            'usuario_nome' => $nome,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}