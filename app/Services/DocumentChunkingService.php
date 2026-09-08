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

    private ?EmbeddingService $embeddingService = null;

    private bool $skipEmbed = false;

    private function embeddingService(): EmbeddingService
    {
        return $this->embeddingService ??= app(EmbeddingService::class);
    }

    private function structure(): SgcProcedureStructureService
    {
        return app(SgcProcedureStructureService::class);
    }

    /**
     * Genera y guarda el embedding de un chunk recién creado. Falla en silencio:
     * el chunk sin embedding lo recoge luego el comando chatbot:embed-chunks.
     */
    private function embedChunk(DocumentChunk $chunk, string $content): void
    {
        try {
            $service = $this->embeddingService();
            $vector = $service->embed($content);
            if ($vector !== null) {
                $chunk->embedding = $vector;
                $chunk->embedding_model = $service->getModel();
                $chunk->save();
            }
        } catch (\Throwable $e) {
            Log::warning("[CHUNKER] No se pudo generar embedding del chunk {$chunk->id}: " . $e->getMessage());
        }
    }

    public function chunkWordDocument(WordDocument $doc, bool $skipEmbed = false): void
    {
        $this->skipEmbed = $skipEmbed;
        $text = (string) $doc->contenido_texto;

        // 1. Fallback a estructura JSON si el texto plano falla
        if (mb_strlen(trim($text)) < 50 && $doc->contenido_estructurado) {
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

        // 5. Cortar por secciones reales del SGC (1. OBJETIVO, 9. RESPONSABLE…).
        $rawSegments = $this->splitBySemanticSections($text);

        // 6. Procesamiento con Buffer (no mezclar secciones distintas)
        $this->processSegmentsWithBuffer($doc->id, $rawSegments);
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

            $segmentTitle = $this->extractTitle($segment);

            // Si el buffer está vacío, inicializamos
            if (empty($buffer)) {
                $buffer = $segment;
                $bufferTitle = $segmentTitle;
                continue;
            }

            // No fusionar dos secciones canónicas distintas (Objetivo + Alcance, etc.).
            $bufferCanon = $this->structure()->detectCanonicalSection($bufferTitle !== '' ? $bufferTitle : $buffer);
            $segmentCanon = $this->structure()->detectCanonicalSection($segmentTitle !== '' ? $segmentTitle : $segment);
            if (
                $bufferCanon
                && $segmentCanon
                && ($bufferCanon['type'] ?? '') !== ($segmentCanon['type'] ?? '')
            ) {
                if ($this->saveToDb($docId, $buffer, $bufferTitle)) {
                    $count++;
                }
                $buffer = $segment;
                $bufferTitle = $segmentTitle;
                continue;
            }

            // Calculamos el tamaño hipotético si unimos
            $potentialSize = mb_strlen($buffer) + mb_strlen($segment) + 2;

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
                $newCanon = $this->structure()->detectCanonicalSection($segmentTitle !== '' ? $segmentTitle : $segment);
                $bufferTitle = $newCanon ? $segmentTitle : ($bufferTitle ?: $segmentTitle);
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
        // 🛡️ PRIMERA CAPA: Sanitización UTF-8 (prevenir json_encode errors)
        if (!mb_check_encoding($text, 'UTF-8')) {
            Log::warning("[CHUNKER] UTF-8 inválido detectado al inicio. Corrigiendo...");
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        // Remover caracteres nulos y bytes peligrosos
        $text = str_replace([chr(0), "\0", "\x00"], '', $text);

        // Normalización de caracteres extraños y control
        $text = str_replace(["\r\n", "\r", "\x07", "\x0B", "\x0C", "\x0D"], "\n", $text);
        $text = preg_replace('/[\x00-\x09\x0E-\x1F\x7F]/u', ' ', $text);
        
        // Unificar espacios y saltos de línea múltiples
        $text = preg_replace("/[ \t]+/", " ", $text);
        $text = preg_replace("/\n+/", "\n", $text);

        // Títulos canónicos del SGC: forzar salto de línea para el split.
        $keywords = [
            'DEFINICIONES',
            'RESPONSABLE\s+DE(?:L)?\s+(?:ELEMENTO|PROCEDIMIENTO)',
            'OBJETIVO',
            'ALCANCE',
            'NORMAS(?:\s+GENERALES)?',
            'DESARROLLO',
            'EVIDENCIAS',
            'DOCUMENTOS\s+DE\s+REFERENCIA',
            'RIESGOS(?:\s+Y\s+DESCRIPCI[OÓ]N)?',
        ];

        $pattern = '/(?<!\n)((?:\d+\.\s*)?(?:' . implode('|', $keywords) . '))\b/iu';
        $text = preg_replace($pattern, "\n$1", $text) ?? $text;

        return trim($text);
    }

    private function splitBySemanticSections(string $text): array
    {
        $headers = 'OBJETIVO|ALCANCE|DEFINICIONES|NORMAS(?:\s+GENERALES)?|DESARROLLO|'
            . 'EVIDENCIAS|RIESGOS(?:\s+Y\s+DESCRIPCI[OÓ]N)?|'
            . 'RESPONSABLE\s+DE(?:L)?\s+(?:ELEMENTO|PROCEDIMIENTO)|'
            . 'DOCUMENTOS\s+DE\s+REFERENCIA';

        $parts = preg_split(
            '/\n(?=(?:\d+\.\s+)?(?:' . $headers . ')\b)/iu',
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

        // 🛡️ PROTECCIÓN UTF-8: Sanitizar antes de guardar para evitar errores de json_encode
        $content = $this->sanitizeUtf8ForJson($content);
        $title = $forceTitle ?? $this->extractTitle($content);
        $title = $this->sanitizeUtf8ForJson($title);
        $typeSource = $title !== '' ? ($title . "\n" . $content) : $content;

        try {
            $chunk = DocumentChunk::create([
                'word_document_id' => $docId,
                'section_title'    => $title,
                'chunk_type'       => $this->detectType($typeSource),
                'content'          => $content,
                'char_count'       => $length,
            ]);

            if (!$this->skipEmbed) {
                $this->embedChunk($chunk, $content);
            }
        } catch (\Exception $e) {
            // Si falla por UTF-8 incluso después de sanitizar, registrar y continuar
            if (strpos($e->getMessage(), 'UTF-8') !== false || strpos($e->getMessage(), 'json_encode') !== false) {
                Log::error("[CHUNKER] ❌ Error UTF-8 al guardar chunk (docId: {$docId}): " . $e->getMessage());
                Log::error("[CHUNKER] Contenido problemático (primeros 100 chars): " . mb_substr($content, 0, 100));
                return false;
            }
            // Si es otro tipo de error, re-lanzarlo
            throw $e;
        }

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
        $header = mb_strtoupper(mb_substr($text, 0, 400));

        // La tabla de pasos usa "Responsable | Actividad": es Desarrollo, no §9/10.
        if (
            preg_match('/RESPONSABLE.{0,40}ACTIVIDAD/u', $header)
            || preg_match('/\|\s*RESPONSABLE\s*\|\s*ACTIVIDAD/u', $text)
        ) {
            return 'development';
        }

        $canon = $this->structure()->detectCanonicalSection($text);
        if ($canon) {
            return $canon['type'];
        }

        if (str_contains($header, 'DEFINICIONES')) return 'definitions';
        if (preg_match('/RESPONSABLE\s+DE(?:L)?\s+(?:ELEMENTO|PROCEDIMIENTO)/u', $header)) {
            return 'responsibles';
        }
        if (str_contains($header, 'OBJETIVO')) return 'objective';
        if (str_contains($header, 'ALCANCE')) return 'alcance';
        if (str_contains($header, 'NORMAS')) return 'norms';
        if (str_contains($header, 'DOCUMENTOS')) return 'references';
        if (str_contains($header, 'DESARROLLO')) return 'development';
        if (str_contains($header, 'EVIDENCIAS')) return 'evidences';
        if (str_contains($header, 'RIESGOS')) return 'risks';
        return 'general';
    }

    private function extractTitle(string $text): string
    {
        $canon = $this->structure()->detectCanonicalSection($text);
        if ($canon) {
            return $canon['title'];
        }

        $lines = explode("\n", $text);
        $title = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $this->isNoiseTitleLine($line)) {
                continue;
            }
            $title = $line;
            break;
        }
        
        // Si el título es muy corto, agregamos la segunda línea para contexto
        if (mb_strlen($title) < 10 && isset($lines[1])) {
            $title .= ' ' . trim($lines[1]);
        }
        
        // Limpiamos caracteres raros del título
        $title = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $title);
        
        return mb_substr($title, 0, 150);
    }

    private function isNoiseTitleLine(string $line): bool
    {
        return (bool) preg_match(
            '/^\[P[áa]gina\s+\d+\]$|portal del sgc|documento controlado|copia no controlada|^p[áa]gina\s+\d+(\s+de\s+\d+)?$/iu',
            $line
        );
    }

    /**
     * 🛡️ Sanitización UTF-8 para json_encode
     * Última línea de defensa antes de guardar en BD
     */
    private function sanitizeUtf8ForJson(string $text): string
    {
        if (empty($text)) return '';

        // 1. Verificar si ya es UTF-8 válido
        if (!mb_check_encoding($text, 'UTF-8')) {
            Log::warning("[CHUNKER] ⚠️ UTF-8 inválido detectado en chunk. Limpiando...");
            
            // Intentar recuperar usando iconv
            $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
            if ($cleaned !== false && $cleaned !== '') {
                $text = $cleaned;
            } else {
                // Si iconv falla, usar mb_convert_encoding
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }
        }

        // 2. Remover caracteres problemáticos para json_encode
        // Eliminar caracteres de control (excepto \n, \r, \t)
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // 3. Remover emojis y caracteres de 4 bytes que rompen MySQL
        $text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text); // Emoticons
        $text = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $text); // Misc Symbols
        $text = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $text); // Transport
        $text = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $text);   // Misc symbols
        $text = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $text);   // Dingbats

        // 4. Test final: Intentar json_encode
        $testJson = @json_encode($text, JSON_UNESCAPED_UNICODE);
        
        if ($testJson === false && json_last_error() === JSON_ERROR_UTF8) {
            Log::warning("[CHUNKER] 🔴 json_encode aún falla. Aplicando filtro de emergencia...");
            
            // EMERGENCIA: Solo permitir caracteres seguros (ASCII extendido + latinos)
            $text = preg_replace('/[^\x20-\x7E\xA0-\xFF\r\n\t]/u', '', $text);
            
            // Si TODAVÍA falla, usar solo ASCII puro
            $testJson = @json_encode($text, JSON_UNESCAPED_UNICODE);
            if ($testJson === false) {
                Log::error("[CHUNKER] 🆘 CRÍTICO: Usando solo ASCII puro.");
                $text = preg_replace('/[^\x20-\x7E\r\n\t]/', '', $text);
            }
        }

        return $text;
    }
}