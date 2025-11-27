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
        Schema::create('fila', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('usuario_id'); 
            $table->integer('posicao');

        
            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('usuarios')
                  ->onDelete('cascade'); 

            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fila');
    }
};
