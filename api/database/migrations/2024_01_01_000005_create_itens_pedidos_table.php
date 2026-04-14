<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_pedidos', function (Blueprint $table) {
            $table->increments('id_item');
            $table->unsignedInteger('id_pedido');
            $table->unsignedInteger('id_produto');
            $table->integer('quantidade')->default(1);
            $table->decimal('preco_unitario', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);

            $table->foreign('id_pedido')->references('id_pedido')->on('pedidos')->onDelete('cascade');
            $table->foreign('id_produto')->references('id_produto')->on('produtos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_pedidos');
    }
};