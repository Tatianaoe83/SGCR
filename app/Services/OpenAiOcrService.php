<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ilovepdf\Ilovepdf;
use ZipArchive;

class OpenAiOcrService
{
    /**
     * Flujo:
     * 1. PDF → JPG (iLovePDF)
     * 2. OCR con GPT-4.1-nano
     * 3. Texto limpio listo para chunking
     */
    public function extractTextFromPdf(string $pdfPath): string
    {
        $fullText = '';
        $tempDir = sys_get_temp_dir() . '/ocr_' . uniqid();

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            Log::info("☁️ [OCR] Enviando PDF a iLovePDF...");

            // 1️⃣ PDF → JPG
            $ilovepdf = new Ilovepdf(
                config('services.ilovepdf.public'),
                config('services.ilovepdf.secret')
            );

            $task = $ilovepdf->newTask('pdfjpg');
            $task->addFile($pdfPath);
            $task->execute();
            $task->download($tempDir);

            Log::info("📥 Imágenes listas. Iniciando OCR...");

            // 2️⃣ Descomprimir ZIP si existe
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

            $imageFiles = glob($tempDir . '/*.jpg');
            sort($imageFiles);

            Log::info("👁️ [OCR AI] Leyendo " . count($imageFiles) . " páginas.");

            // 3️⃣ OCR página por página
            foreach ($imageFiles as $index => $imagePath) {
                $pageNum = $index + 1;

                try {
                    $pageText = $this->analyzeImageWithNano($imagePath);

                    if (strlen(trim($pageText)) > 10) {
                        $fullText .= $pageText . "\n\n";
                        Log::info("✅ Página {$pageNum} leída con texto.");
                    } else {
                        Log::warning("⚠️ Página {$pageNum} sin texto OCR.");
                    }

                } catch (\Throwable $e) {
                    Log::warning("⏭️ Página {$pageNum} omitida por error OCR: " . $e->getMessage());
                }
            }

        } catch (\Throwable $e) {
            Log::error("❌ Error OCR Service: " . $e->getMessage());
            throw $e;
        } finally {
            $this->deleteDirectory($tempDir);
        }

        if (strlen(trim($fullText)) < 50) {
            throw new \Exception("Documento ilegible.");
        }

        return trim($fullText);
    }

    /**
     * OCR con GPT-4.1-nano (Responses API)
     */
    private function analyzeImageWithNano(string $imagePath): string
    {
        $apiKey = env('AI_API_KEY');

        if (!$apiKey) {
            throw new \Exception("Falta la OPENAI_API_KEY en el archivo .env");
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $dataUri = 'data:image/jpeg;base64,' . $imageData;

        $response = Http::withToken($apiKey)
            ->timeout(1200)
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-4.1-nano-2025-04-14',
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' =>
                                    'Transcribe TODO el texto visible de la imagen exactamente. 
                                     Mantén saltos de línea. 
                                     Si hay tablas, usa Markdown. 
                                     No agregues comentarios ni explicaciones.'
                            ],
                            [
                                'type' => 'input_image',
                                'image_url' => $dataUri
                            ]
                        ]
                    ]
                ],
                'max_output_tokens' => 3000,
            ]);

        if ($response->failed()) {
            throw new \Exception("OpenAI API Error: " . $response->body());
        }

        // 🔑 PARSEO CORRECTO DEL TEXTO
        $output = $response->json('output') ?? [];
        $text = '';

        foreach ($output as $item) {
            if (($item['type'] ?? null) === 'message') {
                foreach ($item['content'] ?? [] as $content) {
                    if (($content['type'] ?? null) === 'output_text') {
                        $text .= $content['text'] ?? '';
                    }
                }
            }
        }

        return trim($text);
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
