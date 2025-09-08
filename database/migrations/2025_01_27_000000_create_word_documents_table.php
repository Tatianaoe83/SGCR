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
        Schema::create('word_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_archivo');
            $table->string('nombre_original');
            $table->string('ruta_archivo');
            $table->text('contenido_texto')->nullable();
            $table->json('contenido_estructurado')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('version')->nullable();
            $table->string('autor')->nullable();
            $table->timestamp('fecha_creacion')->nullable();
            $table->timestamp('fecha_modificacion')->nullable();
            $table->text('metadatos')->nullable();
            $table->enum('estado', ['procesado', 'error', 'pendiente'])->default('pendiente');
            $table->text('error_mensaje')->nullable();
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index(['tipo_documento', 'estado']);
            $table->index('fecha_creacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_documents');
    }
};
