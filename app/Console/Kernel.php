<?php

namespace App\Console;

use App\Console\Commands\SendMailRecordatorio;
use App\Models\Elemento;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use function Symfony\Component\Clock\now;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Indexación automática de documentos Word cada hora
        $schedule->command('chatbot:index-word-documents --only-new')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/word-indexing.log'));

        // Re-indexación completa semanal
        $schedule->command('chatbot:index-word-documents --force')
            ->weeklyOn(0, '01:00') // Domingos a la 1 AM
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/word-indexing-weekly.log'));

        // Limpieza diaria del índice
        $schedule->command('chatbot:clean-word-index')
            ->dailyAt('01:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/word-index-cleanup.log'));

        // Optimización diaria del índice
        $schedule->command('chatbot:optimize-index --period=24hours')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Optimización semanal profunda
        $schedule->command('chatbot:optimize-index --period=7days')
            ->weeklyOn(1, '03:00') // Lunes a las 3 AM
            ->withoutOverlapping()
            ->runInBackground();

        // Limpiar analytics antiguos (opcional)
        $schedule->call(function () {
            ChatbotAnalytics::where('created_at', '<', now()->subMonths(6))->delete();
        })->monthlyOn(1, '04:00');

        // Health check del sistema
        $schedule->call(function () {
            $health = [
                'ollama' => app(OllamaService::class)->healthCheck(),
                'database' => DB::connection()->getPdo() ? 'ok' : 'error',
                'cache' => Cache::put('health_check', now()) ? 'ok' : 'error'
            ];
            Cache::put('system_health', $health, 300); // 5 minutos
        })->everyFiveMinutes();

        $schedule->command('recordatorios:enviar')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
