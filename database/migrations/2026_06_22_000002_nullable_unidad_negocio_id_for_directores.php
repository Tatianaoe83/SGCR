<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puesto_trabajos', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_negocio_id')->nullable()->change();
        });

        DB::table('puesto_trabajos')
            ->whereNotNull('unidades_negocio_ids')
            ->update(['unidad_negocio_id' => null]);
    }

    public function down(): void
    {
        DB::table('puesto_trabajos')
            ->whereNull('unidad_negocio_id')
            ->whereNotNull('unidades_negocio_ids')
            ->orderBy('id_puesto_trabajo')
            ->chunk(100, function ($puestos) {
                foreach ($puestos as $puesto) {
                    $ids = json_decode($puesto->unidades_negocio_ids, true) ?? [];
                    if (!empty($ids)) {
                        DB::table('puesto_trabajos')
                            ->where('id_puesto_trabajo', $puesto->id_puesto_trabajo)
                            ->update(['unidad_negocio_id' => (int) $ids[0]]);
                    }
                }
            });

        Schema::table('puesto_trabajos', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_negocio_id')->nullable(false)->change();
        });
    }
};
