<?php

namespace App\Console\Commands;

use App\Models\WordDocument;
use App\Services\DocumentChunkingService;
use App\Services\OpenAiOcrService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Re-OCR de word_documents ya existentes usando el PDF de elementos.archivo_es_formato.
 * No crea registros nuevos: actualiza contenido_texto y regenera document_chunks.
 */
class ReprocesarOcrExistente extends Command
{
    protected $signature = 'documento:reprocesar-ocr
                            {--from=1 : ID inicial (inclusive)}
                            {--to=75 : ID final (inclusive)}
                            {--id= : Un solo ID (ignora from/to)}
                            {--force : Reprocesar aunque el texto ya tenga formato [Página N]}
                            {--dry-run : Solo listar, no tocar la BD}';

    protected $description = 'Actualiza word_documents y document_chunks con OCR nuevo, usando los PDF ya guardados en elementos';

    public function handle(OpenAiOcrService $ocr, DocumentChunkingService $chunker): int
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '512M');

        $query = WordDocument::with('elemento')->orderBy('id');

        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        } else {
            $query->whereBetween('id', [
                (int) $this->option('from'),
                (int) $this->option('to'),
            ]);
        }

        $docs = $query->get();

        if ($docs->isEmpty()) {
            $this->warn('No hay word_documents en ese rango.');
            return self::SUCCESS;
        }

        $this->info("Documentos encontrados: {$docs->count()}");

        $ok = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($docs as $doc) {
            $label = "word_document #{$doc->id} (elemento {$doc->elemento_id})";

            $pdfRel = ltrim((string) ($doc->elemento->archivo_es_formato ?? ''), '/');

            if ($pdfRel === '' || !Storage::disk('public')->exists($pdfRel)) {
                $this->error("  ✗ {$label}: no hay PDF en elementos.archivo_es_formato");
                $failed++;
                continue;
            }

            $alreadyNewOcr = str_starts_with(ltrim((string) $doc->contenido_texto), '[Página ');

            if ($alreadyNewOcr && !$this->option('force')) {
                $this->line("  → {$label}: ya tiene OCR nuevo, se omite (usa --force para forzar)");
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  · {$label}: {$pdfRel} (" . strlen((string) $doc->contenido_texto) . ' chars actuales)');
                $ok++;
                continue;
            }

            $this->info("  OCR {$label} ← {$pdfRel}");

            try {
                $texto = $ocr->extractTextFromPdf($pdfRel);

                if (mb_strlen(trim($texto)) < 20) {
                    throw new \RuntimeException('OCR devolvió texto insuficiente.');
                }

                $texto = $this->sanitizarUtf8($texto);

                $charsAntes = strlen((string) $doc->contenido_texto);

                $doc->contenido_texto = $texto;
                $doc->estado = 'procesado';
                $doc->save();

                $chunker->chunkWordDocument($doc->fresh());

                $chunks = $doc->chunks()->count();
                $this->info("    ✓ chars {$charsAntes} → " . strlen($texto) . ", chunks={$chunks}");
                $ok++;
            } catch (\Throwable $e) {
                Log::error("[REPROCESAR-OCR] {$label}: " . $e->getMessage());
                $this->error("    ✗ {$e->getMessage()}");

                try {
                    WordDocument::where('id', $doc->id)->update(['estado' => 'error']);
                } catch (\Throwable $x) {
                    Log::error("[REPROCESAR-OCR] no se pudo marcar error en #{$doc->id}: " . $x->getMessage());
                }

                $failed++;
            }
        }

        $this->newLine();
        $this->info("Listo. ok={$ok} omitidos={$skipped} fallidos={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function sanitizarUtf8(string $texto): string
    {
        if ($texto === '') {
            return '';
        }

        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
        $texto = str_replace(["\0", chr(0)], '', $texto);

        if (!mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
            if (!mb_check_encoding($texto, 'UTF-8')) {
                $texto = (string) iconv('UTF-8', 'UTF-8//IGNORE', $texto);
            }
        }

        $texto = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $texto);
        $texto = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $texto);

        return trim($texto);
    }
}
