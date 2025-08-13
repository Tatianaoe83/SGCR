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
        Schema::create('elementos', function (Blueprint $table) {
            $table->id('id_elemento');
            $table->unsignedBigInteger('tipo_elemento_id');
            $table->string('nombre_elemento');
            $table->unsignedBigInteger('tipo_proceso_id');
            $table->unsignedBigInteger('unidad_negocio_id');
            $table->integer('ubicacion_eje_x');
            $table->enum('control', ['interno', 'externo'])->default('interno');
            $table->string('folio_elemento');
            $table->decimal('version_elemento', 3, 1)->default(1.0);
            $table->date('fecha_elemento');
            $table->date('periodo_revision');
            $table->unsignedBigInteger('puesto_responsable_id');
            $table->text('puestos_relacionados')->nullable(); // JSON o texto para múltiples puestos
            $table->enum('es_formato', ['si', 'no'])->default('no');
            $table->string('archivo_formato')->nullable(); // Para subir archivo si es formato
            $table->unsignedBigInteger('puesto_ejecutor_id');
            $table->unsignedBigInteger('puesto_resguardo_id');
            $table->enum('medio_soporte', ['digital', 'fisico'])->default('digital');
            $table->string('ubicacion_resguardo');
            $table->date('periodo_resguardo');
            $table->unsignedBigInteger('elemento_padre_id')->nullable(); // Elemento al que pertenece
            $table->unsignedBigInteger('elemento_relacionado_id')->nullable(); // Elemento relacionado
            $table->boolean('correo_implementacion')->default(false);
            $table->boolean('correo_agradecimiento')->default(false);
            $table->timestamps();
            
            //  $table->foreignId('division_id')->constrained()->onDelete('cascade')->references('id_division');
            
            
            // Claves foráneas
            $table->foreign('tipo_elemento_id')->references('id_tipo_elemento')->on('tipo_elementos')->onDelete('cascade');
            $table->foreign('tipo_proceso_id')->references('id_tipo_proceso')->on('tipo_procesos')->onDelete('cascade');
            $table->foreign('unidad_negocio_id')->references('id_unidad_negocio')->on('unidad_negocios')->onDelete('cascade');
            $table->foreign('puesto_responsable_id')->references('id_puesto_trabajo')->on('puesto_trabajos')->onDelete('cascade');
            $table->foreign('puesto_ejecutor_id')->references('id_puesto_trabajo')->on('puesto_trabajos')->onDelete('cascade');
            $table->foreign('puesto_resguardo_id')->references('id_puesto_trabajo')->on('puesto_trabajos')->onDelete('cascade');
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elementos');
    }
};
