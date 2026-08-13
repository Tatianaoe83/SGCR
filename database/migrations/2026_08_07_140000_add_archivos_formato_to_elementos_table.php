<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('elementos', 'archivos_formato')) {
            Schema::table('elementos', function (Blueprint $table) {
                $table->json('archivos_formato')->nullable()->after('archivo_formato');
            });
        }

        // archivo_formato se conserva apuntando al primer archivo, porque lo siguen
        // leyendo la exportacion, la importacion y el generador de documentos.
        DB::table('elementos')
            ->select('id_elemento', 'archivo_formato', 'archivos_formato')
            ->whereNotNull('archivo_formato')
            ->orderBy('id_elemento')
            ->get()
            ->each(function ($elemento) {
                if (!$this->listaVacia($elemento->archivos_formato)) {
                    return;
                }

                DB::table('elementos')
                    ->where('id_elemento', $elemento->id_elemento)
                    ->update([
                        'archivos_formato' => json_encode([$elemento->archivo_formato]),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->dropColumn('archivos_formato');
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
