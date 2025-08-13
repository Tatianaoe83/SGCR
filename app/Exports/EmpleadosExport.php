<?php

namespace App\Exports;

use App\Models\Empleados;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmpleadosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Empleados::with(['puestoTrabajo'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nombre(s) del Empleado',
            'Apellido Paterno',
            'Apellido Materno',
            'Puesto de Trabajo',
            'Correo',
            'Teléfono',
            'Fecha de Creación',
            'Última Actualización'
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->id_empleado,
            $row->nombres ?? 'N/A',
            $row->apellido_paterno ?? 'N/A',
            $row->apellido_materno ?? 'N/A',
            $row->puestoTrabajo->nombre ?? 'N/A',
            $row->correo ?? 'N/A',
            $row->telefono ?? 'N/A',
            $row->created_at->format('d/m/Y H:i:s'),
            $row->updated_at->format('d/m/Y H:i:s'),
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
        ];
    }
}
