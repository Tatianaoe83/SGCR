<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empleados;
use App\Models\PuestoTrabajo;

class EmpleadosTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener puestos de trabajo existentes
        $puestos = PuestoTrabajo::all();
        
        if ($puestos->isEmpty()) {
            $this->command->error('No hay puestos de trabajo disponibles. Crea algunos puestos primero.');
            return;
        }
        
        // Crear empleados de prueba
        $empleados = [
            [
                'nombres' => 'Juan Carlos',
                'apellido_paterno' => 'García',
                'apellido_materno' => 'López',
                'correo' => 'juan.garcia@empresa.com',
                'telefono' => '555-0101',
                'puesto_trabajo_id' => $puestos->first()->id_puesto_trabajo
            ],
            [
                'nombres' => 'María Elena',
                'apellido_paterno' => 'Rodríguez',
                'apellido_materno' => 'Martínez',
                'correo' => 'maria.rodriguez@empresa.com',
                'telefono' => '555-0102',
                'puesto_trabajo_id' => $puestos->first()->id_puesto_trabajo
            ],
            [
                'nombres' => 'Carlos Alberto',
                'apellido_paterno' => 'Hernández',
                'apellido_materno' => 'González',
                'correo' => 'carlos.hernandez@empresa.com',
                'telefono' => '555-0103',
                'puesto_trabajo_id' => $puestos->first()->id_puesto_trabajo
            ]
        ];
        
        foreach ($empleados as $empleadoData) {
            // Verificar si el empleado ya existe
            $existente = Empleados::where('correo', $empleadoData['correo'])->first();
            if (!$existente) {
                Empleados::create($empleadoData);
                $this->command->info("Empleado creado: {$empleadoData['nombres']} {$empleadoData['apellido_paterno']}");
            } else {
                $this->command->info("Empleado ya existe: {$empleadoData['nombres']} {$empleadoData['apellido_paterno']}");
            }
        }
        
        $this->command->info('Seeder de empleados de prueba completado.');
    }
}
