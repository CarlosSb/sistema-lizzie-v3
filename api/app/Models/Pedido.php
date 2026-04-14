<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';
    protected $primaryKey = 'id_pedido';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'id_vendedor',
        'total_pedido',
        'obs_pedido',
        'obs_entrega',
        'obs_cancelamento',
        'data_entrega',
        'status',
        'forma_pag',
        'ped_desconto'
    ];

    protected $casts = [
        'total_pedido' => 'float',
        'ped_desconto' => 'float',
        'status' => 'integer',
        'data_entrega' => 'date',
        'data_pedido' => 'datetime'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }

    public function itens()
    {
        return $this->hasMany(ItemPedido::class, 'id_pedido');
    }
}