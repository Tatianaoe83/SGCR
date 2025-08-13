<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoElemento;

class TipoElementoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            [
                'nombre' => 'Procedimiento',
                'descripcion' => 'Documento que describe paso a paso cómo realizar una actividad o proceso específico.'
            ],
            [
                'nombre' => 'Instrucción de Trabajo',
                'descripcion' => 'Documento que proporciona instrucciones detalladas para realizar una tarea específica.'
            ],
            [
                'nombre' => 'Formato',
                'descripcion' => 'Plantilla o documento estructurado para recopilar información de manera estandarizada.'
            ],
            [
                'nombre' => 'Manual',
                'descripcion' => 'Documento que contiene información completa sobre un tema, proceso o sistema.'
            ],
            [
                'nombre' => 'Política',
                'descripcion' => 'Declaración de principios y directrices que rigen la toma de decisiones y acciones.'
            ],
            [
                'nombre' => 'Registro',
                'descripcion' => 'Documento que contiene información sobre actividades realizadas o resultados obtenidos.'
            ],
            [
                'nombre' => 'Plan',
                'descripcion' => 'Documento que describe objetivos, estrategias y acciones para alcanzar metas específicas.'
            ],
            [
                'nombre' => 'Reporte',
                'descripcion' => 'Documento que presenta información, análisis o resultados de una actividad o investigación.'
            ],
            [
                'nombre' => 'Checklist',
                'descripcion' => 'Lista de verificación que asegura que todos los pasos o elementos requeridos sean completados.'
            ],
            [
                'nombre' => 'Diagrama de Flujo',
                'descripcion' => 'Representación gráfica de un proceso o flujo de trabajo.'
            ]
        ];

        foreach ($tipos as $tipo) {
            TipoElemento::create($tipo);
        }
    }
}
