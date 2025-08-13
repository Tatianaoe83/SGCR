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
        Schema::create('tipo_procesos', function (Blueprint $table) {
            $table->id('id_tipo_proceso');
            $table->string('nombre');
            $table->decimal('nivel', 3, 1); // Permite valores como 1.0, 2.5, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_procesos');
    }
};
