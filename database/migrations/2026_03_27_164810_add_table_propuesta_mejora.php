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
        Schema::create('propuestas_mejora', function (Blueprint $table) {
            $table->bigIncrements('id_propuesta');
            $table->string('titulo', 255);
            $table->unsignedBigInteger('id_elemento');
            $table->text('comentario')->nullable();
            $table->text('justificacion');
            $table->enum('estatus', ['Pendiente', 'Aprobado', 'Rechazado'])->default('Pendiente');
            $table->unsignedBigInteger('id_usuario_solicita')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->foreign('id_elemento')->references('id_elemento')->on('elementos');
            $table->foreign('id_usuario_solicita')->references('id_empleado')->on('empleados');
            $table->index('estatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propuestas_mejora');
    }
};
