<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_lexicon', function (Blueprint $table) {
            $table->id();
            $table->string('category', 40);
            $table->string('term', 191);
            $table->string('mapped_to', 191)->nullable();
            $table->string('label', 191)->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('hits')->default(1);
            $table->decimal('confidence', 5, 3)->default(0.500);
            $table->string('status', 20)->default('pending');
            $table->string('source', 20)->default('learned');
            $table->unsignedBigInteger('last_analytics_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['category', 'term']);
            $table->index(['category', 'status']);
            $table->index('last_analytics_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_lexicon');
    }
};
