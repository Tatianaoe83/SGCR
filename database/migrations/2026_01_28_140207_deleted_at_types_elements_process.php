<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_procesos', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('tipo_elementos', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('elementos', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('tipo_procesos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tipo_elementos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('elementos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
