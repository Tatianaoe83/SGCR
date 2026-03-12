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
        Schema::table('elementos', function (Blueprint $table){
            $table->string('archivo_markdown')->nullable()->after('archivo_es_formato');
            $table->string('archivo_firmado')->nullable()->after('archivo_markdown');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table){
            $table->dropColumn(['archivo_markdown', 'archivo_firmado']);
        });
    }
};
