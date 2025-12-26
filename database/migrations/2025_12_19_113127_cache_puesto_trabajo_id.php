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
        Schema::table('smart_indexes', function (Blueprint $table) {
            $table->unsignedBigInteger('cache_puesto_trabajo_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smart_indexes', function (Blueprint $table) {
            $table->dropColumn('cache_puesto_trabajo_id');
        });
    }
};
