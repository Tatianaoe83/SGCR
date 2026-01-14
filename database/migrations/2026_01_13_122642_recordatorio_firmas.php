<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->string('timer_recordatorio', 20)->default('Semanal')->after('estatus');

            $table->dateTime('next_reminder_at')->nullable()->after('timer_recordatorio');
            $table->dateTime('last_reminder_at')->nullable()->after('next_reminder_at');

            $table->index(['estatus', 'next_reminder_at'], 'idx_firmas_estatus_next');
            $table->index(['elemento_id'], 'idx_firmas_elemento');
        });
    }

    public function down(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->dropIndex('idx_firmas_estatus_next');
            $table->dropIndex('idx_firmas_elemento');

            $table->dropColumn(['timer_recordatorio', 'next_reminder_at', 'last_reminder_at']);
        });
    }
};

