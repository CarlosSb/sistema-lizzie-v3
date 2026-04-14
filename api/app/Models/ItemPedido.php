<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPedido extends Model
{
    protected $table = 'itens_pedidos';
    protected $primaryKey = 'id_item_pedido';
    public $timestamps = false;

    protected $fillable = [
        'id_pedido',
        'id_produto',
        'id_subcategoria',
        'estampa',
        'estampa_lisa',
        'lisa',
        'tam_p',
        'tam_m',
        'tam_g',
        'tam_u',
        'tam_rn',
        'ida_1',
        'ida_2',
        'ida_3',
        'ida_4',
        'ida_6',
        'ida_8',
        'ida_10',
        'ida_12',
        'masculino',
        'feminino',
        'total_item',
        'val_desconto',
        'tam_pp'
    ];

    protected $casts = [
        'estampa' => 'boolean',
        'estampa_lisa' => 'boolean',
        'masculino' => 'boolean',
        'feminino' => 'boolean',
        'total_item' => 'float',
        'val_desconto' => 'float'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'id_produto');
    }
}