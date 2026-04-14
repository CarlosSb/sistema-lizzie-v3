<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->increments('id_pedido');
            $table->unsignedInteger('id_cliente');
            $table->unsignedInteger('id_vendedor');
            $table->decimal('total_pedido', 10, 2)->default(0);
            $table->text('obs_pedido')->nullable();
            $table->text('obs_entrega')->nullable();
            $table->text('obs_cancelamento')->nullable();
            $table->date('data_entrega')->nullable();
            $table->timestamp('data_pedido')->useCurrent();
            $table->tinyInteger('status')->default(1);
            $table->string('forma_pag', 50)->nullable();
            $table->decimal('ped_desconto', 10, 2)->default(0);

            $table->foreign('id_cliente')->references('id_cliente')->on('clientes');
            $table->foreign('id_vendedor')->references('id_vendedor')->on('vendedores');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};