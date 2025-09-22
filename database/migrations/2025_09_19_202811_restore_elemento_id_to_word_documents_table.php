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
            // Solo agregar el campo estado si no existe
            if (!Schema::hasColumn('word_documents', 'estado')) {
                $table->enum('estado', ['procesado', 'error', 'pendiente'])->default('pendiente')->after('updated_at');
            }
            
            // Agregar índice para elemento_id si no existe
            if (Schema::hasColumn('word_documents', 'elemento_id')) {
                try {
                    $table->index('elemento_id');
                } catch (\Exception $e) {
                    // Índice ya existe, ignorar
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('word_documents', function (Blueprint $table) {
            // Eliminar los campos restaurados
            $table->dropIndex(['elemento_id']);
            $table->dropColumn(['elemento_id', 'estado']);
        });
    }
};
