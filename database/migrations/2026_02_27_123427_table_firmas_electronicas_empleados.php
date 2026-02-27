<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmas_electronicas', function (Blueprint $table) {
            $table->unsignedBigInteger('empleado_id')->primary();

            $table->string('path', 255);
            $table->string('mime', 50)->nullable();
            $table->char('hash', 64)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empleado_id')
                ->references('id_empleado')
                ->on('empleados')
                ->onDelete('cascade');
        });

        Schema::table('firmas', function (Blueprint $table) {
            $table->string('firma_snapshot_path', 255)->nullable()->after('evidencia_rechazo_path');
            $table->char('firma_snapshot_hash', 64)->nullable()->after('firma_snapshot_path');

            $table->ipAddress('firma_ip')->nullable()->after('firma_snapshot_hash');
            $table->string('firma_user_agent', 255)->nullable()->after('firma_ip');
        });
    }

    public function down(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->dropColumn([
                'firma_snapshot_path',
                'firma_snapshot_hash',
                'firma_ip',
                'firma_user_agent',
            ]);
        });

        Schema::dropIfExists('firmas_electronicas');
    }
};