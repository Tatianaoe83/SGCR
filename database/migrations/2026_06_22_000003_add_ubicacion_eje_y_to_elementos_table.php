<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->unsignedTinyInteger('ubicacion_eje_y')
                ->default(0)
                ->after('ubicacion_eje_x')
                ->comment('0=ambas filas, 1=CON, 2=AG (mapa industrial)');
        });
    }

    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->dropColumn('ubicacion_eje_y');
        });
    }
};
