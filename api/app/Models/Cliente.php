<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';
    public $timestamps = false;

    protected $fillable = [
        'responsavel',
        'razao_social',
        'nome_fantasia',
        'pessoa',
        'cpf_cnpj',
        'inscricao_estadual',
        'data_nascimento',
        'contato_1',
        'contato_2',
        'contato_3',
        'email',
        'endereco',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'rota',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'pessoa' => 'integer',
        'data_nascimento' => 'date'
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_cliente');
    }
}