<?php

namespace App\Http\Controllers;

use App\Models\TipoElemento;
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
 * Flujo: el cliente parte el archivo, envia cada trozo en orden a store() y
 * al enviar el ultimo recibe un token cifrado con la ruta final. Ese token
 * viaja en el formulario del elemento en lugar del archivo.
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
            'file_name'        => ['required', 'string', 'max:255'],
            'tipo_elemento_id' => ['required', 'integer', 'exists:tipo_elementos,id_tipo_elemento'],
            'chunk'            => ['required', 'file'],
        ]);

        $tipo = TipoElemento::find($request->integer('tipo_elemento_id'));

        if (! $tipo || ! $tipo->permiteMultimedia()) {
            return response()->json([
                'message' => 'Este tipo de elemento no acepta archivos multimedia.',
            ], 422);
        }

        $uploadId   = $request->string('upload_id')->toString();
        $chunkIndex = $request->integer('chunk_index');
        $total      = $request->integer('total_chunks');

        $extension = strtolower(pathinfo($request->string('file_name')->toString(), PATHINFO_EXTENSION));

        if (! in_array($extension, (array) config('uploads.video.extensiones', []), true)) {
            return response()->json([
                'message' => 'Extension no permitida: .' . $extension,
            ], 422);
        }

        $partPath = $this->partPath($uploadId);
        $absPart  = Storage::disk('local')->path($partPath);

        Storage::disk('local')->makeDirectory(dirname($partPath));

        $chunkSize = (int) config('uploads.chunk_size_bytes');
        $yaEscrito = is_file($absPart) ? filesize($absPart) : 0;

        // El indice debe corresponder a lo ya escrito: fuerza el orden y evita
        // que dos requests concurrentes intercalen contenido.
        if ($yaEscrito !== $chunkIndex * $chunkSize) {
            return response()->json([
                'message'        => 'Parte fuera de orden.',
                'expected_index' => intdiv($yaEscrito, $chunkSize),
            ], 409);
        }

        $maxBytes = (int) config('uploads.video.max_size_bytes');
        $entrante = (int) $request->file('chunk')->getSize();

        if ($yaEscrito + $entrante > $maxBytes) {
            $this->descartar($uploadId);

            return response()->json([
                'message' => 'El archivo supera el limite de ' . $this->mb($maxBytes) . ' MB.',
            ], 422);
        }

        $destino = fopen($absPart, 'ab');

        if ($destino === false) {
            return response()->json(['message' => 'No se pudo escribir la parte.'], 500);
        }

        $origen = fopen($request->file('chunk')->getRealPath(), 'rb');

        try {
            stream_copy_to_stream($origen, $destino);
        } finally {
            fclose($origen);
            fclose($destino);
        }

        if ($chunkIndex + 1 < $total) {
            return response()->json([
                'ok'       => true,
                'received' => $chunkIndex + 1,
                'total'    => $total,
            ]);
        }

        return $this->finalizar(
            $uploadId,
            $partPath,
            $absPart,
            $extension,
            $request->string('file_name')->toString()
        );
    }

    /**
     * Ultimo trozo: valida el archivo ya completo y lo mueve a su destino.
     */
    private function finalizar(
        string $uploadId,
        string $partPath,
        string $absPart,
        string $extension,
        string $nombreOriginal
    ): JsonResponse {
        $mime = mime_content_type($absPart) ?: '';

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

        $movido = @rename($absPart, Storage::disk('public')->path($rutaFinal));

        if (! $movido) {
            // rename falla entre volumenes distintos; se copia por streaming.
            $movido = Storage::disk('public')->writeStream($rutaFinal, fopen($absPart, 'rb'));
            Storage::disk('local')->delete($partPath);
        }

        if (! $movido) {
            $this->descartar($uploadId);

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

    /**
     * Ruta de la parte, aislada por usuario para que nadie pueda anexar
     * contenido a la subida de otro. No se usa el id de sesion porque cambia
     * al regenerarse y partiria una subida a medias.
     */
    private function partPath(string $uploadId): string
    {
        $userId = auth()->id();

        if (! $userId) {
            throw new \RuntimeException('Subida por partes sin usuario autenticado.');
        }

        return trim((string) config('uploads.chunk_temp_dir'), '/')
            . '/u' . $userId
            . '/' . $uploadId . '.part';
    }

    private function descartar(string $uploadId): void
    {
        try {
            Storage::disk('local')->delete($this->partPath($uploadId));
        } catch (\Throwable $e) {
            Log::warning('No se pudo borrar la parte ' . $uploadId . ': ' . $e->getMessage());
        }
    }

    private function mb(int $bytes): int
    {
        return (int) round($bytes / 1024 / 1024);
    }
}
