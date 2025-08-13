<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Crear divisiones de ejemplo
        $division = \App\Models\Division::create([
            'nombre' => 'Tecnología'
        ]);

        // Crear unidad de negocio de ejemplo
        $unidadNegocio = \App\Models\UnidadNegocio::create([
            'nombre' => 'Desarrollo de Software',
            'division_id' => $division->id_division
        ]);

        // Crear área de ejemplo
        $area = \App\Models\Area::create([
            'nombre' => 'Desarrollo',
            'unidad_negocio_id' => $unidadNegocio->id_unidad_negocio
        ]);

        // Crear puestos de trabajo de ejemplo
        \App\Models\PuestoTrabajo::create([
            'nombre' => 'Gerente de Proyectos',
            'division_id' => $division->id_division,
            'unidad_negocio_id' => $unidadNegocio->id_unidad_negocio,
            'area_id' => $area->id_area
        ]);

        \App\Models\PuestoTrabajo::create([
            'nombre' => 'Analista de Sistemas',
            'division_id' => $division->id_division,
            'unidad_negocio_id' => $unidadNegocio->id_unidad_negocio,
            'area_id' => $area->id_area
        ]);

        \App\Models\PuestoTrabajo::create([
            'nombre' => 'Desarrollador Senior',
            'division_id' => $division->id_division,
            'unidad_negocio_id' => $unidadNegocio->id_unidad_negocio,
            'area_id' => $area->id_area
        ]);

        $this->call([
            DashboardTableSeeder::class,
            RolesAndPermissionsSeeder::class,
            TipoElementoSeeder::class,
        ]);
    }
}
