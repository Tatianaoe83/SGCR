<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('word_documents', function (Blueprint $table) {
            $table->text('contenido_markdown')->nullable()->after('contenido_texto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('word_documents', function (Blueprint $table) {
            $table->dropColumn('contenido_markdown');
        });
    }
};
