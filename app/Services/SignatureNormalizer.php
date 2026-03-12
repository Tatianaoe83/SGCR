<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class SignatureNormalizer
{
    public function normalizeToPngCanvas(string $inputPublicPath, int $canvasW = 800, int $canvasH = 250): array
    {
        $absoluteIn = Storage::disk('public')->path($inputPublicPath);

        $img = Image::read($absoluteIn);

        // SI tu versión soporta trim() en Image (no en EncodedImage)
        $img = $img->trim();

        $margin = 20;
        $targetW = $canvasW - ($margin * 2);
        $targetH = $canvasH - ($margin * 2);

        $img = $img->scaleDown($targetW, $targetH);

        $canvas = Image::create($canvasW, $canvasH)->fill('rgba(0,0,0,0)');

        $x = (int) floor(($canvasW - $img->width()) / 2);
        $y = (int) floor(($canvasH - $img->height()) / 2);

        $canvas->place($img, 'top-left', $x, $y);

        $outPath = 'Archivos/FirmasElectronicasNormalizadas/' . (string) Str::uuid() . '.png';

        // encode AL FINAL
        Storage::disk('public')->put($outPath, (string) $canvas->toPng());

        $outAbs = Storage::disk('public')->path($outPath);
        $hash = is_file($outAbs) ? hash_file('sha256', $outAbs) : null;

        return ['path' => $outPath, 'mime' => 'image/png', 'hash' => $hash];
    }
}
