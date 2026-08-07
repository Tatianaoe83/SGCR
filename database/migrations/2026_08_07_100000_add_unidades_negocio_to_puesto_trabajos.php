<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Resto de un intento previo: el nivel ahora se deduce del nombre del puesto.
        if (Schema::hasColumn('puesto_trabajos', 'nivel_puesto')) {
            Schema::table('puesto_trabajos', function (Blueprint $table) {
                $table->dropColumn('nivel_puesto');
            });
        }

        if (!Schema::hasColumn('puesto_trabajos', 'unidades_negocio_ids')) {
            Schema::table('puesto_trabajos', function (Blueprint $table) {
                $table->json('unidades_negocio_ids')->nullable()->after('unidad_negocio_id');
            });
        }

        // Los puestos que ya existen deben verse completos en el index y quedar
        // preseleccionados al editarlos: su unidad actual pasa a la lista.
        DB::table('puesto_trabajos')
            ->select('id_puesto_trabajo', 'unidad_negocio_id', 'unidades_negocio_ids')
            ->orderBy('id_puesto_trabajo')
            ->get()
            ->each(function ($puesto) {
                if (!$this->listaVacia($puesto->unidades_negocio_ids)) {
                    return;
                }

                DB::table('puesto_trabajos')
                    ->where('id_puesto_trabajo', $puesto->id_puesto_trabajo)
                    ->update([
                        'unidades_negocio_ids' => json_encode(
                            $puesto->unidad_negocio_id ? [(int) $puesto->unidad_negocio_id] : []
                        ),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('puesto_trabajos', function (Blueprint $table) {
            $table->dropColumn('unidades_negocio_ids');
        });

        }

    private function listaVacia($valor): bool
    {
        if ($valor === null || $valor === '') {
            return true;
        }

        $decodificado = json_decode($valor, true);

        return !is_array($decodificado) || $decodificado === [];
    }
};
