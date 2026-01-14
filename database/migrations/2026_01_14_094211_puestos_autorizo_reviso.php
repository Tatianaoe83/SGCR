<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('firmas', function (Blueprint $table) {

            $table->unsignedBigInteger('puestoTrabajo_id')->nullable()->change();
            $table->unsignedBigInteger('empleado_id')->nullable()->change();
            $table->unsignedBigInteger('elemento_id')->nullable()->change();
            $table->timestamp('fecha')->nullable()->change();
        });

        DB::statement("
            ALTER TABLE firmas
            MODIFY tipo ENUM(
                'Participante',
                'Responsable',
                'Autorizo',
                'Reviso'
            ) NULL DEFAULT NULL
        ");
    }
};
