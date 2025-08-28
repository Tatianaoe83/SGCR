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
        Schema::create('campos_requeridos_tipo_elemento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_elemento_id');
            $table->string('campo_nombre');
            $table->string('campo_label');
            $table->boolean('es_requerido')->default(false);
            $table->boolean('es_obligatorio')->default(false);
            $table->integer('orden')->default(0);
            $table->timestamps();
            
            // Clave foránea
            $table->foreign('tipo_elemento_id')->references('id_tipo_elemento')->on('tipo_elementos')->onDelete('cascade');
            
            // Índices con nombres más cortos
            $table->index(['tipo_elemento_id', 'campo_nombre'], 'idx_tipo_campo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campos_requeridos_tipo_elemento');
    }
};
