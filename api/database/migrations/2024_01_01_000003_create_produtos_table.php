<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->increments('id_produto');
            $table->string('codigo', 50);
            $table->string('nome_produto', 255);
            $table->text('descricao')->nullable();
            $table->string('unidade', 10)->nullable();
            $table->decimal('preco_venda', 10, 2)->default(0);
            $table->integer('estoque')->default(0);
            $table->string('categoria', 100)->nullable();
            $table->boolean('status')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};