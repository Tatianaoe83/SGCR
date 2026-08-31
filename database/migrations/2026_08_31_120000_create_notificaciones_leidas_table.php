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
        Schema::create('notificaciones_leidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->unsignedBigInteger('elemento_id');
            $table->timestamp('evento_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tipo', 'elemento_id'], 'notificaciones_leidas_unica');
            $table->index(['user_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones_leidas');
    }
};
