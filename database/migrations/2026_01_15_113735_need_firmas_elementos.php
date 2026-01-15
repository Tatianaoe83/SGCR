<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->boolean('esfirma')
                ->default(false)
                ->after('correo_agradecimiento');

            $table->dropColumn('estado_semaforo');
        });
    }

    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->dropColumn('esfirma');

            $table->string('estado_semaforo', 20)->nullable();
        });
    }
};
