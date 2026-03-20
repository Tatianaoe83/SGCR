<?php

namespace App\Exports;

use App\Models\ControlCambio;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ControlCambiosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, WithEvents, WithCustomStartCell
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ControlCambio::with([
            'elemento',
            'elemento.tipoElemento',
            'elemento.tipoProceso',
            'elemento.puestoResponsable',
            'elemento.elementoPadre',
            'elemento.elementoPadre.puestoResponsable'
        ])
        ->orderByDesc('created_at')
        ->get();
    }

    public function headings(): array
    {
        return [
            'FOLIO DE CAMBIO',
            'ABREVIATURA PARA FOLIO',
            'AÑO ACTUAL MÁS 3',
            'CONSECUTIVO PARA FOLIO DE CAMBIO',
            'SUMA DE AÑO ACTUAL',
            'NATURALEZA',
            'DESCRIPCIÓN DE LA NATURALEZA',
            'AFECTACIÓN',
            'REDACCIÓN DEL CAMBIO QUE SUFRIÓ',
            'TIPO DE ELEMENTO',
            'TIPO DE PROCESO',
            'FOLIO ELEMENTO',
            'NOMBRE DEL ELEMENTO',
            'PROCESO AL QUE PERTENECE EL ELEMENTO',
            'PROCEDIMIENTO AL QUE PERTENECE EL ELEMENTO',
            //'LINEA DE ACCION DEL PROCESO',
            'RESPONSABLE DEL PROCESO',
            'RESPONSABLE DEL PROCEDIMIENTO',
            'ESTATUS',
            'DETALLE DEL ESTATUS',
            'SEGUIMIENTO',
            'PRIORIDAD',
            'HISTORIAL DE ESTATUS',
        ];
    }

    public function map($controlCambio): array
    {
        $folio = $controlCambio->FolioCambio ?? '';
        $abreviatura = '';
        $anoMas3 = '';
        $consecutivo = '';
        $sumaAno = '';

        if ($folio) {
            preg_match('/^([A-Z]+)/', $folio, $matchesAbrev);
            $abreviatura = $matchesAbrev[1] ?? '';

            preg_match('/([0-9]+)$/', $folio, $matchesNum);
            $parteNumerica = $matchesNum[1] ?? '';

            if (strlen($parteNumerica) >= 3) {
                $ano = substr($parteNumerica, 0, 2);
                $consecutivo = ltrim(substr($parteNumerica, 2), '0') ?: '0';
                
                $anoMas3 = $ano . '000';
                
                $sumaAno = $parteNumerica;
            }
        }

        return [
            $folio,
            $abreviatura,
            $anoMas3,
            $consecutivo,
            $sumaAno,
            $controlCambio->Naturaleza ?? 'Sin Naturaleza',
            $controlCambio->Descripcion ?? 'Sin Descripción',
            $controlCambio->Afectacion ?? 'Sin Afectación',
            $controlCambio->RedaccionCambio ?? 'Sin Redacción',
            $controlCambio->elemento->tipoElemento->nombre ?? 'Sin Tipo de Elemento',
            $controlCambio->elemento->tipoProceso->nombre ?? 'Sin Tipo de Proceso',
            $controlCambio->elemento->folio_elemento ?? 'Sin Folio de Elemento',
            $controlCambio->elemento->nombre_elemento ?? 'Sin Nombre de Elemento',
            $controlCambio->elemento->tipoProceso->nombre ?? 'Sin Tipo de Proceso',
            $controlCambio->elemento->elementoPadre->nombre_elemento ?? 'No Aplica',
            $controlCambio->elemento->puestoResponsable->nombre ?? 'Sin Responsable',
            $controlCambio->elemento->elementoPadre->puestoResponsable->nombre ?? 'Sin Responsable de Elemento Padre',
            $controlCambio->elemento->status ?? 'Sin Estatus',
            $controlCambio->DetalleStatus ?? 'Sin Detalle de Estatus',
            $controlCambio->Seguimiento ?? 'Sin Seguimiento',
            $controlCambio->Prioridad ?? 'Sin Prioridad',
            $controlCambio->HistorialStatus ?? 'Sin Historial de Estatus',
        ];
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function styles(Worksheet $sheet)
    {
        // Los estilos del header se aplican en registerEvents()
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 20,
            'C' => 18,
            'D' => 25,
            'E' => 18,
            'F' => 22,
            'G' => 35,
            'H' => 22,
            'I' => 40,
            'J' => 22,
            'K' => 22,
            'L' => 18,
            'M' => 35,
            'N' => 30,
            'O' => 35,
            'P' => 30,
            'Q' => 30,
            'R' => 15,
            'S' => 30,
            'T' => 30,
            'U' => 12,
            'V' => 40,
        ];
    }

    public function title(): string
    {
        return 'Control de Cambios';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Logo en las primeras filas
                $logoPath = public_path('images/Logo-azul.png');
                if (file_exists($logoPath)) {
                    // Altura de las filas del logo
                    $sheet->getRowDimension(1)->setRowHeight(25);
                    $sheet->getRowDimension(2)->setRowHeight(25);
                    $sheet->getRowDimension(3)->setRowHeight(25);
                    $sheet->getRowDimension(4)->setRowHeight(8);

                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setDescription('PROSER Grupo Constructor');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(70);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(8);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }

                // Altura del header (fila 5 por el logo)
                $sheet->getRowDimension(5)->setRowHeight(40);
                
                // Obtener la última fila y columna con datos
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                // 1. A-E: Verde (Folio de cambio hasta Suma de año actual)
                $sheet->getStyle('A5:E5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'] // Verde
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                        'name' => 'Segoe UI'
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // 2. F-I: Azul fuerte (Naturaleza hasta Redacción del cambio)
                $sheet->getStyle('F5:I5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E40AF'] // Azul fuerte
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                        'name' => 'Segoe UI'
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // 3. J-M: Verde (Tipo de elemento hasta Nombre del elemento)
                $sheet->getStyle('J5:M5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'] // Verde
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                        'name' => 'Segoe UI'
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // 4. N: Rosado/color piel (Proceso al que pertenece)
                $sheet->getStyle('N5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9A8D4'] // Rosado
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                        'name' => 'Segoe UI'
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // 5. O: Café (Procedimiento al que pertenece)
                $sheet->getStyle('O5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '92400E'] // Café
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                        'name' => 'Segoe UI'
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // 6. P: Verde (Responsable del proceso)
                $sheet->getStyle('P5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'] // Verde
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                        'name' => 'Segoe UI'
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // 7. Q: Naranja (Responsable del procedimiento)
                $sheet->getStyle('Q5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F97316'] // Naranja
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                        'name' => 'Segoe UI'
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // 8. R: Verde (Estatus)
                $sheet->getStyle('R5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'] // Verde
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                        'name' => 'Segoe UI'
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // 9. S-V: Azul fuerte (Detalle de estatus hasta Historial)
                $sheet->getStyle('S5:V5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E40AF'] // Azul fuerte
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                        'name' => 'Segoe UI'
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                // Aplicar estilos base a todas las celdas de datos con borders
                $dataRange = 'A6:' . $highestColumn . $highestRow;
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'font' => [
                        'size' => 11,
                        'name' => 'Segoe UI',
                        'color' => ['rgb' => '000000']
                    ]
                ]);

                // Aplicar colores alternados: Blanco y Azul cielo
                for ($row = 6; $row <= $highestRow; $row++) {
                    $color = ($row % 2 == 0) ? 'FFFFFF' : 'DBEAFE'; // Blanco y Azul cielo
                    
                    $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color]
                        ]
                    ]);
                }

                // Aplicar color a columna de prioridad basado en valor (mantener)
                for ($row = 6; $row <= $highestRow; $row++) {
                    $prioridad = $sheet->getCell('U' . $row)->getValue();
                    
                    $colorConfig = match($prioridad) {
                        1 => ['bg' => '10B981', 'text' => 'FFFFFF'], // Verde
                        2 => ['bg' => 'F59E0B', 'text' => 'FFFFFF'], // Amarillo
                        3 => ['bg' => 'F97316', 'text' => 'FFFFFF'], // Naranja
                        4 => ['bg' => 'EF4444', 'text' => 'FFFFFF'], // Rojo
                        default => null
                    };

                    if ($colorConfig) {
                        $sheet->getStyle('U' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $colorConfig['bg']]
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => $colorConfig['text']],
                                'size' => 11
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER
                            ]
                        ]);
                    }
                }

                // Auto-filtro en el header
                $sheet->setAutoFilter('A5:' . $highestColumn . '5');
                
                // Congelar paneles (freeze panes) después del header
                $sheet->freezePane('A6');
            }
        ];
    }
}
