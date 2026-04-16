<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tabelas de estoque — existiam no legado, faltavam migrations no V3.
     * estoques, item_estoques, reg_estoques com schema idêntico ao legado.
     */
    public function up(): void
    {
        // --- estoques ---
        if (!Schema::hasTable('estoques')) {
            Schema::create('estoques', function (Blueprint $table) {
                $table->increments('id');
                $table->string('ref_produto', 255);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // --- item_estoques ---
        if (!Schema::hasTable('item_estoques')) {
            Schema::create('item_estoques', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('estoque_id');
                $table->tinyInteger('estampa')->default(0);
                $table->tinyInteger('estampa_lisa')->default(0);
                $table->integer('lisa')->default(0);
                // Tamanhos
                $table->integer('tam_p')->default(0);
                $table->integer('tam_m')->default(0);
                $table->integer('tam_g')->default(0);
                $table->integer('tam_u')->default(0);
                $table->integer('tam_rn')->default(0);
                $table->integer('ida_1')->default(0);
                $table->integer('ida_2')->default(0);
                $table->integer('ida_3')->default(0);
                $table->integer('ida_4')->default(0);
                $table->integer('ida_6')->default(0);
                $table->integer('ida_8')->default(0);
                $table->integer('ida_10')->default(0);
                $table->integer('ida_12')->default(0);
                // Outros
                $table->tinyInteger('masculino')->default(0);
                $table->tinyInteger('feminino')->default(0);
                $table->string('tipo_entrada', 255);
                $table->text('observacao')->nullable();
                // Reserva de estoque (V3)
                $table->integer('vendedor_id')->nullable();
                $table->tinyInteger('status_uso')->default(0)->comment('0=livre, 1=reservado');
                $table->timestamp('data_bloqueio')->nullable();
                // Timestamps
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
            });
        } else {
            // Adicionar colunas de reserva se não existirem
            if (!Schema::hasColumn('item_estoques', 'vendedor_id')) {
                Schema::table('item_estoques', function (Blueprint $table) {
                    $table->integer('vendedor_id')->nullable()->after('observacao');
                });
            }
            if (!Schema::hasColumn('item_estoques', 'status_uso')) {
                Schema::table('item_estoques', function (Blueprint $table) {
                    $table->tinyInteger('status_uso')->default(0)->after('vendedor_id');
                });
            }
            if (!Schema::hasColumn('item_estoques', 'data_bloqueio')) {
                Schema::table('item_estoques', function (Blueprint $table) {
                    $table->timestamp('data_bloqueio')->nullable()->after('status_uso');
                });
            }
        }

        // --- reg_estoques ---
        if (!Schema::hasTable('reg_estoques')) {
            Schema::create('reg_estoques', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('vendedor_id')->default(0);
                $table->integer('item_estoque_id')->default(0);
                $table->integer('estoque_id')->default(0);
                $table->integer('pedido_id')->default(0);
                $table->string('ref_estoque', 255)->nullable()->default('0');
                $table->text('descricao');
                // Colunas extras do V3
                $table->string('tipo_movimentacao', 20)->nullable()->comment('entrada, saida, reserva');
                $table->integer('quantidade')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        } else {
            // Adicionar colunas extras se não existirem
            if (!Schema::hasColumn('reg_estoques', 'tipo_movimentacao')) {
                Schema::table('reg_estoques', function (Blueprint $table) {
                    $table->string('tipo_movimentacao', 20)->nullable()->after('descricao');
                });
            }
            if (!Schema::hasColumn('reg_estoques', 'quantidade')) {
                Schema::table('reg_estoques', function (Blueprint $table) {
                    $table->integer('quantidade')->nullable()->after('tipo_movimentacao');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_estoques');
        Schema::dropIfExists('item_estoques');
        Schema::dropIfExists('estoques');
    }
};
