<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TipoElemento;
use App\Models\CampoRequeridoTipoElemento;

class CamposRequeridosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los tipos de elementos existentes
        $tiposElemento = TipoElemento::all();
        
        // Campos disponibles para elementos
        $camposDisponibles = [
            'nombre_elemento' => 'Nombre del Elemento',
            'tipo_proceso_id' => 'Tipo de Proceso',
            'unidad_negocio_id' => 'Unidad de Negocio',
            'ubicacion_eje_x' => 'Ubicación Eje X',
            'control' => 'Control',
            'folio_elemento' => 'Folio del Elemento',
            'version_elemento' => 'Versión del Elemento',
            'fecha_elemento' => 'Fecha del Elemento',
            'periodo_revision' => 'Período de Revisión',
            'puesto_responsable_id' => 'Puesto Responsable',
            'puestos_relacionados' => 'Puestos Relacionados',
            'es_formato' => 'Es Formato',
            'archivo_formato' => 'Archivo de Formato',
            'puesto_ejecutor_id' => 'Puesto Ejecutor',
            'puesto_resguardo_id' => 'Puesto de Resguardo',
            'medio_soporte' => 'Medio de Soporte',
            'ubicacion_resguardo' => 'Ubicación de Resguardo',
            'periodo_resguardo' => 'Período de Resguardo',
            'elemento_padre_id' => 'Elemento Padre',
            'elemento_relacionado_id' => 'Elemento Relacionado',
            'correo_implementacion' => 'Correo de Implementación',
            'correo_agradecimiento' => 'Correo de Agradecimiento'
        ];
        
        foreach ($tiposElemento as $tipo) {
            foreach ($camposDisponibles as $campoNombre => $campoLabel) {
                // Marcar algunos campos como requeridos por defecto
                $esRequerido = in_array($campoNombre, [
                    'nombre_elemento',
                    'tipo_proceso_id',
                    'unidad_negocio_id',
                    'puesto_responsable_id'
                ]);
                
                CampoRequeridoTipoElemento::create([
                    'tipo_elemento_id' => $tipo->id_tipo_elemento,
                    'campo_nombre' => $campoNombre,
                    'campo_label' => $campoLabel,
                    'es_requerido' => $esRequerido,
                    'es_obligatorio' => $esRequerido, // Los campos requeridos son obligatorios
                    'orden' => array_search($campoNombre, array_keys($camposDisponibles))
                ]);
            }
        }
    }
}
