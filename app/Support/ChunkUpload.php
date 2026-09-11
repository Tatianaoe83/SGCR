<?php

namespace App\Support;

/**
 * Parametros de la subida por partes compartidos entre el servidor y el
 * navegador. Ambos lados deben usar exactamente el mismo tamano de parte,
 * porque el servidor escribe cada una en la posicion indice * tamano.
 */
class ChunkUpload
{
    private const MB = 1024 * 1024;

    // Espacio que se reserva en post_max_size para los demas campos del
    // formulario y los encabezados multipart.
    private const MARGEN_POST = 64 * 1024;

    private const MINIMO = 512 * 1024;

    public static function chunkSizeBytes(): int
    {
        $fijo = config('uploads.chunk_size_mb');

        if (is_numeric($fijo) && (float) $fijo > 0) {
            return max(self::MINIMO, (int) round((float) $fijo * self::MB));
        }

        $limites = [(int) config('uploads.chunk_max_mb', 8) * self::MB];

        $upload = self::iniBytes('upload_max_filesize');
        if ($upload > 0) {
            $limites[] = $upload;
        }

        $post = self::iniBytes('post_max_size');
        if ($post > 0) {
            $limites[] = $post - self::MARGEN_POST;
        }

        return max(self::MINIMO, min($limites));
    }

    public static function concurrency(): int
    {
        return max(1, min(8, (int) config('uploads.chunk_concurrency', 4)));
    }

    /**
     * Convierte valores del php.ini como "8M" o "1G" a bytes. 0 = sin limite.
     */
    private static function iniBytes(string $clave): int
    {
        $valor = trim((string) ini_get($clave));

        if ($valor === '') {
            return 0;
        }

        $numero = (float) $valor;

        return (int) match (strtolower(substr($valor, -1))) {
            'g'     => $numero * 1024 * self::MB,
            'm'     => $numero * self::MB,
            'k'     => $numero * 1024,
            default => $numero,
        };
    }
}
