<?php

namespace App\Jobs;

use App\Services\ChatbotLexiconLearner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AprenderLexicoBobJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;
    public int $uniqueFor = 120;

    public function __construct(
        public ?int $analyticsId = null,
        public int $days = 30
    ) {
    }

    public function uniqueId(): string
    {
        return 'bob-lexico-' . ($this->analyticsId ?? 'batch');
    }

    public function handle(ChatbotLexiconLearner $learner): void
    {
        $stats = $learner->learn($this->analyticsId, $this->days);
        Log::info('Bob léxico aprendido', $stats + [
            'analytics_id' => $this->analyticsId,
        ]);
    }
}
