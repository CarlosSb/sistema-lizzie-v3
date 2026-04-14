<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    protected $table = 'vendedores';
    protected $primaryKey = 'id_vendedor';
    public $timestamps = false;

    protected $fillable = [
        'nome_vendedor',
        'contato_vendedor',
        'controle_acesso',
        'usuario',
        'senha',
        'status'
    ];

    protected $casts = [
        'status' => 'integer'
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_vendedor');
    }
}