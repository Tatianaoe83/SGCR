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
        Schema::table('firmas', function (Blueprint $table) {
            $table->json('evidencia_rechazo_path')->nullable()->after('comentario_rechazo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->dropColumn('evidencia_rechazo_path');
        });
    }
};
