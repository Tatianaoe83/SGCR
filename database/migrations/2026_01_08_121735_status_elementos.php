<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->enum('status', ['En Proceso', 'En Firmas', 'Publicado', 'Rechazado'])->default('En Proceso')->after('archivo_es_formato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
