<?php

namespace App\Console\Commands;

use App\Jobs\AprenderLexicoBobJob;
use App\Services\ChatbotLexiconLearner;
use Illuminate\Console\Command;

class AprenderLexicoBobCommand extends Command
{
    protected $signature = 'chatbot:aprender-lexico
                            {--dias=30 : Ventana de analytics a revisar}
                            {--analytics= : Un chatbot_analytics.id concreto (p. ej. del feedback)}
                            {--sync : Ejecutar ahora, sin cola}';

    protected $description = 'Aprende saludos, coloquialismos, sinónimos y stopwords desde los chats (analytics)';

    public function handle(ChatbotLexiconLearner $learner): int
    {
        $days = max(1, (int) $this->option('dias'));
        $analyticsId = $this->option('analytics') !== null && $this->option('analytics') !== ''
            ? (int) $this->option('analytics')
            : null;

        if ($this->option('sync')) {
            $stats = $learner->learn($analyticsId, $days);
            $this->info('Escaneados: ' . $stats['scanned']);
            $this->info('Nuevos: ' . $stats['created'] . '  Actualizados: ' . $stats['updated'] . '  Activados: ' . $stats['activated']);

            return self::SUCCESS;
        }

        AprenderLexicoBobJob::dispatch($analyticsId, $days);
        $this->info('Job encolado. Usa --sync para correrlo ahora.');

        return self::SUCCESS;
    }
}
