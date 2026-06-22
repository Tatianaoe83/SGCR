<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $registrosX = DB::table('campos_requeridos_tipo_elemento')
            ->where('campo_nombre', 'ubicacion_eje_x')
            ->whereIn('tipo_elemento_id', function ($query) {
                $query->select('id_tipo_elemento')->from('tipo_elementos');
            })
            ->get();

        foreach ($registrosX as $row) {
            $existe = DB::table('campos_requeridos_tipo_elemento')
                ->where('tipo_elemento_id', $row->tipo_elemento_id)
                ->where('campo_nombre', 'ubicacion_eje_y')
                ->exists();

            if ($existe) {
                continue;
            }

            DB::table('campos_requeridos_tipo_elemento')->insert([
                'tipo_elemento_id' => $row->tipo_elemento_id,
                'campo_nombre'     => 'ubicacion_eje_y',
                'campo_label'      => 'Ubicación Eje Y',
                'es_requerido'     => $row->es_requerido,
                'es_obligatorio'   => $row->es_obligatorio,
                'orden'            => ($row->orden ?? 0) + 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('campos_requeridos_tipo_elemento')
            ->where('campo_nombre', 'ubicacion_eje_y')
            ->delete();
    }
};
