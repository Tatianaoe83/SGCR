<?php

namespace App\Console\Commands;

use App\Models\Elemento;
use App\Models\WordDocument;
use App\Services\DocumentChunkingService;
use App\Services\SgcProcedureStructureService;
use Illuminate\Console\Command;

class MejorarBobCommand extends Command
{
    protected $signature = 'chatbot:mejorar-bob
                            {--solo-responsable : Solo rellenar puesto_responsable_id}
                            {--solo-rechunk : Solo re-chunkear los Word}
                            {--skip-embed : Re-chunkear sin llamar a la API de embeddings}
                            {--force-responsable : Sobrescribir puesto_responsable_id aunque ya tenga valor}';

    protected $description = 'Rellena responsables desde el Word y re-chunka por secciones reales del SGC';

    public function handle(
        SgcProcedureStructureService $structure,
        DocumentChunkingService $chunker
    ): int {
        $soloResp = (bool) $this->option('solo-responsable');
        $soloChunk = (bool) $this->option('solo-rechunk');

        if (!$soloChunk) {
            $this->syncResponsables($structure);
        }

        if (!$soloResp) {
            $this->rechunkDocuments($chunker, (bool) $this->option('skip-embed'));
        }

        if (!$soloResp && !$this->option('skip-embed')) {
            $this->info('Generando embeddings faltantes…');
            $this->call('chatbot:embed-chunks');
        } elseif (!$soloResp && $this->option('skip-embed')) {
            $this->warn('Chunks sin embedding. Corre: php artisan chatbot:embed-chunks');
        }

        return self::SUCCESS;
    }

    private function syncResponsables(SgcProcedureStructureService $structure): void
    {
        $force = (bool) $this->option('force-responsable');
        $elementos = Elemento::query()
            ->with(['wordDocument:id,elemento_id,contenido_texto', 'puestoResponsable:id_puesto_trabajo,nombre'])
            ->whereHas('tipoElemento', fn ($q) => $q->whereIn('nombre', ['Procedimiento', 'Procedimiento_Firmas']))
            ->whereHas('wordDocument', fn ($q) => $q->whereNotNull('contenido_texto')->where('contenido_texto', '!=', ''))
            ->get();

        $this->info("Sincronizando responsables de {$elementos->count()} procedimientos con Word…");

        $updated = 0;
        $already = 0;
        $missing = 0;
        $uncataloged = 0;

        foreach ($elementos as $el) {
            $result = $structure->syncElementoResponsable($el, $force);
            if ($result['updated']) {
                $updated++;
                $this->line("  {$el->folio_elemento} → {$result['nombre']}");
            } elseif (($result['reason'] ?? '') === 'ya_asignado' || ($result['reason'] ?? '') === 'igual') {
                $already++;
            } elseif (($result['reason'] ?? '') === 'puesto_no_catalogado') {
                $uncataloged++;
                $this->warn("  {$el->folio_elemento}: «{$result['nombre']}» no está en el catálogo de puestos");
            } else {
                $missing++;
            }
        }

        $this->info("Responsables: {$updated} actualizados, {$already} ya tenían, {$missing} sin sección, {$uncataloged} sin puesto en catálogo.");
    }

    private function rechunkDocuments(DocumentChunkingService $chunker, bool $skipEmbed): void
    {
        $docs = WordDocument::query()
            ->whereNotNull('contenido_texto')
            ->where('contenido_texto', '!=', '')
            ->get();

        $this->info("Re-chunkeando {$docs->count()} documentos" . ($skipEmbed ? ' (sin embeddings)' : '') . '…');
        $bar = $this->output->createProgressBar($docs->count());
        $bar->start();

        $ok = 0;
        $fail = 0;
        foreach ($docs as $doc) {
            try {
                $chunker->chunkWordDocument($doc, $skipEmbed);
                $ok++;
            } catch (\Throwable $e) {
                $fail++;
                $this->newLine();
                $this->error("  Word {$doc->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Chunks: {$ok} documentos listos, {$fail} con error.");
    }
}
