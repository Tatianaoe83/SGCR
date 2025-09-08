<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class MatrizFiltroExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithDrawings, WithCustomStartCell
{
    protected array $rows;

    public function __construct(array $rows)
    {
        if (empty($rows)) {
            throw new \InvalidArgumentException('El arreglo de datos (rows) es obligatorio y no puede estar vacío.');
        }
        $this->rows = $rows;
    }

    public function collection()
    {
        $norm = array_map(function ($r) {
            return [
                'Proceso'       => $r['Proceso']       ?? '',
                'Folio'         => $r['Folio']         ?? '',
                'Procedimiento' => $r['Procedimiento'] ?? '',
                'Puesto'        => $r['Puesto']        ?? '',
                'Participación' => $r['Participacion'] ?? $r['Participación'] ?? '',
            ];
        }, $this->rows);

        return collect($norm);
    }

    public function headings(): array
    {
        return ['Proceso', 'Folio', 'Procedimiento', 'Puesto', 'Participación'];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:B2');
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(20);

        $sheet->getRowDimension(1)->setRowHeight(38);
        $sheet->getRowDimension(2)->setRowHeight(38);
        $sheet->getRowDimension(3)->setRowHeight(24);

        $lastColumn = $sheet->getHighestColumn();
        $lastRow    = $sheet->getHighestRow();

        $sheet->setCellValue('C1', 'Leyenda de Prefijos:');
        $sheet->setCellValue('C2', 'R = Responsable | E = Ejecutor | A = Resguardo | PR = Relacionado | PM = Adicional');
        $sheet->mergeCells("C1:{$lastColumn}1");
        $sheet->mergeCells("C2:{$lastColumn}2");

        $sheet->getStyle("C1:C2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '444444'],
            ],
        ]);

        $sheet->setCellValue('A3', 'Matriz de Responsabilidades por Puesto de Trabajo');
        $sheet->mergeCells("A3:{$lastColumn}3");

        $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '002060'],
            ],
        ]);

        $sheet->getStyle("A4:E4")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '002060']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $lastDataRow = $sheet->getHighestRow();
        $sheet->getStyle("A4:E{$lastDataRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
        ]);
        $sheet->getStyle("A5:E{$lastDataRow}")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->freezePane('A5');
        $sheet->setAutoFilter('A4:E4');
    }

    public function drawings()
    {
        $colPx = fn(float $w): int => (int)floor($w * 7 + 5);
        $rowPx = fn(float $pt): int => (int)round($pt * (96 / 72));

        $widthA = 10.0;
        $widthB = 17.0;
        $height1 = 38.0;
        $height2 = 38.0;

        $targetWidth  = $colPx($widthA) + $colPx($widthB);
        $targetHeight = $rowPx($height1) + $rowPx($height2);

        $drawing = new Drawing();
        $drawing->setPath(public_path('images/Logo-azul.png'));
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(0);
        $drawing->setOffsetY(0);
        $drawing->setResizeProportional(false);
        $drawing->setWidth($targetWidth);
        $drawing->setHeight($targetHeight);

        return [$drawing];
    }
}
