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
        Schema::create('encabezados_correo', function (Blueprint $table) {
            $table->id('id_encabezado');
            $table->string('nombre', 255);
            $table->text('asunto');
            $table->text('encabezado_html');
            $table->text('encabezado_texto');
            $table->string('tipo', 50); // 'acceso', 'implementacion', 'agradecimiento'
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encabezados_correo');
    }
};
