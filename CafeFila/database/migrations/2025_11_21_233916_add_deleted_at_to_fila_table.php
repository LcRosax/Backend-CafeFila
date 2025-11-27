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
        Schema::table('fila', function (Blueprint $table) {
            // Adiciona a coluna 'deleted_at' para suportar Soft Deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fila', function (Blueprint $table) {
            // Remove a coluna 'deleted_at'
            $table->dropSoftDeletes();
        });
    }
};