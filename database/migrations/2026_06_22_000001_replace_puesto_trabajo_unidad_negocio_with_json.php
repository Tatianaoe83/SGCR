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
            $table->json('unidades_negocio_ids')->nullable()->after('unidad_negocio_id');
        });

        if (Schema::hasTable('puesto_trabajo_unidad_negocio')) {
            $puestoIds = DB::table('puesto_trabajo_unidad_negocio')
                ->distinct()
                ->pluck('puesto_trabajo_id');

            foreach ($puestoIds as $puestoId) {
                $ids = DB::table('puesto_trabajo_unidad_negocio')
                    ->where('puesto_trabajo_id', $puestoId)
                    ->pluck('unidad_negocio_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                DB::table('puesto_trabajos')
                    ->where('id_puesto_trabajo', $puestoId)
                    ->update(['unidades_negocio_ids' => json_encode($ids)]);
            }

            Schema::dropIfExists('puesto_trabajo_unidad_negocio');
        }
    }

    public function down(): void
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

        DB::table('puesto_trabajos')
            ->whereNotNull('unidades_negocio_ids')
            ->orderBy('id_puesto_trabajo')
            ->chunk(100, function ($puestos) {
                foreach ($puestos as $puesto) {
                    $ids = json_decode($puesto->unidades_negocio_ids, true) ?? [];

                    foreach ($ids as $unidadId) {
                        DB::table('puesto_trabajo_unidad_negocio')->insert([
                            'puesto_trabajo_id'  => $puesto->id_puesto_trabajo,
                            'unidad_negocio_id'  => (int) $unidadId,
                        ]);
                    }
                }
            });

        Schema::table('puesto_trabajos', function (Blueprint $table) {
            $table->dropColumn('unidades_negocio_ids');
        });
    }
};
