<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        

    Schema::create('compras', function (Blueprint $table) {
        $table->id();
        $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
        $table->string('item');
        $table->integer('quantidade');
        $table->timestamp('data_compra')->useCurrent();
        $table->unsignedBigInteger('ultima_alteracao_por')->nullable();
        $table->timestamp('ultima_alteracao_em')->nullable();
        $table->foreign('ultima_alteracao_por')->references('id')->on('usuarios')->onDelete('set null');
    });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
