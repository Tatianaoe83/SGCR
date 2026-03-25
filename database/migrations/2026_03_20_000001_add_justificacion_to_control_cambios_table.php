<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('control_cambios', function (Blueprint $table) {
            $table->text('Justificacion')->nullable()->after('Descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('control_cambios', function (Blueprint $table) {
            $table->dropColumn('Justificacion');
        });
    }
};
