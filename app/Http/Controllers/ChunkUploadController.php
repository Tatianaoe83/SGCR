<?php

namespace App\Http\Controllers;

use App\Models\TipoElemento;
use App\Support\ChunkUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Recibe archivos grandes en partes pequenas para esquivar los limites de
 * upload_max_filesize / post_max_size del hosting compartido.
 *
 * Flujo: el cliente parte el archivo y envia varias partes en paralelo, en
 * cualquier orden. Cada parte se escribe directo en su posicion dentro del
 * archivo final (indice * tamano de parte), asi no hay que ensamblar nada al
 * terminar. Un registro protegido con flock lleva la cuenta de las partes
 * recibidas; la request que completa la cuenta valida el archivo y responde
 * con un token cifrado con la ruta final. Ese token viaja en el formulario del
 * elemento en lugar del archivo.
 */
class ChunkUploadController extends Controller
{
    private const UPLOAD_ID_REGEX = '/^[A-Za-z0-9]{16,64}$/';

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id'        => ['required', 'string', 'regex:' . self::UPLOAD_ID_REGEX],
            'chunk_index'      => ['required', 'integer', 'min:0'],
            'total_chunks'     => ['required', 'integer', 'min:1', 'max:10000'],
            'chunk_size'       => ['required', 'integer', 'min:1'],
            'file_size'        => ['required', 'integer', 'min:1'],
            'file_name'        => ['required', 'string', 'max:255'],
            'tipo_elemento_id' => ['required', 'integer'],
            'chunk'            => ['required', 'file'],
        ]);

        $uploadId   = $request->string('upload_id')->toString();
        $chunkIndex = $request->integer('chunk_index');
        $total      = $request->integer('total_chunks');
        $fileSize   = $request->integer('file_size');
        $tipoId     = $request->integer('tipo_elemento_id');
        $nombre     = $request->string('file_name')->toString();
        $chunkSize  = ChunkUpload::chunkSizeBytes();

        // El navegador calculo las posiciones con el tamano que le dio la
        // vista; si no coincide con el del servidor, el archivo quedaria corrupto.
        if ($request->integer('chunk_size') !== $chunkSize) {
            return response()->json([
                'message' => 'La configuracion de subida cambio. Recarga la pagina y vuelve a intentarlo.',
            ], 409);
        }

        $maxBytes = (int) config('uploads.video.max_size_bytes');

        if ($fileSize > $maxBytes) {
            return response()->json([
                'message' => 'El archivo supera el limite de ' . $this->mb($maxBytes) . ' MB.',
            ], 422);
        }

        if ($total !== (int) ceil($fileSize / $chunkSize) || $chunkIndex >= $total) {
            return response()->json(['message' => 'Numero de partes invalido.'], 422);
        }

        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        if (! in_array($extension, (array) config('uploads.video.extensiones', []), true)) {
            return response()->json([
                'message' => 'Extension no permitida: .' . $extension,
            ], 422);
        }

        $esperado = $chunkIndex === $total - 1
            ? $fileSize - ($chunkIndex * $chunkSize)
            : $chunkSize;

        if ((int) $request->file('chunk')->getSize() !== $esperado) {
            return response()->json(['message' => 'Tamano de parte invalido.'], 422);
        }

        // La consulta del tipo solo se hace en la primera parte (para fallar
        // pronto) y al finalizar (la que cuenta); las demas se la ahorran.
        if ($chunkIndex === 0 && ! $this->tipoAceptaMultimedia($tipoId)) {
            return response()->json([
                'message' => 'Este tipo de elemento no acepta archivos multimedia.',
            ], 422);
        }

        $rutas = $this->rutas($uploadId);

        Storage::disk('local')->makeDirectory(dirname($rutas['part']));

        if (! $this->escribirParte($rutas['abs_part'], $chunkIndex * $chunkSize, $request->file('chunk')->getRealPath())) {
            return response()->json(['message' => 'No se pudo escribir la parte.'], 500);
        }

        $recibidas = $this->registrarParte($rutas, $chunkIndex, $total);

        if ($recibidas === null) {
            return response()->json(['message' => 'No se pudo registrar la parte.'], 500);
        }

        if ($recibidas < $total) {
            return response()->json([
                'ok'       => true,
                'received' => $recibidas,
                'total'    => $total,
            ]);
        }

        return $this->finalizar($uploadId, $rutas, $extension, $nombre, $fileSize, $tipoId);
    }

    /**
     * Escribe la parte en su posicion. 'c+b' crea el archivo si no existe sin
     * truncarlo, asi varias requests pueden escribir zonas distintas a la vez.
     */
    private function escribirParte(string $absPart, int $offset, string $origenPath): bool
    {
        $destino = @fopen($absPart, 'c+b');

        if ($destino === false) {
            return false;
        }

        $origen = fopen($origenPath, 'rb');

        try {
            if (fseek($destino, $offset) !== 0) {
                return false;
            }

            return stream_copy_to_stream($origen, $destino) !== false;
        } finally {
            fclose($origen);
            fclose($destino);
        }
    }

    /**
     * Anota la parte en el registro y devuelve cuantas van. Solo una request
     * puede ver la cuenta completa: al llegar ahi se marca como finalizada, y
     * si otra llega despues (un reintento) ya no vuelve a finalizar.
     */
    private function registrarParte(array $rutas, int $indice, int $total): ?int
    {
        $lock = @fopen($rutas['abs_lock'], 'c');

        if ($lock === false) {
            return null;
        }

        try {
            flock($lock, LOCK_EX);

            $estado = is_file($rutas['abs_state'])
                ? (json_decode((string) file_get_contents($rutas['abs_state']), true) ?: [])
                : [];

            if (! empty($estado['finalizado'])) {
                return -1;
            }

            $recibidas = array_flip($estado['recibidas'] ?? []);
            $recibidas[$indice] = true;
            $recibidas = array_keys($recibidas);

            $estado['recibidas'] = $recibidas;
            $estado['finalizado'] = count($recibidas) >= $total;

            file_put_contents($rutas['abs_state'], json_encode($estado));

            return $estado['finalizado'] ? $total : count($recibidas);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Todas las partes llegaron: valida el archivo completo y lo mueve a su destino.
     */
    private function finalizar(
        string $uploadId,
        array $rutas,
        string $extension,
        string $nombreOriginal,
        int $fileSize,
        int $tipoId
    ): JsonResponse {
        if (! $this->tipoAceptaMultimedia($tipoId)) {
            $this->descartar($uploadId);

            return response()->json([
                'message' => 'Este tipo de elemento no acepta archivos multimedia.',
            ], 422);
        }

        clearstatcache(true, $rutas['abs_part']);

        if (! is_file($rutas['abs_part']) || filesize($rutas['abs_part']) !== $fileSize) {
            $this->descartar($uploadId);

            return response()->json(['message' => 'El archivo llego incompleto. Vuelve a subirlo.'], 422);
        }

        $mime = mime_content_type($rutas['abs_part']) ?: '';

        if (! in_array($mime, (array) config('uploads.video.mimetypes', []), true)) {
            $this->descartar($uploadId);

            return response()->json([
                'message' => 'El contenido del archivo no corresponde a un video o audio valido (' . $mime . ').',
            ], 422);
        }

        $base      = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME), '-') ?: 'multimedia';
        $nombre    = $base . '_' . now()->format('YmdHis') . '_' . Str::random(6) . '.' . $extension;
        $rutaFinal = trim((string) config('uploads.video.directorio'), '/') . '/' . $nombre;

        Storage::disk('public')->makeDirectory(dirname($rutaFinal));

        $movido = @rename($rutas['abs_part'], Storage::disk('public')->path($rutaFinal));

        if (! $movido) {
            // rename falla entre volumenes distintos; se copia por streaming.
            $movido = Storage::disk('public')->writeStream($rutaFinal, fopen($rutas['abs_part'], 'rb'));
        }

        $this->descartar($uploadId);

        if (! $movido) {
            return response()->json(['message' => 'No se pudo guardar el archivo.'], 500);
        }

        return response()->json([
            'ok'     => true,
            'done'   => true,
            // Cifrado para que el cliente no pueda inyectar una ruta arbitraria.
            'token'  => Crypt::encryptString($rutaFinal),
            'nombre' => $nombre,
            'size'   => Storage::disk('public')->size($rutaFinal),
        ]);
    }

    public function abort(string $uploadId): JsonResponse
    {
        if (! preg_match(self::UPLOAD_ID_REGEX, $uploadId)) {
            return response()->json(['message' => 'Identificador invalido.'], 422);
        }

        $this->descartar($uploadId);

        return response()->json(['ok' => true]);
    }

    private function tipoAceptaMultimedia(int $tipoId): bool
    {
        $tipo = $tipoId > 0 ? TipoElemento::find($tipoId) : null;

        return $tipo !== null && $tipo->permiteMultimedia();
    }

    /**
     * Archivos de una subida, aislados por usuario para que nadie pueda
     * escribir en la subida de otro. No se usa el id de sesion porque cambia
     * al regenerarse y partiria una subida a medias.
     *
     * @return array{part: string, abs_part: string, abs_state: string, abs_lock: string}
     */
    private function rutas(string $uploadId): array
    {
        $userId = auth()->id();

        if (! $userId) {
            throw new \RuntimeException('Subida por partes sin usuario autenticado.');
        }

        $base = trim((string) config('uploads.chunk_temp_dir'), '/') . '/u' . $userId . '/' . $uploadId;
        $disco = Storage::disk('local');

        return [
            'part'      => $base . '.part',
            'abs_part'  => $disco->path($base . '.part'),
            'abs_state' => $disco->path($base . '.json'),
            'abs_lock'  => $disco->path($base . '.lock'),
        ];
    }

    private function descartar(string $uploadId): void
    {
        try {
            $rutas = $this->rutas($uploadId);

            foreach (['abs_part', 'abs_state', 'abs_lock'] as $clave) {
                if (is_file($rutas[$clave])) {
                    @unlink($rutas[$clave]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo borrar la subida ' . $uploadId . ': ' . $e->getMessage());
        }
    }

    private function mb(int $bytes): int
    {
        return (int) round($bytes / 1024 / 1024);
    }
}
