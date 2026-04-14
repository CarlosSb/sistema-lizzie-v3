<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id_cliente');
            $table->string('razao_social', 255);
            $table->string('nome_fantasia', 255)->nullable();
            $table->string('responsavel', 255)->nullable();
            $table->string('cpf_cnpj', 20)->nullable();
            $table->string('inscricao_estadual', 30)->nullable();
            $table->enum('pessoa', ['F', 'J'])->default('F');
            $table->string('email', 255)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->char('estado', 2)->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('contato_1', 20)->nullable();
            $table->string('contato_2', 20)->nullable();
            $table->string('contato_3', 20)->nullable();
            $table->string('rota', 100)->nullable();
            $table->boolean('status')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};