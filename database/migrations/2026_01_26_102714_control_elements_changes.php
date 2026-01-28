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
        Schema::create('control_cambios', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_elemento');

            $table->string('FolioCambio', 50);

            $table->enum('Naturaleza', [
                'Propuesta de Mejora',
                'Auditoria Interna',
                'Auditoria Externa',
                'Revision Programada del SGC',
                'Por Indicacion',
                'Actualizacion del Elemento',
            ])->nullable();

            $table->text('Descripcion')->nullable();

            $table->enum('Afectacion', [
                'Nuevo',
                'Mejora',
                'Eliminado',
            ])->nullable();

            $table->text('RedaccionCambio')->nullable();

            $table->text('DetalleStatus')->nullable();

            $table->text('Seguimiento')->nullable();

            $table->unsignedTinyInteger('Prioridad')->nullable();

            $table->text('HistorialStatus')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->foreign('id_elemento')
                ->references('id_elemento')
                ->on('elementos')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_cambios');
    }
};