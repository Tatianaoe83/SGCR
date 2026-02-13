<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\WordDocument;
use Illuminate\Support\Facades\Log;

class DocumentChunkingService
{
    // Tamaño máximo absoluto (límite duro para la DB o Context Window)
    const MAX_CHUNK_SIZE = 2500;

    const TARGET_CHUNK_SIZE = 1200; 

    public function chunkWordDocument(WordDocument $doc): void
    {
        Log::info("CHUNKER] Iniciando optimización para Doc ID: {$doc->id}");

        $text = (string) $doc->contenido_texto;

        // 1. Fallback a estructura JSON si el texto plano falla
        if (mb_strlen(trim($text)) < 50 && $doc->contenido_estructurado) {
            Log::info("[CHUNKER] Texto plano vacío, usando contenido estructurado.");
            $text = $this->buildTextFromStructuredContent($doc->contenido_estructurado);
        }

        // 2. Validación inicial
        if (mb_strlen(trim($text)) < 50) {
            Log::warning("[CHUNKER] Texto final insuficiente (" . mb_strlen($text) . " chars). Abortando.");
            return;
        }

        // 3. Limpieza profunda
        $text = $this->sanitizeText($text);
        
        // 4. Borrado previo (Clean Slate)
        $deleted = DocumentChunk::where('word_document_id', $doc->id)->delete();
        Log::info("[CHUNKER] Limpieza completada. Eliminados {$deleted} chunks previos.");

        // 5. División Semántica (Solo por palabras clave grandes, NO por números)
        $rawSegments = $this->splitBySemanticSections($text);
        
        // 6. Procesamiento con Buffer (Acumulación)
        $chunksSaved = $this->processSegmentsWithBuffer($doc->id, $rawSegments);

        Log::info("[CHUNKER] Finalizado. Total chunks optimizados guardados: {$chunksSaved}");
    }

    /**
     * Une segmentos pequeños en chunks coherentes
     */
    private function processSegmentsWithBuffer(int $docId, array $segments): int
    {
        $count = 0;
        $buffer = '';
        $bufferTitle = '';

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if (mb_strlen($segment) < 5) continue; // Ignorar ruido OCR extremo

            // Si el buffer está vacío, inicializamos
            if (empty($buffer)) {
                $buffer = $segment;
                $bufferTitle = $this->extractTitle($segment);
                continue;
            }

            // Calculamos el tamaño hipotético si unimos
            $potentialSize = mb_strlen($buffer) + mb_strlen($segment) + 2;

            // 
            // Unimos si: No superamos el máximo
            if ($potentialSize <= self::MAX_CHUNK_SIZE && 
               (mb_strlen($buffer) < self::TARGET_CHUNK_SIZE || mb_strlen($segment) < 200)) {
                
                $buffer .= "\n\n" . $segment;
                
            } else {
                // El buffer ya está lleno, lo guardamos
                if ($this->saveToDb($docId, $buffer, $bufferTitle)) {
                    $count++;
                }

                // Iniciamos nuevo buffer con el segmento actual
                $buffer = $segment;
                $bufferTitle = $this->extractTitle($segment);
            }
        }

        // Guardar lo que haya quedado pendiente en el buffer final
        if (!empty($buffer)) {
            if ($this->saveToDb($docId, $buffer, $bufferTitle)) {
                $count++;
            }
        }

        return $count;
    }

    private function sanitizeText(string $text): string
    {
        // Normalización de caracteres extraños y control
        $text = str_replace(["\r\n", "\r", "\x07", "\x0B", "\x0C", "\x0D"], "\n", $text);
        $text = preg_replace('/[\x00-\x09\x0E-\x1F\x7F]/u', ' ', $text);
        
        // Unificar espacios y saltos de línea múltiples
        $text = preg_replace("/[ \t]+/", " ", $text);
        $text = preg_replace("/\n+/", "\n", $text);

        // Palabras clave para forzar estructura
        $keywords = [
            'DEFINICIONES','RESPONSABLES?','OBJETIVO','ALCANCE',
            'NORMAS','DESARROLLO','EVIDENCIAS','DIAGRAMA',
            'DOCUMENTOS','RIESGOS','PARTICIPANTES','AUTORIZ'
        ];

        // Asegurar que las palabras clave tengan un salto de línea antes
        $pattern = '/(?<!\n)\b(' . implode('|', $keywords) . ')\b/iu';
        $text = preg_replace($pattern, "\n$1", $text);

        return trim($text);
    }

    private function splitBySemanticSections(string $text): array
    {
        // MODIFICACIÓN CLAVE: Se eliminó la regex de números (\d+)
        // Esto evita que el OCR corte por "1.", "2.3", paginación, etc.
        // Solo corta si encuentra TÍTULOS EN MAYÚSCULAS definidos.
        $parts = preg_split(
            '/\n(?=(DEFINICIONES|RESPONSABLES?|OBJETIVO|TIPOS|ALCANCE|NORMAS|DESARROLLO|EVIDENCIAS|DIAGRAMA|RIESGOS|DOCUMENTOS))/iu',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        
        return $parts ?: [$text];
    }

    private function saveToDb($docId, $content, $forceTitle = null): bool
    {
        $content = trim($content);
        $length = mb_strlen($content);

        // Si después de todo el proceso quedó algo muy chico (ruido), lo descartamos
        if ($length < 30) return false;

        // Si es gigante (caso raro donde una sola sección es > 2500 chars), corte de emergencia
        if ($length > self::MAX_CHUNK_SIZE) {
            $this->emergencySplitAndSave($docId, $content);
            return true;
        }

        DocumentChunk::create([
            'word_document_id' => $docId,
            'section_title'    => $forceTitle ?? $this->extractTitle($content),
            'chunk_type'       => $this->detectType($content),
            'content'          => $content,
            'char_count'       => $length,
        ]);

        return true;
    }

    private function emergencySplitAndSave($docId, $giantContent)
    {
        Log::warning("[CHUNKER] Aplicando corte de emergencia a bloque de " . mb_strlen($giantContent) . " chars.");
        
        // Intentamos dividir por párrafos primero
        $lines = explode("\n", $giantContent);
        $buffer = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            // Si una sola línea es monstruosa, usamos corte duro de caracteres
            if (mb_strlen($line) > self::MAX_CHUNK_SIZE) {
                if ($buffer) {
                    $this->saveToDb($docId, $buffer); 
                    $buffer = '';
                }

                $chunks = mb_str_split($line, self::MAX_CHUNK_SIZE);
                foreach ($chunks as $chunk) $this->saveToDb($docId, $chunk);
                continue;
            }

            if (mb_strlen($buffer . "\n" . $line) > self::MAX_CHUNK_SIZE) {
                $this->saveToDb($docId, $buffer);
                $buffer = $line;
            } else {
                $buffer .= ($buffer ? "\n" : '') . $line;
            }
        }

        if ($buffer) $this->saveToDb($docId, $buffer);
    }

    private function buildTextFromStructuredContent($json): string
    {
        $data = is_array($json) ? $json : json_decode($json, true);
        if (!is_array($data)) return '';
        
        $text = '';
        foreach ($data['parrafos'] ?? [] as $p) {
            $text .= trim($p) . "\n\n"; 
        }
        return trim($text);
    }

    private function detectType(string $text): string
    {
        // Analizamos solo los primeros 100 caracteres para eficiencia
        $header = mb_strtoupper(mb_substr($text, 0, 100));
        
        if (str_contains($header, 'DEFINICIONES')) return 'definitions';
        if (str_contains($header, 'RESPONSABLE')) return 'responsibles';
        if (str_contains($header, 'OBJETIVO')) return 'objective';
        if (str_contains($header, 'NORMAS')) return 'norms';
        if (str_contains($header, 'DOCUMENTOS')) return 'references';
        if (str_contains($header, 'DESARROLLO')) return 'development';
        return 'general';
    }

    private function extractTitle(string $text): string
    {
        $lines = explode("\n", $text);
        // Intentar tomar la primera línea no vacía
        $title = '';
        foreach($lines as $line) {
            if(trim($line) !== '') {
                $title = trim($line);
                break;
            }
        }
        
        // Si el título es muy corto, agregamos la segunda línea para contexto
        if (mb_strlen($title) < 10 && isset($lines[1])) {
            $title .= ' ' . trim($lines[1]);
        }
        
        // Limpiamos caracteres raros del título
        $title = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $title);
        
        return mb_substr($title, 0, 150);
    }
}