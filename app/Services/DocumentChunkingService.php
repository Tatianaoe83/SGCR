<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\WordDocument;
use Illuminate\Support\Facades\Log;

class DocumentChunkingService
{
    const MAX_CHUNK_SIZE = 2000;
    const SAFE_MIN_CHUNK = 20;

    public function chunkWordDocument(WordDocument $doc): void
    {
        Log::info("🚀 [CHUNKER] Iniciando para Doc ID: {$doc->id}");

        $text = (string) $doc->contenido_texto;

        // Fallback a estructura JSON si el texto plano falla
        if (mb_strlen(trim($text)) < 50 && $doc->contenido_estructurado) {
            Log::info("[CHUNKER] Texto plano vacío, usando contenido estructurado.");
            $text = $this->buildTextFromStructuredContent($doc->contenido_estructurado);
        }

        if (mb_strlen(trim($text)) < self::SAFE_MIN_CHUNK) {
            Log::warning("[CHUNKER] Texto final demasiado corto (" . mb_strlen($text) . " chars). Abortando.");
            return;
        }

        // Limpieza
        $text = $this->sanitizeText($text);
        Log::info("[CHUNKER] Texto sanitizado. Longitud: " . mb_strlen($text));

        // Borrado previo
        $deleted = DocumentChunk::where('word_document_id', $doc->id)->delete();
        Log::info("[CHUNKER] Eliminados {$deleted} chunks anteriores.");

        // División Semántica
        $chunks = $this->splitBySemanticSections($text);
        Log::info("[CHUNKER] Texto dividido en " . count($chunks) . " secciones semánticas.");

        // Procesamiento
        $totalGuardados = 0;
        foreach ($chunks as $index => $chunkContent) {
            if ($this->processAndSave($doc->id, $chunkContent, $index)) {
                $totalGuardados++;
            }
        }

        Log::info("CHUNKER] Finalizado. Total chunks guardados: {$totalGuardados}");
    }

    private function sanitizeText(string $text): string
    {
        $text = str_replace(["\x07", "\x0B", "\x0C", "\x0D"], "\n", $text);
        $text = preg_replace('/[\x00-\x09\x0E-\x1F\x7F]/u', ' ', $text);
        $text = preg_replace("/\n+/", "\n", $text);
        $text = preg_replace("/[ \t]+/", " ", $text);

        $keywords = [
            'DEFINICIONES','RESPONSABLES?','OBJETIVO','ALCANCE',
            'NORMAS','DESARROLLO','EVIDENCIAS','DIAGRAMA',
            'DOCUMENTOS','RIESGOS','PARTICIPANTES','AUTORIZ'
        ];

        $pattern = '/(?<!\n)\b(' . implode('|', $keywords) . ')\b/iu';
        $text = preg_replace($pattern, "\n$1", $text);

        return trim($text);
    }

    private function splitBySemanticSections(string $text): array
    {
        $parts = preg_split(
            '/\n(?=(\d+(\.\d+)*\.?\s+|DEFINICIONES|RESPONSABLES?|OBJETIVO|TIPOS|ALCANCE|NORMAS|DESARROLLO|EVIDENCIAS|DIAGRAMA|RIESGOS|DOCUMENTOS))/iu',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        
        return $parts ?: [$text];
    }

    private function processAndSave($docId, $content, $index): bool
    {
        $content = trim($content);
        $length  = mb_strlen($content);

        // Si es demasiado corto, solo lo guardamos si parece un título importante
        // Bajé el límite a 10 para no ser tan agresivo borrando datos.
        if ($length < 10) { 
            // Log::debug("Start skipping chunk #{$index} (Too short: {$length})");
            return false;
        }

        // Si es gigante, activamos emergencia
        if ($length > self::MAX_CHUNK_SIZE) {
            Log::info("[CHUNKER] Sección #{$index} GIGANTE ({$length} chars). Activando corte de emergencia.");
            $this->emergencySplitAndSave($docId, $content);
            return true; // Contamos como procesado (aunque se guarden varios sub-chunks)
        }

        $this->saveToDb($docId, $content);
        return true;
    }

    private function emergencySplitAndSave($docId, $giantContent)
    {
        $lines = explode("\n", $giantContent);

        // Si no hay saltos de línea (bloque sólido), cortamos por caracteres
        if (count($lines) < 2) {
            Log::info("☢️ [CHUNKER] Opción nuclear: Corte por caracteres (sin saltos de línea).");
            $parts = mb_str_split($giantContent, 1500);
        } else {
            $parts = $lines;
        }

        $buffer = '';

        foreach ($parts as $part) {
            $part = trim($part);
            if (!$part) continue;

            // FIX: Si una sola línea del explode sigue siendo > 1500, la cortamos también
            if (mb_strlen($part) > 1500) {
                if ($buffer) {
                    $this->saveToDb($docId, $buffer);
                    $buffer = '';
                }
                
                // Recurso nuclear para esta línea específica gigante
                $subParts = mb_str_split($part, 1500);
                foreach($subParts as $sub) {
                    $this->saveToDb($docId, $sub);
                }
                continue;
            }

            if (mb_strlen($buffer . "\n" . $part) > 1500) {
                $this->saveToDb($docId, $buffer);
                $buffer = $part;
            } else {
                $buffer .= ($buffer ? "\n" : '') . $part;
            }
        }

        if ($buffer) {
            $this->saveToDb($docId, $buffer);
        }
    }

    private function saveToDb($docId, $content)
    {
        DocumentChunk::create([
            'word_document_id' => $docId,
            'section_title'    => $this->extractTitle($content),
            'chunk_type'       => $this->detectType($content),
            'content'          => $content,
            'char_count'       => mb_strlen($content),
        ]);
    }

    
    private function buildTextFromStructuredContent($json): string
    {
        $data = is_array($json) ? $json : json_decode($json, true);
        if (!is_array($data)) return '';
        $text = '';
        foreach ($data['parrafos'] ?? [] as $p) {
            $text .= trim($p) . "\n";
        }
        return trim($text);
    }

    private function detectType(string $text): string
    {
        $t = mb_strtoupper($text);
        if (str_contains($t, 'DEFINICIONES')) return 'definitions';
        if (str_contains($t, 'RESPONSABLE')) return 'responsibles';
        if (str_contains($t, 'OBJETIVO')) return 'objective';
        if (str_contains($t, 'NORMAS')) return 'norms';
        if (str_contains($t, 'DOCUMENTOS')) return 'references';
        if (str_contains($t, 'DESARROLLO')) return 'development';
        return 'general';
    }

    private function extractTitle(string $text): string
    {
        $lines = explode("\n", $text);
        $title = trim($lines[0] ?? '');
        if (mb_strlen($title) < 8 && isset($lines[1])) {
            $title .= ' ' . trim($lines[1]);
        }
        return mb_substr($title, 0, 150);
    }
}