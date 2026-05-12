<?php

namespace App\Services;

use App\Models\Elemento;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi as TcpdfFpdi;

/**
 * Generates an annotated PDF copy from an existing PDF and an array
 * of annotation objects (page, x_pct, y_pct, content).
 *
 * Uses FPDI + TCPDF so the original file is never modified.
 */
class PdfAnnotationService
{
    /** Radius of each annotation circle marker in mm */
    private const CIRCLE_RADIUS_MM = 3.8;

    /**
     * @param  string        $pdfStoragePath  Relative path on the 'public' Storage disk
     * @param  array         $annotations     [{page, x_pct, y_pct, content}, ...]
     * @param  string        $generalComment  Optional reviewer comment shown on legend page
     * @param  Elemento|null $elemento        When provided, filename follows the system naming convention
     * @return string  Storage path of the generated annotated PDF (never overwrites original)
     */
    public function generarPdfAnotado(
        string    $pdfStoragePath,
        array     $annotations,
        string    $generalComment = '',
        ?Elemento $elemento = null
    ): string {
        $absPath = Storage::disk('public')->path($pdfStoragePath);

        if (! is_file($absPath)) {
            throw new RuntimeException("PDF base no encontrado: {$pdfStoragePath}");
        }

        // TCPDF + FPDI in mm with UTF-8 unicode
        $pdf = new TcpdfFpdi('P', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($absPath);

        // Index annotations by page
        $byPage = [];
        foreach ($annotations as $i => $ann) {
            $page = max(1, (int) ($ann['page'] ?? 1));
            $byPage[$page][] = array_merge($ann, ['_num' => $i + 1]);
        }

        for ($p = 1; $p <= $pageCount; $p++) {
            $tpl  = $pdf->importPage($p);
            $size = $pdf->getTemplateSize($tpl);
            $w    = (float) $size['width'];
            $h    = (float) $size['height'];

            $pdf->AddPage($size['orientation'], [$w, $h]);
            $pdf->useTemplate($tpl, 0, 0, $w, $h);

            foreach ($byPage[$p] ?? [] as $ann) {
                $this->drawMarkerWithAnnotation($pdf, $ann, $w, $h);
            }
        }

        // No legend page — annotations are embedded as native PDF comments
        $name = $this->buildFileName($elemento);
        $ruta = 'Archivos/EvidenciasRechazo/' . $name;

        Storage::disk('public')->put($ruta, $pdf->Output('', 'S'));

        return $ruta;
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    private function buildFileName(?Elemento $elemento): string
    {
        $uuid = (string) Str::uuid();

        if ($elemento === null) {
            return 'ANOTADO_' . $uuid . '.pdf';
        }

        $version = $elemento->version_elemento !== null && is_numeric($elemento->version_elemento)
            ? number_format((float) $elemento->version_elemento, 1, '.', '')
            : 'SV';

        $folio  = $this->sanitizePart((string) ($elemento->folio_elemento  ?? 'SIN-FOLIO'));
        $nombre = $this->sanitizePart((string) ($elemento->nombre_elemento ?? 'SIN-NOMBRE'));

        $base = trim("{$version} {$folio} {$nombre}");

        if (mb_strlen($base, 'UTF-8') > 170) {
            $base = rtrim(mb_substr($base, 0, 170, 'UTF-8'));
        }

        return $base . '_' . $uuid . '.pdf';
    }

    private function sanitizePart(string $s): string
    {
        $s = preg_replace('/\s+/', ' ', trim($s)) ?? '';
        $s = preg_replace('/[\/\\\\\?\%\*\:\|"<>]/u', '', $s) ?? '';
        $s = preg_replace('/[^\p{L}\p{N}\s\.\-_()]/u', '', $s) ?? '';
        $s = preg_replace('/\s+/u', ' ', trim($s)) ?? '';
        return trim($s, " .\t\n\r\0\x0B") ?: 'NA';
    }

    /**
     * Draws a visual numbered red circle on the page content stream and attaches
     * a native PDF Text annotation (interactive popup) at the same position.
     *
     * Compatible viewers (Adobe Acrobat, Foxit, etc.) render a clickable icon
     * that opens the reviewer's comment as a popup when clicked.
     */
    private function drawMarkerWithAnnotation(TcpdfFpdi $pdf, array $ann, float $pageW, float $pageH): void
    {
        $xPct    = (float) ($ann['x_pct'] ?? 50);
        $yPct    = (float) ($ann['y_pct'] ?? 50);
        $num     = (int)   ($ann['_num']  ?? 1);
        $content = trim((string) ($ann['content'] ?? ''));
        $r       = self::CIRCLE_RADIUS_MM;

        $cx = ($xPct / 100.0) * $pageW;
        $cy = ($yPct / 100.0) * $pageH;

        // Keep marker inside page bounds
        $cx = max($r + 0.5, min($pageW - $r - 0.5, $cx));
        $cy = max($r + 0.5, min($pageH - $r - 0.5, $cy));

        // 1. Visual marker — filled red circle with white number (page content stream)
        $pdf->SetFillColor(220, 38, 38);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetLineWidth(0.5);
        $pdf->Circle($cx, $cy, $r, 0, 360, 'DF');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($cx - $r, $cy - $r);
        $pdf->Cell($r * 2, $r * 2, (string) $num, 0, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);

        // 2. Native PDF Text annotation — interactive popup comment
        //    Placed over the drawn circle so the click area aligns with the marker.
        //    The annotation bounding box matches the circle exactly.
        $pdf->Annotation(
            $cx - $r,        // x of annotation bounding box (top-left)
            $cy - $r,        // y of annotation bounding box (top-left)
            $r * 2,          // width
            $r * 2,          // height
            $content,        // popup body text shown when user clicks
            [
                'Subtype' => 'Text',
                'T'       => 'Observaci\u00f3n ' . $num,   // title shown in popup header
                'C'       => [220, 38, 38],                 // annotation color R,G,B (0-255)
                'ic'      => [255, 255, 255],               // interior color
                'Name'    => 'Comment',                     // icon style (Comment, Note, etc.)
                'F'       => 4,                             // flags: Print
            ]
        );
    }
}
