<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->json('anotaciones_rechazo')->nullable()->after('evidencia_rechazo_path');
            $table->string('anotaciones_pdf_path')->nullable()->after('anotaciones_rechazo');
        });
    }

    public function down(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->dropColumn(['anotaciones_rechazo', 'anotaciones_pdf_path']);
        });
    }
};
