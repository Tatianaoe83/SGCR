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
        Schema::table('elementos', function (Blueprint $table) {
            // Campo para almacenar IDs de usuarios seleccionados (JSON)
            $table->json('usuarios_correo')->nullable()->after('correo_agradecimiento');
            
            // Campo para almacenar correos libres adicionales (JSON)
            $table->json('correos_libres')->nullable()->after('usuarios_correo');
            
            // Campo para almacenar el estado del semáforo
            $table->string('estado_semaforo', 20)->nullable()->after('correos_libres');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elementos', function (Blueprint $table) {
            $table->dropColumn(['usuarios_correo', 'correos_libres', 'estado_semaforo']);
        });
    }
};
