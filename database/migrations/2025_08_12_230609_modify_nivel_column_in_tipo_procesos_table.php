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
        Schema::table('tipo_procesos', function (Blueprint $table) {
            $table->decimal('nivel', 3, 1)->change(); // Cambia de integer a decimal(3,1)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_procesos', function (Blueprint $table) {
            $table->integer('nivel')->change(); // Revierte a integer
        });
    }
};
