<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmpleadosTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithProperties
{
    /**
     * @return array
     */
    public function array(): array
    {
        
        // Crear filas de ejemplo
        $rows = [];
        
        // Fila de ejemplo
        $rows[] = [
            'Juan',
                'García',
                'López',
                'Analista de Sistemas', // Puesto de Trabajo
                'garcia@proser.com.mx',
                '9999999999',
        ];
        return $rows;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nombre(s) del Empleado *',
            'Apellido Paterno *',
            'Apellido Materno *',
            'Puesto de Trabajo *',
            'Correo *',
            'Teléfono *',
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
            'title'         => 'Plantilla Empleados',
            'description'   => 'Plantilla para importar empleados',
            'subject'       => 'Empleados',
            'keywords'      => 'empleados,importar',
            'category'      => 'Plantilla',
            'manager'       => 'SGCR',
            'company'       => 'SGCR',
        ];
    }
}
