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
        $total = \Illuminate\Support\Facades\DB::table('item_estoques')
            ->where('estoque_id', function ($q) {
                $q->select('id')->from('estoques')
                    ->where('ref_produto', $this->id_produto)->limit(1);
            })
            ->selectRaw('COALESCE(SUM(tam_p + tam_m + tam_g + tam_u + tam_rn + ida_1 + ida_2 + ida_3 + ida_4 + ida_6 + ida_8 + ida_10 + ida_12), 0) as total')
            ->value('total');

        return (int) $total;
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