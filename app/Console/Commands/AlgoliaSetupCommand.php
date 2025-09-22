<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordDocument;
use Algolia\AlgoliaSearch\Api\SearchClient;

class AlgoliaSetupCommand extends Command
{
    protected $signature = 'algolia:setup 
                            {--check : Solo verificar la configuración}
                            {--reindex : Reindexar todos los documentos}
                            {--clear : Limpiar el índice}';

    protected $description = 'Configurar y gestionar el índice de Algolia';

    public function handle()
    {
        $this->info('🔍 Configuración de Algolia Scout');
        $this->newLine();

        // Verificar configuración
        if ($this->option('check') || !$this->option('reindex') && !$this->option('clear')) {
            $this->checkConfiguration();
        }

        // Limpiar índice
        if ($this->option('clear')) {
            $this->clearIndex();
        }

        // Reindexar documentos
        if ($this->option('reindex')) {
            $this->reindexDocuments();
        }

        $this->newLine();
        $this->info('✅ Proceso completado');
    }

    private function checkConfiguration()
    {
        $this->info('📋 Verificando configuración...');
        $this->newLine();

        // Verificar driver
        $driver = config('scout.driver');
        $this->line("Driver Scout: <comment>{$driver}</comment>");

        // Verificar Algolia
        $appId = config('scout.algolia.id');
        $secret = config('scout.algolia.secret');

        if (empty($appId) || empty($secret)) {
            $this->error('❌ Algolia no está configurado correctamente');
            $this->line('');
            $this->line('Para configurar Algolia, agrega estas variables a tu archivo .env:');
            $this->line('');
            $this->line('SCOUT_DRIVER=algolia');
            $this->line('ALGOLIA_APP_ID=tu_app_id_aqui');
            $this->line('ALGOLIA_SECRET=tu_admin_api_key_aqui');
            return;
        }

        $this->line("App ID: <comment>{$appId}</comment>");
        $this->line("Secret configurado: <comment>" . (empty($secret) ? 'No' : 'Sí') . "</comment>");

        // Probar conexión
        try {
            $client = SearchClient::create($appId, $secret);
            
            // En la versión 4.x, usamos searchSingleIndex directamente
            $result = $client->searchSingleIndex('word_documents_index', [
                'query' => '',
                'hitsPerPage' => 0,
                'attributesToRetrieve' => []
            ]);

            $this->info('✅ Conexión exitosa con Algolia');
            $this->line("Registros en índice: <comment>{$result['nbHits']}</comment>");

        } catch (\Exception $e) {
            $this->error('❌ Error conectando con Algolia: ' . $e->getMessage());
        }

        // Verificar modelos
        $totalDocs = WordDocument::count();
        $searchableDocs = WordDocument::whereNotNull('contenido_texto')
            ->where('contenido_texto', '!=', '')
            ->count();

        $this->newLine();
        $this->line("Total documentos: <comment>{$totalDocs}</comment>");
        $this->line("Documentos indexables: <comment>{$searchableDocs}</comment>");
    }

    private function clearIndex()
    {
        if (!$this->confirm('¿Estás seguro de que quieres limpiar el índice de Algolia?')) {
            return;
        }

        $this->info('🗑️  Limpiando índice...');

        try {
            $appId = config('scout.algolia.id');
            $secret = config('scout.algolia.secret');

            if (empty($appId) || empty($secret)) {
                $this->error('❌ Algolia no está configurado');
                return;
            }

            $client = SearchClient::create($appId, $secret);
            $client->clearObjects('word_documents_index');

            $this->info('✅ Índice limpiado correctamente');

        } catch (\Exception $e) {
            $this->error('❌ Error limpiando el índice: ' . $e->getMessage());
        }
    }

    private function reindexDocuments()
    {
        $this->info('📚 Reindexando documentos...');

        try {
            $documents = WordDocument::whereNotNull('contenido_texto')
                ->where('contenido_texto', '!=', '')
                ->get();

            if ($documents->isEmpty()) {
                $this->warn('⚠️  No hay documentos para indexar');
                return;
            }

            $bar = $this->output->createProgressBar($documents->count());
            $bar->start();

            $indexed = 0;
            foreach ($documents as $document) {
                try {
                    if ($document->shouldBeSearchable()) {
                        $document->searchable();
                        $indexed++;
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error("Error indexando documento {$document->id}: " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("✅ {$indexed} documentos indexados correctamente");

        } catch (\Exception $e) {
            $this->error('❌ Error durante la reindexación: ' . $e->getMessage());
        }
    }
}
