<?php

namespace App\Support;

class MapaUbicacionEjeY
{
    public const MODE_NONE = 'none';

    public const MODE_INDUSTRIAL = 'industrial';

    public const MODE_APOYO = 'apoyo';

    public static function modeFromTipoNombre(?string $nombre): string
    {
        $n = strtolower($nombre ?? '');

        if (str_contains($n, 'industrial')) {
            return self::MODE_INDUSTRIAL;
        }

        if (str_contains($n, 'apoyo')) {
            return self::MODE_APOYO;
        }

        return self::MODE_NONE;
    }

    public static function label(?string $tipoNombre, ?int $y): ?string
    {
        return match (self::modeFromTipoNombre($tipoNombre)) {
            self::MODE_INDUSTRIAL => match ((int) $y) {
                1       => 'Concretos (CON)',
                2       => 'Agregados (AG)',
                default => 'Ambas filas (compartido)',
            },
            self::MODE_APOYO => ((int) $y === 2) ? 'Fila inferior' : 'Fila superior',
            default          => null,
        };
    }
}
