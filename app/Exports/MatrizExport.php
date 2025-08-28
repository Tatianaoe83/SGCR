<?php

namespace App\Exports;

use App\Models\Elemento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MatrizExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $puestosRelacionados;

    public function __construct($puestosRelacionados = [])
    {
        $this->puestosRelacionados = $puestosRelacionados;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if (empty($this->puestosRelacionados)) {
            return collect([]);
        }

        return Elemento::with([
            'tipoElemento',
            'tipoProceso',
            'unidadNegocio',
            'puestoResponsable',
            'puestoEjecutor',
            'puestoResguardo',
            'elementoPadre',
            'elementoRelacionado'
        ])
        ->whereHas('tipoElemento', function ($query) {
            $query->where('nombre', 'Procedimiento');
        })
        ->where(function ($q) {
            foreach ($this->puestosRelacionados as $puestoId) {
                $q->orWhereJsonContains('puestos_relacionados', (string) $puestoId);
            }
        })
        ->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nombre del Elemento',
            'Tipo de Elemento',
            'Tipo de Proceso',
            'Unidad de Negocio',
            'Puesto Responsable',
            'Puesto Ejecutor',
            'Puesto Resguardo',
            'Folio del Elemento',
            'Versión del Elemento',
            'Fecha del Elemento',
            'Período de Revisión',
            'Control',
            'Ubicación Eje X',
            'Es Formato',
            'Archivo Formato',
            'Medio de Soporte',
            'Ubicación de Resguardo',
            'Período de Resguardo',
            'Correo de Implementación',
            'Correo de Agradecimiento'
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->nombre_elemento ?? 'N/A',
            $row->tipoElemento->nombre ?? 'N/A',
            $row->tipoProceso->nombre ?? 'N/A',
            $row->unidadNegocio->nombre ?? 'N/A',
            $row->puestoResponsable->nombre ?? 'N/A',
            $row->puestoEjecutor->nombre ?? 'N/A',
            $row->puestoResguardo->nombre ?? 'N/A',
            $row->folio_elemento ?? 'N/A',
            $row->version_elemento ?? 'N/A',
            $row->fecha_elemento ? date('d/m/Y', strtotime($row->fecha_elemento)) : 'N/A',
            $row->periodo_revision ? date('d/m/Y', strtotime($row->periodo_revision)) : 'N/A',
            $row->control ?? 'N/A',
            $row->ubicacion_eje_x ?? 'N/A',
            $row->es_formato ? 'Sí' : 'No',
            $row->archivo_formato ?? 'N/A',
            $row->medio_soporte ?? 'N/A',
            $row->ubicacion_resguardo ?? 'N/A',
            $row->periodo_resguardo ? date('d/m/Y', strtotime($row->periodo_resguardo)) : 'N/A',
            $row->correo_implementacion ? 'Sí' : 'No',
            $row->correo_agradecimiento ? 'Sí' : 'No'
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:T1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:T1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('6B46C1');

        return [];
    }
}
