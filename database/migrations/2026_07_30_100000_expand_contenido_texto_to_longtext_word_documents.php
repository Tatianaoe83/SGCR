<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `word_documents` MODIFY `contenido_texto` LONGTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `word_documents` MODIFY `contenido_texto` TEXT NULL');
    }
};
