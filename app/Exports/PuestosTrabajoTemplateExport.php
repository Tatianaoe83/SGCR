<?php

namespace App\Exports;

use App\Models\Division;
use App\Models\UnidadNegocio;
use App\Models\Area;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PuestosTrabajoTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithProperties
{
    /**
     * @return array
     */
    public function array(): array
    {
        // Obtener datos para las listas de validación
        $divisions = Division::all();
        $unidadesNegocio = UnidadNegocio::all();
        $areas = Area::all();

        // Crear filas de ejemplo
        $rows = [];
        
        // Fila de ejemplo
        $rows[] = [
            'Gerente de Proyectos', // Nombre del puesto
            $divisions->first()->nombre ?? 'División 1', // División
            $unidadesNegocio->first()->nombre ?? 'Unidad 1', // Unidad de Negocio
            $areas->first()->nombre ?? 'Área 1', // Área
        ];

        // Fila de ejemplo adicional
        if ($divisions->count() > 1 && $unidadesNegocio->count() > 1 && $areas->count() > 1) {
            $rows[] = [
                'Analista de Sistemas',
                $divisions->get(1)->nombre ?? 'División 2',
                $unidadesNegocio->get(1)->nombre ?? 'Unidad 2',
                $areas->get(1)->nombre ?? 'Área 2',
            ];
        }

        return $rows;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nombre del Puesto *',
            'División *',
            'Unidad de Negocio *',
            'Área *'
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6B46C1']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
            2 => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F3F4F6']
                ]
            ],
            3 => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F3F4F6']
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function properties(): array
    {
        return [
            'creator'        => 'SGCR',
            'lastModifiedBy' => 'SGCR',
            'title'         => 'Plantilla Puestos de Trabajo',
            'description'   => 'Plantilla para importar puestos de trabajo',
            'subject'       => 'Puestos de Trabajo',
            'keywords'      => 'puestos,trabajo,importar',
            'category'      => 'Plantilla',
            'manager'       => 'SGCR',
            'company'       => 'SGCR',
        ];
    }
}
