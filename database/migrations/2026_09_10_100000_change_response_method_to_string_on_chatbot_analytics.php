<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_analytics', function (Blueprint $table) {
            $table->string('response_method', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_analytics', function (Blueprint $table) {
            $table->enum('response_method', [
                'smart_index', 'ollama', 'fallback', 'integrated_search',
                'ollama_no_context', 'data_based_semantic', 'generic_fallback',
            ])->nullable()->change();
        });
    }
};
