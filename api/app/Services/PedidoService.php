<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PedidoService
{
    private const TAMANHOS_INFANTIS = ['pp', 'p', 'm', 'g', 'u', 'rn'];
    private const TAMANHOS_FEMININO = ['ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12'];

    private function findOrFail($model, $id)
    {
        $table = match ($model) {
            'pedido' => 'pedidos',
            'item' => 'itens_pedidos',
            'produto' => 'produtos',
            default => null
        };
        
        if (!$table) return null;
        
        $pk = match ($model) {
            'pedido' => 'id_pedido',
            'item' => 'id_item_pedido',
            'produto' => 'id_produto',
            default => 'id'
        };
        
        $result = DB::table($table)->where($pk, $id)->first();
        
        if (!$result) {
            throw new \Exception(ucfirst($model) . ' não encontrado: ' . $id);
        }
        
        return $result;
    }

    private function create($table, $data)
    {
        $pk = match ($table) {
            'pedidos' => 'id_pedido',
            'itens_pedidos' => 'id_item_pedido',
            'produtos' => 'id_produto',
            'clientes' => 'id_cliente',
            'vendedores' => 'id_vendedor',
            default => 'id'
        };
        
        $id = DB::table($table)->insertGetId($data);
        return DB::table($table)->where($pk, $id)->first();
    }

    private function update($table, $id, $data)
    {
        $pk = match ($table) {
            'pedidos' => 'id_pedido',
            'itens_pedidos' => 'id_item_pedido',
            'produtos' => 'id_produto',
            default => 'id'
        };
        
        DB::table($table)->where($pk, $id)->update($data);
        
        return DB::table($table)->where($pk, $id)->first();
    }

    public function criarPedido(array $data)
    {
        $itens = $data['itens'] ?? [];
        
        $totalPedido = $this->calcularTotalPedido($itens);
        
        $pedido = $this->create('pedidos', [
            'id_cliente' => $data['id_cliente'],
            'id_vendedor' => $data['id_vendedor'],
            'total_pedido' => $totalPedido['total'],
            'obs_pedido' => $data['obs_pedido'] ?? null,
            'obs_entrega' => $data['obs_entrega'] ?? null,
            'data_entrega' => $data['data_entrega'] ?? null,
            'forma_pag' => $data['forma_pag'] ?? null,
            'ped_desconto' => $data['desconto_valor'] ?? 0,
            'status' => 1,
            'data_pedido' => date('Y-m-d H:i:s')
        ]);

        $pedidoId = $pedido->id_pedido;

        foreach ($itens as $itemData) {
            $this->adicionarItem($pedidoId, $itemData);
        }

        $this->recalcularTotal($pedidoId);

        return $this->getPedidoWithRelations($pedidoId);
    }

    public function adicionarItem(int $pedidoId, array $data)
    {
        $produto = $this->findOrFail('produto', $data['id_produto']);
        if (!$produto) {
            throw new \Exception('Produto não encontrado');
        }
        
        $precoUnitario = $data['regiao'] === 'norte' ? $produto->valor_unt_norte : $produto->valor_unt_norde;
        
        $quantidadeTotal = $this->calcularQuantidadeTotal($data['quantidades'] ?? []);
        
        $subtotal = $quantidadeTotal * $precoUnitario;
        $desconto = $this->calcularDescontoItem($subtotal, $data['desconto_percentual'] ?? 0, $data['desconto_valor'] ?? 0);
        $totalItem = $subtotal - $desconto;

        $item = $this->create('itens_pedidos', [
            'id_pedido' => $pedidoId,
            'id_produto' => $data['id_produto'],
            'id_subcategoria' => $data['id_subcategoria'] ?? 0,
            'estampa' => $data['tem_estampa'] ?? false,
            'estampa_lisa' => $data['tem_estampa_lisa'] ?? false,
            'lisa' => $data['lisa'] ?? 0,
            'tam_p' => $data['quantidades']['p'] ?? 0,
            'tam_m' => $data['quantidades']['m'] ?? 0,
            'tam_g' => $data['quantidades']['g'] ?? 0,
            'tam_u' => $data['quantidades']['u'] ?? 0,
            'tam_rn' => $data['quantidades']['rn'] ?? 0,
            'ida_1' => $data['quantidades']['ida_1'] ?? 0,
            'ida_2' => $data['quantidades']['ida_2'] ?? 0,
            'ida_3' => $data['quantidades']['ida_3'] ?? 0,
            'ida_4' => $data['quantidades']['ida_4'] ?? 0,
            'ida_6' => $data['quantidades']['ida_6'] ?? 0,
            'ida_8' => $data['quantidades']['ida_8'] ?? 0,
            'ida_10' => $data['quantidades']['ida_10'] ?? 0,
            'ida_12' => $data['quantidades']['ida_12'] ?? 0,
            'tam_pp' => $data['quantidades']['pp'] ?? 0,
            'masculino' => ($data['sexo'] ?? 'U') === 'M' ? 1 : 0,
            'feminino' => ($data['sexo'] ?? 'U') === 'F' ? 1 : 0,
            'total_item' => $totalItem,
            'val_desconto' => $desconto
        ]);

        $this->recalcularTotal($pedidoId);

        return $item;
    }

    public function atualizarItem(int $itemId, array $data)
    {
        $item = $this->findOrFail('item', $itemId);
        if (!$item) {
            throw new \Exception('Item não encontrado');
        }
        
        $produto = $this->findOrFail('produto', $item->id_produto);
        $precoUnitario = $data['regiao'] === 'norte' ? $produto->valor_unt_norte : $produto->valor_unt_norde;
        
        $quantidadeTotal = $this->calcularQuantidadeTotal($data['quantidades'] ?? []);
        
        $subtotal = $quantidadeTotal * $precoUnitario;
        $desconto = $this->calcularDescontoItem($subtotal, $data['desconto_percentual'] ?? 0, $data['desconto_valor'] ?? 0);
        $totalItem = $subtotal - $desconto;

        $this->update('itens_pedidos', $itemId, [
            'id_subcategoria' => $data['id_subcategoria'] ?? $item->id_subcategoria,
            'estampa' => $data['tem_estampa'] ?? $item->estampa,
            'estampa_lisa' => $data['tem_estampa_lisa'] ?? $item->estampa_lisa,
            'lisa' => $data['lisa'] ?? $item->lisa,
            'tam_p' => $data['quantidades']['p'] ?? 0,
            'tam_m' => $data['quantidades']['m'] ?? 0,
            'tam_g' => $data['quantidades']['g'] ?? 0,
            'tam_u' => $data['quantidades']['u'] ?? 0,
            'tam_rn' => $data['quantidades']['rn'] ?? 0,
            'ida_1' => $data['quantidades']['ida_1'] ?? 0,
            'ida_2' => $data['quantidades']['ida_2'] ?? 0,
            'ida_3' => $data['quantidades']['ida_3'] ?? 0,
            'ida_4' => $data['quantidades']['ida_4'] ?? 0,
            'ida_6' => $data['quantidades']['ida_6'] ?? 0,
            'ida_8' => $data['quantidades']['ida_8'] ?? 0,
            'ida_10' => $data['quantidades']['ida_10'] ?? 0,
            'ida_12' => $data['quantidades']['ida_12'] ?? 0,
            'tam_pp' => $data['quantidades']['pp'] ?? 0,
            'masculino' => ($data['sexo'] ?? 'U') === 'M' ? 1 : 0,
            'feminino' => ($data['sexo'] ?? 'U') === 'F' ? 1 : 0,
            'total_item' => $totalItem,
            'val_desconto' => $desconto
        ]);

        $this->recalcularTotal($item->id_pedido);

        return $this->findOrFail('item', $itemId);
    }

    public function removerItem(int $itemId): void
    {
        $item = $this->findOrFail('item', $itemId);
        if (!$item) {
            throw new \Exception('Item não encontrado');
        }
        
        $pedidoId = $item->id_pedido;
        
        DB::table('itens_pedidos')->where('id_item_pedido', $itemId)->delete();
        
        $this->recalcularTotal($pedidoId);
    }

    public function aplicarDescontoPedido(int $pedidoId, float $percentual, float $valor)
    {
        $pedido = $this->findOrFail('pedido', $pedidoId);
        if (!$pedido) {
            throw new \Exception('Pedido não encontrado');
        }
        
        $this->update('pedidos', $pedidoId, [
            'ped_desconto' => max($percentual, $valor)
        ]);

        $this->recalcularTotal($pedidoId);

        return $this->findOrFail('pedido', $pedidoId);
    }

    public function atualizarStatus(int $pedidoId, int $status, ?string $obsCancelamento = null)
    {
        $pedido = $this->findOrFail('pedido', $pedidoId);
        if (!$pedido) {
            throw new \Exception('Pedido não encontrado');
        }
        
        $statusAnterior = $pedido->status;
        $updateData = ['status' => $status];
        
        if ($status === 3 && $obsCancelamento) {
            $updateData['obs_cancelamento'] = $obsCancelamento;
        }

        $this->update('pedidos', $pedidoId, $updateData);

        if ($status === 4 && $statusAnterior !== 4) {
            $this->baixaEstoque($pedidoId);
        } elseif ($status === 3 && ($statusAnterior === 4 || $statusAnterior === 2)) {
            $this->retornoEstoque($pedidoId);
        }

        return $this->findOrFail('pedido', $pedidoId);
    }

    private function baixaEstoque(int $pedidoId): void
    {
        $itens = DB::table('itens_pedidos')->where('id_pedido', $pedidoId)->get();
        
        foreach ($itens as $item) {
            $estoque = DB::table('item_estoques')
                ->where('estoque_id', function ($q) use ($item) {
                    $q->select('id')->from('estoques')->where('ref_produto', $item->id_produto)->limit(1);
                })
                ->first();

            if (!$estoque) continue;

            $tamanhos = ['tam_pp', 'tam_p', 'tam_m', 'tam_g', 'tam_u', 'tam_rn', 'ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8'];
            $updateData = [];
            
            foreach ($tamanhos as $tm) {
                $campoItem = $tm;
                $campoEstoque = $tm;
                $atual = $estoque->$campoEstoque ?? 0;
                $usar = $item->$campoItem ?? 0;
                $updateData[$campoEstoque] = max(0, $atual - $usar);
            }

            if (!empty($updateData)) {
                DB::table('item_estoques')->where('id', $estoque->id)->update($updateData);
                
                DB::table('reg_estoques')->insert([
                    'item_estoque_id' => $estoque->id,
                    'estoque_id' => $estoque->estoque_id,
                    'pedido_id' => $pedidoId,
                    'descricao' => 'Baixa automática - Pedido #' . $pedidoId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    private function retornoEstoque(int $pedidoId): void
    {
        $itens = DB::table('itens_pedidos')->where('id_pedido', $pedidoId)->get();
        
        foreach ($itens as $item) {
            $estoque = DB::table('item_estoques')
                ->where('estoque_id', function ($q) use ($item) {
                    $q->select('id')->from('estoques')->where('ref_produto', $item->id_produto)->limit(1);
                })
                ->first();

            if (!$estoque) continue;

            $tamanhos = ['tam_pp', 'tam_p', 'tam_m', 'tam_g', 'tam_u', 'tam_rn', 'ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8'];
            $updateData = [];
            
            foreach ($tamanhos as $tm) {
                $campoItem = $tm;
                $campoEstoque = $tm;
                $atual = $estoque->$campoEstoque ?? 0;
                $retornar = $item->$campoItem ?? 0;
                $updateData[$campoEstoque] = $atual + $retornar;
            }

            if (!empty($updateData)) {
                DB::table('item_estoques')->where('id', $estoque->id)->update($updateData);
                
                DB::table('reg_estoques')->insert([
                    'item_estoque_id' => $estoque->id,
                    'estoque_id' => $estoque->estoque_id,
                    'pedido_id' => $pedidoId,
                    'descricao' => 'Retorno automático - Pedido #' . $pedidoId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    public function recalcularTotal(int $pedidoId): void
    {
        $pedido = $this->findOrFail('pedido', $pedidoId);
        if (!$pedido) {
            throw new \Exception('Pedido não encontrado');
        }
        
        $totalItens = DB::table('itens_pedidos')->where('id_pedido', $pedidoId)->sum('total_item');
        
        $descontoPedido = $pedido->ped_desconto ?? 0;
        
        $percentualDesconto = ($descontoPedido <= 100) ? $descontoPedido : 0;
        $valorDesconto = ($descontoPedido > 100) ? $descontoPedido : ($totalItens * $percentualDesconto / 100);
        
        $totalFinal = $totalItens - $valorDesconto;
        
        $this->update('pedidos', $pedidoId, ['total_pedido' => $totalFinal]);
    }

    private function calcularTotalPedido(array $itens): array
    {
        $subtotal = 0;
        $totalDescontos = 0;
        $totalQuantidade = 0;

        foreach ($itens as $item) {
            $produto = DB::table('produtos')->where('id_produto', $item['id_produto'])->first();
            if (!$produto) continue;

            $preco = $item['regiao'] === 'norte' ? $produto->valor_unt_norte : $produto->valor_unt_norde;
            $quantidade = $this->calcularQuantidadeTotal($item['quantidades'] ?? []);
            
            $subtotalItem = $quantidade * $preco;
            $descontoItem = $this->calcularDescontoItem(
                $subtotalItem, 
                $item['desconto_percentual'] ?? 0, 
                $item['desconto_valor'] ?? 0
            );

            $subtotal += $subtotalItem;
            $totalDescontos += $descontoItem;
            $totalQuantidade += $quantidade;
        }

        return [
            'subtotal' => $subtotal,
            'descontos' => $totalDescontos,
            'total' => $subtotal - $totalDescontos,
            'quantidade' => $totalQuantidade
        ];
    }

    private function calcularQuantidadeTotal(array $quantidades): int
    {
        $total = 0;
        
        foreach (self::TAMANHOS_INFANTIS as $tamanho) {
            $total += $quantidades[$tamanho] ?? 0;
        }
        
        foreach (self::TAMANHOS_FEMININO as $tamanho) {
            $total += $quantidades[$tamanho] ?? 0;
        }
        
        $total += $quantidades['pp'] ?? 0;
        $total += $quantidades['lisa'] ?? 0;
        
        return $total;
    }

    private function calcularDescontoItem(float $subtotal, float $percentual, float $valor): float
    {
        $descontoPercentual = ($subtotal * $percentual) / 100;
        return $descontoPercentual + $valor;
    }

    private function getPedidoWithRelations(int $pedidoId): array
    {
        $pedido = $this->findOrFail('pedido', $pedidoId);
        if (!$pedido) return [];
        
        $cliente = DB::table('clientes')->where('id_cliente', $pedido->id_cliente)->first();
        $vendedor = DB::table('vendedores')->where('id_vendedor', $pedido->id_vendedor)->first();
        $itens = DB::table('itens_pedidos')->where('id_pedido', $pedidoId)->get();
        
        return [
            'id_pedido' => $pedido->id_pedido,
            'id_cliente' => $pedido->id_cliente,
            'id_vendedor' => $pedido->id_vendedor,
            'total_pedido' => $pedido->total_pedido,
            'obs_pedido' => $pedido->obs_pedido,
            'obs_entrega' => $pedido->obs_entrega,
            'data_entrega' => $pedido->data_entrega,
            'forma_pag' => $pedido->forma_pag,
            'ped_desconto' => $pedido->ped_desconto,
            'status' => $pedido->status,
            'data_pedido' => $pedido->data_pedido,
            'cliente' => $cliente,
            'vendedor' => $vendedor,
            'itens' => $itens
        ];
    }

    public function getDetalheCalculo(int $pedidoId): array
    {
        $pedido = $this->findOrFail('pedido', $pedidoId);
        if (!$pedido) {
            throw new \Exception('Pedido não encontrado');
        }
        
        $cliente = DB::table('clientes')->where('id_cliente', $pedido->id_cliente)->first();
        $vendedor = DB::table('vendedores')->where('id_vendedor', $pedido->id_vendedor)->first();
        
        $itensDb = DB::table('itens_pedidos')->where('id_pedido', $pedidoId)->get();
        
        $itensDetalhados = [];
        
        foreach ($itensDb as $item) {
            $produto = DB::table('produtos')->where('id_produto', $item->id_produto)->first();
            
            $quantidades = [];
            
            $tamanhosMap = [
                'pp' => 'tam_pp', 'p' => 'tam_p', 'm' => 'tam_m', 
                'g' => 'tam_g', 'u' => 'tam_u', 'rn' => 'tam_rn',
                'ida_1' => 'ida_1', 'ida_2' => 'ida_2', 'ida_3' => 'ida_3',
                'ida_4' => 'ida_4', 'ida_6' => 'ida_6', 'ida_8' => 'ida_8',
                'ida_10' => 'ida_10', 'ida_12' => 'ida_12'
            ];
            
            foreach ($tamanhosMap as $nome => $coluna) {
                $val = $item->$coluna ?? 0;
                if ($val > 0) $quantidades[$nome] = (int) $val;
            }
            
            if ($item->lisa > 0) $quantidades['lisa'] = (int) $item->lisa;
            
            $quantidadeTotal = array_sum($quantidades);
            $precoUnitario = $produto->valor_unt_norde;
            
            $itensDetalhados[] = [
                'id' => $item->id_item_pedido,
                'produto' => [
                    'id' => $produto->id_produto,
                    'nome' => $produto->produto,
                    'referencia' => $produto->referencia
                ],
                'quantidades' => $quantidades,
                'quantidade_total' => $quantidadeTotal,
                'preco_unitario' => $precoUnitario,
                'subtotal' => $quantidadeTotal * $precoUnitario,
                'desconto' => $item->val_desconto,
                'total' => $item->total_item,
                'caracteristicas' => [
                    'estampa' => (bool)$item->estampa,
                    'estampa_lisa' => (bool)$item->estampa_lisa,
                    'masculino' => (bool)$item->masculino,
                    'feminino' => (bool)$item->feminino
                ]
            ];
        }

        $subtotalItens = array_sum(array_column($itensDetalhados, 'subtotal'));
        $totalDescontosItens = array_sum(array_column($itensDetalhados, 'desconto'));
        
        return [
            'pedido' => [
                'id' => $pedido->id_pedido,
                'cliente' => $cliente->razao_social ?? '',
                'vendedor' => $vendedor->nome_vendedor ?? '',
                'data' => $pedido->data_pedido,
                'status' => $pedido->status
            ],
            'itens' => $itensDetalhados,
            'resumo' => [
                'quantidade_total' => array_sum(array_column($itensDetalhados, 'quantidade_total')),
                'subtotal_itens' => $subtotalItens,
                'descontos_itens' => $totalDescontosItens,
                'desconto_pedido' => $pedido->ped_desconto,
                'total_pedido' => $pedido->total_pedido
            ]
        ];
    }
}