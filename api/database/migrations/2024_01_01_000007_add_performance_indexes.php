<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Clientes indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_clientes_status ON clientes(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_clientes_razao_social ON clientes(razao_social(100))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_clientes_cidade ON clientes(cidade(50))');
        
        // Pedidos indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pedidos_status ON pedidos(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pedidos_data_pedido ON pedidos(data_pedido)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pedidos_id_cliente ON pedidos(id_cliente)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pedidos_id_vendedor ON pedidos(id_vendedor)');
        
        // Itens pedidos indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_itens_pedidos_id_pedido ON itens_pedidos(id_pedido)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_itens_pedidos_id_produto ON itens_pedidos(id_produto)');
        
        // Produtos indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_produtos_status ON produtos(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_produtos_produto ON produtos(produto(100))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_produtos_referencia ON produtos(referencia(50))');
        
        // Vendedores indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_vendedores_status ON vendedores(status)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_clientes_status ON clientes');
        DB::statement('DROP INDEX IF EXISTS idx_clientes_razao_social ON clientes');
        DB::statement('DROP INDEX IF EXISTS idx_clientes_cidade ON clientes');
        DB::statement('DROP INDEX IF EXISTS idx_pedidos_status ON pedidos');
        DB::statement('DROP INDEX IF EXISTS idx_pedidos_data_pedido ON pedidos');
        DB::statement('DROP INDEX IF EXISTS idx_pedidos_id_cliente ON pedidos');
        DB::statement('DROP INDEX IF EXISTS idx_pedidos_id_vendedor ON pedidos');
        DB::statement('DROP INDEX IF EXISTS idx_itens_pedidos_id_pedido ON itens_pedidos');
        DB::statement('DROP INDEX IF EXISTS idx_itens_pedidos_id_produto ON itens_pedidos');
        DB::statement('DROP INDEX IF EXISTS idx_produtos_status ON produtos');
        DB::statement('DROP INDEX IF EXISTS idx_produtos_produto ON produtos');
        DB::statement('DROP INDEX IF EXISTS idx_produtos_referencia ON produtos');
        DB::statement('DROP INDEX IF EXISTS idx_vendedores_status ON vendedores');
    }
};