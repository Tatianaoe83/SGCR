<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puesto_trabajo_unidad_negocio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('puesto_trabajo_id');
            $table->unsignedBigInteger('unidad_negocio_id');

            $table->foreign('puesto_trabajo_id')
                  ->references('id_puesto_trabajo')
                  ->on('puesto_trabajos')
                  ->onDelete('cascade');

            $table->foreign('unidad_negocio_id')
                  ->references('id_unidad_negocio')
                  ->on('unidad_negocios')
                  ->onDelete('cascade');

            $table->unique(['puesto_trabajo_id', 'unidad_negocio_id'], 'ptun_puesto_unidad_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puesto_trabajo_unidad_negocio');
    }
};
