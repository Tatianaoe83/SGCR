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
        Schema::create('cuerpos_correo', function (Blueprint $table) {
            $table->id('id_cuerpo');
            $table->string('nombre', 255);
            $table->text('cuerpo_html');
            $table->text('cuerpo_texto');
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
        Schema::dropIfExists('cuerpos_correo');
    }
};
