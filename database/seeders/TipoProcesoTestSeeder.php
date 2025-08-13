<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TipoProceso;

class TipoProcesoTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposProceso = [
            [
                'nombre' => 'Proceso Principal',
                'nivel' => 1.0,
            ],
            [
                'nombre' => 'Subproceso A',
                'nivel' => 1.5,
            ],
            [
                'nombre' => 'Subproceso B',
                'nivel' => 2.0,
            ],
            [
                'nombre' => 'Subproceso C',
                'nivel' => 2.5,
            ],
            [
                'nombre' => 'Proceso Secundario',
                'nivel' => 3.0,
            ],
        ];

        foreach ($tiposProceso as $tipoProceso) {
            TipoProceso::create($tipoProceso);
        }

        $this->command->info('Tipos de proceso de prueba creados exitosamente con niveles decimales.');
    }
}
