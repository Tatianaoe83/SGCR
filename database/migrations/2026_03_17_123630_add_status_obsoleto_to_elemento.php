<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE elementos
            MODIFY COLUMN status ENUM(
                'En Proceso',
                'En Firmas',
                'Publicado',
                'Rechazado',
                'Obsoleto'
            ) NOT NULL DEFAULT 'En Proceso'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE elementos
            MODIFY COLUMN status ENUM(
                'En Proceso',
                'En Firmas',
                'Publicado',
                'Rechazado'
            ) NOT NULL DEFAULT 'En Proceso'
        ");
    }
};