<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('acao'); // create, update, delete, login, logout
            $table->string('tabela'); // pedidos, clientes, produtos, etc
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nome')->nullable();
            $table->text('dados_anteriores')->nullable();
            $table->text('dados_novos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index('usuario_id');
            $table->index(['tabela', 'registro_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};