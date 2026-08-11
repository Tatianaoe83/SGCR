<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El enum original solo permitía smart_index|ollama|fallback y truncaba
        // métodos reales (talk_lane_decision, etc.), rompiendo el historial del chat.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE chatbot_analytics MODIFY response_method VARCHAR(64) NOT NULL DEFAULT 'fallback'");
        } else {
            // SQLite / otros: intentar change vía schema builder si está disponible.
            Schema::table('chatbot_analytics', function ($table) {
                $table->string('response_method', 64)->default('fallback')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE chatbot_analytics MODIFY response_method VARCHAR(32) NOT NULL DEFAULT 'fallback'");
        }
    }
};
