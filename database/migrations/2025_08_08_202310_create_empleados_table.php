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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id('id_empleado');
            $table->string('nombres');
            $table->string('apellido_materno');
            $table->string('apellido_paterno');
            $table->unsignedBigInteger('puesto_trabajo_id');
            $table->foreign('puesto_trabajo_id')->references('id_puesto_trabajo')->on('puesto_trabajos')->onDelete('cascade');
            $table->string('correo');
            $table->string('telefono');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
