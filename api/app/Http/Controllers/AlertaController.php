<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertaController extends Controller
{
    private $lastEventId = 0;
    
    public function stream(Request $request)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        
        $this->lastEventId = (int) DB::table('alertas')->max('id_alerta') ?: 0;
        
        $timeout = 30;
        $startTime = time();
        
        while ((time() - $startTime) < $timeout) {
            $lastId = DB::table('alertas')->max('id_alerta') ?: 0;
            
            if ($lastId > $this->lastEventId) {
                $this->lastEventId = $lastId;
                $alertas = DB::table('alertas')
                    ->where('id_alerta', '>', $lastId - $this->lastEventId + ($lastId > $this->lastEventId ? 0 : $this->lastEventId - $lastId))
                    ->where('lido', 0)
                    ->get();
                
                if (count($alertas) > 0) {
                    $this->lastEventId = $lastId;
                }
                
                foreach ($alertas as $alerta) {
                    echo "id: " . $alerta->id_alerta . "\n";
                    echo "event: novo_pedido\n";
                    echo "data: " . json_encode($alerta) . "\n\n";
                    ob_flush();
                    flush();
                }
            }
            
            sleep(2);
        }
        
        echo ": heartbeat\n\n";
        ob_flush();
        flush();
    }
    
    public function index(Request $request)
    {
        $query = DB::table('alertas')->orderBy('data_alerta', 'desc');
        
        if ($request->has('nao_lidos')) {
            $query->where('lido', 0);
        }
        
        $alertas = $query->limit(50)->get();
        
        return response()->json([
            'success' => true,
            'data' => $alertas,
            'total' => count($alertas)
        ]);
    }
    
    public function naoLidos()
    {
        $count = DB::table('alertas')->where('lido', 0)->count();
        
        return response()->json([
            'success' => true,
            'data' => ['count' => $count]
        ]);
    }
    
    public function ler($id)
    {
        DB::table('alertas')->where('id_alerta', $id)->update(['lido' => 1]);
        
        return response()->json([
            'success' => true,
            'message' => 'Alerta marcado como lido'
        ]);
    }
    
    public function criarAlertaNovoPedido($pedidoId)
    {
        $pedido = DB::table('pedidos')
            ->select('pedidos.*', 'clientes.razao_social', 'vendedores.nome_vendedor')
            ->leftJoin('clientes', 'pedidos.id_cliente', '=', 'clientes.id_cliente')
            ->leftJoin('vendedores', 'pedidos.id_vendedor', '=', 'vendedores.id_vendedor')
            ->where('pedidos.id_pedido', $pedidoId)
            ->first();
        
        if (!$pedido) return;
        
        $id = DB::table('alertas')->insertGetId([
            'tipo' => 'pedido_novo',
            'titulo' => 'Novo Pedido #' . $pedidoId,
            'mensagem' => 'Pedido de ' . ($pedido->razao_social ?? 'Cliente') . ' pelo vendedor ' . ($pedido->nome_vendedor ?? ''),
            'referencia_id' => $pedidoId,
            'lido' => 0,
            'data_alerta' => date('Y-m-d H:i:s')
        ]);
        
        return $id;
    }
}