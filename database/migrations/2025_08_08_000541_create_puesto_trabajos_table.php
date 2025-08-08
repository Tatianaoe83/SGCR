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
        Schema::create('puesto_trabajos', function (Blueprint $table) {
            $table->id('id_puesto_trabajo');
            $table->string('nombre');
            $table->unsignedBigInteger('division_id');
            $table->unsignedBigInteger('unidad_negocio_id');
            $table->unsignedBigInteger('area_id');
            $table->timestamps();
            
            $table->foreign('division_id')->references('id_division')->on('divisions')->onDelete('cascade');
            $table->foreign('unidad_negocio_id')->references('id_unidad_negocio')->on('unidad_negocios')->onDelete('cascade');
            $table->foreign('area_id')->references('id_area')->on('area')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puesto_trabajos');
    }
};
