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
        Schema::table('word_documents', function (Blueprint $table) {
            // Eliminar solo los campos que existen
            $table->dropColumn([
                'elemento_id',
                'estado'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('word_documents', function (Blueprint $table) {
            // Restaurar los campos eliminados
            $table->integer('elemento_id')->after('id');
            $table->enum('estado', ['procesado', 'error', 'pendiente'])->default('pendiente')->after('contenido_texto');
        });
    }
};
