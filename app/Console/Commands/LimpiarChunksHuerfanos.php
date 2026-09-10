<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Limpia lo que deja atras la subida por partes.
 *
 * Son dos basuras distintas:
 *
 * 1. Partes (.part, con su registro .json y candado .lock) de subidas que
 *    nunca se completaron, porque el usuario cerro la pestana o se corto la red.
 * 2. Archivos que si terminaron de subir y ya viven en el disco publico, pero
 *    cuyo formulario nunca se guardo: ningun elemento los referencia.
 *
 * El segundo caso pesa mucho mas, porque ahi el archivo esta completo.
 */
class LimpiarChunksHuerfanos extends Command
{
    protected $signature = 'uploads:limpiar-chunks
        {--horas= : Antiguedad minima en horas, para ambas limpiezas}
        {--dry-run : Solo lista lo que borraria, sin tocar nada}';

    protected $description = 'Elimina partes de subidas incompletas y archivos multimedia que ningun elemento usa';

    private bool $simulacion = false;

    public function handle(): int
    {
        $this->simulacion = (bool) $this->option('dry-run');

        if ($this->simulacion) {
            $this->warn('Modo simulacion: no se borra nada.');
        }

        $opcion = $this->option('horas');

        $this->limpiarPartes($opcion === null ? (int) config('uploads.chunk_ttl_hours', 24) : (int) $opcion);
        $this->limpiarMultimediaSinDueno($opcion === null ? (int) config('uploads.video.huerfano_ttl_horas', 24) : (int) $opcion);

        return self::SUCCESS;
    }

    /**
     * Caso 1: subidas cortadas a la mitad.
     */
    private function limpiarPartes(int $horas): void
    {
        $base  = trim((string) config('uploads.chunk_temp_dir'), '/');
        $disco = Storage::disk('local');

        if (! $disco->exists($base)) {
            $this->info('Partes incompletas: nada pendiente.');

            return;
        }

        $limite    = now()->subHours($horas)->getTimestamp();
        $borrados  = 0;
        $liberados = 0;

        foreach ($disco->allFiles($base) as $archivo) {
            // .part = datos, .json = registro de partes, .lock = candado del registro.
            if (! in_array(pathinfo($archivo, PATHINFO_EXTENSION), ['part', 'json', 'lock'], true)) {
                continue;
            }

            if ($disco->lastModified($archivo) > $limite) {
                continue;
            }

            $liberados += $disco->size($archivo);
            $borrados++;

            $this->line('  borrar parte  ' . $archivo);

            if (! $this->simulacion) {
                $disco->delete($archivo);
            }
        }

        if (! $this->simulacion) {
            foreach ($disco->directories($base) as $dir) {
                if ($disco->allFiles($dir) === []) {
                    $disco->deleteDirectory($dir);
                }
            }
        }

        $this->info(sprintf(
            'Partes incompletas: %d eliminadas (%.1f MB).',
            $borrados,
            $liberados / 1024 / 1024
        ));
    }

    /**
     * Caso 2: el archivo subio completo pero el formulario nunca se guardo.
     *
     * Se borra solo si ademas de estar viejo no lo referencia ningun elemento,
     * incluidos los borrados en suave: esos conservan su archivo por si se
     * restauran.
     */
    private function limpiarMultimediaSinDueno(int $horas): void
    {
        $dir   = trim((string) config('uploads.video.directorio'), '/');
        $disco = Storage::disk('public');

        if (! $disco->exists($dir)) {
            $this->info('Multimedia sin dueno: nada pendiente.');

            return;
        }

        $enUso     = $this->nombresReferenciados();
        $limite    = now()->subHours($horas)->getTimestamp();
        $borrados  = 0;
        $liberados = 0;

        foreach ($disco->files($dir) as $archivo) {
            if (isset($enUso[basename($archivo)])) {
                continue;
            }

            if ($disco->lastModified($archivo) > $limite) {
                continue;
            }

            $liberados += $disco->size($archivo);
            $borrados++;

            $this->line('  borrar huerfano  ' . $archivo);

            if (! $this->simulacion) {
                $disco->delete($archivo);
            }
        }

        $this->info(sprintf(
            'Multimedia sin dueno: %d eliminados (%.1f MB).',
            $borrados,
            $liberados / 1024 / 1024
        ));
    }

    /**
     * Nombres de archivo citados por algun elemento, usados como set.
     *
     * Se comparan nombres y no rutas completas porque en la base conviven
     * formatos con y sin barra inicial; el nombre ya es unico (lleva fecha y
     * sufijo aleatorio).
     *
     * @return array<string, true>
     */
    private function nombresReferenciados(): array
    {
        $columnas = array_values(array_filter(
            Schema::getColumnListing('elementos'),
            static fn ($col) => str_starts_with($col, 'archivo')
        ));

        if ($columnas === []) {
            return [];
        }

        $nombres = [];

        DB::table('elementos')
            ->select($columnas)
            ->orderBy('id_elemento')
            ->chunk(500, function ($filas) use ($columnas, &$nombres) {
                foreach ($filas as $fila) {
                    foreach ($columnas as $col) {
                        foreach ($this->rutasDeValor($fila->{$col} ?? null) as $ruta) {
                            $nombres[basename($ruta)] = true;
                        }
                    }
                }
            });

        return $nombres;
    }

    /**
     * Una columna puede traer una ruta suelta o un JSON con varias.
     *
     * @return array<int, string>
     */
    private function rutasDeValor(mixed $valor): array
    {
        if (! is_string($valor) || trim($valor) === '') {
            return [];
        }

        $decodificado = json_decode($valor, true);

        if (is_array($decodificado)) {
            return array_values(array_filter(
                $decodificado,
                static fn ($v) => is_string($v) && trim($v) !== ''
            ));
        }

        return [$valor];
    }
}
