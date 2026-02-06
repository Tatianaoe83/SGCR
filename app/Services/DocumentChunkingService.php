<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\WordDocument;

class DocumentChunkingService
{
    public function chunkWordDocument(WordDocument $doc): void
    {
        // =========================
        // 1. OBTENER TEXTO BASE
        // =========================
        $text = trim((string) $doc->contenido_texto);

        // Si NO hay texto plano, intentamos desde contenido_estructurado
        if (mb_strlen($text) < 50 && $doc->contenido_estructurado) {
            $text = $this->buildTextFromStructuredContent($doc->contenido_estructurado);
        }

        if (mb_strlen(trim($text)) < 50) {
            return; // nada usable
        }

        // =========================
        // 2. LIMPIAR CHUNKS ANTERIORES
        // =========================
        DocumentChunk::where('word_document_id', $doc->id)->delete();

        // =========================
        // 3. NORMALIZAR TEXTO
        // =========================
        $text = preg_replace("/\r\n|\r/", "\n", $text);

        // =========================
        // 4. SPLIT POR SECCIONES
        // =========================
        $sections = preg_split(
            '/\n(?=(\d+(\.\d+)*\.\s+|DEFINICIONES|RESPONSABLES?|OBJETIVO|TIPOS))/iu',
            "\n" . $text
        );

        // =========================
        // 5. CREAR CHUNKS
        // =========================
        foreach ($sections as $section) {
            $section = trim($section);

            if (mb_strlen($section) < 120) {
                continue;
            }

            DocumentChunk::create([
                'word_document_id' => $doc->id,
                'section_title'    => $this->extractTitle($section),
                'chunk_type'       => $this->detectType($section),
                'content'          => $section,
                'char_count'       => mb_strlen($section),
            ]);
        }
    }

    // =========================
    // ARMAR TEXTO DESDE JSON
    // =========================
    private function buildTextFromStructuredContent($json): string
    {
        $data = is_array($json) ? $json : json_decode($json, true);

        if (!is_array($data)) return '';

        $text = '';

        if (!empty($data['parrafos'])) {
            foreach ($data['parrafos'] as $p) {
                $text .= trim($p) . "\n\n";
            }
        }

        return trim($text);
    }

    private function detectType(string $text): string
    {
        $t = mb_strtoupper($text);

        if (str_contains($t, 'DEFINICIONES')) return 'definitions';
        if (str_contains($t, 'RESPONSABLE')) return 'responsibles';
        if (str_contains($t, 'OBJETIVO')) return 'objective';

        return 'general';
    }

    private function extractTitle(string $text): string
    {
        $lines = explode("\n", $text);
        return mb_substr(trim($lines[0]), 0, 120);
    }
}
