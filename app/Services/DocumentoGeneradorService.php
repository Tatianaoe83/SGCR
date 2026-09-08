<?php

namespace App\Services;

use App\Models\Elemento;
use App\Models\Firmas;
use App\Support\Pdf\FpdiRotate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class DocumentoGeneradorService
{
    private const TABLA_HEADER_H = 7.0;
    private const TABLA_MIN_ROW_H = 14.0;
    private const TABLA_MAX_IMG_H = 16.0;

    private ?Fpdi $measurePdf = null;

    public function generarDocumentoConMarcaAgua(Elemento $elemento): string
    {
        $archivoBase = $this->resolveElementoBaseAbsolutePath($elemento);
        $extension = strtolower(pathinfo($archivoBase, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return $this->agregarMarcaAguaPDF($archivoBase, $elemento);
        }

        if (in_array($extension, ['doc', 'docx'], true)) {
            $pdfTemp = $this->convertirWordAPdf($archivoBase);
            return $this->agregarMarcaAguaPDF($pdfTemp, $elemento);
        }

        throw new RuntimeException('Formato no soportado para marca de agua');
    }

    private function agregarMarcaAguaPDF(string $rutaPdfAbs, Elemento $elemento): string
    {
        $pdf = new Fpdi();
        $pages = $pdf->setSourceFile($rutaPdfAbs);

        $version = $elemento->version_elemento !== null
            ? (float) $elemento->version_elemento
            : null;

        for ($p = 1; $p <= $pages; $p++) {
            $tpl = $pdf->importPage($p);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            $this->renderSelloNoOficialTopLeft($pdf, (float) $size['width'], $version);
        }

        $name = $this->buildElementoPdfFileName($elemento);
        $ruta = 'Archivos/DocumentosMarkdown/' . $name;

        Storage::disk('public')->put($ruta, $pdf->Output('', 'S'));

        return $ruta;
    }

    private function renderSelloNoOficialTopLeft(Fpdi $pdf, float $pageW, ?float $version): void
    {
        $textoPrincipal = 'ELEMENTO NO OFICIAL';
        $textoVersion = 'v' . ($version !== null ? number_format($version, 1, '.', '') : 'S/V');

        $pdf->SetFont('Arial', 'B', 6);

        $wPrincipal = $pdf->GetStringWidth($textoPrincipal);
        $wVersion = $pdf->GetStringWidth($textoVersion);
        $contenidoW = max($wPrincipal, $wVersion);

        $paddingX = 3;
        $w = $contenidoW + ($paddingX * 2);
        $h = 7;

        $x = 3;
        $y = 3;

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($x, $y, $w, $h);

        $pdf->SetXY($x + $paddingX, $y + 1.5);
        $pdf->Cell($w - ($paddingX * 2), 2.5, $this->pdfText($textoPrincipal), 0, 0, 'C');

        $pdf->SetFont('Arial', '', 5);
        $pdf->SetXY($x + $paddingX, $y + 4);
        $pdf->Cell($w - ($paddingX * 2), 2.5, $this->pdfText($textoVersion), 0, 0, 'C');
    }

    public function generarDocumentoConFirmas(Elemento $elemento): string
    {
        $sourceAbs = $this->resolveElementoBaseAbsolutePath($elemento);
        $ext = strtolower(pathinfo($sourceAbs, PATHINFO_EXTENSION));

        if (in_array($ext, ['doc', 'docx'], true)) {
            $sourceAbs = $this->convertirWordAPdf($sourceAbs);
        } elseif ($ext !== 'pdf') {
            throw new RuntimeException('Formato no soportado para firmado.');
        }

        $firmas = Firmas::where('elemento_id', $elemento->id_elemento)
            ->where('estatus', 'Aprobado')
            ->where('is_active', true)
            ->orderBy('prioridad')
            ->get([
                'id',
                'empleado_id',
                'tipo',
                'prioridad',
                'firma_snapshot_path',
                'nombre_firmante',
                'puesto_firmante',
            ]);

        if ($firmas->isEmpty()) {
            throw new RuntimeException('No hay firmas aprobadas para generar el documento.');
        }

        $rows = $this->buildFirmaRows($firmas->all());
        $sideFirmas = $this->buildSideFirmasUnique($firmas->all());

        $pdf = new FpdiRotate();
        $pages = $pdf->setSourceFile($sourceAbs);

        $lastSize = null;
        $orientation = 'P';

        for ($p = 1; $p <= $pages; $p++) {
            $tpl = $pdf->importPage($p);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            if ($p < $pages) {
                $this->renderFirmasMargenIzquierdo(
                    $pdf,
                    (float) $size['width'],
                    (float) $size['height'],
                    $sideFirmas
                );
            }

            $lastSize = $size;
            $orientation = $size['orientation'];
        }

        if (!$lastSize) {
            throw new RuntimeException('No se pudo leer el tamaño del PDF fuente.');
        }

        $pageW = (float) $lastSize['width'];
        $pageH = (float) $lastSize['height'];

        $marginX = 12.0;
        $tableW = $pageW - ($marginX * 2);
        $colWs = $this->tablaColWidths($tableW);

        $marginBottom = 12.0;
        $marginTop = 22.0;

        $estimatedH = $this->estimateTablaHeight($rows, $colWs);
        $reservedH = min($estimatedH, $pageH * 0.4);
        $startY = $pageH - $marginBottom - $reservedH;

        if ($startY < 20.0) {
            $startY = 20.0;
        }

        $this->renderTablaFirmas($pdf, $marginX, $colWs, $rows, $startY, $pageH - $marginBottom);

        while (!empty($rows)) {
            $pdf->AddPage($orientation, [$pageW, $pageH]);
            $this->renderTablaFirmas($pdf, $marginX, $colWs, $rows, $marginTop, $pageH - $marginBottom);
        }

        $name = $this->buildElementoPdfFileName($elemento);
        $ruta = 'Archivos/DocumentosFirmados/' . $name;

        Storage::disk('public')->put($ruta, $pdf->Output('', 'S'));

        return $ruta;
    }

    private function buildSideFirmasUnique(array $firmas): array
    {
        $seen = [];
        $out = [];

        foreach ($firmas as $f) {
            $empleadoId = $f->empleado_id ?? null;
            $puesto = $this->cleanLine((string) ($f->puesto_firmante ?? ''));
            $nombre = $this->cleanLine((string) ($f->nombre_firmante ?? ''));

            $keyLeft = $empleadoId !== null
                ? 'e:' . (string) $empleadoId
                : 'n:' . mb_strtolower($nombre, 'UTF-8');

            $key = $keyLeft . '|p:' . mb_strtolower($puesto, 'UTF-8');

            if (isset($seen[$key])) {
                continue;
            }

            $absImg = $this->resolveFirmaImageAbsPath($f);
            if (!$absImg) {
                continue;
            }

            $seen[$key] = true;

            $out[] = [
                'absImg' => $absImg,
            ];
        }

        return $out;
    }

    private function renderFirmasMargenIzquierdo(FpdiRotate $pdf, float $pageW, float $pageH, array $sideFirmas): void
    {
        $n = count($sideFirmas);
        if ($n === 0) return;

        $marginLeftX = 1.5;  // Más pegado al borde izquierdo
        $marginW     = 15.0; // Reducido de 40 a 15 para ocupar menos espacio horizontal
        $top         = 18.0;
        $bottom      = 18.0;

        $availH = $pageH - $top - $bottom;
        if ($availH <= 0) return;

        $slotH  = $availH / $n;
        $gap    = 2.0;  // Reducido gap
        $pivotX = $marginLeftX + ($marginW / 2.0);

        for ($i = 0; $i < $n; $i++) {
            $absImg = $sideFirmas[$i]['absImg'] ?? null;
            if (!is_string($absImg) || $absImg === '' || !is_file($absImg)) continue;

            // Invertir dimensiones para rotación: maxW se vuelve maxH y viceversa
            $maxW = min($slotH - $gap, 35.0); // Limitar altura máxima de firma vertical
            $maxH = $marginW - $gap;          // Controla extensión horizontal

            if ($maxH <= 2.0 || $maxW <= 2.0) continue;

            [$imgW, $imgH] = $this->fitImageDims($absImg, $maxW, $maxH);

            $centerY = $top + ($slotH * $i) + ($slotH / 2.0);

            // Calcular posición considerando la rotación
            $x = $pivotX;
            $y = $centerY;

            // Rotar 90 grados en sentido antihorario
            $pdf->Rotate(90, $x, $y);
            $pdf->Image($absImg, $x - ($imgW / 2.0), $y - ($imgH / 2.0), $imgW, $imgH);
            $pdf->Rotate(0);
        }
    }

    /**
     * Una fila por firma, agrupadas por rol: Autorizo, Reviso, Responsables, Participantes.
     */
    private function buildFirmaRows(array $firmas): array
    {
        $byTipo = [
            'Autorizo'     => [],
            'Reviso'       => [],
            'Responsable'  => [],
            'Participante' => [],
        ];

        foreach ($firmas as $f) {
            $tipo = $f->tipo ?? null;
            if (!is_string($tipo) || !array_key_exists($tipo, $byTipo)) {
                continue;
            }
            $byTipo[$tipo][] = $f;
        }

        $labels = [
            'Autorizo'     => 'AUTORIZÓ',
            'Reviso'       => 'REVISÓ',
            'Responsable'  => 'RESPONSABLE',
            'Participante' => 'PARTICIPANTE',
        ];

        $rows = [];

        foreach ($byTipo as $tipo => $items) {
            if (empty($items)) {
                $rows[] = [
                    'rol'    => $labels[$tipo],
                    'nombre' => '',
                    'puesto' => '',
                    'absImg' => null,
                ];
                continue;
            }

            foreach ($items as $f) {
                $rows[] = [
                    'rol'    => $labels[$tipo],
                    'nombre' => $this->cleanLine((string) ($f->nombre_firmante ?? '')),
                    'puesto' => $this->cleanLine((string) ($f->puesto_firmante ?? '')),
                    'absImg' => $this->resolveFirmaImageAbsPath($f),
                ];
            }
        }

        return $rows;
    }

    private function tablaColWidths(float $tableW): array
    {
        return [
            $tableW * 0.22, // Rol
            $tableW * 0.30, // Nombre
            $tableW * 0.28, // Puesto
            $tableW * 0.20, // Firma
        ];
    }

    private function estimateTablaHeight(array $rows, array $colWs): float
    {
        $h = self::TABLA_HEADER_H;

        foreach ($rows as $row) {
            $h += $this->tablaRowHeight($row, $colWs);
        }

        return max(30.0, $h + 4.0);
    }

    private function tablaRowHeight(array $row, array $colWs): float
    {
        $lineH = 3.6;
        $padY = 2.0;

        $rolLines = count($this->wrapPdfLines((string) $row['rol'], $colWs[0] - 3.0, 6.5, 'B'));
        $nombreLines = count($this->wrapPdfLines((string) $row['nombre'], $colWs[1] - 3.0, 6.5));
        $puestoLines = count($this->wrapPdfLines((string) $row['puesto'], $colWs[2] - 3.0, 6.0));

        $textH = max($rolLines, $nombreLines, $puestoLines) * $lineH;

        [, $imgH] = $this->fitImageDims($row['absImg'] ?? null, $colWs[3] - 3.0, self::TABLA_MAX_IMG_H);

        return max(self::TABLA_MIN_ROW_H, max($textH, $imgH) + ($padY * 2));
    }

    /**
     * Dibuja la tabla de firmas; consume $rows y deja pendientes las que no caben.
     */
    private function renderTablaFirmas(Fpdi $pdf, float $x, array $colWs, array &$rows, float $startY, float $endY): void
    {
        if (empty($rows)) {
            return;
        }

        $y = $startY;

        if (($y + self::TABLA_HEADER_H + self::TABLA_MIN_ROW_H) > $endY) {
            return;
        }

        $pdf->SetDrawColor(160, 160, 160);
        $pdf->SetLineWidth(0.1);

        $this->renderTablaHeader($pdf, $x, $y, $colWs);
        $y += self::TABLA_HEADER_H;

        while (!empty($rows)) {
            $row = $rows[0];
            $rowH = $this->tablaRowHeight($row, $colWs);

            if (($y + $rowH) > $endY) {
                return;
            }

            array_shift($rows);

            $this->renderTablaRow($pdf, $x, $y, $rowH, $colWs, $row);
            $y += $rowH;
        }
    }

    private function renderTablaHeader(Fpdi $pdf, float $x, float $y, array $colWs): void
    {
        $titulos = ['ROL', 'NOMBRE', 'PUESTO', 'FIRMA'];
        $h = self::TABLA_HEADER_H;

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetFillColor(217, 217, 217);
        $pdf->SetTextColor(0, 0, 0);

        $cx = $x;
        foreach ($colWs as $i => $w) {
            $pdf->Rect($cx, $y, $w, $h, 'FD');
            $pdf->SetXY($cx, $y + (($h - 3.6) / 2.0));
            $pdf->Cell($w, 3.6, $this->pdfText($titulos[$i]), 0, 0, 'C');
            $cx += $w;
        }
    }

    private function renderTablaRow(Fpdi $pdf, float $x, float $y, float $rowH, array $colWs, array $row): void
    {
        $cx = $x;
        foreach ($colWs as $w) {
            $pdf->Rect($cx, $y, $w, $rowH);
            $cx += $w;
        }

        $this->renderTablaCellText($pdf, $x, $y, $colWs[0], $rowH, (string) $row['rol'], 'B', 6.5);
        $this->renderTablaCellText($pdf, $x + $colWs[0], $y, $colWs[1], $rowH, (string) $row['nombre'], '', 6.5);
        $this->renderTablaCellText($pdf, $x + $colWs[0] + $colWs[1], $y, $colWs[2], $rowH, (string) $row['puesto'], '', 6.0);

        $absImg = $row['absImg'] ?? null;
        if ($absImg) {
            $firmaX = $x + $colWs[0] + $colWs[1] + $colWs[2];
            [$imgW, $imgH] = $this->fitImageDims($absImg, $colWs[3] - 3.0, min(self::TABLA_MAX_IMG_H, $rowH - 2.0));

            $pdf->Image(
                $absImg,
                $firmaX + (($colWs[3] - $imgW) / 2.0),
                $y + (($rowH - $imgH) / 2.0),
                $imgW,
                $imgH
            );
        }
    }

    private function renderTablaCellText(Fpdi $pdf, float $x, float $y, float $w, float $rowH, string $text, string $style, float $size): void
    {
        $lineH = 3.6;

        $pdf->SetFont('Arial', $style, $size);
        $lines = $this->wrapPdfLines($text, $w - 3.0, $size, $style);

        $blockH = count($lines) * $lineH;
        $ty = $y + max(1.0, ($rowH - $blockH) / 2.0);

        foreach ($lines as $line) {
            $pdf->SetXY($x, $ty);
            $pdf->Cell($w, $lineH, $this->pdfText($line), 0, 0, 'C');
            $ty += $lineH;
        }
    }

    /**
     * Parte el texto en lineas que caben en $maxW, con un maximo de 3 lineas.
     */
    private function wrapPdfLines(string $text, float $maxW, float $size, string $style = ''): array
    {
        $text = $this->cleanLine($text);
        if ($text === '' || $maxW <= 2.0) {
            return [$text];
        }

        $measure = $this->measurePdf();
        $measure->SetFont('Arial', $style, $size);

        $words = explode(' ', $text);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $try = $current === '' ? $word : $current . ' ' . $word;

            if ($measure->GetStringWidth($this->pdfText($try)) <= $maxW) {
                $current = $try;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }

            $current = $word;

            while ($measure->GetStringWidth($this->pdfText($current)) > $maxW && mb_strlen($current, 'UTF-8') > 1) {
                $cut = mb_substr($current, 0, mb_strlen($current, 'UTF-8') - 1, 'UTF-8');
                if ($measure->GetStringWidth($this->pdfText($cut)) <= $maxW) {
                    $lines[] = $cut;
                    $current = mb_substr($current, mb_strlen($cut, 'UTF-8'), null, 'UTF-8');
                    break;
                }
                $current = $cut;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        if (empty($lines)) {
            return [$text];
        }

        if (count($lines) > 3) {
            $lines = array_slice($lines, 0, 3);
            $lines[2] = $this->truncateLine($lines[2], max(1, mb_strlen($lines[2], 'UTF-8') - 1));
        }

        return $lines;
    }

    private function measurePdf(): Fpdi
    {
        if ($this->measurePdf === null) {
            $pdf = new Fpdi();
            $pdf->AddPage();
            $this->measurePdf = $pdf;
        }

        return $this->measurePdf;
    }

    private function resolveFirmaImageAbsPath(mixed $firma): ?string
    {
        $path = $firma->firma_snapshot_path ?? null;
        if (!is_string($path) || $path === '') return null;
        if (!Storage::disk('public')->exists($path)) return null;

        $abs = Storage::disk('public')->path($path);
        return $this->normalizeFirmaImagePath($abs);
    }

    private function fitImageDims(?string $absImg, float $maxW, float $maxH): array
    {
        if (!$absImg || !is_file($absImg)) {
            return [$maxW, min($maxH, 10.0)];
        }

        $info = @getimagesize($absImg);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return [$maxW, min($maxH, 10.0)];
        }

        $w0 = (float) $info[0];
        $h0 = (float) $info[1];

        $scale = min($maxW / $w0, $maxH / $h0, 1.0);

        $w = $w0 * $scale;
        $h = $h0 * $scale;

        if ($w <= 0 || $h <= 0) {
            return [$maxW, min($maxH, 10.0)];
        }

        return [$w, $h];
    }

    private function normalizeFirmaImagePath(string $absPath): ?string
    {
        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));

        if (in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            return $absPath;
        }

        if ($ext === 'webp') {
            if (!function_exists('imagecreatefromwebp')) {
                return null;
            }

            $img = imagecreatefromwebp($absPath);
            if (!$img) return null;

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }

            $tmp = $tempDir . DIRECTORY_SEPARATOR . uniqid('firma_', true) . '.png';

            imagepng($img, $tmp);
            imagedestroy($img);

            return is_file($tmp) ? $tmp : null;
        }

        return null;
    }

    private function resolveElementoBaseAbsolutePath(Elemento $elemento): string
    {
        $candidates = [
            $elemento->archivo_es_formato ?? null,
            $elemento->archivo_formato ?? null,
        ];

        foreach ($candidates as $rel) {
            if (!is_string($rel) || $rel === '') continue;
            if (Storage::disk('public')->exists($rel)) {
                return Storage::disk('public')->path($rel);
            }
        }

        throw new RuntimeException('No existe archivo base del elemento en storage.');
    }

    private function convertirWordAPdf(string $rutaWordAbs): string
    {
        $ilovepdf = new \Ilovepdf\Ilovepdf(
            config('services.ilovepdf.public'),
            config('services.ilovepdf.secret')
        );

        $task = $ilovepdf->newTask('officepdf');
        $task->addFile($rutaWordAbs);
        $task->execute();

        $tempDir = storage_path('app/temp/' . uniqid('officepdf_', true));
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $task->download($tempDir);

        $pdfs = glob($tempDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
        if (empty($pdfs)) {
            $pdfs = glob($tempDir . DIRECTORY_SEPARATOR . '**' . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
        }

        if (empty($pdfs)) {
            throw new RuntimeException('No se encontró PDF resultante al convertir Word.');
        }

        return $pdfs[0];
    }

    private function buildElementoPdfFileName(Elemento $elemento): string
    {
        $version = $this->formatVersion($elemento->version_elemento);
        $folio = $this->sanitizeFilePart((string) ($elemento->folio_elemento ?? 'SIN-FOLIO'));
        $nombre = $this->sanitizeFilePart((string) ($elemento->nombre_elemento ?? 'SIN-NOMBRE'));

        $base = trim($version . ' ' . $folio . ' ' . $nombre);
        $base = $this->limitFileBaseLength($base, 170);

        $uuid = (string) Str::uuid();

        return $base . '_' . $uuid . '.pdf';
    }

    private function formatVersion(mixed $version): string
    {
        if ($version === null || $version === '') {
            return 'S/V';
        }

        if (!is_numeric($version)) {
            return 'S/V';
        }

        return number_format((float) $version, 1, '.', '');
    }

    private function sanitizeFilePart(string $s): string
    {
        $s = $this->cleanLine($s);

        if (class_exists(\Normalizer::class)) {
            $norm = \Normalizer::normalize($s, \Normalizer::FORM_C);
            if (is_string($norm) && $norm !== '') {
                $s = $norm;
            }
        }

        $s = preg_replace('/[\/\\\\\?\%\*\:\|"<>]/u', '', $s) ?? '';
        $s = preg_replace('/\p{C}+/u', '', $s) ?? '';
        $s = preg_replace('/[^\p{L}\p{N}\s\.\-_()]/u', '', $s) ?? '';
        $s = preg_replace('/\s+/u', ' ', trim($s)) ?? '';
        $s = trim($s, " .\t\n\r\0\x0B");

        return $s !== '' ? $s : 'NA';
    }

    private function limitFileBaseLength(string $base, int $maxChars): string
    {
        if (mb_strlen($base, 'UTF-8') <= $maxChars) return $base;
        return rtrim(mb_substr($base, 0, $maxChars, 'UTF-8'));
    }

    private function cleanLine(string $s): string
    {
        return preg_replace('/\s+/', ' ', trim($s)) ?? '';
    }

    private function truncateLine(string $s, int $max): string
    {
        if (mb_strlen($s, 'UTF-8') <= $max) return $s;
        return mb_substr($s, 0, $max - 1, 'UTF-8') . '…';
    }

    private function pdfText(string $s): string
    {
        $s = preg_replace('/\s+/', ' ', trim($s)) ?? '';

        $out = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        if ($out === false) {
            $out = utf8_decode($s);
        }

        return $out;
    }
}
