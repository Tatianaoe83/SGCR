<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_chunks', function (Blueprint $table) {
            // FULLTEXT solo funciona en MySQL con InnoDB moderno
            $table->fullText('content');
        });
    }

    public function down(): void
    {
        Schema::table('document_chunks', function (Blueprint $table) {
            $table->dropFullText(['content']);
        });
    }
};
