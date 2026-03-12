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

        $slots = $this->buildColumns3($firmas->all());
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

        $reservedH = 70.0;
        $marginBottom = 10.0;
        $startY = $pageH - $marginBottom - $reservedH;

        if ($startY < 0) {
            $startY = 20.0;
            $reservedH = $pageH - 40.0;
        }

        $this->renderColumns3OnArea($pdf, $pageW, $startY, $reservedH, $slots, false);

        $marginTop = 22.0;
        $marginBottomNew = 18.0;

        while ($this->columnsHavePending($slots)) {
            $pdf->AddPage($orientation, [$pageW, $pageH]);

            $availH = $pageH - $marginTop - $marginBottomNew;
            $this->renderColumns3OnArea($pdf, $pageW, $marginTop, $availH, $slots, true);
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
        if ($n === 0) {
            return;
        }

        $marginLeftX = 6.0;
        $marginW = 18.0;

        $top = 18.0;
        $bottom = 18.0;

        $availH = $pageH - $top - $bottom;
        if ($availH <= 0) {
            return;
        }

        $slotH = $availH / $n;
        $gap = 2.0;

        $pivotX = $marginLeftX + ($marginW / 2.0);

        for ($i = 0; $i < $n; $i++) {
            $absImg = $sideFirmas[$i]['absImg'] ?? null;
            if (!is_string($absImg) || $absImg === '' || !is_file($absImg)) {
                continue;
            }

            $centerY = $top + ($slotH * $i) + ($slotH / 2.0);

            $maxW = max(6.0, $slotH - $gap);
            $maxH = max(6.0, $marginW - $gap);

            [$imgW, $imgH] = $this->fitImageDims($absImg, $maxW, $maxH);

            $x = $pivotX - ($imgW / 2.0);
            $y = $centerY - ($imgH / 2.0);

            $pdf->Rotate(90.0, $pivotX, $centerY);
            $pdf->Image($absImg, $x, $y, $imgW, $imgH);
            $pdf->Rotate(0.0);
        }
    }

    private function buildColumns3(array $firmas): array
    {
        $byTipo = [
            'Participante' => [],
            'Responsable'  => [],
            'Reviso'       => [],
            'Autorizo'     => [],
        ];

        foreach ($firmas as $f) {
            $tipo = $f->tipo ?? null;
            if (!is_string($tipo) || !array_key_exists($tipo, $byTipo)) {
                continue;
            }
            $byTipo[$tipo][] = $f;
        }

        $slots = [];

        if (!empty($byTipo['Participante'])) {
            $slots[] = [
                'label' => 'PARTICIPANTES:',
                'items' => array_values($byTipo['Participante']),
                'printed_once' => false,
                'continuation' => false,
            ];
        }

        if (!empty($byTipo['Responsable'])) {
            $slots[] = [
                'label' => 'RESPONSABLES:',
                'items' => array_values($byTipo['Responsable']),
                'printed_once' => false,
                'continuation' => false,
            ];
        }

        if (!empty($byTipo['Reviso'])) {
            $slots[] = [
                'label' => 'REVISÓ:',
                'items' => array_values($byTipo['Reviso']),
                'printed_once' => false,
                'continuation' => false,
            ];
        }

        if (!empty($byTipo['Autorizo'])) {
            $slots[] = [
                'label' => 'AUTORIZÓ:',
                'items' => array_values($byTipo['Autorizo']),
                'printed_once' => false,
                'continuation' => false,
            ];
        }

        return $slots;
    }

    private function columnsHavePending(array $slots): bool
    {
        foreach ($slots as $slot) {
            if (!empty($slot['items'])) return true;
        }
        return false;
    }

    private function renderColumns3OnArea(Fpdi $pdf, float $pageW, float $startY, float $areaH, array &$slots, bool $isContinuation): void
    {
        $marginX = 12.0;
        $gapX = 6.0;
        $colW = ($pageW - ($marginX * 2) - ($gapX * 3)) / 4.0;

        $colXs = [
            $marginX,
            $marginX + $colW + $gapX,
            $marginX + ($colW * 2) + ($gapX * 2),
            $marginX + ($colW * 3) + ($gapX * 3),
        ];

        $endY = $startY + $areaH;
        $currentRowY = $startY;

        $slotIndex = 0;

        while ($slotIndex < count($slots)) {
            if (empty($slots[$slotIndex]['items'])) {
                $slotIndex++;
                continue;
            }

            $rowSlots = [];
            for ($i = 0; $i < 4 && ($slotIndex + $i) < count($slots); $i++) {
                if (!empty($slots[$slotIndex + $i]['items'])) {
                    $rowSlots[] = $slotIndex + $i;
                }
            }

            if (empty($rowSlots)) {
                break;
            }

            $maxY = $currentRowY;
            $anyIncomplete = false;

            foreach ($rowSlots as $idx) {
                $colPosition = $idx % 4;
                $x = $colXs[$colPosition];

                $slotY = $currentRowY;
                $this->renderSlotColumn($pdf, $x, $colW, $slotY, $endY, $slots[$idx], $isContinuation);

                if ($slotY > $maxY) {
                    $maxY = $slotY;
                }

                if (!empty($slots[$idx]['items'])) {
                    $anyIncomplete = true;
                }
            }

            if ($anyIncomplete) {
                break;
            }

            $slotIndex += count($rowSlots);
            $currentRowY = $maxY + 6.0;

            if ($currentRowY >= $endY) {
                break;
            }
        }
    }

    private function renderSlotColumn(Fpdi $pdf, float $colX, float $colW, float &$cursorY, float $endY, array &$slot, bool $isContinuation): void
    {
        if (empty($slot['items'])) {
            return;
        }

        $headerH = 6.0;
        $gapAfterHeader = 3.0;

        $label = (string) ($slot['label'] ?? 'FIRMAS:');

        // Solo imprimir el encabezado una vez (primera vez), sin repetirlo en continuaciones
        $needHeader = !$slot['printed_once'];
        if ($needHeader) {
            if (($cursorY + $headerH) > $endY) {
                $slot['continuation'] = true;
                return;
            }

            $pdf->SetFont('Arial', 'B', 7);

            // Siempre imprimir el label original, sin "(continuación)"
            $toPrint = $label;

            $pdf->SetXY($colX, $cursorY);
            $pdf->Cell($colW, $headerH, $this->pdfText($toPrint), 0, 0, 'L');

            $cursorY += $headerH + $gapAfterHeader;

            $slot['printed_once'] = true;
            $slot['continuation'] = false;
        }

        while (!empty($slot['items'])) {
            $item = $slot['items'][0];

            $minTextH = (3.5 * 2) + 2.0;
            $maxImgH = 10.0;
            $maxImgW = $colW * 0.85;

            $absImg = $this->resolveFirmaImageAbsPath($item);
            [$imgW, $imgH] = $this->fitImageDims($absImg, $maxImgW, $maxImgH);

            $itemH = $imgH + $minTextH + 3.0;

            if (($cursorY + $itemH) > $endY) {
                $slot['continuation'] = true;
                return;
            }

            array_shift($slot['items']);

            $this->renderFirmaItem3Col($pdf, $colX, $colW, $cursorY, $absImg, $imgW, $imgH, $item);

            $cursorY += $itemH;
        }
    }

    private function renderFirmaItem3Col(
        Fpdi $pdf,
        float $colX,
        float $colW,
        float $y,
        ?string $absImg,
        float $imgW,
        float $imgH,
        mixed $firma
    ): void {
        $bulletX = $colX + 1.5;

        $imgX = $colX + (($colW - $imgW) / 2.0);
        $imgY = $y;

        if ($absImg) {
            $pdf->Image($absImg, $imgX, $imgY, $imgW, $imgH);
        }

        $nombre = $this->truncateLine($this->cleanLine((string) ($firma->nombre_firmante ?? '')), 40);
        $puesto = $this->truncateLine($this->cleanLine((string) ($firma->puesto_firmante ?? '')), 40);

        $textY = $y + $imgH + 1.0;

        $pdf->SetFont('Arial', '', 6.5);

        $pdf->SetXY($bulletX, $textY);
        $pdf->Cell(3.0, 3.5, $this->pdfText('.'), 0, 0, 'L');

        $pdf->SetXY($colX, $textY);
        $pdf->Cell($colW, 3.5, $this->pdfText($nombre), 0, 0, 'C');

        $pdf->SetFont('Arial', '', 6);
        $pdf->SetXY($colX, $textY + 3.5);
        $pdf->Cell($colW, 3.5, $this->pdfText($puesto), 0, 0, 'C');
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
