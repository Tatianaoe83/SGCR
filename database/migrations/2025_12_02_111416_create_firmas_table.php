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
        Schema::create('firmas', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->unsignedBigInteger('puestoTrabajo_id');
            $table->unsignedBigInteger('empleado_id');
            $table->unsignedBigInteger('elemento_id');
            $table->enum('tipo', ['Participantes', 'Responsables'])->default('Participantes');
            $table->timestamp('fecha', 0)->useCurrent();
            $table->enum('estatus', ['Aprobado', 'Rechazado', 'Pendiente'])->default('Pendiente');
            $table->timestamps();

            $table->foreign('puestoTrabajo_id')->references('id_puesto_trabajo')->on('puesto_trabajos')->onDelete('cascade');
            $table->foreign('empleado_id')->references('id_empleado')->on('empleados')->onDelete('cascade');
            $table->foreign('elemento_id')->references('id_elemento')->on('elementos')->onDelete('cascade');
        });
    }
};
