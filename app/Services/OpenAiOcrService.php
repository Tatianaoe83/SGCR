<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ilovepdf\Ilovepdf;
use ZipArchive;

class OpenAiOcrService
{
    /**
     * Flujo:
     * 1. PDF -> JPG a DPI controlado (iLovePDF)
     * 2. OCR de todas las páginas en paralelo (Http::pool)
     * 3. Texto limpio, con marcas de página, listo para chunking
     */
    public function extractTextFromPdf(string $pdfPath): string
    {
        $tempDir = sys_get_temp_dir() . '/ocr_' . uniqid();

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            $imageFiles = $this->renderPdfToImages($pdfPath, $tempDir);

            if (empty($imageFiles)) {
                throw new \RuntimeException("La conversión PDF->JPG no produjo imágenes: {$pdfPath}");
            }

            $pages = $this->ocrPagesInParallel($imageFiles);
            $fullText = $this->joinPages($pages);
        } catch (\Throwable $e) {
            Log::error('[OCR] Error: ' . $e->getMessage());
            throw $e;
        } finally {
            $this->deleteDirectory($tempDir);
        }

        if (mb_strlen(trim($fullText)) < 50) {
            throw new \Exception("Documento ilegible.");
        }

        return trim($fullText);
    }

    /**
     * PDF -> JPG. El DPI es la variable que más pesa en la calidad del OCR:
     * por debajo de 150 se pierden subíndices y texto de tabla.
     *
     * @return string[] rutas de imagen ordenadas por página
     */
    private function renderPdfToImages(string $pdfPath, string $tempDir): array
    {
        if (!Storage::disk('public')->exists($pdfPath)) {
            throw new \RuntimeException("No existe el archivo PDF en storage/public: {$pdfPath}");
        }

        $ilovepdf = new Ilovepdf(
            config('services.ilovepdf.public'),
            config('services.ilovepdf.secret')
        );

        $task = $ilovepdf->newTask('pdfjpg');
        $task->addFile(Storage::disk('public')->path($pdfPath));
        $task->setMode('pages');
        $task->setDpi($this->clampDpi((int) config('services.ocr.dpi', 200)));
        $task->execute();
        $task->download($tempDir);

        foreach (glob($tempDir . '/*') as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'zip') {
                $zip = new ZipArchive;
                if ($zip->open($file) === true) {
                    $zip->extractTo($tempDir);
                    $zip->close();
                    unlink($file);
                }
            }
        }

        $imageFiles = array_merge(
            glob($tempDir . '/*.jpg') ?: [],
            glob($tempDir . '/*.jpeg') ?: []
        );

        // Orden natural: página_2 antes que página_10.
        natsort($imageFiles);

        return array_values($imageFiles);
    }

    /**
     * El SDK rechaza fuera de 24-500; además por encima de 300 el modelo no gana
     * precisión y la imagen (y el costo por token de visión) crece al cuadrado.
     */
    private function clampDpi(int $dpi): int
    {
        return max(120, min(300, $dpi));
    }

    /**
     * OCR de todas las páginas en paralelo, por lotes del tamaño de concurrency.
     * Las páginas que fallan se reintentan; si aun así fallan se omiten y se registra.
     *
     * @param  string[] $imageFiles
     * @return array<int,string> texto indexado por número de página (1-based)
     */
    private function ocrPagesInParallel(array $imageFiles): array
    {
        $concurrency = max(1, (int) config('services.ocr.concurrency', 5));
        $retries = max(0, (int) config('services.ocr.retries', 2));

        $pages = [];
        $pending = [];
        foreach ($imageFiles as $index => $path) {
            $pending[$index + 1] = $path;
        }

        for ($attempt = 0; $attempt <= $retries && !empty($pending); $attempt++) {
            $failed = [];

            foreach (array_chunk($pending, $concurrency, true) as $batch) {
                $responses = Http::pool(function ($pool) use ($batch) {
                    foreach ($batch as $pageNum => $imagePath) {
                        $this->buildOcrRequest($pool->as((string) $pageNum), $imagePath);
                    }
                    return [];
                });

                foreach ($batch as $pageNum => $imagePath) {
                    $response = $responses[(string) $pageNum] ?? null;

                    if ($response instanceof \Throwable) {
                        $failed[$pageNum] = $imagePath;
                        Log::warning("[OCR] Página {$pageNum}: excepción " . $response->getMessage());
                        continue;
                    }

                    if (!$response || $response->failed()) {
                        $failed[$pageNum] = $imagePath;
                        Log::warning("[OCR] Página {$pageNum}: HTTP " . ($response?->status() ?? '?'));
                        continue;
                    }

                    $text = $this->extractOutputText($response->json());

                    if (mb_strlen(trim($text)) > 10) {
                        $pages[$pageNum] = trim($text);
                    } else {
                        // Página en blanco o solo sello: no es un fallo, no se reintenta.
                        Log::info("[OCR] Página {$pageNum} sin texto legible.");
                    }
                }
            }

            $pending = $failed;

            if (!empty($pending) && $attempt < $retries) {
                usleep(500000 * ($attempt + 1)); // backoff simple ante rate limit
            }
        }

        foreach ($pending as $pageNum => $_) {
            Log::warning("[OCR] Página {$pageNum} omitida tras agotar reintentos.");
        }

        ksort($pages);

        return $pages;
    }

    /**
     * Arma la petición de OCR de una página sobre el pool (no la ejecuta).
     */
    private function buildOcrRequest($request, string $imagePath)
    {
        $apiKey = config('services.ai.api_key') ?: env('AI_API_KEY');

        if (!$apiKey) {
            throw new \RuntimeException('Falta AI_API_KEY para el OCR.');
        }

        $dataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imagePath));

        return $request
            ->withToken($apiKey)
            ->timeout((int) config('services.ocr.timeout', 180))
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.ocr.model', 'gpt-4.1-mini'),
                'temperature' => 0,
                'max_output_tokens' => (int) config('services.ocr.max_output_tokens', 8000),
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'input_text', 'text' => $this->ocrPrompt()],
                            [
                                'type' => 'input_image',
                                'image_url' => $dataUri,
                                'detail' => config('services.ocr.detail', 'high'),
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Instrucciones de transcripción. El detalle importa: sin reglas explícitas de
     * tabla y diagrama el modelo resume en vez de transcribir, y ahí se pierde la
     * información que después el chatbot necesita citar.
     */
    private function ocrPrompt(): string
    {
        return <<<PROMPT
        Eres un motor de OCR. Transcribe la imagen a texto plano y Markdown. No resumas, no interpretes, no agregues comentarios.

        Reglas:
        1. Transcribe TODO el texto visible en el orden de lectura: encabezado, cuerpo, pie de página, sellos, notas al margen y texto dentro de figuras.
        2. Respeta saltos de línea, numeración y viñetas tal como aparecen. No corrijas la ortografía del documento.
        3. TABLAS: reprodúcelas como tabla Markdown con encabezado y separador (| col | col |). Una fila por fila real. Celda vacía se escribe vacía. Si una celda ocupa varias filas o columnas (merge), repite su valor en cada celda que abarca. No conviertas la tabla en lista.
        4. DIAGRAMAS DE FLUJO Y ORGANIGRAMAS: transcribe el texto de cada caja y, debajo, las conexiones una por línea con el formato "Caja A -> Caja B" (usa la etiqueta de la flecha si la tiene: "Caja A -[Sí]-> Caja B").
        5. GRÁFICAS: escribe el título, los ejes y su unidad, la leyenda, y luego los valores como tabla Markdown (serie | categoría | valor). Si los valores no están rotulados, escribe el valor aproximado leído del eje y marca la fila con "(aprox)".
        6. FORMULARIOS: transcribe como "Etiqueta: valor". Si el campo está vacío escribe "Etiqueta: (vacío)". Casillas: [x] marcada, [ ] sin marcar.
        7. FIRMAS Y SELLOS: describe entre corchetes lo que sí se lee, p. ej. [Firma: Juan Pérez] o [Sello ilegible].
        8. Si un fragmento es ilegible, escribe [ilegible] en su lugar. Nunca inventes texto.
        9. No envuelvas la salida completa en bloques de código.
        PROMPT;
    }

    /**
     * Parseo de la Responses API.
     */
    private function extractOutputText(?array $json): string
    {
        if (!$json) {
            return '';
        }

        if (!empty($json['output_text']) && is_string($json['output_text'])) {
            return $json['output_text'];
        }

        $text = '';
        foreach ($json['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text') {
                    $text .= $content['text'] ?? '';
                }
            }
        }

        return trim($text);
    }

    /**
     * Une las páginas con una marca que el chunker puede usar como límite natural
     * y que permite citar de qué página salió una respuesta.
     */
    private function joinPages(array $pages): string
    {
        $parts = [];
        foreach ($pages as $pageNum => $text) {
            $parts[] = "[Página {$pageNum}]\n" . $text;
        }

        return trim(implode("\n\n", $parts));
    }

    /**
     * Limpieza de temporales
     */
    private function deleteDirectory($dir): bool
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item);
        }

        return rmdir($dir);
    }
}
