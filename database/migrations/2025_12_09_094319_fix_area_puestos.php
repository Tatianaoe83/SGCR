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
        Schema::table('puesto_trabajos', function (Blueprint $table) {
            $table->json('areas_ids')->nullable()->after('unidad_negocio_id');
        });

        DB::table('puesto_trabajos')
            ->select('id_puesto_trabajo', 'area_id')
            ->whereNotNull('area_id')
            ->orderBy('id_puesto_trabajo')
            ->chunk(100, function ($puestos) {
                foreach ($puestos as $puesto) {
                    DB::table('puesto_trabajos')
                        ->where('id_puesto_trabajo', $puesto->id_puesto_trabajo)
                        ->update([
                            'areas_ids' => json_encode([(int)$puesto->area_id]),
                        ]);
                }
            });

        Schema::table('puesto_trabajos', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn('area_id');

            $table->json('areas_ids')->nullable(false)->change();
        });
    }
};
