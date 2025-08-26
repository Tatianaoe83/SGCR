<?php

namespace App\Exports;

use App\Models\Elemento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ElementosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Elemento::with(['tipoElemento', 'tipoProceso', 'unidadNegocio', 'puestoResponsable', 'puestoEjecutor', 'puestoResguardo', 'puestoResguardo', 'elementoPadre', 'elementoRelacionado'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'NombreTipoElemento',
            'NombreElemento',
            'NombreProceso',
            'UnidadNegocio',
            'UbicacionX',
            'Control',
            'FolioElemento',
            'VersionElemento',
            'FechaElemento',
            'PeriodoRevision',
            'PuestoResponsable',
            'PuestosRelacionados',
            'EsFormato',
            'ArchivoFormato',
            'PuestoEjecutor',
            'PuestoResguardo',
            'MedioSoporte',
            'UbicacionResguardo',
            'PeriodoResguardo',
            'ElementosPadre',
            'ElementosRelacionados',
            'CorreoImplementacion',
            'CorreoAgradecimiento',
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->tipoElemento->nombre ?? 'N/A',
            $row->nombre_elemento ?? 'N/A',
            $row->tipoProceso->nombre ?? 'N/A',
            $row->unidadNegocio->nombre ?? 'N/A',
            $row->ubicacion_eje_x ?? 'N/A',
            $row->control ?? 'N/A',
            $row->folio_elemento ?? 'N/A',
            $row->version_elemento ?? 'N/A',
            $row->fecha_elemento,
            $row->periodo_revision,
            $row->puestoResponsable->nombre ?? 'N/A',
            $row->puestos_relacionados ?? 'N/A',
            $row->es_formato ? 'Si' : 'No',
            $row->archivo_formato ?? 'N/A',
            $row->puestoEjecutor->nombre ?? 'N/A',
            $row->puestoResguardo->nombre ?? 'N/A',
            $row->medio_soporte ?? 'N/A',
            $row->ubicacion_resguardo ?? 'N/A',
            $row->periodo_resguardo,
            $row->elementoPadre ?? 'N/A',
            $row->elementoRelacionado ?? 'N/A',
            $row->correo_implementacion ? '1' : '0',
            $row->correo_agradecimiento ? '1' : '0',
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:W1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:W1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('6B46C1');

        return [];
    }
}
