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
            $table->string('nombre_firmante')->nullable();
            $table->string('puesto_firmante')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->dropColumn('nombre_firmante');
            $table->dropColumn('puesto_firmante');
        });
    }
};
