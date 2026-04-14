<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendedores', function (Blueprint $table) {
            $table->increments('id_vendedor');
            $table->string('nome_vendedor', 255);
            $table->string('cpf', 14)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->decimal('comissao', 5, 2)->default(0);
            $table->boolean('status')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendedores');
    }
};