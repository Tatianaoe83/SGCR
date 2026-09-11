<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_analytics', function (Blueprint $table) {
            // Métodos actuales superan VARCHAR corto / ENUM viejo (p.ej. elemento_meta_responsable).
            $table->string('response_method', 80)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_analytics', function (Blueprint $table) {
            $table->string('response_method', 20)->nullable()->change();
        });
    }
};
