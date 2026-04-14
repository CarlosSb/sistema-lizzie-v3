<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $table = 'produtos';
    protected $primaryKey = 'id_produto';
    public $timestamps = false;

    protected $fillable = [
        'referencia',
        'produto',
        'valor_unt_norde',
        'valor_unt_norte',
        'status'
    ];

    protected $casts = [
        'valor_unt_norde' => 'float',
        'valor_unt_norte' => 'float',
        'status' => 'integer'
    ];

    public function getNomeProdutoAttribute()
    {
        return $this->produto;
    }

    public function getCodigoAttribute()
    {
        return $this->referencia;
    }

    public function getPrecoVendaAttribute()
    {
        return $this->valor_unt_norde;
    }

    public function getDescricaoAttribute()
    {
        return null;
    }

    public function getUnidadeAttribute()
    {
        return 'UN';
    }

    public function getEstoqueAttribute()
    {
        return 0;
    }

    public function getCategoriaAttribute()
    {
        return null;
    }

    public function itensPedido()
    {
        return $this->hasMany(ItemPedido::class, 'id_produto');
    }
}