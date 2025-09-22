<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         // Tabla para indexación inteligente
         Schema::create('smart_indexes', function (Blueprint $table) {
            $table->id();
            $table->text('original_query');
            $table->text('normalized_query');
            $table->json('keywords'); // Palabras clave extraídas
            $table->json('entities'); // Entidades identificadas
            $table->text('response');
            $table->integer('usage_count')->default(1);
            $table->decimal('confidence_score', 5, 3)->default(0);
            $table->json('similar_queries')->nullable(); // Queries similares
            $table->boolean('auto_generated')->default(true);
            $table->boolean('verified')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            // Crear índices usando SQL directo después de la creación de la tabla
            $table->index(['usage_count', 'verified']);
            $table->fullText(['original_query', 'normalized_query']);
        });

        // Tabla para métricas y análisis
        Schema::create('chatbot_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('query');
            $table->text('normalized_query');
            $table->enum('response_method', ['smart_index', 'ollama', 'fallback']);
            $table->text('response');
            $table->integer('response_time_ms');
            $table->boolean('user_satisfied')->nullable();
            $table->json('matched_keywords')->nullable();
            $table->decimal('similarity_score', 5, 3)->nullable();
            $table->string('session_id');
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['response_method', 'created_at']);
            $table->index('session_id');
        });

        // Tabla para feedback de usuarios
        Schema::create('chatbot_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('analytics_id');
            $table->boolean('helpful');
            $table->text('comment')->nullable();
            $table->enum('improvement_suggestion', ['more_detailed', 'more_accurate', 'faster', 'other'])->nullable();
            $table->timestamps();
            
            $table->foreign('analytics_id')->references('id')->on('chatbot_analytics')->onDelete('cascade');
        });

        // Crear índices con longitud limitada usando SQL directo
        DB::statement('CREATE INDEX smart_indexes_normalized_query_confidence_score_index ON smart_indexes (normalized_query(255), confidence_score)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices personalizados antes de eliminar las tablas
        DB::statement('DROP INDEX IF EXISTS smart_indexes_normalized_query_confidence_score_index ON smart_indexes');
        
        Schema::dropIfExists('chatbot_feedback');
        Schema::dropIfExists('chatbot_analytics');
        Schema::dropIfExists('smart_indexes');
    }
};
